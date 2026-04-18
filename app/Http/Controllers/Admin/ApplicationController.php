<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EvaluationRequirement;
use App\Models\InspectionItem;
use App\Models\Zone;
use App\Models\UnitMake;
use App\Models\User;
use App\Models\Franchise;
use App\Models\ProposedUnit;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Mail\InitialApplicationCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');
        $sortField = $request->input('sortField', 'submitted_at');
        $sortDirection = $request->input('sortDirection', 'desc');

        $user = auth()->user();
        $isEncoder = strtolower($user->role->value ?? $user->role) === 'encoder';

        // 1. Fetch Applications and map to Frontend structure via Pagination
        $applications = Application::query()
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('reference_number', 'like', "%{$search}%")
                      ->orWhere('application_type', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($type, function($query, $type) {
                $query->where('application_type', $type);
            })
            ->when($isEncoder, function($query) {
                // ENCODERS: Force to show ONLY Approved applications
                $query->where('status', 'Approved');
            }, function($query) use ($status) {
                // ADMINS: Allow filtering by any status
                if ($status) {
                    $query->where('status', $status);
                }
            })
            ->when($sortField === 'applicant', function($query) use ($sortDirection) {
                $query->orderBy('last_name', $sortDirection)
                      ->orderBy('first_name', $sortDirection);
            }, function($query) use ($sortField, $sortDirection) {
                $allowedSorts = ['reference_number', 'application_type', 'status', 'submitted_at'];
                if (in_array($sortField, $allowedSorts)) {
                    $query->orderBy($sortField, $sortDirection);
                } else {
                    $query->orderBy('submitted_at', 'desc');
                }
            })
            ->paginate(6)
            ->withQueryString()
            ->through(function ($app) {
                return [
                    'id' => $app->id,
                    'reference_no' => $app->reference_number,
                    'type' => $app->application_type,
                    'date_submitted' => $app->submitted_at ? Carbon::parse($app->submitted_at)->format('M d, Y') : 'N/A',
                    'status' => $app->status,
                    'applicant' => [
                        'first_name' => $app->first_name,
                        'last_name' => $app->last_name,
                        'email' => $app->email,
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

        // 3. FETCH DATA FOR THE MODALS
        $zones = Zone::select('id', 'description', 'color')->get();
        $unitMakes = UnitMake::select('id', 'name')->get();
        
        // Fetch Operators (Using User Model with franchise_owner role)
        $operators = User::where('role', 'franchise_owner')
            ->select('id', 'first_name', 'last_name')
            ->get();

        // Fetch Franchises to allow transferring owner (Deceased scenario)
        $franchises = Franchise::with('currentActiveUnit.newUnit')->get()->map(function($f) {
            return [
                'id' => $f->id,
                // Make sure to use 'franchise_number' based on your Franchise model's fillable properties
                'franchise_number' => $f->franchise_number, 
                'unit' => [
                    // Safely access the deeply nested plate number using PHP's nullsafe operator (?->)
                    'plate_number' => $f->currentActiveUnit?->newUnit?->plate_number ?? 'N/A'
                ]
            ];
        });

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
            'evaluationRequirements' => $evalReqs,
            'inspectionRequirements' => $inspReqs,
            'filters' => $request->only(['search', 'status', 'type', 'sortField', 'sortDirection']),
            'isEncoder' => $isEncoder,
            
            // Add Modal Data Props here
            'zones' => $zones,
            'unitMakes' => $unitMakes,
            'operators' => $operators,
            'franchises' => $franchises
        ]);
    }

    public function show($id)
    {

    // Clear unread notifications for this specific application for the current user
        $notifications = auth()->user()->unreadNotifications
            ->where('data.application_id', $application->id);

        if ($notifications->isNotEmpty()) {
            $notifications->markAsRead();
        }
        
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
        try {
            if ($type === 'evaluation') {
                EvaluationRequirement::destroy($id);
            } else {
                InspectionItem::destroy($id);
            }
            return back()->with('success', 'Requirement deleted.');

        } catch (\Illuminate\Database\QueryException $e) {
            // Check for Integrity constraint violation (SQLSTATE 23000)
            if ($e->getCode() == 23000) {
                return back()->with('error', 'Cannot delete this requirement. It is currently linked to existing application records.');
            }
            
            return back()->with('error', 'An error occurred while trying to delete the requirement.');
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'application_type' => 'required|string',
            'owner_mode' => 'required|in:new,existing',
            'existing_operator_id' => 'nullable|exists:users,id',
            'new_owner_email' => 'required_if:owner_mode,new|email|nullable',
        ]);

        DB::beginTransaction();
        try {
            $settings = SystemSetting::first();
            $fiscalYear = $settings->current_fiscal_year ?? date('Y');
            $referenceNumber = 'APP-' . $fiscalYear . '-' . strtoupper(Str::random(6));

            $applicantData = [];
            if ($request->owner_mode === 'existing') {
                $user = User::with('operator')->findOrFail($request->existing_operator_id);
                $applicantData = [
                    'user_id' => $user->id,
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'contact_number' => $user->contact_number,
                    'tin_number' => $user->operator ? $user->operator->tin_number : null,
                    'street_address' => $user->street_address,
                    'barangay' => $user->barangay,
                    'city' => $user->city,
                    'province' => $user->province,
                ];
            } else {
                $applicantData = [
                    'first_name' => $request->new_owner_first_name,
                    'middle_name' => $request->new_owner_middle_name,
                    'last_name' => $request->new_owner_last_name,
                    'email' => $request->new_owner_email,
                    'contact_number' => $request->new_owner_contact,
                    'tin_number' => $request->new_owner_tin,
                    'street_address' => $request->new_owner_address,
                    'barangay' => $request->new_owner_barangay,
                    'city' => $request->new_owner_city,
                    'province' => $request->new_owner_province,
                ];
            }

            // Create Application - NO UNITS ARE SAVED HERE ANYMORE
            $application = Application::create(array_merge($applicantData, [
                'reference_number' => $referenceNumber,
                'application_type' => $request->application_type,
                'franchise_id' => $request->selected_franchise_id,
                'status' => 'Initial',
                'remarks' => $request->remarks,
                'submitted_at' => now(),
            ]));

            $signedUrl = URL::temporarySignedRoute(
                'application.complete', 
                now()->addDays(7), 
                ['application' => $application->id]
            );

            if ($application->email) {
                Mail::to($application->email)->send(new InitialApplicationCreated($application, $signedUrl));
            }

            DB::commit();

            return back()->with('success', 'Initial application created. An email with a secure link has been sent to the applicant.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create application: ' . $e->getMessage()]);
        }
    }
}