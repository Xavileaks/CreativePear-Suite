(() => {
    const formSelector = '[data-xw-sf-form]';
    const gridSelector = '[data-xw-sf-grid]';
    let request;
    let timer;
    let wanted = new URL(location.href);
    const pendingGroups = new Set();

    const grids = (root = document) => [...root.querySelectorAll(gridSelector)];
    const forms = () => [...document.querySelectorAll(formSelector)];
    const status = (form, text) => { form.querySelector('.xw-sf__status').textContent = text; };
    const matchingGrid = (list, grid) => list.find(next =>
        next.dataset.id === grid.dataset.id && next.dataset.xwSfGrid === grid.dataset.xwSfGrid
    );

    function setFormValues(form, url) {
        const prefix = 'xwsf[' + form.dataset.xwSfForm + ']';
        for (const field of form.elements) {
            if (!field.name.startsWith(prefix)) continue;
            const values = url.searchParams.getAll(field.name);
            if (field.name.endsWith('[]')) {
                const stem = field.name.slice(0, -2);
                for (const [name, value] of url.searchParams) {
                    if (name.startsWith(stem + '[') && /^\[\d+\]$/.test(name.slice(stem.length))) values.push(value);
                }
            }
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = values.includes(field.value) || (field.type === 'radio' && !values.length && !field.value);
            } else {
                field.value = values[0] || '';
            }
        }
    }

    function loadStyles(source) {
        for (const style of source.querySelectorAll('head link[rel="stylesheet"][id], head style[id]')) {
            if (!document.getElementById(style.id)) document.head.append(style.cloneNode(true));
        }
    }

    function addEmptyMessage(grid) {
        const content = grid.querySelector(':scope > .elementor-widget-container') || grid;
        if (content.childElementCount || content.textContent.trim()) return;
        const form = forms().find(item => item.dataset.xwSfForm === grid.dataset.xwSfGrid);
        if (!form) return;
        const empty = document.createElement('p');
        empty.textContent = form.dataset.empty;
        content.append(empty);
    }

    function replaceGrid(previous, next) {
        const focusInGrid = previous.contains(document.activeElement);
        previous.replaceWith(next);
        addEmptyMessage(next);
        const frontend = window.elementorFrontend;
        if (frontend?.elementsHandler && window.jQuery) {
            for (const item of [next, ...next.querySelectorAll('.elementor-element[data-element_type]')]) {
                frontend.elementsHandler.runReadyTrigger(window.jQuery(item));
            }
        }
        if (focusInGrid) {
            next.tabIndex = -1;
            next.focus({ preventScroll: true });
        }
    }

    async function update(url, pushHistory) {
        request?.abort();
        const controller = new AbortController();
        request = controller;
        const current = grids().filter(grid => pendingGroups.has(grid.dataset.xwSfGrid));
        const activeForms = forms().filter(form => pendingGroups.has(form.dataset.xwSfForm));
        current.forEach(grid => grid.setAttribute('aria-busy', 'true'));
        activeForms.forEach(form => status(form, ''));
        try {
            const response = await fetch(url, { signal: controller.signal, cache: 'no-cache' });
            if (!response.ok) throw new Error('Could not fetch results');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (controller.signal.aborted) return;
            const upcoming = grids(html);
            const replacements = current.map(grid => matchingGrid(upcoming, grid));
            const missingScript = [...html.querySelectorAll('script[src][id]')].some(script => !document.getElementById(script.id));
            if (!current.length || replacements.some(grid => !grid) || missingScript) {
                location.assign(url);
                return;
            }
            loadStyles(html);
            current.forEach((grid, index) => replaceGrid(grid, replacements[index]));
            if (pushHistory && location.href !== url.href) history.pushState(null, '', url);
            for (const form of forms()) {
                const fresh = [...html.querySelectorAll(formSelector)].find(item => item.dataset.xwSfForm === form.dataset.xwSfForm);
                if (fresh) form.querySelector('.xw-sf__reset').href = fresh.querySelector('.xw-sf__reset').href;
            }
            activeForms.forEach(form => status(form, form.dataset.updated));
            pendingGroups.clear();
        } catch (error) {
            if (controller.signal.aborted) return;
            activeForms.forEach(form => status(form, form.dataset.error));
        } finally {
            if (request === controller) {
                request = undefined;
                current.forEach(grid => grid.removeAttribute('aria-busy'));
            }
        }
    }

    function apply(form, reset = false) {
        clearTimeout(timer);
        const group = form.dataset.xwSfForm;
        const targetGrids = grids().filter(grid => grid.dataset.xwSfGrid === group);
        const prefix = 'xwsf[' + group + ']';
        const url = new URL(wanted);
        const base = new URL(form.action);
        if (url.pathname !== base.pathname || url.searchParams.has('paged') || url.searchParams.has('page')) {
            url.pathname = base.pathname;
            url.searchParams.delete('paged');
            url.searchParams.delete('page');
            grids().forEach(grid => pendingGroups.add(grid.dataset.xwSfGrid));
        }
        for (const key of new Set(url.searchParams.keys())) {
            if (key.startsWith(prefix)) url.searchParams.delete(key);
        }
        for (const grid of targetGrids) url.searchParams.delete('e-page-' + grid.dataset.id);
        if (!reset) {
            for (const [key, value] of new FormData(form)) {
                if (key.startsWith(prefix) && value !== '') url.searchParams.append(key, value);
            }
        }
        if (reset) setFormValues(form, url);
        for (const other of forms()) {
            if (other !== form && other.dataset.xwSfForm === group) setFormValues(other, url);
        }
        pendingGroups.add(group);
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

    document.addEventListener('input', event => {
        const field = event.target;
        const form = field.closest(formSelector);
        if (!form || form.dataset.mode !== 'live' || document.body.classList.contains('elementor-editor-active')) return;
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (form.isConnected && form.checkValidity()) apply(form);
        }, field.type === 'search' || field.type === 'number' ? 400 : 0);
    });

    window.addEventListener('popstate', () => {
        if (!forms().length) return;
        clearTimeout(timer);
        wanted = new URL(location.href);
        forms().forEach(form => {
            setFormValues(form, wanted);
            pendingGroups.add(form.dataset.xwSfForm);
        });
        update(wanted, false);
    });

    if (!document.body.classList.contains('elementor-editor-active')) grids().forEach(addEmptyMessage);
})();
