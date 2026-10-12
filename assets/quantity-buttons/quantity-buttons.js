(function ($) {
    'use strict';
    var strings = window.xwQuantityButtons || { minus: 'Decrease quantity', plus: 'Increase quantity' };
    var sequence = 0;

    function number(value) {
        if (value == null || String(value).trim() === '') return null;
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    // Stay on the input's native step grid, including fractional quantities.
    // Never remove step or exceed a max which is not itself a valid step.
    function next(input, direction) {
        var min = number(input.getAttribute('min'));
        var max = number(input.getAttribute('max'));
        var step = number(input.getAttribute('step'));
        var current = number(input.value);
        if (min === null) min = 0;
        if (step === null || step <= 0) step = 1;
        if (max !== null && max < min) return current;
        var origin = number(input.getAttribute('min'));
        if (origin === null) origin = number(input.getAttribute('value')) || 0;
        var tick;
        if (current === null) tick = Math.ceil(((direction > 0 && min === 0 ? step : min) - origin) / step - 1e-9);
        else {
            var position = (current - origin) / step;
            tick = direction > 0 ? Math.floor(position + 1e-9) + 1 : Math.ceil(position - 1e-9) - 1;
        }
        tick = Math.max(tick, Math.ceil((min - origin) / step - 1e-9));
        if (max !== null) tick = Math.min(tick, Math.floor((max - origin) / step + 1e-9));
        var result = Number((origin + tick * step).toFixed(12));
        return result < min || (max !== null && result > max) ? current : result;
    }

    function update(input) {
        var container = input.closest('.xw-quantity');
        if (!container) return;
        var current = number(input.value);
        container.querySelectorAll('[data-xw-quantity]').forEach(function (button) {
            button.disabled = input.disabled || input.readOnly || input.type === 'hidden' || next(input, Number(button.dataset.xwQuantity)) === current;
        });
    }

    function enhance(input) {
        var container = input.closest('.quantity');
        if (!container || input.closest('.wc-block-components-quantity-selector')) return;
        if (container.classList.contains('xw-quantity')) { update(input); return; }
        if (input.type === 'hidden' || input.readOnly || input.disabled) return;
        // Preserve controls supplied by a theme or another quantity plugin.
        if (container.querySelector('button, .plus, .minus, .qib-button') || container.closest('.qib-button-wrapper')) return;
        if (number(input.getAttribute('max')) !== null && number(input.getAttribute('max')) === number(input.getAttribute('min'))) return;
        if (!input.id) input.id = 'xw-quantity-' + (++sequence);
        [-1, 1].forEach(function (direction) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'xw-quantity-button';
            button.dataset.xwQuantity = String(direction);
            button.textContent = direction < 0 ? '−' : '+';
            button.setAttribute('aria-label', direction < 0 ? strings.minus : strings.plus);
            button.setAttribute('aria-controls', input.id);
            if (direction < 0) container.insertBefore(button, input);
            else input.insertAdjacentElement('afterend', button);
        });
        container.classList.add('xw-quantity');
        update(input);
    }

    function scan() {
        document.querySelectorAll('.quantity input.qty').forEach(enhance);
    }

    $(function () {
        scan();
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-xw-quantity]');
            if (!button || button.disabled) return;
            var input = button.closest('.xw-quantity').querySelector('input.qty');
            if (!input || input.disabled || input.readOnly) return;
            event.preventDefault();
            var value = next(input, Number(button.dataset.xwQuantity));
            if (value === null || value === number(input.value)) return;
            input.value = String(value);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            update(input);
        });
        ['input', 'change'].forEach(function (name) {
            document.addEventListener(name, function (event) {
                if (event.target.matches('.quantity input.qty')) update(event.target);
            });
        });
        $(document.body).on('updated_wc_div updated_cart_totals wc_fragments_refreshed wc_fragments_loaded found_variation reset_data', scan);
        var queued = false;
        new MutationObserver(function () {
            if (queued) return;
            queued = true;
            window.requestAnimationFrame(function () { queued = false; scan(); });
        }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['min', 'max', 'step', 'disabled', 'readonly', 'type'] });
    });
})(jQuery);
