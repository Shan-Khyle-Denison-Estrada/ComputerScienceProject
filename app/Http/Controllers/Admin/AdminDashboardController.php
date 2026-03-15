<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Payment;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\SystemSetting;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

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
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');

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
            'custom' => "DATE_FORMAT(created_at, '%b %d, %Y')", // Defaulting custom to daily
            default => "DATE_FORMAT(created_at, '%b %Y')", // Monthly
        };

        // Determine Chart Date Scope
        $chartStartDate = $fyStart;
        $chartEndDate = $fyEnd;

        if ($chartPeriod === 'custom' && $customStart && $customEnd) {
            try {
                $chartStartDate = Carbon::parse($customStart)->startOfDay();
                $chartEndDate = Carbon::parse($customEnd)->endOfDay();
            } catch (\Exception $e) {
                // If invalid date parsing occurs, fallback to fiscal year
                $chartStartDate = $fyStart;
                $chartEndDate = $fyEnd;
            }
        }

        $revenueData = Payment::select(
            DB::raw('sum(amount_paid) as sums'), 
            DB::raw("$dbFormat as label"),
            DB::raw("MIN(created_at) as sort_date") // Ensure sequential chronological sorting
        )
        ->whereBetween('created_at', [$chartStartDate, $chartEndDate])
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
            ->limit(4) // Keeping the limit to 4 as in your original file
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
                'start_date' => $customStart,
                'end_date' => $customEnd,
            ]
        ]);
    }

    public function report(Request $request)
    {
        $settings = SystemSetting::first();
        $renewalStart = $settings->annual_renewal_start ?? '01-01';
        $currentYear = now()->year;
        
        // Get the requested fiscal year from the URL
        $selectedFiscalYear = $request->query('fiscal_year', (string)$currentYear);

        // Calculate Date Ranges
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

        // Gather Comprehensive Report Data
        $totalFranchises = Franchise::whereBetween('created_at', [$fyStart, $fyEnd])->count();
        $totalOperators = Operator::whereBetween('created_at', [$fyStart, $fyEnd])->count();
        $totalRevenue = Payment::whereBetween('created_at', [$fyStart, $fyEnd])->sum('amount_paid');
        $totalComplaints = Complaint::whereBetween('created_at', [$fyStart, $fyEnd])->count();

        // Monthly Revenue Breakdown
        $monthlyRevenue = Payment::select(
            DB::raw('sum(amount_paid) as total'), 
            DB::raw("DATE_FORMAT(created_at, '%M %Y') as month"),
            DB::raw("MIN(created_at) as sort_date")
        )
        ->whereBetween('created_at', [$fyStart, $fyEnd])
        ->groupBy('month')
        ->orderBy('sort_date')
        ->get();

        return Inertia::render('Admin/Report', [
            'fiscal_year' => $selectedFiscalYear,
            'report_date' => now()->format('F d, Y'),
            'date_range' => $fyStart->format('M d, Y') . ' - ' . $fyEnd->format('M d, Y'),
            'stats' => [
                'franchises' => $totalFranchises,
                'operators' => $totalOperators,
                'revenue' => $totalRevenue,
                'complaints' => $totalComplaints,
            ],
            'monthly_revenue' => $monthlyRevenue
        ]);
    }

    public function downloadReport(Request $request)
    {
        $settings = SystemSetting::first();
        $renewalStart = $settings->annual_renewal_start ?? '01-01';
        $currentYear = now()->year;
        
        $selectedFiscalYear = $request->query('fiscal_year', (string)$currentYear);

        // Calculate Date Ranges
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

        // Gather Comprehensive Report Data
        $totalFranchises = Franchise::whereBetween('created_at', [$fyStart, $fyEnd])->count();
        $totalOperators = Operator::whereBetween('created_at', [$fyStart, $fyEnd])->count();
        $totalRevenue = Payment::whereBetween('created_at', [$fyStart, $fyEnd])->sum('amount_paid');
        
        // Complaints Data
        $totalComplaints = Complaint::whereBetween('created_at', [$fyStart, $fyEnd])->count();
        $resolvedComplaints = Complaint::whereBetween('created_at', [$fyStart, $fyEnd])
            ->where('status', 'resolved') // Ensure this matches your exact database string
            ->count();

        // Monthly Revenue Breakdown
        $monthlyRevenue = Payment::select(
            DB::raw('sum(amount_paid) as total'), 
            DB::raw("DATE_FORMAT(created_at, '%M %Y') as month"),
            DB::raw("MIN(created_at) as sort_date")
        )
        ->whereBetween('created_at', [$fyStart, $fyEnd])
        ->groupBy('month')
        ->orderBy('sort_date')
        ->get();

        // --- Bulletproof Logo Handling for DomPDF using office_logo_path ---
        $logoBase64 = null;
        $logoColumn = $settings->office_logo_path ?? null; 

        if ($logoColumn) { 
            // 1. Check if the file exists in the internal storage folder
            $storagePath = storage_path('app/public/' . $logoColumn);
            
            // 2. Fallback: Check if the file was placed directly in the public folder
            $publicPath = public_path($logoColumn);
            
            // Determine which path is valid
            $actualLogoPath = null;
            if (file_exists($storagePath)) {
                $actualLogoPath = $storagePath;
            } elseif (file_exists($publicPath)) {
                $actualLogoPath = $publicPath;
            } elseif (file_exists(public_path('storage/' . $logoColumn))) {
                $actualLogoPath = public_path('storage/' . $logoColumn);
            }

            // Convert to Base64 using precise MIME type detection
            if ($actualLogoPath) {
                $mimeType = mime_content_type($actualLogoPath);
                $fileData = file_get_contents($actualLogoPath);
                $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileData);
            }
        }

        $data = [
            'fiscal_year' => $selectedFiscalYear,
            'report_date' => now()->format('F d, Y'),
            'date_range' => $fyStart->format('M d, Y') . ' - ' . $fyEnd->format('M d, Y'),
            'logo_base64' => $logoBase64,
            'system_name' => $settings->system_name ?? 'Tricycle Franchise Management System',
            'stats' => [
                'franchises' => $totalFranchises,
                'operators' => $totalOperators,
                'revenue' => $totalRevenue,
                'complaints' => $totalComplaints,
                'resolved_complaints' => $resolvedComplaints,
            ],
            'monthly_revenue' => $monthlyRevenue
        ];

        // Generate PDF using a Blade view
        $pdf = Pdf::loadView('reports.annual-report', $data);

        // Download the file directly
        return $pdf->download("Annual_Report_FY_{$selectedFiscalYear}.pdf");
    }
}