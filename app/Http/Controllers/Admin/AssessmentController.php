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
            'filters' => $request->only(['search', 'status', 'franchise_id', 'sortField', 'sortDirection']),
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
}