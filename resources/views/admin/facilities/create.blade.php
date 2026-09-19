@extends('layouts.app')

@section('title', 'Tambah Fasilitas')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white border p-4 p-md-5">
                <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Master Data</p>
                <h1 class="font-serif fs-3 fw-semibold mb-1">Tambah Fasilitas</h1>
                <p class="text-secondary mb-4">Lengkapi data fasilitas beserta detail sesuai tipenya.</p>

                <form method="POST" action="{{ route('admin.facilities.store') }}">
                    @csrf
                    @include('admin.facilities._form', [
                        'facility' => null,
                        'submitLabel' => 'Simpan Fasilitas',
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
