(() => {
    const builder = document.querySelector('[data-xw-sf-builder]');
    if (!builder) return;

    const rows = builder.querySelector('[data-xw-sf-rows]');
    const template = builder.querySelector('[data-xw-sf-template]');
    const add = builder.querySelector('[data-xw-sf-add]');
    let index = rows.children.length;

    function refresh() {
        const fields = [...rows.querySelectorAll('[data-xw-sf-source]')];
        const selected = new Set(fields.map(field => field.value).filter(Boolean));
        fields.forEach((field, position) => {
            const row = field.closest('[data-xw-sf-row]');
            const mode = row.querySelector('[data-xw-sf-mode]');
            const modes = (field.selectedOptions[0]?.dataset.modes || '').split(' ');
            for (const option of field.options) {
                option.disabled = option.value !== field.value && selected.has(option.value);
            }
            for (const option of mode.options) {
                option.hidden = !modes.includes(option.value);
                option.disabled = option.hidden;
            }
            if (!modes.includes(mode.value)) mode.value = modes[0];
            mode.disabled = !field.value || modes.length === 1;
            row.querySelector('[data-xw-sf-action="up"]').disabled = position === 0;
            row.querySelector('[data-xw-sf-action="down"]').disabled = position === fields.length - 1;
        });
        add.disabled = fields.length >= Math.min(20, template.content.querySelector('[data-xw-sf-source]').options.length - 1);
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

    rows.addEventListener('change', refresh);
    rows.addEventListener('click', event => {
        const button = event.target.closest('[data-xw-sf-action]');
        if (!button) return;
        const row = button.closest('[data-xw-sf-row]');
        const action = button.dataset.xwSfAction;
        if (action === 'remove') {
            const next = row.nextElementSibling || row.previousElementSibling;
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
})();
