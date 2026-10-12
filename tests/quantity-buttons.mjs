import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source = fs.readFileSync(new URL('../assets/quantity-buttons/quantity-buttons.js', import.meta.url), 'utf8');
let ready;
const events = {};
const bodyEvents = {};
let observed;
const nodes = [];
const document = {
    body: { classList: { active: false, add() { this.active = true; }, remove() { this.active = false; } } },
    querySelectorAll() { return nodes; },
    createElement() { return { dataset: {}, attributes: {}, setAttribute(k, v) { this.attributes[k] = v; } }; },
    addEventListener(name, fn) { events[name] = fn; },
};
const window = { requestAnimationFrame(fn) { fn(); }, xwQuantityButtons: { minus: 'Less', plus: 'More' } };
const $ = target => {
    if (typeof target === 'function') { ready = target; return; }
    return { on(names, fn) { names.split(' ').forEach(n => { bodyEvents[n] = fn; }); } };
};
class Observer { constructor(fn) { observed = fn; } observe() {} }
// Expose local closure helpers only in this VM, never in the shipped script.
vm.runInNewContext(source.replace('    $(function () {', '    window.qa = { next: next };\n    $(function () {'), {
    jQuery: $, document, window, MutationObserver: Observer, Event: class { constructor(type) { this.type = type; } },
});
function input(value = '1', attrs = {}) {
    const buttons = [];
    const container = {
        classList: { active: false, contains() { return this.active; }, add() { this.active = true; } },
        querySelector(selector) { return selector === 'input.qty' ? field : attrs.existingButton ? {} : null; },
        querySelectorAll() { return buttons; }, closest() { return attrs.originalPlugin ? {} : null; },
        insertBefore(button) { buttons.push(button); },
    };
    const dispatched = [];
    const field = {
        value, type: attrs.type || 'number', id: '', disabled: Boolean(attrs.disabled), readOnly: Boolean(attrs.readOnly),
        getAttribute(key) { return attrs[key] ?? null; },
        closest(selector) { return selector === '.wc-block-components-quantity-selector' ? attrs.block ? {} : null : container; },
        matches() { return true; },
        insertAdjacentElement(where, button) { buttons.push(button); },
        dispatchEvent(event) { dispatched.push(event.type); events[event.type]?.({ target: field }); },
    };
    return { field, attrs, buttons, container, dispatched };
}
function click(item, index) {
    const button = item.buttons[index];
    events.click({ target: { closest() { button.closest = () => item.container; return button; } }, preventDefault() {} });
}
const next = (value, attrs, direction) => window.qa.next(input(value, attrs).field, direction);
assert.equal(next('1', { min: '1', max: '3', step: '1' }, 1), 2);
assert.equal(next('3', { min: '1', max: '3' }, 1), 3);
assert.equal(next('1', { min: '1' }, -1), 1);
assert.equal(next('1', { min: '0' }, -1), 0);
assert.equal(next('20', { min: '1' }, 1), 21);
assert.equal(next('0.2', { min: '0', step: '0.1' }, 1), 0.3);
assert.equal(next('3', { min: '1', max: '4', step: '2' }, 1), 3);
assert.equal(next('2', { min: '1', step: '2' }, 1), 3);
assert.equal(next('2', { min: '1', step: '2' }, -1), 1);
assert.equal(next('', { min: '1' }, 1), 1);
assert.equal(next('', { min: '0' }, 1), 1);
assert.equal(next('', { min: '0' }, -1), 0);
assert.equal(next('1', { min: '3', max: '2' }, 1), 1);
console.log('PASS: min/max, unbounded, empty, zero, off-grid and fractional steps stay valid');
const item = input('1', { min: '1', max: '3', step: '1' }); nodes.push(item.field); ready();
assert.equal(item.buttons.length, 2); assert.equal(item.buttons[0].disabled, true);
assert.equal(item.buttons[1].attributes['aria-label'], 'More');
assert.equal(item.buttons[1].attributes['aria-controls'], item.field.id);
click(item, 1); assert.equal(item.field.value, '2'); assert.deepEqual(item.dispatched, ['input', 'change']);
click(item, 1); assert.equal(item.field.value, '3'); assert.equal(item.buttons[1].disabled, true);
click(item, 1); assert.equal(item.field.value, '3'); assert.equal(item.dispatched.length, 4);
click(item, 0); assert.equal(item.field.value, '2');
observed(); bodyEvents.updated_wc_div(); assert.equal(item.buttons.length, 2);
console.log('PASS: accessible controls, one increment per click and native change events without duplicate buttons');
const dynamic = input('1', { min: '1', max: '5' }); nodes.push(dynamic.field); observed(); assert.equal(dynamic.buttons.length, 2);
dynamic.attrs.max = '1'; observed(); assert.equal(dynamic.buttons[1].disabled, true);
dynamic.attrs.max = '5'; bodyEvents.found_variation(); assert.equal(dynamic.buttons[1].disabled, false);
dynamic.field.disabled = true; observed(); assert.equal(dynamic.buttons[1].disabled, true);
console.log('PASS: AJAX replacement, variation stock changes and disabled inputs are synchronized');
for (const attrs of [{ type: 'hidden' }, { readOnly: true }, { disabled: true }, { min: '1', max: '1' }, { existingButton: true }, { originalPlugin: true }, { block: true }]) {
    const skipped = input('1', attrs); nodes.push(skipped.field); observed(); assert.equal(skipped.buttons.length, 0);
}
console.log('PASS: hidden, fixed quantities, readonly, native blocks and existing theme/plugin controls are preserved');
events.keydown({ key: 'Tab' }); assert.equal(document.body.classList.active, true);
events.pointerdown(); assert.equal(document.body.classList.active, false);
events.keydown({ key: 'ArrowUp' }); assert.equal(document.body.classList.active, true);
console.log('PASS: keyboard navigation keeps a focus indicator; pointer interaction removes the black ring');
