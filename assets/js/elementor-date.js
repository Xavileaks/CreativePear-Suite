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
    function offStep(iso, base, step) {
        if (step === 'any') return false;
        const size = Number(step) > 0 ? Number(step) : 1;
        const parsed = Date.parse(base + 'T00:00:00Z'), origin = Number.isFinite(parsed) ? parsed : Date.UTC(1970, 0, 1);
        const distance = (Date.parse(iso + 'T00:00:00Z') - origin) / 86400000 / size;
        return Math.abs(distance - Math.round(distance)) > 1e-8;
    }
    if (typeof module === 'object' && module.exports) module.exports = { mask, toISO, isoToDisplay, remainingFormat, prefixError, offStep };
    if (!root.document) return;
    const doc = root.document, settings = root.xwDateSettings || {};
    const selector = '.elementor-widget-form .elementor-form input.elementor-date-field.elementor-use-native[type="date"], .elementor-widget-form .elementor-form .elementor-field-type-date input.flatpickr-mobile[type="date"], .elementor-widget-form .elementor-form .elementor-field-type-date input.elementor-date-field.flatpickr-input[type="text"]';
    const states = new WeakMap();
    let sequence = 0;
    const spanish = {
        firstDayOfWeek: 1, weekdays: { shorthand: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'], longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'] },
        months: { shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] },
        rangeSeparator: ' a ', scrollTitle: 'Desplazar para cambiar', toggleTitle: 'Pulsar para cambiar', monthAriaLabel: 'Mes', yearAriaLabel: 'Año', time_24hr: true,
    };
    function element(tag, className, text) {
        const node = doc.createElement(tag); node.className = className;
        if (text) node.textContent = text;
        return node;
    }
    // Same approved two-wheel interaction as Cita, scoped and owned by the Date feature.
    function dateCalendar(picker, anchor, returnFocus) {
        const calendar = picker.calendarContainer, hooks = [];
        if (!calendar || calendar.classList.contains('xw-date-calendar')) return () => {};
        calendar.classList.add('xw-date-calendar');
        const oldId = calendar.id, oldRole = calendar.getAttribute('role'), oldLabel = calendar.getAttribute('aria-label');
        const oldLocale = picker.config.locale;
        if (settings.locale === 'es') picker.set('locale', spanish);
        calendar.id ||= 'xw-date-calendar-' + (++sequence);
        calendar.setAttribute('role', 'dialog'); calendar.setAttribute('aria-label', settings.calendar || 'Calendar');
        const toolbar = element('div', 'xw-date-toolbar'), title = element('strong', 'xw-date-title', settings.chooseDay || 'Choose a day');
        const close = element('button', 'xw-date-close'); close.type = 'button'; close.setAttribute('aria-label', settings.close || 'Close');
        close.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
        toolbar.append(title, close); calendar.prepend(toolbar);
        const heading = element('button', 'xw-date-period-toggle'); heading.type = 'button'; heading.setAttribute('aria-label', settings.choosePeriod || 'Choose month and year');
        const oldHeading = picker.currentYearElement.parentElement.parentElement, wasHidden = oldHeading.hidden;
        oldHeading.hidden = true; picker.monthNav.append(heading);
        const period = element('div', 'xw-date-period'); period.hidden = true; period.id = calendar.id + '-period';
        period.setAttribute('role', 'group'); period.setAttribute('aria-label', settings.period || 'Month and year'); heading.setAttribute('aria-controls', period.id);
        calendar.append(period);
        let wheelYear, monthWheel, yearWheel;
        function label() { heading.textContent = picker.l10n.months.longhand[picker.currentMonth] + ' ' + picker.currentYear; heading.setAttribute('aria-description', heading.textContent); }
        function days() { period.hidden = true; calendar.classList.remove('xw-date-period-view'); heading.setAttribute('aria-expanded', 'false'); title.textContent = settings.chooseDay || 'Choose a day'; label(); }
        function wheel(labelText, key, onChange) {
            const node = element('div', 'xw-date-wheel'); node.setAttribute('role', 'listbox'); node.setAttribute('aria-label', labelText); node.tabIndex = 0;
            let items = [], active = -1;
            function read() {
                const height = node.firstElementChild?.getBoundingClientRect().height || 44;
                const index = Math.max(0, Math.min(items.length - 1, Math.round(node.scrollTop / height)));
                if (index !== active && items[index]) {
                    node.children[active]?.setAttribute('aria-selected', 'false'); active = index; node.children[index].setAttribute('aria-selected', 'true');
                    node.setAttribute('aria-activedescendant', node.children[index].id); onChange?.(items[index].value);
                }
                return items[index]?.value;
            }
            function select(index) { node.scrollTop = Math.max(0, Math.min(items.length - 1, index)) * (node.firstElementChild?.getBoundingClientRect().height || 44); read(); }
            function render(next, value) {
                items = next; active = -1; node.replaceChildren();
                items.forEach((item, index) => {
                    const option = element('div', 'xw-date-wheel-option', item.label); option.id = period.id + '-' + key + '-' + item.value; option.setAttribute('role', 'option'); option.setAttribute('aria-selected', 'false');
                    option.addEventListener('click', () => { select(index); node.focus({ preventScroll: true }); }); node.append(option);
                });
                select(Math.max(0, items.findIndex(item => item.value === value)));
            }
            node.addEventListener('scroll', read, { passive: true });
            node.addEventListener('keydown', event => {
                const index = Math.max(0, items.findIndex(item => item.value === read()));
                const next = { ArrowDown: index + 1, ArrowUp: index - 1, Home: 0, End: items.length - 1, PageDown: index + 3, PageUp: index - 3 }[event.key];
                if (next !== undefined) { event.preventDefault(); select(next); }
            });
            return { node, read, render };
        }
        function bounds() {
            return [picker.config.minDate && picker.config.minDate.getFullYear() >= 1000 ? picker.config.minDate : new Date(1000, 0, 1), picker.config.maxDate && picker.config.maxDate.getFullYear() <= 3999 ? picker.config.maxDate : new Date(3999, 11, 31)];
        }
        function months(year, preferred) {
            const [min, max] = bounds(), first = year === min.getFullYear() ? min.getMonth() : 0, last = year === max.getFullYear() ? max.getMonth() : 11;
            wheelYear = year;
            monthWheel.render(Array.from({ length: Math.max(0, last - first + 1) }, (_, i) => ({ value: first + i, label: picker.l10n.months.longhand[first + i] })), Math.max(first, Math.min(last, preferred)));
        }
        monthWheel = wheel(picker.l10n.monthAriaLabel, 'month');
        yearWheel = wheel(picker.l10n.yearAriaLabel, 'year', year => { if (year !== wheelYear) months(year, monthWheel.read() ?? picker.currentMonth); });
        period.append(monthWheel.node, yearWheel.node);
        function apply() {
            const year = yearWheel.read(); if (year !== wheelYear) months(year, monthWheel.read());
            if (year !== undefined && monthWheel.read() !== undefined) picker.jumpToDate(new Date(year, monthWheel.read(), 1), true);
            days(); picker._positionCalendar(); heading.focus({ preventScroll: true });
        }
        heading.addEventListener('click', () => {
            period.hidden = false; calendar.classList.add('xw-date-period-view'); heading.setAttribute('aria-expanded', 'true'); title.textContent = settings.period || 'Month and year';
            const [min, max] = bounds(), first = min.getFullYear(), last = max.getFullYear(); wheelYear = Math.max(first, Math.min(last, picker.currentYear));
            yearWheel.render(Array.from({ length: Math.max(0, last - first + 1) }, (_, i) => ({ value: first + i, label: String(first + i) })), wheelYear);
            months(wheelYear, picker.currentMonth); monthWheel.node.focus({ preventScroll: true }); picker._positionCalendar();
        });
        close.addEventListener('click', () => { if (!period.hidden) apply(); else { picker.close(); returnFocus.focus({ preventScroll: true }); } });
        [toolbar, heading, period].forEach(node => node.addEventListener('keydown', event => {
            event.stopPropagation();
            if (!period.hidden && (event.key === 'Escape' || event.key === 'Enter')) { event.preventDefault(); apply(); }
            else if (event.key === 'Escape') { event.preventDefault(); picker.close(); returnFocus.focus({ preventScroll: true }); }
        }));
        function hook(name, callback) { picker.config[name].push(callback); hooks.push([name, callback]); }
        hook('onOpen', days); hook('onMonthChange', label); hook('onYearChange', label); hook('onValueUpdate', label);
        hook('onPreCalendarPosition', () => calendar.style.setProperty('--xw-date-input-width', anchor.getBoundingClientRect().width + 'px'));
        const resize = typeof ResizeObserver === 'function' ? new ResizeObserver(() => { if (picker.isOpen) picker._positionCalendar(); }) : null; resize?.observe(anchor);
        days();
        return destroying => {
            resize?.disconnect(); hooks.forEach(([name, callback]) => { const index = picker.config[name].indexOf(callback); if (index !== -1) picker.config[name].splice(index, 1); });
            if (!destroying) {
                toolbar.remove(); heading.remove(); period.remove(); oldHeading.hidden = wasHidden; calendar.classList.remove('xw-date-calendar', 'xw-date-period-view');
                calendar.id = oldId; if (oldRole === null) calendar.removeAttribute('role'); else calendar.setAttribute('role', oldRole);
                if (oldLabel === null) calendar.removeAttribute('aria-label'); else calendar.setAttribute('aria-label', oldLabel);
                if (settings.locale === 'es') picker.set('locale', oldLocale);
            }
        };
    }

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
        let calendarPicker = picker, proxy = null, calendarCleanup = () => {};
        function calendarRules() {
            const original = source._flatpickr?.config || {};
            return {
                minDate: native.min || original.minDate || '1000-01-01', maxDate: native.max || original.maxDate || '3999-12-31', ...(original.enable ? { enable: original.enable } : {}),
                disable: [...(original.disable || []), date => offStep(root.flatpickr.formatDate(date, 'Y-m-d'), native.min || native.getAttribute('value') || '1970-01-01', native.step)],
            };
        }
        function errorMessage(key) { return settings[key] || settings.invalid || 'Enter a valid date: day 01–31, month 01–12, year 1000–3999.'; }
        function describeFormat() { prefix.textContent = editor.value; remainder.textContent = remainingFormat(editor.value, editor.placeholder); hint.hidden = !remainder.textContent; }
        function syncAttributes() {
            editor.disabled = native.disabled || source.disabled;
            editor.readOnly = native.readOnly;
            editor.required = source.required;
            open.disabled = editor.disabled || editor.readOnly;
            if (!flat && calendarPicker) { calendarPicker.set(calendarRules()); if (open.disabled) calendarPicker.close(); }
        }
        function fromPicker() {
            if (syncing) return;
            editor.value = isoToDisplay(native.value) || (flat && native.value && picker.selectedDates[0] ? isoToDisplay(picker.formatDate(picker.selectedDates[0], 'Y-m-d')) : '');
            const error = prefixError(editor.value);
            editor.setCustomValidity(error ? errorMessage(error) : native.validationMessage || '');
            if (error) editor.setAttribute('aria-invalid', 'true'); else editor.removeAttribute('aria-invalid');
            lastAccepted = error ? '' : editor.value; lastCaret = lastEnd = lastAccepted.length;
            if (!flat && calendarPicker) { if (toISO(editor.value)) calendarPicker.setDate(native.value, false, 'Y-m-d'); else calendarPicker.clear(false); }
            describeFormat();
        }
        function toPicker() {
            if (editor.disabled || editor.readOnly) return;
            const value = editor.value, iso = toISO(value);
            syncing = true;
            // Never let Flatpickr loosely parse partial numeric text as a year.
            if (flat) { if (iso) picker.setDate(iso, false, 'Y-m-d'); else if (native.value || picker.selectedDates.length) picker.clear(false); }
            else native.value = iso;
            if (!flat && calendarPicker) { if (iso) calendarPicker.setDate(iso, false, 'Y-m-d'); else calendarPicker.clear(false); }
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
            else if (typeof root.flatpickr === 'function') {
                if (!calendarPicker) {
                    // Keep the named native control and its validity; the popup uses an unnamed proxy.
                    proxy = doc.createElement('input'); proxy.type = 'text'; proxy.hidden = true; proxy.tabIndex = -1; proxy.setAttribute('aria-hidden', 'true'); wrap.append(proxy);
                    calendarPicker = root.flatpickr(proxy, {
                        dateFormat: 'Y-m-d', ariaDateFormat: 'j F Y', disableMobile: true, clickOpens: false, positionElement: native,
                        defaultDate: native.value || null, locale: settings.locale === 'es' ? spanish : source._flatpickr?.config.locale || 'default', ...calendarRules(),
                        onChange: (dates, _, instance) => {
                            native.value = dates[0] ? instance.formatDate(dates[0], 'Y-m-d') : '';
                            native.dispatchEvent(new Event('input', { bubbles: true })); native.dispatchEvent(new Event('change', { bubbles: true }));
                            fromPicker(); editor.focus({ preventScroll: true });
                        }, onOpen, onClose,
                    });
                    calendarCleanup = dateCalendar(calendarPicker, native, editor);
                }
                calendarPicker.open();
            } else { try { if (typeof native.showPicker === 'function') native.showPicker(); else native.click(); } catch (_) { native.click(); } }
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
        if (source !== native) { const observer = new MutationObserver(syncAttributes); observer.observe(source, { attributes: true, attributeFilter: ['disabled', 'readonly', 'required', 'min', 'max', 'step'] }); observers.push(observer); }
        syncAttributes(); fromPicker();
        const onOpen = () => open.setAttribute('aria-expanded', 'true'), onClose = () => { open.setAttribute('aria-expanded', 'false'); if (doc.activeElement === native) editor.focus(); };
        function cleanup(destroying = false) {
            states.delete(native);
            observers.forEach(observer => observer.disconnect());
            calendarCleanup(destroying);
            if (!flat && calendarPicker) { calendarPicker.destroy(); proxy.remove(); }
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
            calendarCleanup = dateCalendar(picker, native, editor);
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
