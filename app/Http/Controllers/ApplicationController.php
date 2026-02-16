<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ProposedUnit;
use App\Models\EvaluationRequirement;
use App\Models\InspectionItem;
use App\Models\Zone;
use App\Models\UnitMake;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    // --- PUBLIC FACING: APPLY FORM ---
    public function create()
    {
        return Inertia::render('Apply', [
            'zones' => Zone::all(),
            'unitMakes' => UnitMake::all(),
            'barangays' => Barangay::all(),
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validation
        $validated = $request->validate([
            // Applicant
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'contact_number' => 'required',
            'street_address' => 'required',
            'barangay' => 'required',
            'tin_number' => 'nullable',
            'zone_id' => 'required|exists:zones,id',
            
            // Unit (Array support for future multi-unit, but currently handled as single in UI)
            'unit_make_id' => 'required|exists:unit_makes,id',
            'motor_number' => 'required',
            'chassis_number' => 'required',
            'plate_number' => 'nullable',
            'year_model' => 'required|numeric',
            
            // Photos (Validation handled as files)
            'unit_front_photo' => 'nullable|image', 
            // ... add other photo validations
        ]);

        DB::transaction(function () use ($request) {
            // 2. Create Application Header
            $application = Application::create([
                'reference_number' => 'APP-' . now()->year . '-' . Str::upper(Str::random(6)),
                'user_id' => auth()->id() ?? null,
                'application_type' => 'New Franchise Owner', // Dynamic based on route?
                'status' => 'Pending',
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'street_address' => $request->street_address,
                'barangay' => $request->barangay,
                'tin_number' => $request->tin_number,
                'zone_id' => $request->zone_id,
            ]);

            // 3. Handle File Uploads & Create Proposed Unit
            // Note: In production, use Storage::putFile()
            $unitData = [
                'application_id' => $application->id,
                'make_id' => $request->unit_make_id,
                'model_year' => $request->year_model,
                'motor_number' => $request->motor_number,
                'chassis_number' => $request->chassis_number,
                'plate_number' => $request->plate_number,
            ];

            // Helper to upload if present
            foreach(['unit_front_photo', 'unit_back_photo', 'unit_left_photo', 'unit_right_photo', 'cr_photo'] as $field) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store('applications/units', 'public');
                    $unitData[$field] = $path;
                }
            }

            ProposedUnit::create($unitData);
        });

        return redirect()->route('home')->with('success', 'Application submitted successfully!');
    }

    // --- ADMIN: MANAGE APPLICATIONS ---
    public function index()
    {
        return Inertia::render('Admin/Applications/Index', [
            'applications' => Application::with(['zone', 'proposedUnits'])->latest()->paginate(10),
            'requirements' => EvaluationRequirement::all(), // For sidebar management
        ]);
    }

    public function show($id)
    {
        $application = Application::with([
            'proposedUnits.make', 
            'zone', 
            'franchise', 
            'evaluations', // Load saved checklist
            'proposedUnits.inspections' // Load saved inspections
        ])->findOrFail($id);

        return Inertia::render('Admin/Applications/Show', [
            'application' => $application,
            // Configuration for the checklist tabs
            'evaluationRequirements' => EvaluationRequirement::where('group', 'like', "%{$application->application_type}%")
                                        ->orWhere('group', 'General')->get(),
            'inspectionItems' => InspectionItem::all(),
        ]);
    }

    // --- ADMIN: ACTIONS ---
    public function updateStatus(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        
        $request->validate([
            'status' => 'required',
            'remarks' => 'nullable|string'
        ]);

        $application->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Application status updated.');
    }

    public function storeEvaluation(Request $request, $id)
    {
        // Save checklist items
        // Logic to sync `application_evaluations` table
        // ...
        return back();
    }
}