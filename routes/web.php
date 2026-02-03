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
    
    // 1. Dashboard (Redesigned)
    Route::get('/admin/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    // 2. Manage Users (New Page)
    Route::get('/admin/users', function () {
        // In a real app, you would pass a list of users here: 'users' => User::all()
        return Inertia::render('Admin/Users/Index'); 
    })->name('admin.users.index');

    // Inside Admin Routes group...
    Route::put('/admin/users/{id}', [UserController::class, 'update'])->name('admin.users.update');

    // Store New User Logic
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
});

// --- FRANCHISE OWNER ROUTES ---
Route::middleware(['auth', 'role:franchise_owner'])->group(function () {
    Route::get('/franchise/dashboard', function () {
        return Inertia::render('Franchise/Dashboard');
    })->name('franchise.dashboard');
});

// Profile Management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';