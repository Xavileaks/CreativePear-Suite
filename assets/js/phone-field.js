(function () {
    'use strict';

    var config = window.xwPhoneSettings || {};
    var selector = 'form.elementor-form input[type="tel"], form.elementor-form .elementor-field-group-phone input';
    var errorIndex = 0;

    function updateSubmitState(input, isValid) {
        var form = input.closest('form');
        if (!form) {
            return;
        }

        input.dataset.xwPhoneValid = isValid ? '1' : '0';
        var hasInvalidPhone = form.querySelector('[data-xw-phone-valid="0"]');
        var submit = form.querySelector('button.elementor-button[type="submit"]');

        if (submit) {
            submit.disabled = Boolean(hasInvalidPhone);
            submit.classList.toggle('xw-submit-disabled', Boolean(hasInvalidPhone));
        }
    }

    function initialiseInput(input) {
        if (input.dataset.xwIntlPhone || !window.intlTelInput) {
            return;
        }

        input.dataset.xwIntlPhone = '1';
        input.removeAttribute('pattern');
        input.removeAttribute('minlength');
        input.removeAttribute('maxlength');
        input.setAttribute('inputmode', 'tel');

        var allowed = Array.isArray(config.allowedCountries) ? config.allowedCountries : [];
        var primary = Array.isArray(config.primaryCountries) ? config.primaryCountries : [];
        var initialCountry = primary[0] || allowed[0] || 'us';
        var pageLanguage = (document.documentElement.lang || '').toLowerCase();
        var language = pageLanguage.indexOf('es') === 0
            ? 'es'
            : (pageLanguage.indexOf('en') === 0 ? 'en' : (config.language === 'en' ? 'en' : 'es'));
        var options = {
            countryNameLocale: language,
            countryOrder: primary,
            initialCountry: initialCountry,
            separateDialCode: true,
            allowedNumberTypes: ['MOBILE', 'FIXED_LINE'],
            numberDisplayFormat: 'INTERNATIONAL',
            strictMode: true,
            formatAsYouType: true
        };

        if (language === 'es') {
            options.uiTranslations = {
                selectedCountryAriaLabel: 'Cambiar país para el número de teléfono, seleccionado ${countryName} (${dialCode})',
                noCountrySelected: 'Selecciona el país para el número de teléfono',
                countryListAriaLabel: 'Lista de países',
                searchPlaceholder: 'Buscar',
                clearSearchAriaLabel: 'Borrar búsqueda',
                searchEmptyState: 'No se han encontrado resultados',
                searchSummaryAria: function (count) {
                    if (count === 0) {
                        return 'No se han encontrado resultados';
                    }
                    if (count === 1) {
                        return '1 resultado encontrado';
                    }
                    return count + ' resultados encontrados';
                }
            };
        }

        if (allowed.length) {
            options.onlyCountries = allowed;
        }

        var phone = window.intlTelInput(input, options);
        var lastValidFormatted = '';
        var lastValidDigits = '';
        var wrapper = input.closest('.iti');
        var error = document.createElement('span');
        errorIndex += 1;
        error.id = 'xw-phone-error-' + errorIndex;
        error.className = 'xw-phone-error';
        error.setAttribute('role', 'alert');
        error.hidden = true;
        error.textContent = language === 'es'
            ? 'Introduce un número de teléfono válido para el país seleccionado.'
            : 'Enter a valid phone number for the selected country.';

        if (wrapper) {
            wrapper.insertAdjacentElement('afterend', error);
        }

        function updateCountryTooltip() {
            var country = phone.getSelectedCountry();
            var button = input.closest('.iti') ? input.closest('.iti').querySelector('.iti__selected-country') : null;

            if (!button || !country) {
                return;
            }

            var dialCode = country.dialCode ? '+' + country.dialCode : '';
            var tooltip = language === 'es'
                ? 'Cambiar país. Seleccionado: ' + country.name + ' (' + dialCode + ')'
                : 'Change country. Selected: ' + country.name + ' (' + dialCode + ')';

            button.setAttribute('title', tooltip);
            button.setAttribute('aria-label', tooltip);
        }

        function getDigits(value) {
            return value.replace(/\D/g, '');
        }

        function preserveLastFormatting() {
            var current = input.value;
            var currentDigits = getDigits(current);
            var hasFormatting = /[\s().-]/.test(current);

            if (!hasFormatting && lastValidFormatted && currentDigits.indexOf(lastValidDigits) === 0 && currentDigits.length > lastValidDigits.length) {
                input.value = lastValidFormatted + ' ' + currentDigits.slice(lastValidDigits.length);
            }
        }

        function validate() {
            var empty = input.value.trim() === '';
            var valid = empty || phone.isValidNumber();

            if (valid && !empty) {
                lastValidFormatted = input.value;
                lastValidDigits = getDigits(input.value);
            }

            input.classList.toggle('xw-phone-invalid', !valid);
            input.setAttribute('aria-invalid', valid ? 'false' : 'true');
            if (!valid) {
                input.setAttribute('aria-describedby', error.id);
            } else if (input.getAttribute('aria-describedby') === error.id) {
                input.removeAttribute('aria-describedby');
            }
            error.hidden = valid;
            updateSubmitState(input, valid);
            return valid;
        }

        input.xwPhoneValidate = validate;
        input.addEventListener('input', function () {
            preserveLastFormatting();
            validate();
        });
        input.addEventListener('blur', validate);
        input.addEventListener('countrychange', function () {
            lastValidFormatted = '';
            lastValidDigits = '';
            validate();
            updateCountryTooltip();
        });

        var form = input.closest('form');
        if (form && !form.dataset.xwPhoneSubmit) {
            form.dataset.xwPhoneSubmit = '1';
            form.addEventListener('submit', function (event) {
                var hasInvalidPhone = false;

                form.querySelectorAll(selector).forEach(function (phoneInput) {
                    var instance = window.intlTelInput.getInstance(phoneInput);
                    var empty = phoneInput.value.trim() === '';
                    var valid = empty || (instance && instance.isValidNumber());

                    if (!valid) {
                        hasInvalidPhone = true;
                        if (typeof phoneInput.xwPhoneValidate === 'function') {
                            phoneInput.xwPhoneValidate();
                        }
                    } else if (!empty && instance) {
                        phoneInput.value = instance.getNumber();
                    }
                });

                if (hasInvalidPhone) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }, true);
        }

        validate();
        updateCountryTooltip();
        if (phone.promise && typeof phone.promise.then === 'function') {
            phone.promise.then(updateCountryTooltip).catch(function () {});
        }
    }

    function initialiseAll(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        root.querySelectorAll(selector).forEach(initialiseInput);

        if (root.matches && root.matches(selector)) {
            initialiseInput(root);
        }
    }

    function start() {
        initialiseAll(document);

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        initialiseAll(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
