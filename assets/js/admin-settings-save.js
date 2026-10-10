(function ($) {
    'use strict';

    // Only persisted controls count: previews, accordion state and nonce changes do not.
    function snapshot(form) {
        return JSON.stringify(Array.from(new FormData(form).entries())
            .filter(function (entry) { return entry[0].indexOf('xw_settings[') === 0; })
            .sort(function (a, b) { return a[0].localeCompare(b[0]); }));
    }

    function applySaved(form, settings) {
        var changed = [];
        Array.from(form.elements).forEach(function (control) {
            if (!control.name || control.name.indexOf('xw_settings[') !== 0 || control.type === 'hidden') return;
            var keys = control.name.match(/\[([^\]]*)\]/g).map(function (key) { return key.slice(1, -1); });
            var multiple = keys[keys.length - 1] === '';
            if (multiple) keys.pop();
            var value = keys.reduce(function (node, key) { return node == null ? undefined : node[key]; }, settings);
            if (value === undefined) return;
            if (control.type === 'checkbox' || control.type === 'radio') {
                var checked = multiple ? Array.isArray(value) && value.map(String).indexOf(control.value) !== -1
                    : control.type === 'checkbox' ? Boolean(Number(value)) : String(value) === control.value;
                if (control.checked !== checked) { control.checked = checked; changed.push(control); }
            } else if (control.value !== String(value)) {
                control.value = String(value); changed.push(control);
            }
        });
        // Rules rejected by the sanitizer must not remain as apparently saved rows.
        if (settings.woocommerce && Array.isArray(settings.woocommerce.extra_fee_rules)) {
            var rows = form.querySelectorAll('[data-xw-extra-fee-rule]');
            Array.from(rows).slice(settings.woocommerce.extra_fee_rules.length).forEach(function (row) {
                $(row).find('[data-xw-extra-fee-remove]').trigger('click');
            });
        }
        changed.forEach(function (control) { $(control).trigger('change'); });
    }

    $(function () {
        var form = document.querySelector('[data-xw-settings-form]');
        var config = window.xwAdminSettings;
        if (!form || !config || !config.ajaxUrl || !window.fetch) return;
        var button = form.querySelector('[type="submit"]');
        var status = form.querySelector('[data-xw-save-status]');
        if (!button || !status) return;
        var strings = config.save;
        var label = button.value;
        var baseline = snapshot(form);
        var saving = false;
        var message = strings.idle;
        var failed = false;

        function refresh() {
            var dirty = snapshot(form) !== baseline;
            button.disabled = saving || !dirty;
            button.value = saving ? strings.saving : label;
            form.setAttribute('aria-busy', saving ? 'true' : 'false');
            status.classList.toggle('is-error', failed && !saving);
            status.textContent = saving ? strings.saving : failed ? message : dirty ? strings.pending : message;
            return dirty;
        }
        function edited() {
            failed = false; message = strings.idle; refresh();
        }
        $(form).on('input change', edited);
        // Includes dynamic row creation/removal and programmatic dependent controls.
        $(form).on('click', function () { window.setTimeout(function () { if (snapshot(form) !== baseline) edited(); }, 0); });
        window.addEventListener('pageshow', refresh);
        window.addEventListener('beforeunload', function (event) {
            if (saving || snapshot(form) !== baseline) { event.preventDefault(); event.returnValue = ''; }
        });

        $(form).on('submit', async function (event) {
            event.preventDefault();
            if (saving || !refresh()) return;
            var sent = snapshot(form);
            var body = new URLSearchParams(new FormData(form));
            body.set('action', 'xw_save_settings');
            // Last field detects PHP max_input_vars truncation before any option is updated.
            body.delete('xw_settings_complete');
            body.append('xw_settings_complete', '1');
            var controller = new AbortController();
            var timeout = window.setTimeout(function () { controller.abort(); }, 30000);
            saving = true; failed = false; refresh();
            try {
                var response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body, signal: controller.signal });
                var result = await response.json();
                if (result && result.data && result.data.nonce) form.querySelector('[name="_wpnonce"]').value = result.data.nonce;
                if (!response.ok || !result || !result.success || !result.data || !result.data.settings) {
                    var saveError = new Error(result && result.data && result.data.message || strings.error);
                    saveError.isSaveError = true;
                    throw saveError;
                }
                // Edits made while the request was pending are never overwritten or marked saved.
                if (snapshot(form) === sent) {
                    applySaved(form, result.data.settings);
                    baseline = snapshot(form);
                } else {
                    baseline = sent;
                }
                message = strings.saved;
            } catch (error) {
                failed = true; message = error.isSaveError ? error.message : strings.error;
            } finally {
                window.clearTimeout(timeout);
                saving = false; refresh();
            }
        });
        refresh();
    });
})(jQuery);
