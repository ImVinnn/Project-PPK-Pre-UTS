<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\FacilityController as AdminFacilityController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\ReservationController;
use App\Support\Status;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\Officer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\Officer\ReportController as OfficerReportController;
use App\Http\Controllers\Officer\ReservationController as OfficerReservationController;

Route::get('/', [FacilityController::class, 'index'])->name('facilities.index');
Route::get('/facilities/{facility}', [FacilityController::class, 'show'])->name('facilities.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:'.Status::ROLE_USER)->group(function (): void {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
    });

    Route::middleware('role:' . Status::ROLE_USER)
        ->prefix('reports')
        ->name('reports.')
        ->group(function (): void {
            Route::get('/', [DamageReportController::class, 'index'])->name('index');
            Route::get('/create', [DamageReportController::class, 'create'])->name('create');
            Route::post('/', [DamageReportController::class, 'store'])->name('store');
            Route::get('/{report}', [DamageReportController::class, 'show'])->name('show');
        });

    Route::prefix('officer')
        ->name('officer.')
        ->middleware('role:' . Status::ROLE_OFFICER)
        ->group(function (): void {
            Route::get('/', [OfficerDashboardController::class, 'index'])->name('dashboard');

            Route::get('/reports', [OfficerReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{report}', [OfficerReportController::class, 'show'])->name('reports.show');
            Route::patch('/reports/{report}/status', [OfficerReportController::class, 'updateStatus'])->name('reports.status.update');
            Route::patch('/reports/{report}/facility', [OfficerReportController::class, 'updateFacilityCondition'])->name('reports.facility.update');
            Route::get('/reservations', [OfficerReservationController::class, 'index'])->name('reservations.index');
            Route::get('/reservations/{reservation}', [OfficerReservationController::class, 'show'])->name('reservations.show');
            Route::patch('/reservations/{reservation}/approve', [OfficerReservationController::class, 'approve'])->name('reservations.approve');
            Route::patch('/reservations/{reservation}/reject', [OfficerReservationController::class, 'reject'])->name('reservations.reject');
            Route::patch('/reservations/{reservation}/cancel', [OfficerReservationController::class, 'cancel'])->name('reservations.cancel');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:'.Status::ROLE_ADMIN)
        ->group(function (): void {
            Route::view('/', 'dashboard')->name('dashboard');
            Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
            Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
            Route::patch('/accounts/{account}/status', [AccountController::class, 'updateStatus'])
                ->name('accounts.status.update');
            Route::resource('faculties', FacultyController::class)->except('show');
            Route::resource('buildings', BuildingController::class)->except('show');

            Route::get('/facilities', [AdminFacilityController::class, 'index'])->name('facilities.index');
            Route::get('/facilities/create', [AdminFacilityController::class, 'create'])->name('facilities.create');
            Route::post('/facilities', [AdminFacilityController::class, 'store'])->name('facilities.store');
            Route::get('/facilities/{facility}/edit', [AdminFacilityController::class, 'edit'])->name('facilities.edit');
            Route::put('/facilities/{facility}', [AdminFacilityController::class, 'update'])->name('facilities.update');
            Route::patch('/facilities/{facility}/deactivate', [AdminFacilityController::class, 'deactivate'])
                ->name('facilities.deactivate');
        });
});
