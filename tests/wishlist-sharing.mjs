/** Pure JS regression checks; no browser or external sharing requests. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../assets/wishlist/wishlist.js', import.meta.url), 'utf8')
    .replace('async function init(root)', 'window.__wishlistTest = {share};\n    async function init(root)');
const listUrl = 'https://shop.example/wishlist/?xw_wishlist=' + 'a'.repeat(64);
function fixture({blocked = false, throws = false, error = false, invalid = false, shared = false, copyFails = false} = {}) {
    const opened = [], copied = [], operations = [];
    const status = {textContent: '', replaceChildren(input) { this.input = input; }};
    const button = {disabled: false, dataset: {}, setAttribute() {}, removeAttribute() {}};
    const root = {querySelector(selector) { return selector === '.xw-wl-title' ? {textContent: 'My wishlist & gifts'} : status; }};
    const window = {xwWishlist: {ajax: '/ajax', error: 'Error', copied: 'Copied'}, location: {href: shared ? listUrl : 'https://shop.example/wishlist/', origin: 'https://shop.example'},
        open(url, target) { if (throws) throw new Error('Popup denied'); opened.push({url, target}); return blocked ? null : {}; },
        dispatchEvent() {}, addEventListener() {}};
    const document = {readyState: 'loading', documentElement: {}, addEventListener() {}, querySelectorAll() { return []; },
        createElement() { return {setAttribute() {}, focus() {}, select() {}}; }};
    const context = vm.createContext({window, document, URL, URLSearchParams, CustomEvent: class {}, MutationObserver: class {observe() {}},
        navigator: {clipboard: {async writeText(url) { if (copyFails) throw new Error('Clipboard denied'); copied.push(url); }}},
        async fetch(_, options) {
            const operation = options.body.get('operation'); operations.push(operation);
            await new Promise(resolve => setImmediate(resolve));
            return {ok: !error, async json() { return error ? {success: false, data: {message: 'Share failed'}} : {success: true,
                data: operation === 'state' ? {items: [], count: 0, nonce: 'test'} : {url: invalid ? 'javascript:alert(1)' : listUrl}}; }};
        }});
    vm.runInContext(source, context);
    return {window, root, button, status, opened, copied, operations, share: window.__wishlistTest.share};
}
const services = {facebook: ['www.facebook.com', 'u'], x: ['twitter.com', 'url'], pinterest: ['pinterest.com', 'url'],
    whatsapp: ['api.whatsapp.com', 'text'], telegram: ['t.me', 'url'], linkedin: ['www.linkedin.com', 'url'], reddit: ['www.reddit.com', 'url']};
for (const [network, [host, key]] of Object.entries(services)) {
    const f = fixture(); f.button.dataset.xwWlShare = network;
    const task = f.share(f.root, f.button);
    assert.equal(f.opened.length, 0, 'No blank window while the link is generated');
    await task;
    assert.equal(f.opened.length, 1); const route = new URL(f.opened[0].url);
    assert.equal(route.hostname, host); assert.equal(route.searchParams.get(key), listUrl);
    assert.equal(f.button.disabled, false);
    console.log(`PASS: ${network} opens its final route with the read-only wishlist link`);
}
for (const options of [{blocked: true}, {throws: true}]) {
    const f = fixture(options); f.button.dataset.xwWlShare = 'telegram'; await f.share(f.root, f.button);
    assert.equal(new URL(f.window.location.href).hostname, 't.me'); console.log('PASS: popup refusal navigates to the chosen service');
}
for (const options of [{error: true}, {invalid: true}]) {
    const f = fixture(options); f.button.dataset.xwWlShare = 'facebook'; await f.share(f.root, f.button);
    assert.equal(f.opened.length, 0); assert.ok(f.status.textContent); console.log('PASS: failures show feedback without an empty tab');
}
const shared = fixture({shared: true}); shared.button.dataset.xwWlShare = 'telegram'; await shared.share(shared.root, shared.button);
assert.equal(shared.operations.length, 0); assert.equal(new URL(shared.opened[0].url).searchParams.get('url'), listUrl);
console.log('PASS: a shared wishlist forwards the same link, not the visitor wishlist');
const email = fixture(); email.button.dataset.xwWlShare = 'email'; await email.share(email.root, email.button);
assert.equal(new URL(email.window.location.href).protocol, 'mailto:'); assert.equal(new URL(email.window.location.href).searchParams.get('body'), listUrl);
const copy = fixture(); copy.button.dataset.xwWlShare = 'copy'; await copy.share(copy.root, copy.button);
assert.equal(copy.copied[0], listUrl); assert.equal(copy.status.textContent, 'Copied');
const fallback = fixture({copyFails: true}); fallback.button.dataset.xwWlShare = 'copy'; await fallback.share(fallback.root, fallback.button);
assert.equal(fallback.status.input.value, listUrl);
console.log('PASS: email, clipboard and manual copy fallback');
