/* Elementor's own Flatpickr; no native mobile replacement or manual date entry. */
(function () {
    'use strict';
    const cfg = window.xwAppointments;
    if (!cfg) return;
    const initialized = new WeakSet();
    const spanish = {
        firstDayOfWeek: 1, weekdays: { shorthand: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'], longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'] },
        months: { shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] },
        rangeSeparator: ' a ', scrollTitle: 'Desplazar para cambiar', toggleTitle: 'Pulsar para cambiar', monthAriaLabel: 'Mes', yearAriaLabel: 'Año', time_24hr: true,
    };
    function init(root) {
        if (initialized.has(root) || typeof window.flatpickr !== 'function') return;
        const input = root.querySelector('.xw-appointment-date-input');
        const select = root.querySelector('.xw-appointment-time');
        const value = root.querySelector('.xw-appointment-value');
        const status = root.querySelector('.xw-appointment-status');
        const retry = root.querySelector('.xw-appointment-retry');
        if (!input || !select || !value) return;
        initialized.add(root);
        let days = {}, selected = '', sequence = 0, request;
        const month = () => picker.currentYear + '-' + String(picker.currentMonth + 1).padStart(2, '0');
        function clearValue() { value.value = ''; value.dispatchEvent(new Event('change', { bubbles: true })); }
        function times() {
            const previous = select.value;
            select.replaceChildren(new Option(cfg.choose, ''));
            (days[selected] || []).forEach(slot => select.add(new Option(slot.label, slot.value)));
            select.disabled = !(days[selected] || []).length;
            select.required = true;
            if (Array.from(select.options).some(option => option.value && option.value === previous)) select.value = previous;
            value.value = select.value;
            status.textContent = selected && select.disabled ? cfg.empty : cfg.ready;
        }
        const picker = window.flatpickr(input, {
            dateFormat: 'Y-m-d', altInput: false, ariaDateFormat: 'j F Y', allowInput: false, disableMobile: true,
            minDate: cfg.today, maxDate: cfg.max, locale: cfg.locale === 'es' ? spanish : 'default', enable: [],
            onReady: function (_, __, instance) {
                instance.calendarContainer.classList.add('xw-appointment-calendar');
                // Flatpickr's built-in navigation SVG is kept; add keyboard semantics.
                [[instance.prevMonthNav, 'Previous month', 'Mes anterior'], [instance.nextMonthNav, 'Next month', 'Mes siguiente']].forEach(([nav, en, es]) => {
                    nav.setAttribute('role', 'button'); nav.tabIndex = 0;
                    nav.setAttribute('aria-label', cfg.locale === 'es' ? es : en);
                    nav.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); nav.click(); } });
                });
            },
            onOpen: () => load(),
            onMonthChange: () => load(), onYearChange: () => load(),
            onChange: (dates, _, instance) => { selected = dates[0] ? instance.formatDate(dates[0], 'Y-m-d') : ''; select.value = ''; clearValue(); times(); if (!select.disabled) select.focus(); },
            onDayCreate: (_, __, ___, day) => {
                const key = day.dateObj.getFullYear() + '-' + String(day.dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(day.dateObj.getDate()).padStart(2, '0');
                if (days[key]?.length) day.classList.add('xw-appointment-available');
                day.setAttribute('role', 'button');
                day.setAttribute('aria-disabled', day.classList.contains('flatpickr-disabled') ? 'true' : 'false');
            },
        });
        // Display is friendly while the hidden field is always canonical YYYY-MM-DDTHH:mm.
        picker.config.onValueUpdate.push(function (dates) {
            if (dates[0]) input.value = picker.formatDate(dates[0], 'd/m/Y');
        });
        input.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); picker.open(); } });
        root.querySelector('.xw-appointment-open').addEventListener('click', () => picker.open());
        select.addEventListener('change', () => { value.value = select.value; select.setCustomValidity(''); value.dispatchEvent(new Event('change', { bubbles: true })); });
        retry.addEventListener('click', () => load());
        async function load() {
            const current = ++sequence;
            request?.abort(); request = new AbortController();
            root.setAttribute('aria-busy', 'true'); retry.hidden = true; status.textContent = cfg.loading;
            select.disabled = true;
            // Fail closed while new availability is in flight, including previously cached months.
            days = {}; picker.set('enable', []);
            try {
                const url = new URL(cfg.url, location.href); url.searchParams.set('action', 'xw_appt_availability'); url.searchParams.set('month', month());
                const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: request.signal });
                const data = await response.json();
                if (!response.ok || !data.success || !data.data?.days) throw new Error('availability');
                if (current !== sequence || !root.isConnected) return;
                days = data.data.days;
                picker.set('enable', [date => {
                    const key = picker.formatDate(date, 'Y-m-d'); return !!days[key]?.length;
                }]);
                picker.redraw();
                if (selected && !days[selected]?.some(slot => slot.value === value.value)) clearValue();
                times();
            } catch (error) {
                if (error.name !== 'AbortError' && current === sequence) { clearValue(); select.replaceChildren(new Option(cfg.choose, '')); status.textContent = cfg.error; retry.hidden = false; }
            } finally { if (current === sequence) root.removeAttribute('aria-busy'); }
        }
        const form = root.closest('form');
        const onSubmit = event => {
            if (!root.isConnected) return;
            if (!value.value || root.hasAttribute('aria-busy')) { event.preventDefault(); event.stopImmediatePropagation(); status.textContent = cfg.required; input.focus(); picker.open(); }
        };
        const onReset = () => { selected = ''; clearValue(); picker.clear(); select.value = ''; times(); };
        const onSuccess = () => { onReset(); load(); };
        if (form) {
            form.addEventListener('submit', onSubmit, true);
            form.addEventListener('reset', onReset);
            if (window.jQuery) window.jQuery(form).on('submit_success.xwAppointments', onSuccess);
        }
        load();
        const removal = new MutationObserver(() => {
            if (!root.isConnected) {
                request?.abort(); picker.destroy(); removal.disconnect();
                form?.removeEventListener('submit', onSubmit, true); form?.removeEventListener('reset', onReset);
                if (window.jQuery && form) window.jQuery(form).off('submit_success.xwAppointments', onSuccess);
            }
        });
        removal.observe(document.body, { childList: true, subtree: true });
    }
    function scan() { document.querySelectorAll('[data-xw-appointment]').forEach(init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
    new MutationObserver(scan).observe(document.documentElement, { childList: true, subtree: true });
}());
