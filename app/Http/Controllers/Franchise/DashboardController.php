<?php

namespace App\Http\Controllers\Franchise;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Franchise;

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
            'currentActiveUnit.newUnit.make', // Current Unit
            'unitHistory.newUnit.make',       // Unit History
            'driver.user',                    // Current Driver
            'driverAssignments.driver.user',  // Driver History
            'ownershipHistory.newOwner.user', 
            'ownershipHistory.previousOwner.user', 
            'zone',                           // Zone Information
            'assessments.payments',           // For Payment History
            'assessments.particulars'         // To show what was paid for
        ])
        ->get();

        if ($franchises->isEmpty()) {
             return Inertia::render('Franchise/Dashboard', [
                'hasFranchise' => false,
                'operator' => $operator->load('user'),
                'franchises' => []
            ]);
        }

        // 3. Process each franchise to format nested data
        $franchises->transform(function ($franchise) {
            
            // Calculate Status dynamically 
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
            $franchise->driver_history = $franchise->driverAssignments->sortByDesc('created_at')->values();

            return $franchise;
        });

        return Inertia::render('Franchise/Dashboard', [
            'hasFranchise' => true,
            'franchises' => $franchises,
            'operator' => $operator->load('user'),
        ]);
    }
}