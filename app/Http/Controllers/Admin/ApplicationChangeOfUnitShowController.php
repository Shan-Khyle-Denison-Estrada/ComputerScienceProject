<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationChangeOfUnitShowController extends Controller
{
    public function show(Application $application)
    {
        abort_if($application->application_type !== 'Change of Unit', 404);

        $application->load([
            'user',
            'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', 
            'zone',
            'proposedUnits.make', 
            'proposedUnits.unitInspections',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments'
        ]);

        $inspectionItems = InspectionItem::all();

        return Inertia::render('Admin/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems
        ]);
    }

    public function updateEvaluation(Request $request, Application $application)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:application_evaluations,id',
            'status' => 'required|in:Approved,Rejected,Pending',
            'remarks' => 'nullable|string'
        ]);

        $isCompliant = null;
        if ($request->status === 'Approved') $isCompliant = true;
        elseif ($request->status === 'Rejected') $isCompliant = false;

        ApplicationEvaluation::where('id', $request->evaluation_id)
            ->where('application_id', $application->id)
            ->update([
                'is_compliant' => $isCompliant,
                'remarks' => $request->remarks
            ]);

        return redirect()->back();
    }

    public function updateInspection(Request $request, Application $application)
    {
        $request->validate([
            'inspection_item_id' => 'required|exists:inspection_items,id',
            'status' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        $proposedUnit = $application->proposedUnits()->first();

        if (!$proposedUnit) {
            return redirect()->back()->withErrors(['error' => 'No proposed unit found to inspect.']);
        }

        UnitInspection::updateOrCreate(
            [
                'proposed_unit_id' => $proposedUnit->id,
                'inspection_item_id' => $request->inspection_item_id,
            ],
            [
                'rating' => $request->status,
                'remarks' => $request->remarks,
            ]
        );

        return redirect()->back();
    }

    public function approveApplication(Application $application)
    {
        $application->update(['status' => 'Approved']);
        return redirect()->back()->with('success', 'Application approved.');
    }

    public function rejectApplication(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'status' => 'Rejected',
            'remarks' => $request->remarks
        ]);

        return redirect()->back()->with('success', 'Application rejected.');
    }

    public function returnApplication(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'status' => 'Returned',
            'remarks' => $request->remarks
        ]);
        
        return redirect()->back()->with('success', 'Application returned for compliance.');
    }
}