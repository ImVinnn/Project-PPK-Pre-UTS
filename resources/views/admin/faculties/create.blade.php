@extends('layouts.app')

@section('title', 'Tambah Fakultas')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="bg-white border p-4 p-md-5">
                <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Master Data</p>
                <h1 class="font-serif fs-3 fw-semibold mb-1">Tambah Fakultas</h1>
                <p class="text-secondary mb-4">Kode fakultas akan disimpan dalam huruf kapital.</p>

                <form method="POST" action="{{ route('admin.faculties.store') }}">
                    @csrf
                    @include('admin.faculties._form', [
                        'faculty' => null,
                        'submitLabel' => 'Simpan Fakultas',
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
