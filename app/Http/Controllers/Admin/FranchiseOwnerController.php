<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Operator;
use App\Models\Barangay; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use App\Enums\UserRole;

class FranchiseOwnerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Fetch Users with 'franchise_owner' role AND their Operator details
        // Strict filtering for 'franchise_owner' role
        $users = User::with('operator')
            ->where('role', 'franchise_owner') 
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Fetch Barangays for the dropdown
        $barangays = Barangay::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/FranchiseOwners/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
            'barangays' => $barangays, 
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'contact_number' => 'nullable|string|max:20',
            'street_address' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'user_photo' => 'nullable|image|max:2048', 
        ]);

        DB::transaction(function () use ($request) {
            
            // Handle Photo Upload
            $photoPath = null;
            if ($request->hasFile('user_photo')) {
                $photoPath = $request->file('user_photo')->store('user-photos', 'public');
            }

            // 1. Create User
            $user = User::create([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                
                // FORCE ROLE TO FRANCHISE OWNER
                'role' => UserRole::FRANCHISE_OWNER, 
                
                'contact_number' => $request->contact_number,
                'street_address' => $request->street_address,
                'barangay' => $request->barangay,
                'city' => $request->city,
                'user_photo' => $photoPath,
                'status' => 'active',
            ]);

            // 2. Create Operator Profile
            Operator::create([
                'user_id' => $user->id,
                'tin_number' => $request->tin_number,
            ]);
        });

        return redirect()->back()->with('success', 'Franchise owner registered successfully.');
    }

public function update(Request $request, User $user)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'street_address' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'user_photo' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive', // Added validation
        ]);

        DB::transaction(function () use ($request, $user) {
            
            // Exclude fields handled manually
            $data = $request->except(['tin_number', 'password', 'user_photo', 'role']);

            // Handle Photo Replacement
            if ($request->hasFile('user_photo')) {
                if ($user->user_photo) {
                    Storage::disk('public')->delete($user->user_photo);
                }
                $data['user_photo'] = $request->file('user_photo')->store('user-photos', 'public');
            }

            // Update Password if provided
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // Update User (Includes Status)
            $user->update($data);

            // Update Operator Details
            $user->operator()->updateOrCreate(
                ['user_id' => $user->id],
                ['tin_number' => $request->tin_number]
            );
        });

        return redirect()->back()->with('success', 'Franchise owner updated successfully.');
    }
}