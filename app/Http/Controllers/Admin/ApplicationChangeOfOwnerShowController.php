<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\User;
use App\Models\Operator;
use App\Models\Ownership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ApplicationChangeOfOwnerShowController extends Controller
{
    public function show(Application $application)
    {
        abort_if($application->application_type !== 'Change of Owner', 404);

        $application->load([
            'user',
            'franchise.currentOwnership.newOwner.user', 
            'franchise.zone', 
            'zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments'
        ]);

        return Inertia::render('Admin/Applications/ShowChangeOfOwner', [
            'application' => $application
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

    public function approveApplication(Application $application)
    {
        $application->update(['status' => 'Approved']);
        return redirect()->back()->with('success', 'Application approved. You can now finalize the ownership change.');
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

    // Finalize Ownership Transfer in Database
    public function finalizeApplication(Request $request, Application $application)
    {
        // Check if user already exists to determine if password is required
        $userExists = User::where('email', $application->email)->exists();

        $request->validate([
            'change_date' => 'required|date',
            'remarks' => 'nullable|string',
            'password' => $userExists ? 'nullable|min:8|confirmed' : 'required|min:8|confirmed',
        ], [
            'password.required' => 'A password must be set because this operator does not have an account yet.'
        ]);

        DB::transaction(function () use ($request, $application) {
            $franchise = $application->franchise;
            $previousOwnership = $franchise->currentOwnership;
            $previousOperatorId = $previousOwnership ? $previousOwnership->new_operator_id : null;

            // 1. Attempt to find an existing user by the exact email provided in the application
            $user = User::where('email', $application->email)->first();

            if (!$user) {
                // Create brand new User for the new owner using the exact submitted password
                $user = User::create([
                    'first_name' => $application->first_name,
                    'middle_name' => $application->middle_name,
                    'last_name' => $application->last_name,
                    'email' => $application->email,
                    'contact_number' => $application->contact_number,
                    'street_address' => $application->street_address,
                    'barangay' => $application->barangay,
                    'city' => $application->city,
                    'password' => Hash::make($request->password), 
                    'role' => 'franchise_owner',
                    'status' => 'active',
                ]);
            } else if ($request->filled('password')) {
                // Optional: Update existing user's password if the admin deliberately inputted a new one
                $user->update([
                    'password' => Hash::make($request->password)
                ]);
            }

            // 2. Locate or create the Operator record
            $operator = Operator::firstOrCreate(
                ['user_id' => $user->id],
                ['tin_number' => $application->tin_number]
            );

            // 3. Create the historical Ownership record
            $newOwnership = Ownership::create([
                'franchise_id' => $franchise->id,
                'new_operator_id' => $operator->id,
                'previous_operator_id' => $previousOperatorId,
                'date_transferred' => $request->change_date,
            ]);

            // 4. Bind the active ownership directly to the Franchise
            $franchise->update([
                'ownership_id' => $newOwnership->id,
            ]);

            // 5. Close out the application
            $application->update([
                'status' => 'Completed',
                'remarks' => 'Change of Owner finalized successfully. ' . ($request->remarks ?? ''),
            ]);
        });

        return redirect()->back()->with('success', 'Change of Owner finalized and activated successfully!');
    }
}