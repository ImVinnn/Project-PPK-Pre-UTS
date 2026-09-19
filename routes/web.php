<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\AuthController;
use App\Support\Status;
use Illuminate\Support\Facades\Route;

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
        });
});
