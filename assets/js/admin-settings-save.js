(function ($) {
    'use strict';

    function editors(form) {
        return window.tinymce ? window.tinymce.editors.filter(function (editor) {
            var field = editor.getElement();
            return field && form.contains(field) && field.name.indexOf('xw_settings[') === 0;
        }) : [];
    }

    function persistedData(form) {
        var data = new FormData(form);
        editors(form).forEach(function (editor) {
            if (editor.initialized && !editor.isHidden()) data.set(editor.getElement().name, editor.getContent());
        });
        return data;
    }

    // TinyMCE writes raw HTML into its textarea on beforeunload. Compare the
    // editor's canonical content, not that late rewrite, to avoid false warnings.
    function snapshot(form) {
        return JSON.stringify(Array.from(persistedData(form).entries())
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
        editors(form).forEach(function (editor) {
            if (!editor.initialized || editor.isHidden()) return;
            var value = editor.getElement().value;
            if (editor.getContent() !== value) editor.setContent(value);
            editor.save();
            editor.setDirty(false);
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
        function bindEditor(editor) {
            if (editors(form).indexOf(editor) === -1) return;
            function initialized() {
                // Initial editor formatting is not a user edit. Rebase only
                // this field, preserving pending edits in all other controls.
                var field = editor.getElement();
                var entries = JSON.parse(baseline);
                var entry = entries.find(function (item) { return item[0] === field.name; });
                if (!saving && entry && entry[1] === field.value) {
                    entry[1] = editor.getContent();
                    baseline = JSON.stringify(entries);
                }
                editor.on('input change Undo Redo SetContent', edited);
                refresh();
            }
            if (editor.initialized) initialized();
            else editor.on('init', initialized);
        }
        editors(form).forEach(bindEditor);
        if (window.tinymce) window.tinymce.on('AddEditor', function (event) { bindEditor(event.editor); });
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
            var body = new URLSearchParams(persistedData(form));
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
