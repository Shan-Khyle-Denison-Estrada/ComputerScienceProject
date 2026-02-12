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
            'assessments.particulars'         
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
}