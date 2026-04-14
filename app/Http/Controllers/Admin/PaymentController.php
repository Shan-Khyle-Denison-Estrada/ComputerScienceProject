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
                        : 'ASM-' . str_pad($assessment->id, 6, '0', STR_PAD_LEFT),
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
            'filters' => $request->only(['search', 'city', 'sortField', 'sortDirection']), // <-- NEW FILTERS ADDED HERE
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
}