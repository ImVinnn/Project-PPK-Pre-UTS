@php
    $roomLikeTypes = [
        \App\Support\Status::FACILITY_CLASSROOM,
        \App\Support\Status::FACILITY_HALL,
        \App\Support\Status::FACILITY_LABORATORY,
    ];
    $selectedType = old('type', $facility?->type);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="faculty_id" class="form-label">Fakultas</label>
        <select class="form-select @error('faculty_id') is-invalid @enderror" id="faculty_id" name="faculty_id">
            <option value="">Universitas</option>
            @foreach ($faculties as $faculty)
                <option
                    value="{{ $faculty->id }}"
                    @selected((string) old('faculty_id', $facility?->faculty_id) === (string) $faculty->id)
                >
                    {{ $faculty->code }} — {{ $faculty->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Pilih Universitas jika fasilitas tidak dimiliki fakultas tertentu.</div>
        @error('faculty_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="building_id" class="form-label">Gedung</label>
        <select class="form-select @error('building_id') is-invalid @enderror" id="building_id" name="building_id">
            <option value="">Di luar gedung</option>
            @foreach ($buildings as $building)
                <option
                    value="{{ $building->id }}"
                    @selected((string) old('building_id', $facility?->building_id) === (string) $building->id)
                >
                    {{ $building->code }} — {{ $building->name }} ({{ $building->faculty->code ?? 'Universitas' }})
                </option>
            @endforeach
        </select>
        <div class="form-text">Pastikan Fakultas di atas sama dengan pemilik gedung yang dipilih di sini.</div>
        @error('building_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3 mt-3">
    <label for="name" class="form-label">Nama Fasilitas</label>
    <input
        type="text"
        class="form-control @error('name') is-invalid @enderror"
        id="name"
        name="name"
        value="{{ old('name', $facility?->name) }}"
        maxlength="120"
        required
        autofocus
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="type" class="form-label">Tipe</label>
    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
        <option value="">Pilih tipe</option>
        @foreach (\App\Support\Status::FACILITY_TYPES as $type)
            <option value="{{ $type }}" @selected($selectedType === $type)>
                {{ ucwords(str_replace('_', ' ', $type)) }}
            </option>
        @endforeach
    </select>
    @if ($facility)
        <div class="form-text">
            Mengubah tipe akan ditolak server jika fasilitas ini sudah memiliki riwayat reservasi/laporan —
            buat fasilitas baru dan nonaktifkan yang lama bila itu terjadi.
        </div>
    @endif
    @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3" id="capacity-field">
    <label for="capacity" class="form-label">Kapasitas</label>
    <input
        type="number"
        class="form-control @error('capacity') is-invalid @enderror"
        id="capacity"
        name="capacity"
        value="{{ old('capacity', $facility?->capacity) }}"
        min="1"
    >
    <div class="form-text">Wajib diisi untuk semua tipe kecuali Alat.</div>
    @error('capacity')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="location_detail" class="form-label">Lokasi</label>
    <input
        type="text"
        class="form-control @error('location_detail') is-invalid @enderror"
        id="location_detail"
        name="location_detail"
        value="{{ old('location_detail', $facility?->location_detail) }}"
        maxlength="200"
        placeholder="Contoh: Lantai 2, sayap timur"
        required
    >
    @error('location_detail')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-4">
    <label for="description" class="form-label">Deskripsi (opsional)</label>
    <textarea
        class="form-control @error('description') is-invalid @enderror"
        id="description"
        name="description"
        rows="3"
    >{{ old('description', $facility?->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div id="room-fields" class="border p-3 mb-4 bg-light-subtle">
    <p class="fw-semibold mb-3">Detail Ruang/Tempat</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="room_number" class="form-label">Nomor/Kode Ruang</label>
            <input
                type="text"
                class="form-control @error('room_number') is-invalid @enderror"
                id="room_number"
                name="room_number"
                value="{{ old('room_number', $facility?->roomDetail?->room_number) }}"
                maxlength="30"
            >
            @error('room_number')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="floor" class="form-label">Lantai (opsional)</label>
            <input
                type="number"
                class="form-control @error('floor') is-invalid @enderror"
                id="floor"
                name="floor"
                value="{{ old('floor', $facility?->roomDetail?->floor) }}"
            >
            <div class="form-text">Boleh negatif untuk basement.</div>
            @error('floor')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div id="equipment-fields" class="border p-3 mb-4 bg-light-subtle">
    <p class="fw-semibold mb-3">Detail Alat</p>
    <div class="row g-3">
        <div class="col-md-6">
            <label for="brand" class="form-label">Merek (opsional)</label>
            <input
                type="text"
                class="form-control @error('brand') is-invalid @enderror"
                id="brand"
                name="brand"
                value="{{ old('brand', $facility?->equipmentDetail?->brand) }}"
                maxlength="100"
            >
            @error('brand')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="model" class="form-label">Model (opsional)</label>
            <input
                type="text"
                class="form-control @error('model') is-invalid @enderror"
                id="model"
                name="model"
                value="{{ old('model', $facility?->equipmentDetail?->model) }}"
                maxlength="100"
            >
            @error('model')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="stock_total" class="form-label">Stok Total</label>
            <input
                type="number"
                class="form-control @error('stock_total') is-invalid @enderror"
                id="stock_total"
                name="stock_total"
                value="{{ old('stock_total', $facility?->equipmentDetail?->stock_total) }}"
                min="1"
            >
            @error('stock_total')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="stock_unavailable" class="form-label">Stok Tidak Tersedia</label>
            <input
                type="number"
                class="form-control @error('stock_unavailable') is-invalid @enderror"
                id="stock_unavailable"
                name="stock_unavailable"
                value="{{ old('stock_unavailable', $facility?->equipmentDetail?->stock_unavailable) }}"
                min="0"
            >
            <div id="stock-warning" class="form-text text-danger d-none">
                Stok tidak tersedia melebihi stok total.
            </div>
            @error('stock_unavailable')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
<a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary">Batal</a>

<script>
(function () {
    var ROOM_LIKE_TYPES = ['ruang_kelas', 'aula', 'laboratorium'];
    var EQUIPMENT_TYPE = 'alat';

    var typeSelect = document.getElementById('type');
    var roomBlock = document.getElementById('room-fields');
    var equipmentBlock = document.getElementById('equipment-fields');
    var capacityField = document.getElementById('capacity-field');
    var capacityInput = document.getElementById('capacity');
    var roomNumberInput = document.getElementById('room_number');
    var floorInput = document.getElementById('floor');
    var brandInput = document.getElementById('brand');
    var modelInput = document.getElementById('model');
    var stockTotalInput = document.getElementById('stock_total');
    var stockUnavailableInput = document.getElementById('stock_unavailable');
    var stockWarning = document.getElementById('stock-warning');

    // Bagian tersembunyi di-disable, bukan hanya disembunyikan, supaya nilai
    // lamanya tidak ikut terkirim (mis. capacity harus NULL untuk tipe alat).
    function applyType() {
        var type = typeSelect.value;
        var isEquipment = type === EQUIPMENT_TYPE;
        var isRoomLike = ROOM_LIKE_TYPES.indexOf(type) !== -1;

        roomBlock.classList.toggle('d-none', !isRoomLike);
        equipmentBlock.classList.toggle('d-none', !isEquipment);
        capacityField.classList.toggle('d-none', isEquipment);

        roomNumberInput.required = isRoomLike;
        roomNumberInput.disabled = !isRoomLike;
        floorInput.disabled = !isRoomLike;

        stockTotalInput.required = isEquipment;
        stockTotalInput.disabled = !isEquipment;
        stockUnavailableInput.disabled = !isEquipment;
        brandInput.disabled = !isEquipment;
        modelInput.disabled = !isEquipment;

        capacityInput.required = !isEquipment;
        capacityInput.disabled = isEquipment;
    }

    function checkStock() {
        var total = parseInt(stockTotalInput.value, 10) || 0;
        var unavailable = parseInt(stockUnavailableInput.value, 10) || 0;
        stockWarning.classList.toggle('d-none', unavailable <= total);
    }

    typeSelect.addEventListener('change', applyType);
    stockTotalInput.addEventListener('input', checkStock);
    stockUnavailableInput.addEventListener('input', checkStock);

    applyType();
    checkStock();
})();
</script>
