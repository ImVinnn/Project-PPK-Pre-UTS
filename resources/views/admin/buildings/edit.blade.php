@extends('layouts.app')

@section('title', 'Edit Gedung')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="bg-white border p-4 p-md-5">
                <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Master Data</p>
                <h1 class="font-serif fs-3 fw-semibold mb-1">Edit Gedung</h1>
                <p class="text-secondary mb-4">Perbarui identitas dan pemilik gedung {{ $building->code }}.</p>

                <form method="POST" action="{{ route('admin.buildings.update', $building) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.buildings._form', [
                        'building' => $building,
                        'submitLabel' => 'Perbarui Gedung',
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
