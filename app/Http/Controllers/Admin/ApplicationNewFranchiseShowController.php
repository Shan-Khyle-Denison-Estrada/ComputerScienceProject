<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitMake;
use App\Models\Zone;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationNewFranchiseShowController extends Controller
{
    public function __invoke(Application $application)
    {
        // Load the specific relationships required to review a new franchise,
        // specifically adding assessments, payments, and unit inspections.
        $application->load([
            'user',
            'proposedUnits.make',
            'proposedUnits.zone',
            'proposedUnits.inspections',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
        ]);
        
        $user = auth()->user();
        $isEncoder = strtolower($user->role->value) === 'encoder';

        $operatorExists = \App\Models\Operator::where('tin_number', $application->tin_number)->exists();

        return Inertia::render('Admin/Applications/NewFranchiseShow', [
            'application' => $application,
            'inspectionItems' => InspectionItem::all(),
            'zones' => Zone::orderBy('description')->get(),
            'unitMakes' => UnitMake::orderBy('name')->get(),
            'isEncoder' => $isEncoder,
            'operatorExists' => $operatorExists,
        ]);
    }

    public function updateInspection(Request $request, Application $application)
    {
        // Validate incoming payload
        $request->validate([
            'unit_id' => 'required', // This represents the proposed_unit_id from the frontend
            'inspection_item_id' => 'required',
            'status' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        // Create or update the inspection record
        UnitInspection::updateOrCreate(
            [
                'proposed_unit_id' => $request->unit_id,
                'inspection_item_id' => $request->inspection_item_id,
            ],
            [
                'rating' => $request->status,
                'remarks' => $request->remarks,
                'application_id' => $application->id,
            ]
        );

        return redirect()->back()->with('success', 'Unit inspection updated successfully.');
    }
}