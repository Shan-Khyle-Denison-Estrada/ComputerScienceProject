<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Models\Barangay; //
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        $query = Driver::query();

       // 1. Search Filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  // Allows searching for "First Last"
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  // Allows searching for "Last First"
                  ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"])
                  ->orWhere('license_number', 'like', "%{$search}%");
            });
        }

        // 2. Status Filter
        if ($status) {
            $query->where('status', $status);
        }

        // 3. Handle Sorting
        if ($sortField) {
            if ($sortField === 'name') {
                $query->orderBy('last_name', $sortDirection)
                      ->orderBy('first_name', $sortDirection);
            } else {
                $allowedSorts = ['license_expiration_date', 'status'];
                if (in_array($sortField, $allowedSorts)) {
                    $query->orderBy($sortField, $sortDirection);
                }
            }
        } else {
            $query->latest();
        }

        // Eager load assignments and logs to find franchises and complaints
        $drivers = $query->with([
            'driverAssignments.franchise',
            'driverLogs.franchise.complaints'
        ])->paginate(6)->withQueryString();

        // Process strictly-matched complaints for each driver based on their logs
        $drivers->getCollection()->transform(function ($driver) {
            $myComplaints = collect();

            foreach ($driver->driverLogs as $log) {
                if (!$log->franchise) continue;
                
                foreach ($log->franchise->complaints as $complaint) {
                    if ($complaint->incident_date && $complaint->incident_time) {
                        $incidentString = \Carbon\Carbon::parse($complaint->incident_date . ' ' . $complaint->incident_time)->format('Y-m-d H:i');
                        $startString = \Carbon\Carbon::parse($log->started_at)->timezone('Asia/Manila')->format('Y-m-d H:i');
                        $endString = $log->ended_at 
                            ? \Carbon\Carbon::parse($log->ended_at)->timezone('Asia/Manila')->format('Y-m-d H:i')
                            : \Carbon\Carbon::now()->timezone('Asia/Manila')->format('Y-m-d H:i');

                        if ($incidentString >= $startString && $incidentString <= $endString) {
                            // Ensure no duplicates if logs somehow overlapped
                            if (!$myComplaints->contains('id', $complaint->id)) {
                                $complaint->franchise_number = $log->franchise->franchise_number; // attach for the UI
                                $myComplaints->push($complaint);
                            }
                        }
                    }
                }
            }
            
            $driver->assigned_complaints = $myComplaints->sortByDesc('incident_date')->values();
            return $driver;
        });

        // Fetch Data for Dropdowns
        $franchiseOwners = User::where('role', 'franchise_owner')->get();
        $barangays = Barangay::orderBy('name')->get(); // Fetch Barangays sorted by name
        $existingDriverUserIds = Driver::whereNotNull('user_id')->pluck('user_id')->toArray();

        return Inertia::render('Admin/Drivers/Index', [
            'drivers' => $drivers,
            'franchiseOwners' => $franchiseOwners,
            'barangays' => $barangays, // Pass to View
            'existingDriverUserIds' => $existingDriverUserIds,
            'filters' => $request->only(['search', 'status', 'sortField', 'sortDirection']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|string',
            'user_photo' => 'nullable|image|max:2048',
            'license_front_photo' => 'required|image|max:2048', // Changed to required
            'license_back_photo' => 'required|image|max:2048',  // Changed to required
        ]);

        if ($request->hasFile('user_photo')) {
            $validated['user_photo'] = $request->file('user_photo')->store('driver_photos', 'public');
        }
        if ($request->hasFile('license_front_photo')) {
            $validated['license_front_photo'] = $request->file('license_front_photo')->store('license_photos', 'public');
        }
        if ($request->hasFile('license_back_photo')) {
            $validated['license_back_photo'] = $request->file('license_back_photo')->store('license_photos', 'public');
        }

        Driver::create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'license_number' => 'required|string|unique:drivers,license_number,' . $driver->id,
            'license_expiration_date' => 'required|date',
            'status' => 'required|string',
            'middle_name' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'street' => 'nullable|string',
            'province' => 'nullable|string',
            'barangay' => 'nullable|string',
            'city' => 'nullable|string',
            // Ensure these validations are present for security (Kept nullable for updates)
            'user_photo' => 'nullable|image|max:2048',
            'license_front_photo' => 'nullable|image|max:2048',
            'license_back_photo' => 'nullable|image|max:2048',
        ]);
        
        // 1. Handle User Photo
        if ($request->hasFile('user_photo')) {
            if ($driver->user_photo) Storage::disk('public')->delete($driver->user_photo);
            $driver->user_photo = $request->file('user_photo')->store('driver_photos', 'public');
        }

        // 2. Handle License Front Photo 
        if ($request->hasFile('license_front_photo')) {
            if ($driver->license_front_photo) Storage::disk('public')->delete($driver->license_front_photo);
            $driver->license_front_photo = $request->file('license_front_photo')->store('license_photos', 'public');
        }

        // 3. Handle License Back Photo 
        if ($request->hasFile('license_back_photo')) {
            if ($driver->license_back_photo) Storage::disk('public')->delete($driver->license_back_photo);
            $driver->license_back_photo = $request->file('license_back_photo')->store('license_photos', 'public');
        }

        // 4. Update other fields
        // We exclude the photo fields here because we manually handled them above
        $driver->fill($request->except(['user_photo', 'license_front_photo', 'license_back_photo']));
        
        // 5. Save all changes
        $driver->save();

        return redirect()->back();
    }
}