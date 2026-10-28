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
    function reorderCards() {
        const grid = document.querySelector('.xw-appt-bookings');
        if (!grid) return;
        const priority = { pending: 0, confirmed: 1, cancelled: 2, completed: 3 }, focused = document.activeElement;
        [...grid.querySelectorAll('.xw-appt-booking')].sort((a, b) => (priority[a.dataset.status] ?? 3) - (priority[b.dataset.status] ?? 3) || a.dataset.start.localeCompare(b.dataset.start)).forEach(card => grid.append(card));
        if (focused?.isConnected) focused.focus({ preventScroll: true });
    }
    const forms = new Map(), savingCards = new WeakSet();
    const snapshot = form => JSON.stringify(Array.from(new FormData(form)));
    async function request(data, signal) {
        const response = await fetch(cfg.url, { method: 'POST', body: data, credentials: 'same-origin', signal });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.data?.message || cfg.error);
        return result.data || {};
    }
    function bindForm(form) {
        if (forms.has(form)) return;
        const isSchedule = form.id === 'xw-appt-schedule', isAccess = form.id === 'xw-appt-access', isMessages = form.id === 'xw-appt-messages';
        const operation = isSchedule ? 'schedule' : isAccess ? 'access' : isMessages ? 'messages' : form.dataset.operation || (form.classList.contains('xw-appt-details-form') ? 'details' : 'status');
        const card = form.closest('.xw-appt-booking');
        const button = Array.from(form.elements).find(el => el.matches('button[type="submit"]'));
        const status = isSchedule ? document.querySelector('#xw-appt-save-status') : isAccess ? document.querySelector('#xw-appt-access-status') : form.querySelector('[role="status"]');
        if (!button || !status) return;
        const state = { baseline: snapshot(form), busy: false }; forms.set(form, state);
        const dirty = () => state.baseline !== snapshot(form);
        function update() {
            button.disabled = state.busy || !dirty() || (['reschedule', 'rebook'].includes(operation) && !form.elements.appointment.value);
            if (!state.busy) status.textContent = dirty() ? cfg.dirty : '';
        }
        // Controls in the access panel belong to an external form, not the surrounding schedule.
        form.addEventListener('input', event => { if (event.target.form === form) update(); });
        form.addEventListener('change', event => { if (event.target.form === form || event.target === form) update(); });
        if (isAccess) Array.from(form.elements).forEach(control => { control.addEventListener('input', update); control.addEventListener('change', update); });
        form.addEventListener('submit', async event => {
            event.preventDefault(); if (state.busy || !dirty()) return;
            if (card && savingCards.has(card)) { status.textContent = cfg.saving; return; }
            if (operation === 'status' && form.elements.status.value === 'cancelled' && !window.confirm(cfg.cancel)) return;
            const drafts = card ? Array.from(card.querySelectorAll('form')).filter(other => other !== form && forms.has(other) && forms.get(other).baseline !== snapshot(other)).map(other => ({ selector: other.id ? '#' + other.id : '.' + other.className.split(' ')[0], values: Array.from(new FormData(other)).filter(([key]) => !['revision','booking_view','rebook_after'].includes(key)), visible: !other.hidden, dateDisplay: other.querySelector('.xw-appointment-date-input')?.value })) : [];
            const sent = snapshot(form), data = new FormData(form);
            data.set('action', isAccess ? 'xw_appt_access_save' : 'xw_appt_admin_save'); data.set('nonce', form.dataset.nonce);
            data.set('operation', operation);
            if (isAccess) data.set('revision', form.dataset.revision);
            if (card) data.set('booking', form.dataset.booking);
            state.busy = true; button.disabled = true; status.textContent = cfg.saving;
            const controls = card ? Array.from(card.querySelectorAll('input,select,textarea,button')).map(el => [el, el.disabled]) : [];
            controls.forEach(([el]) => { el.disabled = true; });
            if (card) savingCards.add(card);
            try {
                const result = await request(data);
                controls.forEach(([el, disabled]) => { el.disabled = disabled; });
                state.baseline = sent; state.busy = false;
                if (result.revision) form.dataset.revision = result.revision;
                if (result.redirect) { window.location.assign(result.redirect); return; }
                // Refresh existing contact links after changing templates or the country code.
                if (isSchedule && state.baseline === snapshot(form) && !Array.from(forms).some(([other, saved]) => other !== form && other.isConnected && (saved.busy || saved.baseline !== snapshot(other)))) { window.location.reload(); return; }
                if (card && result.card) {
                    const template = document.createElement('template'); template.innerHTML = result.card;
                    const replacement = template.content.firstElementChild;
                    card.querySelectorAll('form').forEach(old => forms.delete(old));
                    card.replaceWith(replacement); bindCard(replacement);
                    drafts.forEach(draft => {
                        const other = replacement.querySelector(draft.selector); if (!other) return;
                        draft.values.forEach(([key, value]) => { if (other.elements.namedItem(key)) other.elements.namedItem(key).value = value; });
                        if (draft.dateDisplay) other.querySelector('.xw-appointment-date-input').value = draft.dateDisplay;
                        other.hidden = !draft.visible;
                        const toggle = replacement.querySelector('[aria-controls="' + other.id + '"]'); if (toggle) toggle.setAttribute('aria-expanded', String(draft.visible));
                        other.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    reorderCards();
                    const notice = replacement.querySelector('[role="status"]');
                    if (notice) notice.textContent = operation === 'reschedule' ? cfg.rescheduled : cfg.saved;
                    replacement.querySelector('h3').tabIndex = -1;
                    replacement.querySelector('h3').focus({ preventScroll: true });
                } else { update(); status.textContent = dirty() ? cfg.dirty : cfg.saved; }
            } catch (error) {
                controls.forEach(([el, disabled]) => { el.disabled = disabled; });
                state.busy = false; update(); status.textContent = error.message || cfg.error;
            } finally { if (card) savingCards.delete(card); if ((card || isMessages) && state.baseline === sent) refreshList(); }
        });
        update();
    }
    function bindCard(card) {
        card.querySelectorAll('.xw-appt-reschedule-toggle, .xw-appt-edit-toggle').forEach(toggle => {
            const form = document.getElementById(toggle.getAttribute('aria-controls'));
            toggle.addEventListener('click', () => {
                form.hidden = !form.hidden; toggle.setAttribute('aria-expanded', String(!form.hidden));
                if (!form.hidden) form.querySelector('textarea, input:not([type="hidden"]), select')?.focus();
            });
        });
        card.querySelectorAll('form').forEach(bindForm);
    }
    document.querySelectorAll('.xw-appt-booking').forEach(bindCard);
    document.querySelectorAll('#xw-appt-schedule, #xw-appt-access, #xw-appt-messages').forEach(bindForm);
    const searchForm = document.querySelector('#xw-appt-live-search'), results = document.querySelector('#xw-appt-results');
    let listTimer, listRequest, listSequence = 0;
    const dirtyResults = () => Array.from(forms).some(([form, saved]) => results?.contains(form) && (saved.busy || saved.baseline !== snapshot(form)));
    function refreshList(page = null, fromPagination = false) {
        if (!searchForm || !results) return;
        clearTimeout(listTimer); listRequest?.abort(); const sequence = ++listSequence;
        listTimer = setTimeout(async () => {
            const status = document.querySelector('#xw-appt-list-status');
            if (!searchForm.checkValidity()) { status.textContent = cfg.invalidDays; results.setAttribute('aria-busy', 'false'); return; }
            if (dirtyResults()) { status.textContent = cfg.finishEdits; results.setAttribute('aria-busy', 'false'); return; }
            const data = new FormData(searchForm), targetPage = page ?? Number(searchForm.dataset.page || 1);
            data.set('action', 'xw_appt_admin_save'); data.set('operation', 'list'); data.set('nonce', cfg.nonce);
            data.set('rebook_after', searchForm.elements.rebook_after?.value || searchForm.dataset.days);
            data.set('booking_page', targetPage);
            if (searchForm.dataset.bookingId !== '0') data.set('booking_id', searchForm.dataset.bookingId);
            listRequest = new AbortController(); status.textContent = cfg.searching; results.setAttribute('aria-busy', 'true');
            try {
                const response = await request(data, listRequest.signal);
                if (sequence !== listSequence) return;
                if (dirtyResults()) { status.textContent = cfg.finishEdits; return; }
                results.querySelectorAll('.xw-appt-booking form').forEach(form => forms.delete(form));
                results.innerHTML = response.html; results.querySelectorAll('.xw-appt-booking').forEach(bindCard);
                if (fromPagination) { results.tabIndex = -1; results.focus({ preventScroll: true }); results.scrollIntoView({ block: 'start' }); }
                document.querySelector('.xw-appt-count').textContent = response.count;
                searchForm.dataset.page = response.page; searchForm.dataset.days = data.get('rebook_after');
                status.textContent = response.count === 1 ? cfg.searchOne : cfg.searchCount.replace('%d', response.count);
                const url = new URL(window.location.href);
                ['booking_status', 'changed_before', 'booking_id'].forEach(key => url.searchParams.delete(key));
                url.searchParams.set('booking_view', searchForm.dataset.view); url.searchParams.set('booking_page', response.page);
                url.searchParams.set('rebook_after', data.get('rebook_after'));
                if (data.get('booking_search')) url.searchParams.set('booking_search', data.get('booking_search')); else url.searchParams.delete('booking_search');
                if (data.has('booking_id')) url.searchParams.set('booking_id', data.get('booking_id'));
                window.history.replaceState(null, '', url);
                document.querySelectorAll('.xw-appt-views a').forEach(link => { const target = new URL(link.href); target.searchParams.set('rebook_after', data.get('rebook_after')); link.href = target.href; });
            } catch (error) { if (sequence === listSequence && error.name !== 'AbortError') status.textContent = error.message || cfg.error; }
            finally { if (sequence === listSequence) results.setAttribute('aria-busy', 'false'); }
        }, 220);
    }
    if (searchForm && results) {
        searchForm.addEventListener('submit', event => { event.preventDefault(); searchForm.dataset.bookingId = '0'; refreshList(1); });
        searchForm.addEventListener('input', () => { searchForm.dataset.bookingId = '0'; refreshList(1); });
        results.addEventListener('click', event => {
            const link = event.target.closest('.xw-appt-pagination a'); if (!link) return;
            event.preventDefault(); refreshList(Number(new URL(link.href).searchParams.get('booking_page')) || 1, true);
        });
    }
    const dialog = document.querySelector('#xw-appt-mail-dialog'), mail = dialog?.querySelector('form');
    if (mail) {
        const send = mail.querySelector('[type="submit"]'), refresh = mail.querySelector('.xw-appt-mail-preview');
        const status = mail.querySelector('[role="status"]'), frame = mail.querySelector('iframe');
        let booking = '', kind = '', token = '', preview = '', busy = false, sent = false, requestId = '', opening;
        const value = () => JSON.stringify([mail.elements.subject.value, mail.elements.text.value]);
        const update = () => { send.disabled = busy || sent || !token || preview !== value(); refresh.disabled = busy || sent; };
        function data(operation, initial = false) {
            const body = new FormData();
            Object.entries({ action: 'xw_appt_admin_save', operation, nonce: cfg.nonce, booking, kind, preview_token: token, request_id: requestId }).forEach(([key, val]) => body.set(key, val));
            if (!initial) { body.set('subject', mail.elements.subject.value); body.set('text', mail.elements.text.value); }
            return body;
        }
        async function showPreview(initial = false) {
            if (busy) return;
            busy = true; update(); status.textContent = cfg.saving;
            mail.elements.subject.readOnly = true; mail.elements.text.readOnly = true;
            try {
                const result = await request(data('message_preview', initial));
                mail.elements.subject.value = result.subject; mail.elements.text.value = result.text;
                mail.querySelector('.xw-appt-mail-recipient').textContent = result.recipient;
                kind = result.kind; token = result.preview_token; preview = value(); frame.srcdoc = result.html; status.textContent = '';
            } catch (error) { token = ''; status.textContent = error.message || cfg.error; }
            finally { busy = false; mail.elements.subject.readOnly = false; mail.elements.text.readOnly = false; update(); }
        }
        document.addEventListener('click', event => {
            const button = event.target.closest('.xw-appt-email-open');
            if (!button || busy) return;
            opening = button; booking = button.closest('.xw-appt-booking').dataset.booking; kind = button.dataset.kind;
            requestId = window.crypto?.randomUUID?.() || Date.now().toString(36) + '-' + Math.random().toString(36).slice(2) + '-' + Math.random().toString(36).slice(2); token = ''; preview = ''; sent = false; frame.srcdoc = ''; mail.reset();
            dialog.showModal(); showPreview(true);
        });
        mail.addEventListener('input', () => { update(); status.textContent = cfg.previewFirst; });
        refresh.addEventListener('click', () => showPreview());
        mail.addEventListener('submit', async event => {
            event.preventDefault(); if (send.disabled || busy) return;
            busy = true; update(); status.textContent = cfg.emailSending;
            mail.elements.subject.readOnly = true; mail.elements.text.readOnly = true;
            try { await request(data('message_send')); sent = true; status.textContent = cfg.emailSent; }
            catch (error) { status.textContent = error.message || cfg.error; }
            finally { busy = false; mail.elements.subject.readOnly = sent; mail.elements.text.readOnly = sent; update(); }
        });
        dialog.querySelector('.xw-appt-mail-close').addEventListener('click', () => { if (!busy) dialog.close(); });
        dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
        dialog.addEventListener('close', () => opening?.focus());
    }
    window.addEventListener('beforeunload', event => {
        if (Array.from(forms).some(([form, state]) => form.isConnected && (state.busy || state.baseline !== snapshot(form)))) { event.preventDefault(); event.returnValue = ''; }
    });
}());
