(function () {
    'use strict';
    var selector = 'form.elementor-form textarea';
    var messages = window.xwMessageValidation || {
        characters: 'Usa solo letras del español o del inglés, números y signos de puntuación habituales.',
        link: 'El mensaje no puede contener enlaces. Elimínalos para continuar.'
    };
    var allowed = /^[A-Za-z0-9áéíóúüñÁÉÍÓÚÜÑ \t\r\n.,;:!?¿¡'"’“”«»()\[\]\-–—+%&€$#@/\\=*_]*$/u;
    var forbiddenUrl = /(?:\b(?:https?|hxxps?|ftp)\s*:|\bwww\s*\.|\b(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})\b|\b(?:\d{1,3}\.){3}\d{1,3}\b|\[\s*(?:url|link)\b)/i;
    var errors = new WeakMap();
    var index = 0;
    var decoder = document.createElement('textarea');
    function errorCode(value) {
        var normal = value.normalize('NFC');
        if (!allowed.test(normal)) return 'characters';
        // The allowlist above rejects raw < and >. A detached textarea decodes
        // entities as text; decoded markup is never inserted into the page.
        decoder.innerHTML = normal;
        var candidate = decoder.value;
        for (var i = 0; i < 2; i++) { try { candidate = decodeURIComponent(candidate); } catch (_) { break; } }
        if (!allowed.test(candidate.normalize('NFC'))) return 'characters';
        return forbiddenUrl.test(candidate) ? 'link' : '';
    }
    function validate(field) {
        var code = errorCode(field.value);
        var error = errors.get(field);
        if (!error) {
            error = document.createElement('span');
            error.id = 'xw-message-error-' + (++index);
            error.className = 'xw-message-error';
            error.setAttribute('aria-live', 'polite');
            field.insertAdjacentElement('afterend', error);
            errors.set(field, error);
        }
        error.textContent = code ? messages[code] : '';
        error.hidden = !code;
        field.dataset.xwMessageValid = code ? '0' : '1';
        field.classList.toggle('xl-invalid', !!code);
        field.setAttribute('aria-invalid', code ? 'true' : 'false');
        field.setCustomValidity(code ? messages[code] : '');
        var descriptions = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(function (id) { return id && id !== error.id; });
        if (code) descriptions.push(error.id);
        if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' '));
        else field.removeAttribute('aria-describedby');
        var form = field.closest('form');
        var invalid = !!form.querySelector('[data-xw-message-valid="0"], [data-xw-phone-valid="0"]');
        form.querySelectorAll('button.elementor-button[type="submit"]').forEach(function (button) {
            button.disabled = invalid;
            button.classList.toggle('xl-submit-disabled', invalid);
            button.classList.toggle('xw-submit-disabled', invalid);
        });
        return !code;
    }
    function init(root) {
        if (root.matches && root.matches(selector)) validate(root);
        if (root.querySelectorAll) root.querySelectorAll(selector).forEach(validate);
    }
    document.addEventListener('input', function (event) { if (event.target.matches(selector)) validate(event.target); });
    document.addEventListener('submit', function (event) {
        if (!event.target.matches('form.elementor-form')) return;
        var valid = true;
        event.target.querySelectorAll('textarea').forEach(function (field) { if (!validate(field)) valid = false; });
        if (!valid) { event.preventDefault(); event.stopImmediatePropagation(); }
    }, true);
    function start() {
        init(document);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) { mutation.addedNodes.forEach(function (node) { if (node.nodeType === 1) init(node); }); });
        }).observe(document.body, {childList: true, subtree: true});
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
