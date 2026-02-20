<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use App\Models\Barangay;
use App\Models\Zone;
use App\Models\UnitMake;
use App\Models\User;
use App\Models\Operator;
use App\Models\Franchise;
use App\Models\Ownership;
use App\Models\Unit;
use App\Models\ActiveUnit;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ApplicationShowController extends Controller
{
    public function show($id)
    {
        // 1. Fetch Application with relationships
        $application = Application::with([
            'user',
            'zone', 
            'franchise.zone',
            'proposedUnits.make', 
            'evaluations.requirement'
        ])->findOrFail($id);

        // 2. Map Evaluation Requirements
        $evaluations = $application->evaluations->map(function ($eval) {
            $status = 'Pending';
            if ($eval->is_compliant === 1 || $eval->is_compliant === true) $status = 'Approved';
            if ($eval->is_compliant === 0 || $eval->is_compliant === false) $status = 'Rejected';

            return [
                'id' => $eval->id,
                'requirement_id' => $eval->requirement_id,
                'name' => $eval->requirement->name ?? 'Requirement #' . $eval->requirement_id,
                'status' => $status,
                'file_url' => $eval->file_path ? asset('storage/' . $eval->file_path) : '#',
                'remarks' => $eval->remarks
            ];
        });

        // 3. Map Franchises / Units
        $franchises = [];

        if ($application->proposedUnits->isNotEmpty()) {
            $franchises = $application->proposedUnits->map(function ($unit) use ($application) {
                return [
                    'id' => $unit->id,
                    'make_id' => $unit->make_id,
                    'zone_id' => $application->zone_id,
                    'zone_name' => $application->zone->description ?? 'N/A', 
                    'date_issued' => 'Pending',
                    'make_name' => $unit->make->name ?? 'N/A',
                    'model_year' => $unit->model_year,
                    'plate_number' => $unit->plate_number,
                    'cr_number' => $unit->cr_number,
                    'motor_number' => $unit->motor_number,
                    'chassis_number' => $unit->chassis_number,
                    // [!code ++] ADD THESE RAW PATHS (Crucial for copying)
                    'unit_front_photo_path' => $unit->unit_front_photo, 
                    'unit_back_photo_path' => $unit->unit_back_photo,
                    'unit_left_photo_path' => $unit->unit_left_photo,
                    'unit_right_photo_path' => $unit->unit_right_photo,
                    // Photos
                    'unit_front_photo' => $unit->unit_front_photo ? asset('storage/'.$unit->unit_front_photo) : null,
                    'unit_back_photo' => $unit->unit_back_photo ? asset('storage/'.$unit->unit_back_photo) : null,
                    'unit_left_photo' => $unit->unit_left_photo ? asset('storage/'.$unit->unit_left_photo) : null,
                    'unit_right_photo' => $unit->unit_right_photo ? asset('storage/'.$unit->unit_right_photo) : null,
                    // NEW: Added Franchise Certificate Photo
                    'franchise_certificate_photo' => $unit->franchise_certificate_photo ? asset('storage/' . $unit->franchise_certificate_photo) : null,
                    'cr_photo' => $unit->cr_photo ? asset('storage/' . $unit->cr_photo) : null,
                    'or_photo' => $unit->or_photo ? asset('storage/' . $unit->or_photo) : null,
                ];
            });
        } 
        elseif ($application->franchise) {
            $franchises[] = [
                'id' => $application->franchise->id,
                'zone_name' => $application->franchise->zone->description ?? 'N/A',
                'date_issued' => $application->franchise->date_issued,
                'make_name' => 'Existing Unit', 
                'model_year' => 'N/A',
                'plate_number' => 'N/A', 
                'franchise_certificate_photo' => null, // Typically not needed for existing/renewal display here
            ];
        }

        // 4. Resolve Contact Number
        $contactNumber = $application->contact_number;
        if (empty($contactNumber) && $application->user) {
            $contactNumber = $application->user->contact_number;
        }

        $barangayId = $application->user->barangay_id ?? null;
        if (!$barangayId && $application->barangay) {
            // Try to find the barangay by name (case-insensitive) to get its ID
            $found = Barangay::where('name', 'LIKE', $application->barangay)->first();
            if ($found) {
                $barangayId = $found->id;
            }
        }

        // 5. Construct Data Object
        $appData = [
            'id' => $application->id,
            'zone_id' => $application->zone_id,
            'reference_no' => $application->reference_number,
            'type' => $application->application_type,
            'date_submitted' => $application->submitted_at ? Carbon::parse($application->submitted_at)->format('M d, Y') : 'N/A',
            'status' => $application->status,
            'applicant' => [
                'user_photo_path' => $application->user_photo ?? null,
                'first_name' => $application->first_name,
                'middle_name' => $application->middle_name,
                'last_name' => $application->last_name,
                'email' => $application->email,
                'contact' => $contactNumber ?? 'N/A',
                'street' => $application->street_address,
                'barangay' => $application->barangay,
                'city' => $application->city,
// [!code focus] PASS THE RESOLVED ID
                'barangay_id' => $barangayId, 
                
                // Keep name for fallback display if needed
                'barangay_name' => $application->barangay,
                'tin_number' => $application->tin_number,
                'photo' => $application->user && $application->user->user_photo 
                            ? asset('storage/' . $application->user->user_photo) 
                            : null,
            ],
            'evaluation_requirements' => $evaluations,
            'inspection_requirements' => [], 
            'franchises' => $franchises,
            'complaints' => [], 
            'receipt' => null,  
        ];

        $barangays = Barangay::orderBy('name')->get();
        $zones = Zone::all();
        $unitMakes = UnitMake::orderBy('name')->get();

        return Inertia::render('Admin/Applications/Show', [
            'application' => $appData,
            'barangays' => $barangays, // [!code ++] Pass to view
            'zones' => $zones,         // [!code ++] Pass to view
            'unitMakes' => $unitMakes  // [!code ++] Pass to view
        ]);
    }

    public function updateEvaluation(Request $request, $id)
    {
        $request->validate([
            'evaluation_id' => 'required|exists:application_evaluations,id',
            'status' => 'required|in:Approved,Rejected',
            'remarks' => 'nullable|string'
        ]);

        $evaluation = ApplicationEvaluation::findOrFail($request->evaluation_id);
        
        $evaluation->update([
            'is_compliant' => $request->status === 'Approved' ? true : false,
            'remarks' => $request->remarks
        ]);

        return back()->with('success', 'Requirement status updated.');
    }

    public function returnApplication(Request $request, $id)
    {
        $request->validate(['remarks' => 'required|string']);

        $application = Application::findOrFail($id);
        $application->update([
            'status' => 'Returned',
            'remarks' => $request->remarks,
            'reviewed_at' => now()
        ]);

        return back()->with('success', 'Application returned to applicant.');
    }

    // NEW: Reject Application Logic
    public function rejectApplication(Request $request, $id)
    {
        $request->validate(['remarks' => 'required|string']);

        $application = Application::findOrFail($id);
        $application->update([
            'status' => 'Rejected',
            'remarks' => $request->remarks,
            'reviewed_at' => now()
        ]);

        return back()->with('success', 'Application has been rejected.');
    }

    // [!code ++] Add this method to the controller
    public function approveApplication(Request $request, $id)
    {
        $application = Application::findOrFail($id);

        // Optional: Add validation to ensure it has required inspections/evaluations
        // if ($application->evaluations()->where('is_compliant', false)->exists()) { ... }

        $application->update([
            'status' => 'Approved',
            'reviewed_at' => now()
        ]);

        return back()->with('success', 'Application approved. You can now finalize the franchise account.');
    }

public function finalizeAccount(Request $request, $id)
{
    $application = Application::findOrFail($id);

    // 1. VALIDATE INPUT (Rules Only)
    $validated = $request->validate([
        // Personal Info
        'first_name' => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        'contact_number' => 'required|string|max:20',
        'street_address' => 'required|string|max:255',
        
        // [!code focus] CORRECT: Validate that the ID exists, don't fetch name yet
        'barangay_id' => 'required|exists:barangays,id', 
        
        'city' => 'required|string|max:255',
        'tin_number' => 'nullable|string|max:50',
        'password' => 'required|string|min:8|confirmed',

        // Franchise / Unit Info
        'franchises' => 'required|array|min:1',
        'franchises.*.zone_id' => 'required|exists:zones,id',
        'franchises.*.date_issued' => 'required|date',
        'franchises.*.make_id' => 'required|exists:unit_makes,id',
        'franchises.*.plate_number' => 'required|string|unique:units,plate_number',
        'franchises.*.motor_number' => 'required|string|unique:units,motor_number',
        'franchises.*.chassis_number' => 'required|string|unique:units,chassis_number',
        'franchises.*.cr_number' => 'nullable|string',
        'franchises.*.model_year' => 'nullable|integer|digits:4',

        'owner_photo_path' => 'nullable|string', // [!code ++] Allow path string
        'franchises.*.unit_front_photo_path' => 'nullable|string', // [!code ++]
        'franchises.*.unit_back_photo_path' => 'nullable|string',
        'franchises.*.unit_left_photo_path' => 'nullable|string',
        'franchises.*.unit_right_photo_path' => 'nullable|string',
    ]);

    try {
        DB::transaction(function () use ($validated, $application, $request) {
            
            // [!code focus] FETCH BARANGAY NAME HERE (Inside the logic block)
            // If your users table stores the name (string), fetch it now:
            $barangayName = Barangay::find($validated['barangay_id'])->name;

            // A. HANDLE USER PHOTO
            $userPhotoPath = null;
            if (!empty($request->owner_photo_path)) {
                // Ensure the file actually exists before trying to copy
                if (Storage::disk('public')->exists($request->owner_photo_path)) {
                    $ext = pathinfo($request->owner_photo_path, PATHINFO_EXTENSION);
                    $newPath = 'profile-photos/' . uniqid() . '.' . $ext;
                    
                    Storage::disk('public')->copy($request->owner_photo_path, $newPath);
                    $userPhotoPath = $newPath;
                }
            }

            // A. Create User (Franchise Owner)
            $user = User::create([
                'user_photo' => $userPhotoPath, // Save the NEW path
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'contact_number' => $validated['contact_number'],
                'street_address' => $validated['street_address'],
                
                // [!code focus] SAVE THE NAME STRING
                'barangay' => $barangayName, 
                
                'city' => $validated['city'],
                'role' => 'franchise_owner', 
                'status' => 'active',
            ]);

            // B. Create Operator Record
            $operator = Operator::create([
                'user_id' => $user->id,
                'tin_number' => $validated['tin_number'],

            ]);

            // C. Loop through Franchises
            foreach ($validated['franchises'] as $franchiseData) {
                // B. COPY UNIT PHOTOS
                $unitPhotos = [];
                $photoMap = [
                    'unit_front_photo_path' => 'unit_front_photo',
                    'unit_back_photo_path'  => 'unit_back_photo',
                    'unit_left_photo_path'  => 'unit_left_photo',
                    'unit_right_photo_path' => 'unit_right_photo',
                ];
                foreach ($photoMap as $inputKey => $dbColumn) {
                    if (!empty($franchiseData[$inputKey])) {
                        $originalPath = $franchiseData[$inputKey];
                        
                        if (Storage::disk('public')->exists($originalPath)) {
                            $ext = pathinfo($originalPath, PATHINFO_EXTENSION);
                            $newUnitPath = 'units/' . uniqid() . '_' . $dbColumn . '.' . $ext;
                            
                            Storage::disk('public')->copy($originalPath, $newUnitPath);
                            $unitPhotos[$dbColumn] = $newUnitPath;
                        }
                    }
                }
                // Create Unit
                $unit = Unit::create(array_merge([
                    'make_id' => $franchiseData['make_id'],
                    'plate_number' => $franchiseData['plate_number'],
                    'motor_number' => $franchiseData['motor_number'],
                    'chassis_number' => $franchiseData['chassis_number'],
                    'cr_number' => $franchiseData['cr_number'] ?? null,
                    'model_year' => $franchiseData['model_year'] ?? date('Y'),
                    'condition' => 'Good',
                ], $unitPhotos)); // [!code focus] Merge photos into creation array

                // 2. Create Franchise
                $franchiseNumber = 'FR-' . strtoupper(uniqid()); 
                
                $franchise = Franchise::create([
                    'franchise_number' => $franchiseNumber,
                    'zone_id' => $franchiseData['zone_id'],
                    'status' => 'active', 
                    'date_issued' => $franchiseData['date_issued'],
                    'expiry_date' => now()->addYear(), 
                ]);

                // 3. Create Ownership Record
                $ownership = Ownership::create([
                    'franchise_id' => $franchise->id,
                    'new_operator_id' => $operator->id,
                    'date_transferred' => now(),
                    'remarks' => 'Initial Ownership from Application',
                ]);

                // 4. Update Franchise Current Ownership
                $franchise->update(['ownership_id' => $ownership->id]);

                // 5. Create Active Unit Record
                $activeUnit = ActiveUnit::create([
                    'franchise_id' => $franchise->id,
                    'new_unit_id' => $unit->id,
                    'date_changed' => now(),
                    'remarks' => 'Initial Unit',
                ]);

                // 6. Update Franchise Current Unit
                $franchise->update(['active_unit_id' => $activeUnit->id]);

                $qrContent = route('franchises.public_show', $franchise->id);
                $qrImage = QrCode::format('svg')->size(300)->generate($qrContent);
                $filename = 'qr-' . $franchise->id . '.svg';
                Storage::disk('public')->put('qrcodes/' . $filename, $qrImage);
                $franchise->update(['qr_code'        => $filename]);
            }

            // D. Finalize Application
            $application->update([
                'status' => 'approved', 
                'franchise_id' => isset($franchise) ? $franchise->id : null, 
            ]);

        });

        return redirect()->back()->with('success', 'Franchise Account created successfully!');

    } catch (\Exception $e) {
        return redirect()->back()->withErrors(['error' => 'Transaction Failed: ' . $e->getMessage()]);
    }
}
}