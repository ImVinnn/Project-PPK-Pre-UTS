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

    /**
     * 2. The Discovery: Staggered Scroll Reveals
     * Memunculkan elemen secara bertahap (cascading) saat masuk ke viewport.
     */
    function initScrollReveals() {
        const revealElements = document.querySelectorAll('.fotel-reveal');
        if (!revealElements.length) return;

        if (prefersReducedMotion || !('IntersectionObserver' in window)) {
            revealElements.forEach(function (el) {
                el.classList.add('is-revealed');
            });
            return;
        }

        const observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    obs.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.08
        });

        revealElements.forEach(function (el) {
            observer.observe(el);
        });
    }

    /**
     * 4. The Waiting State: Skeleton Placeholders
     * Menampilkan skeleton shimmer seketika saat filter dikirim agar UI tetap stabil tanpa layout shift.
     */
    function initCatalogSkeletons() {
        const filterForm = document.getElementById('catalog-filter-form');
        const skeletonBox = document.getElementById('catalog-skeleton');
        const productsBox = document.getElementById('catalog-products');

        if (!skeletonBox || !productsBox) return;

        function showSkeletons() {
            productsBox.style.display = 'none';
            skeletonBox.style.display = 'flex';
        }

        if (filterForm) {
            filterForm.addEventListener('submit', function () {
                showSkeletons();
            });
        }

        // Tangkap juga klik pada strip kategori atau sel bento untuk transisi mulus
        document.querySelectorAll('.fotel-strip-item a, .fotel-bento-cell').forEach(function (link) {
            link.addEventListener('click', function () {
                const href = link.getAttribute('href');
                if (href && !link.classList.contains('active-strip')) {
                    showSkeletons();
                }
            });
        });

        // Pulihkan tampilan jika pengguna kembali via browser Back/Forward Cache
        window.addEventListener('pageshow', function () {
            skeletonBox.style.display = 'none';
            productsBox.style.display = '';
        });
    }

    /**
     * 5. The Tactile Metrics: Number Counter Animation
     * Menganimasikan angka dari 0 menuju target saat pengguna mendarat (landed) di halaman.
     */
    function initStatCounters() {
        if (prefersReducedMotion) return;

        const counterElements = document.querySelectorAll('[data-counter]');
        if (!counterElements.length) return;

        const duration = 1000; // ms

        counterElements.forEach(function (el) {
            const target = parseInt(el.getAttribute('data-counter'), 10);
            if (isNaN(target)) return;

            // Jika target 0, tetap tampilkan 0
            if (target === 0) {
                el.textContent = '0';
                return;
            }

            el.textContent = '0';
            let startTime = null;

            function updateCounter(timestamp) {
                if (!startTime) startTime = timestamp;
                const elapsed = timestamp - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Quartic ease-out curve untuk perlambatan yang halus dan presisi
                const easeOut = 1 - Math.pow(1 - progress, 4);
                const current = Math.round(easeOut * target);

                el.textContent = current;

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    el.textContent = target;
                }
            }

            // Jalankan setelah sedikit jeda (120ms) agar transisi halaman landing selesai dahulu
            setTimeout(function () {
                requestAnimationFrame(updateCounter);
            }, 120);
        });
    }

    // Inisialisasi saat DOM siap
    function init() {
        initPageTransitions();
        initScrollReveals();
        initCatalogSkeletons();
        initStatCounters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

