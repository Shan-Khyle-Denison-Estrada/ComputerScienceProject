<?php

namespace App\Http\Controllers\Franchise;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Franchise;
use App\Models\Ownership;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Find the Operator record linked to this User
        $operator = $user->operator; 

        if (!$operator) {
            // Handle edge case where user has role but no operator record
            return Inertia::render('Franchise/Dashboard', [
                'hasFranchise' => false
            ]);
        }

        // 2. Find the *Active* Franchise for this Operator
        // We look for the latest ownership record where this operator is the 'new_owner'
        // and link it to the franchise.
        $ownership = Ownership::where('new_operator_id', $operator->id)
            ->latest()
            ->with('franchise')
            ->first();

        if (!$ownership || !$ownership->franchise) {
             return Inertia::render('Franchise/Dashboard', [
                'hasFranchise' => false,
                'operator' => $operator->load('user')
            ]);
        }

        $franchise = $ownership->franchise;

        // 3. Load all deep relationships needed for the dashboard
        $franchise->load([
            'currentActiveUnit.newUnit.make', // Unit details
            'driver.user',                         // Current driver
            'zone',                           // Zone
            'assessments.payments',           // Payment History (via Assessments)
            // Driver History isn't directly a table, usually inferred or logged. 
            // For now, we will just show the current driver. 
            // If you have a driver_history table, load it here.
        ]);

        // Flatten payments for the table
        $payments = $franchise->assessments->flatMap(function($assessment) {
            return $assessment->payments->map(function($payment) use ($assessment) {
                $payment->assessment_id = $assessment->id;
                $payment->particulars = $assessment->particulars->pluck('name')->join(', ');
                return $payment;
            });
        })->sortByDesc('created_at')->values();

        return Inertia::render('Franchise/Dashboard', [
            'hasFranchise' => true,
            'franchise' => $franchise,
            'operator' => $operator->load('user'),
            'payments' => $payments
        ]);
    }
}