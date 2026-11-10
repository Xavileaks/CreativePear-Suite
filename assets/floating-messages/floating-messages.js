(() => {
    'use strict';

    const selector = 'form.elementor-form .elementor-message:not(.elementor-help-inline)';
    const closeLabel = window.xwFloatingMessages?.closeLabel || 'Close notice';
    let activeMessage;

    function showMessage(message) {
        if (message.classList.contains('xw-floating-message') || message.closest('.elementor-field-group') || !message.textContent.trim()) return;

        activeMessage?.close();
        const form = message.closest('form');
        const isError = message.classList.contains('elementor-message-danger') || message.classList.contains('elementor-message-error');
        const isSuccess = message.classList.contains('elementor-message-success');
        const content = document.createElement('div');
        content.className = 'xw-floating-message__text';
        content.append(...message.childNodes);
        for (const element of [message, content, ...content.querySelectorAll('*')]) {
            element.style.setProperty('color', '#000', 'important');
            element.style.setProperty('-webkit-text-fill-color', '#000', 'important');
        }

        const icon = document.createElement('span');
        icon.className = 'xw-floating-message__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = isSuccess ? '✓' : isError ? '!' : 'i';

        const dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'xw-floating-message__close';
        dismiss.setAttribute('aria-label', closeLabel);
        dismiss.textContent = '×';

        const progress = document.createElement('span');
        progress.className = 'xw-floating-message__progress';
        progress.setAttribute('aria-hidden', 'true');

        function close() {
            const restoreFocus = message.contains(document.activeElement);
            if (typeof message.hidePopover === 'function' && message.matches(':popover-open')) message.hidePopover();
            message.hidden = true;
            if (activeMessage?.message === message) activeMessage = undefined;
            if (restoreFocus) form.querySelector('[type="submit"]')?.focus({ preventScroll: true });
        }

        message.classList.add('xw-floating-message');
        message.dataset.xwNotice = isSuccess ? 'success' : isError ? 'error' : 'info';
        if (!message.hasAttribute('role')) message.setAttribute('role', isError ? 'alert' : 'status');
        message.append(icon, content, dismiss, progress);
        dismiss.addEventListener('click', close);
        progress.addEventListener('animationend', close, { once: true });
        activeMessage = { message, close };

        // The top layer keeps the notice above Elementor containers with transforms or overflow clipping.
        if (typeof message.showPopover === 'function') {
            message.setAttribute('popover', 'manual');
            message.showPopover();
        }
    }

    function start() {
        document.querySelectorAll(selector).forEach(showMessage);
        new MutationObserver((mutations) => {
            if (activeMessage && !activeMessage.message.isConnected) activeMessage = undefined;
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType !== Node.ELEMENT_NODE || !node.isConnected) continue;
                    if (node.matches(selector)) showMessage(node);
                    node.querySelectorAll(selector).forEach(showMessage);
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
