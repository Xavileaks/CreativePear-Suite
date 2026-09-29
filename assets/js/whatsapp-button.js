(function () {
    'use strict';

    function initializeWhatsAppButton() {
        var button = document.getElementById('xw-whatsapp-button');

        if (!button) {
            return;
        }

        var config = window.xwWhatsAppButton || {};
        var offset = Math.max(0, parseInt(config.offset, 10) || 0);
        var ticking = false;

        function getScrollTop() {
            return window.pageYOffset || document.documentElement.scrollTop || 0;
        }

        function updateCollisionOffset() {
            var backToTop = document.getElementById('xw-back-to-top');
            var buttonSide = button.classList.contains('xw-whatsapp-button--bottom-left') ? 'left' : 'right';
            var backToTopSide = backToTop && backToTop.classList.contains('xw-back-to-top--bottom-left') ? 'left' : 'right';
            var backToTopVisible = backToTop &&
                backToTop.classList.contains('is-visible') &&
                window.getComputedStyle(backToTop).display !== 'none';
            var collisionOffset = backToTopVisible && buttonSide === backToTopSide
                ? backToTop.offsetHeight + 12
                : 0;

            button.style.setProperty('--xw-wa-collision-offset', collisionOffset + 'px');
        }

        function updateButton() {
            var visible = getScrollTop() >= offset;

            button.classList.toggle('is-visible', visible);
            button.setAttribute('aria-hidden', visible ? 'false' : 'true');
            button.setAttribute('tabindex', visible ? '0' : '-1');
            updateCollisionOffset();
            ticking = false;
        }

        function requestUpdate() {
            if (!ticking) {
                window.requestAnimationFrame(updateButton);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        updateButton();
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initializeWhatsAppButton, { once: true });
    } else {
        initializeWhatsAppButton();
    }
})();
