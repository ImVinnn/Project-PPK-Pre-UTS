@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="p-4" style="background: var(--ink); border-top: 3px solid var(--brass);">
                <h1 class="font-serif fs-4 fw-semibold mb-1" style="color: #f5f3ed;">Masuk</h1>
                <p class="small mb-4" style="color: #a9b2ba;">Untuk mengajukan reservasi atau melaporkan kerusakan.</p>
                @if (session('success'))
                    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label small" style="color: #c7cdd2;">Email</label>
                        <input
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            autofocus
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label small" style="color: #c7cdd2;">Password</label>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>
                <p class="small text-center mt-4 mb-0" style="color: #a9b2ba;">
                    Belum punya akun? <a href="{{ route('register') }}" style="color: var(--brass);">Daftar di sini</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
