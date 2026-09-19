<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->to($this->dashboardRoute($request->user()));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => Status::ROLE_USER,
            'account_status' => Status::ACCOUNT_PENDING,
        ]);

        return to_route('login')
            ->with('success', 'Pendaftaran berhasil. Tunggu admin mengaktifkan akun Anda.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }

    private function dashboardRoute(User $user): string
    {
        return match ($user->role) {
            Status::ROLE_ADMIN => route('admin.dashboard'),
            Status::ROLE_OFFICER => route('officer.dashboard'),
            default => route('dashboard'),
        };
    }
}
