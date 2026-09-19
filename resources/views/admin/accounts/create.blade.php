@extends('layouts.app')

@section('title', 'Tambah Akun')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="bg-white border p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Admin</p>
                        <h1 class="font-serif fs-3 fw-semibold mb-1">Tambah Akun Aktif</h1>
                        <p class="text-secondary mb-0">Buat akun pengguna atau petugas tanpa verifikasi tambahan.</p>
                    </div>
                    <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
                </div>

                <form method="POST" action="{{ route('admin.accounts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">Pilih role</option>
                            <option value="{{ \App\Support\Status::ROLE_USER }}" @selected(old('role') === \App\Support\Status::ROLE_USER)>Pengguna</option>
                            <option value="{{ \App\Support\Status::ROLE_OFFICER }}" @selected(old('role') === \App\Support\Status::ROLE_OFFICER)>Petugas</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" autocomplete="new-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Akun</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
