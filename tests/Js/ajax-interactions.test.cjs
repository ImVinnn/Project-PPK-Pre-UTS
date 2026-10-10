const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../public/js/ajax-interactions.js'), 'utf8');

function browser() {
    const listeners = {};
    const calls = [];
    const history = [];
    const scrolls = [];
    const main = {
        innerHTML: 'old page',
        setAttribute() {},
        contains: () => true,
        querySelector: () => null,
        querySelectorAll: () => [],
        prepend() {},
    };
    const status = { textContent: '' };
    const classList = { toggle() {} };
    const windowListeners = {};
    const window = {
        location: { href: 'http://localhost/', origin: 'http://localhost' },
        history: { pushState: (_, __, url) => {
            history.push(url);
            window.location.href = url;
        } },
        addEventListener: (name, callback) => { windowListeners[name] = callback; },
        scrollX: 0,
        scrollY: 0,
        scrollTo: (options) => { scrolls.push(options); },
    };
    const document = {
        documentElement: { classList },
        body: { classList: { remove() {} }, style: { removeProperty() {} } },
        title: 'Old title',
        getElementById: (id) => id === 'main-content' ? main : id === 'ajax-status' ? status : null,
        querySelector: () => null,
        querySelectorAll: () => [],
        addEventListener: (name, callback) => { listeners[name] = callback; },
        dispatchEvent() {},
        createElement: () => ({ setAttribute() {}, className: '', textContent: '' }),
    };
    class HTMLFormElement {}
    class FormData {
        constructor(form) { this.form = form; }
    }
    class DOMParser {
        parseFromString(html) {
            return {
                title: 'New title',
                querySelector: (selector) => selector === '#main-content' ? { innerHTML: html } : null,
            };
        }
    }
    const context = {
        window, document, HTMLFormElement, FormData, DOMParser,
        URL, URLSearchParams, Event: class Event {}, AbortController,
        fetch: async (url, options) => {
            calls.push({ url, options });
            return {
                ok: true,
                url,
                headers: { get: () => 'text/html; charset=utf-8' },
                text: async () => '<section>Loaded</section>',
            };
        },
    };
    vm.runInNewContext(source, context);
    return { listeners, windowListeners, calls, history, scrolls, main, window, HTMLFormElement };
}

async function settle() {
    await new Promise((resolve) => setImmediate(resolve));
}

test('internal catalog link is fetched and added to browser history', async () => {
    const app = browser();
    const link = {
        href: 'http://localhost/?tipe=alat',
        target: '',
        hasAttribute: () => false,
    };
    let prevented = false;
    app.listeners.click({
        target: { closest: (selector) => selector === 'a[href]' ? link : null },
        button: 0,
        defaultPrevented: false,
        preventDefault: () => { prevented = true; },
    });
    await settle();

    assert.equal(prevented, true);
    assert.equal(app.calls.length, 1);
    assert.equal(app.calls[0].options.method, undefined);
    assert.equal(app.calls[0].options.credentials, 'same-origin');
    assert.equal(app.main.innerHTML, '<section>Loaded</section>');
    assert.equal(app.history[0], 'http://localhost/?tipe=alat');
});

test('mutation submits FormData with cookies and does not submit twice', async () => {
    const app = browser();
    const form = new app.HTMLFormElement();
    form.method = 'POST';
    form.action = 'http://localhost/reservations';
    form.hasAttribute = () => false;
    const submitter = { disabled: false };
    let prevented = 0;
    const event = {
        target: form,
        submitter,
        defaultPrevented: false,
        preventDefault: () => { prevented++; },
    };
    app.listeners.submit(event);
    app.listeners.submit(event);
    await settle();

    assert.equal(prevented, 2);
    assert.equal(app.calls.length, 1);
    assert.equal(app.calls[0].options.method, 'POST');
    assert.equal(app.calls[0].options.credentials, 'same-origin');
    assert.equal(submitter.disabled, true);
});

test('browser Back reloads the current URL without adding history', async () => {
    const app = browser();
    app.window.location.href = 'http://localhost/reservations?page=2';
    app.windowListeners.popstate();
    await settle();

    assert.equal(app.calls.length, 1);
    assert.equal(app.history.length, 0);
    assert.equal(app.main.innerHTML, '<section>Loaded</section>');
});

test('CSV downloads and cancelled confirmation forms keep native behaviour', async () => {
    const app = browser();
    const download = {
        href: 'http://localhost/admin/recaps/export/places',
        target: '',
        hasAttribute: (name) => name === 'download',
    };
    app.listeners.click({
        target: { closest: (selector) => selector === 'a[href]' ? download : null },
        button: 0,
        defaultPrevented: false,
        preventDefault: () => assert.fail('CSV download must not be intercepted'),
    });

    const form = new app.HTMLFormElement();
    form.method = 'POST';
    form.action = 'http://localhost/reservations/1/cancel';
    form.hasAttribute = () => false;
    app.listeners.submit({
        target: form,
        defaultPrevented: true,
        preventDefault: () => assert.fail('Cancelled confirmation must not be submitted'),
    });
    await settle();
    assert.equal(app.calls.length, 0);
});

// -- Posisi gulir ---------------------------------------------------------

// Objek dari vm punya prototipe lain, jadi bandingkan lewat JSON.
const plain = (value) => JSON.parse(JSON.stringify(value));

function linkTo(href, extra = {}) {
    return { href, target: '', hasAttribute: () => false, closest: () => null, ...extra };
}

function clickLink(app, link) {
    app.listeners.click({
        target: { closest: (selector) => selector === 'a[href]' ? link : null },
        button: 0,
        defaultPrevented: false,
        preventDefault() {},
    });
}

function formFor(app, method, action, extra = {}) {
    const form = new app.HTMLFormElement();
    form.method = method;
    form.action = action;
    form.hasAttribute = () => false;
    form.closest = () => null;
    return Object.assign(form, extra);
}

test('GET form on the same page (filter) keeps the scroll position', async () => {
    const app = browser();
    app.window.scrollY = 480;
    app.window.scrollX = 12;
    app.listeners.submit({
        target: formFor(app, 'GET', 'http://localhost/'),
        defaultPrevented: false,
        preventDefault() {},
    });
    await settle();

    assert.equal(app.calls.length, 1);
    assert.deepEqual(plain(app.scrolls), [{ left: 12, top: 480, behavior: 'instant' }]);
});

test('GET form to another page scrolls to the top', async () => {
    const app = browser();
    app.window.scrollY = 480;
    app.listeners.submit({
        target: formFor(app, 'GET', 'http://localhost/search'),
        defaultPrevented: false,
        preventDefault() {},
    });
    await settle();

    assert.deepEqual(plain(app.scrolls), [{ top: 0, behavior: 'auto' }]);
});

test('data-ajax-refresh keeps the scroll position and does not touch history', async () => {
    const app = browser();
    app.window.scrollY = 900;
    const button = { closest: (selector) => selector === '[data-ajax-refresh]' ? button : null };
    app.listeners.click({ target: button, preventDefault() {} });
    await settle();

    assert.equal(app.calls.length, 1);
    assert.equal(app.history.length, 0);
    assert.deepEqual(plain(app.scrolls), [{ left: 0, top: 900, behavior: 'instant' }]);
});

test('links to other pages, including pagination, scroll to the top', async () => {
    const app = browser();
    app.window.scrollY = 700;
    clickLink(app, linkTo('http://localhost/reservations'));
    await settle();
    app.window.scrollY = 700;
    clickLink(app, linkTo('http://localhost/reservations?page=2'));
    await settle();

    assert.equal(app.calls.length, 2);
    assert.deepEqual(plain(app.scrolls), [{ top: 0, behavior: 'auto' }, { top: 0, behavior: 'auto' }]);
});

test('a link inside data-ajax-scroll="preserve" keeps the scroll position', async () => {
    const app = browser();
    app.window.scrollY = 350;
    clickLink(app, linkTo('http://localhost/?tipe=alat', {
        closest: (selector) => selector === '[data-ajax-scroll="preserve"]' ? {} : null,
    }));
    await settle();

    assert.deepEqual(plain(app.scrolls), [{ left: 0, top: 350, behavior: 'instant' }]);
});

test('POST scrolls to the top even when the server redirects back to the same URL', async () => {
    const app = browser();
    app.window.scrollY = 600;
    app.listeners.submit({
        target: formFor(app, 'POST', 'http://localhost/'),
        defaultPrevented: false,
        preventDefault() {},
    });
    await settle();

    assert.equal(app.calls[0].options.method, 'POST');
    assert.deepEqual(plain(app.scrolls), [{ top: 0, behavior: 'auto' }]);
});

test('Back/Forward does not scroll', async () => {
    const app = browser();
    app.windowListeners.popstate();
    await settle();

    assert.equal(app.scrolls.length, 0);
});

test('preserved scroll skips the staggered entrance by revealing new elements at once', async () => {
    const app = browser();
    const revealed = [];
    app.main.querySelectorAll = (selector) => selector === '.fotel-reveal'
        ? [{ classList: { add: (name) => revealed.push(name) } }, { classList: { add: (name) => revealed.push(name) } }]
        : [];
    app.window.scrollY = 200;
    app.listeners.submit({
        target: formFor(app, 'GET', 'http://localhost/'),
        defaultPrevented: false,
        preventDefault() {},
    });
    await settle();

    assert.deepEqual(revealed, ['is-revealed', 'is-revealed']);
});
