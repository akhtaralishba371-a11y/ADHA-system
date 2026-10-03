<?php

use App\Http\Controllers\AdhdController;
use App\Http\Controllers\Auth\StaffInvitationController;
use App\Http\Controllers\Auth\StaffNewPasswordController;
use App\Http\Controllers\Auth\StaffPasswordResetLinkController;
use App\Http\Controllers\Auth\StaffSessionController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PublicAvailabilityController;
use App\Http\Controllers\PublicAvailabilityDatesController;
use App\Http\Controllers\PublicBookingOptionsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\Staff\AppointmentController;
use App\Http\Controllers\Staff\AppointmentStatusController;
use App\Http\Controllers\Staff\CareTaskController;
use App\Http\Controllers\Staff\ClinicianAvailabilityController;
use App\Http\Controllers\Staff\ClinicianController;
use App\Http\Controllers\Staff\ClinicianOnboardingController;
use App\Http\Controllers\Staff\ClinicianTimeOffController;
use App\Http\Controllers\Staff\DashboardController;
use App\Http\Controllers\Staff\PatientController;
use App\Http\Controllers\Staff\ServiceController;
use App\Http\Controllers\Staff\StaffAccountController;
use App\Http\Controllers\Staff\StaffAvailabilityController;
use App\Http\Controllers\Staff\SystemSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdhdController::class, 'index'])->name('adhd.home');
Route::get('/clinicians', [AdhdController::class, 'clinicians'])->name('adhd.clinicians');
Route::get('/how-it-works', [AdhdController::class, 'howItWorks'])->name('adhd.how-it-works');
Route::get('/booking.js', [AdhdController::class, 'bookingScripts'])->name('adhd.booking.scripts');
Route::get('/care-workspace/styles.css', [AdhdController::class, 'careWorkspaceStyles'])->name('adhd.crm.styles');
Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:public-booking')->name('bookings.store');
Route::get('/booking-options', PublicBookingOptionsController::class)->name('bookings.options');
Route::get('/availability', PublicAvailabilityController::class)->middleware('throttle:public-availability')->name('availability.index');
Route::get('/availability/dates', PublicAvailabilityDatesController::class)->middleware('throttle:public-availability')->name('availability.dates');

Route::middleware(['setup.available', 'throttle:setup'])->group(function (): void {
    Route::get('/setup', [SetupController::class, 'create'])->name('setup.create');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/staff/login', [StaffSessionController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [StaffSessionController::class, 'store'])->middleware('throttle:staff-login')->name('staff.login.store');
    Route::get('/staff/forgot-password', [StaffPasswordResetLinkController::class, 'create'])->name('staff.password.request');
    Route::post('/staff/forgot-password', [StaffPasswordResetLinkController::class, 'store'])->middleware('throttle:staff-login')->name('staff.password.email');
    Route::get('/staff/reset-password/{token}', [StaffNewPasswordController::class, 'create'])->middleware('signed')->name('staff.password.reset');
    Route::post('/staff/reset-password', [StaffNewPasswordController::class, 'store'])->middleware('throttle:staff-login')->name('staff.password.update');
    Route::get('/staff/invitation/{token}', [StaffInvitationController::class, 'create'])->middleware('signed')->name('staff.invitation.show');
    Route::post('/staff/invitation', [StaffInvitationController::class, 'store'])->middleware('throttle:staff-login')->name('staff.invitation.store');
});

Route::middleware(['auth', 'staff'])->group(function (): void {
    Route::post('/staff/logout', [StaffSessionController::class, 'destroy'])->name('staff.logout');
    Route::get('/care-workspace', function () {
        return redirect()->to(auth()->user()->panelRoute());
    })->name('adhd.crm');
    Route::get('/care-workspace/script.js', [AdhdController::class, 'careWorkspaceScripts'])->name('adhd.crm.scripts');
    Route::get('/care-workspace/assets/{filename}', [AdhdController::class, 'careWorkspaceAsset'])->name('adhd.crm.assets');
});

Route::middleware(['auth', 'staff', 'admin.panel'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdhdController::class, 'adminWorkspace'])->name('panel');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/clinician-onboardings', ClinicianOnboardingController::class)->name('clinician-onboardings.store');
    Route::apiResource('clinicians', ClinicianController::class);
    Route::get('/clinicians/{clinician}/availabilities', [ClinicianAvailabilityController::class, 'index'])->name('clinicians.availabilities.index');
    Route::post('/clinicians/{clinician}/availabilities', [ClinicianAvailabilityController::class, 'store'])->name('clinicians.availabilities.store');
    Route::put('/clinicians/{clinician}/availabilities/{availability}', [ClinicianAvailabilityController::class, 'update'])->scopeBindings()->name('clinicians.availabilities.update');
    Route::patch('/clinicians/{clinician}/availabilities/{availability}', [ClinicianAvailabilityController::class, 'update'])->scopeBindings();
    Route::delete('/clinicians/{clinician}/availabilities/{availability}', [ClinicianAvailabilityController::class, 'destroy'])->scopeBindings()->name('clinicians.availabilities.destroy');
    Route::get('/clinicians/{clinician}/time-off', [ClinicianTimeOffController::class, 'index'])->name('clinicians.time-off.index');
    Route::post('/clinicians/{clinician}/time-off', [ClinicianTimeOffController::class, 'store'])->name('clinicians.time-off.store');
    Route::delete('/clinicians/{clinician}/time-off/{timeOff}', [ClinicianTimeOffController::class, 'destroy'])->scopeBindings()->name('clinicians.time-off.destroy');
    Route::apiResource('patients', PatientController::class)->only(['index', 'show', 'store', 'update']);
    Route::apiResource('tasks', CareTaskController::class);
    Route::get('/availability', StaffAvailabilityController::class)->name('availability.index');
    Route::apiResource('appointments', AppointmentController::class)->only(['index', 'store', 'update']);
    Route::patch('/appointments/{appointment}/status', [AppointmentStatusController::class, 'update'])->name('appointments.status.update');
    Route::apiResource('services', ServiceController::class)->only(['index', 'store', 'update']);
    Route::get('/account', [StaffAccountController::class, 'show'])->name('account.show');
    Route::put('/account', [StaffAccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [StaffAccountController::class, 'updatePassword'])->name('account.password');
    Route::get('/settings', [SystemSettingsController::class, 'show'])->middleware('super.admin')->name('settings.show');
    Route::put('/settings', [SystemSettingsController::class, 'update'])->middleware('super.admin')->name('settings.update');
    Route::post('/settings/test-email', [SystemSettingsController::class, 'sendTest'])->middleware('super.admin')->name('settings.test-email');
});

Route::middleware(['auth', 'staff', 'clinician.panel'])->prefix('clinician')->name('clinician.')->group(function (): void {
    Route::get('/', [AdhdController::class, 'clinicianWorkspace'])->name('panel');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::apiResource('clinicians', ClinicianController::class)->only(['index', 'show', 'update']);
    Route::get('/clinicians/{clinician}/availabilities', [ClinicianAvailabilityController::class, 'index'])->name('clinicians.availabilities.index');
    Route::post('/clinicians/{clinician}/availabilities', [ClinicianAvailabilityController::class, 'store'])->name('clinicians.availabilities.store');
    Route::put('/clinicians/{clinician}/availabilities/{availability}', [ClinicianAvailabilityController::class, 'update'])->scopeBindings()->name('clinicians.availabilities.update');
    Route::patch('/clinicians/{clinician}/availabilities/{availability}', [ClinicianAvailabilityController::class, 'update'])->scopeBindings();
    Route::delete('/clinicians/{clinician}/availabilities/{availability}', [ClinicianAvailabilityController::class, 'destroy'])->scopeBindings()->name('clinicians.availabilities.destroy');
    Route::get('/clinicians/{clinician}/time-off', [ClinicianTimeOffController::class, 'index'])->name('clinicians.time-off.index');
    Route::post('/clinicians/{clinician}/time-off', [ClinicianTimeOffController::class, 'store'])->name('clinicians.time-off.store');
    Route::delete('/clinicians/{clinician}/time-off/{timeOff}', [ClinicianTimeOffController::class, 'destroy'])->scopeBindings()->name('clinicians.time-off.destroy');
    Route::apiResource('patients', PatientController::class)->only(['index', 'show']);
    Route::apiResource('tasks', CareTaskController::class)->only(['index', 'show', 'update']);
    Route::get('/availability', StaffAvailabilityController::class)->name('availability.index');
    Route::apiResource('appointments', AppointmentController::class)->only(['index', 'store', 'update']);
    Route::patch('/appointments/{appointment}/status', [AppointmentStatusController::class, 'update'])->name('appointments.status.update');
    Route::get('/account', [StaffAccountController::class, 'show'])->name('account.show');
    Route::put('/account', [StaffAccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [StaffAccountController::class, 'updatePassword'])->name('account.password');
});
