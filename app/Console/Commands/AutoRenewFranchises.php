<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Franchise;
use App\Models\Application;
use App\Models\Assessment;
use App\Models\Particular;
use App\Models\SystemSetting;
use Carbon\Carbon;

class AutoRenewFranchises extends Command
{
    // The command we type in the terminal to run this manually
    protected $signature = 'franchise:auto-renew';
    
    // Description for the console (Updated to reflect new behavior)
    protected $description = 'Auto-generate annual renewal applications at the start of the renewal period.';

    public function handle()
    {
        // --- STEP 0: AUTO-TERMINATE FRANCHISES (3 YEARS UNPAID/NON-COMPLIANT) ---
        $threeYearsAgo = now()->subYears(3);

        // Find franchises with an unpaid assessment from more than 3 years ago
        $franchisesToTerminate = Franchise::whereHas('assessments', function ($query) use ($threeYearsAgo) {
            $query->where('assessment_status', '!=', 'paid')
                  ->whereDate('assessment_date', '<=', $threeYearsAgo);
        })->where('status', '!=', 'Terminated')->get();

        // Update them to Terminated
        foreach ($franchisesToTerminate as $franchise) {
            $franchise->update(['status' => 'Terminated']);
        }

        if ($franchisesToTerminate->isNotEmpty()) {
            $this->info("Auto-terminated {$franchisesToTerminate->count()} franchises for 3 consecutive years of non-compliance.");
        }
        // ---------------------------------------------------------------------------

        $currentYear = now()->year;
        $settings = SystemSetting::first();

        // 1. DETERMINE THE FISCAL YEAR STRING (Based on Start Date)
        $renewalStart = $settings->annual_renewal_start ?? '01-01';
        $startDateThisYear = Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$renewalStart}")->startOfDay();

        if ($renewalStart === '01-01') {
            $fiscalYearString = (string) $currentYear;
        } else {
            if (now()->lt($startDateThisYear)) {
                $fiscalYearString = ($currentYear - 1) . '-' . $currentYear;
            } else {
                $fiscalYearString = $currentYear . '-' . ($currentYear + 1);
            }
        }

        // 2. DETERMINE THE RENEWAL START AND DEADLINE
        // If today hasn't reached the renewal start date yet, stop.
        if (now()->startOfDay()->lt($startDateThisYear)) {
            $this->info("The {$fiscalYearString} renewal period ({$startDateThisYear->format('M d, Y')}) has not started yet.");
            return;
        }

        // We still need the due date so we can set it on the Assessment record
        $renewalDue = $settings->annual_renewal_due ?? '12-31'; 
        $deadlineDate = Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$renewalDue}")->endOfDay();

        // 3. FIND FRANCHISES THAT NEED A RENEWAL BILL
        $franchises = Franchise::whereDoesntHave('applications', function ($query) use ($currentYear) {
            $query->where('application_type', 'Renewal')
                  ->whereYear('created_at', $currentYear); 
        })
        ->where('status', '!=', 'Terminated')
        ->with('currentOwnership.newOwner.user')
        ->get();

        if ($franchises->isEmpty()) {
            $this->info("All active franchises already have renewal applications for the {$fiscalYearString} fiscal year.");
            return;
        }

        // 4. CALCULATE BASE FEES ONLY (Penalty logic removed)
        $particulars = Particular::where('group', 'Renewal')->get();
        $totalAmountDue = $particulars->sum('amount');

        // 5. GENERATE THE APPLICATIONS
        foreach ($franchises as $franchise) {
            $user = $franchise->currentOwnership->newOwner->user ?? null;
            if (!$user) continue; 

            // Create the Application
            $application = Application::create([
                'reference_number' => 'APP-' . $fiscalYearString . '-' . strtoupper(Str::random(6)),
                'user_id'          => $user->id,
                'franchise_id'     => $franchise->id,
                'zone_id'          => $franchise->zone_id,
                'application_type' => 'Renewal',
                'status'           => 'Pending',
                'remarks'          => "SYSTEM AUTO-GENERATED: {$fiscalYearString} Annual Renewal.",
                'submitted_at'     => now(),
                'first_name'       => $user->first_name,
                'middle_name'      => $user->middle_name,
                'last_name'        => $user->last_name,
                'contact_number'   => $user->contact_number,
                'email'            => $user->email,
                'tin_number'       => $user->tin_number,
                'street_address'   => $user->street_address,
                'barangay'         => $user->barangay,
                'city'             => $user->city ?? 'Zamboanga City',
            ]);

            if ($particulars->isNotEmpty()) {
                $assessment = Assessment::create([
                    'application_id'    => $application->id,
                    'assessment_date'   => now(),
                    'assessment_due'    => $deadlineDate, 
                    'total_amount_due'  => $totalAmountDue,
                    'assessment_status' => 'Pending',
                    'remarks'           => "Auto-generated annual renewal.",
                ]);

                foreach ($particulars as $particular) {
                    $assessment->particulars()->attach($particular->id, [
                        'quantity' => 1,
                        'subtotal' => $particular->amount
                    ]);
                }
            }
            $franchise->update(['status' => 'Pending Renewal']);
        }

        $this->info("Successfully auto-generated renewals for the {$fiscalYearString} fiscal year.");
    }
}