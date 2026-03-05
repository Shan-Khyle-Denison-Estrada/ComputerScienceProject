<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Handle Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Handle Filtering
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
            'contact_number' => 'nullable|string|max:255', 
            'street_address' => 'nullable|string|max:255', 
            'barangay' => 'nullable|string|max:255',       
            'city' => 'nullable|string|max:255',           
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)], 
            'photo' => 'nullable|image|max:2048', 
            'signature' => 'nullable|image|max:2048', // <-- Added Signature Validation
        ]);

        // Handle Photo Upload
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
        }

        // Handle Signature Upload
        $signaturePath = null;
        if ($request->hasFile('signature') && in_array($request->role, ['sp_approver', 'tab_approver'])) {
            $signaturePath = $request->file('signature')->store('signatures', 'public');
        }

        User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number, 
            'street_address' => $request->street_address, 
            'barangay' => $request->barangay,             
            'city' => $request->city,                     
            'password' => Hash::make($request->password),
            'role' => $request->role, 
            'user_photo' => $photoPath,
            'signature_photo' => $signaturePath, // <-- Added to DB Insertion
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
            'contact_number' => 'nullable|string|max:255', 
            'street_address' => 'nullable|string|max:255', 
            'barangay' => 'nullable|string|max:255',       
            'city' => 'nullable|string|max:255',           
            'role' => ['required', Rule::enum(UserRole::class)], 
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048', // <-- Added Signature Validation
        ]);

        $data = [
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number, 
            'street_address' => $request->street_address, 
            'barangay' => $request->barangay,             
            'city' => $request->city,                     
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

        // Handle Signature Replacement
        if ($request->hasFile('signature') && in_array($request->role, ['sp_approver', 'tab_approver'])) {
            if ($user->signature_photo) {
                Storage::disk('public')->delete($user->signature_photo);
            }
            $data['signature_photo'] = $request->file('signature')->store('signatures', 'public');
        }

        $user->update($data);

        return back()->with('success', 'User updated successfully.');
    }
}