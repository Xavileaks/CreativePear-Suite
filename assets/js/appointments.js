/* One field, two steps, using Elementor's own Flatpickr. */
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
    function element(tag, className, text) {
        const node = document.createElement(tag); node.className = className;
        if (text) node.textContent = text;
        return node;
    }
    function init(root) {
        if (initialized.has(root) || typeof window.flatpickr !== 'function') return;
        const input = root.querySelector('.xw-appointment-date-input'), value = root.querySelector('.xw-appointment-value');
        if (!input || !value) return;
        initialized.add(root);
        let days = {}, selected = '', sequence = 0, request, calendar, hours, status, retry, back, title, zone, transition;
        let period, periodButton, monthWheel, yearWheel, wheelYear, footer;
        const month = () => picker.currentYear + '-' + String(picker.currentMonth + 1).padStart(2, '0');
        const friendly = day => day.split('-').reverse().join('/');
        function display() { input.value = value.value ? friendly(value.value.slice(0, 10)) + ' · ' + value.value.slice(11) : ''; }
        function setValue(next) {
            value.value = next; input.removeAttribute('aria-invalid'); display();
            value.dispatchEvent(new Event('change', { bubbles: true }));
        }
        function calendarView() {
            clearTimeout(transition); calendar.classList.remove('xw-appointment-hours-view', 'xw-appointment-fading', 'xw-appointment-period-view'); back.hidden = true; hours.hidden = true;
            if (period) { period.hidden = true; footer.hidden = false; periodButton.setAttribute('aria-expanded', 'false'); }
            title.textContent = cfg.locale === 'es' ? 'Elige un día' : 'Choose a day';
            status.textContent = root.hasAttribute('aria-busy') ? cfg.loading : cfg.ready;
        }
        function periodLabel(instance) {
            periodButton.textContent = instance.l10n.months.longhand[instance.currentMonth] + ' ' + instance.currentYear;
            periodButton.setAttribute('aria-description', periodButton.textContent);
        }
        // Scroll-snap listboxes keep touch, mouse wheel and keyboard on the same centered option.
        function wheel(label, key, onChange) {
            const node = element('div', 'xw-appointment-wheel');
            node.setAttribute('role', 'listbox'); node.setAttribute('aria-label', label); node.tabIndex = 0;
            let items = [], active = -1;
            function read() {
                const rowHeight = node.firstElementChild?.getBoundingClientRect().height || 44;
                const index = Math.max(0, Math.min(items.length - 1, Math.round(node.scrollTop / rowHeight)));
                if (index !== active && items[index]) {
                    active = index;
                    Array.from(node.children).forEach((option, i) => option.setAttribute('aria-selected', String(i === index)));
                    node.setAttribute('aria-activedescendant', node.children[index].id);
                    onChange?.(items[index].value);
                }
                return items[index]?.value;
            }
            function select(index) {
                const rowHeight = node.firstElementChild?.getBoundingClientRect().height || 44;
                node.scrollTop = Math.max(0, Math.min(items.length - 1, index)) * rowHeight; read();
            }
            function render(next, value) {
                items = next; active = -1; node.replaceChildren();
                items.forEach((item, index) => {
                    const option = element('div', 'xw-appointment-wheel-option', item.label);
                    option.id = calendar.id + '-' + key + '-' + item.value; option.setAttribute('role', 'option');
                    option.addEventListener('click', () => { select(index); node.focus({ preventScroll: true }); });
                    node.append(option);
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
        function periodMonths(year, preferred) {
            const first = Number(cfg.today.slice(0, 4)) === year ? Number(cfg.today.slice(5, 7)) - 1 : 0;
            const last = Number(cfg.max.slice(0, 4)) === year ? Number(cfg.max.slice(5, 7)) - 1 : 11;
            wheelYear = year;
            monthWheel.render(Array.from({ length: last - first + 1 }, (_, i) => ({ value: first + i, label: picker.l10n.months.longhand[first + i] })), Math.max(first, Math.min(last, preferred)));
        }
        function openPeriod() {
            clearTimeout(transition);
            calendar.classList.remove('xw-appointment-fading'); calendar.classList.add('xw-appointment-period-view');
            period.hidden = false; footer.hidden = true; back.hidden = true;
            title.textContent = cfg.locale === 'es' ? 'Mes y año' : 'Month and year'; periodButton.setAttribute('aria-expanded', 'true');
            const first = Number(cfg.today.slice(0, 4)), last = Number(cfg.max.slice(0, 4));
            wheelYear = picker.currentYear;
            yearWheel.render(Array.from({ length: last - first + 1 }, (_, i) => ({ value: first + i, label: String(first + i) })), picker.currentYear);
            periodMonths(picker.currentYear, picker.currentMonth);
            monthWheel.node.focus({ preventScroll: true }); picker._positionCalendar();
        }
        function applyPeriod() {
            const year = yearWheel.read();
            if (year !== wheelYear) periodMonths(year, monthWheel.read());
            const nextMonth = monthWheel.read(), changed = year !== picker.currentYear || nextMonth !== picker.currentMonth;
            picker.jumpToDate(new Date(year, nextMonth, 1), false);
            periodLabel(picker); calendarView();
            if (changed) load();
            picker._positionCalendar(); periodButton.focus({ preventScroll: true });
        }
        function timeView(focus) {
            calendar.classList.add('xw-appointment-hours-view'); back.hidden = false; hours.hidden = false;
            title.textContent = friendly(selected); hours.replaceChildren();
            const slots = days[selected] || [];
            status.textContent = slots.length ? cfg.choose : cfg.empty;
            slots.forEach(slot => {
                const button = element('button', 'xw-appointment-slot', slot.label); button.type = 'button';
                button.setAttribute('aria-label', friendly(selected) + ', ' + slot.label);
                button.setAttribute('aria-pressed', String(value.value === slot.value));
                button.addEventListener('click', () => { setValue(slot.value); picker.close(); input.focus(); });
                hours.append(button);
            });
            if (focus) (hours.querySelector('button') || back).focus();
        }
        const picker = window.flatpickr(input, {
            dateFormat: 'Y-m-d', ariaDateFormat: 'j F Y', allowInput: false, disableMobile: true, closeOnSelect: false,
            minDate: cfg.today, maxDate: cfg.max, locale: cfg.locale === 'es' ? spanish : 'default', enable: [],
            // Measure before Flatpickr decides left/right alignment, including after resize.
            onPreCalendarPosition: (_, __, instance) => {
                instance.calendarContainer.style.setProperty('--xw-appt-input-width', input.getBoundingClientRect().width + 'px');
            },
            onReady: function (_, __, instance) {
                calendar = instance.calendarContainer; calendar.classList.add('xw-appointment-calendar');
                calendar.id = 'xw-appointment-popup-' + Math.random().toString(36).slice(2);
                calendar.setAttribute('role', 'dialog'); calendar.setAttribute('aria-label', cfg.required);
                input.setAttribute('aria-controls', calendar.id); input.setAttribute('aria-haspopup', 'dialog'); input.setAttribute('aria-expanded', 'false');
                const toolbar = element('div', 'xw-appointment-toolbar');
                back = element('button', 'xw-appointment-back', '←'); back.type = 'button'; back.setAttribute('aria-label', cfg.back); back.title = cfg.back;
                title = element('strong', 'xw-appointment-step-title'); title.setAttribute('aria-live', 'polite');
                const close = element('button', 'xw-appointment-close'); close.type = 'button'; close.setAttribute('aria-label', cfg.close);
                close.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';
                toolbar.append(back, title, close); calendar.prepend(toolbar);
                hours = element('div', 'xw-appointment-hours'); hours.setAttribute('role', 'group'); hours.setAttribute('aria-label', cfg.time);
                status = element('p', 'xw-appointment-status'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
                retry = element('button', 'xw-appointment-retry', cfg.retry); retry.type = 'button'; retry.hidden = true;
                zone = element('small', 'xw-appointment-zone', cfg.zone.replace('%s', cfg.timezone));
                footer = element('div', 'xw-appointment-footer'); footer.append(status, retry, zone); calendar.append(hours, footer);
                periodButton = element('button', 'xw-appointment-period-toggle'); periodButton.type = 'button';
                periodButton.setAttribute('aria-expanded', 'false'); periodButton.setAttribute('aria-label', cfg.locale === 'es' ? 'Elegir mes y año' : 'Choose month and year');
                instance.currentYearElement.parentElement.parentElement.hidden = true;
                instance.monthNav.append(periodButton); periodLabel(instance);
                period = element('div', 'xw-appointment-period'); period.hidden = true; period.id = calendar.id + '-period';
                period.setAttribute('role', 'group'); period.setAttribute('aria-label', cfg.locale === 'es' ? 'Mes y año' : 'Month and year');
                periodButton.setAttribute('aria-controls', period.id);
                monthWheel = wheel(instance.l10n.monthAriaLabel, 'month');
                yearWheel = wheel(instance.l10n.yearAriaLabel, 'year', year => { if (year !== wheelYear) periodMonths(year, monthWheel.read() ?? instance.currentMonth); });
                period.append(monthWheel.node, yearWheel.node); calendar.append(period);
                periodButton.addEventListener('click', openPeriod);
                back.addEventListener('click', () => { calendarView(); instance.daysContainer.querySelector('.selected:not(.flatpickr-disabled), .xw-appointment-available')?.focus(); });
                close.addEventListener('click', () => {
                    if (calendar.classList.contains('xw-appointment-period-view')) { applyPeriod(); return; }
                    instance.close(); input.focus();
                });
                retry.addEventListener('click', () => load());
                // Do not let Flatpickr interpret Enter on custom buttons as a day selection.
                [toolbar, hours, footer, period, periodButton].forEach(panel => panel.addEventListener('keydown', event => {
                    event.stopPropagation();
                    if (calendar.classList.contains('xw-appointment-period-view') && (event.key === 'Escape' || event.key === 'Enter')) { event.preventDefault(); applyPeriod(); return; }
                    if (event.key === 'Escape') { event.preventDefault(); instance.close(); input.focus(); }
                }));
                [[instance.prevMonthNav, 'Previous month', 'Mes anterior'], [instance.nextMonthNav, 'Next month', 'Mes siguiente']].forEach(([nav, en, es]) => {
                    nav.setAttribute('role', 'button'); nav.tabIndex = 0; nav.setAttribute('aria-label', cfg.locale === 'es' ? es : en);
                    nav.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); nav.click(); } });
                });
                const colors = cfg.colors || {};
                Object.entries({ border: '--xw-appt-accent', background: '--xw-appt-day-bg', text: '--xw-appt-day-text', calendar_background: '--xw-appt-calendar-bg' }).forEach(([key, css]) => {
                    if (/^#[a-f\d]{6}$/i.test(colors[key] || '')) calendar.style.setProperty(css, colors[key]);
                });
                if (colors.calendar_background) {
                    const rgb = colors.calendar_background.slice(1).match(/.{2}/g).map(hex => parseInt(hex, 16));
                    if (rgb[0] * .299 + rgb[1] * .587 + rgb[2] * .114 < 145) calendar.classList.add('xw-appointment-dark');
                }
                calendarView();
            },
            onOpen: () => { input.setAttribute('aria-expanded', 'true'); calendarView(); load(); },
            onClose: () => { clearTimeout(transition); input.setAttribute('aria-expanded', 'false'); display(); },
            onMonthChange: (_, __, instance) => { periodLabel(instance); calendarView(); load(); }, onYearChange: (_, __, instance) => { periodLabel(instance); calendarView(); load(); },
            onChange: (dates, _, instance) => {
                if (!dates[0]) return;
                selected = instance.formatDate(dates[0], 'Y-m-d'); setValue(''); calendar.classList.add('xw-appointment-fading');
                clearTimeout(transition); transition = setTimeout(() => { calendar.classList.remove('xw-appointment-fading'); timeView(true); }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 120);
            },
            onValueUpdate: () => display(),
            onDayCreate: (_, __, ___, day) => {
                const key = day.dateObj.getFullYear() + '-' + String(day.dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(day.dateObj.getDate()).padStart(2, '0');
                if (days[key]?.length) day.classList.add('xw-appointment-available');
                day.setAttribute('role', 'button'); day.setAttribute('aria-disabled', String(day.classList.contains('flatpickr-disabled')));
            },
        });
        // Flatpickr otherwise reopens on focus after committing a time.
        input.removeEventListener('focus', picker.open);
        input.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); picker.open(); } });
        root.querySelector('.xw-appointment-open').addEventListener('click', () => picker.open());
        const fieldResize = typeof ResizeObserver === 'function' ? new ResizeObserver(() => {
            if (picker.isOpen) picker._positionCalendar();
        }) : null;
        fieldResize?.observe(input);
        async function load() {
            const current = ++sequence, requestedMonth = month();
            request?.abort(); request = new AbortController();
            root.setAttribute('aria-busy', 'true'); calendar.setAttribute('aria-busy', 'true'); retry.hidden = true; status.textContent = cfg.loading;
            hours.replaceChildren(); days = {}; picker.set('enable', []); display();
            try {
                const url = new URL(cfg.url, location.href);
                const admin = root.dataset.xwAppointmentAction === 'xw_appt_admin_availability';
                url.searchParams.set('action', admin ? 'xw_appt_admin_availability' : 'xw_appt_availability'); url.searchParams.set('month', requestedMonth);
                if (admin) { url.searchParams.set('booking', root.dataset.booking); url.searchParams.set('nonce', root.dataset.nonce); }
                const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: request.signal });
                const data = await response.json();
                if (!response.ok || !data.success || !data.data?.days) throw new Error('availability');
                if (current !== sequence || !root.isConnected) return;
                days = data.data.days;
                picker.set('enable', [date => !!days[picker.formatDate(date, 'Y-m-d')]?.length]); picker.redraw();
                // Browsing other months must not erase a committed booking.
                if (value.value.startsWith(requestedMonth) && !days[value.value.slice(0, 10)]?.some(slot => slot.value === value.value)) setValue('');
                display(); zone.textContent = cfg.zone.replace('%s', data.data.timezone || cfg.timezone);
                if (calendar.classList.contains('xw-appointment-hours-view')) timeView(false); else status.textContent = cfg.ready;
            } catch (error) {
                if (error.name !== 'AbortError' && current === sequence) { status.textContent = cfg.error; retry.hidden = false; }
            } finally {
                if (current === sequence) { root.removeAttribute('aria-busy'); calendar.removeAttribute('aria-busy'); }
            }
        }
        const form = root.closest('form');
        const onSubmit = event => {
            if (!root.isConnected) return;
            if (!value.value || root.hasAttribute('aria-busy')) {
                event.preventDefault(); event.stopImmediatePropagation(); input.setAttribute('aria-invalid', 'true'); input.focus(); picker.open();
            }
        };
        const onReset = () => { selected = ''; setValue(''); picker.clear(false); calendarView(); };
        const onSuccess = () => { onReset(); days = {}; };
        if (form) {
            form.addEventListener('submit', onSubmit, true); form.addEventListener('reset', onReset);
            if (window.jQuery) window.jQuery(form).on('submit_success.xwAppointments', onSuccess);
        }
        const removal = new MutationObserver(() => {
            if (!root.isConnected) {
                clearTimeout(transition); request?.abort(); fieldResize?.disconnect(); picker.destroy(); removal.disconnect();
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
