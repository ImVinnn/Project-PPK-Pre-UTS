/**
 * Progressive AJAX navigation for the application content.
 * Every link and form still has a working server-rendered fallback without JS.
 */
(function () {
    'use strict';

    const main = document.getElementById('main-content');
    const status = document.getElementById('ajax-status');
    if (!main) return;

    let navigationController = null;
    let mutationPending = false;

    function announce(message) {
        if (status) status.textContent = message;
    }

    function setBusy(isBusy) {
        main.setAttribute('aria-busy', String(isBusy));
        document.documentElement.classList.toggle('ajax-loading', isBusy);
        if (!isBusy) {
            const skeleton = document.getElementById('catalog-skeleton');
            const products = document.getElementById('catalog-products');
            if (skeleton) skeleton.style.display = 'none';
            if (products) products.style.display = '';
        }
        announce(isBusy ? 'Memuat halaman…' : 'Halaman siap.');
    }

    function showError(message) {
        const old = main.querySelector('#ajax-error');
        if (old) old.remove();

        const alert = document.createElement('div');
        alert.id = 'ajax-error';
        alert.className = 'alert alert-danger m-3';
        alert.setAttribute('role', 'alert');
        alert.textContent = message;
        main.prepend(alert);
        announce(message);
    }

    async function runPageScripts() {
        for (const oldScript of main.querySelectorAll('script')) {
            const script = document.createElement('script');
            for (const attribute of oldScript.attributes) {
                script.setAttribute(attribute.name, attribute.value);
            }
            if (oldScript.src) {
                // Await scripts that initialise controls on the newly rendered page.
                await new Promise((resolve, reject) => {
                    script.addEventListener('load', resolve, { once: true });
                    script.addEventListener('error', reject, { once: true });
                    oldScript.replaceWith(script);
                });
            } else {
                script.textContent = oldScript.textContent;
                oldScript.replaceWith(script);
            }
        }
    }

    const PRESERVE_SELECTOR = '[data-ajax-scroll="preserve"]';

    // 'preserve' keeps the scroll position; anything else scrolls to the top.
    function scrollModeFor(...elements) {
        return elements.some((el) => el && el.closest && el.closest(PRESERVE_SELECTOR)) ? 'preserve' : 'top';
    }

    async function render(response, historyMode, scrollMode = 'top') {
        const contentType = response.headers.get('Content-Type') || '';
        if (!response.ok || !contentType.includes('text/html')) {
            throw new Error('Server tidak mengembalikan halaman yang dapat ditampilkan.');
        }

        const destination = new URL(response.url, window.location.href);
        if (destination.origin !== window.location.origin) {
            throw new Error('Tujuan berada di luar aplikasi.');
        }

        const documentFromServer = new DOMParser().parseFromString(await response.text(), 'text/html');
        const newMain = documentFromServer.querySelector('#main-content');
        if (!newMain) {
            throw new Error('Halaman yang diminta tidak tersedia.');
        }

        // Dismiss an open Bootstrap modal before removing its element/backdrop.
        if (window.bootstrap) {
            document.querySelectorAll('.modal.show').forEach((modal) => {
                window.bootstrap.Modal.getInstance(modal)?.hide();
            });
        }
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        const savedScroll = scrollMode === 'preserve'
            ? { left: window.scrollX || 0, top: window.scrollY || 0 }
            : null;

        main.innerHTML = newMain.innerHTML;
        // Skip the staggered entrance when the position is kept, so content does not jump.
        if (savedScroll) {
            main.querySelectorAll('.fotel-reveal').forEach((el) => el.classList.add('is-revealed'));
        }
        const newHeader = documentFromServer.querySelector('header.fotel-header');
        const header = document.querySelector('header.fotel-header');
        if (newHeader && header) header.innerHTML = newHeader.innerHTML;
        const newFooter = documentFromServer.querySelector('footer.fotel-footer');
        const footer = document.querySelector('footer.fotel-footer');
        if (newFooter && footer) footer.innerHTML = newFooter.innerHTML;
        document.title = documentFromServer.title;

        const changedLocation = destination.href !== window.location.href;
        if (historyMode === 'push' && changedLocation) {
            window.history.pushState({}, '', destination.href);
        }

        await runPageScripts();
        document.dispatchEvent(new Event('sora:page-updated'));
        if (savedScroll) {
            window.scrollTo({ ...savedScroll, behavior: 'instant' });
        } else if (historyMode === 'push') {
            window.scrollTo({ top: 0, behavior: 'auto' });
        }
    }

    async function navigate(url, historyMode = 'push', scrollMode = 'top') {
        if (mutationPending) return;
        if (navigationController) navigationController.abort();
        const controller = new AbortController();
        navigationController = controller;
        setBusy(true);

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                cache: 'no-store',
                signal: controller.signal,
            });
            if (navigationController !== controller) return;
            await render(response, historyMode, scrollMode);
        } catch (error) {
            if (error.name !== 'AbortError') {
                showError('Gagal memuat data. Periksa koneksi lalu coba lagi.');
            }
        } finally {
            if (navigationController === controller) {
                navigationController = null;
                setBusy(false);
            }
        }
    }

    async function submit(form, submitter) {
        if (mutationPending) return;
        const method = (form.method || 'GET').toUpperCase();
        const data = submitter ? new FormData(form, submitter) : new FormData(form);
        const url = new URL(form.action, window.location.href);

        const explicit = scrollModeFor(form, submitter);

        if (method === 'GET') {
            url.search = new URLSearchParams(data).toString();
            // Same page, only the query changes (filters): keep the position.
            const current = new URL(window.location.href);
            const samePage = url.origin === current.origin && url.pathname === current.pathname;
            await navigate(url.href, 'push', explicit === 'preserve' || samePage ? 'preserve' : 'top');
            return;
        }

        mutationPending = true;
        if (submitter) submitter.disabled = true;
        setBusy(true);
        try {
            const response = await fetch(url.href, {
                method,
                body: data,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            });
            await render(response, 'push', explicit);
        } catch (error) {
            // Never repeat a POST automatically: the server may already have saved it.
            showError('Hasil tindakan belum dapat dipastikan. Periksa data sebelum mencoba lagi.');
        } finally {
            mutationPending = false;
            if (submitter && submitter.isConnected) submitter.disabled = false;
            setBusy(false);
        }
    }

    document.addEventListener('click', (event) => {
        const refresh = event.target.closest('[data-ajax-refresh]');
        if (refresh && main.contains(refresh)) {
            event.preventDefault();
            navigate(window.location.href, 'none', 'preserve');
            return;
        }

        const link = event.target.closest('a[href]');
        if (!link || !main.contains(link) || event.defaultPrevented ||
            event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
            link.hasAttribute('download') || link.hasAttribute('data-no-ajax') ||
            (link.target && link.target !== '_self')) return;

        const destination = new URL(link.href, window.location.href);
        if (!['http:', 'https:'].includes(destination.protocol) ||
            destination.origin !== window.location.origin ||
            destination.href === window.location.href ||
            (destination.pathname === window.location.pathname &&
                destination.search === window.location.search && destination.hash)) return;

        event.preventDefault();
        navigate(destination.href, 'push', scrollModeFor(link));
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !main.contains(form) ||
            event.defaultPrevented || form.hasAttribute('data-no-ajax') ||
            (form.target && form.target !== '_self')) return;

        event.preventDefault();
        submit(form, event.submitter);
    });

    window.addEventListener('popstate', () => navigate(window.location.href, 'none'));
    window.SoraAjax = {
        navigate,
        refresh: () => navigate(window.location.href, 'none', 'preserve'),
    };
})();
