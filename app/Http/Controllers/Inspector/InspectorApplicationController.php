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
            ->whereIn('application_type', ['Renewal', 'Change of Unit'])
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

        $applications = $query->latest()->paginate(10)->withQueryString();

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
            'rating' => 'required|string', // Removed hardcoded Pass/Fail
            'remarks' => 'nullable|string'
        ]);

        $unitId = $application->franchise->currentActiveUnit->new_unit_id;

        UnitInspection::updateOrCreate(
            [
                'application_id' => $application->id,
                'unit_id' => $unitId,
                'inspection_item_id' => $request->inspection_item_id,
            ],
            [
                'rating' => $request->rating,
                'remarks' => $request->remarks ?? "Marked as {$request->rating}.",
                'inspected_by' => auth()->id(),
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
}