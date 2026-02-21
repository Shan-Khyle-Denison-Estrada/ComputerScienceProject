<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assessment;

class UpdatePenalties extends Command
{
    // The command to run in terminal: php artisan assessments:update-penalties
    protected $signature = 'assessments:update-penalties';
    protected $description = 'Daily job to update compounding surcharge and interest on overdue assessments';

    public function handle()
    {
        $this->info('Starting penalty calculations...');

        // Fetch all assessments that haven't been paid yet
        $assessments = Assessment::whereIn('assessment_status', ['pending', 'overdue'])->get();

        $count = 0;
        foreach ($assessments as $assessment) {
            $assessment->recalculatePenalties();
            $count++;
        }

        $this->info("Updated penalties for {$count} active assessments.");
    }
}