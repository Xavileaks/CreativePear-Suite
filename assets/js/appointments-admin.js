(function () {
    'use strict';
    const cfg = window.xwAppointmentAdmin;
    if (!cfg) return;
    document.querySelectorAll('#xw-appt-schedule, .xw-appt-booking-status').forEach(form => {
        const button = form.querySelector('button[type="submit"], button');
        const status = form.querySelector('[role="status"]');
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
