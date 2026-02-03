<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\DriverController;
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

    // 2. Zone Management
    // This single line creates routes for: index, store, update, and destroy
Route::resource('admin/zones', ZoneController::class)
    ->names([
        'index'   => 'admin.zones.index',
        'store'   => 'admin.zones.store',
        'create'  => 'admin.zones.create',
        'show'    => 'admin.zones.show',
        'update'  => 'admin.zones.update',
        'destroy' => 'admin.zones.destroy',
        'edit'    => 'admin.zones.edit',
    ]);

    // 3. User Management
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{id}', [UserController::class, 'update'])->name('admin.users.update');

    // 4. Barangay Management
    Route::post('/admin/barangays', [\App\Http\Controllers\Admin\BarangayController::class, 'store'])->name('admin.barangays.store');
    Route::put('/admin/barangays/{barangay}', [\App\Http\Controllers\Admin\BarangayController::class, 'update'])->name('admin.barangays.update');
    Route::delete('/admin/barangays/{barangay}', [\App\Http\Controllers\Admin\BarangayController::class, 'destroy'])->name('admin.barangays.destroy');

    // 5. Driver Management
    Route::get('/admin/drivers', [DriverController::class, 'index'])->name('admin.drivers.index');
    Route::post('/admin/drivers', [DriverController::class, 'store'])->name('admin.drivers.store');
    Route::put('/admin/drivers/{driver}', [DriverController::class, 'update'])->name('admin.drivers.update'); // Using POST for file uploads with method spoofing
});

// --- FRANCHISE OWNER ROUTES ---
Route::middleware(['auth', 'role:franchise_owner'])->group(function () {
    Route::get('/franchise/dashboard', function () {
        return Inertia::render('Franchise/Dashboard');
    })->name('franchise.dashboard');
});

// --- PROFILE MANAGEMENT ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';