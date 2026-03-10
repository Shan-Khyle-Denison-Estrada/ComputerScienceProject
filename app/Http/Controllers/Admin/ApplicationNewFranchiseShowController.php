<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\Operator;
use App\Models\User;
use App\Models\Franchise;
use App\Models\Ownership;
use App\Models\Unit;
use App\Models\ActiveUnit;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ApplicationNewFranchiseShowController extends Controller
{
    public function show(Application $application)
    {
        abort_if($application->application_type !== 'New Franchise', 404);

        $user = auth()->user();
        $isEncoder = strtolower($user->role->value) === 'encoder';

        $application->load([
            'user',
            'zone',
            'proposedUnits.make',
            'proposedUnits.zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments'
        ]);

        return Inertia::render('Admin/Applications/ShowNewFranchise', [
            'application' => $application,
            'isEncoder' => $isEncoder,
            'zones' => Zone::orderBy('name')->get(),
        ]);
    }

    public function updateEvaluation(Request $request, Application $application)
    {
        $request->validate([
            'evaluations' => 'required|array',
            'evaluations.*.id' => 'required|exists:application_evaluations,id',
            'evaluations.*.is_compliant' => 'required|boolean',
            'evaluations.*.remarks' => 'nullable|string',
        ]);

        foreach ($request->evaluations as $evalData) {
            ApplicationEvaluation::where('id', $evalData['id'])
                ->update([
                    'is_compliant' => $evalData['is_compliant'],
                    'remarks' => $evalData['remarks'] ?? null,
                ]);
        }

        $allCompliant = !collect($request->evaluations)->contains('is_compliant', false);
        
        $application->update([
            'evaluator_status' => $allCompliant ? 'Passed' : 'Failed',
        ]);

        return redirect()->back()->with('success', 'Evaluation updated successfully.');
    }

    public function finalizeApplication(Request $request, Application $application)
    {
        $request->validate([
            'mtfrb_case_no' => 'required|string',
            'franchise_number' => 'required|string|unique:franchises,franchise_number',
            'date_issued' => 'required|date',
            'valid_until' => 'required|date',
            'plate_number' => 'required|string',
            'zone_id' => 'required|exists:zones,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Resolve or Create User and Operator profiles
            $user = $application->user;
            if (!$user) {
                $user = User::where('email', $application->email)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => "{$application->first_name} {$application->last_name}",
                        'email' => $application->email,
                        'password' => Hash::make(Str::random(12)), 
                        'role' => 'franchise_owner',
                        'status' => 'active',
                    ]);
                }
            }

            $operator = Operator::firstOrCreate(
                ['user_id' => $user->id],
                ['tin_number' => $application->tin_number]
            );

            // 2. Create the Franchise
            $franchise = Franchise::create([
                'franchise_number' => $request->franchise_number,
                'mtfrb_case_no' => $request->mtfrb_case_no,
                'zone_id' => $request->zone_id,
                'status' => 'Active',
                'issue_date' => $request->date_issued,
                'valid_until' => $request->valid_until,
            ]);

            // 3. Bind Ownership
            $ownership = Ownership::create([
                'franchise_id' => $franchise->id,
                'new_operator_id' => $operator->id,
                'previous_operator_id' => null, // Net new!
                'date_transferred' => $request->date_issued,
            ]);
            $franchise->update(['ownership_id' => $ownership->id]);

            // 4. Create Unit & Active Unit Log
            $proposed = $application->proposedUnits->first();
            if ($proposed) {
                $unit = Unit::create([
                    'make_id' => $proposed->make_id,
                    'motor_number' => $proposed->motor_number,
                    'chassis_number' => $proposed->chassis_number,
                    'model_year' => $proposed->model_year,
                    'plate_number' => $request->plate_number,
                    'unit_front_photo' => $proposed->unit_front_photo,
                    'unit_back_photo' => $proposed->unit_back_photo,
                    'unit_left_photo' => $proposed->unit_left_photo,
                    'unit_right_photo' => $proposed->unit_right_photo,
                ]);

                $activeUnit = ActiveUnit::create([
                    'franchise_id' => $franchise->id,
                    'new_unit_id' => $unit->id,
                    'date_changed' => $request->date_issued,
                    'remarks' => 'Initial Unit for New Franchise',
                ]);
                
                $franchise->update(['active_unit_id' => $activeUnit->id]);
            }

            // 5. Finalize App
            $application->update([
                'status' => 'Completed',
                'franchise_id' => $franchise->id,
                'remarks' => 'New Franchise officially finalized and activated.',
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'New Franchise has been finalized and recorded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Finalization failed: ' . $e->getMessage()]);
        }
    }
}