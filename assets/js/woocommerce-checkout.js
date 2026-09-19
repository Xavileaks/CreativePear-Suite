(() => {
    'use strict';

    let scheduled = false;
    let shippingState = null;
    let shippingUpdateTimeout = 0;

    function finishShippingUpdate() {
        document.body.classList.remove('xw-checkout-shipping-updating');
        window.clearTimeout(shippingUpdateTimeout);
        shippingUpdateTimeout = 0;

        const orderReview = document.querySelector('#order_review');

        if (orderReview) {
            orderReview.removeAttribute('aria-busy');
        }
    }

    function startShippingUpdate() {
        document.body.classList.add('xw-checkout-shipping-updating');

        const orderReview = document.querySelector('#order_review');

        if (orderReview) {
            orderReview.setAttribute('aria-busy', 'true');
        }

        window.clearTimeout(shippingUpdateTimeout);
        shippingUpdateTimeout = window.setTimeout(finishShippingUpdate, 15000);
    }

    function shippingMethodKey(input) {
        return `${input.name}\u0000${input.value}`;
    }

    function captureShippingState(event) {
        const input = event.target;

        if (!(input instanceof HTMLInputElement) || !input.matches('input[name^="shipping_method"]')) {
            return;
        }

        const review = input.closest('.woocommerce-checkout-review-order-table');

        if (!review) {
            return;
        }

        shippingState = {
            lists: Array.from(review.querySelectorAll('.woocommerce-shipping-methods')).map((list) => (
                Array.from(list.children).map((item) => {
                    const method = item.querySelector('input[name^="shipping_method"]');
                    return method ? shippingMethodKey(method) : '';
                }).filter(Boolean)
            )),
        };

        startShippingUpdate();
    }

    function restoreShippingState() {
        if (!shippingState) {
            return;
        }

        const review = document.querySelector('.woocommerce-checkout-review-order-table');

        if (!review) {
            return;
        }

        const lists = Array.from(review.querySelectorAll('.woocommerce-shipping-methods'));

        if (!lists.length) {
            shippingState = null;
            return;
        }

        lists.forEach((list, index) => {
            const items = new Map();

            Array.from(list.children).forEach((item) => {
                const method = item.querySelector('input[name^="shipping_method"]');

                if (method) {
                    items.set(shippingMethodKey(method), item);
                }
            });

            (shippingState.lists[index] || []).forEach((key) => {
                const item = items.get(key);

                if (item) {
                    list.appendChild(item);
                    items.delete(key);
                }
            });

            items.forEach((item) => list.appendChild(item));
        });

        shippingState = null;
    }

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

    document.addEventListener('change', captureShippingState, true);

    if (window.jQuery) {
        window.jQuery(document.body).on('updated_checkout.xwShippingOrder', () => {
            restoreShippingState();
            finishShippingUpdate();
            scheduleArrangement();
        });

        window.jQuery(document.body).on('checkout_error.xwShippingOrder', finishShippingUpdate);
    }

    const checkout = document.querySelector('form.checkout') || document.body;

    if (checkout) {
        new MutationObserver((mutations) => {
            const reviewWasReplaced = mutations.some((mutation) => (
                Array.from(mutation.addedNodes).some((node) => (
                    node instanceof Element && (
                        node.matches('.woocommerce-checkout-review-order-table') ||
                        node.querySelector('.woocommerce-checkout-review-order-table')
                    )
                ))
            ));

            if (reviewWasReplaced) {
                restoreShippingState();
                finishShippingUpdate();
            }

            scheduleArrangement();
        }).observe(checkout, {
            childList: true,
            subtree: true,
        });
    }
})();
