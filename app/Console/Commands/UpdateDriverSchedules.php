<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DriverAssignment;
use App\Models\DriverLog;
use Carbon\Carbon;

class UpdateDriverSchedules extends Command
{
    protected $signature = 'drivers:update-schedules';
    protected $description = 'Automatically activates or deactivates drivers based on their weekly schedule.';

    public function handle()
    {
        // Get current time and day in Philippine Time
        $now = Carbon::now('Asia/Manila');
        $currentDay = $now->format('l'); // e.g., 'Monday'
        $currentTime = $now->format('H:i'); // e.g., '14:30'

        // Fetch all assignments that have a schedule
        $assignments = DriverAssignment::whereNotNull('schedule')->get();

                foreach ($assignments as $assignment) {
            // ------------------------------------------------------------------
            // NEW: Skip any assignment where manual override is active.
            // This prevents the scheduler from changing a driver that has been
            // manually overridden (either as active or inactive).
            // ------------------------------------------------------------------
            if ($assignment->manual_override) {
                // If this driver is the one with override, do nothing.
                continue;
            }

            // Also, if there is an active driver on this franchise with override,
            // skip all automatic changes for this franchise entirely.
            $activeOverride = DriverAssignment::where('franchise_id', $assignment->franchise_id)
                ->where('is_active', true)
                ->where('manual_override', true)
                ->exists();

            if ($activeOverride) {
                // A manual override is in effect for this franchise; skip any
                // automated activation/deactivation for all assignments in it.
                continue;
            }

            $schedule = $assignment->schedule;
            $shouldBeActive = false;

            // Find today's schedule for this driver
            foreach ($schedule as $daySched) {
                if ($daySched['day'] === $currentDay && !$daySched['is_off']) {
                    if (!empty($daySched['start']) && !empty($daySched['end'])) {
                        // Check if current time falls within their shift
                        if ($currentTime >= $daySched['start'] && $currentTime < $daySched['end']) {
                            $shouldBeActive = true;
                        }
                    }
                }
            }

            // If the driver SHOULD be working but IS NOT currently active
            if ($shouldBeActive && !$assignment->is_active) {
                // 1. Deactivate anyone else currently active on this franchise
                $currentActive = DriverAssignment::where('franchise_id', $assignment->franchise_id)
                    ->where('is_active', true)
                    ->first();

                if ($currentActive) {
                    // Extra safety: if the current active has override, skip (shouldn't happen due to check above)
                    if ($currentActive->manual_override) {
                        continue;
                    }
                    $currentActive->update(['is_active' => false]);
                    DriverLog::where('franchise_id', $assignment->franchise_id)
                        ->where('driver_id', $currentActive->driver_id)
                        ->whereNull('ended_at')
                        ->update(['ended_at' => $now]);
                }

                // 2. Activate this driver
                $assignment->update(['is_active' => true]);

                // 3. Create a new Log entry
                DriverLog::create([
                    'franchise_id' => $assignment->franchise_id,
                    'driver_id' => $assignment->driver_id,
                    'started_at' => $now,
                ]);

                $this->info("Activated driver ID {$assignment->driver_id} for Franchise {$assignment->franchise_id}");
            } 
            
            // If the driver SHOULD NOT be working but IS currently active
            elseif (!$shouldBeActive && $assignment->is_active) {
                
                $assignment->update(['is_active' => false]);
                
                DriverLog::where('franchise_id', $assignment->franchise_id)
                    ->where('driver_id', $assignment->driver_id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => $now]);

                $this->info("Deactivated driver ID {$assignment->driver_id} for Franchise {$assignment->franchise_id}");
            }
        }

        $this->info('Driver schedules checked successfully.');
    }
}