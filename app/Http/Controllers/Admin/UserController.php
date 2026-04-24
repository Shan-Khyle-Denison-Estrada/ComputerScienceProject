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
        $search = $request->input('search');
        $status = $request->input('status');
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        $query = User::with('temporaryRoles')
            // Hide Franchise Owners from this specific staff management list
            ->where('role', '!=', UserRole::FRANCHISE_OWNER->value)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                      ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'user_name') {
                    $query->orderBy('first_name', $sortDirection)
                          ->orderBy('last_name', $sortDirection);
                } else {
                    $allowedSorts = ['email', 'contact_number', 'role', 'status'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy($sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                $query->latest();
            });

        $users = $query->paginate(6)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'sortField', 'sortDirection']),
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
            'province' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',       
            'city' => 'nullable|string|max:255',           
            'role' => ['required', Rule::enum(UserRole::class)], 
            'photo' => 'nullable|image|max:2048', 
            'signature' => 'nullable|image|max:2048', 
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

        // --- NEW LOGIC: Ensure only one active SP/TAB Approver ---
        // Note: New users are active by default in the store method.
        if (in_array($request->role, ['sp_approver', 'tab_approver'])) {
            User::where('role', $request->role)
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
        }
        // ---------------------------------------------------------
        $generatedPassword = \Illuminate\Support\Str::password(10, true, true, false, false);
        $user = User::create([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number, 
            'street_address' => $request->street_address, 
            'province' => $request->province,
            'barangay' => $request->barangay,             
            'city' => $request->city,                     
            'password' => Hash::make($generatedPassword),
            'force_password_change' => true,
            'role' => $request->role, 
            'user_photo' => $photoPath,
            'signature_photo' => $signaturePath, 
            'status' => 'active', // Defaulting to active
        ]);

        // Send Email to the new Staff User
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\NewAccountCredentials($user, $generatedPassword));

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
            'province' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',       
            'city' => 'nullable|string|max:255',           
            'role' => ['required', Rule::enum(UserRole::class)], 
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number, 
            'street_address' => $request->street_address,
            'province' => $request->province,
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

        // --- NEW LOGIC: Ensure only one active SP/TAB Approver ---
        // If the user is being set to an active SP or TAB Approver, deactivate others with the same role
        if (in_array($request->role, ['sp_approver', 'tab_approver']) && $request->status === 'active') {
            User::where('role', $request->role)
                ->where('id', '!=', $user->id) // Exclude the user currently being updated
                ->where('status', 'active')
                ->update(['status' => 'inactive']);
        }
        // ---------------------------------------------------------

        $user->update($data);

        return back()->with('success', 'User updated successfully.');
    }

    public function assignTemporaryRole(Request $request, User $user)
    {
        // 1. Validate the request
        $request->validate([
            'role' => ['required', 'string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'permissions' => ['nullable', 'array'] // Validate the array
        ]);

        // --- NEW SECURITY CHECKS ---
        // 2. Prevent SP Approvers from receiving temporary roles
        $baseRole = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;
        if ($baseRole === 'sp_approver') {
            return back()->with('error', 'SP Approvers cannot be assigned temporary roles.');
        }

        // 3. Prevent assigning a temporary role that the user already permanently holds
        if ($baseRole === $request->role) {
            return back()->with('error', 'The user already holds this role permanently.');
        }
        // ---------------------------

        // 4. Save to database
        \App\Models\TemporaryRole::updateOrCreate(
            ['user_id' => $user->id, 'role' => $request->role],
            [
                'granted_by' => auth()->id(), 
                'expires_at' => $request->expires_at,
                'permissions' => $request->permissions ?? [] // Save it!
            ]
        );

        return back()->with('success', 'Temporary role assigned successfully.');
    }

    public function revokeTemporaryRole(User $user, $role)
    {
        \App\Models\TemporaryRole::where('user_id', $user->id)
                                 ->where('role', $role)
                                 ->delete();
                                 
        return back()->with('success', 'Temporary role revoked successfully.');
    }
}