(function ($) {
    'use strict';

    var config = window.xwVariationSwatches || {};

    function firstEnabledValue($select) {
        var value = '';
        $select.find('option').each(function () {
            if (!value && this.value && !this.disabled) {
                value = this.value;
            }
        });
        return value;
    }

    function syncControl($control) {
        var $select = $control.find('select').first();
        var selected = String($select.val() || '');
        var available = {};

        $select.find('option').each(function () {
            if (this.value) {
                available[String(this.value)] = !this.disabled;
            }
        });

        $control.find('.xw-vs-item').each(function () {
            var $item = $(this);
            var value = String($item.data('value'));
            var isSelected = value === selected;
            var isDisabled = available[value] === false || typeof available[value] === 'undefined';

            $item
                .toggleClass('is-selected', isSelected)
                .toggleClass('is-disabled', isDisabled)
                .prop('disabled', isDisabled)
                .attr('aria-checked', isSelected ? 'true' : 'false')
                .attr('aria-disabled', isDisabled ? 'true' : 'false');
        });

        if (config.showSelectedLabel) {
            var $row = $control.closest('tr');
            var $label = $row.find('th.label label').first();
            var $name = $label.find('.xw-vs-selected-name');
            var selectedLabel = selected ? $control.find('.xw-vs-item.is-selected').data('label') || '' : '';

            if (!$name.length) {
                $name = $('<span class="xw-vs-selected-name" aria-live="polite"></span>').appendTo($label);
            }
            $name.text(selectedLabel ? ': ' + selectedLabel : '');
        }
    }

    function syncForm($form) {
        $form.find('.xw-vs-control').each(function () {
            syncControl($(this));
        });
    }

    function fillNextEmpty($form) {
        var $empty = $form.find('.xw-vs-control select').filter(function () {
            return !this.value;
        }).first();

        if (!$empty.length) {
            return;
        }

        var value = firstEnabledValue($empty);
        if (!value) {
            return;
        }

        $empty.val(value).trigger('change');
        window.setTimeout(function () {
            fillNextEmpty($form);
        }, 0);
    }

    function initializeForm(form) {
        var $form = $(form);
        if ($form.data('xwVsReady')) {
            syncForm($form);
            return;
        }
        $form.data('xwVsReady', true);

        $form.on('click', '.xw-vs-item', function () {
            var $item = $(this);
            if ($item.hasClass('is-disabled')) {
                return;
            }

            var $control = $item.closest('.xw-vs-control');
            var $select = $control.find('select').first();
            var value = String($item.data('value'));
            var nextValue = config.clearOnReselect && String($select.val() || '') === value ? '' : value;

            $select.val(nextValue).trigger('change');

            if ('after_first' === config.autoSelect && nextValue) {
                window.setTimeout(function () {
                    fillNextEmpty($form);
                }, 0);
            }
        });

        $form.on('change', '.xw-vs-control select', function () {
            syncControl($(this).closest('.xw-vs-control'));
        });

        $form.on('woocommerce_update_variation_values reset_data hide_variation show_variation', function () {
            window.setTimeout(function () {
                syncForm($form);
            }, 0);
        });

        syncForm($form);

        if ('page_load' === config.autoSelect) {
            window.setTimeout(function () {
                fillNextEmpty($form);
            }, 0);
        }
    }

    function initializeAll(context) {
        $(context).find('.variations_form').addBack('.variations_form').each(function () {
            initializeForm(this);
        });
    }

    $(function () {
        initializeAll(document);
    });

    $(document).on('wc_variation_form', '.variations_form', function () {
        initializeForm(this);
    });
})(jQuery);

