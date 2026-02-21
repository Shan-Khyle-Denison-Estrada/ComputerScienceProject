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
        $currentYear = now()->year;
        $settings = SystemSetting::first();

        // 1. DETERMINE THE DEADLINE AND FISCAL YEAR
        // Example: fiscal_year_end is "03-27" (March 27)
        $fiscalYearEnd = $settings->fiscal_year_end ?? '12-31'; 
        $deadlineDate = \Carbon\Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$fiscalYearEnd}")->endOfDay();

        // If the current date is past the deadline, but we are in the next calendar year,
        // we need to make sure we are calculating the Fiscal Year string correctly.
        // For a deadline of March 27, 2026, the Fiscal Year is "2025-2026"
        $previousYear = $currentYear - 1;
        $fiscalYearString = "{$previousYear}-{$currentYear}";

        // If today hasn't reached the deadline yet, stop.
        if (now()->startOfDay()->lt($deadlineDate->startOfDay())) {
            $this->info("The {$fiscalYearString} renewal deadline ({$deadlineDate->format('M d, Y')}) has not passed yet.");
            return;
        }

        // 2. FIND NON-COMPLIANT FRANCHISES
        // We look for franchises missing a renewal for THIS specific fiscal year cycle
        $franchises = Franchise::whereDoesntHave('applications', function ($query) use ($currentYear) {
            $query->where('application_type', 'Renewal')
                  ->whereYear('created_at', $currentYear); 
        })
        ->where('status', '!=', 'terminated') 
        ->with('currentOwnership.newOwner.user')
        ->get();

        if ($franchises->isEmpty()) {
            $this->info("All active franchises are compliant for the {$fiscalYearString} fiscal year.");
            return;
        }

        // 3. CALCULATE FEES & PENALTIES
        $particulars = Particular::where('group', 'Renewal')->get();
        $baseAmountDue = $particulars->sum('amount');
        
        $surchargeRate = ($settings->surcharge_rate ?? 0) / 100;
        $interestRate = ($settings->interest_rate ?? 0) / 100;

        $surchargeFee = $baseAmountDue * $surchargeRate;
        $interestFee = $baseAmountDue * $interestRate;
        
        $totalAmountDue = $baseAmountDue + $surchargeFee + $interestFee;

        // 4. GENERATE THE APPLICATIONS
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
        }

        $this->info("Successfully auto-generated late renewals for the {$fiscalYearString} fiscal year.");
    }
}