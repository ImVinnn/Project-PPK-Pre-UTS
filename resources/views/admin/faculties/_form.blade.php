<div class="mb-3">
    <label for="code" class="form-label">Kode Fakultas</label>
    <input
        type="text"
        class="form-control text-uppercase @error('code') is-invalid @enderror"
        id="code"
        name="code"
        value="{{ old('code', $faculty?->code) }}"
        maxlength="20"
        required
        autofocus
    >
    @error('code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4">
    <label for="name" class="form-label">Nama Fakultas</label>
    <input
        type="text"
        class="form-control @error('name') is-invalid @enderror"
        id="name"
        name="name"
        value="{{ old('name', $faculty?->name) }}"
        maxlength="120"
        required
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
<a href="{{ route('admin.faculties.index') }}" class="btn btn-outline-secondary">Batal</a>
