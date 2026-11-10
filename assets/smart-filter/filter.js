(() => {
    const formSelector = '[data-xw-sf-form]';
    const gridSelector = '[data-xw-sf-grid]';
    let request;
    let timer;
    let wanted = new URL(location.href);
    let pageGroup = history.state?.xwSfPage || '';
    const pendingGroups = new Map();
    const busyGrids = new Set();

    const forms = () => [...document.querySelectorAll(formSelector)];
    const status = (form, text) => { form.querySelector('.xw-sf__status').textContent = text; };
    const targetKey = group => 'xwsf_grid[' + group + ']';
    const groupKey = group => 'xwsf[' + group + ']';

    function findGrid(root, form) {
        const id = form.dataset.gridId;
        if (id) {
            const grid = root.getElementById(id);
            return grid?.classList.contains('elementor-widget-loop-grid') ? grid : null;
        }
        const matches = [...root.querySelectorAll(gridSelector)].filter(grid => grid.dataset.xwSfGrid === form.dataset.xwSfForm);
        return matches.length === 1 ? matches[0] : null;
    }

    function syncPrice(container, input) {
        const slider = container.querySelector('.xw-sf__slider');
        const [low, high] = slider.querySelectorAll('input');
        const [minInput, maxInput] = container.querySelectorAll('input[type="number"]');
        const min = Number(low.min);
        const max = Number(low.max);
        const clamp = value => Math.min(max, Math.max(min, value));
        let from = clamp(minInput.value === '' ? min : minInput.valueAsNumber);
        let to = clamp(maxInput.value === '' ? max : maxInput.valueAsNumber);
        if (input === low) {
            from = Math.min(low.valueAsNumber, to);
            minInput.value = from;
        } else if (input === high) {
            to = Math.max(high.valueAsNumber, from);
            maxInput.value = to;
        }
        if (from > to) [from, to] = [to, from];
        low.value = from;
        high.value = to;
        low.style.zIndex = from === to && to === max ? '3' : '1';
        slider.style.setProperty('--xw-sf-low', ((from - min) / (max - min) * 100) + '%');
        slider.style.setProperty('--xw-sf-high', ((to - min) / (max - min) * 100) + '%');
        slider.hidden = false;
    }

    function valueKeys(url, name) {
        if (!name.endsWith('[]')) return [name];
        const stem = name.slice(0, -2);
        return [...new Set([name, ...[...url.searchParams.keys()].filter(key =>
            key.startsWith(stem + '[') && /^\[\d+\]$/.test(key.slice(stem.length))
        )])];
    }

    function valuesFor(url, name) {
        return valueKeys(url, name).flatMap(key => url.searchParams.getAll(key));
    }

    function setFormValues(form, url) {
        const prefix = groupKey(form.dataset.xwSfForm);
        for (const field of form.elements) {
            if (!field.name.startsWith(prefix)) continue;
            const values = valuesFor(url, field.name);
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = values.includes(field.value) || (field.type === 'radio' && !values.length && !field.value);
            } else {
                field.value = values[0] || '';
            }
        }
        form.querySelectorAll('[data-xw-sf-price]').forEach(container => syncPrice(container));
    }

    function renderChips(form, url) {
        const chips = form.querySelector('.xw-sf__chips');
        if (!chips) return;
        const focusIndex = [...chips.children].indexOf(document.activeElement);
        chips.replaceChildren();
        function chip(label, fields) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'xw-sf__chip';
            button.textContent = label + ' ×';
            button.setAttribute('aria-label', chips.dataset.remove.replace('%s', label));
            button.addEventListener('click', () => {
                const next = new URL(wanted);
                for (const { name, value } of fields) {
                    const keep = value === undefined ? [] : valuesFor(next, name).filter(item => item !== value);
                    for (const key of valueKeys(next, name)) next.searchParams.delete(key);
                    for (const item of keep) next.searchParams.append(name, item);
                }
                setFormValues(form, next);
                apply(form);
            });
            chips.append(button);
        }
        for (const fieldset of form.querySelectorAll('.xw-sf__field')) {
            const title = fieldset.querySelector('legend').textContent.trim();
            const fields = [...fieldset.querySelectorAll('input[name], select[name]')];
            const range = fields.filter(field => field.type === 'number');
            if (range.length) {
                const bounds = range.map(field => {
                    const value = valuesFor(url, field.name)[0];
                    return value ? field.closest('label').textContent.trim() + ' ' + value : '';
                }).filter(Boolean);
                if (bounds.length) chip(title + ': ' + bounds.join(' – '), range.map(field => ({ name: field.name })));
                continue;
            }
            for (const field of fields) {
                const values = valuesFor(url, field.name);
                if (field.type === 'checkbox' || field.type === 'radio') {
                    if (field.value && values.includes(field.value)) {
                        chip(title + ': ' + field.closest('label').textContent.trim(), [{ name: field.name, value: field.value }]);
                    }
                } else if (values[0]) {
                    const label = field.options ? [...field.options].find(option => option.value === values[0])?.text : values[0];
                    if (label) chip(title + ': ' + label, [{ name: field.name }]);
                }
            }
        }
        chips.hidden = !chips.childElementCount;
        if (focusIndex >= 0) (chips.children[Math.min(focusIndex, chips.children.length - 1)] || form.querySelector('.xw-sf__apply'))?.focus();
    }

    function renderCount(form, grid) {
        const count = form.querySelector('.xw-sf__count');
        if (!count || !grid?.hasAttribute('data-xw-sf-count')) return;
        const total = Number(grid.dataset.xwSfCount);
        count.textContent = (total === 1 ? count.dataset.one : count.dataset.many).replace('%s', total.toLocaleString());
        count.hidden = false;
    }

    function loadStyles(source, grid) {
        const templates = new Set([...grid.querySelectorAll('[data-elementor-id]')].map(item => item.dataset.elementorId));
        for (const id of templates) {
            for (const suffix of ['-css', '-inline-css']) {
                const key = 'elementor-post-' + id + suffix;
                const style = source.getElementById(key);
                if (style?.matches('link[rel="stylesheet"], style') && !document.getElementById(key)) {
                    document.head.append(style.cloneNode(true));
                }
            }
        }
    }

    function addEmptyMessage(grid, form) {
        const content = grid.querySelector(':scope > .elementor-widget-container') || grid;
        if (content.childElementCount || content.textContent.trim()) return;
        if (!form) return;
        const empty = document.createElement('p');
        empty.textContent = form.dataset.empty;
        content.append(empty);
    }

    function replaceGrid(previous, next, form) {
        const focusInGrid = previous.contains(document.activeElement);
        previous.replaceWith(next);
        addEmptyMessage(next, form);
        const frontend = window.elementorFrontend;
        if (typeof frontend?.elementsHandler?.runReadyTrigger === 'function' && window.jQuery) {
            requestAnimationFrame(() => {
                if (!next.isConnected) return;
                for (const item of [next, ...next.querySelectorAll('.elementor-element[data-element_type]')]) {
                    frontend.elementsHandler.runReadyTrigger(window.jQuery(item));
                }
            });
        }
        if (focusInGrid) {
            next.tabIndex = -1;
            next.focus({ preventScroll: true });
        }
    }

    async function update(url, pushHistory) {
        request?.abort();
        for (const grid of busyGrids) grid.removeAttribute('aria-busy');
        busyGrids.clear();
        const controller = new AbortController();
        request = controller;
        const activeForms = forms().filter(form => pendingGroups.get(form.dataset.xwSfForm)?.dataset.gridId === form.dataset.gridId);
        const current = new Map();
        activeForms.forEach(form => status(form, ''));
        for (const form of activeForms) {
            const grid = findGrid(document, form);
            if (grid) {
                current.set(grid, form);
                grid.setAttribute('aria-busy', 'true');
                busyGrids.add(grid);
            }
        }
        try {
            if (!current.size) return;
            const requestUrl = new URL(url);
            requestUrl.searchParams.set('xwsf_refresh', '1');
            for (const form of activeForms) {
                if (form.dataset.gridId) requestUrl.searchParams.set(targetKey(form.dataset.xwSfForm), form.dataset.gridId);
            }
            const response = await fetch(requestUrl, { signal: controller.signal, cache: 'no-store' });
            if (!response.ok) throw new Error('Could not fetch results');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (controller.signal.aborted) return;
            const replacements = [];
            for (const [grid, form] of current) {
                const next = findGrid(html, form);
                if (!next || next.dataset.id !== grid.dataset.id || next.dataset.xwSfGrid !== form.dataset.xwSfForm) {
                    status(form, form.dataset.unlinked);
                    return;
                }
                replacements.push([grid, next, form]);
            }
            for (const [grid, next, form] of replacements) {
                loadStyles(html, next);
                replaceGrid(grid, next, form);
            }
            if (pushHistory && location.href !== url.href) history.pushState({ ...history.state, xwSfPage: pageGroup }, '', url);
            document.title = html.title || document.title;
            for (const form of forms()) {
                const fresh = [...html.querySelectorAll(formSelector)].find(item => item.dataset.xwSfForm === form.dataset.xwSfForm && item.dataset.gridId === form.dataset.gridId);
                if (fresh) form.querySelector('.xw-sf__reset').href = fresh.querySelector('.xw-sf__reset').href;
            }
            activeForms.forEach(form => {
                renderChips(form, url);
                renderCount(form, findGrid(document, form));
                status(form, form.dataset.updated);
            });
        } catch (error) {
            if (controller.signal.aborted) return;
            activeForms.forEach(form => status(form, form.dataset.error));
        } finally {
            if (request === controller) {
                request = undefined;
                for (const grid of busyGrids) grid.removeAttribute('aria-busy');
                busyGrids.clear();
                pendingGroups.clear();
                wanted = new URL(location.href);
                pageGroup = history.state?.xwSfPage || '';
            }
        }
    }

    function apply(form, reset = false) {
        clearTimeout(timer);
        const group = form.dataset.xwSfForm;
        const grid = findGrid(document, form);
        if (!grid) {
            status(form, form.dataset.unlinked);
            return;
        }
        const prefix = groupKey(group);
        const url = new URL(wanted);
        const base = new URL(form.action);
        if (url.pathname !== base.pathname || url.searchParams.has('paged') || url.searchParams.has('page')) {
            url.pathname = base.pathname;
            url.searchParams.delete('paged');
            url.searchParams.delete('page');
            pageGroup = '';
        }
        for (const key of new Set(url.searchParams.keys())) {
            if (key.startsWith(prefix)) url.searchParams.delete(key);
        }
        url.searchParams.delete('e-page-' + grid.dataset.id);
        if (form.dataset.gridId) url.searchParams.set(targetKey(group), form.dataset.gridId);
        else url.searchParams.delete(targetKey(group));
        if (!reset) {
            for (const [key, value] of new FormData(form)) {
                if (key.startsWith(prefix) && value !== '') url.searchParams.append(key, value);
            }
        }
        if (reset) setFormValues(form, url);
        for (const other of forms()) {
            if (other !== form && other.dataset.xwSfForm === group && other.dataset.gridId === form.dataset.gridId) setFormValues(other, url);
        }
        pendingGroups.set(group, form);
        wanted = url;
        update(url, true);
    }

    document.addEventListener('submit', event => {
        if (!event.target.matches(formSelector)) return;
        event.preventDefault();
        if (!document.body.classList.contains('elementor-editor-active')) apply(event.target);
    });

    document.addEventListener('click', event => {
        const link = event.target.closest('.xw-sf__reset');
        const form = link?.closest(formSelector);
        if (!form || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey) return;
        if (document.body.classList.contains('elementor-editor-active')) {
            event.preventDefault();
            return;
        }
        event.preventDefault();
        apply(form, true);
    });

    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey ||
            document.body.classList.contains('elementor-editor-active')) return;
        const link = event.target.closest('.elementor-pagination a[href]');
        if (!link) return;
        const grid = link.closest('.elementor-widget-loop-grid');
        if (!grid) return;
        const form = forms().find(item => item.dataset.pagination === 'yes' && findGrid(document, item) === grid);
        if (!form) return;
        const url = new URL(link.href);
        if (url.origin !== location.origin) return;
        const group = form.dataset.xwSfForm;
        const keys = new Set([...wanted.searchParams.keys(), ...url.searchParams.keys()]);
        for (const key of keys) {
            if (!key.startsWith('xwsf[') && !key.startsWith('xwsf_grid[')) continue;
            url.searchParams.delete(key);
            for (const value of wanted.searchParams.getAll(key)) url.searchParams.append(key, value);
        }
        if (form.dataset.gridId) url.searchParams.set(targetKey(group), form.dataset.gridId);
        event.preventDefault();
        event.stopPropagation();
        pageGroup = group;
        wanted = url;
        pendingGroups.set(group, form);
        update(url, true);
    }, true);

    document.addEventListener('input', event => {
        const field = event.target;
        const form = field.closest(formSelector);
        const price = field.closest('[data-xw-sf-price]');
        if (price) syncPrice(price, field);
        if (!form || form.dataset.mode !== 'live' || document.body.classList.contains('elementor-editor-active')) return;
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (form.isConnected && form.checkValidity()) apply(form);
        }, ['search', 'number', 'range'].includes(field.type) ? 400 : 0);
    });

    window.addEventListener('popstate', () => {
        if (!forms().length) return;
        clearTimeout(timer);
        const previous = wanted;
        const previousPageGroup = pageGroup;
        pageGroup = history.state?.xwSfPage || '';
        wanted = new URL(location.href);
        forms().forEach(form => {
            const group = form.dataset.xwSfForm;
            const grid = findGrid(document, form);
            const target = wanted.searchParams.get(targetKey(group)) || previous.searchParams.get(targetKey(group));
            if (target && form.dataset.gridId !== target) return;
            const keys = url => [...url.searchParams].filter(([key]) =>
                key.startsWith(groupKey(group)) || key === targetKey(group) || (grid && key === 'e-page-' + grid.dataset.id)
            );
            const pageChanged = [previousPageGroup, pageGroup].includes(group) &&
                (previous.pathname !== wanted.pathname || ['page', 'paged'].some(key => previous.searchParams.get(key) !== wanted.searchParams.get(key)));
            if (!pageChanged && JSON.stringify(keys(previous)) === JSON.stringify(keys(wanted))) return;
            setFormValues(form, wanted);
            if (grid) pendingGroups.set(group, form);
        });
        if (pendingGroups.size) update(wanted, false);
    });

    function prepare(root = document) {
        root.querySelectorAll('[data-xw-sf-price]').forEach(container => syncPrice(container));
        if (document.body.classList.contains('elementor-editor-active')) return;
        forms().forEach(form => {
            const grid = findGrid(document, form);
            if (grid) addEmptyMessage(grid, form);
            renderCount(form, grid);
            renderChips(form, wanted);
        });
    }

    prepare();
    function bindEditor() {
        window.elementorFrontend?.hooks?.addAction('frontend/element_ready/xw-smart-filter.default', $scope => prepare($scope[0]));
    }
    if (window.elementorFrontend?.hooks) bindEditor();
    else if (window.jQuery) window.jQuery(window).on('elementor/frontend/init', bindEditor);
})();
