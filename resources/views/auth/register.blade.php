@extends('layouts.app')

@section('title', 'Daftar Akun - Fasilitas Kampus')

@section('content')
<div class="fotel-container py-5 my-md-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="p-4 p-md-5 bg-white border rounded">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/brand/sora-icon.png') }}" alt="SORA" class="fotel-brand-icon fotel-brand-icon-lg mb-2">
                    <div class="fotel-brand-text fs-3 lh-1">SORA</div>
                    <div class="small text-muted mb-3">Sistem Operasional Reservasi dan Aduan Fasilitas Kampus</div>
                    <h1 class="fs-4 fw-bold text-dark mb-0 tracking-tight">Daftar Akun</h1>
                </div>

                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Nama Lengkap</label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            placeholder="Nama Sivitas Lengkap"
                            autofocus
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Password</label>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            placeholder="Minimal 8 karakter"
                            autocomplete="new-password"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label small fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Konfirmasi Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Ulangi password"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-fotel-gold w-100 py-2 fs-6">Daftar</button>
                </form>

                <p class="small text-center mt-4 mb-0 text-muted">
                    Sudah punya akun? <a href="{{ route('login') }}" class="fw-bold text-dark text-decoration-none">Masuk</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
