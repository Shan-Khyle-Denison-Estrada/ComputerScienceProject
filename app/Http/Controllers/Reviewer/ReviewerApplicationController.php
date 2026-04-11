<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewerApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->where('application_type', '!=', 'Franchise Owner Account') 
            ->where('status', 'Pending')
            ->where('evaluator_status', 'Approved')
            ->where(function ($q) {
                $q->where(function ($subQuery) {
                    // Include New Franchise here
                    $subQuery->whereIn('application_type', ['Renewal', 'Change of Unit', 'Change of Owner', 'New Franchise'])
                             ->where('inspector_status', 'Approved')
                             ->where('capo_status', 'Approved');
                })
                ->orWhere('application_type', 'Change of Owner'); 
            })
            // THE FIX: Allow applications with Paid assessments OR no assessments at all
            ->where(function($q) {
                $q->whereDoesntHave('assessment')
                  ->orWhereHas('assessment', function($subQuery) {
                      $subQuery->where('assessment_status', 'Paid');
                  });
            })
            ->where(function($q) {
                $q->where('reviewer_status', 'Pending')
                  ->orWhereNull('reviewer_status');
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

        return Inertia::render('Reviewer/Applications/Index', [
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

        return Inertia::render('Reviewer/Applications/ShowRenewal', [
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
            'zone',
            'proposedUnits.make',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'franchise.complaints',
            'franchise.redFlags.nature'
        ]);

        $inspectionItems = InspectionItem::all();
        $unitInspections = UnitInspection::where('application_id', $application->id)->get();

        return Inertia::render('Reviewer/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
        ]);
    }

    public function showChangeOfOwner(Application $application)
    {
        abort_if($application->application_type !== 'Change of Owner', 404);

        $application->load([
            'user',
            'franchise.currentOwnership.newOwner.user', 
            'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', 
            'zone',
            // Using the standard proposed_owners or proposed_ownerships table.
            // If the relationship isn't defined, we rely on the generic 'user' 
            // tied to the application for the applicant's details.
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'franchise.complaints',
            'franchise.redFlags.nature'
        ]);

        return Inertia::render('Reviewer/Applications/ShowChangeOfOwner', [
            'application' => $application,
        ]);
    }

    public function approve(Application $application)
    {
        $application->update(['reviewer_status' => 'Approved']);
        
        return redirect()->route('reviewer.applications.index')
                         ->with('success', "Application has been approved by the Reviewer.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'reviewer_status' => 'Rejected',
            'status' => 'Rejected',
            'remarks' => $request->remarks
        ]);

        return redirect()->route('reviewer.applications.index')
                         ->with('success', "Application has been returned.");
    }

    public function showNewFranchise(Application $application)
    {
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
            'proposedUnits'
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

        return Inertia::render('Reviewer/Applications/ShowNewFranchise', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }
}