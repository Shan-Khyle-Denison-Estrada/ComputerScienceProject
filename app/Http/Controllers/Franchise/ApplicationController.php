<?php

namespace App\Http\Controllers\Franchise;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Franchise;
use App\Models\EvaluationRequirement;

class ApplicationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $operator = $user->operator; 

        // 1. Handle Non-Operator Users
        if (!$operator) {
            return Inertia::render('Franchise/MakeApplication', [
                'hasFranchise' => false,
                'franchises' => [],
                'operator' => null,
                'evaluationRequirements' => []
            ]);
        }

        // 2. Fetch all active evaluation requirements grouped by the 'group' column 
        // Example groups: 'Renewal', 'Change of Owner', 'Change of Unit'
        $evaluationRequirements = EvaluationRequirement::where('is_active', true)
            ->get()
            ->groupBy('group');

        // 3. Find ALL Active Franchises for this Operator (Same as DashboardController)
        $franchises = Franchise::whereHas('currentOwnership', function ($query) use ($operator) {
            $query->where('new_operator_id', $operator->id);
        })
        ->with([
            'currentOwnership',               
            'currentActiveUnit.newUnit.make', 
            'unitHistory.newUnit.make',       
            'driverAssignments.driver.user',  
            'ownershipHistory.newOwner.user', 
            'ownershipHistory.previousOwner.user', 
            'zone',                           
            'assessments.payments',           
            'assessments.particulars'         
        ])
        ->get();

        // 4. Process each franchise to format nested data
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

        // Assuming your page view is named 'Franchise/MakeApplication'
        return Inertia::render('Franchise/MakeApplication', [
            'hasFranchise' => true,
            'franchises' => $franchises,
            'operator' => $operator->load('user'),
            'evaluationRequirements' => $evaluationRequirements
        ]);
    }
}