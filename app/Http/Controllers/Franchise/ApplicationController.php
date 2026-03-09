<?php

namespace App\Http\Controllers\Franchise;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Models\Franchise;
use App\Models\EvaluationRequirement;
use App\Models\Application;
use App\Models\ProposedUnit;
use App\Models\ApplicationEvaluation;
use App\Models\Barangay;
use App\Models\UnitMake;
use App\Models\Operator;
use App\Models\Unit;
use App\Models\Assessment;
use App\Models\Particular;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 
use Illuminate\Validation\ValidationException;

class ApplicationController extends Controller
{
public function index()
    {
        $user = Auth::user();
        $operator = $user->operator; 

        if (!$operator) {
            return Inertia::render('Franchise/MakeApplication', [
                'hasFranchise' => false,
                'franchises' => [],
                'operator' => null,
                'evaluationRequirements' => [],
                'barangays' => [],
                'unitMakes' => [],
                'operators' => [],
                'units' => [],
                'applications' => [] 
            ]);
        }

        $evaluationRequirements = EvaluationRequirement::where('is_active', true)
            ->get()
            ->groupBy('group');

        $barangays = Barangay::select('id', 'name')->orderBy('name', 'asc')->get();
        $unitMakes = UnitMake::select('id', 'name')->orderBy('name', 'asc')->get();
        
        $operators = Operator::with('user')->get()->map(function($op) {
            return [
                'id' => $op->id,
                'name' => $op->user ? trim($op->user->first_name . ' ' . $op->user->last_name) : 'Unknown',
                'email' => $op->user ? $op->user->email : 'N/A',
            ];
        });

        // Find all currently active unit IDs across all franchises
        $activeUnitIds = Franchise::with('currentActiveUnit')->get()->map(function($f) {
            if ($f->currentActiveUnit) {
                // Safely grab the unit ID depending on your exact column name
                return $f->currentActiveUnit->new_unit_id ?? $f->currentActiveUnit->unit_id;
            }
            return null;
        })->filter()->toArray();

        // Only pass units that are NOT currently active
        $units = Unit::with('make')
            ->whereNotIn('id', $activeUnitIds)
            ->get()
            ->map(function($unit) {
                return [
                    'id' => $unit->id,
                    'plate' => $unit->plate_number,
                    'make' => $unit->make ? $unit->make->name : 'Unknown',
                    'motor' => $unit->motor_number,
                ];
            });

        $franchises = Franchise::whereHas('currentOwnership', function ($query) use ($operator) {
            $query->where('new_operator_id', $operator->id);
        })
        ->with([
            'currentOwnership',               
            'currentActiveUnit.newUnit.make', 
            'unitHistory.newUnit.make',       
            'driverAssignments.driver.user',  
            'ownershipHistory.newOwner.user', 
            'ownershipHistory.previousOwner.user', 
            'zone',                           
            'assessments.payments',           
            'assessments.particulars'         
        ])
        ->get();

        $fiscalYearString = $this->getFiscalYearString();

        $franchises->transform(function ($franchise) use ($fiscalYearString) {
            $franchise->current_status = $franchise->status; 

            $franchise->payment_history = $franchise->assessments->flatMap(function($assessment) {
                return $assessment->payments->map(function($payment) use ($assessment) {
                    $payment->assessment_id = $assessment->id;
                    $payment->assessment_date = $assessment->assessment_date;
                    $payment->particulars_string = $assessment->particulars 
                        ? $assessment->particulars->pluck('name')->join(', ') 
                        : 'N/A';
                    return $payment;
                });
            })->sortByDesc('created_at')->values();

            $franchise->unit_history = $franchise->unitHistory->sortByDesc('date_changed')->values();
            $franchise->ownership_history = $franchise->ownershipHistory->sortByDesc('date_transferred')->values();
            
            $franchise->driver_history = $franchise->driverAssignments
                ->sortByDesc('is_active')
                ->values();

            $franchise->active_driver = $franchise->driverAssignments
                ->where('is_active', true)
                ->first()
                ?->driver;

            // 1. Grab the active/conflicting renewal application directly
            $franchise->conflicting_renewal = Application::where('franchise_id', $franchise->id)
                ->where('application_type', 'Renewal')
                ->whereNotIn('status', ['Rejected', 'Cancelled'])
                ->where('reference_number', 'LIKE', "%APP-{$fiscalYearString}-%")
                ->select('id', 'reference_number as ref_no', 'application_type as type', 'status', 'remarks')
                ->latest()
                ->first();

            // 2. NEW CRITERIA: Check if they have a rejected renewal this year
            $franchise->has_rejected_renewal = Application::where('franchise_id', $franchise->id)
                ->where('application_type', 'Renewal')
                ->whereIn('status', ['Rejected', 'Cancelled'])
                ->where('reference_number', 'LIKE', "%APP-{$fiscalYearString}-%")
                ->exists();

            // 3. Grab conflicting Change of Unit
            $franchise->conflicting_change_unit = Application::where('franchise_id', $franchise->id)
                ->where('application_type', 'Change of Unit')
                ->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled', 'Completed'])
                ->select('id', 'reference_number as ref_no', 'application_type as type', 'status', 'remarks')
                ->latest()
                ->first();

            // 4. Grab conflicting Change of Owner
            $franchise->conflicting_change_owner = Application::where('franchise_id', $franchise->id)
                ->where('application_type', 'Change of Owner')
                ->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled', 'Completed'])
                ->select('id', 'reference_number as ref_no', 'application_type as type', 'status', 'remarks')
                ->latest()
                ->first();

            return $franchise;
        });

        $applicationsData = Application::where('user_id', $user->id)
            ->with(['evaluations.requirement', 'unitInspections.inspectionItem'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($app) {
                $step = 1;
                $status = $app->status ?? 'Pending';
                
                if (in_array($status, ['Pending', 'Returned'])) $step = 1;
                elseif ($status === 'Under Review') $step = 2;
                elseif (in_array($status, ['Inspection', 'For Payment'])) $step = 3;
                elseif (in_array($status, ['Processing', 'Approved', 'Rejected'])) $step = 4;

                return [
                    'id' => $app->id,
                    'ref_no' => $app->reference_number,
                    'type' => $app->application_type,
                    'date' => $app->created_at ? $app->created_at->format('Y-m-d') : 'N/A',
                    'status' => $status,
                    'current_step' => $step,
                    'remarks' => $app->remarks ?? 'No remarks provided.',
                    'is_active' => !in_array($status, ['Approved', 'Rejected', 'Cancelled', 'Completed']),
                    'franchise_id' => $app->franchise_id,
                    'evaluator_status' => $app->evaluator_status ?? 'Pending',
                    'inspector_status' => $app->inspector_status ?? 'Pending',
                    'capo_status'      => $app->capo_status ?? 'Pending',
                    'reviewer_status'  => $app->reviewer_status ?? 'Pending',
                    'sp_status'        => $app->sp_status ?? 'Pending',
                    'tab_status'       => $app->tab_status ?? 'Pending',
                    'evaluations' => $app->evaluations->map(function($eval) {
                        return [
                            'id' => $eval->id,
                            'requirement_id' => $eval->requirement_id,
                            'name' => $eval->requirement->name ?? 'Document',
                            'is_compliant' => $eval->is_compliant,
                            'status' => $eval->is_compliant === 1 ? 'Approved' : ($eval->is_compliant === 0 ? 'Rejected' : 'Pending'),
                            'remarks' => $eval->remarks,
                        ];
                    }),
                    'unit_inspections' => $app->unitInspections ? $app->unitInspections->map(function($insp) {
                        return [
                            'id' => $insp->id,
                            'name' => $insp->inspectionItem->name ?? 'Inspection Item',
                            'rating' => $insp->rating,
                            'remarks' => $insp->remarks,
                        ];
                    }) : []
                ];
            });

        return Inertia::render('Franchise/MakeApplication', [
            'hasFranchise' => true,
            'franchises' => $franchises,
            'operator' => $operator->load('user'),
            'evaluationRequirements' => $evaluationRequirements,
            'barangays' => $barangays,
            'unitMakes' => $unitMakes,
            'operators' => $operators,
            'units' => $units,
            'applications' => $applicationsData 
        ]);
    }

    private function getFiscalYearString() {
        $settings = SystemSetting::first();
        $currentYear = now()->year;
        
        $renewalStart = $settings->annual_renewal_start ?? '01-01';
        $startDateThisYear = Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$renewalStart}")->startOfDay();

        if ($renewalStart === '01-01') {
            return (string) $currentYear;
        } else {
            if (now()->lt($startDateThisYear)) {
                return ($currentYear - 1) . '-' . $currentYear;
            } else {
                return $currentYear . '-' . ($currentYear + 1);
            }
        }
    }

    public function storeChangeOfUnit(Request $request)
    {
        $request->validate([
            'selected_franchise_id' => 'required|exists:franchises,id',
            'documents'             => 'required|array',
            'documents.*'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'unit_mode'             => 'required|in:new,existing', 
        ]);

        $existingApp = Application::where('franchise_id', $request->selected_franchise_id)
            ->where('application_type', 'Change of Unit')
            ->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled', 'Completed'])
            ->exists();

        if ($existingApp) {
            throw ValidationException::withMessages([
                'selected_franchise_id' => 'An active Change of Unit application already exists for this franchise.',
            ]);
        }

        $user = Auth::user();
        $franchise = Franchise::findOrFail($request->selected_franchise_id);

        if ($request->unit_mode === 'existing') {
            $request->validate([
                'existing_unit_id' => 'required|exists:units,id',
            ]);
            
            $unit = Unit::findOrFail($request->existing_unit_id);
            
            $makeId = $unit->make_id;
            $modelYear = $unit->model_year;
            $plateNumber = $unit->plate_number;
            $motorNumber = $unit->motor_number;
            $chassisNumber = $unit->chassis_number;
            $crNumber = $unit->cr_number;
            
            // FETCH EXISTING PHOTOS: Don't let them be null
            $frontPath = $unit->unit_front_photo ?? null;
            $backPath  = $unit->unit_back_photo ?? null;
            $leftPath  = $unit->unit_left_photo ?? null;
            $rightPath = $unit->unit_right_photo ?? null;

        } else {
            // Require photos upload only if unit is completely new
            $request->validate([
                'make_id'               => 'required', 
                'model_year'            => 'required|numeric',
                'plate_number'          => 'required|string',
                'motor_number'          => 'required|string',
                'chassis_number'        => 'required|string',
                'cr_number'             => 'required|string',
                'unit_front_photo'      => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'unit_back_photo'       => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'unit_left_photo'       => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'unit_right_photo'      => 'required|image|mimes:jpeg,png,jpg|max:5120',
            ]);
            
            $makeId = $request->make_id;
            $modelYear = $request->model_year;
            $plateNumber = $request->plate_number;
            $motorNumber = $request->motor_number;
            $chassisNumber = $request->chassis_number;
            $crNumber = $request->cr_number;

            // Store newly uploaded photos
            $frontPath = $request->file('unit_front_photo')->store('units/photos', 'public');
            $backPath  = $request->file('unit_back_photo')->store('units/photos', 'public');
            $leftPath  = $request->file('unit_left_photo')->store('units/photos', 'public');
            $rightPath = $request->file('unit_right_photo')->store('units/photos', 'public');
        }

        DB::beginTransaction();

        try {
            $application = Application::create([
                'reference_number' => 'APP-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'user_id'          => $user->id,
                'franchise_id'     => $franchise->id,
                'zone_id'          => $franchise->zone_id,
                'application_type' => 'Change of Unit',
                'status'           => 'Pending',
                'remarks'          => 'Application submitted. Waiting for initial review.',
                'submitted_at'     => now(),
                
                'first_name'       => $user->first_name,
                'middle_name'      => $user->middle_name,
                'last_name'        => $user->last_name,
                'contact_number'   => $user->contact_number,
                'email'            => $user->email, 
                'tin_number'       => $user->operator->tin_number ?? $user->tin_number,
                'street_address'   => $user->street_address ?? $user->address,
                'barangay'         => $user->barangay,
                'city'             => $user->city ?? 'Zamboanga City',
            ]);

            ProposedUnit::create([
                'application_id'   => $application->id,
                'make_id'          => $makeId, 
                'model_year'       => $modelYear,
                'plate_number'     => $plateNumber,
                'motor_number'     => $motorNumber,
                'chassis_number'   => $chassisNumber,
                'cr_number'        => $crNumber,
                'unit_front_photo' => $frontPath,
                'unit_back_photo'  => $backPath,
                'unit_left_photo'  => $leftPath,
                'unit_right_photo' => $rightPath,
            ]);

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $requirementId => $file) {
                    $filePath = $file->store('applications/documents', 'public');

                    ApplicationEvaluation::create([
                        'application_id' => $application->id,
                        'requirement_id' => $requirementId,
                        'file_path'      => $filePath,
                        'is_compliant'   => null,
                        'remarks'        => 'Uploaded upon submission.'
                    ]);
                }
            }

            $particulars = Particular::where('group', 'change_of_unit')->get();
            if ($particulars->isNotEmpty()) {
                $totalAmountDue = $particulars->sum('amount');
                $assessment = Assessment::create([
                    'application_id'    => $application->id,
                    'franchise_id'     => $franchise->id,
                    'assessment_date'   => now(),
                    'total_amount_due'  => $totalAmountDue,
                    'assessment_status' => 'Pending',
                    'remarks'           => 'Auto-generated assessment for Change of Unit Application: ' . $application->reference_number,
                ]);
                foreach ($particulars as $particular) {
                    $assessment->particulars()->attach($particular->id, [
                        'quantity' => 1,
                        'subtotal' => $particular->amount
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Change of Unit application submitted successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to submit application. Please try again.');
        }
    }

    public function storeChangeOfOwner(Request $request)
    {
        $request->validate([
            'selected_franchise_id' => 'required|exists:franchises,id',
            'documents'             => 'required|array',
            'documents.*'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'owner_mode'            => 'required|in:new,existing',
        ]);

        $existingApp = Application::where('franchise_id', $request->selected_franchise_id)
            ->where('application_type', 'Change of Owner')
            ->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled', 'Completed'])
            ->exists();

        if ($existingApp) {
             throw ValidationException::withMessages([
                'selected_franchise_id' => 'An active Change of Owner application already exists for this franchise.',
            ]);
        }

        $user = Auth::user();
        $franchise = Franchise::findOrFail($request->selected_franchise_id);

        if ($request->owner_mode === 'existing') {
            $request->validate([
                'existing_operator_id' => 'required|exists:operators,id',
            ]);
            $operator = Operator::with('user')->findOrFail($request->existing_operator_id);
            $firstName = $operator->user->first_name;
            $middleName = $operator->user->middle_name;
            $lastName = $operator->user->last_name;
            $contactNumber = $operator->user->contact_number;
            $email = $operator->user->email;
            $tinNumber = $operator->tin_number ?? $operator->user->tin_number;
            $address = $operator->user->street_address;
            $barangay = $operator->user->barangay;
            $city = $operator->user->city;
        } else {
            $request->validate([
                'new_owner_first_name' => 'required|string',
                'new_owner_last_name'  => 'required|string',
                'new_owner_email'      => 'required|email',
                'new_owner_contact'    => 'required|string',
                'new_owner_tin'        => 'required|string',
                'new_owner_address'    => 'required|string',
                'new_owner_barangay'   => 'required|string',
                'new_owner_province' => 'required|string',
                'new_owner_city' => 'required|string',
            ]);
            $firstName = $request->new_owner_first_name;
            $middleName = $request->new_owner_middle_name;
            $lastName = $request->new_owner_last_name;
            $contactNumber = $request->new_owner_contact;
            $email = $request->new_owner_email;
            $tinNumber = $request->new_owner_tin;
            $address = $request->new_owner_address;
            $barangay = $request->new_owner_barangay;
            $city = $request->new_owner_city;
            $province = $request->new_owner_province;   
        }

        DB::beginTransaction();

        try {
            $application = Application::create([
                'reference_number' => 'APP-' . date('Y') . '-' . strtoupper(Str::random(6)),
                'user_id'          => $user->id,
                'franchise_id'     => $franchise->id,
                'zone_id'          => $franchise->zone_id,
                'application_type' => 'Change of Owner',
                'status'           => 'Pending',
                'remarks'          => 'Application submitted. Waiting for initial review.',
                'submitted_at'     => now(),
                
                'first_name'       => $firstName,
                'middle_name'      => $middleName,
                'last_name'        => $lastName,
                'contact_number'   => $contactNumber,
                'email'            => $email, 
                'tin_number'       => $tinNumber,
                'street_address'   => $address,
                'province'         => $province,
                'barangay'         => $barangay,
                'city'             => $city,
            ]);

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $requirementId => $file) {
                    $filePath = $file->store('applications/documents', 'public');
                    ApplicationEvaluation::create([
                        'application_id' => $application->id,
                        'requirement_id' => $requirementId,
                        'file_path'      => $filePath,
                        'is_compliant'   => null,
                        'remarks'        => 'Uploaded upon submission.'
                    ]);
                }
            }

            $particulars = Particular::where('group', 'change_of_owner')->get();
            if ($particulars->isNotEmpty()) {
                $totalAmountDue = $particulars->sum('amount');
                $assessment = Assessment::create([
                    'application_id'    => $application->id,
                    'franchise_id'     => $franchise->id,
                    'assessment_date'   => now(),
                    'total_amount_due'  => $totalAmountDue,
                    'assessment_status' => 'Pending',
                    'remarks'           => 'Auto-generated assessment for Change of Owner Application: ' . $application->reference_number,
                ]);
                foreach ($particulars as $particular) {
                    $assessment->particulars()->attach($particular->id, [
                        'quantity' => 1,
                        'subtotal' => $particular->amount
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Change of Owner application submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to submit application. Please try again.');
        }
    }

    public function storeRenewal(Request $request)
    {
        $request->validate([
            'selected_franchise_id' => 'required|exists:franchises,id',
            'documents'             => 'required|array',
            'documents.*'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $fiscalYearString = $this->getFiscalYearString();

        $existingApp = Application::where('franchise_id', $request->selected_franchise_id)
            ->where('application_type', 'Renewal')
            ->whereNotIn('status', ['Rejected', 'Cancelled'])
            ->where('reference_number', 'LIKE', "%APP-{$fiscalYearString}-%")
            ->exists();

        if ($existingApp) {
             throw ValidationException::withMessages([
                'selected_franchise_id' => "A Renewal application for the {$fiscalYearString} fiscal year already exists for this franchise.",
            ]);
        }

        $user = Auth::user();
        $franchise = Franchise::findOrFail($request->selected_franchise_id);

        DB::beginTransaction();

        try {
            $application = Application::create([
                'reference_number' => 'APP-' . $fiscalYearString . '-' . strtoupper(Str::random(6)),
                'user_id'          => $user->id,
                'franchise_id'     => $franchise->id,
                'zone_id'          => $franchise->zone_id,
                'application_type' => 'Renewal',
                'status'           => 'Pending',
                'remarks'          => "Application submitted for {$fiscalYearString} cycle. Waiting for initial review.",
                'submitted_at'     => now(),
                
                'first_name'       => $user->first_name,
                'middle_name'      => $user->middle_name,
                'last_name'        => $user->last_name,
                'contact_number'   => $user->contact_number,
                'email'            => $user->email, 
                'tin_number'       => $user->operator->tin_number ?? $user->tin_number,
                'street_address'   => $user->street_address ?? $user->address,
                'barangay'         => $user->barangay,
                'city'             => $user->city ?? 'Zamboanga City',
            ]);

            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $requirementId => $file) {
                    $filePath = $file->store('applications/documents', 'public');
                    ApplicationEvaluation::create([
                        'application_id' => $application->id,
                        'requirement_id' => $requirementId,
                        'file_path'      => $filePath,
                        'is_compliant'   => null,
                        'remarks'        => 'Uploaded upon submission.'
                    ]);
                }
            }

            $particulars = Particular::where('group', 'renewal')->get();
            if ($particulars->isNotEmpty()) {
                $totalAmountDue = $particulars->sum('amount');
                $assessment = Assessment::create([
                    'application_id'    => $application->id,
                    'franchise_id'     => $franchise->id,
                    'assessment_date'   => now(),
                    'assessment_due'    => now()->addDays(7), 
                    'total_amount_due'  => $totalAmountDue,
                    'assessment_status' => 'Pending',
                    'remarks'           => 'Auto-generated assessment for Renewal Application: ' . $application->reference_number,
                ]);
                foreach ($particulars as $particular) {
                    $assessment->particulars()->attach($particular->id, [
                        'quantity' => 1,
                        'subtotal' => $particular->amount
                    ]);
                }
            }

            $franchise->update(['status' => 'Pending Renewal']);

            DB::commit();

            return redirect()->back()->with('success', 'Renewal application submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to submit application. Please try again.');
        }
    }

    public function resubmitApplication(Request $request, Application $application)
    {
        abort_if($application->user_id !== Auth::id(), 403);

        $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $evaluationId => $file) {
                $filePath = $file->store('applications/documents', 'public');

                ApplicationEvaluation::where('id', $evaluationId)
                    ->where('application_id', $application->id)
                    ->update([
                        'file_path' => $filePath,
                        'is_compliant' => null, 
                        'remarks' => 'Resubmitted by applicant.'
                    ]);
            }
        }

        $application->update([
            'status' => 'Pending',
            'evaluator_status' => 'Pending',
            'remarks' => 'Application compliance submitted. Waiting for re-evaluation.'
        ]);

        return redirect()->back()->with('success', 'Compliance submitted successfully!');
    }

    public function cancelApplication(Request $request, Application $application)
    {
        abort_if($application->user_id !== Auth::id(), 403);

        if ($application->application_type === 'Renewal') {
            return redirect()->back()->with('error', 'Renewal applications cannot be cancelled.');
        }

        if (in_array($application->status, ['Approved', 'Rejected', 'Completed', 'Cancelled'])) {
            return redirect()->back()->with('error', 'This application cannot be cancelled in its current state.');
        }

        DB::beginTransaction();

        try {
            $application->update([
                'status' => 'Cancelled',
                'remarks' => 'Application cancelled by the applicant.'
            ]);

            if ($application->assessment) {
                $application->assessment()->update([
                    'assessment_status' => 'Cancelled',
                    'total_amount_due' => 0, 
                    'remarks' => 'Assessment cancelled due to application cancellation.'
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Application and associated assessment cancelled successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to cancel the application. Please try again.');
        }
    }

    public function submitRenewalDocuments(Request $request, Application $application)
    {
        abort_if($application->user_id !== Auth::id(), 403);

        if ($application->application_type !== 'Renewal') {
            return redirect()->back()->with('error', 'Invalid application type for this action.');
        }

        $request->validate([
            'documents'   => 'required|array',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        DB::beginTransaction();

        try {
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $requirementId => $file) {
                    $filePath = $file->store('applications/documents', 'public');

                    ApplicationEvaluation::updateOrCreate(
                        [
                            'application_id' => $application->id,
                            'requirement_id' => $requirementId,
                        ],
                        [
                            'file_path'    => $filePath,
                            'is_compliant' => null, 
                            'remarks'      => 'Uploaded by applicant for auto-renewal.'
                        ]
                    );
                }
            }

            $application->update([
                'status'       => 'Pending', 
                'submitted_at' => now(),
                'remarks'      => 'Renewal documents submitted. Waiting for initial review.'
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Renewal documents submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to submit documents. Please try again.');
        }
    }

    public function resubmitForInspection(Application $application)
    {
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $application->update([
            'status' => 'Pending', 
            'inspector_status' => 'Pending',
            'remarks' => 'Applicant has addressed the mechanical issues and the unit is ready for re-inspection.'
        ]);

        return redirect()->back()->with('success', 'Application successfully resubmitted for unit inspection.');
    }
}