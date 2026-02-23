<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use App\Models\Complaint;
use App\Models\RedFlag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EvaluatorApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->where('application_type', 'Renewal')
            ->where('status', 'Pending')
            ->where(function($q) {
                $q->where('evaluator_status', 'Pending')
                  ->orWhereNull('evaluator_status');
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

        return Inertia::render('Evaluator/Applications/Index', [
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

        return Inertia::render('Evaluator/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function evaluateDocument(Request $request, Application $application)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:application_evaluations,id',
            'status' => 'required|in:Approved,Rejected,Pending',
            'remarks' => 'nullable|string'
        ]);

        $evaluation = ApplicationEvaluation::findOrFail($request->evaluation_id);
        
        $isCompliant = match($request->status) {
            'Approved' => 1,
            'Rejected' => 0,
            default => null,
        };

        $evaluation->update([
            'is_compliant' => $isCompliant,
            'remarks' => $request->remarks ?? ($isCompliant === 0 ? 'Document rejected.' : 'Document accepted.')
        ]);

        return redirect()->back()->with('success', 'Document evaluation updated successfully.');
    }

    public function resolveComplaint(Request $request, Application $application, Complaint $complaint)
    {
        $complaint->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Complaint marked as resolved.');
    }

    public function resolveRedFlag(Request $request, Application $application, RedFlag $redFlag)
    {
        $redFlag->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Red Flag marked as resolved.');
    }
    public function approve(Application $application)
    {
        $application->update(['evaluator_status' => 'Approved']);
        
        return redirect()->back()->with('success', "Renewal Evaluation has been approved.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'evaluator_status' => 'Rejected',
            'status' => 'Returned',
            'remarks' => $request->remarks
        ]);

        return redirect()->back()->with('success', "Renewal Evaluation has been rejected/returned by the Evaluator.");
    }
}