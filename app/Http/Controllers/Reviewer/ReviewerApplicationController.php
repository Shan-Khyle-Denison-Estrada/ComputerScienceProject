<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewerApplicationController extends Controller
{
    public function index(Request $request)
    {
        // STRICT FILTERING: 
        // 1. Renewal & Pending Application Status
        // 2. Evaluator, Inspector, & CAPO Approved
        // 3. Assessment is Fully Paid
        // 4. Reviewer Status is Pending or Null
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->where('application_type', 'Renewal')
            ->where('status', 'Pending')
            ->where('evaluator_status', 'Approved')
            ->where('inspector_status', 'Approved')
            ->where('capo_status', 'Approved')
            ->whereHas('assessment', function ($q) {
                // Adjust this if your actual paid value is capitalized differently
                $q->where('assessment_status', 'paid'); 
            })
            ->where(function($q) {
                $q->where('reviewer_status', 'Pending')
                  ->orWhereNull('reviewer_status');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $applications = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Reviewer/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search']),
        ]);
    }

    public function showRenewal(Application $application)
    {
        abort_if($application->application_type !== 'Renewal', 404);

        $application->load([
            'user',
            'franchise.currentOwnership.newOwner.user', 
            'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', 
            'zone',
            'evaluations.requirement',
            'assessment.particulars',
            'assessment.payments',
            'franchise.complaints',
            'franchise.redFlags.nature'
        ]);

        $inspectionItems = InspectionItem::all();

        $currentUnitId = null;
        $unitInspections = [];
        if ($application->franchise && $application->franchise->currentActiveUnit) {
            $currentUnitId = $application->franchise->currentActiveUnit->new_unit_id;
            $unitInspections = UnitInspection::where('unit_id', $currentUnitId)
                ->where('application_id', $application->id) 
                ->get();
        }

        return Inertia::render('Reviewer/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function approve(Application $application)
    {
        $application->update(['reviewer_status' => 'Approved']);
        
        return redirect()->back()->with('success', "Renewal has been approved by the Reviewer.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'reviewer_status' => 'Rejected',
            'status' => 'Returned',
            'remarks' => $request->remarks
        ]);

        return redirect()->back()->with('success', "Renewal has been rejected/returned by the Reviewer.");
    }
}