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
        $query = Driver::query();

        // Search Filter
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('license_number', 'like', "%{$request->search}%");
            });
        }

        // Status Filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $drivers = $query->latest()->paginate(10)->withQueryString();

        // Fetch Data for Dropdowns
        $franchiseOwners = User::where('role', 'franchise_owner')->get(); 
        $barangays = Barangay::orderBy('name')->get(); // Fetch Barangays sorted by name

        return Inertia::render('Admin/Drivers/Index', [
            'drivers' => $drivers,
            'franchiseOwners' => $franchiseOwners,
            'barangays' => $barangays, // Pass to View
            'filters' => $request->only(['search', 'status']),
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
            'barangay' => 'nullable|string',
            'city' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|string',
            'user_photo' => 'nullable|image|max:2048',
            'license_front_photo' => 'nullable|image|max:2048',
            'license_back_photo' => 'nullable|image|max:2048',
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
        // ... (Existing update logic remains the same)
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'license_number' => 'required|string|unique:drivers,license_number,' . $driver->id,
            'license_expiration_date' => 'required|date',
            'status' => 'required|string',
            'middle_name' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'street' => 'nullable|string',
            'barangay' => 'nullable|string',
            'city' => 'nullable|string',
        ]);
        
        if ($request->hasFile('user_photo')) {
            if ($driver->user_photo) Storage::disk('public')->delete($driver->user_photo);
            $driver->user_photo = $request->file('user_photo')->store('driver_photos', 'public');
        }
        // Add similar checks for license photos if needed for update

        $driver->update($request->except(['user_photo', 'license_front_photo', 'license_back_photo']));
        if($request->hasFile('user_photo')) $driver->save();

        return redirect()->back();
    }
}