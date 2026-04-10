<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Operator;
use App\Models\Franchise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class FranchiseOwnerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status'); // Get status filter
        $sortField = $request->input('sortField', '');
        $sortDirection = $request->input('sortDirection', '');

        // Fetch Users with 'franchise_owner' role
        $users = User::with('operator')
            ->where('role', 'franchise_owner')
            // 1. Handle Search
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            // 2. Handle Status Filter
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            // 3. Handle Sorting
            ->when($sortField, function ($query) use ($sortField, $sortDirection) {
                if ($sortField === 'name') {
                    $query->orderBy('last_name', $sortDirection)
                          ->orderBy('first_name', $sortDirection);
                } else {
                    $allowedSorts = ['status'];
                    if (in_array($sortField, $allowedSorts)) {
                        $query->orderBy($sortField, $sortDirection);
                    }
                }
            }, function ($query) {
                // Default sorting when no sortField is provided
                $query->latest();
            })
            ->paginate(6)
            ->withQueryString();

        // Eager load the current franchises for each user/operator in the paginated set
        $users->getCollection()->transform(function ($user) {
            $user->franchises = [];
            if ($user->operator) {
                $user->franchises = Franchise::whereHas('currentOwnership', function ($query) use ($user) {
                    $query->where('new_operator_id', $user->operator->id);
                })->with('zone')->get(); // We pull the zone to display it nicely on the cards
            }
            return $user;
        });

        return Inertia::render('Admin/FranchiseOwners/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'sortField', 'sortDirection']), 
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
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'user_photo' => 'nullable|image|max:2048', 
        ]);

        DB::transaction(function () use ($request) {
            $photoPath = null;
            if ($request->hasFile('user_photo')) {
                $photoPath = $request->file('user_photo')->store('user-photos', 'public');
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'franchise_owner', 
                'contact_number' => $request->contact_number,
                'street_address' => $request->street_address,
                'province' => $request->province,
                'city' => $request->city,
                'barangay' => $request->barangay,
                'user_photo' => $photoPath,
                'status' => 'active',
            ]);

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
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'barangay' => 'required|string|max:255',
            'tin_number' => 'nullable|string|max:50',
            'user_photo' => 'nullable|image|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        DB::transaction(function () use ($request, $user) {
            $data = $request->except(['tin_number', 'password', 'user_photo', 'role']);

            if ($request->hasFile('user_photo')) {
                if ($user->user_photo) {
                    Storage::disk('public')->delete($user->user_photo);
                }
                $data['user_photo'] = $request->file('user_photo')->store('user-photos', 'public');
            }

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            $user->operator()->updateOrCreate(
                ['user_id' => $user->id],
                ['tin_number' => $request->tin_number]
            );
        });

        return redirect()->back()->with('success', 'Franchise owner updated successfully.');
    }
}