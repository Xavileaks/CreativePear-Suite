(function ($) {
    'use strict';
    var selected = new WeakMap();
    var hooked = false;

    function roots(form) {
        var id = String($(form).data('product_id') || form.getAttribute('data-product_id') || '');
        var context = '.e-loop-item, li.product, .elementor-popup-modal, dialog, [role="dialog"]';
        var loop = form.closest(context);
        return $('[data-xw-product-savings][data-variable="1"]').filter(function () {
            return this.getAttribute('data-product-id') === id && this.closest(context) === loop;
        });
    }

    function update(form, values) {
        roots(form).each(function () {
            var badge = this.querySelector('.xw-product-savings__badge');
            var value = this.querySelector('.xw-product-savings__value');
            var notice = this.querySelector('.xw-product-savings__notice');
            if (!badge || !value) return;
            var mode = this.getAttribute('data-value-mode') === 'absolute' ? 'absolute' : 'relative';
            var html = values && typeof values[mode] === 'string' ? values[mode] : '';
            // Markup is generated and sanitized by WooCommerce/PHP, never from user-entered fields.
            if (mode === 'absolute') value.innerHTML = html;
            else value.textContent = html;
            badge.hidden = !html;
            if (notice) notice.hidden = !!html;
        });
    }

    function sync() {
        $('form.variations_form').each(function () {
            var id = String($(this).find('input.variation_id').val() || '');
            var variations = $(this).data('product_variations');
            if (!id) { update(this, null); return; }
            var cached = selected.get(this);
            if (cached && String(cached.variation_id) === id) { update(this, cached.xw_product_savings); return; }
            if (!Array.isArray(variations)) return;
            var current = variations.find(function (variation) { return String(variation.variation_id) === id; });
            if (current) update(this, current.xw_product_savings);
        });
    }

    $(document).on('found_variation.xwSavings', 'form.variations_form', function (event, variation) {
        if (variation) selected.set(this, variation);
        update(this, variation && variation.xw_product_savings);
    }).on('reset_data.xwSavings hide_variation.xwSavings', 'form.variations_form', function () {
        selected.delete(this);
        update(this, null);
    });
    $(sync);
    function editorHooks() {
        if (!hooked && window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/xw-product-savings.default', sync);
            hooked = true;
        }
    }
    $(window).on('elementor/frontend/init', editorHooks);
    if (window.elementorFrontend) editorHooks();
})(jQuery);
