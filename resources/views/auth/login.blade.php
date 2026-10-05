@extends('layouts.app')

@section('title', 'Masuk - Fasilitas Kampus')

@section('content')
<div class="fotel-container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="p-4 p-md-5 bg-white border rounded">
                <div class="text-center mb-4">
                    <div class="fotel-logo-icon mx-auto mb-2" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <h1 class="fs-4 fw-bold text-dark mb-1 tracking-tight">Masuk</h1>
                    <p class="small text-muted mb-0">Untuk mengajukan reservasi atau melaporkan kerusakan.</p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success border small mb-4" role="alert">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Email</label>
                        <input
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            placeholder="nama@kampus.ac.id"
                            autofocus
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Password</label>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-fotel-gold w-100 py-2 fs-6">Masuk</button>
                </form>

                <p class="small text-center mt-4 mb-0 text-muted">
                    Belum punya akun? <a href="{{ route('register') }}" class="fw-bold text-dark text-decoration-none">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
