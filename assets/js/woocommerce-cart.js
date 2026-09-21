(function () {
    'use strict';

    const productSelector = '.elementor-menu-cart__product';

    function syncQuantityBadge(product) {
        const image = product.querySelector('.elementor-menu-cart__product-image');
        const quantity = product.querySelector('.elementor-menu-cart__product-price .product-quantity');

        if (!image || !quantity) {
            return;
        }

        const match = quantity.textContent.trim().match(/[0-9]+(?:[.,][0-9]+)?/);

        if (!match) {
            return;
        }

        let badge = image.querySelector('.xw-mini-cart-product-quantity');

        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'xw-mini-cart-product-quantity';
            badge.setAttribute('aria-hidden', 'true');
            image.appendChild(badge);
        }

        badge.textContent = match[0];
    }

    function scan(root) {
        if (!(root instanceof Element) && root !== document) {
            return;
        }

        if (root instanceof Element && root.matches(productSelector)) {
            syncQuantityBadge(root);
        }

        root.querySelectorAll(productSelector).forEach(syncQuantityBadge);
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', function () {
            scan(document);
        }, { once: true });
    } else {
        scan(document);
    }

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node instanceof Element) {
                    scan(node);
                }
            });
        });
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
}());
