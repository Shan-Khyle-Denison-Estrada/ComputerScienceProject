<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Barangay; // Ensure this Model exists
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'city']);

        $payments = Payment::query()
            ->filter($filters)
            ->latest()
            ->paginate(10)
            ->withQueryString();
            
        // Fetch Barangays for the dropdown
        // Assuming your table is 'barangays' and has 'id' and 'name'
        $barangays = Barangay::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $filters,
            'barangays' => $barangays, 
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'assessment_id' => 'nullable|integer',
            'amount_paid' => 'required|numeric|min:0',
            'payee_first_name' => 'required|string|max:255',
            'payee_middle_name' => 'nullable|string|max:255',
            'payee_last_name' => 'required|string|max:255',
            'payee_contact_number' => 'nullable|string|max:20',
            'payee_street_address' => 'nullable|string|max:255',
            'payee_barangay' => 'required|string|max:255', // Now required
            'payee_city' => 'nullable|string|max:255',
        ]);

        Payment::create($validated);

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}