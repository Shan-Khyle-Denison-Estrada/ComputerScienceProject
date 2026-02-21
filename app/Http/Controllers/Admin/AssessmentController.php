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
            ->paginate(5) // <--- CHANGED TO 6
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
                'assessment_due' => $validated['assessment_due'],
                'total_amount_due' => $baseTotal,
                'remarks' => $validated['remarks'],
                'assessment_status' => 'pending'
            ]);

            // 3. Attach Items
            $assessment->particulars()->attach($itemsToAttach);

            // 4. TRIGGER AUTOMATIC PENALTIES
            // This will instantly inject surcharge/interest if created past the due date
            $assessment->recalculatePenalties(); 
        });

        return redirect()->back()->with('success', 'Assessment created successfully.');
    }
}