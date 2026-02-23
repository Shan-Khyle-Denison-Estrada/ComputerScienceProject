<?php

namespace App\Http\Controllers\Capo;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CapoApplicationController extends Controller
{
    public function index(Request $request)
    {
        // STRICT FILTERING: Renewal, Status Pending, Inspector Approved, CAPO Pending
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->where('application_type', 'Renewal')
            ->where('status', 'Pending')
            ->where('inspector_status', 'Approved') // Must be approved by Inspector first
            ->where(function($q) {
                $q->where('capo_status', 'Pending')
                  ->orWhereNull('capo_status');
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

        return Inertia::render('Capo/Applications/Index', [
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

        return Inertia::render('Capo/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function approve(Application $application)
    {
        $application->update(['capo_status' => 'Approved']);
        
        return redirect()->back()->with('success', "Renewal has been approved by the City Anti-Pollution Officer.");
    }

    public function reject(Request $request, Application $application)
    {
        $request->validate([
            'remarks' => 'required|string|max:1000'
        ]);

        $application->update([
            'capo_status' => 'Rejected',
            'status' => 'Returned',
            'remarks' => $request->remarks
        ]);

        return redirect()->back()->with('success', "Renewal has been rejected/returned by the CAPO.");
    }
}