@extends('layouts.app')

@section('title', 'Edit Fasilitas')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white border p-4 p-md-5">
                <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Master Data</p>
                <h1 class="font-serif fs-3 fw-semibold mb-1">Edit Fasilitas</h1>
                <p class="text-secondary mb-4">Perbarui data fasilitas {{ $facility->name }}.</p>

                <form method="POST" action="{{ route('admin.facilities.update', $facility) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.facilities._form', [
                        'facility' => $facility,
                        'submitLabel' => 'Perbarui Fasilitas',
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
