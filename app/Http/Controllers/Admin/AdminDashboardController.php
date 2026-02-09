<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Payment;
use App\Models\Driver;
use App\Models\Operator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 1. Top Cards Statistics
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

        // 2. Single Chart: Monthly Revenue (Last 6 Months)
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

        // 3. Recent Payments Table
        $recentPayments = Payment::with(['assessment.franchise.currentActiveUnit.newUnit'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount_paid,
                    'date' => $payment->created_at->format('M d'),
                    'plate_number' => $payment->assessment->franchise->currentActiveUnit->newUnit->plate_number ?? 'No Unit',
                    'payee' => $payment->payee_first_name . ' ' . $payment->payee_last_name,
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_franchises' => $totalFranchises,
                'franchise_growth' => round($franchiseGrowth, 1),
                'total_operators' => $totalOperators,
                'total_revenue' => $totalRevenue,
                'revenue_growth' => round($revenueGrowth, 1),
            ],
            'chart' => $revenueChart, // Passing single chart
            'recent_payments' => $recentPayments
        ]);
    }
}