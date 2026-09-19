@extends('layouts.app')

@section('title', 'Kelola Akun')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Admin</p>
            <h1 class="font-serif fw-semibold mb-1">Kelola Akun</h1>
            <p class="text-secondary mb-0">Verifikasi pendaftar dan kelola status akun tanpa menghapus riwayat.</p>
        </div>
        <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary">Tambah Akun Aktif</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    <div class="table-responsive bg-white border">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3">Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end px-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($accounts as $account)
                    <tr>
                        <td class="px-3 fw-medium">{{ $account->name }}</td>
                        <td>{{ $account->email }}</td>
                        <td><span class="badge text-bg-secondary">{{ ucfirst($account->role) }}</span></td>
                        <td><span class="badge text-bg-light border">{{ ucfirst($account->account_status) }}</span></td>
                        <td class="text-end px-3">
                            @if (! $account->hasRole(\App\Support\Status::ROLE_ADMIN))
                                <form method="POST" action="{{ route('admin.accounts.status.update', $account) }}" class="d-inline-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="account_status" class="form-select form-select-sm" aria-label="Status akun {{ $account->name }}">
                                        <option value="{{ \App\Support\Status::ACCOUNT_ACTIVE }}" @selected($account->account_status === \App\Support\Status::ACCOUNT_ACTIVE)>Aktif</option>
                                        <option value="{{ \App\Support\Status::ACCOUNT_REJECTED }}" @selected($account->account_status === \App\Support\Status::ACCOUNT_REJECTED)>Ditolak</option>
                                        <option value="{{ \App\Support\Status::ACCOUNT_INACTIVE }}" @selected($account->account_status === \App\Support\Status::ACCOUNT_INACTIVE)>Nonaktif</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Simpan</button>
                                </form>
                            @else
                                <span class="small text-secondary">Dilindungi</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Belum ada akun.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $accounts->links() }}</div>
</div>
@endsection
