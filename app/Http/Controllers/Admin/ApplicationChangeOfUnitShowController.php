<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use App\Models\UnitMake;
use App\Models\Unit;
use App\Models\ActiveUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApplicationChangeOfUnitShowController extends Controller
{
    public function show(Application $application)
    {
        abort_if($application->application_type !== 'Change of Unit', 404);

        $user = auth()->user();
        $isEncoder = strtolower($user->role->value) === 'encoder';

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
        $unitMakes = UnitMake::orderBy('name', 'asc')->get();

        // NEW: Automatically detect if the proposed unit already exists by Plate Number
        $proposedUnit = $application->proposedUnits()->first();
        $unitExists = false;
        $existingUnit = null;
        
        if ($proposedUnit && $proposedUnit->plate_number) {
            // Change exists() to first() to get the actual unit's data (including photos)
            $existingUnit = Unit::where('plate_number', $proposedUnit->plate_number)->first();
            $unitExists = $existingUnit ? true : false;
        }

        return Inertia::render('Admin/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitMakes' => $unitMakes,
            'isEncoder' => $isEncoder,
            'unitExists' => $unitExists, // <-- Pass it to the view
            'existingUnit' => $existingUnit,
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
        $application->update(['status' => 'Completed']);
        return redirect()->back()->with('success', 'Application approved. You can now finalize the unit change.');
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
        $request->validate([
            'make_id' => 'required|exists:unit_makes,id',
            'model_year' => 'required|numeric',
            'plate_number' => 'required|string',
            'motor_number' => 'required|string',
            'chassis_number' => 'required|string',
            'change_date' => 'required|date',
            'remarks' => 'nullable|string',
            'front_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'back_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'left_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'right_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $proposedUnit = $application->proposedUnits()->first();

        // Resolve photo paths
        $frontPhoto = $request->hasFile('front_photo') ? $request->file('front_photo')->store('units/photos', 'public') : $proposedUnit->unit_front_photo;
        $backPhoto = $request->hasFile('back_photo') ? $request->file('back_photo')->store('units/photos', 'public') : $proposedUnit->unit_back_photo;
        $leftPhoto = $request->hasFile('left_photo') ? $request->file('left_photo')->store('units/photos', 'public') : $proposedUnit->unit_left_photo;
        $rightPhoto = $request->hasFile('right_photo') ? $request->file('right_photo')->store('units/photos', 'public') : $proposedUnit->unit_right_photo;

        DB::transaction(function () use ($request, $application, $frontPhoto, $backPhoto, $leftPhoto, $rightPhoto) {
            
            // FIX: Search by Plate Number specifically
            $newUnit = Unit::updateOrCreate(
                [
                    'plate_number' => $request->plate_number, // Search key
                ],
                [
                    'make_id' => $request->make_id,
                    'model_year' => $request->model_year,
                    'motor_number' => $request->motor_number,
                    'chassis_number' => $request->chassis_number,
                    'unit_front_photo' => $frontPhoto,
                    'unit_back_photo' => $backPhoto,
                    'unit_left_photo' => $leftPhoto,
                    'unit_right_photo' => $rightPhoto,
                ]
            );

            $franchise = $application->franchise;
            
            $previousUnitId = null;
            if ($franchise->active_unit_id) {
                $previousUnitId = $franchise->currentActiveUnit->new_unit_id ?? null;
            }

            // Create the historical active unit log pointing to the found/created unit
            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id' => $newUnit->id,
                'previous_unit_id' => $previousUnitId,
                'date_changed' => $request->change_date,
                'remarks' => $request->remarks ?? 'Change of Unit Finalized',
            ]);

            // Assign the new active unit to the franchise
            $franchise->update([
                'active_unit_id' => $activeUnit->id,
            ]);

            // Finally, close out the Application lifecycle
            $application->update([
                'status' => 'Completed',
                'remarks' => 'Change of Unit has been officially finalized.',
            ]);
        });

        return redirect()->back()->with('success', 'Change of Unit finalized and activated successfully!');
    }
}