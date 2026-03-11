<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InspectorApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->whereIn('application_type', ['Renewal', 'Change of Unit', 'New Franchise'])
            ->where('status', 'Pending')
            ->where(function($q) {
                $q->where('inspector_status', 'Pending')
                  ->orWhereNull('inspector_status');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $applications = $query->latest()->paginate(8)->withQueryString();

        return Inertia::render('Inspector/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search']),
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

        return Inertia::render('Inspector/Applications/ShowRenewal', [
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
            'proposedUnits.make', // <--- Added this to load proposed unit
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'franchise.complaints',
            'franchise.redFlags.nature'
        ]);

        $inspectionItems = InspectionItem::all();

        // Get inspections by application ID to ensure we fetch the proposed unit inspections
        $unitInspections = UnitInspection::where('application_id', $application->id)->get();

        return Inertia::render('Inspector/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
        ]);
    }

    public function inspectUnit(Request $request, Application $application)
    {
        $request->validate([
            'inspection_item_id' => 'required|exists:inspection_items,id',
            'rating' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        // 1. Determine if we are inspecting a Proposed Unit or an Existing Unit
        $proposedUnit = $application->proposedUnits()->latest()->first();
        
        $matchConditions = [
            'application_id' => $application->id,
            'inspection_item_id' => $request->inspection_item_id,
        ];

        // 2. Assign the correct ID column based on what exists
        if ($proposedUnit) {
            $matchConditions['proposed_unit_id'] = $proposedUnit->id;
        } elseif ($application->franchise && $application->franchise->currentActiveUnit) {
            $matchConditions['unit_id'] = $application->franchise->currentActiveUnit->new_unit_id;
        } else {
            return redirect()->back()->withErrors(['error' => 'No unit found to inspect for this application.']);
        }

        // 3. Save the inspection
        UnitInspection::updateOrCreate(
            $matchConditions,
            [
                'rating' => $request->rating,
                'remarks' => $request->remarks ?? "Marked as {$request->rating}.",
                // 'inspected_by' => auth()->id(), // Uncomment if you track the specific inspector
            ]
        );

        return redirect()->back()->with('success', 'Inspection item updated.');
    }

    public function approve(Application $application)
    {
        $application->update(['inspector_status' => 'Approved']);
        
        return redirect()->route('inspector.applications.index')
                         ->with('success', "Unit Inspection has been approved.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'inspector_status' => 'Rejected',
            'status' => 'Returned',
            'remarks' => $request->remarks
        ]);

        return redirect()->route('inspector.applications.index')
                         ->with('success', "Application has been returned for physical unit modifications.");
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
            'proposedUnits' // <-- Crucial: load the proposed units
        ]);

        $inspectionItems = InspectionItem::all();

        $currentUnitId = null;
        $unitInspections = [];
        
        // Check for Proposed Unit first
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

        return Inertia::render('Inspector/Applications/ShowNewFranchise', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }
}