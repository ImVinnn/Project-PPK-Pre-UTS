/**
 * motion.js - Engine Gerak & Interaksi Fotel (Emil Kowalski Design Engineering)
 * Memberikan transisi halaman yang mulus tanpa flash putih, scroll reveal bertahap,
 * respon taktil hardware-accelerated, dan skeleton shimmer state.
 */

(function () {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /**
     * 1. The Entrance: Soft exit link transition
     * Mencegah flash layar saat pindah halaman internal dengan transisi keluar 110ms.
     */
    function initPageTransitions() {
        if (prefersReducedMotion) return;

        const mainContent = document.getElementById('main-content');
        if (!mainContent) return;

        // Reset state jika pengguna kembali via browser back-forward cache
        window.addEventListener('pageshow', function (event) {
            mainContent.classList.remove('is-exiting');
        });

        document.addEventListener('click', function (e) {
            const anchor = e.target.closest('a');
            if (!anchor) return;

            const href = anchor.getAttribute('href');
            if (!href) return;

            // Abaikan tautan eksternal, tab baru, unduhan, hash murni, atau modifier keys
            if (
                anchor.target === '_blank' ||
                anchor.hasAttribute('download') ||
                anchor.getAttribute('rel') === 'external' ||
                href.startsWith('#') ||
                href.startsWith('javascript:') ||
                href.startsWith('mailto:') ||
                href.startsWith('tel:') ||
                e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ||
                e.button !== 0
            ) {
                return;
            }

            // Pastikan domain sama
            try {
                const targetUrl = new URL(anchor.href, window.location.origin);
                if (targetUrl.origin !== window.location.origin) return;

                // Jika hanya mengganti hash di halaman yang sama, jangan intersep
                if (targetUrl.pathname === window.location.pathname && targetUrl.search === window.location.search && targetUrl.hash) {
                    return;
                }

                // Jangan navigasi ulang ke URL yang persis sama
                if (targetUrl.href === window.location.href) {
                    return;
                }

                e.preventDefault();
                mainContent.classList.add('is-exiting');

                setTimeout(function () {
                    window.location.href = targetUrl.href;
                }, 110);
            } catch (err) {
                // Abaikan kesalahan parsing URL dan biarkan aksi default
            }
        });
    }

    // Inisialisasi saat DOM siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPageTransitions);
    } else {
        initPageTransitions();
    }
})();
