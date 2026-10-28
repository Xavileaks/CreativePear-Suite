(function ($) {
    'use strict';
    var hooked = false;
    function init() {
        if (hooked || !window.elementor || !elementor.hooks) return;
        hooked = true;
        // The native collection is rebuilt on document changes, after core promotes Custom Widget.
        elementor.hooks.addFilter('panel/elements/regionViews', function (views) {
            var collection = views.categories && views.categories.options && views.categories.options.collection;
            if (!collection) return views;
            var suite = collection.findWhere({name: 'xw-suite'});
            if (!suite) return views;
            var anchor = collection.findWhere({name: 'custom-widgets'}) || collection.findWhere({name: 'pro-elements'});
            if (!anchor) return views;
            collection.remove(suite, {silent: true});
            collection.add(suite, {at: collection.indexOf(anchor), silent: true});
            return views;
        });
    }
    $(window).on('elementor:init', init);
    init();
})(jQuery);
