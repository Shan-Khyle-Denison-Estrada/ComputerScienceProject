<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\ApplicationAttachment;
use App\Models\Assessment;
use App\Models\Particular;
use App\Models\Franchise;
use App\Models\Ownership;
use App\Models\ActiveUnit;
use App\Models\Operator;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    // 1. List All Applications (Admin View)
    public function index(Request $request)
    {
        $query = Application::with(['user', 'franchise', 'assessment', 'attachments.requirement', 'inspections']);

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->search) {
             $query->where('id', 'like', "%{$request->search}%")
                   ->orWhereHas('user', function($q) use ($request){
                       $q->where('first_name', 'like', "%{$request->search}%")
                         ->orWhere('last_name', 'like', "%{$request->search}%");
                   });
        }

        // Fetch requirements for the "Manage Requirements" modal
        $requirements = ApplicationRequirement::all()->groupBy('application_type');

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['status', 'type', 'search']),
            'requirements' => $requirements,
        ]);
    }

    // 2. Create Application (Admin acting on behalf of user)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'franchise_id' => 'nullable|exists:franchises,id',
            'remarks' => 'nullable|string',
            'proposed_data' => 'nullable|array', 
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Create Application
            $application = Application::create([
                'user_id' => auth()->id(), // Admin is the creator in this context
                'franchise_id' => $validated['franchise_id'] ?? null,
                'type' => $validated['type'],
                'proposed_data' => $validated['proposed_data'] ?? null,
                'status' => 'pending',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Auto-Create Assessment (if applicable)
            if ($validated['type'] !== 'new_operator') {
                $particulars = Particular::where('group', $validated['type'])->get();

                if ($particulars->isNotEmpty()) {
                    $total = $particulars->sum('amount');
                    
                    $assessment = Assessment::create([
                        'franchise_id' => $validated['franchise_id'],
                        'assessment_date' => now(),
                        'assessment_due' => now()->addDays(30),
                        'total_amount_due' => $total,
                        'assessment_status' => 'pending',
                        'remarks' => 'Auto-generated for Application #' . $application->id
                    ]);

                    foreach ($particulars as $p) {
                        $assessment->particulars()->attach($p->id, [
                            'quantity' => 1,
                            'subtotal' => $p->amount
                        ]);
                    }

                    $application->update(['assessment_id' => $assessment->id]);
                }
            }
        });

        return redirect()->back()->with('success', 'Application created successfully.');
    }

    // 3. Upload Attachment (Admin uploading for the application)
    public function uploadAttachment(Request $request, Application $application)
    {
        $request->validate([
            'requirement_id' => 'required|exists:application_requirements,id',
            'file' => 'required|file|max:5120' // 5MB max
        ]);

        $path = $request->file('file')->store('application_attachments', 'public');

        ApplicationAttachment::updateOrCreate(
            [
                'application_id' => $application->id,
                'application_requirement_id' => $request->requirement_id
            ],
            ['file_path' => $path]
        );

        return back()->with('success', 'Document uploaded.');
    }

    // 4. Approve Application (The Core Logic)
    public function approve(Application $application)
    {
        // Guard: Assessment must be paid
        if ($application->assessment_id) {
            $application->load('assessment');
            if ($application->assessment->assessment_status !== 'paid') {
                return back()->with('error', 'Cannot approve. Assessment must be paid first.');
            }
        }

        DB::transaction(function () use ($application) {
            
            switch ($application->type) {
                case 'new_operator':
                    Operator::create([
                        'user_id' => $application->user_id,
                        'tin_number' => $application->proposed_data['tin'] ?? null 
                    ]);
                    $application->user->update(['role' => 'franchise_owner']);
                    break;

                case 'renewal':
                    // Logic to extend franchise validity
                    // $application->franchise->update([...]);
                    break;

                case 'transfer_ownership':
                    $newOperatorId = $application->proposed_data['new_operator_id'] ?? null;
                    if($newOperatorId && $application->franchise) {
                        $franchise = $application->franchise;
                        
                        Ownership::create([
                            'franchise_id' => $franchise->id,
                            'previous_operator_id' => $franchise->ownership_id,
                            'new_operator_id' => $newOperatorId,
                            'date_transferred' => now(),
                        ]);
                        
                        $franchise->update(['ownership_id' => $newOperatorId]);
                    }
                    break;

                case 'change_unit':
                    $newUnitId = $application->proposed_data['new_unit_id'] ?? null;
                    if($newUnitId && $application->franchise) {
                        $franchise = $application->franchise;

                        ActiveUnit::create([
                            'franchise_id' => $franchise->id,
                            'previous_unit_id' => $franchise->active_unit_id,
                            'new_unit_id' => $newUnitId,
                            'date_changed' => now(),
                            'remarks' => 'Approved Application #' . $application->id
                        ]);

                        $franchise->update(['active_unit_id' => $newUnitId]);
                    }
                    break;
                    
                case 'new_franchise':
                    // Logic for new franchise creation
                    break;
            }

            $application->update(['status' => 'approved']);
        });

        return back()->with('success', 'Application approved and processed.');
    }

// Update to handle Category (Evaluation vs Inspection)
    public function storeRequirement(Request $request)
    {
        $validated = $request->validate([
            'application_type' => 'required|string',
            'name' => 'required|string|max:255',
            'category' => 'required|in:evaluation,inspection', // New validation
        ]);

        ApplicationRequirement::create($validated);

        return back()->with('success', 'Requirement added successfully.');
    }

    // NEW: Save Inspection Results
    public function saveInspection(Request $request, Application $application)
    {
        $validated = $request->validate([
            'inspections' => 'required|array',
            'inspections.*.requirement_id' => 'required|exists:application_requirements,id',
            'inspections.*.score' => 'nullable|numeric',
            'inspections.*.remarks' => 'nullable|string',
        ]);

        foreach ($validated['inspections'] as $item) {
            ApplicationInspection::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'application_requirement_id' => $item['requirement_id']
                ],
                [
                    'score' => $item['score'],
                    'remarks' => $item['remarks']
                ]
            );
        }

        return back()->with('success', 'Inspection results saved.');
    }

    // 6. Manage Requirements (Destroy)
    public function destroyRequirement(ApplicationRequirement $requirement)
    {
        $requirement->delete();
        return back()->with('success', 'Requirement removed.');
    }
}