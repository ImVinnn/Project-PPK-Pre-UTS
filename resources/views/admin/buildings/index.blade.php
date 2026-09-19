@extends('layouts.app')

@section('title', 'Kelola Gedung')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="font-mono small text-uppercase mb-1" style="color: var(--brass-dark);">Master Data</p>
            <h1 class="font-serif fw-semibold mb-1">Gedung</h1>
            <p class="text-secondary mb-0">Kelola gedung fakultas dan gedung tingkat universitas.</p>
        </div>
        <a href="{{ route('admin.buildings.create') }}" class="btn btn-primary">Tambah Gedung</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="table-responsive bg-white border">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3">Kode</th>
                    <th>Nama Gedung</th>
                    <th>Pemilik</th>
                    <th class="text-end px-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($buildings as $building)
                    <tr>
                        <td class="px-3"><span class="badge text-bg-secondary">{{ $building->code }}</span></td>
                        <td class="fw-medium">{{ $building->name }}</td>
                        <td>
                            @if ($building->faculty)
                                {{ $building->faculty->code }} — {{ $building->faculty->name }}
                            @else
                                <span class="badge text-bg-light border">Universitas</span>
                            @endif
                        </td>
                        <td class="text-end px-3">
                            <a href="{{ route('admin.buildings.edit', $building) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                            <form
                                method="POST"
                                action="{{ route('admin.buildings.destroy', $building) }}"
                                class="d-inline"
                                onsubmit="return confirm('Hapus gedung ini? Gedung yang masih dipakai fasilitas tidak dapat dihapus.')"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-secondary py-4">Belum ada gedung.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $buildings->links() }}</div>
</div>
@endsection
