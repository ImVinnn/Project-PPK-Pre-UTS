@extends('layouts.app')

@section('title', 'Daftar Akun')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="p-4" style="background: var(--ink); border-top: 3px solid var(--brass);">
                <h1 class="font-serif fs-4 fw-semibold mb-1" style="color: #f5f3ed;">Daftar Akun</h1>
                <p class="small mb-4" style="color: #a9b2ba;">Akun baru perlu diaktifkan admin sebelum dapat digunakan.</p>

                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label small" style="color: #c7cdd2;">Nama</label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            autofocus
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small" style="color: #c7cdd2;">Email</label>
                        <input
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small" style="color: #c7cdd2;">Password</label>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label small" style="color: #c7cdd2;">Konfirmasi Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Daftar</button>
                </form>

                <p class="small text-center mt-4 mb-0" style="color: #a9b2ba;">
                    Sudah punya akun? <a href="{{ route('login') }}" style="color: var(--brass);">Masuk</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
