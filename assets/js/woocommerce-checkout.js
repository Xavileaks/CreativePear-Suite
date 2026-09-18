(() => {
    'use strict';

    let scheduled = false;
    let shippingState = null;

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

        const methods = Array.from(review.querySelectorAll('input[name^="shipping_method"]'));
        const selected = {};

        methods.forEach((method) => {
            if (method.checked || 'hidden' === method.type) {
                selected[method.name] = method.value;
            }
        });

        shippingState = {
            selected,
            lists: Array.from(review.querySelectorAll('.woocommerce-shipping-methods')).map((list) => (
                Array.from(list.children).map((item) => {
                    const method = item.querySelector('input[name^="shipping_method"]');
                    return method ? shippingMethodKey(method) : '';
                }).filter(Boolean)
            )),
        };
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

        Object.entries(shippingState.selected).forEach(([name, value]) => {
            const methods = Array.from(review.querySelectorAll('input[name^="shipping_method"]'))
                .filter((method) => method.name === name);
            const selectedMethod = methods.find((method) => method.value === value);

            if (!selectedMethod) {
                return;
            }

            methods.forEach((method) => {
                if ('radio' === method.type) {
                    method.checked = method === selectedMethod;
                }
            });
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
            scheduleArrangement();
        });
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
            }

            scheduleArrangement();
        }).observe(checkout, {
            childList: true,
            subtree: true,
        });
    }
})();
