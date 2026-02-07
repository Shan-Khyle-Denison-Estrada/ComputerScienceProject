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
use App\Models\DriverAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FranchiseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $franchises = Franchise::with([
                'currentOwnership.newOwner.user', 
                'currentActiveUnit.newUnit.make', 
                'driverAssignments.driver.user', 
                'zone',
                'assessments.payments' 
            ])
            ->when($search, function ($query, $search) {
                $query->where('id', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $operators = Operator::with('user')->get(); 
        $units = Unit::with('make')->orderBy('plate_number')->get();
        $drivers = Driver::with('user')->get(); 
        $zones = Zone::orderBy('description')->get();

        return Inertia::render('Admin/Franchises/Index', [
            'franchises' => $franchises,
            'operators' => $operators,
            'units' => $units,
            'drivers' => $drivers,
            'zones' => $zones,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_issued' => 'required|date',
            'zone_id' => 'required|exists:zones,id',
            // Add other validations as needed
        ]);
        
        // Basic creation logic - expand based on your specific requirements
        $franchise = Franchise::create($validated);

        return redirect()->route('admin.franchises.index')->with('success', 'Franchise created successfully.');
    }

    public function show(Franchise $franchise)
    {
        // FIX #4: Added 'zone' to eager loading to prevent "Unassigned"
        // Also added 'ownershipHistory.previousOwner.user' to ensure the table displays correctly
        $franchise->load([
            'currentOwnership.newOwner.user',
            'currentActiveUnit.newUnit.make',
            'ownershipHistory.newOwner.user',
            'ownershipHistory.previousOwner.user', // Ensure previous owner data is loaded
            'unitHistory.newUnit.make',
            'driverAssignments.driver.user', 
            'assessments.payments',
            'zone' 
        ]);

        $operators = Operator::with('user')->get();
        $units = Unit::with('make')->get();
        $allDrivers = Driver::with('user')->get();

        return Inertia::render('Admin/Franchises/Show', [
            'franchise' => $franchise,
            'operators' => $operators,
            'units' => $units,
            'drivers' => $allDrivers, 
        ]);
    }

    public function assignDriver(Request $request, Franchise $franchise)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $exists = DriverAssignment::where('franchise_id', $franchise->id)
                                  ->where('driver_id', $request->driver_id)
                                  ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['driver_id' => 'This driver is already assigned to this franchise.']);
        }

        DriverAssignment::create([
            'franchise_id' => $franchise->id,
            'driver_id' => $request->driver_id,
        ]);

        return redirect()->back()->with('success', 'Driver assigned successfully.');
    }

    public function removeDriver(Franchise $franchise, DriverAssignment $assignment)
    {
        if ($assignment->franchise_id !== $franchise->id) {
            abort(403);
        }

        $assignment->delete();

        return redirect()->back()->with('success', 'Driver removed successfully.');
    }

    public function transferOwnership(Request $request, Franchise $franchise)
    {
        $request->validate([
            'new_operator_id' => 'required|exists:operators,id',
            'date_transferred' => 'required|date',
        ]);

        return DB::transaction(function () use ($request, $franchise) {
            $currentOwnership = $franchise->currentOwnership;

            // FIX #5: Check if transferring to the same owner
            if ($currentOwnership && $currentOwnership->new_operator_id == $request->new_operator_id) {
                // We return with errors to the session
                return redirect()->back()->withErrors(['new_operator_id' => 'Operator already owns this franchise.']);
            }

            // FIX #2: Correctly capturing the previous owner ID
            $ownership = Ownership::create([
                'franchise_id' => $franchise->id,
                'new_operator_id' => $request->new_operator_id,
                'previous_operator_id' => $currentOwnership ? $currentOwnership->new_operator_id : null,
                'date_transferred' => $request->date_transferred,
            ]);

            $franchise->update(['ownership_id' => $ownership->id]);

            return redirect()->back()->with('success', 'Ownership transferred successfully.');
        });
    }

    public function changeUnit(Request $request, Franchise $franchise)
    {
        $request->validate([
            'new_unit_id' => 'required|exists:units,id',
            'date_changed' => 'required|date',
            'remarks' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request, $franchise) {
            $currentActiveUnit = $franchise->currentActiveUnit;

            // FIX #6: Check if changing to the same unit
            if ($currentActiveUnit && $currentActiveUnit->new_unit_id == $request->new_unit_id) {
                return redirect()->back()->withErrors(['new_unit_id' => 'Unit is already the active unit.']);
            }

            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id' => $request->new_unit_id,
                'previous_unit_id' => $currentActiveUnit ? $currentActiveUnit->new_unit_id : null,
                'date_changed' => $request->date_changed,
                'remarks' => $request->remarks,
            ]);

            $franchise->update(['active_unit_id' => $activeUnit->id]);

            return redirect()->back()->with('success', 'Unit changed successfully.');
        });
    }

    public function verify()
    {
        return Inertia::render('Verify');
    }

    public function publicShow($id)
    {
        $franchise = Franchise::with([
            'currentOwnership.newOwner.user',
            'currentActiveUnit.newUnit.make',
            // CHANGED: 'driver.user' -> 'driverAssignments.driver.user'
            'driverAssignments.driver.user', 
            'zone',
            'assessments.payments'
        ])->findOrFail($id);

        return Inertia::render('PublicShow', [
            'franchise' => $franchise
        ]);
    }

    // Update the lookup method to redirect to the PUBLIC route
    public function lookup(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string'
        ]);

        // Reconstruct filename
        $scannedCode = $request->qr_code;
        $filename = 'qr-' . $scannedCode . '.svg';

        $franchise = Franchise::where('qr_code', $filename)->first();

        if (!$franchise) {
            return redirect()->back()->withErrors(['qr_code' => 'Franchise not found or invalid QR code.']);
        }

        // CHANGED: Redirect to the public show page
        return redirect()->route('franchises.public_show', $franchise->id);
    }
}