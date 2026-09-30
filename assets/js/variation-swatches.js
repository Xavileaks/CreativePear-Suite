(function ($) {
    'use strict';

    var config = window.xwVariationSwatches || {};

    function variationImageMap($form, attributeName) {
        var map = {};
        var variations = $form.data('product_variations');
        if (!Array.isArray(variations)) { return map; }
        variations.forEach(function (variation) {
            var attributes = variation.attributes || {};
            var value = String(attributes[attributeName] || '');
            var image = variation.image || {};
            var url = image.gallery_thumbnail_src || image.thumb_src || image.src || '';
            if (value && url && !map[value]) { map[value] = url; }
        });
        return map;
    }

    function isAutomaticImageAttribute(attributeName) {
        var normalized = String(attributeName || '').toLowerCase().replace(/^attribute_/, '').replace(/^pa_/, '').replace(/[\s_-]+/g, '');
        return ['color', 'colour', 'colores', 'imagen', 'imagenes', 'image', 'images'].indexOf(normalized) !== -1;
    }

    function upgradeLocalImageSwatches($form) {
        $form.find('.xw-vs-control').each(function () {
            var $control = $(this);
            var attributeName = String($control.attr('data-attribute-name') || '');
            if (!isAutomaticImageAttribute(attributeName)) { return; }
            var images = variationImageMap($form, attributeName);
            if (!Object.keys(images).length) { return; }
            $control.find('.xw-vs-item').each(function () {
                var $item = $(this);
                var value = String($item.attr('data-value') || '');
                var imageUrl = images[value] || '';
                if (!imageUrl) {
                    Object.keys(images).some(function (key) {
                        if (key.toLowerCase() === value.toLowerCase()) { imageUrl = images[key]; return true; }
                        return false;
                    });
                }
                if (!imageUrl) { return; }
                var label = String($item.attr('data-label') || value);
                var $image = $('<img>', { src: imageUrl, alt: label, loading: 'lazy', decoding: 'async' });
                $item.removeClass('xw-vs-item--label xw-vs-item--color').addClass('xw-vs-item--image');
                $item.find('.xw-vs-item__content').empty().append($image.clone());
                var tooltipMode = config.tooltipContent || 'text_image';
                var $tooltip = $item.find('.xw-vs-tooltip');
                var $tooltipText = $tooltip.find('.xw-vs-tooltip__text, span').last();
                $tooltip.find('img').remove();
                if ('text' !== tooltipMode) { $tooltip.prepend($image.attr('alt', '').clone()); }
                $tooltipText.toggle('image' !== tooltipMode);
            });
        });
    }

    function firstEnabledValue($select) {
        var value = '';
        $select.find('option').each(function () { if (!value && this.value && !this.disabled) { value = this.value; } });
        return value;
    }

    function syncControl($control) {
        var $select = $control.find('select').first();
        var selected = String($select.val() || '');
        var available = {};
        $select.find('option').each(function () { if (this.value) { available[String(this.value)] = !this.disabled; } });
        $control.find('.xw-vs-item').each(function () {
            var $item = $(this);
            var value = String($item.data('value'));
            var isSelected = value === selected;
            var isDisabled = available[value] === false || typeof available[value] === 'undefined';
            $item.toggleClass('is-selected', isSelected).toggleClass('is-disabled', isDisabled).prop('disabled', isDisabled)
                .attr('aria-checked', isSelected ? 'true' : 'false').attr('aria-disabled', isDisabled ? 'true' : 'false');
        });
        if (config.showSelectedLabel) {
            var $row = $control.closest('tr');
            var $label = $row.find('th.label label').first();
            var $name = $label.find('.xw-vs-selected-name');
            var selectedLabel = selected ? $control.find('.xw-vs-item.is-selected').data('label') || '' : '';
            if (!$name.length) { $name = $('<span class="xw-vs-selected-name" aria-live="polite"></span>').appendTo($label); }
            $name.text(selectedLabel ? ': ' + selectedLabel : '');
        }
    }

    function syncForm($form) { $form.find('.xw-vs-control').each(function () { syncControl($(this)); }); }

    function fillNextEmpty($form) {
        var $empty = $form.find('.xw-vs-control select').filter(function () { return !this.value; }).first();
        if (!$empty.length) { return; }
        var value = firstEnabledValue($empty);
        if (!value) { return; }
        $empty.val(value).trigger('change');
        window.setTimeout(function () { fillNextEmpty($form); }, 0);
    }

    function initializeForm(form) {
        var $form = $(form);
        if ($form.data('xwVsReady')) { upgradeLocalImageSwatches($form); syncForm($form); return; }
        $form.data('xwVsReady', true);
        upgradeLocalImageSwatches($form);
        $form.on('click', '.xw-vs-item', function () {
            var $item = $(this);
            if ($item.hasClass('is-disabled')) { return; }
            var $control = $item.closest('.xw-vs-control');
            var $select = $control.find('select').first();
            var value = String($item.data('value'));
            var nextValue = config.clearOnReselect && String($select.val() || '') === value ? '' : value;
            $select.val(nextValue).trigger('change');
            if ('after_first' === config.autoSelect && nextValue) { window.setTimeout(function () { fillNextEmpty($form); }, 0); }
        });
        $form.on('change', '.xw-vs-control select', function () { syncControl($(this).closest('.xw-vs-control')); });
        $form.on('woocommerce_update_variation_values reset_data hide_variation show_variation', function () {
            window.setTimeout(function () { syncForm($form); }, 0);
        });
        syncForm($form);
        if ('page_load' === config.autoSelect) { window.setTimeout(function () { fillNextEmpty($form); }, 0); }
    }

    function initializeAll(context) { $(context).find('.variations_form').addBack('.variations_form').each(function () { initializeForm(this); }); }
    $(function () { initializeAll(document); });
    $(document).on('wc_variation_form', '.variations_form', function () { initializeForm(this); });
})(jQuery);
