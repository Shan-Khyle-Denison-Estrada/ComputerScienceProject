<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    public function create(Request $request)
    {
        // If they try to access this page but don't need to change their password, kick them out
        if (!$request->user()->force_password_change) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ForcePasswordChange');
    }

    public function store(Request $request)
    {
        // Double check they actually need to change it
        if (!$request->user()->force_password_change) {
            return redirect()->route('dashboard');
        }

        // Validate the new password
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Update the user's password and turn OFF the force change flag
        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        // Route them to the traffic director which will send them to their dashboard
        return redirect()->intended(route('dashboard', absolute: false));
    }
}