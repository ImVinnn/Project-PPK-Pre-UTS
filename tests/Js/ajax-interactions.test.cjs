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
        scrollTo() {},
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
    return { listeners, windowListeners, calls, history, main, window, HTMLFormElement };
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
