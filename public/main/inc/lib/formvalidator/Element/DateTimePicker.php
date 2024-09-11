<?php

/* For licensing terms, see /license.txt */

/**
 * Form element to select a date and hour.
 */
class DateTimePicker extends HTML_QuickForm_text
{
    /**
     * DateTimePicker constructor.
     *
     * @param string       $elementName
     * @param string|array $elementLabel
     * @param array        $attributes
     */
    public function __construct($elementName, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }
        $attributes['class'] = 'p-component p-inputtext';
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
    }

    /**
     * HTML code to display this datepicker.
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $id = $this->getAttribute('id');
        $value = $this->getValue();

        $formattedValue = '';
        if (!empty($value)) {
            $formattedValue = api_format_date($value, DATE_TIME_FORMAT_LONG_24H);
        }

        $label = $this->getLabel();
        if (is_array($label) && isset($label[0])) {
            $label = $label[0];
        }

        //$resetFieldX = sprintf(get_lang('Reset %s'), $label);

        return '<input '.$this->_getAttrString($this->_attributes).' />'.$this->getElementJS();
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $value = substr($value, 0, 16);
        $this->updateAttributes(['value' => $value]);
    }

    /**
     * Get the necessary javascript for this datepicker.
     *
     * @return string
     */
    private function getElementJS(): string
    {
        $localeCode = $this->getLocaleCode();
        $id = $this->getAttribute('id');

        $altFormat = ($localeCode === 'en') ? 'F d, Y - H:i' : 'd F, Y - H:i';

        $js = "<script>
        document.addEventListener('DOMContentLoaded', function () {
            function initializeFlatpickr() {
                const fp = flatpickr('#{$id}', {
                    locale: '{$localeCode}',
                    altInput: true,
                    altFormat: '{$altFormat}',
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    time_24hr: true,
                    wrap: false,
                    onReady: function(selectedDates, dateStr, instance) {
                        const validateButton = document.createElement('button');
                        validateButton.textContent = '".get_lang('Validate')."';
                        validateButton.className = 'flatpickr-validate-btn';
                        validateButton.type = 'button';
                        validateButton.onclick = function() {
                            instance.close();
                        };

                        instance.calendarContainer.appendChild(validateButton);
                    }
                });

                document.querySelector('label[for=\"' + '{$id}' + '\"]').classList.add('datepicker-label');
            }

            function loadLocaleAndInitialize() {
                if ('{$localeCode}' !== 'en') {
                    var script = document.createElement('script');
                    script.src = '/build/flatpickr/l10n/' + '{$localeCode}.js';
                    script.onload = initializeFlatpickr;
                    document.head.appendChild(script);
                } else {
                    initializeFlatpickr();
                }
            }

            loadLocaleAndInitialize();
        });
        </script>";

        return $js;
    }

    /**
     * Retrieves the locale code based on user and course settings.
     * Extracts the ISO language code from user or course settings and checks
     * its availability in the list of supported locales. Returns 'en' if the language
     * is not available.
     *
     * @return string Locale code (e.g., 'es', 'en', 'fr').
     */
    private function getLocaleCode(): string
    {
        $locale = api_get_language_isocode();
        $userInfo = api_get_user_info();
        if (is_array($userInfo) && !empty($userInfo['language']) && ANONYMOUS != $userInfo['status']) {
            $locale = $userInfo['language'];
        }

        $courseInfo = api_get_course_info();
        if (!empty($courseInfo)) {
            $locale = $courseInfo['language'];
        }

        $localeCode = explode('_', $locale)[0];
        $availableLocales = [
            'ar', 'ar-dz', 'at', 'az', 'be', 'bg', 'bn', 'bs', 'cat', 'ckb', 'cs', 'cy', 'da', 'de',
            'eo', 'es', 'et', 'fa', 'fi', 'fo', 'fr', 'ga', 'gr', 'he', 'hi', 'hr', 'hu', 'hy',
            'id', 'is', 'it', 'ja', 'ka', 'km', 'ko', 'kz', 'lt', 'lv', 'mk', 'mn', 'ms', 'my',
            'nl', 'nn', 'no', 'pa', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr', 'sr-cyr',
            'sv', 'th', 'tr', 'uk', 'uz', 'uz_latn', 'vn', 'zh', 'zh-tw'
        ];
        if (!in_array($localeCode, $availableLocales)) {
            $localeCode = 'en';
        }

        return $localeCode;
    }
}
