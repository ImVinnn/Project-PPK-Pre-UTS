@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white border p-4 p-md-5">
                <p class="font-mono small text-uppercase mb-2" style="color: var(--brass-dark);">
                    {{ auth()->user()->role }}
                </p>
                <h1 class="font-serif fw-semibold">Selamat datang, {{ auth()->user()->name }}</h1>
                <p class="text-secondary mb-0">
                    Anda berhasil masuk dengan akun aktif. Menu sesuai peran akan tersedia dari halaman ini.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
