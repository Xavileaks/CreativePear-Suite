(function (root) {
    'use strict';
    function isoToDisplay(value) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
        return match ? match[3] + '/' + match[2] + '/' + match[1] : '';
    }
    function mask(value) {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return isoToDisplay(value);
        const digits = String(value).replace(/\D/g, '').slice(0, 8);
        return digits.slice(0, 2) + (digits.length > 2 ? '/' + digits.slice(2, 4) : '') + (digits.length > 4 ? '/' + digits.slice(4) : '');
    }
    function toISO(value) {
        const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);
        if (!match) return '';
        const day = +match[1], month = +match[2], year = +match[3];
        if (!year || month < 1 || month > 12 || day < 1 || day > 31) return '';
        const date = new Date(0);
        date.setUTCFullYear(year, month - 1, day);
        return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day ? match[3] + '-' + match[2] + '-' + match[1] : '';
    }
    if (typeof module === 'object' && module.exports) module.exports = { mask, toISO, isoToDisplay };
    if (!root.document) return;
    const doc = root.document, settings = root.xwDateSettings || {};
    const selector = '.elementor-widget-form .elementor-form input.elementor-date-field.elementor-use-native[type="date"], .elementor-widget-form .elementor-form .elementor-field-type-date input.flatpickr-mobile[type="date"]';
    const states = new WeakMap();
    let sequence = 0;

    function enhance(native) {
        if (states.has(native) || native.disabled || native.readOnly || native.closest('.elementor-edit-area-active')) return;
        const form = native.closest('form'), group = native.closest('.elementor-field-type-date');
        if (!form || !group) return;
        const source = native.classList.contains('flatpickr-mobile') ? group.querySelector('.elementor-date-field:not(.flatpickr-mobile)') : native;
        if (!source) return;
        const editor = doc.createElement('input'), wrap = doc.createElement('span');
        const computed = root.getComputedStyle(native);
        wrap.className = 'xw-date-wrap';
        wrap.style.setProperty('--xw-date-color', computed.color);
        editor.type = 'text';
        editor.id = (source.id || 'xw-date') + '-numeric-' + (++sequence);
        editor.className = native.className.split(/\s+/).filter(name => !/^(elementor-date-field|elementor-use-native|flatpickr-input|flatpickr-mobile|active)$/.test(name)).join(' ') + ' xw-date-editor';
        editor.inputMode = 'numeric';
        editor.autocomplete = 'off';
        editor.placeholder = settings.placeholder || 'dd/mm/yyyy';
        editor.maxLength = 10;
        editor.setAttribute('data-xw-date-editor', '');
        for (const property of ['font', 'letter-spacing', 'text-align', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right', 'border-width', 'border-style', 'box-sizing']) {
            editor.style.setProperty(property, computed.getPropertyValue(property));
        }
        // No name: only the original native/Flatpickr field is submitted.
        for (const attribute of ['aria-label', 'aria-labelledby', 'aria-describedby']) {
            if (source.hasAttribute(attribute)) editor.setAttribute(attribute, source.getAttribute(attribute));
        }
        const labels = Array.from(form.querySelectorAll('label')).filter(label => label.htmlFor === source.id || label.htmlFor === native.id);
        const originalLabels = labels.map(label => [label, label.htmlFor]);
        labels.forEach(label => { label.htmlFor = editor.id; });
        const nativeAriaLabel = native.getAttribute('aria-label');
        native.setAttribute('aria-label', (settings.calendar || 'Calendar') + ': ' + (source.getAttribute('aria-label') || labels.map(label => label.textContent.trim()).join(' ') || editor.placeholder));
        // Keep native input keyboard-reachable as the unchanged calendar control.
        // Keep native date input in place and visible at the icon, never replace the picker.
        native.before(wrap); wrap.append(native, editor);
        native.classList.add('xw-date-native');
        let syncing = false;
        function syncAttributes() {
            editor.disabled = native.disabled || source.disabled;
            editor.readOnly = native.readOnly;
            editor.required = source.required;
        }
        function fromPicker() {
            if (syncing) return;
            editor.value = isoToDisplay(native.value);
            editor.setCustomValidity(native.validationMessage || '');
        }
        function toPicker() {
            if (editor.disabled || editor.readOnly) return;
            const value = editor.value, iso = toISO(value);
            syncing = true;
            native.value = iso;
            const nativeError = native.validationMessage;
            // Flatpickr's own change listener synchronizes its original named field.
            native.dispatchEvent(new Event('input', { bubbles: true }));
            native.dispatchEvent(new Event('change', { bubbles: true }));
            syncing = false;
            editor.setCustomValidity(value && !iso ? (settings.invalid || 'Enter a valid date in dd/mm/yyyy format.') : (nativeError || (iso && native.value !== iso ? (settings.unavailable || 'This date is not available.') : '')));
        }
        editor.addEventListener('input', function () {
            const caret = editor.selectionStart;
            const before = editor.value;
            const digitsBefore = before.slice(0, caret).replace(/\D/g, '').length;
            editor.value = mask(before);
            let next = 0, digits = 0;
            while (next < editor.value.length && digits < digitsBefore) { if (/\d/.test(editor.value[next])) digits++; next++; }
            editor.setSelectionRange(next, next);
            toPicker();
        });
        editor.addEventListener('change', toPicker);
        native.addEventListener('input', fromPicker);
        native.addEventListener('change', fromPicker);
        function onInvalid(event) { event.preventDefault(); editor.setCustomValidity(native.validationMessage || settings.invalid || 'Invalid date'); editor.reportValidity(); }
        function onReset() { root.setTimeout(function () {
            // Flatpickr's original hidden field, not its auxiliary mobile control,
            // is authoritative after the browser resets the form.
            if (source !== native && (!source.value || /^\d{4}-\d{2}-\d{2}$/.test(source.value))) {
                native.value = source.value;
                native.dispatchEvent(new Event('change', { bubbles: true }));
            }
            fromPicker();
        }, 0); }
        function onSubmit(event) {
            toPicker();
            if (!editor.checkValidity()) { event.preventDefault(); event.stopImmediatePropagation(); editor.reportValidity(); }
        }
        native.addEventListener('invalid', onInvalid);
        form.addEventListener('reset', onReset);
        form.addEventListener('submit', onSubmit, true);
        const observers = [new MutationObserver(syncAttributes)];
        observers[0].observe(native, { attributes: true, attributeFilter: ['disabled', 'readonly', 'required', 'min', 'max', 'step'] });
        if (source !== native) { const observer = new MutationObserver(syncAttributes); observer.observe(source, { attributes: true, attributeFilter: ['disabled', 'required'] }); observers.push(observer); }
        syncAttributes(); fromPicker();
        states.set(native, { cleanup() {
            observers.forEach(observer => observer.disconnect());
            native.removeEventListener('input', fromPicker); native.removeEventListener('change', fromPicker); native.removeEventListener('invalid', onInvalid);
            form.removeEventListener('reset', onReset); form.removeEventListener('submit', onSubmit, true);
            originalLabels.forEach(([label, target]) => { if (label.htmlFor === editor.id) label.htmlFor = target; });
            native.classList.remove('xw-date-native');
            if (nativeAriaLabel === null) native.removeAttribute('aria-label'); else native.setAttribute('aria-label', nativeAriaLabel);
            if (wrap.isConnected) wrap.remove();
            states.delete(native);
        } });
    }
    function scan(scope) {
        if (scope.nodeType === 1 && scope.matches(selector)) enhance(scope);
        if (scope.querySelectorAll) scope.querySelectorAll(selector).forEach(enhance);
    }
    function start() {
        scan(doc);
        new MutationObserver(records => records.forEach(record => {
            if (record.type === 'attributes') { scan(record.target); return; }
            record.removedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                const inputs = node.matches('input.xw-date-native') ? [node] : Array.from(node.querySelectorAll('input.xw-date-native'));
                inputs.forEach(input => { if (!input.isConnected) states.get(input)?.cleanup(); });
            });
            record.addedNodes.forEach(node => { if (node.nodeType === 1) scan(node); });
        })).observe(doc.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'readonly', 'type'] });
    }
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start, { once: true }); else start();
})(typeof window === 'undefined' ? globalThis : window);
