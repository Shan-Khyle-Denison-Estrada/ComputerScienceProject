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
        $filters = $request->only(['search', 'city']);

        $payments = Payment::query()
            ->filter($filters)
            ->latest()
            ->paginate(6)
            ->withQueryString();
            
        // Fetch Barangays for the dropdown
        $barangays = Barangay::select('id', 'name')->orderBy('name')->get();

        // NEW: Fetch Pending/Overdue Assessments with their current balance
        // We eagerly load payments to calculate the balance on the fly if needed, 
        // or rely on a raw query for performance. Here we use Eloquent for simplicity.
        $assessments = Assessment::whereIn('assessment_status', ['pending', 'overdue'])
            ->with('payments') // Eager load to calculate balance
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'franchise_id' => $assessment->franchise_id,
                    'total_due' => $assessment->total_amount_due,
                    'balance' => $assessment->total_amount_due - $assessment->payments->sum('amount_paid'),
                    'label' => "Assessment #{$assessment->id} - Bal: ₱" . number_format($assessment->total_amount_due - $assessment->payments->sum('amount_paid'), 2)
                ];
            });

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'barangays' => $barangays,
            'assessments' => $assessments, // Pass to view
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assessment_id' => 'nullable|exists:assessments,id', // Validate it exists
            'amount_paid' => 'required|numeric|min:0',
            'payee_first_name' => 'required|string|max:255',
            'payee_middle_name' => 'nullable|string|max:255',
            'payee_last_name' => 'required|string|max:255',
            'payee_contact_number' => 'nullable|string|max:20',
            'payee_street_address' => 'nullable|string|max:255',
            'payee_barangay' => 'required|string|max:255',
            'payee_city' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            // 1. Create Payment
            Payment::create($validated);

            // 2. Update Assessment Status if applicable
            if (!empty($validated['assessment_id'])) {
                $assessment = Assessment::with('payments')->find($validated['assessment_id']);
                
                // Recalculate total paid (including the new one)
                $totalPaid = $assessment->payments()->sum('amount_paid');

                // Check if fully paid
                if ($totalPaid >= $assessment->total_amount_due) {
                    $assessment->update(['assessment_status' => 'paid']);
                }
            }
        });

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}