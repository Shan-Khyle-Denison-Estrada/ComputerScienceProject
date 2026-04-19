<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EvaluationRequirement;
use App\Models\Zone;
use App\Models\UnitMake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApplicationCompletionController extends Controller
{
    public function edit(Application $application)
    {
        if ($application->status !== 'Initial') {
            abort(403, 'This application has already been submitted or is currently being processed.');
        }

        $requirements = EvaluationRequirement::where('group', $application->application_type)
            ->where('is_active', true)
            ->get();

        $zones = Zone::select('id', 'description', 'color')->get();
        $unitMakes = UnitMake::select('id', 'name')->get();

        return Inertia::render('CompleteApplication', [
            'application' => $application,
            'requirements' => $requirements,
            'zones' => $zones,
            'unitMakes' => $unitMakes,
        ]);
    }

    public function update(Request $request, Application $application)
    {
        if ($application->status !== 'Initial') {
            abort(403, 'This application has already been submitted.');
        }

        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        DB::beginTransaction();
        try {
            // 1. Process Requirements
            foreach ($request->file('documents', []) as $requirementId => $file) {
                $path = $file->store('applications/requirements', 'public');
                $application->evaluations()->create([
                    'requirement_id' => $requirementId,
                    'file_path' => $path,
                    'remarks' => null
                ]);
            }

            // 2. Process Proposed Units (If New Franchise)
            if ($application->application_type === 'New Franchise' && $request->has('units')) {
                foreach ($request->units as $index => $unitData) {
                    // Check if the exact name exists. If not, auto-create it.
                    $makeId = null;
                    if (!empty($unitData['make_name'])) {
                        $unitMake = UnitMake::firstOrCreate(
                            ['name' => trim($unitData['make_name'])]
                        );
                        $makeId = $unitMake->id;
                    }

                    $frontPhoto = $request->file("units.{$index}.unit_front_photo") ? $request->file("units.{$index}.unit_front_photo")->store('units/photos', 'public') : null;
                    $backPhoto = $request->file("units.{$index}.unit_back_photo") ? $request->file("units.{$index}.unit_back_photo")->store('units/photos', 'public') : null;
                    $leftPhoto = $request->file("units.{$index}.unit_left_photo") ? $request->file("units.{$index}.unit_left_photo")->store('units/photos', 'public') : null;
                    $rightPhoto = $request->file("units.{$index}.unit_right_photo") ? $request->file("units.{$index}.unit_right_photo")->store('units/photos', 'public') : null;
                    $crPhoto = $request->file("units.{$index}.cr_photo") ? $request->file("units.{$index}.cr_photo")->store('units/documents', 'public') : null;
                    $orPhoto = $request->file("units.{$index}.or_photo") ? $request->file("units.{$index}.or_photo")->store('units/documents', 'public') : null;

                    $application->proposedUnits()->create([
                        'make_id' => $makeId,
                        'zone_id' => $unitData['zone_id'] ?? null,
                        'model_year' => $unitData['model_year'] ?? null,
                        'plate_number' => $unitData['plate_number'] ?? null,
                        'motor_number' => $unitData['motor_number'] ?? null,
                        'chassis_number' => $unitData['chassis_number'] ?? null,
                        'cr_number' => $unitData['cr_number'] ?? null,
                        'unit_front_photo' => $frontPhoto,
                        'unit_back_photo' => $backPhoto,
                        'unit_left_photo' => $leftPhoto,
                        'unit_right_photo' => $rightPhoto,
                        'cr_photo' => $crPhoto,
                        'or_photo' => $orPhoto,
                    ]);
                }
            }

            $application->update([
                'status' => 'Pending',
                'submitted_at' => now(),
            ]);

            // --- AUTO-GENERATE ASSESSMENT FOR NEW FRANCHISE ---
            $assessment = null; // Initialize variable for the email
            
            if ($application->application_type === 'New Franchise') {
                // Using absolute paths so you don't have to worry about missing imports at the top
                $particulars = \App\Models\Particular::where('group', 'new_franchise')->get();

                if ($particulars->isNotEmpty()) {
                    $totalAmountDue = $particulars->sum('amount');
                    $deadlineDate = now()->addDays(15);

                    $assessment = \App\Models\Assessment::create([
                        'application_id'    => $application->id,
                        'franchise_id'      => null, 
                        'assessment_date'   => now(),
                        'assessment_due'    => $deadlineDate, 
                        'total_amount_due'  => $totalAmountDue,
                        'assessment_status' => 'Pending',
                        'remarks'           => "Auto-generated assessment for new franchise application.",
                    ]);

                    foreach ($particulars as $particular) {
                        $assessment->particulars()->attach($particular->id, [
                            'quantity' => 1,
                            'subtotal' => $particular->amount
                        ]);
                    }
                }
            }
            // ---------------------------------------------------

            DB::commit();

            // SEND EMAIL NOTIFICATION HERE
            if ($application->email) {
                \Illuminate\Support\Facades\Mail::to($application->email)->send(
                    new \App\Mail\ApplicationSubmittedMail($application->reference_number, $application->first_name, $assessment)
                );
            }

            return redirect('/')->with('success', 'Your application requirements have been submitted successfully! We will notify you once evaluation is complete.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to upload documents: ' . $e->getMessage()]);
        }
    }
}