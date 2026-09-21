/**
 * slot-picker.js - Client-Side Enhancement untuk Form Reservasi (P2)
 *
 * Memberikan feedback cepat dan kenyamanan pemakai (UX) saat memilih slot waktu.
 * Sesuai aturan PRD, validasi ini hanyalah kenyamanan di sisi client;
 * seluruh aturan tetap ditegakkan secara mutlak oleh StoreReservationRequest di sisi server.
 */

document.addEventListener('DOMContentLoaded', function () {
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    const facilitySelect = document.getElementById('facility_id');

    if (!startTimeSelect || !endTimeSelect) {
        return;
    }

    function timeToMinutes(timeStr) {
        if (!timeStr) return 0;
        const parts = timeStr.split(':');
        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    function updateEndTimeOptions() {
        const startVal = startTimeSelect.value;
        if (!startVal) return;

        const startMinutes = timeToMinutes(startVal);
        const currentEndVal = endTimeSelect.value;

        let hasValidSelection = false;

        Array.from(endTimeSelect.options).forEach(function (opt) {
            if (!opt.value) return;

            const endMinutes = timeToMinutes(opt.value);
            // End time harus lebih besar dari start time
            if (endMinutes <= startMinutes) {
                opt.disabled = true;
                opt.style.display = 'none';
            } else {
                opt.disabled = false;
                opt.style.display = '';
                if (opt.value === currentEndVal) {
                    hasValidSelection = true;
                }
            }
        });

        // Jika opsi end_time yang dipilih sebelumnya menjadi tidak valid, reset
        if (!hasValidSelection && currentEndVal && timeToMinutes(currentEndVal) <= startMinutes) {
            endTimeSelect.value = '';
        }
    }

    startTimeSelect.addEventListener('change', updateEndTimeOptions);
    updateEndTimeOptions();
});
