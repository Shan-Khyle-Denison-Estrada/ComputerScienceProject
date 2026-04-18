<?php

namespace App\Http\Controllers\TabApprover;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TabApproverApplicationController extends Controller
{
    public function index(Request $request)
    {
        // FILTERING: 
        // 1. Specific Application Types
        // 2. Pending Application Status
        // 3. For Renewal -> SP Approved. For Change of Unit/Owner -> Reviewer Approved (Ignore SP Status).
        // 4. Tab Status is Pending or Null
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->whereIn('application_type', ['Renewal', 'Change of Unit', 'Change of Owner', 'Change of Owner (Deceased)', 'New Franchise'])
            ->where('status', 'Pending')
            ->where(function ($q) {
                // Rule 1: Renewal & New Franchise requires SP Approval
                $q->where(function ($sub) {
                    $sub->whereIn('application_type', ['Renewal', 'New Franchise'])
                        ->where('sp_status', 'Approved');
                })
                // Rule 2: Change of Unit & Owner bypass SP Approval but require Reviewer Approval
                ->orWhere(function ($sub) {
                    $sub->whereIn('application_type', ['Change of Unit', 'Change of Owner', 'Change of Owner (Deceased)'])
                        ->where('reviewer_status', 'Approved');
                });
            })
            ->where(function($q) {
                $q->where('tab_status', 'Pending')
                  ->orWhereNull('tab_status');
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

        return Inertia::render('TabApprover/Applications/Index', [
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

        return Inertia::render('TabApprover/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function showChangeOfUnit(Application $application)
    {
        abort_if($application->application_type !== 'Change of Unit', 404);

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
            'proposedUnits.make',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments'
        ]);

        $inspectionItems = InspectionItem::all();
        $unitInspections = UnitInspection::where('application_id', $application->id)->get();

        return Inertia::render('TabApprover/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
        ]);
    }

    public function showChangeOfOwner(Application $application)
    {
        abort_if($application->application_type !== 'Change of Owner' && $application->application_type !== 'Change of Owner (Deceased)', 404);

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
            'assessment.payments'
        ]);

        return Inertia::render('TabApprover/Applications/ShowChangeOfOwner', [
            'application' => $application,
        ]);
    }

    public function approve(Application $application)
    {
        // 1. Approve the application's tab status and set overarching status
        $application->update([
            'tab_status' => 'Approved',
            'status' => 'Approved'
        ]);
        
        // 2. Update the associated franchise depending on the application type
        if ($application->franchise) {
            if ($application->application_type === 'Renewal') {
                $application->franchise->update([
                    'status' => 'Renewed',
                    'date_issued' => now()->format('Y-m-d')
                ]);
            }
            // For Change of Unit/Owner, the admin/system might handle final data migration elsewhere
            // but the status goes to 'Approved' successfully.
        }

        return redirect()->route('tab_approver.applications.index')
                         ->with('success', "Application has been approved successfully by Tabulation.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'tab_status' => 'Rejected',
            'status' => 'Rejected',
            'remarks' => $request->remarks
        ]);

        return redirect()->route('tab_approver.applications.index')
                         ->with('success', "Application has been rejected.");
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
            'proposedUnits.make',
            'proposedUnits.zone'
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

        return Inertia::render('TabApprover/Applications/ShowNewFranchise', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }
}