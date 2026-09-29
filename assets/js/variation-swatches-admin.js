(function ($) {
    'use strict';

    $(document).on('click', '[data-xw-vs-image-choose]', function (event) {
        event.preventDefault();
        var $control = $(event.currentTarget).closest('[data-xw-vs-term-image]');
        var strings = window.xwVariationSwatchesAdmin || {};
        var frame = wp.media({
            title: strings.chooseImage || 'Choose swatch image',
            button: { text: strings.useImage || 'Use this image' },
            library: { type: 'image' },
            multiple: false
        });

        frame.on('select', function () {
            var image = frame.state().get('selection').first().toJSON();
            var url = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;
            $control.find('[data-xw-vs-image-id]').val(image.id);
            $control.find('[data-xw-vs-image-preview]').prop('hidden', false).find('img').attr('src', url);
            $control.find('[data-xw-vs-image-remove]').prop('hidden', false);
        });

        frame.open();
    });

    $(document).on('click', '[data-xw-vs-image-remove]', function (event) {
        event.preventDefault();
        var $control = $(event.currentTarget).closest('[data-xw-vs-term-image]');
        $control.find('[data-xw-vs-image-id]').val('');
        $control.find('[data-xw-vs-image-preview]').prop('hidden', true).find('img').attr('src', '');
        $(event.currentTarget).prop('hidden', true);
    });
})(jQuery);

