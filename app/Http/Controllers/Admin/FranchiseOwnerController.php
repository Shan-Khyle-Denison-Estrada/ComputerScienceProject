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

class FranchiseOwnerController extends Controller
{
    // ... index and store methods remain the same ...

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