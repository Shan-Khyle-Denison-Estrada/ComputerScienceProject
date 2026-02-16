<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Barangay;
use App\Models\EvaluationRequirement;
use App\Models\ProposedUnit;
use App\Models\UnitMake;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function create()
    {
        return Inertia::render('Apply', [
            'barangays' => Barangay::orderBy('name')->get(),
            'zones' => Zone::all(),
            'unitMakes' => UnitMake::orderBy('name')->get(),
            'requirements' => EvaluationRequirement::where('is_active', true)
                ->orderBy('group')
                ->get()
                ->groupBy('group'),
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validate the Request
        $validated = $request->validate([
            // Applicant Details
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
            'contact_number' => 'required|string|max:20',
            'tin_number' => 'nullable|string|max:50',
            'street_address' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            
            // Multiple Units Validation
            'units' => 'required|array|min:1',
            'units.*.make_id' => 'required|exists:unit_makes,id',
            'units.*.zone_id' => 'required|exists:zones,id', // Zone is now per unit
            'units.*.motor_number' => 'required|string|max:255',
            'units.*.chassis_number' => 'required|string|max:255',
            'units.*.plate_number' => 'nullable|string|max:255',
            'units.*.model_year' => 'required|integer|min:1900|max:'.(date('Y')+1),
            
            // Photos
            'units.*.unit_front_photo' => 'required|image|max:5120',
            'units.*.unit_back_photo' => 'required|image|max:5120',
            'units.*.unit_left_photo' => 'required|image|max:5120',
            'units.*.unit_right_photo' => 'required|image|max:5120',
            'units.*.cr_photo' => 'required|image|max:5120',
            'units.*.or_photo' => 'required|image|max:5120',
            'units.*.franchise_certificate_photo' => 'nullable|image|max:5120', // Optional if new
        ]);

        try {
            DB::beginTransaction();

            $referenceNumber = 'APP-' . date('Y') . '-' . strtoupper(Str::random(5));

            // 2. Create Application Header
            $application = Application::create([
                'reference_number' => $referenceNumber,
                'user_id' => auth()->id() ?? null,
                // We use the first unit's zone as the "Primary Zone" for the header, or leave null
                'zone_id' => $validated['units'][0]['zone_id'], 
                'application_type' => 'New Franchise',
                'status' => 'Pending',
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'contact_number' => $validated['contact_number'],
                'email' => $validated['email'],
                'tin_number' => $validated['tin_number'],
                'street_address' => $validated['street_address'],
                'barangay' => $validated['barangay'],
                'city' => $validated['city'],
                'submitted_at' => now(),
            ]);

            // 3. Process Each Unit
            foreach ($request->units as $unitData) {
                
                $photoPaths = [];
                $photoFields = [
                    'unit_front_photo', 'unit_back_photo', 'unit_left_photo', 'unit_right_photo', 
                    'cr_photo', 'or_photo', 'franchise_certificate_photo'
                ];

                foreach ($photoFields as $field) {
                    if (isset($unitData[$field]) && $unitData[$field] instanceof \Illuminate\Http\UploadedFile) {
                        $photoPaths[$field] = $unitData[$field]->store('application_units', 'public');
                    } else {
                        $photoPaths[$field] = null;
                    }
                }

                ProposedUnit::create([
                    'application_id' => $application->id,
                    'make_id' => $unitData['make_id'],
                    'zone_id' => $unitData['zone_id'], // Saved per unit
                    'plate_number' => $unitData['plate_number'] ?? 'To Follow',
                    'motor_number' => $unitData['motor_number'],
                    'chassis_number' => $unitData['chassis_number'],
                    'model_year' => $unitData['model_year'],
                    'unit_front_photo' => $photoPaths['unit_front_photo'],
                    'unit_back_photo' => $photoPaths['unit_back_photo'],
                    'unit_left_photo' => $photoPaths['unit_left_photo'],
                    'unit_right_photo' => $photoPaths['unit_right_photo'],
                    'cr_photo' => $photoPaths['cr_photo'],
                    'or_photo' => $photoPaths['or_photo'],
                    'franchise_certificate_photo' => $photoPaths['franchise_certificate_photo'],
                ]);
            }

            DB::commit();

            return redirect()->route('home')->with('success', "Application submitted! Ref No: $referenceNumber");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }
}