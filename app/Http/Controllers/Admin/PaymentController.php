<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Assessment;
use App\Models\Barangay;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $city = $request->input('city');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        $payments = Payment::query()
            // Eager load the nested relationships to prevent N+1 queries
            ->with([
                'assessment.franchise.currentOwnership.newOwner.user',
                'assessment.application.franchise.currentOwnership.newOwner.user'
            ])
            // 1. Handle Search (OR Number, First/Last Name, combined names)
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('or_number', 'like', "%{$search}%")
                      ->orWhere('payee_first_name', 'like', "%{$search}%")
                      ->orWhere('payee_last_name', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(payee_first_name, ' ', payee_last_name) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("CONCAT(payee_last_name, ' ', payee_first_name) LIKE ?", ["%{$search}%"]);
                });
            })
            // 2. Handle City Filter
            ->when($city, function ($query, $city) {
                $query->where('payee_city', 'like', "%{$city}%");
            })
            ->when($dateFrom, function($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            // 3. Handle Sorting
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'payee_name') {
                    $query->orderBy('payee_last_name', $sortDirection)
                          ->orderBy('payee_first_name', $sortDirection);
                } else {
                    $allowedSorts = ['or_number', 'amount_paid', 'created_at'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy($sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                $query->latest();
            })
            ->paginate(6)
            ->withQueryString();

        // Transform the collection to append franchise details on the fly
        $payments->getCollection()->transform(function ($payment) {
            // An assessment might connect to a franchise directly, or via an application
            $franchise = $payment->assessment?->franchise ?? $payment->assessment?->application?->franchise;

            // Use setAttribute to force Laravel to include these in the JSON response
            $payment->setAttribute(
                'franchise_number', 
                $franchise?->franchise_number ?? 'No Franchise Number'
            );
            
            $payment->setAttribute(
                'franchise_owner', 
                $franchise?->currentOwnership?->newOwner?->user?->full_name
            );

            // Optionally attach application reference id for Vue fallback
            $payment->setAttribute(
                'application_reference_id', 
                $payment->assessment?->application?->reference_number
            );

            return $payment;
        });
            
        // Fetch Barangays for the dropdown
        $barangays = Barangay::select('id', 'name')->orderBy('name')->get();

        // NEW: Fetch Pending/Overdue Assessments with their current balance
        // We eagerly load payments to calculate the balance on the fly if needed, 
        // or rely on a raw query for performance. Here we use Eloquent for simplicity.
        $assessments = Assessment::whereIn('assessment_status', ['pending', 'overdue'])
            ->with(['application', 'particulars']) // <-- 1. Eager load relationships
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    // 2. Grab the reference_number from the loaded Application relationship
                    // Provide a fallback reference if it's a standalone assessment
                    'reference_number' => $assessment->application 
                        ? $assessment->application->reference_number 
                        : 'ASM-' . $assessment->id,
                    // Keep this for backward compatibility if needed by other components, or remove it
                    'application_reference_id' => $assessment->application ? $assessment->application->reference_number : null,
                    'label' => $assessment->remarks ?? 'Application Assessment',
                    'balance' => $assessment->balance,
                    'total_amount' => $assessment->total_amount_due,
                    // 3. Map the particulars and grab the subtotal from the pivot table
                    'particulars' => $assessment->particulars->map(function ($particular) {
                        return [
                            'name' => $particular->name,
                            'quantity' => $particular->pivot->quantity, // <-- ADD THIS LINE
                            'amount' => $particular->pivot->subtotal 
                        ];
                    })
                ];
            });

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'city', 'date_from', 'date_to', 'sortField', 'sortDirection']), // <-- NEW FILTERS ADDED HERE
            'barangays' => $barangays,
            'assessments' => $assessments,
            'userRole' => auth()->user()->role->value ?? auth()->user()->role,
        ]);
    }

public function store(Request $request)
    {
        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'amount_paid' => 'required|numeric|min:0',
            'payee_first_name' => 'required|string|max:255',
            'payee_middle_name' => 'nullable|string|max:255',
            'payee_last_name' => 'required|string|max:255',
            'payee_contact_number' => 'required|string|max:20',
            'payee_street_address' => 'required|string|max:255',
            'payee_province' => 'required|string|max:255', // <-- ADDED
            'payee_city' => 'required|string|max:255',
            'payee_barangay' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $latestPayment = Payment::lockForUpdate()->latest('id')->first();
            $nextSequence = $latestPayment ? $latestPayment->id + 1 : 1;
            
            $validated['or_number'] = 'OR-' . now()->format('Ymd') . '-' . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            Payment::create($validated);

            if (!empty($validated['assessment_id'])) {
                $assessment = Assessment::with('payments')->find($validated['assessment_id']);
                
                $totalPaid = $assessment->payments()->sum('amount_paid');

                if ($totalPaid >= $assessment->total_amount_due) {
                    $assessment->update(['assessment_status' => 'paid']);
                }
            }
        });

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }

    public function reportPdf(Request $request)
    {
        $query = $this->buildReportQuery($request);
        $payments = $query->get();

        $html = '
        <div style="font-family: sans-serif; font-size: 12px;">
            <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Payment Records Report</h2>
            <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <tr style="background-color: #f3f4f6;">
                    <th style="text-align: left;">OR Number</th>
                    <th style="text-align: left;">ASM ID</th>
                    <th style="text-align: left;">Franchise No.</th>
                    <th style="text-align: left;">Payee Name</th>
                    <th style="text-align: left;">Address</th>
                    <th style="text-align: left;">Contact No.</th>
                    <th style="text-align: right;">Amount Paid</th>
                    <th style="text-align: left;">Date</th>
                </tr>';
        
        foreach ($payments as $payment) {
            $payeeName = trim($payment->payee_first_name . ' ' . $payment->payee_middle_name . ' ' . $payment->payee_last_name);
            $address = trim($payment->payee_street_address . ', ' . $payment->payee_barangay . ', ' . $payment->payee_city);
            $asmId = $payment->assessment_id ? 'ASM-' . str_pad($payment->assessment_id, 6, '0', STR_PAD_LEFT) : 'N/A';
            $franchiseNo = $payment->assessment->franchise->franchise_number ?? 'N/A';

            $html .= '<tr>
                        <td>' . $payment->or_number . '</td>
                        <td>' . $asmId . '</td>
                        <td>' . $franchiseNo . '</td>
                        <td>' . $payeeName . '</td>
                        <td>' . $address . '</td>
                        <td>' . $payment->payee_contact_number . '</td>
                        <td style="text-align: right;">PHP ' . number_format($payment->amount_paid, 2) . '</td>
                        <td>' . \Carbon\Carbon::parse($payment->created_at)->format('M d, Y h:i A') . '</td>
                      </tr>';
        }
        $html .= '</table></div>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        
        $filename = 'Payment_Records_' . now()->format('Ymd_His') . '.pdf';

        if ($request->has('download') && $request->input('download') == 1) {
            return $pdf->download($filename);
        }
        
        return $pdf->stream($filename);
    }

    public function reportExcel(Request $request)
    {
        $query = $this->buildReportQuery($request);
        $payments = $query->get();

        $filename = "Payment_Records_" . now()->format('Ymd_His') . ".xls";
        
        $html = '<table border="1">
                    <tr style="background-color: #f3f4f6; font-weight: bold;">
                        <th>OR Number</th>
                        <th>ASM ID</th>
                        <th>Franchise No.</th>
                        <th>Payee Name</th>
                        <th>Contact No.</th>
                        <th>Address</th>
                        <th>Amount Paid</th>
                        <th>Date</th>
                    </tr>';

        foreach ($payments as $payment) {
            $payeeName = trim($payment->payee_first_name . ' ' . $payment->payee_middle_name . ' ' . $payment->payee_last_name);
            $address = trim($payment->payee_street_address . ', ' . $payment->payee_barangay . ', ' . $payment->payee_city . ', ' . $payment->payee_province);
            $asmId = $payment->assessment_id ? 'ASM-' . str_pad($payment->assessment_id, 6, '0', STR_PAD_LEFT) : 'N/A';
            $franchiseNo = $payment->assessment->franchise->franchise_number ?? 'N/A';

            $html .= '<tr>
                        <td>' . $payment->or_number . '</td>
                        <td>' . $asmId . '</td>
                        <td>' . $franchiseNo . '</td>
                        <td>' . $payeeName . '</td>
                        <td>' . $payment->payee_contact_number . '</td>
                        <td>' . $address . '</td>
                        <td>' . number_format($payment->amount_paid, 2) . '</td>
                        <td>' . \Carbon\Carbon::parse($payment->created_at)->format('M d, Y h:i A') . '</td>
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

    private function buildReportQuery(Request $request)
    {
        $search = $request->input('search');
        $city = $request->input('city');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        return Payment::query()
            ->with([
                'assessment.franchise.currentOwnership.newOwner.user',
                'assessment.application.franchise.currentOwnership.newOwner.user'
            ])
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('or_number', 'like', "%{$search}%")
                      ->orWhere('payee_first_name', 'like', "%{$search}%")
                      ->orWhere('payee_last_name', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(payee_first_name, ' ', payee_last_name) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("CONCAT(payee_last_name, ' ', payee_first_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when($city, function ($query, $city) {
                $query->where('payee_city', 'like', "%{$city}%");
            })
            ->when($dateFrom, function($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'payee_name') {
                    $query->orderBy('payee_last_name', $sortDirection)
                          ->orderBy('payee_first_name', $sortDirection);
                } else {
                    $allowedSorts = ['or_number', 'amount_paid', 'created_at'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy($sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                $query->orderBy('id', 'desc');
            });
    }
}