(() => {
    const builder = document.querySelector('[data-xw-sf-builder]');
    if (!builder) return;

    const rows = builder.querySelector('[data-xw-sf-rows]');
    const template = builder.querySelector('[data-xw-sf-template]');
    const termTemplate = builder.querySelector('[data-xw-sf-term-template]');
    const add = builder.querySelector('[data-xw-sf-add]');
    let index = rows.children.length;

    function renderTermCards(row) {
        const section = row.querySelector('[data-xw-sf-term-custom]');
        const cards = section.querySelector('ol');
        const picker = row.querySelector('[data-xw-sf-term-picker]');
        const terms = picker.hidden ? [] : [...picker.querySelector('select').selectedOptions];
        const swatches = row.querySelector('[data-xw-sf-mode]').value === 'swatches';
        const custom = JSON.parse(row.querySelector('[data-xw-sf-custom-values]').value);
        cards.replaceChildren();
        section.hidden = !terms.length;
        terms.forEach((term, position) => {
            const card = termTemplate.content.firstElementChild.cloneNode(true);
            card.dataset.termId = term.value;
            card.querySelector('[data-term-name]').textContent = term.text;
            const label = card.querySelector('[data-term-label]');
            label.placeholder = term.text;
            label.value = custom[term.value]?.label || '';
            const color = card.querySelector('[data-term-color]');
            color.value = custom[term.value]?.color || '';
            color.disabled = !swatches;
            card.querySelector('[data-term-color-field]').hidden = !swatches;
            const moves = [...card.querySelectorAll('[data-term-move]')];
            moves[0].disabled = position === 0;
            moves[1].disabled = position === terms.length - 1;
            for (const button of moves) button.setAttribute('aria-label', button.getAttribute('aria-label') + ' · ' + term.text);
            cards.append(card);
        });
    }

    function saveTermCards(row) {
        const custom = {};
        for (const card of row.querySelectorAll('[data-term-id]')) {
            custom[card.dataset.termId] = {
                label: card.querySelector('[data-term-label]').value,
                color: card.querySelector('[data-term-color]').value
            };
        }
        row.querySelector('[data-xw-sf-custom-values]').value = JSON.stringify(custom);
    }

    function refreshTerms(row, source) {
        const settings = row.querySelector('[data-xw-sf-term-settings]');
        const limit = row.querySelector('[data-xw-sf-limit]');
        const picker = row.querySelector('[data-xw-sf-term-picker]');
        const select = row.querySelector('[data-xw-sf-terms]');
        const ids = row.querySelector('[data-xw-sf-term-ids]');
        const isTaxonomy = source.selectedOptions[0]?.hasAttribute('data-taxonomy');
        const config = window.xwSmartFilterAdmin;
        const control = window.jQuery(select);
        if (select.dataset.taxonomy && select.dataset.taxonomy !== source.value) {
            if (control.data('select2')) control.selectWoo('destroy');
            control.off('.xwSmartFilter').empty();
            ids.value = '[]';
            row.querySelector('[data-xw-sf-custom-values]').value = '{}';
            limit.value = '0';
        }
        select.dataset.taxonomy = source.value;
        settings.hidden = !isTaxonomy;
        limit.disabled = !isTaxonomy;
        picker.hidden = !isTaxonomy || limit.value !== '1';
        const mode = row.querySelector('[data-xw-sf-mode]').value;
        row.querySelector('[data-xw-sf-layout-field]').hidden = mode === 'select';
        for (const field of row.querySelectorAll('[data-xw-sf-swatch-field]')) field.hidden = mode !== 'swatches';
        renderTermCards(row);
        if (picker.hidden || control.data('select2')) return;
        control.selectWoo({
            width: '100%',
            placeholder: config.placeholder,
            maximumSelectionLength: 100,
            ajax: {
                url: config.ajax,
                delay: 200,
                data: params => ({
                    action: 'xw_sf_terms', nonce: config.nonce, taxonomy: source.value,
                    q: params.term || '', page: params.page || 1
                })
            }
        }).on('change.xwSmartFilter', () => {
            ids.value = JSON.stringify(control.val() || []);
            renderTermCards(row);
            saveTermCards(row);
        });
    }

    function refresh() {
        const fields = [...rows.querySelectorAll('[data-xw-sf-source]')];
        const selected = new Set(fields.map(field => field.value).filter(Boolean));
        fields.forEach((field, position) => {
            const row = field.closest('[data-xw-sf-row]');
            const mode = row.querySelector('[data-xw-sf-mode]');
            const modes = (field.selectedOptions[0]?.dataset.modes || '').split(' ');
            for (const option of field.options) {
                option.disabled = option.value !== field.value && !option.hasAttribute('data-taxonomy') && selected.has(option.value);
            }
            for (const option of mode.options) {
                option.hidden = !modes.includes(option.value);
                option.disabled = option.hidden;
            }
            if (!modes.includes(mode.value)) mode.value = modes[0];
            mode.disabled = !field.value || modes.length === 1;
            refreshTerms(row, field);
            row.querySelector('[data-xw-sf-action="up"]').disabled = position === 0;
            row.querySelector('[data-xw-sf-action="down"]').disabled = position === fields.length - 1;
        });
        add.disabled = fields.length >= 20;
    }

    add.addEventListener('click', () => {
        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelectorAll('[name]').forEach(field => {
            field.name = field.name.replace('INDEX', String(index));
        });
        index += 1;
        rows.append(row);
        refresh();
        row.querySelector('select').focus();
    });

    rows.addEventListener('change', event => {
        if (!event.target.closest('[data-term-id]')) refresh();
    });
    rows.addEventListener('input', event => {
        if (event.target.closest('[data-term-id]')) saveTermCards(event.target.closest('[data-xw-sf-row]'));
    });
    rows.addEventListener('click', event => {
        const move = event.target.closest('[data-term-move]');
        if (move) {
            const row = move.closest('[data-xw-sf-row]');
            const card = move.closest('[data-term-id]');
            const select = row.querySelector('[data-xw-sf-terms]');
            const terms = [...select.selectedOptions];
            const position = terms.findIndex(term => term.value === card.dataset.termId);
            const next = position + Number(move.dataset.termMove);
            if (next < 0 || next >= terms.length) return;
            [terms[position], terms[next]] = [terms[next], terms[position]];
            select.append(...terms);
            window.jQuery(select).trigger('change.select2');
            row.querySelector('[data-xw-sf-term-ids]').value = JSON.stringify(terms.map(term => term.value));
            renderTermCards(row);
            const reordered = row.querySelector('[data-term-id="' + card.dataset.termId + '"]');
            (reordered.querySelector('[data-term-move="' + move.dataset.termMove + '"]:enabled') ||
                reordered.querySelector('[data-term-move]:enabled'))?.focus();
            return;
        }
        const button = event.target.closest('[data-xw-sf-action]');
        if (!button) return;
        const row = button.closest('[data-xw-sf-row]');
        const action = button.dataset.xwSfAction;
        if (action === 'remove') {
            const next = row.nextElementSibling || row.previousElementSibling;
            const select = window.jQuery(row.querySelector('[data-xw-sf-terms]'));
            if (select.data('select2')) select.selectWoo('destroy');
            row.remove();
            (next?.querySelector('[data-xw-sf-source]') || add).focus();
        } else if (action === 'up' && row.previousElementSibling) {
            row.previousElementSibling.before(row);
        } else if (action === 'down' && row.nextElementSibling) {
            row.nextElementSibling.after(row);
        }
        refresh();
        if (action !== 'remove') (button.disabled ? row.querySelector('[data-xw-sf-source]') : button).focus();
    });
    refresh();

    window.jQuery(() => {
        const form = builder.closest('form');
        const bar = form?.querySelector('[data-xw-sf-save-bar]');
        const config = window.xwSmartFilterAdmin;
        if (!bar || !config || !window.fetch) return;
        const button = bar.querySelector('button');
        const status = bar.querySelector('[role="status"]');
        const title = form.querySelector('#title');
        const strings = config.save;
        let revision = config.revision;
        let saving = false;
        let failed = false;
        let message = strings.idle;

        function state() {
            return {
                title: title.value,
                rows: [...rows.children].map(row => Object.fromEntries(
                    [...row.querySelectorAll('[name^="xw_sf_rows["]')].map(field => [
                        field.name.match(/\[([^\]]+)\]$/)[1], field.value
                    ])
                ))
            };
        }
        const snapshot = () => JSON.stringify(state());
        let baseline = snapshot();

        function updateBar() {
            const dirty = snapshot() !== baseline;
            button.disabled = saving || !dirty;
            status.classList.toggle('is-error', failed);
            status.textContent = saving ? strings.saving : failed ? message : dirty ? strings.pending : message;
            form.setAttribute('aria-busy', saving ? 'true' : 'false');
            return dirty;
        }
        function edited() {
            if (saving) return;
            failed = false;
            message = strings.idle;
            updateBar();
        }
        window.jQuery(form).on('input.xwSfSave change.xwSfSave', edited);
        form.addEventListener('click', () => window.setTimeout(() => {
            if (!saving && snapshot() !== baseline) edited();
        }, 0));
        window.addEventListener('pageshow', updateBar);
        window.addEventListener('beforeunload', event => {
            if (saving || snapshot() !== baseline) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
        // This screen tracks the title and all filter rows together.
        window.jQuery(window).off('beforeunload.edit-post');
        window.wp?.autosave?.server?.suspend();
        window.wp?.autosave?.local?.suspend();

        form.addEventListener('submit', async event => {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (saving || !updateBar() || !form.reportValidity()) return;
            const sent = state();
            const body = new URLSearchParams({
                action: 'xw_sf_save_group',
                nonce: form.querySelector('[name="xw_sf_nonce"]').value,
                post_id: form.querySelector('#post_ID').value,
                revision,
                title: sent.title,
                rows: JSON.stringify(sent.rows)
            });
            const controls = [title, ...builder.querySelectorAll('input, select, button')];
            const disabled = controls.map(control => control.disabled);
            controls.forEach(control => { control.disabled = true; });
            saving = true;
            failed = false;
            const label = button.textContent;
            button.textContent = strings.saving;
            updateBar();
            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 30000);
            try {
                const response = await fetch(config.ajax, {
                    method: 'POST', credentials: 'same-origin', body, signal: controller.signal
                });
                const result = await response.json();
                if (!response.ok || !result.success || typeof result.data?.html !== 'string') {
                    const error = new Error(result.data?.message || strings.error);
                    error.isSaveError = true;
                    throw error;
                }
                controls.forEach((control, position) => { control.disabled = disabled[position]; });
                rows.querySelectorAll('[data-xw-sf-terms]').forEach(select => {
                    const control = window.jQuery(select);
                    if (control.data('select2')) control.selectWoo('destroy');
                });
                rows.innerHTML = result.data.html;
                index = rows.children.length;
                title.value = result.data.title;
                revision = result.data.revision;
                form.querySelector('[name="xw_sf_nonce"]').value = result.data.nonce;
                for (const id of ['post_status', 'original_post_status', 'hidden_post_status']) {
                    const field = form.querySelector('#' + id);
                    if (field) {
                        if (field.tagName === 'SELECT' && ![...field.options].some(option => option.value === result.data.status)) {
                            field.add(new Option(result.data.statusLabel, result.data.status));
                        }
                        field.value = result.data.status;
                    }
                }
                refresh();
                baseline = snapshot();
                message = strings.saved;
                button.textContent = strings.button;
                if (result.data.editUrl) history.replaceState(history.state, '', result.data.editUrl);
            } catch (error) {
                failed = true;
                message = error.isSaveError ? error.message : strings.error;
                button.textContent = label;
            } finally {
                window.clearTimeout(timeout);
                controls.forEach((control, position) => { control.disabled = disabled[position]; });
                saving = false;
                updateBar();
            }
        }, true);
        document.body.classList.add('xw-sf-ajax-save');
        bar.hidden = false;
        updateBar();
    });
})();
