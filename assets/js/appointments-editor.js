(function () {
    'use strict';
    const cfg = window.xwAppointmentPreview;
    if (!cfg || !window.elementor?.hooks) return;
    window.elementor.hooks.addFilter('elementor_pro/forms/content_template/field/xw_appointment', function (_, item, index, settings) {
        const wrap = document.createElement('div'); wrap.className = 'xw-appointment'; wrap.dataset.xwAppointment = '';
        const date = document.createElement('div'); date.className = 'xw-appointment-date';
        const input = document.createElement('input'); input.type = 'text'; input.readOnly = true;
        input.id = 'form_field_' + index; input.placeholder = cfg.date;
        input.className = 'elementor-field elementor-field-textual xw-appointment-date-input elementor-size-' + (settings.input_size || 'sm');
        const open = document.createElement('button'); open.type = 'button'; open.className = 'xw-appointment-open'; open.setAttribute('aria-label', cfg.date);
        open.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/></svg>';
        date.append(input, open);
        const value = document.createElement('input'); value.type = 'hidden'; value.className = 'xw-appointment-value';
        wrap.append(date, value); return wrap.outerHTML;
    });
}());
