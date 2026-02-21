<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use App\Models\Complaint;
use App\Models\RedFlag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApplicationRenewalShowController extends Controller
{
    public function show(Application $application)
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
            // NEW: Load the complaints and red flags to display in the tabs
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

        return Inertia::render('Admin/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
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
            'unit_id' => 'required|exists:units,id',
            'status' => 'required|string',
            'remarks' => 'nullable|string'
        ]);

        UnitInspection::updateOrCreate(
            [
                'application_id' => $application->id,
                'unit_id' => $request->unit_id,
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
        // FIX: Only count unresolved complaints
        if ($application->franchise && $application->franchise->complaints()->where('status', '!=', 'resolved')->count() > 3) {
            return redirect()->back()->withErrors(['error' => 'Cannot approve renewal: Franchise has more than 3 unresolved complaints.']);
        }

        $application->update(['status' => 'Approved']);
        return redirect()->back()->with('success', 'Application approved. You can now finalize the renewal.');
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

    public function finalizeApplication(Request $request, Application $application)
    {
        // FIX: Only count unresolved complaints
        if ($application->franchise && $application->franchise->complaints()->where('status', '!=', 'resolved')->count() > 3) {
            return redirect()->back()->withErrors(['error' => 'Cannot finalize renewal: Franchise has more than 3 unresolved complaints.']);
        }

        $request->validate([
            'new_date_issued' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $application) {
            $franchise = $application->franchise;

            $franchise->update([
                'date_issued' => $request->new_date_issued,
            ]);

            $application->update([
                'status' => 'Completed',
                'remarks' => 'Franchise Renewal finalized successfully. ' . ($request->remarks ?? ''),
            ]);
        });

        return redirect()->back()->with('success', 'Renewal finalized and Franchise extended successfully!');
    }

    // NEW: Resolve Complaint
    public function resolveComplaint(Request $request, Application $application, Complaint $complaint)
    {
        $complaint->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Complaint marked as resolved.');
    }

    // NEW: Resolve Red Flag
    public function resolveRedFlag(Request $request, Application $application, RedFlag $redFlag)
    {
        $redFlag->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Red Flag marked as resolved.');
    }
}