<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccountRequest;
use App\Http\Requests\Admin\UpdateAccountStatusRequest;
use App\Models\User;
use App\Support\Status;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $accounts = User::query()
            ->orderByRaw('CASE WHEN account_status = ? THEN 0 ELSE 1 END', [Status::ACCOUNT_PENDING])
            ->latest()
            ->paginate(15);

        return view('admin.accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        return view('admin.accounts.create');
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'account_status' => Status::ACCOUNT_ACTIVE,
        ]);

        return to_route('admin.accounts.index')
            ->with('success', 'Akun aktif berhasil dibuat.');
    }

    public function updateStatus(
        UpdateAccountStatusRequest $request,
        User $account,
    ): RedirectResponse {
        abort_if($account->hasRole(Status::ROLE_ADMIN), 403);

        $account->update([
            'account_status' => $request->validated('account_status'),
        ]);

        return to_route('admin.accounts.index')
            ->with('success', 'Status akun berhasil diperbarui.');
    }
}
