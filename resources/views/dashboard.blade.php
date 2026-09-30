@extends('layouts.app')

@section('title', 'Dashboard Pengguna - Fasilitas Kampus')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white border rounded p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fotel-badge fotel-badge-slate font-mono text-uppercase">
                        {{ auth()->user()->role }}
                    </span>
                    <span class="small font-mono text-muted">Sistem Aktif</span>
                </div>

                <h1 class="fs-2 fw-bold text-dark mb-2 tracking-tight">
                    Selamat datang, {{ auth()->user()->name }}
                </h1>
                <p class="text-secondary mb-4 leading-relaxed">
                    Anda berhasil masuk dengan akun sivitas kampus aktif. Gunakan menu di bawah untuk memeriksa ketersediaan fasilitas atau memantau riwayat pengajuan reservasi Anda.
                </p>

                <div class="row g-3 pt-3 border-top">
                    <div class="col-sm-6">
                        <a href="{{ route('facilities.index') }}" class="btn-fotel-gold w-100 text-center text-decoration-none py-2">
                            Buka Katalog Fasilitas &rarr;
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="{{ route('reservations.index') }}" class="btn-fotel-outline w-100 text-center text-decoration-none py-2">
                            Lihat Reservasi Saya
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
