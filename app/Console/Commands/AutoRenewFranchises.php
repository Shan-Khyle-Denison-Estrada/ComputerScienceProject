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
    
    // Description for the console
    protected $description = 'Auto-generate late renewal applications and apply penalties.';

    public function handle()
    {
        // --- NEW STEP: AUTO-TERMINATE FRANCHISES (3 YEARS UNPAID/NON-COMPLIANT) ---
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
        $startDateThisYear = \Carbon\Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$renewalStart}")->startOfDay();

        if ($renewalStart === '01-01') {
            $fiscalYearString = (string) $currentYear;
        } else {
            if (now()->lt($startDateThisYear)) {
                $fiscalYearString = ($currentYear - 1) . '-' . $currentYear;
            } else {
                $fiscalYearString = $currentYear . '-' . ($currentYear + 1);
            }
        }

        // 2. DETERMINE THE PENALTY DEADLINE (Based on Due Date)
        $renewalDue = $settings->annual_renewal_due ?? '12-31'; 
        $deadlineDate = \Carbon\Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$renewalDue}")->endOfDay();

        // If today hasn't reached the penalty deadline yet, stop.
        if (now()->startOfDay()->lt($deadlineDate->startOfDay())) {
            $this->info("The {$fiscalYearString} renewal deadline ({$deadlineDate->format('M d, Y')}) has not passed yet.");
            return;
        }

        // 3. FIND NON-COMPLIANT FRANCHISES
        // We look for franchises missing a renewal for THIS specific fiscal year cycle
        $franchises = Franchise::whereDoesntHave('applications', function ($query) use ($currentYear) {
            $query->where('application_type', 'Renewal')
                  ->whereYear('created_at', $currentYear); 
        })
        ->where('status', '!=', 'Terminated') // Updated to match case
        ->with('currentOwnership.newOwner.user')
        ->get();

        if ($franchises->isEmpty()) {
            $this->info("All active franchises are compliant for the {$fiscalYearString} fiscal year.");
            return;
        }

        // 4. CALCULATE FEES & PENALTIES
        $particulars = Particular::where('group', 'Renewal')->get();
        $baseAmountDue = $particulars->sum('amount');
        
        $surchargeRate = ($settings->surcharge_rate ?? 0) / 100;
        $interestRate = ($settings->interest_rate ?? 0) / 100;

        $surchargeFee = $baseAmountDue * $surchargeRate;
        $interestFee = $baseAmountDue * $interestRate;
        
        $totalAmountDue = $baseAmountDue + $surchargeFee + $interestFee;

        // 5. GENERATE THE APPLICATIONS
        foreach ($franchises as $franchise) {
            $user = $franchise->currentOwnership->newOwner->user ?? null;
            if (!$user) continue; 

            // Create the Application using the Fiscal Year string
            $application = Application::create([
                'reference_number' => 'APP-' . $fiscalYearString . '-' . strtoupper(Str::random(6)), // e.g., APP-2025-2026-X8B9Q
                'user_id'          => $user->id,
                'franchise_id'     => $franchise->id,
                'zone_id'          => $franchise->zone_id,
                'application_type' => 'Renewal',
                'status'           => 'For Payment',
                'remarks'          => "SYSTEM AUTO-GENERATED: Missed {$fiscalYearString} Renewal Deadline.",
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
                    // SET DUE DATE EXACTLY TO THE RENEWAL SCHEDULE DEADLINE
                    'assessment_due'    => $deadlineDate, 
                    'total_amount_due'  => $totalAmountDue,
                    'assessment_status' => 'Pending',
                    'remarks'           => "Auto-generated late renewal.",
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

        $this->info("Successfully auto-generated late renewals for the {$fiscalYearString} fiscal year.");
    }
}