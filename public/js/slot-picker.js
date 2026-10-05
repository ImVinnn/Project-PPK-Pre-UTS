/**
 * slot-picker.js - Client-Side Enhancement untuk Form Reservasi (Fotel Style)
 *
 * Memberikan feedback cepat dan kenyamanan pemakai (UX) saat memilih slot waktu.
 * Sesuai aturan PRD, validasi ini hanyalah kenyamanan di sisi client;
 * seluruh aturan tetap ditegakkan secara mutlak oleh StoreReservationRequest di sisi server.
 */

document.addEventListener('DOMContentLoaded', function () {
    const startTimeSelect = document.getElementById('start_time');
    const endTimeSelect = document.getElementById('end_time');
    const durationText = document.getElementById('durationText');
    const slotCountText = document.getElementById('slotCountText');

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
        const currentEndVal = endTimeSelect.value;

        if (!startVal) {
            updateDurationDisplay();
            return;
        }

        const startMinutes = timeToMinutes(startVal);
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

        updateDurationDisplay();
    }

    function updateDurationDisplay() {
        if (!durationText || !slotCountText) return;

        const startVal = startTimeSelect.value;
        const endVal = endTimeSelect.value;

        if (!startVal || !endVal) {
            durationText.textContent = '-';
            slotCountText.textContent = '-';
            return;
        }

        const startMinutes = timeToMinutes(startVal);
        const endMinutes = timeToMinutes(endVal);

        if (endMinutes > startMinutes) {
            const diff = endMinutes - startMinutes;
            const hours = Math.floor(diff / 60);
            const mins = diff % 60;
            const slots = diff / 30;

            let text = '';
            if (hours > 0) text += hours + ' Jam ';
            if (mins > 0) text += mins + ' Menit';

            durationText.textContent = text.trim();
            slotCountText.textContent = slots + ' Slot (@ 30 mnt)';
        } else {
            durationText.textContent = 'Waktu tidak valid';
            slotCountText.textContent = '-';
        }
    }

    startTimeSelect.addEventListener('change', updateEndTimeOptions);
    endTimeSelect.addEventListener('change', updateDurationDisplay);

    updateEndTimeOptions();
    updateDurationDisplay();
});
