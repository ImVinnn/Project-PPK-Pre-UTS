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
        Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    });

    Route::view('/officer', 'dashboard')
        ->middleware('role:'.Status::ROLE_OFFICER)
        ->name('officer.dashboard');

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
