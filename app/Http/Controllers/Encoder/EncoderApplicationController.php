<?php

namespace App\Http\Controllers\Encoder;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InspectionItem;
use App\Models\UnitInspection;
use App\Models\Barangay;
use App\Models\Zone;
use App\Models\UnitMake;
use App\Models\User;
use App\Models\Operator;
use App\Models\Franchise;
use App\Models\Ownership;
use App\Models\ActiveUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Inertia\Inertia;

class EncoderApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['user', 'franchise.currentActiveUnit.newUnit'])
            ->where('status', 'Approved');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('application_type', $request->type);
        }

        $applications = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Encoder/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    // --- RENEWAL ---
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

        return Inertia::render('Encoder/Applications/ShowRenewal', [
            'application' => $application,
            'inspectionItems' => $inspectionItems,
            'unitInspections' => $unitInspections,
            'currentUnitId' => $currentUnitId
        ]);
    }

    public function finalizeRenewal(Request $request, Application $application)
    {
        $request->validate([
            'new_date_issued' => 'required|date',
            'remarks' => 'nullable|string|max:1000'
        ]);

        DB::transaction(function () use ($request, $application) {
            $application->franchise->update([
                'date_issued' => $request->new_date_issued,
                'status' => 'Active'
            ]);
            $application->update([
                'status' => 'Completed',
                'remarks' => 'Renewal finalized successfully. ' . ($request->remarks ?? '')
            ]);
        });

        return redirect()->route('encoder.applications.index')->with('success', 'Renewal finalized and Franchise extended successfully!');
    }

    // --- FRANCHISE OWNER ACCOUNT (New Franchise) ---
    public function showNewFranchise(Application $application)
    {
        abort_if($application->application_type !== 'Franchise Owner Account', 404);

        $application->load([
            'user', 'zone', 'proposedUnits.make', 
            'evaluations.requirement', 'assessment.particulars', 'assessment.payments'
        ]);
        
        return Inertia::render('Encoder/Applications/ShowNewFranchise', [
            'application' => $application,
            // 'barangays' => Barangay::all(),
            'zones' => Zone::all(),
            'unitMakes' => UnitMake::orderBy('name', 'asc')->get(),
        ]);
    }

    // --- CHANGE OF OWNER ---
    public function showChangeOfOwner(Application $application)
    {
        abort_if($application->application_type !== 'Change of Owner', 404);

        $application->load([
            'user', 'franchise.currentOwnership.newOwner.user', 
            'franchise.zone', 'zone', 
            'evaluations.requirement', 'assessment.particulars', 'assessment.payments'
        ]);

        return Inertia::render('Encoder/Applications/ShowChangeOfOwner', [
            'application' => $application
        ]);
    }

    // --- CHANGE OF UNIT ---
    public function showChangeOfUnit(Application $application)
    {
        abort_if($application->application_type !== 'Change of Unit', 404);

        $application->load([
            'user', 'franchise.currentActiveUnit.newUnit.make', 
            'franchise.zone', 'zone', 
            'proposedUnits.make', 'proposedUnits.unitInspections.inspectionItem', 
            'evaluations.requirement', 'assessment.particulars', 'assessment.payments'
        ]);

        return Inertia::render('Encoder/Applications/ShowChangeOfUnit', [
            'application' => $application,
            'inspectionItems' => InspectionItem::all(),
            'unitMakes' => UnitMake::orderBy('name', 'asc')->get()
        ]);
    }
}