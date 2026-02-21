<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Payment;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\SystemSetting; // <-- Added SystemSetting import
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 1. Calculate Current Fiscal Year String
        $settings = SystemSetting::first();
        $currentYear = now()->year;
        $fiscalYearEnd = $settings->fiscal_year_end ?? '12-31';
        $deadlineThisYear = Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$fiscalYearEnd}")->endOfDay();

        if (now()->lte($deadlineThisYear)) {
            $fiscalYearString = ($currentYear - 1) . '-' . $currentYear;
        } else {
            $fiscalYearString = $currentYear . '-' . ($currentYear + 1);
        }

        // 2. Top Cards Statistics
        $totalFranchises = Franchise::count();
        
        $franchisesLastMonth = Franchise::where('created_at', '<', Carbon::now()->subMonth())->count();
        $franchiseGrowth = $franchisesLastMonth > 0 
            ? (($totalFranchises - $franchisesLastMonth) / $franchisesLastMonth) * 100 
            : 0;

        $totalOperators = Operator::count();
        
        $totalRevenue = Payment::sum('amount_paid');
        
        $revenueLastMonth = Payment::where('created_at', '<', Carbon::now()->subMonth())->sum('amount_paid');
        $revenueGrowth = $revenueLastMonth > 0
            ? (($totalRevenue - $revenueLastMonth) / $revenueLastMonth) * 100
            : 0;

        // 3. Single Chart: Monthly Revenue (Last 6 Months)
        $monthlyRevenue = Payment::select(
            DB::raw('sum(amount_paid) as sums'), 
            DB::raw("DATE_FORMAT(created_at,'%M') as month")
        )
        ->where('created_at', '>=', Carbon::now()->subMonths(6))
        ->groupBy('month')
        ->orderBy('created_at')
        ->get();

        $revenueChart = [
            'labels' => $monthlyRevenue->pluck('month'),
            'data' => $monthlyRevenue->pluck('sums'),
        ];

        // 4. Recent Payments Table
        $recentPayments = Payment::with(['assessment.application.franchise.currentActiveUnit.newUnit']) 
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount_paid,
                    'date' => $payment->created_at->format('M d'),
                    'plate_number' => $payment->assessment->application->franchise->currentActiveUnit->newUnit->plate_number ?? 'No Unit', 
                    'payee' => $payment->payee_first_name . ' ' . $payment->payee_last_name,
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'current_fiscal_year' => $fiscalYearString, // <-- Passed to Vue
                'total_franchises' => $totalFranchises,
                'franchise_growth' => round($franchiseGrowth, 1),
                'total_operators' => $totalOperators,
                'total_revenue' => $totalRevenue,
                'revenue_growth' => round($revenueGrowth, 1),
            ],
            'chart' => $revenueChart, 
            'recent_payments' => $recentPayments
        ]);
    }
}