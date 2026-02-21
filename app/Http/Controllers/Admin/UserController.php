<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule; // <-- Added this
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // 1. Removed strict 'admin' requirement. 
        // Optional: If you want to hide Franchise Owners from this specific staff management list, uncomment the line below:
        // $query->where('role', '!=', UserRole::FRANCHISE_OWNER->value); 

        // 2. Handle Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 3. Handle Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(6)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'contact_number' => 'nullable|string|max:255', // <-- Added
            'street_address' => 'nullable|string|max:255', // <-- Added
            'barangay' => 'nullable|string|max:255',       // <-- Added
            'city' => 'nullable|string|max:255',           // <-- Added
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)], // Using Enum validation
            'photo' => 'nullable|image|max:2048', // Max 2MB
        ]);

        // Handle Photo Upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
        }

        User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number, // <-- Added
            'street_address' => $request->street_address, // <-- Added
            'barangay' => $request->barangay,             // <-- Added
            'city' => $request->city,                     // <-- Added
            'password' => Hash::make($request->password),
            'role' => $request->role, 
            'user_photo' => $photoPath,
            'status' => 'active',
        ]);

        return back()->with('success', 'User account created successfully.');
    }
public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'contact_number' => 'nullable|string|max:255', // <-- Added
            'street_address' => 'nullable|string|max:255', // <-- Added
            'barangay' => 'nullable|string|max:255',       // <-- Added
            'city' => 'nullable|string|max:255',           // <-- Added
            'role' => ['required', Rule::enum(UserRole::class)], 
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number, // <-- Added
            'street_address' => $request->street_address, // <-- Added
            'barangay' => $request->barangay,             // <-- Added
            'city' => $request->city,                     // <-- Added
            'role' => $request->role,
            'status' => $request->status,
        ];

        // Update Password only if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $data['password'] = Hash::make($request->password);
        }

        // Handle Photo Replacement
        if ($request->hasFile('photo')) {
            if ($user->user_photo) {
                Storage::disk('public')->delete($user->user_photo);
            }
            $data['user_photo'] = $request->file('photo')->store('profile-photos', 'public');
        }

        $user->update($data);

        return back()->with('success', 'User updated successfully.');
    }
}