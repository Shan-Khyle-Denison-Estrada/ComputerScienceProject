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

        $units = Unit::with('make')->get()->map(function($unit) {
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

        $franchises->transform(function ($franchise) {
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

            return $franchise;
        });

        $applicationsData = Application::where('user_id', $user->id)
            ->with('evaluations.requirement')
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
                    'evaluations' => $app->evaluations->map(function($eval) {
                        return [
                            'id' => $eval->id,
                            'requirement_id' => $eval->requirement_id,
                            'name' => $eval->requirement->name ?? 'Document',
                            'is_compliant' => $eval->is_compliant,
                            'status' => $eval->is_compliant === 1 ? 'Approved' : ($eval->is_compliant === 0 ? 'Rejected' : 'Pending'),
                            'remarks' => $eval->remarks,
                        ];
                    })
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

    public function storeChangeOfUnit(Request $request)
    {
        $request->validate([
            'selected_franchise_id' => 'required|exists:franchises,id',
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
            'documents'             => 'required|array',
            'documents.*'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $user = Auth::user();
        $franchise = Franchise::findOrFail($request->selected_franchise_id);

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
            'tin_number'       => $user->tin_number,
            'street_address'   => $user->street_address ?? $user->address,
            'barangay'         => $user->barangay,
            'city'             => $user->city ?? 'Zamboanga City',
        ]);

        $frontPath = $request->file('unit_front_photo')->store('units/photos', 'public');
        $backPath  = $request->file('unit_back_photo')->store('units/photos', 'public');
        $leftPath  = $request->file('unit_left_photo')->store('units/photos', 'public');
        $rightPath = $request->file('unit_right_photo')->store('units/photos', 'public');

        ProposedUnit::create([
            'application_id'   => $application->id,
            'make_id'          => $request->make_id, 
            'model_year'       => $request->model_year,
            'plate_number'     => $request->plate_number,
            'motor_number'     => $request->motor_number,
            'chassis_number'   => $request->chassis_number,
            'cr_number'        => $request->cr_number,
            'unit_front_photo' => $frontPath,
            'unit_back_photo'  => $backPath,
            'unit_left_photo'  => $leftPath,
            'unit_right_photo' => $rightPath,
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $requirementId => $file) {
                $filePath = $file->store('applications/documents', 'public');

                ApplicationEvaluation::create([
                    'application_id'            => $application->id,
                    'requirement_id'            => $requirementId,
                    'file_path'                 => $filePath,
                    'is_compliant'              => null,
                    'remarks'                   => 'Uploaded upon submission.'
                ]);
            }
        }

        $particulars = Particular::where('group', 'change_of_unit')->get();
        
        if ($particulars->isNotEmpty()) {
            $totalAmountDue = $particulars->sum('amount');
            
            $assessment = Assessment::create([
                'application_id'    => $application->id,
                'assessment_date'   => now(),
                'assessment_due'    => now()->addDays(7), 
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

        return redirect()->back()->with('success', 'Change of Unit application submitted successfully!');
    }

    public function storeChangeOfOwner(Request $request)
    {
        $request->validate([
            'selected_franchise_id' => 'required|exists:franchises,id',
            'documents'             => 'required|array',
            'documents.*'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'owner_mode'            => 'required|in:new,existing',
        ]);

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
            ]);
            $firstName = $request->new_owner_first_name;
            $middleName = $request->new_owner_middle_name;
            $lastName = $request->new_owner_last_name;
            $contactNumber = $request->new_owner_contact;
            $email = $request->new_owner_email;
            $tinNumber = $request->new_owner_tin;
            $address = $request->new_owner_address;
            $barangay = $request->new_owner_barangay;
            $city = $request->new_owner_city ?? 'Zamboanga City';
        }

        $application = Application::create([
            'reference_number' => 'APP-' . date('Y') . '-' . strtoupper(Str::random(6)),
            'user_id'          => $user->id,
            'franchise_id'     => $franchise->id,
            'zone_id'          => $franchise->zone_id,
            'application_type' => 'Change of Owner',
            'status'           => 'Pending',
            'remarks'          => 'Application submitted. Waiting for initial review.',
            'submitted_at'     => now(),
            
            // Log the PROPOSED new owner's details directly in the application
            'first_name'       => $firstName,
            'middle_name'      => $middleName,
            'last_name'        => $lastName,
            'contact_number'   => $contactNumber,
            'email'            => $email, 
            'tin_number'       => $tinNumber,
            'street_address'   => $address,
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
                'assessment_date'   => now(),
                'assessment_due'    => now()->addDays(7), 
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

        return redirect()->back()->with('success', 'Change of Owner application submitted successfully!');
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
                        'is_compliant' => null, // Reset to pending
                        'remarks' => 'Resubmitted by applicant.'
                    ]);
            }
        }

        $application->update([
            'status' => 'Pending',
            'remarks' => 'Application compliance submitted. Waiting for re-evaluation.'
        ]);

        return redirect()->back()->with('success', 'Compliance submitted successfully!');
    }
}