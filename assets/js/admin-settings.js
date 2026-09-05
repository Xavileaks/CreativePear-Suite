(function ($) {
    'use strict';

    function updateCard(toggle) {
        var card = $(toggle).closest('[data-xw-feature]');
        card.toggleClass('is-enabled', toggle.checked);

        if (!toggle.checked) {
            card.find('[data-xw-options-toggle]').attr('aria-expanded', 'false');
            card.find('[data-xw-options]').prop('hidden', true);
        }
    }

    function buildPhoneCountrySettings() {
        var grid = $('[data-xw-country-grid]');

        if (!grid.length || !window.allCountries) {
            return;
        }

        var config = window.xwAdminSettings && xwAdminSettings.phone ? xwAdminSettings.phone : {};
        var allowed = Array.isArray(config.allowed_countries) ? config.allowed_countries : [];
        var selectEveryCountry = false;
        var codes = window.allCountries.map(function (country) {
            return country.iso2;
        }).sort();
        var countryRegions = [
            {
                es: 'América Latina',
                en: 'Latin America',
                codes: ['ar', 'bo', 'br', 'bz', 'cl', 'co', 'cr', 'ec', 'sv', 'fk', 'gf', 'gt', 'gy', 'hn', 'mx', 'ni', 'pa', 'py', 'pe', 'sr', 'uy', 've']
            },
            {
                es: 'Caribe',
                en: 'Caribbean',
                codes: ['ai', 'ag', 'aw', 'bs', 'bb', 'bq', 'vg', 'ky', 'cu', 'cw', 'dm', 'do', 'gd', 'gp', 'ht', 'jm', 'mq', 'ms', 'pr', 'bl', 'kn', 'lc', 'mf', 'sx', 'vc', 'tt', 'tc', 'vi']
            },
            {
                es: 'América del Norte',
                en: 'North America',
                codes: ['bm', 'ca', 'gl', 'pm', 'us']
            },
            {
                es: 'Europa',
                en: 'Europe',
                codes: ['ax', 'al', 'ad', 'at', 'by', 'be', 'ba', 'bg', 'hr', 'cy', 'cz', 'dk', 'ee', 'fo', 'fi', 'fr', 'de', 'gi', 'gr', 'gg', 'hu', 'is', 'ie', 'im', 'je', 'it', 'xk', 'lv', 'li', 'lt', 'lu', 'mt', 'md', 'mc', 'me', 'nl', 'mk', 'no', 'pl', 'pt', 'ro', 'ru', 'sm', 'rs', 'sk', 'si', 'es', 'sj', 'se', 'ch', 'ua', 'gb', 'va']
            },
            {
                es: 'Asia',
                en: 'Asia',
                codes: ['af', 'bd', 'bt', 'bn', 'kh', 'cn', 'hk', 'in', 'id', 'io', 'jp', 'kz', 'kg', 'la', 'mo', 'my', 'mv', 'mn', 'mm', 'np', 'kp', 'pk', 'ph', 'sg', 'kr', 'lk', 'tw', 'tj', 'th', 'tl', 'tm', 'uz', 'vn']
            },
            {
                es: 'Medio Oriente',
                en: 'Middle East',
                codes: ['am', 'az', 'bh', 'ge', 'ir', 'iq', 'il', 'jo', 'kw', 'lb', 'om', 'ps', 'qa', 'sa', 'sy', 'tr', 'ae', 'ye']
            },
            {
                es: 'África',
                en: 'Africa',
                codes: ['ac', 'dz', 'ao', 'bj', 'bw', 'bf', 'bi', 'cv', 'cm', 'cf', 'td', 'km', 'cg', 'cd', 'ci', 'dj', 'eg', 'gq', 'er', 'sz', 'et', 'ga', 'gm', 'gh', 'gn', 'gw', 'ke', 'ls', 'lr', 'ly', 'mg', 'mw', 'ml', 'mr', 'mu', 'yt', 'ma', 'mz', 'na', 'ne', 'ng', 're', 'rw', 'st', 'sn', 'sc', 'sl', 'so', 'za', 'ss', 'sh', 'sd', 'tz', 'tg', 'tn', 'ug', 'eh', 'zm', 'zw']
            },
            {
                es: 'Oceanía',
                en: 'Oceania',
                codes: ['as', 'au', 'cx', 'cc', 'ck', 'fj', 'pf', 'gu', 'ki', 'mh', 'fm', 'nr', 'nc', 'nz', 'nu', 'nf', 'mp', 'pw', 'pg', 'ws', 'sb', 'tk', 'to', 'tv', 'vu', 'wf']
            }
        ];
        var grouped = {};

        function appendCountryRegion(region) {
            var regionCodes = region.codes.filter(function (code) {
                return codes.indexOf(code) !== -1 && !grouped[code];
            }).sort();

            if (!regionCodes.length) {
                return;
            }

            var section = $('<section>', { class: 'xw-country-region' });
            var title = $('<h4>', {
                class: 'xw-country-region-title',
                'data-region-label': '1',
                'data-label-es': region.es,
                'data-label-en': region.en
            });
            var regionGrid = $('<div>', { class: 'xw-country-region-grid' });

            regionCodes.forEach(function (code) {
                grouped[code] = true;
                var checkbox = $('<input>', {
                    type: 'checkbox',
                    name: 'xw_settings[phone][allowed_countries][]',
                    value: code,
                    checked: selectEveryCountry || allowed.indexOf(code) !== -1
                });
                var label = $('<label>', { class: 'xw-country-code', 'data-country-code': code });
                label.append(checkbox, $('<span>').text(code.toUpperCase()));
                regionGrid.append(label);
            });

            section.append(title, regionGrid);
            grid.append(section);
        }

        countryRegions.forEach(appendCountryRegion);
        appendCountryRegion({
            es: 'Otros territorios',
            en: 'Other territories',
            codes: codes.filter(function (code) { return !grouped[code]; })
        });

        $('[data-xw-primary-country]').each(function () {
            var select = $(this);
            var selected = select.data('selected') || '';
            select.append($('<option>', { value: '', text: '—', 'data-empty-country': '1' }));

            codes.forEach(function (code) {
                select.append($('<option>', {
                    value: code,
                    text: code.toUpperCase(),
                    'data-country-code': code,
                    selected: code === selected
                }));
            });
        });

        function getCountryName(code, displayNames) {
            return displayNames ? (displayNames.of(code.toUpperCase()) || code.toUpperCase()) : code.toUpperCase();
        }

        function updateCountryLanguage() {
            var language = $('[data-xw-country-language]').val() === 'en' ? 'en' : 'es';
            var displayNames = null;

            try {
                if (window.Intl && Intl.DisplayNames) {
                    displayNames = new Intl.DisplayNames([language], { type: 'region' });
                }
            } catch (error) {
                displayNames = null;
            }

            grid.find('[data-region-label]').each(function () {
                $(this).text($(this).attr(language === 'en' ? 'data-label-en' : 'data-label-es'));
            });

            grid.find('[data-country-code]').each(function () {
                var code = $(this).data('country-code');
                var name = getCountryName(code, displayNames);
                $(this).attr('title', name).attr('aria-label', name + ' (' + code.toUpperCase() + ')');
            });

            $('[data-xw-primary-country]').each(function () {
                $(this).find('[data-empty-country]').text(language === 'en' ? '— None —' : '— Ninguno —');
                $(this).find('option[data-country-code]').each(function () {
                    var code = $(this).data('country-code');
                    $(this).text(getCountryName(code, displayNames) + ' (' + code.toUpperCase() + ')');
                });
            });
        }

        function updateSelectAll() {
            var boxes = grid.find('input[type="checkbox"]');
            var checked = boxes.filter(':checked').length;
            $('[data-xw-select-all-countries]')
                .prop('checked', checked === boxes.length)
                .prop('indeterminate', checked > 0 && checked < boxes.length);
        }

        function updatePhoneMode() {
            var international = $('[data-xw-phone-mode]:checked').val() === 'international';
            $('[data-xw-phone-international]').prop('hidden', !international);
        }

        $('[data-xw-select-all-countries]').on('change', function () {
            grid.find('input[type="checkbox"]').prop('checked', this.checked);

            if (!this.checked) {
                $('[data-xw-primary-country]').val('');
            }

            updateSelectAll();
        });

        grid.on('change', 'input[type="checkbox"]', function () {
            var thisValue = this.value;
            if (!this.checked) {
                $('[data-xw-primary-country]').each(function () {
                    if (this.value === thisValue) {
                        $(this).val('');
                    }
                });
            }

            updateSelectAll();
        });

        $('[data-xw-primary-country]').on('change', function () {
            var current = this;

            if (current.value) {
                grid.find('input[value="' + current.value + '"]').prop('checked', true);
                $('[data-xw-primary-country]').not(current).each(function () {
                    if (this.value === current.value) {
                        $(this).val('');
                    }
                });
            }

            updateSelectAll();
        });

        $('[data-xw-phone-mode]').on('change', updatePhoneMode);
        $('[data-xw-country-language]').on('change', updateCountryLanguage);
        updateCountryLanguage();
        updateSelectAll();
        updatePhoneMode();
    }

    $(function () {
        buildPhoneCountrySettings();

        $('[data-xw-toggle]').each(function () {
            updateCard(this);
        }).on('change', function () {
            updateCard(this);
        });

        $('[data-xw-options-toggle]').on('click', function () {
            var button = $(this);
            var expanded = button.attr('aria-expanded') === 'true';
            var options = $('#' + button.attr('aria-controls'));

            button.attr('aria-expanded', expanded ? 'false' : 'true');
            options.prop('hidden', expanded);
        });

        $('[data-xw-color-picker]').on('input change', function () {
            $(this)
                .removeClass('is-empty')
                .siblings('[data-xw-color-value]')
                .val(this.value.toUpperCase());
        });

        $('[data-xw-color-value]').on('input change blur', function (event) {
            var textInput = $(this);
            var picker = textInput.siblings('input[type="color"]');
            var value = textInput.val().trim();

            if (value === '') {
                picker.addClass('is-empty');
                textInput.val('');
                return;
            }

            if (value.charAt(0) !== '#') {
                value = '#' + value;
            }

            if (/^#[0-9a-fA-F]{3}$/.test(value) && event.type !== 'input') {
                value = '#' + value.charAt(1) + value.charAt(1)
                    + value.charAt(2) + value.charAt(2)
                    + value.charAt(3) + value.charAt(3);
            }

            if (/^#[0-9a-fA-F]{6}$/.test(value)) {
                value = value.toUpperCase();
                picker.val(value).removeClass('is-empty');
                textInput.val(value);
            } else if (event.type === 'blur' || event.type === 'change') {
                textInput.val(picker.hasClass('is-empty') ? '' : picker.val().toUpperCase());
            }
        });

        $('[data-xw-media]').on('click', function (event) {
            var control = $(event.currentTarget).closest('.xw-logo-field');
            var strings = window.xwAdminSettings && xwAdminSettings.i18n ? xwAdminSettings.i18n : {};
            var frame = wp.media({
                title: strings.chooseLogo || 'Choose logo',
                button: { text: strings.useLogo || 'Use this logo' },
                library: { type: 'image' },
                multiple: false
            });

            frame.on('select', function () {
                var image = frame.state().get('selection').first().toJSON();
                control.find('input[type="url"]').val(image.url).trigger('change');
                control.find('[data-xw-logo-preview]').removeAttr('hidden').find('img').attr('src', image.url);
            });

            frame.open();
        });

        $('[data-xw-media-remove]').on('click', function (event) {
            var control = $(event.currentTarget).closest('.xw-logo-field');
            control.find('input[type="url"]').val('');
            control.find('[data-xw-logo-preview]').attr('hidden', true).find('img').attr('src', '');
        });
    });
})(jQuery);
