<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EvaluationRequirement;
use App\Models\InspectionItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    public function index()
    {
        // 1. Fetch Applications and map to Frontend structure
        $applications = Application::latest('submitted_at')
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'reference_no' => $app->reference_number,
                    'type' => $app->application_type,
                    // FIX: Parse the string to Carbon before formatting
                    'date_submitted' => $app->submitted_at ? Carbon::parse($app->submitted_at)->format('M d, Y') : 'N/A',
                    'status' => $app->status,
                    'applicant' => [
                        'first_name' => $app->first_name,
                        'last_name' => $app->last_name,
                        'email' => $app->email,
                        // Ensure photo path is correct if it exists
                        'photo' => $app->user_photo ? '/storage/' . $app->user_photo : null,
                    ]
                ];
            });

        // 2. Fetch Requirements
        $evalReqs = EvaluationRequirement::all()->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->group 
            ];
        });

        $inspReqs = InspectionItem::all()->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'options' => is_array($item->rating_options) ? implode(', ', $item->rating_options) : $item->rating_options
            ];
        });

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
            'evaluationRequirements' => $evalReqs,
            'inspectionRequirements' => $inspReqs
        ]);
    }

    public function show($id)
    {
        $app = Application::with([
            'proposedUnits.make', 
            'proposedUnits.inspections', 
            'evaluations.requirement', 
            'zone'
        ])->findOrFail($id);

        // 1. Map Application to Vue Structure
        $applicationData = [
            'id' => $app->id,
            'reference_no' => $app->reference_number,
            'status' => $app->status,
            'type' => $app->application_type,
            'date_submitted' => $app->submitted_at ? Carbon::parse($app->submitted_at)->format('M d, Y') : 'N/A',
            'applicant' => [
                'first_name' => $app->first_name,
                'last_name' => $app->last_name,
                'email' => $app->email,
                'contact_number' => $app->contact_number,
                'address' => "{$app->street_address}, {$app->barangay}, {$app->city}",
                'tin' => $app->tin_number,
                'photo' => $app->user_photo ? '/storage/' . $app->user_photo : null,
            ],
            'units' => $app->proposedUnits->map(function($unit) {
                return [
                    'id' => $unit->id,
                    'make' => $unit->make->name,
                    'motor_no' => $unit->motor_number,
                    'chassis_no' => $unit->chassis_number,
                    'plate_no' => $unit->plate_number,
                    'photos' => [
                        'front' => $unit->unit_front_photo,
                        'back' => $unit->unit_back_photo,
                        'side' => $unit->unit_left_photo // Adjust based on your columns
                    ],
                    // Map existing inspection results
                    'inspections' => $unit->inspections->pluck('rating', 'inspection_item_id')
                ];
            }),
            'zone' => $app->zone,
            // Merge Uploaded Docs with Requirements
            'evaluations' => $this->getMergedEvaluations($app) 
        ];

        return Inertia::render('Admin/Applications/Show', [
            'application' => $applicationData,
            'inspectionItems' => InspectionItem::all()->map(function($item){
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'options' => $item->rating_options // Ensure this is cast to array in Model
                ];
            })
        ]);
    }

    // Helper to merge defined requirements with uploaded files
    private function getMergedEvaluations($app)
    {
        // Get requirements relevant to this app type
        $requirements = EvaluationRequirement::where('is_active', true)
            ->where(function($q) use ($app) {
                $q->where('group', $app->application_type)
                  ->orWhere('group', 'General');
            })->get();

        return $requirements->map(function($req) use ($app) {
            $uploaded = $app->evaluations->firstWhere('requirement_id', $req->id);
            return [
                'id' => $req->id,
                'name' => $req->name,
                'status' => $uploaded ? ($uploaded->is_compliant ? 'Compliant' : 'Submitted') : 'Missing',
                'file_url' => $uploaded ? '/storage/' . $uploaded->file_path : null,
                'remarks' => $uploaded ? $uploaded->remarks : null
            ];
        });
    }

    // Action: Save Single Inspection Item
    public function updateInspection(Request $request, $id)
    {
        $request->validate([
            'unit_id' => 'required|exists:proposed_units,id',
            'item_id' => 'required|exists:inspection_items,id',
            'rating' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        UnitInspection::updateOrCreate(
            [
                'proposed_unit_id' => $request->unit_id,
                'inspection_item_id' => $request->item_id
            ],
            [
                'rating' => $request->rating,
                'remarks' => $request->remarks
            ]
        );

        return back()->with('success', 'Inspection updated.');
    }

    // Action: Return Application
    public function returnApplication(Request $request, $id)
    {
        $app = Application::findOrFail($id);
        
        $request->validate(['remarks' => 'required|string']);

        $app->update([
            'status' => 'Returned',
            'remarks' => $request->remarks,
            'reviewed_at' => now()
        ]);

        return back()->with('success', 'Application returned to applicant.');
    }

    // 1. Update Status (e.g., Pending -> For Inspection, or Reject)
    public function updateStatus(Request $request, Application $application)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        $application->update([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'],
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Application status updated to {$validated['status']}.");
    }

    // 2. Store Inspection Results (Pass/Fail for each unit's items)
    public function storeInspection(Request $request, Application $application)
    {
        $validated = $request->validate([
            'inspections' => 'required|array', // Structure: [ unit_id => [ item_id => 'Pass' ] ]
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['inspections'] as $unitId => $items) {
                foreach ($items as $itemId => $result) {
                    UnitInspection::updateOrCreate(
                        [
                            'proposed_unit_id' => $unitId, 
                            'inspection_item_id' => $itemId
                        ],
                        [
                            'rating' => $result,
                            'remarks' => null 
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Inspection results saved.');
    }

    // 3. Approve & Generate Franchise (The Big One)
    public function approve(Application $application)
    {
        if ($application->status === 'Approved') {
            return back()->with('error', 'Application is already approved.');
        }

        try {
            DB::beginTransaction();

            // A. Create/Find Operator Profile
            // If application has a linked user, use them. Otherwise, logic might be needed to create a user.
            // Assuming user_id exists for now as per your flow.
            $operator = Operator::firstOrCreate(
                ['user_id' => $application->user_id],
                ['tin_number' => $application->tin_number]
            );

            // B. Process Units & Franchise
            // Note: Currently assumes 1 Application = 1 Franchise (even with multiple units, usually 1 active)
            // Or multiple units = multiple franchises? 
            // For simplicity based on your schema: 1 Application -> 1 Franchise -> 1 Active Unit (first one)
            // If you support fleets, you might loop this to create multiple franchises.
            
            // 1. Create Franchise Header
            $franchise = Franchise::create([
                'zone_id' => $application->zone_id,
                'date_issued' => now(),
                'status' => 'active',
            ]);

            // 2. Create Real Unit from Proposed Unit (Take the first one for now)
            $proposedUnit = $application->proposedUnits->first();
            
            $unit = Unit::create([
                'make_id' => $proposedUnit->make_id,
                'plate_number' => $proposedUnit->plate_number,
                'motor_number' => $proposedUnit->motor_number,
                'chassis_number' => $proposedUnit->chassis_number,
                'model_year' => $proposedUnit->model_year,
                'unit_front_photo' => $proposedUnit->unit_front_photo,
                'unit_back_photo' => $proposedUnit->unit_back_photo,
                'unit_left_photo' => $proposedUnit->unit_left_photo,
                'unit_right_photo' => $proposedUnit->unit_right_photo,
            ]);

            // 3. Link Ownership History
            $ownership = Ownership::create([
                'franchise_id' => $franchise->id,
                'new_operator_id' => $operator->id,
                'date_transferred' => now(),
            ]);

            // 4. Link Active Unit History
            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id' => $unit->id,
                'date_changed' => now(),
                'remarks' => 'Initial Franchise Issuance',
            ]);

            // 5. Update Franchise Current Pointers
            $franchise->update([
                'ownership_id' => $ownership->id,
                'active_unit_id' => $activeUnit->id,
            ]);

            // 6. Mark Application Approved
            $application->update([
                'status' => 'Approved',
                'franchise_id' => $franchise->id,
                'reviewed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.applications.index')->with('success', 'Application approved and Franchise generated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Approval failed: ' . $e->getMessage()]);
        }
    }

    // --- MANAGE REQUIREMENTS ---

    public function storeRequirement(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:evaluation,inspection',
            'id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'type' => 'required_if:category,evaluation|nullable|string',
            'options' => 'required_if:category,inspection|nullable|string',
        ]);

        if ($request->category === 'evaluation') {
            EvaluationRequirement::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'group' => $request->type, 'is_active' => true]
            );
        } else {
            $optionsArray = array_map('trim', explode(',', $request->options));
            InspectionItem::updateOrCreate(
                ['id' => $request->id],
                ['name' => $request->name, 'rating_options' => $optionsArray]
            );
        }

        return back()->with('success', 'Requirement saved successfully.');
    }

    public function destroyRequirement($type, $id)
    {
        if ($type === 'evaluation') {
            EvaluationRequirement::destroy($id);
        } else {
            InspectionItem::destroy($id);
        }
        return back()->with('success', 'Requirement deleted.');
    }
}