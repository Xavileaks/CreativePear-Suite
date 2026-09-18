(() => {
    'use strict';

    let scheduled = false;

    function arrangeProductVariations() {
        scheduled = false;

        document.querySelectorAll('.woocommerce-checkout-review-order-table td.product-name').forEach((cell) => {
            const line = Array.from(cell.children).find((child) => child.classList.contains('xw-checkout-product-line'));
            const variation = Array.from(cell.children).find((child) => child.matches('dl.variation'));
            const title = line ? line.querySelector('.xw-checkout-product-name') : null;

            if (title && variation) {
                title.appendChild(variation);
            }
        });
    }

    function scheduleArrangement() {
        if (scheduled) {
            return;
        }

        scheduled = true;
        window.requestAnimationFrame(arrangeProductVariations);
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', scheduleArrangement, { once: true });
    } else {
        scheduleArrangement();
    }

    const checkout = document.querySelector('form.checkout') || document.body;

    if (checkout) {
        new MutationObserver(scheduleArrangement).observe(checkout, {
            childList: true,
            subtree: true,
        });
    }
})();
