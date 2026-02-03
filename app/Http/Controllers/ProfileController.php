<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage; // Import Storage
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            // Ensure the user data usually flows automatically via HandleInertiaRequests middleware,
            // but you can pass specific overrides here if needed.
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        // 1. Get validated data
        $validated = $request->validated();
        $user = $request->user();

        // 2. Handle Photo Upload
        if ($request->hasFile('photo')) {
            // Delete old photo if it exists
            if ($user->user_photo) {
                Storage::disk('public')->delete($user->user_photo);
            }

            // Store new photo and update path in $validated array
            // This stores in storage/app/public/profile-photos
            $path = $request->file('photo')->store('profile-photos', 'public');
            $validated['user_photo'] = $path;
        }

        // 3. Update User Model
        // We remove 'photo' from validated because the column name is 'user_photo'
        unset($validated['photo']); 
        
        $user->fill($validated);

        // 4. Reset email verification if email changed
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}