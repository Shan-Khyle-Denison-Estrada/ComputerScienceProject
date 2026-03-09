<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Payment;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $settings = SystemSetting::first();
        $renewalStart = $settings->annual_renewal_start ?? '01-01';
        
        // 1. Determine Available Fiscal Years
        $oldestPaymentDate = Payment::min('created_at') ?? now();
        $minYear = Carbon::parse($oldestPaymentDate)->year;
        $currentYear = now()->year;
        
        $availableFiscalYears = [];
        // Generate from slightly before earliest record to current year to ensure coverage
        for ($y = $minYear - 1; $y <= $currentYear; $y++) {
            if ($renewalStart === '01-01') {
                $availableFiscalYears[] = (string) $y;
            } else {
                $availableFiscalYears[] = $y . '-' . ($y + 1);
            }
        }
        $availableFiscalYears = array_reverse(array_unique($availableFiscalYears));

        // Determine Default "Current" Fiscal Year
        $startDateThisYear = Carbon::createFromFormat('Y-m-d', "{$currentYear}-{$renewalStart}")->startOfDay();
        if ($renewalStart === '01-01') {
            $defaultFyStr = (string) $currentYear;
        } else {
            $defaultFyStr = now()->lt($startDateThisYear) 
                ? ($currentYear - 1) . '-' . $currentYear 
                : $currentYear . '-' . ($currentYear + 1);
        }

        // 2. Set Active Filter Context
        $selectedFiscalYear = $request->query('fiscal_year', $defaultFyStr);
        $chartPeriod = $request->query('chart_period', 'monthly');

        // Ensure selected FY is valid, fallback to default if not
        if (!in_array($selectedFiscalYear, $availableFiscalYears)) {
            $selectedFiscalYear = $defaultFyStr;
        }

        // 3. Calculate Date Ranges for Selected Fiscal Year & Previous Fiscal Year (for growth stats)
        if ($renewalStart === '01-01') {
            $fyStart = Carbon::createFromFormat('Y-m-d', "{$selectedFiscalYear}-01-01")->startOfDay();
            $fyEnd = $fyStart->copy()->endOfYear();
        } else {
            $parts = explode('-', $selectedFiscalYear);
            $y1 = $parts[0] ?? $currentYear;
            $y2 = $parts[1] ?? ($currentYear + 1);
            $fyStart = Carbon::createFromFormat('Y-m-d', "{$y1}-{$renewalStart}")->startOfDay();
            $fyEnd = Carbon::createFromFormat('Y-m-d', "{$y2}-{$renewalStart}")->subDay()->endOfDay();
        }

        $prevFyStart = $fyStart->copy()->subYear();
        $prevFyEnd = $fyEnd->copy()->subYear();

        // 4. Top Cards Statistics (Scoped to Fiscal Year)
        $totalFranchises = Franchise::whereBetween('created_at', [$fyStart, $fyEnd])->count();
        $prevFranchises = Franchise::whereBetween('created_at', [$prevFyStart, $prevFyEnd])->count();
        $franchiseGrowth = $prevFranchises > 0 
            ? (($totalFranchises - $prevFranchises) / $prevFranchises) * 100 
            : ($totalFranchises > 0 ? 100 : 0);

        $totalOperators = Operator::whereBetween('created_at', [$fyStart, $fyEnd])->count();
        
        $totalRevenue = Payment::whereBetween('created_at', [$fyStart, $fyEnd])->sum('amount_paid');
        $prevRevenue = Payment::whereBetween('created_at', [$prevFyStart, $prevFyEnd])->sum('amount_paid');
        $revenueGrowth = $prevRevenue > 0
            ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100
            : ($totalRevenue > 0 ? 100 : 0);

        // 5. Dynamic Chart Data based on Timeline Selection
        $dbFormat = match($chartPeriod) {
            'daily' => "DATE_FORMAT(created_at, '%b %d, %Y')",
            'weekly' => "CONCAT('Week ', WEEK(created_at, 1), ', ', YEAR(created_at))",
            'quarterly' => "CONCAT('Q', QUARTER(created_at), ' ', YEAR(created_at))",
            'annually' => "DATE_FORMAT(created_at, '%Y')",
            default => "DATE_FORMAT(created_at, '%b %Y')", // Monthly
        };

        $revenueData = Payment::select(
            DB::raw('sum(amount_paid) as sums'), 
            DB::raw("$dbFormat as label"),
            DB::raw("MIN(created_at) as sort_date") // Ensure sequential chronological sorting
        )
        ->whereBetween('created_at', [$fyStart, $fyEnd])
        ->groupBy('label')
        ->orderBy('sort_date')
        ->get();

        $revenueChart = [
            'labels' => $revenueData->pluck('label'),
            'data' => $revenueData->pluck('sums'),
        ];

        // 6. Recent Payments Table (Scoped to Fiscal Year & Strictly Limited to 5)
        $recentPayments = Payment::with(['assessment.application.franchise.currentActiveUnit.newUnit']) 
            ->whereBetween('created_at', [$fyStart, $fyEnd])
            ->latest()
            ->limit(4) // Strictly limit the amount of data returned to 5
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount_paid,
                    'date' => $payment->created_at->format('M d, Y'),
                    'plate_number' => $payment->assessment->application->franchise->currentActiveUnit->newUnit->plate_number ?? 'No Unit', 
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
            'chart' => $revenueChart, 
            'recent_payments' => $recentPayments,
            'available_fiscal_years' => array_values($availableFiscalYears),
            'filters' => [
                'fiscal_year' => $selectedFiscalYear,
                'chart_period' => $chartPeriod,
            ]
        ]);
    }
}