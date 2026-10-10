@extends('layouts.app')

@section('title', 'Rekap Fasilitas - Fasilitas Kampus')

@use('App\Http\Requests\Admin\RecapFilterRequest')
@use('App\Services\RecapService')

@php
    $tabs = [
        RecapService::TYPE_PLACES => [
            'label' => 'Tempat',
            'note' => 'Okupansi tempat (ruang kelas, aula, laboratorium, lapangan) dari reservasi disetujui yang beririsan dengan periode. Menit dihitung hanya bagian yang berada di dalam periode.',
            'empty' => 'Tidak ada reservasi tempat yang disetujui pada periode ini.',
        ],
        RecapService::TYPE_EQUIPMENT => [
            'label' => 'Alat',
            'note' => 'Okupansi alat dari reservasi disetujui yang beririsan dengan periode. Unit-menit = menit beririsan × jumlah unit yang dipinjam.',
            'empty' => 'Tidak ada reservasi alat yang disetujui pada periode ini.',
        ],
        RecapService::TYPE_REPORTS => [
            'label' => 'Laporan',
            'note' => 'Frekuensi laporan kerusakan berdasarkan waktu laporan dibuat di dalam periode, dipecah per status saat ini. Satu kerusakan bisa dilaporkan lebih dari sekali.',
            'empty' => 'Tidak ada laporan kerusakan yang dibuat pada periode ini.',
        ],
    ];

    $labelColumns = ['faculty', 'building', 'facility'];

    $query = array_filter([
        'from' => $filters['from']->format(RecapFilterRequest::FORMAT),
        'to' => $filters['to']->format(RecapFilterRequest::FORMAT),
        'faculty_id' => $filters['facultyId'],
        'building_id' => $filters['buildingId'],
    ]);
@endphp

@section('content')
<div class="fotel-container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 pb-3 border-bottom gap-2">
        <div>
            <div class="small text-uppercase font-mono text-muted">Pelaporan</div>
            <h1 class="fs-2 fw-bold text-dark mb-1 tracking-tight">Rekap Fasilitas</h1>
            <p class="text-secondary small mb-0">
                Periode {{ $filters['from']->locale('id')->translatedFormat('d M Y H:i') }}
                &ndash; {{ $filters['to']->locale('id')->translatedFormat('d M Y H:i') }} WIB
            </p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.recaps.index') }}" class="row g-3 align-items-end mb-4 p-3 bg-white border rounded" novalidate>
        <div class="col-md-6 col-lg-3">
            <label for="from" class="form-label small text-muted mb-1">Mulai</label>
            <input type="datetime-local" id="from" name="from" required
                   class="form-control @error('from') is-invalid @enderror"
                   value="{{ old('from', $query['from']) }}">
            @error('from')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-lg-3">
            <label for="to" class="form-label small text-muted mb-1">Sampai</label>
            <input type="datetime-local" id="to" name="to" required
                   class="form-control @error('to') is-invalid @enderror"
                   value="{{ old('to', $query['to']) }}">
            @error('to')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-lg-2">
            <label for="faculty_id" class="form-label small text-muted mb-1">Fakultas</label>
            <select id="faculty_id" name="faculty_id" class="form-select @error('faculty_id') is-invalid @enderror">
                <option value="">Semua fakultas</option>
                @foreach ($faculties as $faculty)
                    <option value="{{ $faculty->id }}" @selected((string) old('faculty_id', $filters['facultyId']) === (string) $faculty->id)>
                        {{ $faculty->name }}
                    </option>
                @endforeach
            </select>
            @error('faculty_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 col-lg-2">
            <label for="building_id" class="form-label small text-muted mb-1">Gedung</label>
            <select id="building_id" name="building_id" class="form-select @error('building_id') is-invalid @enderror">
                <option value="">Semua gedung</option>
                @foreach ($buildings as $building)
                    <option value="{{ $building->id }}" @selected((string) old('building_id', $filters['buildingId']) === (string) $building->id)>
                        {{ $building->name }} ({{ $building->faculty->name ?? 'Universitas' }})
                    </option>
                @endforeach
            </select>
            @error('building_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-lg-2">
            <button type="submit" class="btn-fotel-gold w-100 py-2">Terapkan</button>
        </div>
        <div class="col-12">
            <p class="small text-muted mb-0">Rentang maksimal 1 tahun. Waktu akhir tidak termasuk dalam periode.</p>
        </div>
    </form>

    <div class="alert alert-light border small mb-4" role="note">
        <strong>Catatan:</strong> okupansi berarti pemakaian <em>terjadwal</em> berdasarkan reservasi yang disetujui,
        bukan kehadiran nyata. Reservasi menunggu, ditolak, dan dibatalkan tidak dihitung.
    </div>

    {{-- Tab --}}
    <ul class="nav nav-tabs" role="tablist">
        @foreach ($tabs as $type => $tab)
            <li class="nav-item" role="presentation">
                <button class="nav-link text-dark @if ($loop->first) active @endif"
                        id="tab-{{ $type }}" type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#pane-{{ $type }}"
                        aria-controls="pane-{{ $type }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $tab['label'] }}
                    <span class="fotel-badge fotel-badge-slate ms-1">{{ count($recaps[$type]) }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content bg-white border border-top-0 rounded-bottom">
        @foreach ($tabs as $type => $tab)
            <div class="tab-pane fade @if ($loop->first) show active @endif"
                 id="pane-{{ $type }}" role="tabpanel" aria-labelledby="tab-{{ $type }}" tabindex="0">
                <div class="p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom">
                    <p class="small text-secondary mb-0">{{ $tab['note'] }}</p>
                    <a href="{{ route('admin.recaps.export', ['type' => $type] + $query) }}" data-no-ajax
                       class="btn-fotel-secondary py-1 px-3 text-nowrap text-decoration-none" download>
                        Unduh CSV {{ $tab['label'] }}
                    </a>
                </div>

                @if ($recaps[$type] === [])
                    <div class="text-center py-5 text-muted">
                        <p class="mb-0">{{ $tab['empty'] }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light font-mono" style="font-size: 0.72rem; letter-spacing: 0.05em; text-transform: uppercase;">
                                <tr>
                                    @foreach (RecapService::COLUMNS[$type] as $key => $label)
                                        <th class="py-3 {{ $loop->first ? 'ps-4' : '' }} {{ $loop->last ? 'pe-4' : '' }} {{ in_array($key, $labelColumns, true) ? '' : 'text-end' }}">
                                            {{ $label }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.88rem;">
                                @foreach ($recaps[$type] as $row)
                                    <tr>
                                        @foreach (RecapService::COLUMNS[$type] as $key => $label)
                                            <td class="{{ $loop->first ? 'ps-4' : '' }} {{ $loop->last ? 'pe-4' : '' }} {{ in_array($key, $labelColumns, true) ? '' : 'text-end font-mono' }} {{ $key === 'facility' ? 'fw-semibold' : '' }}">
                                                {{ $row[$key] }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
