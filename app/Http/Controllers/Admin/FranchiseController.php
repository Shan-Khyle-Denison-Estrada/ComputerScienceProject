<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Operator;
use App\Models\Unit;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\Ownership;
use App\Models\ActiveUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Str;

class FranchiseController extends Controller
{
public function index(Request $request)
    {
        $search = $request->input('search');

        $franchises = Franchise::with([
                'currentOwnership.newOwner.user', 
                'currentActiveUnit.newUnit.make', 
                'driver.user', // Eager load driver user if exists
                'zone'
            ])
            ->when($search, function ($query, $search) {
                $query->where('id', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Data for "Create" Modal
        $operators = Operator::with('user')->get(); 
        
        // Only get units that are available (optional logic, but good practice)
        $units = Unit::with('make')->orderBy('plate_number')->get();
        
        // Fetch Drivers - Handle potential User relation or direct names
        $drivers = Driver::with('user')->get(); 
        
        // Fetch Zones
        $zones = Zone::orderBy('description')->get();

        return Inertia::render('Admin/Franchises/Index', [
            'franchises' => $franchises,
            'operators' => $operators,
            'units' => $units,
            'drivers' => $drivers,
            'zones' => $zones,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'unit_id' => 'required|exists:units,id',
            'driver_id' => 'required|exists:drivers,id',
            'zone_id' => 'required|exists:zones,id',
            'date_issued' => 'required|date',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Create the Franchise Skeleton
            $franchise = Franchise::create([
                'driver_id' => $request->driver_id,
                'zone_id' => $request->zone_id,
                'date_issued' => $request->date_issued,
                'status' => 'active',
                'qr_code' => Str::random(10), // Placeholder logic
            ]);

            // 2. Create Initial Ownership Record
            $ownership = Ownership::create([
                'franchise_id' => $franchise->id,
                'new_operator_id' => $request->operator_id,
                'date_transferred' => $request->date_issued,
            ]);

            // 3. Create Initial Active Unit Record
            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id' => $request->unit_id,
                'date_changed' => $request->date_issued,
                'remarks' => 'Initial Unit Registration'
            ]);

            // 4. Update Franchise with pointers
            $franchise->update([
                'ownership_id' => $ownership->id,
                'active_unit_id' => $activeUnit->id,
            ]);
        });

        return redirect()->back()->with('success', 'Franchise created successfully.');
    }

    public function show(Franchise $franchise)
    {
        $franchise->load([
            'currentOwnership.newOwner.user',
            'currentActiveUnit.newUnit.make',
            'driver',
            'zone',
            'ownershipHistory.newOwner.user',
            'ownershipHistory.previousOwner.user',
            'unitHistory.newUnit.make',
            'unitHistory.previousUnit.make',
        ]);

        $operators = Operator::with('user')->get();
        $units = Unit::with('make')->get();

        return Inertia::render('Admin/Franchises/Show', [
            'franchise' => $franchise,
            'operators' => $operators,
            'units' => $units,
        ]);
    }

    // --- Transfer Ownership Logic ---
    public function transferOwnership(Request $request, Franchise $franchise)
    {
        $request->validate([
            'new_operator_id' => 'required|exists:operators,id',
            'date_transferred' => 'required|date',
        ]);

        DB::transaction(function () use ($request, $franchise) {
            // Get current owner
            $currentOwnerId = $franchise->currentOwnership->new_operator_id;

            // Create new history record
            $ownership = Ownership::create([
                'franchise_id' => $franchise->id,
                'previous_operator_id' => $currentOwnerId,
                'new_operator_id' => $request->new_operator_id,
                'date_transferred' => $request->date_transferred,
            ]);

            // Update Franchise Pointer
            $franchise->update(['ownership_id' => $ownership->id]);
        });

        return redirect()->back()->with('success', 'Ownership transferred successfully.');
    }

    // --- Change Unit Logic ---
    public function changeUnit(Request $request, Franchise $franchise)
    {
        $request->validate([
            'new_unit_id' => 'required|exists:units,id',
            'date_changed' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $franchise) {
            // Get current unit
            $currentUnitId = $franchise->currentActiveUnit->new_unit_id;

            // Create new history record
            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'previous_unit_id' => $currentUnitId,
                'new_unit_id' => $request->new_unit_id,
                'date_changed' => $request->date_changed,
                'remarks' => $request->remarks,
            ]);

            // Update Franchise Pointer
            $franchise->update(['active_unit_id' => $activeUnit->id]);
        });

        return redirect()->back()->with('success', 'Unit changed successfully.');
    }
}