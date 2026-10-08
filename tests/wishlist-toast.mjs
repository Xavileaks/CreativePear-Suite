/** Isolated DOM/timer regression checks, without browser or network access. */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

class Element {
    constructor() { this.children = []; this.attributes = {}; this.events = {}; this.textContent = ''; }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(key, value) { this.events[key] = value; }
    contains(element) { return element === this || this.children.some(child => child.contains(element)); }
    append(...children) { children.forEach(child => { child.parent = this; this.children.push(child); }); }
    remove() { this.parent.children = this.parent.children.filter(child => child !== this); }
}
const body = new Element(), status = new Element(), focus = {focus() { this.focused = true; }};
const timers = new Map(); let timerId = 0;
const root = {querySelector(selector) { return selector === '.xw-wl-status' ? status : focus; }};
const source = fs.readFileSync(new URL('../assets/wishlist/wishlist.js', import.meta.url), 'utf8')
    .replace('async function init(root)', 'window.__toastTest = {cartToast};\n    async function init(root)');
const window = {xwWishlist: {dismiss: 'Dismiss message'}, location: {href: 'https://shop.example/', origin: 'https://shop.example'}, addEventListener() {}};
const context = vm.createContext({window, document: {body, readyState: 'loading', documentElement: {}, addEventListener() {}, createElement() { return new Element(); }},
    URL, URLSearchParams, MutationObserver: class {observe() {}},
    setTimeout(callback, delay) { timers.set(++timerId, {callback, delay}); return timerId; }, clearTimeout(id) { timers.delete(id); }});
vm.runInContext(source, context);
const toast = window.__toastTest.cartToast;
status.textContent = 'Old inline message';
toast(root, '1 product added to cart.');
assert.equal(status.textContent, ''); assert.equal(body.children.length, 1);
const notice = body.children[0];
assert.equal(notice.attributes.role, 'status'); assert.equal(notice.attributes['aria-live'], 'polite');
assert.equal(notice.children[0].textContent, '1 product added to cart.');
assert.equal(notice.children[1].attributes['aria-label'], 'Dismiss message');
assert.equal(timers.get(timerId).delay, 5000);
timers.get(timerId).callback(); assert.equal(body.children.length, 0);
console.log('PASS: floating accessible notification clears inline message and closes at 5000ms');
toast(root, 'First addition'); const oldTimer = timerId;
toast(root, 'Second addition'); assert.equal(body.children.length, 1); assert.equal(timers.has(oldTimer), false);
assert.equal(body.children[0].children[0].textContent, 'Second addition');
assert.equal(timers.get(timerId).delay, 5000);
console.log('PASS: repeated actions replace the toast and restart the five-second timer');
body.children[0].children[1].events.click(); assert.equal(body.children.length, 0);
assert.equal(timers.size, 0); assert.equal(focus.focused, true);
console.log('PASS: manual dismissal clears timer and restores wishlist focus');
