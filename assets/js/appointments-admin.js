(function () {
    'use strict';
    const cfg = window.xwAppointmentAdmin;
    if (!cfg) return;
    const schedule = document.querySelector('#xw-appt-schedule');
    if (schedule) {
        const template = document.querySelector('#xw-appt-shift-template');
        schedule.querySelectorAll('.xw-appt-day').forEach(day => {
            const shifts = day.querySelector('.xw-appt-shifts'), add = day.querySelector('.xw-appt-add-shift');
            function renumber() {
                const rows = shifts.querySelectorAll('.xw-appt-shift');
                rows.forEach((row, index) => {
                    row.querySelectorAll('select').forEach(select => {
                        select.name = `schedule[shifts][${day.dataset.day}][${index}][${select.dataset.edge}]`;
                        select.setAttribute('aria-label', `${day.querySelector('legend').textContent}, ${cfg.shift} ${index + 1}: ${cfg[select.dataset.edge]}`);
                    });
                    row.querySelector('button').setAttribute('aria-label', `${cfg.remove}: ${day.querySelector('legend').textContent}, ${index + 1}`);
                });
                add.disabled = rows.length >= 4;
            }
            add.addEventListener('click', () => {
                if (shifts.children.length >= 4) return;
                shifts.append(template.content.cloneNode(true)); renumber();
                shifts.lastElementChild.querySelector('select').focus();
                schedule.dispatchEvent(new Event('change', { bubbles: true }));
            });
            shifts.addEventListener('click', event => {
                const remove = event.target.closest('.xw-appt-remove-shift');
                if (!remove) return;
                if (shifts.children.length === 1) remove.closest('.xw-appt-shift').querySelectorAll('select').forEach(select => { select.value = ''; });
                else remove.closest('.xw-appt-shift').remove();
                renumber(); add.focus(); schedule.dispatchEvent(new Event('change', { bubbles: true }));
            });
            renumber();
        });
        const closed = document.querySelector('#xw-appt-closed'), summary = document.querySelector('#xw-appt-closed-summary');
        const dates = closed.value.split(/[\s,]+/).filter(Boolean), past = dates.filter(date => date < cfg.today);
        function describe() { const values = closed.value.split(/[\s,]+/).filter(Boolean); summary.textContent = values.length ? `${cfg.daysOff} (${values.length}): ${values.join(', ')}` : cfg.none; }
        describe();
        if (typeof window.flatpickr === 'function') {
            const locale = cfg.locale === 'es' ? {
                firstDayOfWeek: 1,
                weekdays: { shorthand: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá'], longhand: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'] },
                months: { shorthand: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], longhand: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] },
                yearAriaLabel: 'Año', monthAriaLabel: 'Mes',
            } : 'default';
            window.flatpickr(document.querySelector('.xw-appt-calendar-anchor'), {
                inline: true, mode: 'multiple', dateFormat: 'Y-m-d', minDate: cfg.today, disableMobile: true, locale,
                defaultDate: dates.filter(date => date >= cfg.today),
                onReady: (_, __, picker) => {
                    picker.calendarContainer.classList.add('xw-appt-days-off');
                    [[picker.prevMonthNav, cfg.locale === 'es' ? 'Mes anterior' : 'Previous month'], [picker.nextMonthNav, cfg.locale === 'es' ? 'Mes siguiente' : 'Next month']].forEach(([nav, label]) => {
                        nav.tabIndex = 0; nav.setAttribute('role', 'button'); nav.setAttribute('aria-label', label);
                        nav.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); nav.click(); } });
                    });
                },
                onDayCreate: (_, __, ___, day) => { day.setAttribute('role', 'button'); day.setAttribute('aria-disabled', day.classList.contains('flatpickr-disabled') ? 'true' : 'false'); },
                onChange: (selected, _, picker) => {
                    closed.value = [...new Set([...past, ...selected.map(date => picker.formatDate(date, 'Y-m-d'))])].sort().join(',');
                    describe(); closed.dispatchEvent(new Event('change', { bubbles: true }));
                },
            });
        } else summary.textContent = cfg.calendarMissing + ' ' + summary.textContent;
        const preview = schedule.querySelector('.xw-appt-color-preview');
        function previewColors() {
            const get = key => schedule.querySelector(`[data-color="${key}"]`).value;
            preview.style.backgroundColor = /^#[a-f\d]{6}$/i.test(get('calendar_background')) ? get('calendar_background') : '#ffffff';
            const circle = preview.querySelector('span');
            circle.style.borderColor = get('border') || '#3852eb'; circle.style.backgroundColor = get('background') || '#ffffff'; circle.style.color = get('text') || '#1d2327';
        }
        schedule.querySelectorAll('.xw-appt-color').forEach(control => {
            const picker = control.querySelector('[type="color"]'), text = control.querySelector('[data-color]');
            picker.addEventListener('input', () => { text.value = picker.value; text.dispatchEvent(new Event('input', { bubbles: true })); });
            text.addEventListener('input', () => { if (/^#[a-f\d]{6}$/i.test(text.value)) picker.value = text.value; previewColors(); });
            control.querySelector('button').addEventListener('click', () => { text.value = ''; previewColors(); text.dispatchEvent(new Event('change', { bubbles: true })); });
        });
        previewColors();
    }
    document.querySelectorAll('#xw-appt-schedule, .xw-appt-booking-status').forEach(form => {
        const button = form.querySelector('button[type="submit"]') || form.querySelector('button');
        const status = form.id === 'xw-appt-schedule' ? form.querySelector('#xw-appt-save-status') : form.querySelector('[role="status"]');
        const snapshot = () => JSON.stringify(Array.from(new FormData(form)));
        let baseline = snapshot(), busy = false;
        function update() { button.disabled = busy || baseline === snapshot(); if (!busy) status.textContent = button.disabled ? cfg.saved : cfg.dirty; }
        form.addEventListener('input', update); form.addEventListener('change', update);
        form.addEventListener('submit', async event => {
            event.preventDefault(); if (busy || baseline === snapshot()) return;
            const isSchedule = form.id === 'xw-appt-schedule';
            if (!isSchedule && form.elements.status.value === 'cancelled' && !window.confirm(cfg.cancel)) return;
            const sent = snapshot(); const data = new FormData(form);
            data.set('action', 'xw_appt_admin_save'); data.set('nonce', form.dataset.nonce);
            data.set('operation', isSchedule ? 'schedule' : 'status');
            if (!isSchedule) data.set('booking', form.dataset.booking);
            busy = true; button.disabled = true; status.textContent = cfg.saving;
            try {
                const response = await fetch(cfg.url, { method: 'POST', body: data, credentials: 'same-origin' });
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.data?.message || cfg.error);
                baseline = sent; busy = false; update();
                if (!isSchedule && result.data.status === 'cancelled') { form.querySelector('select').value = 'cancelled'; form.querySelector('select').disabled = true; baseline = snapshot(); update(); }
            } catch (error) { busy = false; button.disabled = baseline === snapshot(); status.textContent = error.message || cfg.error; }
        });
        window.addEventListener('beforeunload', event => { if (busy || baseline !== snapshot()) { event.preventDefault(); event.returnValue = ''; } });
    });
}());
