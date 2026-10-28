(function ($) {
    'use strict';
    var selected = new WeakMap();
    var originals = new WeakMap();
    var hooked = false;
    function roots(form) {
        var id = String($(form).data('product_id') || form.getAttribute('data-product_id') || '');
        var context = '.e-loop-item, li.product, .elementor-popup-modal, dialog, [role="dialog"]';
        var loop = form.closest(context);
        return $('[data-xw-product-price][data-variable="1"]').filter(function () {
            return this.getAttribute('data-product-id') === id && this.closest(context) === loop;
        });
    }
    function remember(root) {
        if (!originals.has(root)) originals.set(root, {
            current: root.querySelector('.xw-product-price__current').innerHTML,
            original: root.querySelector('.xw-product-price__original').innerHTML,
            suffix: root.querySelector('.xw-product-price__suffix').innerHTML
        });
    }
    function update(form, values) {
        roots(form).each(function () {
            remember(this);
            var price = values || originals.get(this);
            var original = this.querySelector('.xw-product-price__original');
            // All three values are sanitized on the server; no input text is inserted here.
            this.querySelector('.xw-product-price__current').innerHTML = price.current || '';
            original.innerHTML = price.original || '';
            original.hidden = !price.original;
            this.querySelector('.xw-product-price__suffix').innerHTML = price.suffix || '';
        });
    }
    function sync() {
        $('[data-xw-product-price][data-variable="1"]').each(function () { remember(this); });
        $('form.variations_form').each(function () {
            var id = String($(this).find('input.variation_id').val() || '');
            if (!id) { update(this, null); return; }
            var variation = selected.get(this);
            if (!variation || String(variation.variation_id) !== id) {
                var variations = $(this).data('product_variations');
                variation = Array.isArray(variations) ? variations.find(function (item) { return String(item.variation_id) === id; }) : null;
            }
            if (variation) update(this, variation.xw_product_price);
        });
    }
    $(document).on('found_variation.xwPrice', 'form.variations_form', function (event, variation) {
        if (variation) selected.set(this, variation);
        update(this, variation && variation.xw_product_price);
    }).on('reset_data.xwPrice hide_variation.xwPrice', 'form.variations_form', function () {
        selected.delete(this);
        update(this, null);
    });
    $(sync);
    function init() {
        if (hooked || !window.elementorFrontend || !elementorFrontend.hooks) return;
        hooked = true;
        elementorFrontend.hooks.addAction('frontend/element_ready/xw-product-price.default', sync);
    }
    $(window).on('elementor/frontend/init', init);
    init();
})(jQuery);
