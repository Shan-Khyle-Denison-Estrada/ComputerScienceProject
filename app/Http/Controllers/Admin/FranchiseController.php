<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\Operator;
use App\Models\Unit;
use App\Models\Driver;
use App\Models\Zone;
use App\Models\DriverLog;
use App\Models\Ownership;
use App\Models\ActiveUnit;
use App\Models\DriverAssignment;
use App\Models\Complaint;
use App\Models\NatureOfRedFlag;
use App\Models\NatureOfComplaint;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class FranchiseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sortField = $request->input('sortField', 'created_at');
        $sortDirection = $request->input('sortDirection', 'desc');
        $status = $request->input('status');
        $zoneId = $request->input('zone_id');

        $franchises = Franchise::with([
                'currentOwnership.newOwner.user', 
                'currentActiveUnit.newUnit.make', 
                'driverAssignments.driver.user', 
                'zone'
            ])
            ->when($search, function ($query, $search) {
                $query->where('franchise_number', 'like', "%{$search}%");
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($zoneId, function ($query, $zoneId) {
                $query->where('zone_id', $zoneId);
            });

        // Handle Sorting
        if ($sortField === 'owner') {
            $franchises->leftJoin('ownerships', 'franchises.ownership_id', '=', 'ownerships.id')
                       ->leftJoin('operators', 'ownerships.new_operator_id', '=', 'operators.id')
                       ->leftJoin('users', 'operators.user_id', '=', 'users.id')
                       ->orderBy('users.last_name', $sortDirection)
                       ->orderBy('users.first_name', $sortDirection)
                       ->select('franchises.*'); // Select only franchise columns to prevent ID collision
        } else {
            $allowedSorts = ['franchise_number', 'status', 'created_at'];
            if (in_array($sortField, $allowedSorts)) {
                $franchises->orderBy('franchises.' . $sortField, $sortDirection);
            } else {
                $franchises->orderBy('franchises.created_at', 'desc');
            }
        }

        $franchises = $franchises->paginate(6)->withQueryString();

        return Inertia::render('Admin/Franchises/Index', [
            'franchises' => $franchises,
            'operators' => Operator::with('user')->get(),
            'units' => Unit::with('make')->orderBy('plate_number')->get(),
            'drivers' => Driver::with('user')->get(),
            'zones' => Zone::orderBy('description')->get(),
            'filters' => $request->only(['search', 'sortField', 'sortDirection', 'status', 'zone_id']),
            'userRole' => auth()->user()->role->value ?? auth()->user()->role,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_issued' => 'required|date',
            'zone_id'     => 'required|exists:zones,id',
            'operator_id' => 'required|exists:operators,id',
            'unit_id'     => 'required|exists:units,id',
            'driver_id'   => 'nullable|exists:drivers,id',
        ]);

        DB::transaction(function () use ($validated) {
            $franchise = Franchise::create([
                'date_issued' => $validated['date_issued'],
                'zone_id'     => $validated['zone_id'],
            ]);

            $ownership = Ownership::create([
                'franchise_id'    => $franchise->id,
                'new_operator_id' => $validated['operator_id'],
                'date_transferred' => $validated['date_issued'],
            ]);

            $activeUnit = ActiveUnit::create([
                'franchise_id' => $franchise->id,
                'new_unit_id'  => $validated['unit_id'],
                'date_changed' => $validated['date_issued'],
            ]);

            if (!empty($validated['driver_id'])) {
                DriverAssignment::create([
                    'franchise_id' => $franchise->id,
                    'driver_id'    => $validated['driver_id'],
                ]);
            }

            $qrContent = route('franchises.public_show', $franchise->id);
            $qrImage = QrCode::format('svg')->size(300)->generate($qrContent);
            $filename = 'qr-' . $franchise->id . '.svg';
            Storage::disk('public')->put('qrcodes/' . $filename, $qrImage);

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
        // Eager load ALL possible assessment and payment paths
        $franchise->load([
            'currentOwnership.newOwner.user',
            'currentActiveUnit.newUnit.make',
            'driverAssignments.driver.user',
            'driverLogs.driver.user',
            'zone',
            'assessments.payments', // Direct franchise assessments
            'applications.assessment.payments', // Application-linked assessments
            'complaints',
            'redFlags.nature'
        ]);
        
        $activeAssignment = $franchise->driverAssignments->where('is_active', true)->first();
        $franchise->active_driver = $activeAssignment ? $activeAssignment->driver : null;

        // Gather all unique assessments for this franchise
        $allAssessments = collect($franchise->assessments)->merge(
            collect($franchise->applications)->map->assessment->filter()
        )->unique('id');

        // Extract all unique payments from these assessments
        $allPayments = $allAssessments->flatMap->payments->unique('id')->sortByDesc('created_at')->values();

        // Set Latest Payment Data
        $latestPayment = $allPayments->first();
        if ($latestPayment) {
            $franchise->latest_payment = [
                'amount' => $latestPayment->amount_paid,
                'or_number' => $latestPayment->or_number, // <-- Updated to use actual or_number
                'date' => $latestPayment->created_at->format('M d, Y')
            ];
        } else {
            $franchise->latest_payment = null;
        }

        // Construct flat payment history for the table
        $franchise->payment_history = $allPayments->map(function($payment) use ($allAssessments) {
            $assessment = $allAssessments->firstWhere('id', $payment->assessment_id);
            return [
                'id' => $payment->id,
                'date' => $payment->created_at->format('M d, Y'),
                'or_number' => $payment->or_number,
                'payee' => trim("{$payment->payee_first_name} {$payment->payee_last_name}"),
                'amount_paid' => $payment->amount_paid,
                'assessment_status' => $assessment ? ucfirst($assessment->assessment_status) : 'Unknown',
            ];
        });

        // Fetch active approvers
        $tabApprover = User::where('role', 'tab_approver')->where('status', 'active')->first();
        $spApprover = User::where('role', 'sp_approver')->where('status', 'active')->first();

        return Inertia::render('Admin/Franchises/Show', [
            'franchise' => $franchise,
            'operators' => Operator::with('user')->get(),
            'units' => Unit::with('make')->orderBy('plate_number')->get(),
            'drivers' => Driver::where('status', 'active')->get(),
            'redFlagNatures' => NatureOfRedFlag::all(),
            'complaintNatures' => NatureOfComplaint::all(),
            'systemSetting' => SystemSetting::first(), // <-- FETCH AND PASS THE SETTINGS
            'userRole' => auth()->user()->role->value ?? auth()->user()->role,
            'tabApprover' => $tabApprover, // <-- ADDED
            'spApprover' => $spApprover,   // <-- ADDED
            'certificateTemplate' => \App\Models\FranchiseCertificateTemplate::first(),
        ]);
    }

    public function assignDriver(Request $request, Franchise $franchise)
    {
        $request->validate(['driver_id' => 'required|exists:drivers,id']);
        if (DriverAssignment::where('franchise_id', $franchise->id)->where('driver_id', $request->driver_id)->exists()) {
            return redirect()->back()->withErrors(['driver_id' => 'This driver is already assigned.']);
        }
        DriverAssignment::create(['franchise_id' => $franchise->id, 'driver_id' => $request->driver_id]);
        return redirect()->back()->with('success', 'Driver assigned successfully.');
    }

    public function removeDriver(Franchise $franchise, DriverAssignment $assignment)
    {
        if ($assignment->franchise_id !== $franchise->id) abort(403);
        $assignment->delete();
        return redirect()->back()->with('success', 'Driver removed successfully.');
    }

    public function transferOwnership(Request $request, Franchise $franchise)
    {
        $request->validate(['new_operator_id' => 'required|exists:operators,id', 'date_transferred' => 'required|date']);
        return DB::transaction(function () use ($request, $franchise) {
            $currentOwnership = $franchise->currentOwnership;
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
        $request->validate(['new_unit_id' => 'required|exists:units,id', 'date_changed' => 'required|date', 'remarks' => 'nullable|string']);
        return DB::transaction(function () use ($request, $franchise) {
            $currentActiveUnit = $franchise->currentActiveUnit;
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
            'currentOwnership.newOwner.user', 'currentActiveUnit.newUnit.make',
            'driverAssignments.driver.user', 'zone'
        ])->findOrFail($id);

        return Inertia::render('PublicShow', [
            'franchise' => $franchise,
            'natureOfComplaints' => NatureOfComplaint::orderBy('name')->get() 
        ]);
    }

    public function lookup(Request $request)
    {
        $request->validate(['qr_code' => 'required|string']);
        $id = (int) basename($request->qr_code);
        $franchise = Franchise::find($id);

        if (!$franchise) return redirect()->back()->withErrors(['qr_code' => 'Franchise not found or invalid QR code.']);
        return redirect()->route('franchises.public_show', $franchise->id);
    }

    public function storeComplaint(Request $request, $franchiseId)
    {
        $validated = $request->validate([
            'nature_of_complaint' => 'required|string', 'incident_date' => 'required|date',
            'incident_time' => 'required', 'remarks' => 'nullable|string',
            'fare_collected' => 'nullable|numeric', 'pick_up_point' => 'nullable|string',
            'drop_off_point' => 'nullable|string', 'complainant_contact' => 'required|string', 
        ]);
        $validated['franchise_id'] = $franchiseId;
        $validated['status'] = 'pending';
        Complaint::create($validated);

        return redirect()->back()->with('success', 'Complaint logged successfully.');
    }

    public function resolveComplaint($id)
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->update(['status' => 'Resolved']);
        return redirect()->back()->with('success', 'Complaint marked as resolved.');
    }


    public function storeAndAssignDriver(Request $request, Franchise $franchise)
    {
        // 1. Validate the incoming data
        $validated = $request->validate([
            'application_id' => 'nullable|exists:applications,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'license_number' => 'required|string|unique:drivers,license_number',
            'license_expiration_date' => 'required|date',
            'middle_name' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'street' => 'nullable|string',
            'province' => 'required|string',
            'barangay' => 'nullable|string',
            'city' => 'nullable|string',
            'status' => 'required|string',
            
            // Allow string paths from the frontend (copied from the application)
            'existing_user_photo' => 'nullable|string',
            'existing_license_front_photo' => 'nullable|string',
            'existing_license_back_photo' => 'nullable|string',
        ]);

        try {
            // 2. Execute within a transaction. If any DB operation fails, everything rolls back.
            DB::transaction(function () use ($validated, $franchise) {
                
                // Re-assign the paths directly to the driver columns
                $validated['user_photo'] = $validated['existing_user_photo'] ?? null;
                $validated['license_front_photo'] = $validated['existing_license_front_photo'] ?? null;
                $validated['license_back_photo'] = $validated['existing_license_back_photo'] ?? null;

                // Remove the 'existing_' and 'application_id' keys so they don't break Driver::create()
                $applicationId = $validated['application_id'] ?? null;
                unset(
                    $validated['existing_user_photo'], 
                    $validated['existing_license_front_photo'], 
                    $validated['existing_license_back_photo'],
                    $validated['application_id']
                );

                // Create the new driver
                $driver = Driver::create($validated);

                // Create the assignment linking to the franchise
                DriverAssignment::create([
                    'franchise_id' => $franchise->id,
                    'driver_id'    => $driver->id,
                ]);
                
                // Optionally auto-update the Application status to Finalized/Completed
                if ($applicationId) {
                    \App\Models\Application::where('id', $applicationId)->update(['status' => 'Completed']); 
                }
            });

            return redirect()->back()->with('success', 'Driver successfully finalized and assigned to the franchise!');

        } catch (\Exception $e) {
            // 3. Catch any unexpected errors, log them if necessary, and return a clean error to the user
            return redirect()->back()->withErrors([
                'global_error' => 'An error occurred while saving the driver: ' . $e->getMessage()
            ]);
        }
    }
}