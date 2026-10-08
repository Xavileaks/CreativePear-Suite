/* global xwWishlist, elementorFrontend, jQuery */
(() => {
    'use strict';
    const config = window.xwWishlist;
    if (!config || window.xwWishlistReady) return;
    window.xwWishlistReady = true;
    let own = null;
    let nonce = '';
    let startup = null;
    let serial = Promise.resolve();
    let generation = 0;
    const shareToken = new URL(window.location.href).searchParams.get('xw_wishlist') || '';
    const initialized = new WeakSet();
    let toastNode = null;
    let toastTimer = null;
    let toastRoot = null;
    const safeUrl = (value) => {
        if (!value || typeof value !== 'string') return '';
        try { const u = new URL(value, window.location.origin); return ['https:', 'http:'].includes(u.protocol) ? u.href : ''; } catch (_) { return ''; }
    };
    const parse = (value, fallback = {}) => { try { return JSON.parse(value || ''); } catch (_) { return fallback; } };
    async function request(operation, data = {}, write = false) {
        const body = new URLSearchParams({action: 'xw_wishlist', operation});
        if (write) body.set('nonce', nonce);
        Object.entries(data).forEach(([key, value]) => {
            if (Array.isArray(value)) value.forEach((item) => body.append(`${key}[]`, String(item)));
            else body.set(key, String(value));
        });
        const response = await fetch(config.ajax, {method: 'POST', credentials: 'same-origin', cache: 'no-store', body});
        const json = await response.json();
        if (!response.ok || !json.success) throw new Error(json.data?.message || config.error);
        if (json.data.nonce) nonce = json.data.nonce;
        return json.data;
    }
    const message = (root, text) => { const status = root.querySelector('.xw-wl-status'); if (status) status.textContent = text; };
    function dismissToast() {
        clearTimeout(toastTimer);
        const restoreFocus = toastNode?.contains(document.activeElement);
        const root = toastRoot;
        toastNode?.remove();
        toastNode = null;
        toastRoot = null;
        if (restoreFocus) (root?.querySelector('[data-xw-wl-cart-row]') || root?.querySelector('.xw-wl-empty a'))?.focus();
    }
    function cartToast(root, text) {
        dismissToast();
        message(root, '');
        const notice = document.createElement('div');
        notice.className = 'xw-wl xw-wl-toast';
        notice.setAttribute('role', 'status');
        notice.setAttribute('aria-live', 'polite');
        notice.setAttribute('aria-atomic', 'true');
        const content = document.createElement('span');
        const close = document.createElement('button');
        close.type = 'button'; close.className = 'xw-wl-toast-close';
        close.textContent = '×'; close.setAttribute('aria-label', config.dismiss || 'Dismiss message');
        close.addEventListener('click', () => {
            dismissToast();
            (root.querySelector('[data-xw-wl-cart-row]') || root.querySelector('.xw-wl-empty a'))?.focus();
        });
        notice.append(content, close);
        document.body.append(notice);
        content.textContent = text;
        toastNode = notice;
        toastRoot = root;
        toastTimer = setTimeout(dismissToast, 5000);
    }
    function selectedProduct(button) {
        const productId = Number(button.dataset.xwWlAdd);
        const context = button.closest('[data-xw-wl]')?.querySelector('[data-xw-wl-product-context]');
        const scope = context?.dataset.xwWlProductContext === 'single' ? document : button.closest('.product') || button.closest('[data-xw-wl]');
        const form = [...(scope?.querySelectorAll('form.variations_form') || [])].find(form => Number(form.dataset.product_id || form.querySelector('[name="product_id"]')?.value) === productId);
        const variationId = Number(form?.querySelector('[name="variation_id"]')?.value || 0);
        const attributes = {};
        if (variationId) form.querySelectorAll('select[name^="attribute_"]').forEach(select => { attributes[select.name] = select.value; });
        return {productId, id: variationId || productId, variationId, attributes};
    }
    function selectedItem(selection) {
        return (own?.items || []).find(item => item.id === selection.id && (!selection.variationId || Object.entries(item.attributes || {}).every(([key, value]) => selection.attributes[key] === value)));
    }
    function updateButtons() {
        document.querySelectorAll('[data-xw-wl-add]').forEach((button) => {
            if (button.closest('[data-xw-wl-editor]')) return;
            const added = Boolean(selectedItem(selectedProduct(button)));
            button.classList.toggle('is-added', added);
            button.setAttribute('aria-pressed', String(added));
            const label = added ? button.dataset.xwWlAddedLabel : button.dataset.xwWlLabel;
            const text = button.querySelector('.xw-wl-add-label');
            if (text) text.textContent = label;
            button.setAttribute('aria-label', `${label}: ${button.dataset.xwWlProductName}`);
        });
        document.querySelectorAll('[data-xw-wl="counter"]').forEach((root) => {
            const editor = root.hasAttribute('data-xw-wl-editor');
            const count = editor ? Number(root.dataset.xwWlPreviewCount || 4) : own?.count || 0;
            const badge = root.querySelector('[data-xw-wl-count]');
            (badge.querySelector('.xw-wl-badge-value') || badge).textContent = String(count);
            badge.hidden = count === 0 && root.dataset.xwWlShowZero !== 'yes';
            root.querySelector('[data-xw-wl-count-label]').textContent = ` (${count})`;
            const link = root.querySelector('[data-xw-wl-counter-label]');
            if (link) link.setAttribute('aria-label', `${link.dataset.xwWlCounterLabel} (${count})`);
        });
    }
    function renderTable(root, data) {
        const labels = parse(root.dataset.xwWlLabels);
        const body = root.querySelector('tbody');
        const template = root.querySelector('[data-xw-wl-row]');
        if (!body || !template) return;
        const previous = new Set([...root.querySelectorAll('[data-xw-wl-select]:checked')].map((c) => c.closest('tr').dataset.wishlistKey || c.closest('tr').dataset.productId));
        body.replaceChildren();
        root.classList.toggle('is-read-only', Boolean(data.read_only));
        root.dataset.xwWlReadOnly = data.read_only ? '1' : '';
        (data.items || []).forEach((product) => {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('tr');
            row.dataset.productId = String(product.id);
            row.dataset.wishlistKey = product.key || String(product.id);
            row.dataset.productUrl = safeUrl(product.url);
            row.dataset.simple = product.simple ? '1' : '';
            row.querySelectorAll('[data-xw-wl-link]').forEach((a) => { a.href = safeUrl(product.url); });
            const image = row.querySelector('img');
            if (image) { image.src = safeUrl(product.image); image.alt = product.name; }
            row.querySelector('[data-xw-wl-field="name"]').textContent = product.name;
            // El servidor devuelve únicamente HTML de precio filtrado con wp_kses_post.
            row.querySelector('[data-xw-wl-field="price"]').innerHTML = product.price;
            const date = row.querySelector('[data-xw-wl-field="date"]');
            if (date) date.textContent = product.date;
            const stock = row.querySelector('.xw-wl-stock');
            if (stock) {
                stock.classList.add(product.in_stock ? 'is-available' : 'is-unavailable');
                stock.querySelector('[data-xw-wl-stock-text]').textContent = product.in_stock ? labels.stock : labels.unavailable;
            }
            row.querySelector('[data-xw-wl-remove]').setAttribute('aria-label', `${config.removeLabel || 'Remove'}: ${product.name}`);
            const select = row.querySelector('[data-xw-wl-select]');
            if (select) {
                select.checked = previous.has(row.dataset.wishlistKey);
                row.querySelector('[data-xw-wl-select-label]').textContent = `${config.selectLabel || 'Select'}: ${product.name}`;
            }
            const action = row.querySelector('[data-xw-wl-cart-row]');
            action.querySelector('[data-xw-wl-action-text]').textContent = data.read_only || !product.purchasable ? labels.view : (product.cartable ?? product.simple) ? labels.cart : labels.options;
            action.dataset.cartable = (product.cartable ?? product.simple) && product.purchasable && !data.read_only ? '1' : '';
            body.append(fragment);
        });
        root.querySelector('.xw-wl-empty').hidden = (data.items || []).length > 0;
        root.querySelector('.xw-wl-data').hidden = !data.items?.length;
        root.querySelector('.xw-wl-data').setAttribute('aria-busy', 'false');
        const bulk = root.querySelector('.xw-wl-bulk');
        if (bulk) bulk.hidden = !data.items?.length || data.read_only;
        const share = root.querySelector('.xw-wl-share');
        if (share) share.hidden = !data.items?.length;
        const all = root.querySelector('[data-xw-wl-select-all]');
        if (all) { all.checked = false; all.indeterminate = false; }
    }
    function sync(data) {
        own = data;
        updateButtons();
        document.querySelectorAll('[data-xw-wl="table"]:not([data-xw-wl-editor])').forEach((root) => {
            if (!shareToken) renderTable(root, data);
        });
        window.dispatchEvent(new CustomEvent('xw:wishlist-changed', {detail: {count: data.count}}));
    }
    function load() {
        if (!startup) startup = request('state').then((data) => { sync(data); return data; }).catch((error) => { startup = null; throw error; });
        return startup;
    }
    async function busy(root, button, operation, data) {
        if (button.disabled) return;
        button.disabled = true;
        ++generation;
        button.setAttribute('aria-busy', 'true');
        try {
            const task = serial.catch(() => {}).then(async () => { await load(); return request(operation, data, true); });
            serial = task;
            const result = await task;
            if (result.items) sync(result);
            return result;
        } catch (error) { message(root, error.message || config.error); return null; }
        finally { button.disabled = false; button.removeAttribute('aria-busy'); }
    }
    async function share(root, button) {
        const network = button.dataset.xwWlShare;
        const result = shareToken ? {url: window.location.href} : await busy(root, button, 'share');
        if (!result) return;
        const url = safeUrl(result.url);
        if (!url) { message(root, config.error); return; }
        const encoded = encodeURIComponent(url);
        const title = encodeURIComponent(root.querySelector('.xw-wl-title')?.textContent || 'Wishlist');
        const routes = {
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encoded}`,
            x: `https://twitter.com/intent/tweet?url=${encoded}`,
            pinterest: `https://pinterest.com/pin/create/button/?url=${encoded}`,
            whatsapp: `https://api.whatsapp.com/send?text=${encoded}`,
            telegram: `https://t.me/share/url?url=${encoded}&text=${title}`,
            linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encoded}`,
            reddit: `https://www.reddit.com/submit?url=${encoded}&title=${title}`,
        };
        if (routes[network]) {
            // Never reserve an about:blank window. Only open a validated final URL.
            // Async popup blocking must not leave the user stranded: navigate directly.
            try {
                const popup = window.open(routes[network], '_blank');
                if (popup) popup.opener = null;
                else window.location.href = routes[network];
            } catch (_) { window.location.href = routes[network]; }
        } else if (network === 'email') window.location.href = `mailto:?subject=${title}&body=${encoded}`;
        else if (network === 'copy') {
            try { await navigator.clipboard.writeText(url); message(root, config.copied); }
            catch (_) {
                const input = document.createElement('input'); input.readOnly = true; input.value = url;
                input.setAttribute('aria-label', config.copyLabel || 'Wishlist link');
                root.querySelector('.xw-wl-status').replaceChildren(input); input.focus(); input.select();
            }
        }
    }
    async function init(root) {
        if (initialized.has(root)) return;
        initialized.add(root);
        if (root.hasAttribute('data-xw-wl-editor')) {
            if (root.dataset.xwWl === 'table') renderTable(root, parse(root.dataset.xwWlPreview, {items: []}));
            updateButtons();
            // Solo vista previa: no modificar deseos ni carrito al diseñar.
            root.addEventListener('click', (event) => event.preventDefault());
            return;
        }
        try {
            await load();
            if (root.dataset.xwWl === 'table') renderTable(root, shareToken ? await request('state', {share: shareToken}) : own);
            updateButtons();
        } catch (error) { message(root, error.message || config.error); root.querySelector('.xw-wl-data')?.setAttribute('aria-busy', 'false'); }
    }
    document.addEventListener('click', async (event) => {
        const root = event.target.closest('[data-xw-wl]');
        if (!root || root.hasAttribute('data-xw-wl-editor')) return;
        const add = event.target.closest('[data-xw-wl-add]');
        if (add) {
            event.preventDefault();
            if (add.disabled) return;
            const selection = selectedProduct(add);
            const item = selectedItem(selection);
            if (item && add.dataset.xwWlAddedAction === 'view' && safeUrl(add.dataset.xwWlUrl)) { window.location.href = safeUrl(add.dataset.xwWlUrl); return; }
            const operation = item ? 'remove' : 'add';
            const result = await busy(root, add, operation, item ? {key: item.key || String(item.id)} : {product_id: selection.productId, variation_id: selection.variationId, attributes: JSON.stringify(selection.attributes)});
            if (result) message(root, operation === 'add' ? config.saved : config.removed);
            return;
        }
        const remove = event.target.closest('[data-xw-wl-remove]');
        if (remove && !root.dataset.xwWlReadOnly) {
            event.preventDefault();
            const row = remove.closest('tr');
            const next = row.nextElementSibling?.querySelector('[data-xw-wl-remove]') || row.previousElementSibling?.querySelector('[data-xw-wl-remove]');
            const nextId = next?.closest('tr').dataset.wishlistKey;
            if (await busy(root, remove, 'remove', {key: row.dataset.wishlistKey || row.dataset.productId})) {
                message(root, config.removed);
                const focus = [...root.querySelectorAll('tr')].find((r) => r.dataset.wishlistKey === nextId)?.querySelector('[data-xw-wl-remove]') || root.querySelector('.xw-wl-empty a');
                focus?.focus();
            }
            return;
        }
        const rowButton = event.target.closest('[data-xw-wl-cart-row]');
        const selected = event.target.closest('[data-xw-wl-cart-selected]');
        const all = event.target.closest('[data-xw-wl-cart-all]');
        if (rowButton || selected || all) {
            event.preventDefault();
            const button = rowButton || selected || all;
            if (rowButton && !rowButton.dataset.cartable) { window.location.href = safeUrl(rowButton.closest('tr').dataset.productUrl); return; }
            if (root.dataset.xwWlReadOnly) return;
            const itemKey = row => row.dataset.wishlistKey || row.dataset.productId;
            const ids = rowButton ? [itemKey(rowButton.closest('tr'))] : [...root.querySelectorAll(selected ? '[data-xw-wl-select]:checked' : 'tbody tr')].map(el => itemKey(el.closest('tr')));
            if (!ids.length) { message(root, config.select); return; }
            const hadFocus = document.activeElement === button;
            const result = await busy(root, button, 'cart', {ids});
            if (result) {
                cartToast(root, result.message);
                if (hadFocus && !button.isConnected) (root.querySelector('[data-xw-wl-cart-row]') || root.querySelector('.xw-wl-empty a'))?.focus();
                if (result.added > 0) {
                    if (window.jQuery) { jQuery(document.body).trigger('wc_fragment_refresh'); jQuery(document.body).trigger('wc-blocks_added_to_cart'); }
                    window.dispatchEvent(new CustomEvent('wc-blocks_added_to_cart', {detail: {preserveCartData: false}}));
                }
            }
            return;
        }
        const shareButton = event.target.closest('[data-xw-wl-share]');
        if (shareButton) { event.preventDefault(); if (!shareButton.disabled) await share(root, shareButton); }
    });
    document.addEventListener('change', (event) => {
        if (event.target.matches('form.variations_form select, form.variations_form [name="variation_id"]')) updateButtons();
        const root = event.target.closest('[data-xw-wl="table"]');
        if (!root) return;
        if (event.target.matches('[data-xw-wl-select-all]')) root.querySelectorAll('[data-xw-wl-select]').forEach((c) => { c.checked = event.target.checked; });
        if (event.target.matches('[data-xw-wl-select]')) {
            const checkboxes = [...root.querySelectorAll('[data-xw-wl-select]')];
            const selected = checkboxes.filter((c) => c.checked).length;
            const all = root.querySelector('[data-xw-wl-select-all]');
            if (all) { all.checked = selected === checkboxes.length; all.indeterminate = selected > 0 && selected < checkboxes.length; }
        }
    });
    const scan = (container = document) => { container.querySelectorAll('[data-xw-wl]').forEach(init); };
    if (window.jQuery) jQuery(document).on('found_variation.xwWishlist reset_data.xwWishlist hide_variation.xwWishlist', 'form.variations_form', () => setTimeout(updateButtons, 0));
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan()); else scan();
    // Elementor vuelve a renderizar widgets en el editor, loops y popups.
    const observer = new MutationObserver((records) => records.forEach((record) => record.addedNodes.forEach((node) => {
        if (node.nodeType !== 1) return;
        if (node.matches('[data-xw-wl]')) init(node);
        scan(node);
    })));
    observer.observe(document.documentElement, {childList: true, subtree: true});
    window.addEventListener('pageshow', (event) => { if (event.persisted) { startup = null; load().catch(() => {}); } });
    window.addEventListener('focus', () => { if (own) { const expected = generation; request('state').then((data) => { if (generation === expected) sync(data); }).catch(() => {}); } });
})();
