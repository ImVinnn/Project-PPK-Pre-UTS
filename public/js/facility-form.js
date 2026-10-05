/**
 * facility-form.js - Form fasilitas admin (create & edit).
 *
 * Blok detail berganti sesuai tipe; validasi tetap ditegakkan StoreFacilityRequest/UpdateFacilityRequest.
 */
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
