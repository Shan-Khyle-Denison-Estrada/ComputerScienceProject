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
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FranchiseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $franchises = Franchise::with([
                'currentOwnership.newOwner.user', 
                'currentActiveUnit.newUnit.make', 
                'driver.user',
                'zone',
                // Eager load these to calculate status efficiently
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
            // 1. Generate QR Code Data and File
            // Unique identifier for the QR content (e.g., FR-TIMESTAMP-RANDOM)
            $qrData = 'FR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            $qrFilename = 'qr-' . $qrData . '.svg';

            // Ensure the directory exists
            if (!Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }

            // Generate the SVG content and save it to storage/app/public/qrcodes
            $qrContent = QrCode::format('svg')
                            ->size(300)
                            ->margin(2)
                            ->generate($qrData);
                            
            Storage::disk('public')->put('qrcodes/' . $qrFilename, $qrContent);

            // 2. Create the Franchise Skeleton
            $franchise = Franchise::create([
                'driver_id' => $request->driver_id,
                'zone_id' => $request->zone_id,
                'date_issued' => $request->date_issued,
                'status' => 'active',
                'qr_code' => $qrFilename, // Save filename instead of random string
            ]);

            // 3. Create Initial Ownership Record
            $ownership = Ownership::create([
                'franchise_id' => $franchise->id,
                'new_operator_id' => $request->operator_id,
                'date_transferred' => $request->date_issued,
            ]);

            // 4. Create Initial Active Unit Record
            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id' => $request->unit_id,
                'date_changed' => $request->date_issued,
                'remarks' => 'Initial Unit Registration'
            ]);

            // 5. Update Franchise with pointers
            $franchise->update([
                'ownership_id' => $ownership->id,
                'active_unit_id' => $activeUnit->id,
            ]);
        });

        return redirect()->back()->with('success', 'Franchise created and QR code generated successfully.');
    }

    public function show(Franchise $franchise)
    {
        $franchise->load([
            'currentOwnership.newOwner.user',
            'currentActiveUnit.newUnit.make',
            'driver.user',
            'zone',
            'ownershipHistory.newOwner.user',
            'ownershipHistory.previousOwner.user',
            'unitHistory.newUnit.make',
            // Load for status calculation
            'assessments.payments'
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

    public function verify()
    {
        return Inertia::render('Verify');
    }

public function publicShow($id)
{
    // Fetch franchise with relationships needed for display AND status calculation
    $franchise = Franchise::with([
        'currentOwnership.newOwner.user',
        'currentActiveUnit.newUnit.make',
        'driver.user',
        'zone',
        'assessments.payments' // Required for the getStatusAttribute to work
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