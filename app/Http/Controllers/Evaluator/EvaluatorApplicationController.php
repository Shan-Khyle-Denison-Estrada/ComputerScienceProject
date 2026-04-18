<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use App\Models\Complaint;
use App\Models\RedFlag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EvaluatorApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->where('status', 'Pending')
            ->where(function($q) {
                $q->where('evaluator_status', 'Pending')
                  ->orWhereNull('evaluator_status');
            });

        $search = $request->input('search');
        $type = $request->input('type');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        // 1. Handle Advanced Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // 2. Handle Application Type Filter
        if ($type) {
            $query->where('application_type', $type);
        }

        // 3. Handle Sorting
        $query->when($sortField, function ($q) use ($sortField, $sortDirection) {
            if ($sortField === 'applicant_name') {
                $q->orderBy('first_name', $sortDirection)
                  ->orderBy('last_name', $sortDirection);
            } else {
                $allowedSorts = ['reference_number', 'application_type', 'status'];
                if (in_array($sortField, $allowedSorts)) {
                    $q->orderBy($sortField, $sortDirection);
                }
            }
        }, function ($q) {
            $q->latest();
        });

        $applications = $query->paginate(7)->withQueryString();

        return Inertia::render('Evaluator/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search', 'type', 'sortField', 'sortDirection']),
        ]);
    }

    public function showRenewal(Application $application)
    {
        abort_if($application->application_type !== 'Renewal', 404);

        $application->load([
            'user',
            'franchise.currentOwnership.newOwner.user', 
            'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', 
            'zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'franchise.complaints',
            'franchise.redFlags.nature'
        ]);

        $inspectionItems = InspectionItem::all();

        $currentUnitId = null;
        $unitInspections = [];
        if ($application->franchise && $application->franchise->currentActiveUnit) {
            $currentUnitId = $application->franchise->currentActiveUnit->new_unit_id;
            $unitInspections = UnitInspection::where('unit_id', $currentUnitId)
                ->where('application_id', $application->id) 
                ->get();
        }

        return Inertia::render('Evaluator/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function showChangeOfOwner(Application $application)
    {
        abort_if(!in_array($application->application_type, [
            'Change of Owner', 
            'Change of Owner (Deceased)'
        ]), 404);

        $application->load([
            'user',
            'franchise',
            'franchise.currentOwnership.newOwner.user',
            'franchise.currentActiveUnit.newUnit.make',
            'franchise.zone',
            'franchise.complaints',
            'franchise.redFlags.nature',
            'zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments'
        ]);

        $inspectionItems = InspectionItem::all();

        $currentUnitId = null;
        $unitInspections = [];
        if ($application->franchise && $application->franchise->currentActiveUnit) {
            $currentUnitId = $application->franchise->currentActiveUnit->new_unit_id;
            $unitInspections = UnitInspection::where('unit_id', $currentUnitId)
                ->where('application_id', $application->id) 
                ->get();
        }

        return Inertia::render('Evaluator/Applications/ShowChangeOfOwner', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function showChangeOfUnit(Application $application)
    {
        abort_if($application->application_type !== 'Change of Unit', 404);

        $application->load([
            'user',
            'franchise.currentOwnership.newOwner.user',
            'franchise.currentActiveUnit.newUnit.make',
            'franchise.zone',
            'franchise.complaints',
            'franchise.redFlags.nature',
            'zone',
            'proposedUnits.make', // <--- CHANGED THIS
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments'
        ]);

        $inspectionItems = InspectionItem::all();
        
        $unitInspections = UnitInspection::where('application_id', $application->id)->get();

        return Inertia::render('Evaluator/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
        ]);
    }

    public function showFranchiseOwnerAccount(Application $application)
    {
        // Assuming your DB stores this type as 'New Franchise'. Adjust if it is strictly 'Franchise Owner Account'
        abort_if(!in_array($application->application_type, ['New Franchise', 'Franchise Owner Account']), 404);

        $application->load([
            'user',
            'zone',
            'proposedUnits.make',
            'proposedUnits.zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments'
        ]);

        $inspectionItems = InspectionItem::all();
        
        // Fetch inspections tied directly to this application
        $unitInspections = UnitInspection::where('application_id', $application->id)->get();

        return Inertia::render('Evaluator/Applications/ShowFranchiseOwnerAccount', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
        ]);
    }

    public function evaluateDocument(Request $request, Application $application)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:application_evaluations,id',
            'status' => 'required|in:Approved,Rejected,Pending',
            'remarks' => 'nullable|string'
        ]);

        $evaluation = ApplicationEvaluation::findOrFail($request->evaluation_id);
        
        $isCompliant = match($request->status) {
            'Approved' => 1,
            'Rejected' => 0,
            default => null,
        };

        $evaluation->update([
            'is_compliant' => $isCompliant,
            'remarks' => $request->remarks ?? ($isCompliant === 0 ? 'Document rejected.' : 'Document accepted.')
        ]);

        return redirect()->back()->with('success', 'Document evaluation updated successfully.');
    }

    public function resolveComplaint(Request $request, Application $application, Complaint $complaint)
    {
        $complaint->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Complaint marked as resolved.');
    }

    public function resolveRedFlag(Request $request, Application $application, RedFlag $redFlag)
    {
        $redFlag->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Red Flag marked as resolved.');
    }

    public function approve(Application $application)
    {
        // FIX: Only count unresolved complaints for renewals
        if ($application->application_type === 'Renewal' && $application->franchise && $application->franchise->complaints()->where('status', '!=', 'resolved')->count() > 3) {
            return redirect()->back()->withErrors(['error' => 'Cannot approve renewal: Franchise has more than 3 unresolved complaints.']);
        }

        $updateData = [
            'evaluator_status' => 'Approved',
        ];

        if ($application->application_type === 'Franchise Owner Account') {
            $updateData['status'] = 'Approved';
        }

        if ($application->application_type === 'New Driver') {
            $updateData['status'] = 'Approved';
        }

        $application->update($updateData);
        
        return redirect()->route('evaluator.applications.index')
            ->with('success', "Evaluation has been approved successfully.");
    }
    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'evaluator_status' => 'Rejected',
            'status' => 'Rejected', // Terminal state
            'remarks' => $request->remarks
        ]);

        return redirect()->route('evaluator.applications.index')
            ->with('success', "Application has been rejected.");
    }

    public function returnApp(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'evaluator_status' => 'Returned', 
            'status' => 'Returned', // Sent back for correction
            'remarks' => $request->remarks
        ]);

        return redirect()->route('evaluator.applications.index')
            ->with('success', "Application has been returned for corrections.");
    }

    public function showNewFranchise(Application $application)
    {
        // Strictly limit to New Franchise
        abort_if($application->application_type !== 'New Franchise', 404);

        $application->load([
            'user',
            'franchise.currentOwnership.newOwner.user', 
            'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', 
            'zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'proposedUnits.make', // <--- Load the Make relationship
            'proposedUnits.zone'  // <--- Load the Zone relationship
        ]);

        $inspectionItems = InspectionItem::all();

        $currentUnitId = null;
        $unitInspections = [];
        
        // 1. Check if a Proposed Unit exists (For New Franchise / Change of Unit)
        $proposedUnit = $application->proposedUnits->last();

        if ($proposedUnit) {
            $currentUnitId = $proposedUnit->id;
            $unitInspections = UnitInspection::where('proposed_unit_id', $currentUnitId)
                ->where('application_id', $application->id) 
                ->get();
        } 
        // 2. Fallback to existing active unit (For Renewals / Change of Owner)
        elseif ($application->franchise && $application->franchise->currentActiveUnit) {
            $currentUnitId = $application->franchise->currentActiveUnit->new_unit_id;
            $unitInspections = UnitInspection::where('unit_id', $currentUnitId)
                ->where('application_id', $application->id) 
                ->get();
        }

        return Inertia::render('Evaluator/Applications/ShowNewFranchise', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function showNewDriver(Application $application)
    {
        abort_if($application->application_type !== 'New Driver', 404);

        // Clear unread notifications for this specific application for the current user
        $notifications = auth()->user()->unreadNotifications
            ->where('data.application_id', $application->id);

        if ($notifications->isNotEmpty()) {
            $notifications->markAsRead();
        }

        // Load the evaluations, franchise, and zone definitions
        $application->load([
            'user', 
            'evaluations.requirement',
            'franchise.zone', // Loads the related franchise and its zone
            'zone' // Fallback in case zone is directly on the application
        ]);

        return Inertia::render('Evaluator/Applications/ShowNewDriver', [
            'application' => $application
        ]);
    }
}