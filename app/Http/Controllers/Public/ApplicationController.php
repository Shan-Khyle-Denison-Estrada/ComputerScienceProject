<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation; // Ensure this Model exists
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
        // Fetch requirements for the form
        $relevantGroups = ['Franchise Owner Account', 'General', 'New Franchise'];
        
        $requirements = EvaluationRequirement::where('is_active', true)
            ->whereIn('group', $relevantGroups)
            ->orderBy('group')
            ->get()
            ->groupBy('group');

        return Inertia::render('Apply', [
            'barangays' => Barangay::orderBy('name')->get(),
            'zones' => Zone::all(),
            'unitMakes' => UnitMake::orderBy('name')->get(),
            'requirements' => $requirements,
        ]);
    }

    public function store(Request $request)
    {
        // 1. Fetch requirements to build dynamic validation rules
        $relevantGroups = ['Franchise Owner Account', 'General', 'New Franchise'];
        $requiredDocs = EvaluationRequirement::where('is_active', true)
            ->whereIn('group', $relevantGroups)
            ->get();

        // 2. Define Base Rules
        $rules = [
            // Applicant
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'street_address' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',

            // Units
            'units' => 'required|array|min:1',
            'units.*.make_id' => 'required|exists:unit_makes,id',
            'units.*.zone_id' => 'required|exists:zones,id',
            'units.*.motor_number' => 'required|string|max:255',
            'units.*.chassis_number' => 'required|string|max:255',
            'units.*.model_year' => 'required|integer|min:1900|max:'.(date('Y')+1),
            
            // Unit Photos
            'units.*.unit_front_photo' => 'required|image|max:5120',
            'units.*.unit_back_photo' => 'required|image|max:5120',
            'units.*.unit_left_photo' => 'required|image|max:5120',
            'units.*.unit_right_photo' => 'required|image|max:5120',
// CHANGED: Allow documents (PDFs) for Certificates and Registrations
            'units.*.cr_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'units.*.or_photo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'units.*.franchise_certificate_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // Requirements Container (Relaxed validation)
            'requirement_files' => 'nullable|array', 
        ];

        // 3. Add Dynamic Rules for Specific Requirements
        foreach ($requiredDocs as $req) {
            // Enforce file upload for each active requirement
            $rules["requirement_files.{$req->id}"] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
        }

        // Validate
        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $referenceNumber = 'APP-' . date('Y') . '-' . strtoupper(Str::random(5));

            // A. Create Application
            $application = Application::create([
                'reference_number' => $referenceNumber,
                'user_id' => auth()->id() ?? null,
                'zone_id' => $validated['units'][0]['zone_id'], // Primary zone from first unit
                'application_type' => 'Franchise Owner Account',
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

            // B. Process Requirement Files
            if ($request->hasFile('requirement_files')) {
                foreach ($request->file('requirement_files') as $reqId => $file) {
                    // Only save if it matches a valid requirement in our list
                    if ($requiredDocs->contains('id', $reqId)) {
                        $path = $file->store('application_requirements', 'public');
                        
                        ApplicationEvaluation::create([
                            'application_id' => $application->id,
                            'requirement_id' => $reqId,
                            'file_path' => $path,
                            'is_compliant' => null,
                            'remarks' => 'Submitted by applicant'
                        ]);
                    }
                }
            }

            // C. Process Units
            foreach ($request->units as $unitData) {
                // Helper to safely store unit photos
                $storePhoto = function($field) use ($unitData) {
                    if (isset($unitData[$field]) && $unitData[$field] instanceof \Illuminate\Http\UploadedFile) {
                        return $unitData[$field]->store('application_units', 'public');
                    }
                    return null;
                };

                ProposedUnit::create([
                    'application_id' => $application->id,
                    'make_id' => $unitData['make_id'],
                    'zone_id' => $unitData['zone_id'],
                    'plate_number' => $unitData['plate_number'] ?? 'To Follow',
                    'motor_number' => $unitData['motor_number'],
                    'cr_number' => $unitData['cr_number'],
                    'chassis_number' => $unitData['chassis_number'],
                    'model_year' => $unitData['model_year'],
                    // Store Photos
                    'unit_front_photo' => $storePhoto('unit_front_photo'),
                    'unit_back_photo' => $storePhoto('unit_back_photo'),
                    'unit_left_photo' => $storePhoto('unit_left_photo'),
                    'unit_right_photo' => $storePhoto('unit_right_photo'),
                    'cr_photo' => $storePhoto('cr_photo'),
                    'or_photo' => $storePhoto('or_photo'),
                    'franchise_certificate_photo' => $storePhoto('franchise_certificate_photo'),
                ]);
            }

            DB::commit();

            return redirect()->route('home')->with('success', "Application submitted successfully! Ref No: $referenceNumber");

        } catch (\Exception $e) {
            DB::rollBack();
            // Return the specific error for debugging
            return back()->withErrors(['error' => 'Submission Failed: ' . $e->getMessage()]);
        }
    }
}