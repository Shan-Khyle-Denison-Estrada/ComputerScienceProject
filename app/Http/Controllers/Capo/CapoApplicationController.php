<?php

namespace App\Http\Controllers\Capo;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationStatusUpdated;

class CapoApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->whereIn('application_type', ['Renewal', 'Change of Unit', 'New Franchise']) // Include New Franchise applications
            ->where('status', 'Pending')
            ->where('inspector_status', 'Approved') // Must be approved by Inspector first
            ->where(function($q) {
                $q->where('capo_status', 'Pending')
                  ->orWhereNull('capo_status');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $applications = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Capo/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search']),
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

        return Inertia::render('Capo/Applications/ShowRenewal', [
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
            'proposedUnits.make', // <--- Added to load proposed unit data
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'franchise.complaints',
            'franchise.redFlags.nature'
        ]);

        $inspectionItems = InspectionItem::all();

        // Get inspections by application ID to review the proposed unit
        $unitInspections = UnitInspection::where('application_id', $application->id)->get();

        return Inertia::render('Capo/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
        ]);
    }

    public function approve(Application $application)
    {
        $application->update(['capo_status' => 'Approved']);
        
        // Send Email Notification
        if ($application->email) {
            Mail::to($application->email)->send(new ApplicationStatusUpdated($application, 'Approved', 'City Anti-Pollution Officer'));
        }

        return redirect()->route('capo.applications.index')
                         ->with('success', "Application has been approved by the City Anti-Pollution Officer.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        // CRITICAL CHANGE: Returns to inspector, NOT to the applicant.
        // Overall status stays 'Pending' so the applicant doesn't see 'Returned'
        $application->update([
            'inspector_status' => 'Pending', // Pushes it back to Inspector's queue
            'capo_status' => 'Pending',      // Resets CAPO state
            'remarks' => "CAPO returned to Inspector: " . $request->remarks
        ]);

        // Send Email Notification treating this rejection as a 'Returned' action
        if ($application->email) {
            Mail::to($application->email)->send(new ApplicationStatusUpdated($application, 'Returned', 'City Anti-Pollution Officer', $request->remarks));
        }

        return redirect()->route('capo.applications.index')
                         ->with('success', "Application has been returned to the Inspector for re-evaluation.");
    }

    public function showNewFranchise(Application $application)
    {
        // Strictly limit to New Franchise
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
            'proposedUnits.make', // <-- FIX: Load the proposed unit's make
            'proposedUnits.zone'  // <-- FIX: Load the proposed unit's zone
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

        return Inertia::render('Capo/Applications/ShowNewFranchise', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }
}