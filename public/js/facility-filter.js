/**
 * facility-filter.js - Client-Side Enhancement untuk Katalog Fasilitas (P2)
 *
 * Memberikan interaksi instan saat memfilter jenis fasilitas pada katalog publik.
 */

document.addEventListener('DOMContentLoaded', function () {
    const tipeSelect = document.getElementById('tipe');
    const filterForm = tipeSelect ? tipeSelect.closest('form') : null;

    if (!tipeSelect || !filterForm) {
        return;
    }

    // Auto-submit saat pilihan tipe berubah untuk kenyamanan pengguna
    tipeSelect.addEventListener('change', function () {
        filterForm.submit();
    });
});
