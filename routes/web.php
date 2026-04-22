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
use App\Http\Controllers\Admin\ApplicationNewDriverShowController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Evaluator\EvaluatorApplicationController;
use App\Http\Controllers\Inspector\InspectorApplicationController;
use App\Http\Controllers\Capo\CapoApplicationController;
use App\Http\Controllers\Reviewer\ReviewerApplicationController;
use App\Http\Controllers\SpApprover\SpApproverApplicationController;
use App\Http\Controllers\TabApprover\TabApproverApplicationController;
use App\Http\Controllers\Encoder\EncoderApplicationController;
use App\Http\Controllers\Public\NewFranchiseController;
use App\Http\Controllers\Admin\ApplicationNewFranchiseShowController;
use App\Http\Controllers\Public\ApplicationCompletionController;
use App\Http\Controllers\CertificateTemplateController;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use App\Models\Franchise;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

// Add this outside of your auth middleware (so the applicant can access it via email)
Route::get('/application/{application}/complete', [ApplicationCompletionController::class, 'edit'])
    ->name('application.complete')
    ->middleware('signed'); // Secures the route using Laravel's signature hash

Route::post('/application/{application}/complete', [ApplicationCompletionController::class, 'update'])
    ->name('application.complete.submit')
    ->middleware('signed');

Route::post('/apply/send-otp', [ApplicationController::class, 'sendOtp'])->name('application.send-otp');
Route::post('/apply/verify-otp', [ApplicationController::class, 'verifyOtp'])->name('application.verify-otp');  

// --- THE NEW TRAFFIC DIRECTOR ---
Route::get('/dashboard', function () {
    $user = Auth::user();
    $role = $user->role instanceof UserRole ? $user->role->value : $user->role;

    return match ($role) {
        UserRole::ADMIN->value => redirect()->route('admin.dashboard'),
        UserRole::FRANCHISE_OWNER->value => redirect()->route('franchise.dashboard'),
        UserRole::COLLECTOR->value => redirect()->route('admin.payments.index'),
        UserRole::EVALUATOR->value => redirect()->route('evaluator.applications.index'),
        UserRole::INSPECTOR->value => redirect()->route('inspector.applications.index'),
        UserRole::CITY_ANTI_POLLUTION_OFFICER->value => redirect()->route('capo.applications.index'),
        UserRole::REVIEWER->value => redirect()->route('reviewer.applications.index'),
        UserRole::SP_APPROVER->value => redirect()->route('sp_approver.applications.index'),
        UserRole::TAB_APPROVER->value => redirect()->route('tab_approver.applications.index'),
        UserRole::RELEASER->value => redirect()->route('admin.franchises.index'),
        UserRole::ENCODER->value => redirect()->route('admin.applications.index'),
        default => Inertia::render('Dashboard'), // Fallback if a role has no specific route
    };
})->middleware(['auth', 'verified', 'prevent-back-history'])->name('dashboard');

Route::get('/', function () {
    return Inertia::render('Index', [
        'renewedFranchisesSum' => \App\Models\Franchise::where('status', 'Renewed')
            ->count(), 
    ]);
})->name('home');

// Public Route for New Franchise Applications
Route::get('/new-franchise', [NewFranchiseController::class, 'create'])->name('new-franchise.create');
Route::post('/new-franchise', [NewFranchiseController::class, 'store'])->name('new-franchise.store');
Route::post('/new-franchise/send-otp', [NewFranchiseController::class, 'sendOtp'])->name('new-franchise.send-otp');
Route::post('/new-franchise/verify-otp', [NewFranchiseController::class, 'verifyOtp'])->name('new-franchise.verify-otp');

Route::get('/apply', [ApplicationController::class, 'create'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store'])->name('application.store');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/ordinances', function () {
    return Inertia::render('Ordinances');
})->name('ordinances');

Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    return Inertia::render('TermsOfService');
})->name('terms-of-service');

// The Verification Page (Scanner)
Route::get('/verify', [FranchiseController::class, 'verify'])->name('verify');
Route::post('/verify/lookup', [FranchiseController::class, 'lookup'])->name('verify.lookup');

// NEW: Public Franchise Detail View
Route::get('/franchise-check/{id}', [FranchiseController::class, 'publicShow'])->name('franchises.public_show');

Route::post('/complaints/report', [ComplaintController::class, 'store'])->name('complaints.store');

// --- ADMIN ROUTES ---
Route::middleware(['auth', 'prevent-back-history', 'role:admin'])->group(function () {
    Route::post('/users/{user}/temporary-roles', [\App\Http\Controllers\Admin\UserController::class, 'assignTemporaryRole'])->name('admin.users.temp-roles.store');
    Route::delete('/users/{user}/temporary-roles/{role}', [\App\Http\Controllers\Admin\UserController::class, 'revokeTemporaryRole'])->name('admin.users.temp-roles.destroy');

    Route::post('/admin/applications', [AdminApplicationController::class, 'store'])
            ->name('admin.applications.store');

    Route::get('/dashboard/report/download', [AdminDashboardController::class, 'downloadReport'])->name('admin.dashboard.report.download');
    
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

    // 9. Units Routes
    Route::get('/admin/units', [UnitController::class, 'index'])->name('admin.units.index');
    Route::post('/admin/units', [UnitController::class, 'store'])->name('admin.units.store');
    Route::put('/admin/units/{unit}', [UnitController::class, 'update'])->name('admin.units.update');

    // 10. Unit Makes (Brands) Routes
    Route::post('/admin/unit-makes', [UnitMakeController::class, 'store'])->name('admin.unit-makes.store');
    Route::put('/admin/unit-makes/{unitMake}', [UnitMakeController::class, 'update'])->name('admin.unit-makes.update');
    Route::delete('/admin/unit-makes/{unitMake}', [UnitMakeController::class, 'destroy'])->name('admin.unit-makes.destroy');

    // 11. Franchise Management Routes
    Route::post('/admin/franchises', [FranchiseController::class, 'store'])->name('admin.franchises.store');

    // 12. Franchise Actions
    Route::post('/admin/franchises/{franchise}/transfer', [FranchiseController::class, 'transferOwnership'])->name('admin.franchises.transfer');
    Route::post('/admin/franchises/{franchise}/change-unit', [FranchiseController::class, 'changeUnit'])->name('admin.franchises.change-unit');

    // 14. Complaint Route
    Route::get('/admin/complaints', [ComplaintController::class, 'index'])->name('admin.complaints.index');
    // Application Logs
    Route::get('/admin/application-logs', [\App\Http\Controllers\Admin\ApplicationLogController::class, 'index'])->name('admin.application-logs.index');
    Route::patch('/admin/complaints/{complaint}/resolve', [FranchiseController::class, 'resolveComplaint'])->name('admin.complaints.resolve');

    // 15. Red Flags Routes
    Route::get('/admin/red-flags', [RedFlagController::class, 'index'])->name('admin.red-flags.index');
    Route::post('/admin/red-flags/nature', [RedFlagController::class, 'storeNature'])->name('admin.red-flags.nature.store');
    Route::delete('/admin/red-flags/nature/{nature}', [RedFlagController::class, 'destroyNature'])->name('admin.red-flags.nature.destroy');
    Route::patch('/admin/red-flags/{redFlag}/resolve', [RedFlagController::class, 'resolve'])->name('admin.red-flags.resolve');

    Route::post('/admin/complaints/nature', [ComplaintController::class, 'storeNature'])->name('admin.complaints.nature.store');
    Route::delete('/admin/complaints/nature/{nature}', [ComplaintController::class, 'destroyNature'])->name('admin.complaints.nature.destroy');

    // Requirements Management
    Route::post('/admin/applications/requirements', [AdminApplicationController::class, 'storeRequirement'])->name('admin.requirements.store');
    Route::delete('/admin/applications/requirements/{type}/{id}', [AdminApplicationController::class, 'destroyRequirement'])->name('admin.requirements.destroy');

    Route::post('/applications/{id}/evaluate', [ApplicationShowController::class, 'updateEvaluation'])->name('admin.applications.evaluate');
    Route::post('/applications/{id}/return', [ApplicationShowController::class, 'returnApplication'])->name('admin.applications.return');
    Route::post('/applications/{id}/reject', [ApplicationShowController::class, 'rejectApplication'])->name('admin.applications.reject');
    Route::post('/applications/{id}/approve', [ApplicationShowController::class, 'approveApplication'])->name('admin.applications.approve');

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
Route::middleware(['auth', 'prevent-back-history', 'role:franchise_owner'])->group(function () {
    Route::get('/franchise/dashboard', [DashboardController::class, 'index'])->name('franchise.dashboard');
    Route::post('/franchise/{franchise}/set-driver', [DashboardController::class, 'setActiveDriver'])->name('franchise.set-driver');
    Route::post('/franchise/{franchise}/deactivate-driver', [DashboardController::class, 'deactivateDriver'])->name('franchise.deactivate-driver');
    Route::post('/franchise/{franchise}/drivers/{assignment}/schedule', [DashboardController::class, 'updateDriverSchedule'])->name('franchise.drivers.schedule');
    
    // Applications
    Route::get('/franchise/applications', [FranchiseApplicationController::class, 'index'])->name('franchise.make-application');
    Route::post('/franchise/applications/renewal', [FranchiseApplicationController::class, 'storeRenewal'])->name('franchise.applications.store-renewal');
    Route::post('/franchise/applications/change-unit', [FranchiseApplicationController::class, 'storeChangeOfUnit'])->name('franchise.applications.store-change-unit');
    Route::post('/franchise/applications/change-owner', [FranchiseApplicationController::class, 'storeChangeOfOwner'])->name('franchise.applications.store-change-owner');
    Route::post('/franchise/applications/{application}/submit-renewal-documents', [FranchiseApplicationController::class, 'submitRenewalDocuments'])->name('franchise.applications.submit-renewal-documents');
    
    // NEW: Application Resubmit/Comply Route
    Route::post('/franchise/applications/{application}/resubmit', [FranchiseApplicationController::class, 'resubmitApplication'])->name('franchise.applications.resubmit');

    // NEW: Cancel Application Route
    Route::post('/franchise/applications/{application}/cancel', [FranchiseApplicationController::class, 'cancelApplication'])->name('franchise.applications.cancel');

    Route::post('/franchise/applications/{application}/resubmit-inspection', [FranchiseApplicationController::class, 'resubmitForInspection'])->name('franchise.applications.resubmit-inspection');

    Route::post('/franchise/applications/new-driver', [FranchiseApplicationController::class, 'storeNewDriver'])->name('franchise.applications.store-new-driver');
    Route::post('/franchise/applications/new-franchise', [FranchiseApplicationController::class, 'storeNewFranchise'])->name('franchise.applications.store-new-franchise');
});

// --- PROFILE MANAGEMENT & USER ACTIONS ---
Route::middleware(['auth', 'prevent-back-history'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ADD THESE TWO NEW ROUTES:
    Route::get('/force-password-change', [\App\Http\Controllers\Auth\ForcePasswordChangeController::class, 'create'])->name('password.force-change');
    Route::post('/force-password-change', [\App\Http\Controllers\Auth\ForcePasswordChangeController::class, 'store'])->name('password.force-change.store');

    // NOTIFICATIONS ROUTE
    Route::post('/notifications/{id}/read', function (Request $request, $id) {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return back(); // Inertia will handle the state update automatically
    })->name('notifications.read');
});

// --- CITY ANTI-POLLUTION OFFICER (CAPO) ROUTES ---
Route::middleware(['auth', 'prevent-back-history', 'role:city_anti_pollution_officer'])->group(function () {
    Route::get('/capo/applications', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'index'])->name('capo.applications.index');
    
    // View Routes
    Route::get('/capo/applications/renewal/{application}', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'showRenewal'])->name('capo.applications.show');
    Route::get('/capo/applications/change-of-unit/{application}', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'showChangeOfUnit'])->name('capo.applications.show-change-of-unit');
    Route::get('/capo/applications/new-franchise/{application}', [CapoApplicationController::class, 'showNewFranchise'])
    ->name('capo.applications.show-new-franchise');

    // Shared Action Routes (Used by both Renewal and Change of Unit)
    Route::post('/capo/applications/{application}/approve', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'approve'])
        ->name('capo.applications.approve');
    Route::post('/capo/applications/{application}/reject', [\App\Http\Controllers\Capo\CapoApplicationController::class, 'reject'])
        ->name('capo.applications.reject');
});

// --- EVALUATOR ROUTES ---
Route::middleware(['auth', 'prevent-back-history', 'role:evaluator'])->group(function () {
    Route::get('/evaluator/applications', [EvaluatorApplicationController::class, 'index'])->name('evaluator.applications.index');
    
    // Application Specific Show Routes
    Route::get('/evaluator/applications/renewal/{application}', [EvaluatorApplicationController::class, 'showRenewal'])->name('evaluator.applications.show'); // Kept original name for backward compatibility
    Route::get('/evaluator/applications/change-of-owner/{application}', [EvaluatorApplicationController::class, 'showChangeOfOwner'])->name('evaluator.applications.show-change-of-owner');
    Route::get('/evaluator/applications/change-of-unit/{application}', [EvaluatorApplicationController::class, 'showChangeOfUnit'])->name('evaluator.applications.show-change-of-unit');
    Route::get('/evaluator/applications/franchise-owner-account/{application}', [EvaluatorApplicationController::class, 'showFranchiseOwnerAccount'])
    ->name('evaluator.applications.show-franchise-owner-account');
    Route::get('/evaluator/applications/new-franchise/{application}', [EvaluatorApplicationController::class, 'showNewFranchise'])
    ->name('evaluator.applications.show-new-franchise');

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
    Route::get('/evaluator/applications/new-driver/{application}', [\App\Http\Controllers\Evaluator\EvaluatorApplicationController::class, 'showNewDriver'])
    ->name('evaluator.applications.show-new-driver');
});

// --- INSPECTOR ROUTES ---
Route::middleware(['auth', 'prevent-back-history', 'role:inspector'])->group(function () {
    Route::get('/inspector/applications', [InspectorApplicationController::class, 'index'])->name('inspector.applications.index');
    
    // View Routes
    Route::get('/inspector/applications/renewal/{application}', [InspectorApplicationController::class, 'showRenewal'])->name('inspector.applications.show');
    Route::get('/inspector/applications/change-of-unit/{application}', [InspectorApplicationController::class, 'showChangeOfUnit'])->name('inspector.applications.show-change-of-unit');
    Route::get('/inspector/applications/new-franchise/{application}', [InspectorApplicationController::class, 'showNewFranchise'])
    ->name('inspector.applications.show-new-franchise');
    // Shared Action Routes (Used by both Renewal and Change of Unit)
    Route::post('/inspector/applications/{application}/approve', [InspectorApplicationController::class, 'approve'])
        ->name('inspector.applications.approve');
    Route::post('/inspector/applications/{application}/reject', [InspectorApplicationController::class, 'reject'])
        ->name('inspector.applications.reject');
    Route::post('/inspector/applications/{application}/inspect', [InspectorApplicationController::class, 'inspectUnit'])
        ->name('inspector.applications.inspect');
});

// --- REVIEWER ROUTES ---
Route::middleware(['auth', 'prevent-back-history', 'role:reviewer'])->prefix('reviewer')->name('reviewer.')->group(function () {
    Route::get('/applications', [ReviewerApplicationController::class, 'index'])->name('applications.index');
    
    // Split the show routes based on application type
    Route::get('/applications/renewal/{application}', [ReviewerApplicationController::class, 'showRenewal'])->name('applications.showRenewal');
    Route::get('/applications/change-of-unit/{application}', [ReviewerApplicationController::class, 'showChangeOfUnit'])->name('applications.showChangeOfUnit');
    Route::get('/applications/change-of-owner/{application}', [ReviewerApplicationController::class, 'showChangeOfOwner'])->name('applications.showChangeOfOwner');
    Route::get('/applications/new-franchise/{application}', [ReviewerApplicationController::class, 'showNewFranchise'])
    ->name('reviewer.applications.showNewFranchise');
    
    Route::post('/applications/{application}/approve', [ReviewerApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [ReviewerApplicationController::class, 'reject'])->name('applications.reject');
});

// --- SP APPROVER ROUTES ---
Route::middleware(['auth', 'prevent-back-history', 'role:sp_approver'])->group(function () {
    Route::get('/sp-approver/applications', [SpApproverApplicationController::class, 'index'])->name('sp_approver.applications.index');
    Route::get('/sp-approver/applications/renewal/{application}', [SpApproverApplicationController::class, 'showRenewal'])->name('sp_approver.applications.show');
    Route::get('/sp-approver/applications/new-franchise/{application}', [SpApproverApplicationController::class, 'showNewFranchise'])
    ->name('sp_approver.applications.show-new-franchise');

    Route::post('/sp-approver/applications/renewal/{application}/approve', [SpApproverApplicationController::class, 'approve'])
        ->name('sp_approver.applications.renewal.approve');
    Route::post('/sp-approver/applications/renewal/{application}/reject', [SpApproverApplicationController::class, 'reject'])
        ->name('sp_approver.applications.renewal.reject');
});

// --- TAB APPROVER ROUTES ---
Route::middleware(['auth', 'prevent-back-history', 'role:tab_approver'])->prefix('tab-approver')->name('tab_approver.')->group(function () {
    Route::get('/applications', [TabApproverApplicationController::class, 'index'])->name('applications.index');
    
    // Split the show routes based on application type
    Route::get('/applications/renewal/{application}', [TabApproverApplicationController::class, 'showRenewal'])->name('applications.showRenewal');
    Route::get('/applications/change-of-unit/{application}', [TabApproverApplicationController::class, 'showChangeOfUnit'])->name('applications.showChangeOfUnit');
    Route::get('/applications/change-of-owner/{application}', [TabApproverApplicationController::class, 'showChangeOfOwner'])->name('applications.showChangeOfOwner');
    Route::get('/applications/new-franchise/{application}', [App\Http\Controllers\TabApprover\TabApproverApplicationController::class, 'showNewFranchise'])
    ->name('applications.show-new-franchise');
    
    Route::post('/applications/{application}/approve', [TabApproverApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [TabApproverApplicationController::class, 'reject'])->name('applications.reject');
});

// --- SHARED APPLICATION ROUTES (Admin & Encoder) ---
Route::middleware(['auth', 'prevent-back-history', 'role:admin,encoder'])->group(function () {
    
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

    Route::post('/applications/new-franchise/{application}/inspect', [ApplicationNewFranchiseShowController::class, 'updateInspection'])->name('admin.applications.new-franchise.inspect');

    Route::get('/admin/applications/new-franchise/{application}', ApplicationNewFranchiseShowController::class)
    ->name('admin.applications.show-new-franchise');

    Route::patch('/admin/red-flags/{redFlag}/resolve', [RedFlagController::class, 'resolve'])->name('admin.red-flags.resolve');
    Route::patch('/admin/complaints/{complaint}/resolve', [FranchiseController::class, 'resolveComplaint'])->name('admin.complaints.resolve');

    Route::get('admin/drivers/create', [DriverController::class, 'create'])->name('admin.drivers.create');
    Route::post('admin/drivers', [DriverController::class, 'store'])->name('admin.drivers.store');
    Route::get('admin/drivers/{driver}/edit', [DriverController::class, 'edit'])->name('admin.drivers.edit');
    Route::put('admin/drivers/{driver}', [DriverController::class, 'update'])->name('admin.drivers.update');
    Route::delete('admin/drivers/{driver}', [DriverController::class, 'destroy'])->name('admin.drivers.destroy');
    Route::post('/admin/franchises/{franchise}/red-flags', [RedFlagController::class, 'store'])->name('admin.franchises.red-flags.store');
    Route::post('/admin/franchises/{franchise}/complaints', [FranchiseController::class, 'storeComplaint'])->name('admin.franchises.complaints.store');
    Route::delete('/admin/franchises/{franchise}/drivers/{assignment}', [FranchiseController::class, 'removeDriver'])->name('admin.franchises.remove-driver');
    Route::post('/admin/franchises/{franchise}/drivers', [FranchiseController::class, 'assignDriver'])->name('admin.franchises.assign-driver');
    Route::post('/franchises/{franchise}/store-and-assign-driver', [FranchiseController::class, 'storeAndAssignDriver'])
    ->name('franchises.store_and_assign_driver');
    Route::get('/admin/applications/new-driver/{application}', [ApplicationNewDriverShowController::class, 'show'])->name('admin.applications.new-driver.show');
});

// --- PAYMENTS ROUTES (Admin & Collector) ---
Route::middleware(['auth', 'prevent-back-history', 'role:admin,collector'])->group(function () {
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');
});

// Route::middleware(['auth', 'prevent-back-history', 'role:collector'])->group(function () {
    
// });

// --- ASSESSMENTS ROUTES (Admin, Evaluator, Encoder) ---
Route::middleware(['auth', 'prevent-back-history', 'role:admin,evaluator,encoder'])->group(function () {
    Route::get('/assessments', [AssessmentController::class, 'index'])->name('admin.assessments.index');
    Route::post('/assessments', [AssessmentController::class, 'store'])->name('admin.assessments.store');

    Route::get('/admin/assessments-report/pdf', [AssessmentController::class, 'reportPdf'])->name('admin.assessments.report.pdf');
    Route::get('/admin/assessments-report/excel', [AssessmentController::class, 'reportExcel'])->name('admin.assessments.report.excel');

    Route::get('/admin/payments-report/pdf', [PaymentController::class, 'reportPdf'])->name('admin.payments.report.pdf');
    Route::get('/admin/payments-report/excel', [PaymentController::class, 'reportExcel'])->name('admin.payments.report.excel');
});

// --- PARTICULARS ROUTES (Admin Only) ---
Route::middleware(['auth', 'prevent-back-history', 'role:admin'])->group(function () {
    Route::post('/particulars', [ParticularController::class, 'store'])->name('admin.particulars.store');
    Route::post('/particulars/{particular}', [ParticularController::class, 'update'])->name('admin.particulars.update');
    Route::delete('/particulars/{particular}', [ParticularController::class, 'destroy'])->name('admin.particulars.destroy');
});

// --- SHARED ROUTES: Admin & Releaser ---
// Both can view the franchise show page
Route::middleware(['auth', 'prevent-back-history', 'role:admin,releaser,encoder'])->group(function () {
    Route::get('/admin/franchises/{franchise}', [FranchiseController::class, 'show'])->name('admin.franchises.show');
    Route::get('/admin/franchises', [FranchiseController::class, 'index'])->name('admin.franchises.index');

    // NEW: Just two routes for the single template editor
    Route::get('/admin/certificate-template', [CertificateTemplateController::class, 'edit'])->name('certificate-template.edit');
    Route::post('/admin/certificate-template', [CertificateTemplateController::class, 'update'])->name('certificate-template.update');
});

// --- ENCODER ONLY ROUTES ---
// Only encoders can create, store, edit, update, and delete drivers
// Route::middleware(['auth', 'prevent-back-history', 'role:encoder'])->group(function () {

// });

require __DIR__.'/auth.php';