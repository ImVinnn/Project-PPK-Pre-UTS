@extends('layouts.app')

@section('title', 'Tambah Gedung')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="bg-white border p-4 p-md-5">
                <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Master Data</p>
                <h1 class="font-serif fs-3 fw-semibold mb-1">Tambah Gedung</h1>
                <p class="text-secondary mb-4">Gedung dapat dimiliki fakultas atau langsung oleh universitas.</p>

                <form method="POST" action="{{ route('admin.buildings.store') }}">
                    @csrf
                    @include('admin.buildings._form', [
                        'building' => null,
                        'submitLabel' => 'Simpan Gedung',
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
