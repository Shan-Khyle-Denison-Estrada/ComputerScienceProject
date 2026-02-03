<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Landing Page
Route::get('/', function () {
    return Inertia::render('Index');
})->name('home');

// --- ADMIN ROUTES ---
Route::middleware(['auth', 'role:admin'])->group(function () {
    
    // 1. Dashboard
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    // 2. Manage Users (Connected to Controller)
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
});

// --- FRANCHISE OWNER ROUTES ---
Route::middleware(['auth', 'role:franchise_owner'])->group(function () {
    Route::get('/franchise/dashboard', function () {
        return Inertia::render('Franchise/Dashboard');
    })->name('franchise.dashboard');
});

// --- PROFILE MANAGEMENT ---
Route::middleware('auth')->group(function () {
    // We are pointing to the standard ProfileController here.
    // Ensure you have a standard ProfileController or create one that returns Inertia::render('Profile/Edit');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';