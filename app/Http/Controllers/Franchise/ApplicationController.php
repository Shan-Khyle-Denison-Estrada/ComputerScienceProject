<?php

namespace App\Http\Controllers\Franchise;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationRequirement;
use App\Models\ApplicationAttachment;
use App\Models\Assessment;
use App\Models\Particular;
use App\Models\Franchise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // [!code warning] Ensure this is imported
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function create()
    {
        // Use Auth facade to get the user
        $user = Auth::user(); 
        
        // Ensure the user has an operator profile
        if (!$user->operator) {
            return redirect()->route('franchise.dashboard')
                ->with('error', 'Operator profile not found. Please contact admin.');
        }

        $operator = $user->operator;

        // 1. Get Operator's Franchises for dropdown
        // We filter for franchises owned by this operator
        $franchises = Franchise::whereHas('currentOwnership', function ($q) use ($operator) {
            $q->where('new_operator_id', $operator->id);
        })->with(['currentActiveUnit.newUnit', 'zone'])->get()->map(function($f) {
            return [
                'id' => $f->id,
                'plate' => $f->currentActiveUnit->newUnit->plate_number ?? 'No Unit',
                'zone' => $f->zone->description ?? 'N/A',
            ];
        });

        // 2. Get Requirements grouped by Type
        // We exclude 'new_franchise' and 'new_operator' as those are different flows
        $allowedTypes = ['renewal', 'transfer_ownership', 'change_unit'];
        
        $requirements = ApplicationRequirement::whereIn('application_type', $allowedTypes)
            ->get()
            ->groupBy('application_type');

        return Inertia::render('Franchise/Applications/Create', [
            'franchises' => $franchises,
            'requirements' => $requirements
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'type' => 'required|string|in:renewal,change_of_owner,change_of_unit',
            'remarks' => 'nullable|string',
            'proposed_data' => 'nullable|array'
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Create Application
            $application = Application::create([
                'user_id' => Auth::id(), // Get ID directly from Auth
                'franchise_id' => $validated['franchise_id'],
                'type' => $validated['type'],
                'status' => 'draft', // Start as draft for uploading requirements
                'remarks' => $validated['remarks'],
                'proposed_data' => $validated['proposed_data'] ?? null,
            ]);

            // 2. Auto-Create Assessment (Same logic as Admin)
            $particulars = Particular::where('group', $validated['type'])->get();

            if ($particulars->isNotEmpty()) {
                $total = $particulars->sum('amount');
                
                $assessment = Assessment::create([
                    'franchise_id' => $validated['franchise_id'],
                    'assessment_date' => now(),
                    'assessment_due' => now()->addDays(30),
                    'total_amount_due' => $total,
                    'assessment_status' => 'pending',
                    'remarks' => 'Auto-generated for Application #' . $application->id
                ]);

                foreach ($particulars as $p) {
                    $assessment->particulars()->attach($p->id, [
                        'quantity' => 1,
                        'subtotal' => $p->amount
                    ]);
                }

                $application->update(['assessment_id' => $assessment->id]);
            }

            // Redirect to the Wizard (Step 2)
            return redirect()->route('franchise.applications.edit', $application->id);
        });
    }

    public function edit(Application $application)
    {
        // Security Check: Only allow the owner to view
        if ($application->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $application->load(['attachments', 'franchise', 'assessment']);
        
        // Fetch requirements specific to this application's type
        $requirements = ApplicationRequirement::where('application_type', $application->type)->get();

        return Inertia::render('Franchise/Applications/Wizard', [
            'application' => $application,
            'requirements' => $requirements,
        ]);
    }

    public function uploadAttachment(Request $request, Application $application)
    {
        if ($application->user_id !== Auth::id()) abort(403);

        $request->validate([
            'requirement_id' => 'required|exists:application_requirements,id',
            'file' => 'required|file|max:5120' // 5MB Limit
        ]);

        $path = $request->file('file')->store('application_attachments', 'public');

        ApplicationAttachment::updateOrCreate(
            [
                'application_id' => $application->id,
                'application_requirement_id' => $request->requirement_id
            ],
            ['file_path' => $path]
        );

        return back()->with('success', 'Document uploaded.');
    }

    public function submit(Application $application)
    {
        if ($application->user_id !== Auth::id()) abort(403);

        // Update status to pending so Admin can see it
        $application->update(['status' => 'pending']);

        return redirect()->route('franchise.dashboard')->with('success', 'Application submitted successfully!');
    }
}