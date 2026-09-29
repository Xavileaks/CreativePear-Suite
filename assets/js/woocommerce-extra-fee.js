(function ($) {
    'use strict';

    function copyComputedStyle(source, target) {
        if (!source || !target || !window.getComputedStyle) {
            return;
        }

        var computed = window.getComputedStyle(source);
        var properties = [
            'color',
            'font-family',
            'font-size',
            'font-style',
            'font-weight',
            'line-height',
            'letter-spacing',
            'text-align',
            'text-decoration',
            'text-transform',
            'padding-top',
            'padding-right',
            'padding-bottom',
            'padding-left',
            'border-top-color',
            'border-top-style',
            'border-top-width',
            'border-bottom-color',
            'border-bottom-style',
            'border-bottom-width',
            'background-color'
        ];

        properties.forEach(function (property) {
            target.style.setProperty(property, computed.getPropertyValue(property), 'important');
        });
    }

    function syncExtraFeeStyle() {
        var table = document.querySelector('.woocommerce-checkout-review-order-table');

        if (!table) {
            return;
        }

        var totalRow = table.querySelector('tr.order-total');
        var feeLabel = window.xwExtraFeeSettings && xwExtraFeeSettings.label
            ? xwExtraFeeSettings.label.trim()
            : 'Extra Fees';

        if (!totalRow) {
            return;
        }

        table.querySelectorAll('tr.fee').forEach(function (feeRow) {
            var heading = feeRow.querySelector('th');

            if (!heading || heading.textContent.trim() !== feeLabel) {
                return;
            }

            feeRow.classList.add('xw-extra-fee-row');
            copyComputedStyle(totalRow, feeRow);
            copyComputedStyle(totalRow.querySelector('th'), heading);
            copyComputedStyle(totalRow.querySelector('td'), feeRow.querySelector('td'));
            copyComputedStyle(
                totalRow.querySelector('td .woocommerce-Price-amount, td strong'),
                feeRow.querySelector('td .woocommerce-Price-amount')
            );
        });
    }

    $(syncExtraFeeStyle);
    $(document.body).on('updated_checkout', syncExtraFeeStyle);
    $(window).on('resize', function () {
        window.clearTimeout(syncExtraFeeStyle.resizeTimer);
        syncExtraFeeStyle.resizeTimer = window.setTimeout(syncExtraFeeStyle, 100);
    });
})(jQuery);
