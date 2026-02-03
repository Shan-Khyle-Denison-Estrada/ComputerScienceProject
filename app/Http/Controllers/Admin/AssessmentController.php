<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Particular;
use App\Models\Franchise; // Import Franchise
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        // --- AUTO-UPDATE OVERDUE STATUS ---
        Assessment::where('assessment_status', 'pending')
            ->whereDate('assessment_due', '<', now()->toDateString())
            ->update(['assessment_status' => 'overdue']);
        // ----------------------------------

        $search = $request->input('search');

        $assessments = Assessment::query()
            ->with(['particulars', 'payments'])
            ->when($search, function($query, $search) {
                $query->where('id', 'like', "%{$search}%")
                      ->orWhere('franchise_id', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $particulars = Particular::all();
        
        // Fetch all Franchises with their current owner for the dropdown
        $franchises = Franchise::with(['currentOwnership.newOwner.user'])->get();

        return Inertia::render('Admin/Assessments/Index', [
            'assessments' => $assessments,
            'particulars' => $particulars,
            'franchises' => $franchises, // Pass to frontend
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'nullable|exists:franchises,id', // Update validation
            'assessment_date' => 'required|date',
            'assessment_due' => 'required|date|after_or_equal:assessment_date',
            'remarks' => 'nullable|string',
            'total_amount_due' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.particular_id' => 'required|exists:particulars,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $assessment = Assessment::create([
                'franchise_id' => $validated['franchise_id'],
                'assessment_date' => $validated['assessment_date'],
                'assessment_due' => $validated['assessment_due'],
                'total_amount_due' => $validated['total_amount_due'],
                'remarks' => $validated['remarks'],
                'assessment_status' => 'pending'
            ]);

            foreach ($validated['items'] as $item) {
                $assessment->particulars()->attach($item['particular_id'], [
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal']
                ]);
            }
        });

        return redirect()->back()->with('success', 'Assessment created successfully.');
    }
}