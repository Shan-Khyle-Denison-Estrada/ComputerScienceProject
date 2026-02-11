<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Particular;
use App\Models\Franchise;
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

        // 1. Pagination set to 6 rows
        $assessments = Assessment::query()
            ->with(['particulars', 'payments'])
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhere('remarks', 'like', "%{$search}%");
                });
            })
            ->when($status, function($query, $status) {
                $query->where('assessment_status', $status);
            })
            ->when($franchiseId, function($query, $franchiseId) {
                $query->where('franchise_id', $franchiseId);
            })
            ->latest()
            ->paginate(7) // <--- CHANGED TO 6
            ->withQueryString();

        $particulars = Particular::orderBy('group')->orderBy('name')->get();
        
        // Load franchise data safely with mapping
        $franchises = Franchise::with('currentOwnership')
            ->get()
            ->map(function ($franchise) {
                return [
                    'id' => $franchise->id,
                    'franchise_number' => $franchise->id,
                    'status' => $franchise->status,
                    'last_name' => $franchise->currentOwnership ? $franchise->currentOwnership->last_name : '',
                    'first_name' => $franchise->currentOwnership ? $franchise->currentOwnership->first_name : '',
                ];
            });

        return Inertia::render('Admin/Assessments/Index', [
            'assessments' => $assessments,
            'filters' => $request->only(['search', 'status', 'franchise_id']),
            'particulars' => $particulars,
            'franchises' => $franchises
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'nullable|exists:franchises,id',
            'assessment_date' => 'required|date',
            'assessment_due' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.particular_id' => 'required|exists:particulars,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            
            // 1. Fetch Particulars
            $particularIds = array_column($validated['items'], 'particular_id');
            $dbParticulars = Particular::whereIn('id', $particularIds)->get()->keyBy('id');

            // 2. Identify Renewal Basis & Build Basic List
            $renewalBasis = 0;
            $itemsToAttach = [];

            foreach ($validated['items'] as $item) {
                $p = $dbParticulars[$item['particular_id']];
                
                // Security: Skip if user tried to manually send a system penalty ID (we calculate these later)
                if ($p->is_system || $p->group === 'penalty') {
                    continue; 
                }

                $amount = $p->amount;
                
                if ($p->group === 'renewal') {
                    $renewalBasis += ($amount * $item['quantity']);
                }

                $itemsToAttach[$item['particular_id']] = [
                    'quantity' => $item['quantity'],
                    'subtotal' => $amount * $item['quantity']
                ];
            }

            // 3. AUTOMATIC PENALTY INJECTION
            $assessDate = Carbon::parse($validated['assessment_date']);
            $dueDate = Carbon::parse($validated['assessment_due']);
            
            // Logic: If Assessment Date is AFTER Due Date, it is Late.
            // And we only apply penalties if there is a Renewal Fee involved.
            if ($renewalBasis > 0 && $assessDate->gt($dueDate)) {
                
                // Calculate Months Delayed
                // If late by 1 day, we count as 1 month.
                $monthsDelayed = $dueDate->diffInMonths($assessDate);
                if($assessDate->day > $dueDate->day || $monthsDelayed == 0) {
                    $monthsDelayed += 1;
                }

                // Fetch System Particulars
                $surchargeP = Particular::where('code', 'surcharge')->first();
                $interestP = Particular::where('code', 'interest')->first();

                // A. Auto-Add Surcharge (25%)
                if ($surchargeP) {
                    $surchargeAmount = $renewalBasis * 0.25;
                    $itemsToAttach[$surchargeP->id] = [
                        'quantity' => 1,
                        'subtotal' => $surchargeAmount
                    ];
                }

                // B. Auto-Add Interest (2% per month)
                if ($interestP) {
                    $interestRate = 0.02;
                    $interestAmount = $renewalBasis * $interestRate * $monthsDelayed;
                    $itemsToAttach[$interestP->id] = [
                        'quantity' => 1, // Usually represented as 1 "Interest Charge"
                        'subtotal' => $interestAmount
                    ];
                }
            }

            // 4. Calculate Final Total
            $totalAmountDue = 0;
            foreach ($itemsToAttach as $data) {
                $totalAmountDue += $data['subtotal'];
            }

            // 5. Create Assessment
            $assessment = Assessment::create([
                'franchise_id' => $validated['franchise_id'],
                'assessment_date' => $validated['assessment_date'],
                'assessment_due' => $validated['assessment_due'],
                'total_amount_due' => $totalAmountDue,
                'remarks' => $validated['remarks'],
                'assessment_status' => 'pending'
            ]);

            // 6. Attach Items
            foreach ($itemsToAttach as $id => $data) {
                $assessment->particulars()->attach($id, [
                    'quantity' => $data['quantity'],
                    'subtotal' => $data['subtotal'],
                ]);
            }
        });

        return redirect()->back()->with('success', 'Assessment created successfully.');
    }
}