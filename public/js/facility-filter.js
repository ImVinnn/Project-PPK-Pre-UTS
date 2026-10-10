/**
 * facility-filter.js - Client-Side Enhancement untuk Katalog Fasilitas (Fotel Style)
 *
 * Memberikan interaksi instan saat memfilter jenis fasilitas pada katalog publik.
 */

function initFacilityFilter() {
    const tipeSelect = document.getElementById('tipe');
    const filterForm = tipeSelect ? tipeSelect.closest('form') : null;

    if (!tipeSelect || !filterForm) {
        return;
    }

    // Auto-submit saat pilihan dropdown berubah
    tipeSelect.addEventListener('change', function () {
        filterForm.requestSubmit();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFacilityFilter, { once: true });
} else {
    initFacilityFilter();
}
