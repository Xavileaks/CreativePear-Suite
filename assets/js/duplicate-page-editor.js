(function (wp, settings) {
    'use strict';
    if (!wp || !settings || !wp.plugins || !wp.element) { return; }
    var MenuItem = (wp.editor && wp.editor.PluginMoreMenuItem) || (wp.editPost && wp.editPost.PluginMoreMenuItem);
    if (!MenuItem) { return; }
    wp.plugins.registerPlugin('xw-duplicate-page', {
        render: function () {
            return wp.element.createElement(MenuItem, { href: settings.url }, settings.label);
        }
    });
}(window.wp, window.xwDuplicatePage));
