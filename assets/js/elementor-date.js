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
        if (year < 1000 || year > 3999 || month < 1 || month > 12 || day < 1 || day > 31) return '';
        const date = new Date(0);
        date.setUTCFullYear(year, month - 1, day);
        return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day ? match[3] + '-' + match[2] + '-' + match[1] : '';
    }
    function prefixError(value) {
        const digits = String(value).replace(/\D/g, '');
        if (digits.length && !/^[0-3]/.test(digits)) return 'day';
        if (digits.length >= 2 && (+digits.slice(0, 2) < 1 || +digits.slice(0, 2) > 31)) return 'day';
        if (digits.length >= 3 && !/[01]/.test(digits[2])) return 'month';
        if (digits.length >= 4 && (+digits.slice(2, 4) < 1 || +digits.slice(2, 4) > 12)) return 'month';
        if (digits.length >= 5 && !/[123]/.test(digits[4])) return 'year';
        if (digits.length >= 4 && +digits.slice(0, 2) > [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][+digits.slice(2, 4) - 1]) return 'invalid';
        if (digits.length === 8 && !toISO(mask(digits))) return 'invalid';
        return '';
    }
    function remainingFormat(value, template) { return value && value.length < 10 ? template.slice(value.length) : ''; }
    if (typeof module === 'object' && module.exports) module.exports = { mask, toISO, isoToDisplay, remainingFormat, prefixError };
    if (!root.document) return;
    const doc = root.document, settings = root.xwDateSettings || {};
    const selector = '.elementor-widget-form .elementor-form input.elementor-date-field.elementor-use-native[type="date"], .elementor-widget-form .elementor-form .elementor-field-type-date input.flatpickr-mobile[type="date"], .elementor-widget-form .elementor-form .elementor-field-type-date input.elementor-date-field.flatpickr-input[type="text"]';
    const states = new WeakMap();
    let sequence = 0;

    function enhance(native) {
        if (states.has(native) || native.disabled || native.readOnly || native.closest('.elementor-edit-area-active')) return;
        const form = native.closest('form'), group = native.closest('.elementor-field-type-date');
        if (!form || !group) return;
        const source = native.classList.contains('flatpickr-mobile') ? group.querySelector('.elementor-date-field:not(.flatpickr-mobile)') : native;
        if (!source) return;
        const flat = native.type === 'text', picker = flat ? native._flatpickr : null;
        if (flat && (!picker || picker.isMobile || picker.config.enableTime || picker.config.mode !== 'single')) return;
        const editor = doc.createElement('input'), wrap = doc.createElement('span'), hint = doc.createElement('span'), prefix = doc.createElement('span'), remainder = doc.createElement('span'), open = doc.createElement('button');
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
        hint.className = 'xw-date-format'; hint.setAttribute('aria-hidden', 'true'); prefix.className = 'xw-date-format-prefix'; hint.append(prefix, remainder);
        wrap.style.setProperty('--xw-date-placeholder-color', root.getComputedStyle(native, '::placeholder').color);
        for (const property of ['font', 'letter-spacing', 'text-align', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right', 'border-width', 'border-style', 'box-sizing']) {
            editor.style.setProperty(property, computed.getPropertyValue(property));
            hint.style.setProperty(property, computed.getPropertyValue(property));
        }
        // No name: only the original native/Flatpickr field is submitted.
        for (const attribute of ['aria-label', 'aria-labelledby', 'aria-describedby']) {
            if (source.hasAttribute(attribute)) editor.setAttribute(attribute, source.getAttribute(attribute));
        }
        const labels = Array.from(form.querySelectorAll('label')).filter(label => label.htmlFor === source.id || label.htmlFor === native.id);
        const originalLabels = labels.map(label => [label, label.htmlFor]);
        labels.forEach(label => { label.htmlFor = editor.id; });
        const nativeAriaLabel = native.getAttribute('aria-label');
        const nativeAriaHidden = native.getAttribute('aria-hidden'), nativeTabIndex = native.getAttribute('tabindex');
        const calendarLabel = (settings.calendar || 'Calendar') + ': ' + (source.getAttribute('aria-label') || labels.map(label => label.textContent.trim()).join(' ') || editor.placeholder);
        open.type = 'button'; open.className = 'xw-date-open'; open.setAttribute('aria-label', calendarLabel); open.setAttribute('aria-haspopup', 'dialog');
        // Same authored calendar icon as the Appointment field, not a replacement date picker.
        open.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/></svg>';
        native.setAttribute('aria-hidden', 'true'); native.tabIndex = -1;
        native.before(wrap); wrap.append(native, editor, hint, open);
        native.classList.add('xw-date-native');
        let syncing = false, lastAccepted = '', lastCaret = 0, lastEnd = 0;
        function errorMessage(key) { return settings[key] || settings.invalid || 'Enter a valid date: day 01–31, month 01–12, year 1000–3999.'; }
        function describeFormat() { prefix.textContent = editor.value; remainder.textContent = remainingFormat(editor.value, editor.placeholder); hint.hidden = !remainder.textContent; }
        function syncAttributes() {
            editor.disabled = native.disabled || source.disabled;
            editor.readOnly = native.readOnly;
            editor.required = source.required;
            open.disabled = editor.disabled || editor.readOnly;
        }
        function fromPicker() {
            if (syncing) return;
            editor.value = isoToDisplay(native.value) || (flat && native.value && picker.selectedDates[0] ? isoToDisplay(picker.formatDate(picker.selectedDates[0], 'Y-m-d')) : '');
            const error = prefixError(editor.value);
            editor.setCustomValidity(error ? errorMessage(error) : native.validationMessage || '');
            if (error) editor.setAttribute('aria-invalid', 'true'); else editor.removeAttribute('aria-invalid');
            lastAccepted = error ? '' : editor.value; lastCaret = lastEnd = lastAccepted.length;
            describeFormat();
        }
        function toPicker() {
            if (editor.disabled || editor.readOnly) return;
            const value = editor.value, iso = toISO(value);
            syncing = true;
            // Never let Flatpickr loosely parse partial numeric text as a year.
            if (flat) { if (iso) picker.setDate(iso, false, 'Y-m-d'); else if (native.value || picker.selectedDates.length) picker.clear(false); }
            else native.value = iso;
            const nativeError = native.validationMessage;
            // Flatpickr's own change listener synchronizes its original named field.
            native.dispatchEvent(new Event('input', { bubbles: true }));
            native.dispatchEvent(new Event('change', { bubbles: true }));
            syncing = false;
            const accepted = flat ? (picker.selectedDates[0] ? picker.formatDate(picker.selectedDates[0], 'Y-m-d') : '') : native.value;
            editor.setCustomValidity(value && !iso ? (settings.invalid || 'Enter a valid date in dd/mm/yyyy format.') : (iso && accepted !== iso ? (settings.unavailable || 'This date is not available.') : nativeError || ''));
            if (value.length === 10 && editor.validationMessage) editor.setAttribute('aria-invalid', 'true'); else editor.removeAttribute('aria-invalid');
            describeFormat();
        }
        open.addEventListener('click', function () {
            if (open.disabled) return;
            if (flat) picker.open();
            else { try { if (typeof native.showPicker === 'function') native.showPicker(); else native.click(); } catch (_) { native.click(); } }
        });
        editor.addEventListener('beforeinput', function () { lastCaret = editor.selectionStart; lastEnd = editor.selectionEnd; });
        editor.addEventListener('input', function (event) {
            const caret = editor.selectionStart;
            const before = editor.value;
            const digitsBefore = before.slice(0, caret).replace(/\D/g, '').length;
            const candidate = mask(before), error = prefixError(candidate);
            const deleting = (event.inputType || '').startsWith('delete');
            if (error && !deleting) {
                editor.value = lastAccepted; editor.setSelectionRange(lastCaret, lastEnd);
                editor.setCustomValidity(errorMessage(error)); editor.setAttribute('aria-invalid', 'true'); describeFormat(); editor.reportValidity(); return;
            }
            editor.value = candidate;
            let next = 0, digits = 0;
            while (next < editor.value.length && digits < digitsBefore) { if (/\d/.test(editor.value[next])) digits++; next++; }
            editor.setSelectionRange(next, next);
            lastAccepted = editor.value; lastCaret = lastEnd = next;
            toPicker();
            if (error) { editor.setCustomValidity(errorMessage(error)); editor.setAttribute('aria-invalid', 'true'); }
        });
        editor.addEventListener('change', toPicker);
        editor.addEventListener('blur', toPicker);
        native.addEventListener('input', fromPicker);
        native.addEventListener('change', fromPicker);
        function onInvalid(event) { event.preventDefault(); editor.setCustomValidity(native.validationMessage || settings.invalid || 'Invalid date'); editor.reportValidity(); }
        function onReset() { root.setTimeout(function () {
            if (!native.isConnected) return;
            // Flatpickr's original hidden field, not its auxiliary mobile control,
            // is authoritative after the browser resets the form.
            if (source !== native && (!source.value || /^\d{4}-\d{2}-\d{2}$/.test(source.value))) {
                native.value = source.value;
                native.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (flat) { if (/^\d{4}-\d{2}-\d{2}$/.test(native.value)) picker.setDate(native.value, false, 'Y-m-d'); else picker.clear(false); }
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
        const onOpen = () => open.setAttribute('aria-expanded', 'true'), onClose = () => { open.setAttribute('aria-expanded', 'false'); if (doc.activeElement === native) editor.focus(); };
        function cleanup(destroying = false) {
            states.delete(native);
            observers.forEach(observer => observer.disconnect());
            native.removeEventListener('input', fromPicker); native.removeEventListener('change', fromPicker); native.removeEventListener('invalid', onInvalid);
            form.removeEventListener('reset', onReset); form.removeEventListener('submit', onSubmit, true);
            originalLabels.forEach(([label, target]) => { if (label.htmlFor === editor.id) label.htmlFor = target; });
            native.classList.remove('xw-date-native');
            if (nativeAriaLabel === null) native.removeAttribute('aria-label'); else native.setAttribute('aria-label', nativeAriaLabel);
            if (nativeAriaHidden === null) native.removeAttribute('aria-hidden'); else native.setAttribute('aria-hidden', nativeAriaHidden);
            if (nativeTabIndex === null) native.removeAttribute('tabindex'); else native.setAttribute('tabindex', nativeTabIndex);
            if (flat) {
                for (const [hook, listener] of [['onValueUpdate', fromPicker], ['onChange', fromPicker], ['onOpen', onOpen], ['onClose', onClose], ...(!destroying ? [['onDestroy', onDestroy]] : [])]) {
                    const index = picker.config[hook].indexOf(listener); if (index !== -1) picker.config[hook].splice(index, 1);
                }
            }
            if (wrap.isConnected) { if (native.parentElement === wrap) wrap.replaceWith(native); else wrap.remove(); }
        }
        const onDestroy = () => cleanup(true);
        states.set(native, { cleanup });
        if (flat) {
            for (const [hook, listener] of [['onValueUpdate', fromPicker], ['onChange', fromPicker], ['onOpen', onOpen], ['onClose', onClose], ['onDestroy', onDestroy]]) picker.config[hook].push(listener);
            open.setAttribute('aria-expanded', 'false');
        }
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
        })).observe(doc.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'readonly', 'type', 'class'] });
    }
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start, { once: true }); else start();
})(typeof window === 'undefined' ? globalThis : window);
