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
use App\Http\Controllers\Public\ApplicationController;
use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\ApplicationShowController;
use App\Http\Controllers\Franchise\ApplicationController as FranchiseApplicationController;
use App\Http\Controllers\Admin\ApplicationChangeOfUnitShowController;
use App\Http\Controllers\Admin\ApplicationChangeOfOwnerShowController;
use App\Http\Controllers\Admin\ApplicationRenewalShowController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Evaluator\EvaluatorApplicationController;
use App\Http\Controllers\Inspector\InspectorApplicationController;
use App\Http\Controllers\Capo\CapoApplicationController;
use App\Http\Controllers\Reviewer\ReviewerApplicationController;
use App\Http\Controllers\SpApprover\SpApproverApplicationController;
use App\Http\Controllers\TabApprover\TabApproverApplicationController;
use App\Http\Controllers\Encoder\EncoderApplicationController;
use Illuminate\Support\Facades\Auth; // <-- Add this
use App\Enums\UserRole; // <-- Add this
use App\Models\Franchise;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::post('/apply/send-otp', [ApplicationController::class, 'sendOtp'])->name('application.send-otp');
Route::post('/apply/verify-otp', [ApplicationController::class, 'verifyOtp'])->name('application.verify-otp');  

// --- THE NEW TRAFFIC DIRECTOR ---
Route::get('/dashboard', function () {
    $user = Auth::user();
    $role = $user->role instanceof UserRole ? $user->role->value : $user->role;

    return match ($role) {
        UserRole::ADMIN->value => redirect()->route('admin.dashboard'),
        UserRole::FRANCHISE_OWNER->value => redirect()->route('franchise.dashboard'),
        UserRole::COLLECTOR->value => redirect()->route('payments.index'),
        UserRole::EVALUATOR->value => redirect()->route('evaluations.index'),
        UserRole::INSPECTOR->value => redirect()->route('inspections.index'),
        UserRole::CITY_ANTI_POLLUTION_OFFICER->value => redirect()->route('capo.inspections.index'),
        UserRole::REVIEWER->value, UserRole::SP_APPROVER->value, UserRole::TAB_APPROVER->value => redirect()->route('approvals.index'),
        UserRole::RELEASER->value => redirect()->route('releasing.index'),
        UserRole::ENCODER->value => redirect()->route('encodes.index'),
        default => Inertia::render('Dashboard'), // Fallback if a role has no specific route
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/', function () {
    return Inertia::render('Index', [
        'renewedFranchisesSum' => \App\Models\Franchise::where('status', 'Renewed')
            ->count(), // Replace 'amount' with your actual column name
    ]);
});

Route::get('/apply', [ApplicationController::class, 'create'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store'])->name('application.store');

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
    // Route::resource('admin/drivers', DriverController::class)
    //     ->names([
    //         'index'   => 'admin.drivers.index',
    //         'store'   => 'admin.drivers.store',
    //         'create'  => 'admin.drivers.create',
    //         'show'    => 'admin.drivers.show',
    //         'update'  => 'admin.drivers.update',
    //         'destroy' => 'admin.drivers.destroy',
    //         'edit'    => 'admin.drivers.edit',
    //     ]);

    // // 6. Payment Routes
    // Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    // Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');

    // // 7. Assessment Routes
    // Route::get('/assessments', [AssessmentController::class, 'index'])->name('admin.assessments.index');
    // Route::post('/assessments', [AssessmentController::class, 'store'])->name('admin.assessments.store');

    // 9. Units Routes
    Route::get('/admin/units', [UnitController::class, 'index'])->name('admin.units.index');
    Route::post('/admin/units', [UnitController::class, 'store'])->name('admin.units.store');
    Route::put('/admin/units/{unit}', [UnitController::class, 'update'])->name('admin.units.update');

    // 10. Unit Makes (Brands) Routes
    Route::post('/admin/unit-makes', [UnitMakeController::class, 'store'])->name('admin.unit-makes.store');
    Route::put('/admin/unit-makes/{unitMake}', [UnitMakeController::class, 'update'])->name('admin.unit-makes.update');
    Route::delete('/admin/unit-makes/{unitMake}', [UnitMakeController::class, 'destroy'])->name('admin.unit-makes.destroy');

    // 11. Franchise Management Routes
    // Route::get('/admin/franchises', [FranchiseController::class, 'index'])->name('admin.franchises.index');
    Route::post('/admin/franchises', [FranchiseController::class, 'store'])->name('admin.franchises.store');
    // Route::get('/admin/franchises/{franchise}', [FranchiseController::class, 'show'])->name('admin.franchises.show');

    // 12. Franchise Actions
    Route::post('/admin/franchises/{franchise}/transfer', [FranchiseController::class, 'transferOwnership'])->name('admin.franchises.transfer');
    Route::post('/admin/franchises/{franchise}/change-unit', [FranchiseController::class, 'changeUnit'])->name('admin.franchises.change-unit');

    // 13. Driver Assignment Routes
    
    

    // 14. Complaint Route
    Route::get('/admin/complaints', [ComplaintController::class, 'index'])->name('admin.complaints.index');
    Route::patch('/admin/complaints/{complaint}/resolve', [FranchiseController::class, 'resolveComplaint'])->name('admin.complaints.resolve');

    // 15. Red Flags Routes
    Route::get('/admin/red-flags', [RedFlagController::class, 'index'])->name('admin.red-flags.index');
    Route::post('/admin/red-flags/nature', [RedFlagController::class, 'storeNature'])->name('admin.red-flags.nature.store');
    Route::delete('/admin/red-flags/nature/{nature}', [RedFlagController::class, 'destroyNature'])->name('admin.red-flags.nature.destroy');
    Route::patch('/admin/red-flags/{redFlag}/resolve', [RedFlagController::class, 'resolve'])->name('admin.red-flags.resolve');

    Route::post('/admin/complaints/nature', [ComplaintController::class, 'storeNature'])->name('admin.complaints.nature.store');
    Route::delete('/admin/complaints/nature/{nature}', [ComplaintController::class, 'destroyNature'])->name('admin.complaints.nature.destroy');

    // Application Index
    // Route::get('/admin/applications', [AdminApplicationController::class, 'index'])->name('admin.applications.index');

    // Requirements Management
    Route::post('/admin/applications/requirements', [AdminApplicationController::class, 'storeRequirement'])->name('admin.requirements.store');
    Route::delete('/admin/applications/requirements/{type}/{id}', [AdminApplicationController::class, 'destroyRequirement'])->name('admin.requirements.destroy');

    // Route::get('/applications/{id}', [ApplicationShowController::class, 'show'])->name('admin.applications.show');
    Route::post('/applications/{id}/evaluate', [ApplicationShowController::class, 'updateEvaluation'])->name('admin.applications.evaluate');
    Route::post('/applications/{id}/return', [ApplicationShowController::class, 'returnApplication'])->name('admin.applications.return');
    Route::post('/applications/{id}/reject', [ApplicationShowController::class, 'rejectApplication'])->name('admin.applications.reject');
    Route::post('/applications/{id}/approve', [ApplicationShowController::class, 'approveApplication'])->name('admin.applications.approve');
    Route::post('/applications/{id}/finalize', [ApplicationShowController::class, 'finalizeAccount'])->name('admin.applications.finalize');

    // CHANGE OF UNIT SHOW ROUTES
    Route::get('/applications/change-of-unit/{application}', [ApplicationChangeOfUnitShowController::class, 'show'])
        ->name('admin.applications.show-change-of-unit');
    Route::post('/applications/change-of-unit/{application}/evaluate', [ApplicationChangeOfUnitShowController::class, 'updateEvaluation'])
        ->name('admin.applications.change-of-unit.evaluate');
    Route::post('/applications/change-of-unit/{application}/inspect', [ApplicationChangeOfUnitShowController::class, 'updateInspection'])
        ->name('admin.applications.change-of-unit.inspect');
    Route::post('/applications/change-of-unit/{application}/approve', [ApplicationChangeOfUnitShowController::class, 'approveApplication'])
        ->name('admin.applications.change-of-unit.approve');
    Route::post('/applications/change-of-unit/{application}/reject', [ApplicationChangeOfUnitShowController::class, 'rejectApplication'])
        ->name('admin.applications.change-of-unit.reject');
    Route::post('/applications/change-of-unit/{application}/return', [ApplicationChangeOfUnitShowController::class, 'returnApplication'])
        ->name('admin.applications.change-of-unit.return');
    Route::post('/applications/change-of-unit/{application}/finalize', [ApplicationChangeOfUnitShowController::class, 'finalizeApplication'])
    ->name('admin.applications.change-of-unit.finalize');

    // NEW: CHANGE OF OWNER SHOW ROUTES
    Route::get('/applications/change-of-owner/{application}', [ApplicationChangeOfOwnerShowController::class, 'show'])->name('admin.applications.show-change-of-owner');
    Route::post('/applications/change-of-owner/{application}/evaluate', [ApplicationChangeOfOwnerShowController::class, 'updateEvaluation'])->name('admin.applications.change-of-owner.evaluate');
    Route::post('/applications/change-of-owner/{application}/approve', [ApplicationChangeOfOwnerShowController::class, 'approveApplication'])->name('admin.applications.change-of-owner.approve');
    Route::post('/applications/change-of-owner/{application}/reject', [ApplicationChangeOfOwnerShowController::class, 'rejectApplication'])->name('admin.applications.change-of-owner.reject');
    Route::post('/applications/change-of-owner/{application}/return', [ApplicationChangeOfOwnerShowController::class, 'returnApplication'])->name('admin.applications.change-of-owner.return');
    Route::post('/applications/change-of-owner/{application}/finalize', [ApplicationChangeOfOwnerShowController::class, 'finalizeApplication'])->name('admin.applications.change-of-owner.finalize');

    // NEW: RENEWAL SHOW ROUTES
    Route::get('/applications/renewal/{application}', [ApplicationRenewalShowController::class, 'show'])->name('admin.applications.show-renewal');
    Route::post('/applications/renewal/{application}/evaluate', [ApplicationRenewalShowController::class, 'updateEvaluation'])->name('admin.applications.renewal.evaluate');
    Route::post('/applications/renewal/{application}/inspect', [ApplicationRenewalShowController::class, 'updateInspection'])->name('admin.applications.renewal.inspect');
    Route::post('/applications/renewal/{application}/approve', [ApplicationRenewalShowController::class, 'approveApplication'])->name('admin.applications.renewal.approve');
    Route::post('/applications/renewal/{application}/reject', [ApplicationRenewalShowController::class, 'rejectApplication'])->name('admin.applications.renewal.reject');
    Route::post('/applications/renewal/{application}/return', [ApplicationRenewalShowController::class, 'returnApplication'])->name('admin.applications.renewal.return');
    Route::post('/applications/renewal/{application}/finalize', [ApplicationRenewalShowController::class, 'finalizeApplication'])->name('admin.applications.renewal.finalize');

    Route::patch('/applications/renewal/{application}/complaints/{complaint}/resolve', [ApplicationRenewalShowController::class, 'resolveComplaint'])->name('admin.applications.renewal.resolve-complaint');
    Route::patch('/applications/renewal/{application}/red-flags/{redFlag}/resolve', [ApplicationRenewalShowController::class, 'resolveRedFlag'])->name('admin.applications.renewal.resolve-red-flag');

    Route::get('/admin/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/admin/settings', [SettingsController::class, 'update'])->name('admin.settings.update');

});

// --- FRANCHISE OWNER ROUTES ---
Route::middleware(['auth', 'role:franchise_owner'])->group(function () {
    Route::get('/franchise/dashboard', [DashboardController::class, 'index'])->name('franchise.dashboard');
    Route::post('/franchise/{franchise}/set-driver', [DashboardController::class, 'setActiveDriver'])->name('franchise.set-driver');
    
    // Applications
    Route::get('/franchise/applications', [FranchiseApplicationController::class, 'index'])->name('franchise.make-application');
    Route::post('/franchise/applications/change-unit', [FranchiseApplicationController::class, 'storeChangeOfUnit'])->name('franchise.applications.store-change-unit');
    Route::post('/franchise/applications/change-owner', [FranchiseApplicationController::class, 'storeChangeOfOwner'])->name('franchise.applications.store-change-owner');
    Route::post('/franchise/applications/{application}/submit-renewal-documents', [FranchiseApplicationController::class, 'submitRenewalDocuments'])->name('franchise.applications.submit-renewal-documents');
    
    // NEW: Application Resubmit/Comply Route
    Route::post('/franchise/applications/{application}/resubmit', [FranchiseApplicationController::class, 'resubmitApplication'])->name('franchise.applications.resubmit');

    // NEW: Cancel Application Route
    Route::post('/franchise/applications/{application}/cancel', [FranchiseApplicationController::class, 'cancelApplication'])->name('franchise.applications.cancel');

    Route::post('/franchise/applications/{application}/resubmit-inspection', [FranchiseApplicationController::class, 'resubmitForInspection'])->name('franchise.applications.resubmit-inspection');
});

// --- PROFILE MANAGEMENT ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- CITY ANTI-POLLUTION OFFICER (CAPO) ROUTES ---
Route::middleware(['auth', 'role:city_anti_pollution_officer'])->group(function () {
    Route::get('/capo/applications', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'index'])->name('capo.applications.index');
    
    // View Routes
    Route::get('/capo/applications/renewal/{application}', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'showRenewal'])->name('capo.applications.show');
    Route::get('/capo/applications/change-of-unit/{application}', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'showChangeOfUnit'])->name('capo.applications.show-change-of-unit');

    // Shared Action Routes (Used by both Renewal and Change of Unit)
    Route::post('/capo/applications/{application}/approve', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'approve'])
        ->name('capo.applications.approve');
    Route::post('/capo/applications/{application}/reject', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'reject'])
        ->name('capo.applications.reject');
});

// --- EVALUATOR ROUTES ---
Route::middleware(['auth', 'role:evaluator'])->group(function () {
    Route::get('/evaluator/applications', [EvaluatorApplicationController::class, 'index'])->name('evaluator.applications.index');
    
    // Application Specific Show Routes
    Route::get('/evaluator/applications/renewal/{application}', [EvaluatorApplicationController::class, 'showRenewal'])->name('evaluator.applications.show'); // Kept original name for backward compatibility
    Route::get('/evaluator/applications/change-of-owner/{application}', [EvaluatorApplicationController::class, 'showChangeOfOwner'])->name('evaluator.applications.show-change-of-owner');
    Route::get('/evaluator/applications/change-of-unit/{application}', [EvaluatorApplicationController::class, 'showChangeOfUnit'])->name('evaluator.applications.show-change-of-unit');
    Route::get('/evaluator/applications/new-franchise/{application}', [EvaluatorApplicationController::class, 'showFranchiseOwnerAccount'])->name('evaluator.applications.show-new-franchise');

    // Generic Action Routes
    Route::post('/evaluator/applications/{application}/approve', [EvaluatorApplicationController::class, 'approve'])
        ->name('evaluator.applications.approve');
    Route::post('/evaluator/applications/{application}/reject', [EvaluatorApplicationController::class, 'reject'])
        ->name('evaluator.applications.reject');
    Route::post('/evaluator/applications/{application}/return', [EvaluatorApplicationController::class, 'returnApp'])
        ->name('evaluator.applications.return');
    Route::post('/evaluator/applications/{application}/evaluate', [EvaluatorApplicationController::class, 'evaluateDocument'])
        ->name('evaluator.applications.evaluate');

    // Resolve Complaints and Red Flags
    Route::post('/evaluator/applications/{application}/complaints/{complaint}/resolve', [EvaluatorApplicationController::class, 'resolveComplaint'])
        ->name('evaluator.applications.resolve-complaint');
    Route::post('/evaluator/applications/{application}/red-flags/{red_flag}/resolve', [EvaluatorApplicationController::class, 'resolveRedFlag'])
        ->name('evaluator.applications.resolve-red-flag');
});

// --- INSPECTOR ROUTES ---
Route::middleware(['auth', 'role:inspector'])->group(function () {
    Route::get('/inspector/applications', [InspectorApplicationController::class, 'index'])->name('inspector.applications.index');
    
    // View Routes
    Route::get('/inspector/applications/renewal/{application}', [InspectorApplicationController::class, 'showRenewal'])->name('inspector.applications.show');
    Route::get('/inspector/applications/change-of-unit/{application}', [InspectorApplicationController::class, 'showChangeOfUnit'])->name('inspector.applications.show-change-of-unit');

    // Shared Action Routes (Used by both Renewal and Change of Unit)
    Route::post('/inspector/applications/{application}/approve', [InspectorApplicationController::class, 'approve'])
        ->name('inspector.applications.approve');
    Route::post('/inspector/applications/{application}/reject', [InspectorApplicationController::class, 'reject'])
        ->name('inspector.applications.reject');
    Route::post('/inspector/applications/{application}/inspect', [InspectorApplicationController::class, 'inspectUnit'])
        ->name('inspector.applications.inspect');
});

// --- REVIEWER ROUTES ---
Route::middleware(['auth', 'role:reviewer'])->prefix('reviewer')->name('reviewer.')->group(function () {
    Route::get('/applications', [ReviewerApplicationController::class, 'index'])->name('applications.index');
    
    // Split the show routes based on application type
    Route::get('/applications/renewal/{application}', [ReviewerApplicationController::class, 'showRenewal'])->name('applications.showRenewal');
    Route::get('/applications/change-of-unit/{application}', [ReviewerApplicationController::class, 'showChangeOfUnit'])->name('applications.showChangeOfUnit');
    Route::get('/applications/change-of-owner/{application}', [ReviewerApplicationController::class, 'showChangeOfOwner'])->name('applications.showChangeOfOwner');
    
    Route::post('/applications/{application}/approve', [ReviewerApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [ReviewerApplicationController::class, 'reject'])->name('applications.reject');
});

// --- SP APPROVER ROUTES ---
Route::middleware(['auth', 'role:sp_approver'])->group(function () {
    Route::get('/sp-approver/applications', [SpApproverApplicationController::class, 'index'])->name('sp_approver.applications.index');
    Route::get('/sp-approver/applications/renewal/{application}', [SpApproverApplicationController::class, 'showRenewal'])->name('sp_approver.applications.show');

    Route::post('/sp-approver/applications/renewal/{application}/approve', [SpApproverApplicationController::class, 'approve'])
        ->name('sp_approver.applications.renewal.approve');
    Route::post('/sp-approver/applications/renewal/{application}/reject', [SpApproverApplicationController::class, 'reject'])
        ->name('sp_approver.applications.renewal.reject');
});

// --- TAB APPROVER ROUTES ---
Route::middleware(['auth', 'role:tab_approver'])->prefix('tab-approver')->name('tab_approver.')->group(function () {
    Route::get('/applications', [TabApproverApplicationController::class, 'index'])->name('applications.index');
    
    // Split the show routes based on application type
    Route::get('/applications/renewal/{application}', [TabApproverApplicationController::class, 'showRenewal'])->name('applications.showRenewal');
    Route::get('/applications/change-of-unit/{application}', [TabApproverApplicationController::class, 'showChangeOfUnit'])->name('applications.showChangeOfUnit');
    Route::get('/applications/change-of-owner/{application}', [TabApproverApplicationController::class, 'showChangeOfOwner'])->name('applications.showChangeOfOwner');
    
    Route::post('/applications/{application}/approve', [TabApproverApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [TabApproverApplicationController::class, 'reject'])->name('applications.reject');
});

// --- SHARED APPLICATION ROUTES (Admin & Encoder) ---
Route::middleware(['auth', 'role:admin,encoder'])->group(function () {
    // We will use the 'admin.' prefix for the route names to prevent needing to rewrite all your Vue links,
    // but the URLs will simply be /applications/...
    
    Route::get('/applications', [AdminApplicationController::class, 'index'])->name('admin.applications.index');
    
    // New Franchise (Franchise Owner Account)
    Route::get('/applications/new-franchise/{application}', [ApplicationShowController::class, 'show'])->name('admin.applications.show');
    Route::post('/applications/new-franchise/{application}/evaluate', [ApplicationShowController::class, 'updateEvaluation'])->name('admin.applications.evaluate');
    Route::post('/applications/new-franchise/{application}/finalize', [ApplicationShowController::class, 'finalizeAccount'])->name('admin.applications.finalize');

    // Renewal
    Route::get('/applications/renewal/{application}', [ApplicationRenewalShowController::class, 'show'])->name('admin.applications.renewal.show');
    Route::post('/applications/renewal/{application}/finalize', [ApplicationRenewalShowController::class, 'finalizeApplication'])->name('admin.applications.renewal.finalize');

    // Change of Owner
    Route::get('/applications/change-of-owner/{application}', [ApplicationChangeOfOwnerShowController::class, 'show'])->name('admin.applications.change-of-owner.show');
    Route::post('/applications/change-of-owner/{application}/finalize', [ApplicationChangeOfOwnerShowController::class, 'finalizeApplication'])->name('admin.applications.change-of-owner.finalize');

    // Change of Unit
    Route::get('/applications/change-of-unit/{application}', [ApplicationChangeOfUnitShowController::class, 'show'])->name('admin.applications.change-of-unit.show');
    Route::post('/applications/change-of-unit/{application}/finalize', [ApplicationChangeOfUnitShowController::class, 'finalizeApplication'])->name('admin.applications.change-of-unit.finalize');

    Route::get('admin/drivers', [DriverController::class, 'index'])->name('admin.drivers.index');
    Route::get('admin/drivers/{driver}', [DriverController::class, 'show'])->name('admin.drivers.show');
});

// --- SHARED ROUTES: Admin & Collector ---
// Both can view the index pages for payments and assessments
Route::middleware(['auth', 'role:admin,collector'])->group(function () {
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('admin.assessments.index');
});

// --- COLLECTOR ONLY ROUTES ---
// Only collectors have full CRUD access (store, update, destroy)
Route::middleware(['auth', 'role:collector'])->group(function () {
    // Payment CRUD
    Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');
    // If you add update/destroy for payments in the future, add them here.
    
    // Assessment CRUD
    Route::post('/assessments', [AssessmentController::class, 'store'])->name('admin.assessments.store');
    // If you add update/destroy for assessments in the future, add them here.

    // 8. Particulars (Fee Types) Routes
    Route::post('/particulars', [ParticularController::class, 'store'])->name('admin.particulars.store');
    Route::put('/particulars/{particular}', [ParticularController::class, 'update'])->name('admin.particulars.update');
    Route::delete('/particulars/{particular}', [ParticularController::class, 'destroy'])->name('admin.particulars.destroy');
});

// --- SHARED ROUTES: Admin & Releaser ---
// Both can view the franchise show page
Route::middleware(['auth', 'role:admin,releaser,encoder'])->group(function () {
    Route::get('/admin/franchises/{franchise}', [FranchiseController::class, 'show'])->name('admin.franchises.show');
    Route::get('/admin/franchises', [FranchiseController::class, 'index'])->name('admin.franchises.index');
});

// --- ENCODER ONLY ROUTES ---
// Only encoders can create, store, edit, update, and delete drivers
Route::middleware(['auth', 'role:encoder'])->group(function () {
    Route::get('admin/drivers/create', [DriverController::class, 'create'])->name('admin.drivers.create');
    Route::post('admin/drivers', [DriverController::class, 'store'])->name('admin.drivers.store');
    Route::get('admin/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('admin.drivers.edit');
    Route::put('admin/drivers/{driver}', [DriverController::class, 'update'])->name('admin.drivers.update');
    Route::delete('admin/drivers/{driver}', [DriverController::class, 'destroy'])->name('admin.drivers.destroy');
    Route::post('/admin/franchises/{franchise}/red-flags', [RedFlagController::class, 'store'])->name('admin.franchises.red-flags.store');
    Route::post('/admin/franchises/{franchise}/complaints', [FranchiseController::class, 'storeComplaint'])->name('admin.franchises.complaints.store');
    Route::delete('/admin/franchises/{franchise}/drivers/{assignment}', [FranchiseController::class, 'removeDriver'])->name('admin.franchises.remove-driver');
    Route::post('/admin/franchises/{franchise}/drivers', [FranchiseController::class, 'assignDriver'])->name('admin.franchises.assign-driver');
    Route::patch('/admin/red-flags/{redFlag}/resolve', [RedFlagController::class, 'resolve'])->name('admin.red-flags.resolve');
    Route::patch('/admin/complaints/{complaint}/resolve', [FranchiseController::class, 'resolveComplaint'])->name('admin.complaints.resolve');
});

require __DIR__.'/auth.php';