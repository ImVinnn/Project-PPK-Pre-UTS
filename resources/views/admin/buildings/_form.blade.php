<div class="mb-3">
    <label for="faculty_id" class="form-label">Pemilik Gedung</label>
    <select class="form-select @error('faculty_id') is-invalid @enderror" id="faculty_id" name="faculty_id">
        <option value="">Universitas</option>
        @foreach ($faculties as $faculty)
            <option
                value="{{ $faculty->id }}"
                @selected((string) old('faculty_id', $building?->faculty_id) === (string) $faculty->id)
            >
                {{ $faculty->code }} — {{ $faculty->name }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Pilih Universitas jika gedung tidak dimiliki fakultas tertentu.</div>
    @error('faculty_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="code" class="form-label">Kode Gedung</label>
    <input
        type="text"
        class="form-control text-uppercase @error('code') is-invalid @enderror"
        id="code"
        name="code"
        value="{{ old('code', $building?->code) }}"
        maxlength="30"
        required
        autofocus
    >
    @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4">
    <label for="name" class="form-label">Nama Gedung</label>
    <input
        type="text"
        class="form-control @error('name') is-invalid @enderror"
        id="name"
        name="name"
        value="{{ old('name', $building?->name) }}"
        maxlength="120"
        required
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
<a href="{{ route('admin.buildings.index') }}" class="btn btn-outline-secondary">Batal</a>
