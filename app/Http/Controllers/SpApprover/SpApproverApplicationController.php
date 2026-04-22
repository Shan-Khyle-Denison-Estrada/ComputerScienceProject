<?php

namespace App\Http\Controllers\SpApprover;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationStatusUpdated;

class SpApproverApplicationController extends Controller
{
    public function index(Request $request)
    {
        // STRICT FILTERING: 
        // 1. Renewal & New Franchise & Pending Application Status
        // 2. Reviewer Approved (Which inherently implies Evaluator, Inspector, CAPO, and Paid Assessment are cleared)
        // 3. SP Status is Pending or Null
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->whereIn('application_type', ['Renewal', 'New Franchise'])
            ->where('status', 'Pending')
            ->where('reviewer_status', 'Approved') 
            ->where(function($q) {
                $q->where('sp_status', 'Pending')
                  ->orWhereNull('sp_status');
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

        $applications = $query->paginate(10)->withQueryString();

        return Inertia::render('SpApprover/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search', 'type', 'sortField', 'sortDirection']),
        ]);
    }

    public function showRenewal(Application $application)
    {
        abort_if($application->application_type !== 'Renewal', 404);

        // Clear unread notifications for this specific application for the current user
        $notifications = auth()->user()->unreadNotifications
            ->where('data.application_id', $application->id);

        if ($notifications->isNotEmpty()) {
            $notifications->markAsRead();
        }

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

        return Inertia::render('SpApprover/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function approve(Application $application)
    {
        $application->update(['sp_status' => 'Approved']);

        // Send Email Notification
        if ($application->email) {
            Mail::to($application->email)->send(new ApplicationStatusUpdated($application, 'Approved', 'SP Approver'));
        }
        
        return redirect()->route('sp_approver.applications.index')
        ->with('success', "Application has been approved by the Sangguniang Panlungsod.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'sp_status' => 'Rejected',
            'status' => 'Rejected',
            'remarks' => $request->remarks
        ]);

        // Send Email Notification
        if ($application->email) {
            Mail::to($application->email)->send(new ApplicationStatusUpdated($application, 'Rejected', 'SP Approver', $request->remarks));
        }

        return redirect()->route('sp_approver.applications.index')
        ->with('success', "Application has been rejected by the Sangguniang Panlungsod.");
    }

    public function showNewFranchise(Application $application)
    {
        abort_if($application->application_type !== 'New Franchise', 404);

        // Clear unread notifications for this specific application for the current user
        $notifications = auth()->user()->unreadNotifications
            ->where('data.application_id', $application->id);

        if ($notifications->isNotEmpty()) {
            $notifications->markAsRead();
        }

        $application->load([
            'user',
            'franchise.currentOwnership.newOwner.user', 
            'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', 
            'zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'proposedUnits.make', // <-- Added .make
            'proposedUnits.zone'  // <-- Added .zone
        ]);

        $inspectionItems = InspectionItem::all();

        $currentUnitId = null;
        $unitInspections = [];

        $proposedUnit = $application->proposedUnits->last();
        
        if ($proposedUnit) {
            $currentUnitId = $proposedUnit->id;
            $unitInspections = UnitInspection::where('proposed_unit_id', $currentUnitId)
                ->where('application_id', $application->id) 
                ->get();
        } 
        // Fallback to active unit if no proposed unit exists
        elseif ($application->franchise && $application->franchise->currentActiveUnit) {
            $currentUnitId = $application->franchise->currentActiveUnit->new_unit_id;
            $unitInspections = UnitInspection::where('unit_id', $currentUnitId)
                ->where('application_id', $application->id) 
                ->get();
        }

        return Inertia::render('SpApprover/Applications/ShowNewFranchise', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }
}