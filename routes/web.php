<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\AssessmentController;
use App\Http\Controllers\Admin\ParticularController;
use App\Http\Controllers\Admin\FranchiseOwnerController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UnitMakeController;
use App\Http\Controllers\Admin\FranchiseController;
use App\Http\Controllers\Franchise\DashboardController;
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

    // 3. User Management (Admins)
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    // 4. Franchise Owners (Operators) - NEW ROUTES
    Route::get('/admin/franchise-owners', [FranchiseOwnerController::class, 'index'])->name('admin.franchise-owners.index');
    Route::post('/admin/franchise-owners', [FranchiseOwnerController::class, 'store'])->name('admin.franchise-owners.store');
    Route::put('/admin/franchise-owners/{user}', [FranchiseOwnerController::class, 'update'])->name('admin.franchise-owners.update');

    // 5. Driver Management
    Route::resource('admin/drivers', DriverController::class)
        ->names([
            'index'   => 'admin.drivers.index',
            'store'   => 'admin.drivers.store',
            'create'  => 'admin.drivers.create',
            'show'    => 'admin.drivers.show',
            'update'  => 'admin.drivers.update',
            'destroy' => 'admin.drivers.destroy',
            'edit'    => 'admin.drivers.edit',
        ]);

    // 6. Payment Routes
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');

    // 7. Assessment Routes
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('admin.assessments.index');
    Route::post('/assessments', [AssessmentController::class, 'store'])->name('admin.assessments.store');

    // 8. Particulars (Fee Types) Routes
    Route::post('/particulars', [ParticularController::class, 'store'])->name('admin.particulars.store');
    Route::put('/particulars/{particular}', [ParticularController::class, 'update'])->name('admin.particulars.update');
    Route::delete('/particulars/{particular}', [ParticularController::class, 'destroy'])->name('admin.particulars.destroy');

    // 9. Units Routes
    Route::get('/admin/units', [UnitController::class, 'index'])->name('admin.units.index');
    Route::post('/admin/units', [UnitController::class, 'store'])->name('admin.units.store');
    Route::put('/admin/units/{unit}', [UnitController::class, 'update'])->name('admin.units.update');

    // 10. Unit Makes (Brands) Routes
    Route::post('/admin/unit-makes', [UnitMakeController::class, 'store'])->name('admin.unit-makes.store');
    Route::put('/admin/unit-makes/{unitMake}', [UnitMakeController::class, 'update'])->name('admin.unit-makes.update');
    Route::delete('/admin/unit-makes/{unitMake}', [UnitMakeController::class, 'destroy'])->name('admin.unit-makes.destroy');

    // 11. Franchise Management Routes
    Route::get('/admin/franchises', [FranchiseController::class, 'index'])->name('admin.franchises.index');
    Route::post('/admin/franchises', [FranchiseController::class, 'store'])->name('admin.franchises.store');
    Route::get('/admin/franchises/{franchise}', [FranchiseController::class, 'show'])->name('admin.franchises.show');

    // 12. Franchise Actions (Transfer / Swap) Routes
    Route::post('/admin/franchises/{franchise}/transfer', [FranchiseController::class, 'transferOwnership'])->name('admin.franchises.transfer');
    Route::post('/admin/franchises/{franchise}/change-unit', [FranchiseController::class, 'changeUnit'])->name('admin.franchises.change-unit');
});

// --- FRANCHISE OWNER ROUTES ---
Route::middleware(['auth', 'role:franchise_owner'])->group(function () {
    Route::get('/franchise/dashboard', function () {
        return Inertia::render('Franchise/Dashboard');
    })->name('franchise.dashboard');

    Route::get('/franchise/dashboard', [DashboardController::class, 'index'])->name('franchise.dashboard');
});

// --- PROFILE MANAGEMENT ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';