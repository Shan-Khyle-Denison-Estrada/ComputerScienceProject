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
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ComplaintController;
use App\Http\Controllers\Admin\RedFlagController;
use App\Models\Franchise;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // [!code ++] Fetch the count and pass it to the view
    return Inertia::render('Index', [
        'activeFranchisesCount' => Franchise::count()
    ]);
})->name('home');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/ordinances', function () {
    return Inertia::render('Ordinances');
})->name('ordinances');

// The Verification Page (Scanner)
Route::get('/verify', [FranchiseController::class, 'verify'])->name('verify');
Route::post('/verify/lookup', [FranchiseController::class, 'lookup'])->name('verify.lookup');

// NEW: Public Franchise Detail View
Route::get('/franchise-check/{id}', [FranchiseController::class, 'publicShow'])->name('franchises.public_show');

Route::post('/complaints/report', [ComplaintController::class, 'store'])->name('complaints.store');

// --- ADMIN ROUTES ---
Route::middleware(['auth', 'role:admin'])->group(function () {
    
    // 1. Dashboard
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

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
    Route::post('/admin/franchises/{franchise}/complaints', [ComplaintController::class, 'store'])->name('admin.franchises.complaints.store');

    // 12. Franchise Actions
    Route::post('/admin/franchises/{franchise}/transfer', [FranchiseController::class, 'transferOwnership'])->name('admin.franchises.transfer');
    Route::post('/admin/franchises/{franchise}/change-unit', [FranchiseController::class, 'changeUnit'])->name('admin.franchises.change-unit');

    // 13. Driver Assignment Routes
    Route::post('/admin/franchises/{franchise}/drivers', [FranchiseController::class, 'assignDriver'])->name('admin.franchises.assign-driver');
    Route::delete('/admin/franchises/{franchise}/drivers/{assignment}', [FranchiseController::class, 'removeDriver'])->name('admin.franchises.remove-driver');

    // 14. Complaint Route
    Route::get('/admin/complaints', [ComplaintController::class, 'index'])->name('admin.complaints.index');
    Route::post('/admin/franchises/{franchise}/complaints', [FranchiseController::class, 'storeComplaint'])->name('admin.franchises.complaints.store');
    Route::patch('/admin/complaints/{complaint}/resolve', [FranchiseController::class, 'resolveComplaint'])->name('admin.complaints.resolve');

    // 15. Red Flags Routes
    Route::get('/admin/red-flags', [RedFlagController::class, 'index'])->name('admin.red-flags.index');
    Route::post('/admin/red-flags/nature', [RedFlagController::class, 'storeNature'])->name('admin.red-flags.nature.store'); // For Nature CRUD
    Route::delete('/admin/red-flags/nature/{nature}', [RedFlagController::class, 'destroyNature'])->name('admin.red-flags.nature.destroy');
    
    // Storing a Red Flag (usually from Franchise Show page)
    Route::post('/admin/franchises/{franchise}/red-flags', [RedFlagController::class, 'store'])->name('admin.franchises.red-flags.store');
    
    // resolving
    Route::patch('/admin/red-flags/{redFlag}/resolve', [RedFlagController::class, 'resolve'])->name('admin.red-flags.resolve');

    Route::post('/admin/complaints/nature', [ComplaintController::class, 'storeNature'])->name('admin.complaints.nature.store');
    Route::delete('/admin/complaints/nature/{nature}', [ComplaintController::class, 'destroyNature'])->name('admin.complaints.nature.destroy');

    Route::get('/admin/applications', function () {
        return Inertia::render('Admin/Applications/Index');
    })->name('admin.applications.index');

    Route::get('/admin/applications/{id}', function ($id) {
        return Inertia::render('Admin/Applications/Show', ['id' => $id]);
    })->name('admin.applications.show');
});

// --- FRANCHISE OWNER ROUTES ---
Route::middleware(['auth', 'role:franchise_owner'])->group(function () {
    Route::get('/franchise/dashboard', function () {
        return Inertia::render('Franchise/Dashboard');
    })->name('franchise.dashboard');

    Route::get('/franchise/dashboard', [DashboardController::class, 'index'])->name('franchise.dashboard');

    Route::post('/franchise/{franchise}/set-driver', [DashboardController::class, 'setActiveDriver'])
        ->name('franchise.set-driver');

    Route::get('/franchise/applications', function () {
        return Inertia::render('Franchise/MakeApplication');
    })->name('franchise.make-application');
});

// --- PROFILE MANAGEMENT ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';