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
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // Added for file storage
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
        // 1. Validate all inputs from the form
        $validated = $request->validate([
            'date_issued' => 'required|date',
            'zone_id'     => 'required|exists:zones,id',
            'operator_id' => 'required|exists:operators,id',
            'unit_id'     => 'required|exists:units,id',
            'driver_id'   => 'nullable|exists:drivers,id',
        ]);

        DB::transaction(function () use ($validated) {
            // 2. Create the Franchise Base Record
            $franchise = Franchise::create([
                'date_issued' => $validated['date_issued'],
                'zone_id'     => $validated['zone_id'],
                // ownership_id and active_unit_id will be updated after creating the child records
            ]);

            // 3. Create Ownership Record
            $ownership = Ownership::create([
                'franchise_id'    => $franchise->id,
                'new_operator_id' => $validated['operator_id'],
                'date_transferred' => $validated['date_issued'], // Initial assignment
            ]);

            // 4. Create Active Unit Record
            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id'  => $validated['unit_id'],
                'date_changed' => $validated['date_issued'], // Initial assignment
            ]);

            // 5. Assign Driver (if selected)
            if (!empty($validated['driver_id'])) {
                DriverAssignment::create([
                    'franchise_id' => $franchise->id,
                    'driver_id'    => $validated['driver_id'],
                ]);
            }

            // 6. Generate QR Code
            // We generate a URL pointing to the public show page for this franchise
            $qrContent = route('franchises.public_show', $franchise->id);
            
            // Generate SVG. You can adjust size/format as needed.
            $qrImage = QrCode::format('svg')->size(300)->generate($qrContent);
            
            // Define filename matching the lookup logic: 'qr-{code}.svg'
            // We use the Franchise ID as the unique code.
            $filename = 'qr-' . $franchise->id . '.svg';
            
            // Ensure the directory exists and save the file
            // This saves to storage/app/public/qrcodes (requires `php artisan storage:link`)
            Storage::disk('public')->put('qrcodes/' . $filename, $qrImage);

            // 7. Update Franchise with references
            $franchise->update([
                'ownership_id'   => $ownership->id,
                'active_unit_id' => $activeUnit->id,
                'qr_code'        => $filename, 
            ]);
        });

        return redirect()->route('admin.franchises.index')->with('success', 'Franchise created and QR code generated successfully.');
    }

    public function show(Franchise $franchise)
    {
        $franchise->load([
            'currentOwnership.newOwner.user',
            'currentActiveUnit.newUnit.make',
            'ownershipHistory.newOwner.user',
            'ownershipHistory.previousOwner.user',
            'unitHistory.newUnit.make',
            'driverAssignments.driver.user', 
            'assessments.payments',
            'zone',
            // Load Complaints
            'complaints' => function($q) {
                $q->latest();
            }
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

            if ($currentOwnership && $currentOwnership->new_operator_id == $request->new_operator_id) {
                return redirect()->back()->withErrors(['new_operator_id' => 'Operator already owns this franchise.']);
            }

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
            'driverAssignments.driver.user', 
            'zone',
            'assessments.payments'
        ])->findOrFail($id);

        return Inertia::render('PublicShow', [
            'franchise' => $franchise
        ]);
    }

public function lookup(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string'
        ]);

        $scannedCode = $request->qr_code;

        // FIX: The scanned code is a full URL (e.g., .../public-show/12).
        // We need to extract the ID (12) from the end of the string.
        
        // Get the last segment of the URL/string
        $extractedId = basename($scannedCode);
        
        // Ensure we strictly have an integer (handles query params or trailing slashes)
        $id = (int) $extractedId;

        // Find the franchise directly by ID
        $franchise = Franchise::find($id);

        if (!$franchise) {
            return redirect()->back()->withErrors(['qr_code' => 'Franchise not found or invalid QR code.']);
        }

        return redirect()->route('franchises.public_show', $franchise->id);
    }

    // Store Complaint (Ensuring this exists based on your Vue usage)
    public function storeComplaint(Request $request, $franchiseId)
    {
        $validated = $request->validate([
            'nature_of_complaint' => 'required|string',
            'incident_date' => 'required|date',
            'incident_time' => 'required',
            'complainant_contact' => 'required|string',
            'remarks' => 'nullable|string',
            'fare_collected' => 'nullable|numeric',
            'pick_up_point' => 'nullable|string',
            'drop_off_point' => 'nullable|string',
        ]);

        $franchise = Franchise::findOrFail($franchiseId);
        
        $franchise->complaints()->create($validated);

        return redirect()->back()->with('success', 'Complaint logged successfully.');
    }

    // NEW: Resolve Complaint
    public function resolveComplaint($id)
    {
        $complaint = Complaint::findOrFail($id);
        
        $complaint->update([
            'status' => 'Resolved'
        ]);

        return redirect()->back()->with('success', 'Complaint marked as resolved.');
    }
}