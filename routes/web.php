<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\BuildingController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\AuthController;
use App\Support\Status;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\Officer\ReportController as OfficerReportController;

Route::view('/', 'facilities.index')->name('facilities.index');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::view('/dashboard', 'dashboard')
        ->middleware('role:'.Status::ROLE_USER)
        ->name('dashboard');

    Route::middleware('role:' . Status::ROLE_USER)
        ->prefix('reports')
        ->name('reports.')
        ->group(function (): void {
            Route::get('/', [DamageReportController::class, 'index'])->name('index');
            Route::get('/create', [DamageReportController::class, 'create'])->name('create');
            Route::post('/', [DamageReportController::class, 'store'])->name('store');
            Route::get('/{report}', [DamageReportController::class, 'show'])->name('show');
        });

    Route::view('/officer', 'dashboard')
        ->middleware('role:'.Status::ROLE_OFFICER)
        ->name('officer.dashboard');

    Route::prefix('officer')
        ->name('officer.')
        ->middleware('role:' . Status::ROLE_OFFICER)
        ->group(function (): void {
            Route::get('/reports', [OfficerReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{report}', [OfficerReportController::class, 'show'])->name('reports.show');
            Route::patch('/reports/{report}/status', [OfficerReportController::class, 'updateStatus'])->name('reports.status.update');
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

            Route::get('/facilities', [FacilityController::class, 'index'])->name('facilities.index');
            Route::get('/facilities/create', [FacilityController::class, 'create'])->name('facilities.create');
            Route::post('/facilities', [FacilityController::class, 'store'])->name('facilities.store');
            Route::get('/facilities/{facility}/edit', [FacilityController::class, 'edit'])->name('facilities.edit');
            Route::put('/facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
            Route::patch('/facilities/{facility}/deactivate', [FacilityController::class, 'deactivate'])
                ->name('facilities.deactivate');
        });
});
