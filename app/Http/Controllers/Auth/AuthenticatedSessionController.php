<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole; 
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $url = match($request->user()->role) {
            \App\Enums\UserRole::ADMIN => route('admin.dashboard', absolute: false),
            \App\Enums\UserRole::FRANCHISE_OWNER => route('franchise.dashboard', absolute: false),
            \App\Enums\UserRole::EVALUATOR => route('evaluator.applications.index', absolute: false),
            \App\Enums\UserRole::INSPECTOR => route('inspector.applications.index', absolute: false),
            \App\Enums\UserRole::CITY_ANTI_POLLUTION_OFFICER => route('capo.applications.index', absolute: false),
            \App\Enums\UserRole::REVIEWER => route('reviewer.applications.index', absolute: false),
            \App\Enums\UserRole::SP_APPROVER => route('sp_approver.applications.index', absolute: false),
            \App\Enums\UserRole::TAB_APPROVER => route('tab_approver.applications.index', absolute: false),
            \App\Enums\UserRole::ENCODER => route('admin.applications.index', absolute: false),
            \App\Enums\UserRole::COLLECTOR => route('admin.assessments.index', absolute: false),
            \App\Enums\UserRole::RELEASER => route('admin.franchises.index', absolute: false),
            default => route('login'), 
        };

        return redirect()->intended($url);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}