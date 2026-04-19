<?php

namespace App\Http\Controllers\Franchise;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Franchise;
use App\Models\DriverAssignment;
use App\Models\DriverLog;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $operator = $user->operator; 

        // 1. Handle Non-Operator Users
        if (!$operator) {
            return Inertia::render('Franchise/Dashboard', [
                'hasFranchise' => false,
                'franchises' => [],
                'operator' => null
            ]);
        }

        // 2. Find ALL Active Franchises for this Operator
        $franchises = Franchise::whereHas('currentOwnership', function ($query) use ($operator) {
            $query->where('new_operator_id', $operator->id);
        })
        ->with([
            'currentOwnership',               
            'currentActiveUnit.newUnit.make', 
            'unitHistory.newUnit.make',       
            // We load assignments to show the list of drivers to toggle
            'driverAssignments.driver.user',  
            'ownershipHistory.newOwner.user', 
            'ownershipHistory.previousOwner.user', 
            'zone',                           
            'assessments.payments',           
            'assessments.particulars',
            'assessments.application',
            'complaints',
            'redFlags.nature',
            'driverLogs.driver' // Added driverLogs to verify actual historical drivers
        ])
        ->get();

        // 3. Process each franchise to format nested data
        $franchises->transform(function ($franchise) {
            
            $franchise->current_status = $franchise->status; 

            // Flatten payments
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

            // Sort histories
            $franchise->unit_history = $franchise->unitHistory->sortByDesc('date_changed')->values();
            $franchise->ownership_history = $franchise->ownershipHistory->sortByDesc('date_transferred')->values();
            
            // Sort drivers so the 'Active' one (is_active = true) is always at the top
            $franchise->driver_history = $franchise->driverAssignments
                ->sortByDesc('is_active')
                ->values();

            // Process Complaints strictly against Driver Logs using local time string comparison
            $franchise->complaints_history = $franchise->complaints->map(function($complaint) use ($franchise) {
                $assignedDriver = null;

                if ($complaint->incident_date && $complaint->incident_time) {
                    
                    // 1. Format the incident date and time into a standard "YYYY-MM-DD HH:MM" string 
                    // (Dropping seconds to avoid mismatch if the database has seconds but the input doesn't)
                    $incidentString = \Carbon\Carbon::parse($complaint->incident_date . ' ' . $complaint->incident_time)
                                        ->format('Y-m-d H:i');

                    // 2. Strictly check against driver logs
                    foreach ($franchise->driverLogs as $log) {
                        
                        // Parse log timestamps, enforce Philippine Time (Asia/Manila) in case the DB stores UTC, 
                        // and format identically without seconds.
                        $startString = \Carbon\Carbon::parse($log->started_at)
                                        ->timezone('Asia/Manila')
                                        ->format('Y-m-d H:i');
                        
                        $endString = $log->ended_at 
                            ? \Carbon\Carbon::parse($log->ended_at)->timezone('Asia/Manila')->format('Y-m-d H:i')
                            : \Carbon\Carbon::now()->timezone('Asia/Manila')->format('Y-m-d H:i');

                        // 3. Alphabetical string comparison works perfectly for 'YYYY-MM-DD HH:MM'
                        if ($incidentString >= $startString && $incidentString <= $endString) {
                            $assignedDriver = $log->driver;
                            break;
                        }
                    }
                }

                $complaint->driver_name = $assignedDriver 
                    ? ($assignedDriver->first_name . ' ' . $assignedDriver->last_name) 
                    : 'No Driver Assigned';
                    
                $complaint->driver_contact = $assignedDriver 
                    ? $assignedDriver->contact_number 
                    : 'N/A';

                return $complaint;
            })->sortByDesc('incident_date')->values();

            $franchise->red_flags_history = $franchise->redFlags->sortByDesc('created_at')->values();

            // Helper to quickly identify active driver for the frontend card
            $franchise->active_driver = $franchise->driverAssignments
                ->where('is_active', true)
                ->first()
                ?->driver;

            return $franchise;
        });

        return Inertia::render('Franchise/Dashboard', [
            'hasFranchise' => true,
            'franchises' => $franchises,
            'operator' => $operator->load('user'),
        ]);
    }

    /**
     * Set the Active Driver for a Franchise.
     * This toggles the 'is_active' flag and updates the DriverLog.
     */
    public function setActiveDriver(Request $request, $franchiseId)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id'
        ]);

        $newDriverId = $request->input('driver_id');

        DB::transaction(function () use ($franchiseId, $newDriverId) {
            $now = now();

            // 1. Find the currently active driver assignment for this franchise
            $currentActive = DriverAssignment::where('franchise_id', $franchiseId)
                ->where('is_active', true)
                ->first();

            // If the selected driver is already active, do nothing
            if ($currentActive && $currentActive->driver_id == $newDriverId) {
                return;
            }

            // 2. Deactivate the current driver and close their log
            if ($currentActive) {
                $currentActive->update(['is_active' => false]);
                
                // Update the log entry to set the end time
                DriverLog::where('franchise_id', $franchiseId)
                    ->where('driver_id', $currentActive->driver_id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => $now]);
            }

            // 3. Activate the new driver
            DriverAssignment::where('franchise_id', $franchiseId)
                ->where('driver_id', $newDriverId)
                ->update(['is_active' => true]);

            // 4. Create a new log entry for the new driver
            DriverLog::create([
                'franchise_id' => $franchiseId,
                'driver_id' => $newDriverId,
                'started_at' => $now,
            ]);
        });

        return redirect()->back()->with('success', 'Active driver updated successfully.');
    }

/**
     * Update the schedule for a specific driver assignment.
     */
    public function updateDriverSchedule(Request $request, $franchiseId, $assignmentId)
    {
        $request->validate([
            'schedule' => 'required|array',
            'schedule.*.day' => 'required|string',
            'schedule.*.is_off' => 'required|boolean',
            'schedule.*.start' => 'nullable|string',
            'schedule.*.end' => 'nullable|string',
        ]);

        $newSchedule = $request->schedule;

        // 1. Fetch the current assignment first to get the driver_id
        $assignment = DriverAssignment::where('franchise_id', $franchiseId)
            ->where('id', $assignmentId)
            ->firstOrFail();

        // 2. Fetch assignments to check against: 
        // Either in the same franchise (other drivers) OR for the same driver (other franchises)
        $assignmentsToCheck = DriverAssignment::where('id', '!=', $assignmentId)
            ->whereNotNull('schedule')
            ->where(function($query) use ($franchiseId, $assignment) {
                $query->where('franchise_id', $franchiseId)
                      ->orWhere('driver_id', $assignment->driver_id);
            })
            ->with(['driver', 'franchise']) // Eager-load the franchise relationship too
            ->get();

        foreach ($newSchedule as $newDay) {
            // Skip days off
            if ($newDay['is_off'] || empty($newDay['start']) || empty($newDay['end'])) {
                continue;
            }

            $newStart = strtotime($newDay['start']);
            $newEnd = strtotime($newDay['end']);

            // Prevent overnight/invalid scheduling for a single day
            if ($newEnd <= $newStart) {
                return back()->withErrors([
                    'schedule' => "Invalid schedule on {$newDay['day']}. The end time must be later than the start time."
                ]);
            }

            foreach ($assignmentsToCheck as $other) {
                $otherSchedule = $other->schedule;
                if (!$otherSchedule) continue;

                foreach ($otherSchedule as $otherDay) {
                    if ($otherDay['day'] === $newDay['day'] && !$otherDay['is_off'] && !empty($otherDay['start']) && !empty($otherDay['end'])) {
                        $otherStart = strtotime($otherDay['start']);
                        $otherEnd = strtotime($otherDay['end']);

                        // Overlap condition: Start A < End B AND End A > Start B
                        if ($newStart < $otherEnd && $newEnd > $otherStart) {
                            
                            // Format the NEW requested times
                            $formattedNewStart = date('g:i A', $newStart);
                            $formattedNewEnd   = date('g:i A', $newEnd);

                            // Format the EXISTING scheduled times from the database
                            $formattedOtherStart = date('g:i A', $otherStart);
                            $formattedOtherEnd   = date('g:i A', $otherEnd);
                            
                            // Check WHICH rule was violated to give a specific error message
                            if ($other->franchise_id == $franchiseId) {
                                $driverName = $other->driver ? $other->driver->first_name . ' ' . $other->driver->last_name : 'another driver';
                                return back()->withErrors([
                                    'schedule' => "Schedule overlap detected on {$newDay['day']}. Your requested time ({$formattedNewStart} - {$formattedNewEnd}) conflicts with {$driverName}'s schedule ({$formattedOtherStart} - {$formattedOtherEnd}) in this franchise."
                                ]);
                            } else {
                                // Extract the franchise number with a fallback
                                $franchiseNumber = $other->franchise ? $other->franchise->franchise_number : 'an unknown franchise';
                                
                                return back()->withErrors([
                                    'schedule' => "Schedule overlap detected on {$newDay['day']}. This driver is already scheduled from {$formattedOtherStart} - {$formattedOtherEnd} in Franchise #{$franchiseNumber}."
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // 3. Update if all checks pass
        $assignment->update([
            'schedule' => $newSchedule
        ]);

        return redirect()->back()->with('success', 'Driver schedule updated successfully.');
    }
}