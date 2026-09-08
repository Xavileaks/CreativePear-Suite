(function () {
    'use strict';

    function initializeBackToTop() {
        var button = document.getElementById('xw-back-to-top');

        if (!button) {
            return;
        }

        var config = window.xwBackToTop || {};
        var offset = Math.max(0, parseInt(config.offset, 10) || 0);
        var duration = Math.max(0, parseInt(config.duration, 10) || 0);
        var ticking = false;

        function getScrollTop() {
            return window.pageYOffset || document.documentElement.scrollTop || 0;
        }

        function updateButton() {
            var scrollTop = getScrollTop();
            var documentHeight = Math.max(
                document.body.scrollHeight,
                document.documentElement.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.offsetHeight
            );
            var scrollable = Math.max(0, documentHeight - window.innerHeight);
            var progress = scrollable ? Math.min(100, Math.max(0, (scrollTop / scrollable) * 100)) : 0;
            var visible = scrollTop >= offset && scrollable > 0;

            button.style.setProperty('--xw-btt-progress', progress.toFixed(2) + '%');
            button.classList.toggle('is-visible', visible);
            button.setAttribute('aria-hidden', visible ? 'false' : 'true');
            button.setAttribute('tabindex', visible ? '0' : '-1');
            ticking = false;
        }

        function requestUpdate() {
            if (!ticking) {
                window.requestAnimationFrame(updateButton);
                ticking = true;
            }
        }

        function scrollToTop() {
            var start = getScrollTop();
            var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!start || !duration || reduceMotion) {
                window.scrollTo(0, 0);
                return;
            }

            var startedAt = window.performance.now();

            function animate(now) {
                var elapsed = Math.min(1, (now - startedAt) / duration);
                var eased = 1 - Math.pow(1 - elapsed, 3);

                window.scrollTo(0, Math.round(start * (1 - eased)));

                if (elapsed < 1) {
                    window.requestAnimationFrame(animate);
                }
            }

            window.requestAnimationFrame(animate);
        }

        button.addEventListener('click', scrollToTop);
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        updateButton();
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initializeBackToTop, { once: true });
    } else {
        initializeBackToTop();
    }
})();
