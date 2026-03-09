<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Franchise;
use Carbon\Carbon;

class CheckExpiredFranchises extends Command
{
    // The command we type in the terminal to run this manually
    protected $signature = 'franchise:check-expired';
    
    protected $description = 'Check and mark franchises as Expired if 1 year has passed since date_issued without a completed renewal.';

    public function handle()
    {
        $oneYearAgo = now()->subYear();
        $currentYear = now()->year;

        // Find franchises issued more than 1 year ago
        $franchisesToExpire = Franchise::where('status', '!=', 'Terminated')
            ->where('status', '!=', 'Expired') // Skip already expired ones
            ->whereDate('date_issued', '<=', $oneYearAgo)
            ->whereDoesntHave('applications', function ($query) use ($currentYear) {
                // Ensure they don't have a COMPLETED renewal for the current year
                $query->where('application_type', 'Renewal')
                      ->whereYear('created_at', $currentYear)
                      // Replace 'Completed'/'Approved' with your exact success status(es)
                      ->whereIn('status', ['Completed', 'Approved']); 
            })
            ->get();

        $count = 0;
        foreach ($franchisesToExpire as $franchise) {
            $franchise->update(['status' => 'Expired']);
            $count++;
        }

        if ($count > 0) {
            $this->info("Successfully marked {$count} franchises as Expired.");
        } else {
            $this->info("No franchises found that need to be marked as Expired.");
        }
    }
}