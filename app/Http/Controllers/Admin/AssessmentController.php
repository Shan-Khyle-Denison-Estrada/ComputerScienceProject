<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Particular;
use App\Models\Franchise;
use App\Models\Operator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        // --- AUTO-UPDATE OVERDUE STATUS ---
        Assessment::where('assessment_status', 'pending')
            ->whereDate('assessment_due', '<', now()->toDateString())
            ->update(['assessment_status' => 'overdue']);

        $search = $request->input('search');
        $status = $request->input('status');
        $franchiseId = $request->input('franchise_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        // 1. Pagination set to 6 rows
        $assessments = Assessment::query()
            // Added currentOwnership.newOwner.user to safely fetch operator names
            ->with(['particulars', 'payments', 'application', 'franchise.currentOwnership.newOwner.user']) 
            ->when($search, function($query, $search) {
                // Strip "ASM-", "ASM", and leading zeros so "ASM-000012" becomes "12"
                $parsedId = preg_replace('/^ASM-?0*/i', '', $search);

                $query->where(function($q) use ($search, $parsedId) {
                    // FIX: Explicitly target the assessments table to prevent ambiguous column errors during joins
                    $q->where('assessments.id', 'like', "%{$parsedId}%")
                      ->orWhere('assessments.remarks', 'like', "%{$search}%")
                      // Query the attached application's reference_number
                      ->orWhereHas('application', function ($aq) use ($search) {
                          $aq->where('reference_number', 'like', "%{$search}%");
                      })
                      ->orWhereHas('franchise', function ($fq) use ($search) {
                          $fq->where('franchise_number', 'like', "%{$search}%")
                             // FIX: Properly search deeply into the users table for the owner name
                             ->orWhereHas('currentOwnership.newOwner.user', function ($uq) use ($search) {
                                 $uq->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                             });
                      });
                });
            })
            ->when($status, function($query, $status) {
                $query->where('assessment_status', $status);
            })
            ->when($franchiseId, function($query, $franchiseId) {
                $query->where('franchise_id', $franchiseId);
            })
            ->when($dateFrom, function($query, $dateFrom) {
                $query->whereDate('assessment_date', '>=', $dateFrom);
            })
            ->when($dateTo, function($query, $dateTo) {
                $query->whereDate('assessment_date', '<=', $dateTo);
            })
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'franchise') {
                    $query->leftJoin('franchises', 'assessments.franchise_id', '=', 'franchises.id')
                          ->orderBy('franchises.franchise_number', $sortDirection)
                          ->select('assessments.*');
                } else {
                    $allowedSorts = ['id', 'assessment_status', 'assessment_due', 'total_amount_due'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy($sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                $query->latest();
            })
            ->paginate(6)
            ->withQueryString();

        $particulars = Particular::orderBy('name')->get();
        
        // Eager load the deeply nested relationships and map them into the required array format
        $franchises = Franchise::with(['currentOwnership.newOwner.user'])
            ->get()
            ->map(function ($franchise) {
                return [
                    'id' => $franchise->id,
                    'franchise_number' => $franchise->franchise_number,
                    'owner_name' => $franchise->currentOwnership?->newOwner?->user?->full_name ?? 'Unknown Owner',
                ];
            });

        return Inertia::render('Admin/Assessments/Index', [
            'assessments' => $assessments,
            'filters' => $request->only(['search', 'status', 'franchise_id', 'date_from', 'date_to', 'sortField', 'sortDirection']),
            'particulars' => $particulars,
            'franchises' => $franchises,
            'userRole' => auth()->user()->role->value ?? auth()->user()->role,
        ]);
    }

public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'nullable|exists:franchises,id',
            'assessment_date' => 'required|date',
            'assessment_due' => 'nullable|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.particular_id' => 'required|exists:particulars,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            
            // 1. Fetch Particulars & Build Base List
            $particularIds = array_column($validated['items'], 'particular_id');
            $dbParticulars = Particular::whereIn('id', $particularIds)->get()->keyBy('id');

            $baseTotal = 0;
            $itemsToAttach = [];

            foreach ($validated['items'] as $item) {
                $p = $dbParticulars[$item['particular_id']];
                
                if ($p->is_system || $p->code === 'surcharge' || $p->code === 'interest') continue; 

                $subtotal = $p->amount * $item['quantity'];
                $baseTotal += $subtotal;

                $itemsToAttach[$item['particular_id']] = [
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal
                ];
            }

            // 2. Create Assessment with Base Amount
            $assessment = Assessment::create([
                'franchise_id' => $validated['franchise_id'],
                'assessment_date' => $validated['assessment_date'],
                // 'assessment_due' => $validated['assessment_due'],
                'total_amount_due' => $baseTotal,
                'remarks' => $validated['remarks'],
                'assessment_status' => 'pending'
            ]);

            // 3. Attach Items
            $assessment->particulars()->attach($itemsToAttach);

            // 4. TRIGGER AUTOMATIC PENALTIES
            // This will instantly inject surcharge/interest if created past the due date
            $assessment->recalculatePenalties(); 

            // 5. SEND NOTIFICATION EMAIL
            // Load relationships needed to find the email address and populate the email template
            $assessment->load(['application', 'franchise.currentOwnership.newOwner.user', 'particulars']);
            
            // Fallback: Try application email first, then operator's user email
            $email = $assessment->application->email ?? $assessment->franchise->currentOwnership->newOwner->user->email ?? null;

            if ($email) {
                \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\AssessmentNotification($assessment));
            }
        });

        return redirect()->back()->with('success', 'Assessment created successfully.');
    }

    public function reportPdf(Request $request)
    {
        $query = $this->buildReportQuery($request);
        $assessments = $query->get();

        // Basic HTML structure for the PDF report. 
        $html = '
        <div style="font-family: sans-serif; font-size: 12px;">
            <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Assessment Records Report</h2>
            <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <tr style="background-color: #f3f4f6;">
                    <th style="text-align: left;">ASM ID</th>
                    <th style="text-align: left;">Franchise No.</th>
                    <th style="text-align: left;">Operator</th>
                    <th style="text-align: left;">Date Issued</th>
                    <th style="text-align: left;">Due Date</th>
                    <th style="text-align: left;">Status</th>
                    <th style="text-align: right;">Total Due</th>
                </tr>';
        
        foreach ($assessments as $assessment) {
            $operatorName = $assessment->franchise->currentOwnership->newOwner->user->first_name ?? '';
            $operatorName .= ' ' . ($assessment->franchise->currentOwnership->newOwner->user->last_name ?? '');
            
            if (trim($operatorName) === '' && $assessment->application) {
                $operatorName = trim(($assessment->application->first_name ?? '') . ' ' . ($assessment->application->last_name ?? ''));
            }

            $html .= '<tr>
                        <td>ASM-' . str_pad($assessment->id, 6, '0', STR_PAD_LEFT) . '</td>
                        <td>' . ($assessment->franchise->franchise_number ?? 'N/A') . '</td>
                        <td>' . (trim($operatorName) !== '' ? trim($operatorName) : 'N/A') . '</td>
                        <td>' . \Carbon\Carbon::parse($assessment->assessment_date)->format('M d, Y') . '</td>
                        <td>' . \Carbon\Carbon::parse($assessment->assessment_due)->format('M d, Y') . '</td>
                        <td style="text-transform: capitalize;">' . $assessment->assessment_status . '</td>
                        <td style="text-align: right;">PHP ' . number_format($assessment->total_amount_due, 2) . '</td>
                      </tr>';
        }
        $html .= '</table></div>';

        // NOTE: This relies on "barryvdh/laravel-dompdf". Run `composer require barryvdh/laravel-dompdf` if you haven't yet.
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        
        $filename = 'Assessment_Records_' . now()->format('Ymd_His') . '.pdf';

        if ($request->has('download') && $request->input('download') == 1) {
            return $pdf->download($filename);
        }
        
        return $pdf->stream($filename);
    }

    public function reportExcel(Request $request)
    {
        $query = $this->buildReportQuery($request);
        $assessments = $query->get();

        $filename = "Assessment_Records_" . now()->format('Ymd_His') . ".xls";

        // Build an HTML table. Excel natively parses this into spreadsheet columns and rows.
        $html = '<table border="1">
                    <tr style="background-color: #f3f4f6; font-weight: bold;">
                        <th>ASM ID</th>
                        <th>Franchise No.</th>
                        <th>Operator</th>
                        <th>Date Issued</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Total Amount Due</th>
                        <th>Remarks</th>
                    </tr>';

        foreach ($assessments as $assessment) {
            $operatorName = $assessment->franchise->currentOwnership->newOwner->user->first_name ?? '';
            $operatorName .= ' ' . ($assessment->franchise->currentOwnership->newOwner->user->last_name ?? '');
            
            if (trim($operatorName) === '' && $assessment->application) {
                $operatorName = trim(($assessment->application->first_name ?? '') . ' ' . ($assessment->application->last_name ?? ''));
            }

            $html .= '<tr>
                        <td>ASM-' . str_pad($assessment->id, 6, '0', STR_PAD_LEFT) . '</td>
                        <td>' . ($assessment->franchise->franchise_number ?? 'N/A') . '</td>
                        <td>' . (trim($operatorName) !== '' ? trim($operatorName) : 'N/A') . '</td>
                        <td>' . \Carbon\Carbon::parse($assessment->assessment_date)->format('M d, Y') . '</td>
                        <td>' . \Carbon\Carbon::parse($assessment->assessment_due)->format('M d, Y') . '</td>
                        <td style="text-transform: capitalize;">' . $assessment->assessment_status . '</td>
                        <td>' . number_format($assessment->total_amount_due, 2) . '</td>
                        <td>' . $assessment->remarks . '</td>
                      </tr>';
        }
        $html .= '</table>';

        $headers = [
            "Content-Type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=\"$filename\"",
            "Pragma"              => "no-cache",
            "Expires"             => "0"
        ];

        return response($html, 200, $headers);
    }

    // Helper method to keep query logic identical to the index method
    private function buildReportQuery(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $franchiseId = $request->input('franchise_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        return Assessment::query()
            ->with(['application', 'franchise.currentOwnership.newOwner.user']) 
            ->when($search, function($query, $search) {
                $parsedId = preg_replace('/^ASM-?0*/i', '', $search);
                $query->where(function($q) use ($search, $parsedId) {
                    $q->where('assessments.id', 'like', "%{$parsedId}%")
                      ->orWhere('assessments.remarks', 'like', "%{$search}%")
                      ->orWhereHas('application', function ($aq) use ($search) {
                          $aq->where('reference_number', 'like', "%{$search}%");
                      })
                      ->orWhereHas('franchise', function ($fq) use ($search) {
                          $fq->where('franchise_number', 'like', "%{$search}%")
                             ->orWhereHas('currentOwnership.newOwner.user', function ($uq) use ($search) {
                                 $uq->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                             });
                      });
                });
            })
            ->when($status, function($query, $status) {
                $query->where('assessment_status', $status);
            })
            ->when($franchiseId, function($query, $franchiseId) {
                $query->where('franchise_id', $franchiseId);
            })
            ->when($dateFrom, function($query, $dateFrom) {
                $query->whereDate('assessment_date', '>=', $dateFrom);
            })
            ->when($dateTo, function($query, $dateTo) {
                $query->whereDate('assessment_date', '<=', $dateTo);
            })
            ->orderBy('id', 'desc');
    }
}