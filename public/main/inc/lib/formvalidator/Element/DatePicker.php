<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Form element to select a date.
 */
class DatePicker extends HTML_QuickForm_text
{
    /**
     * @param string       $elementName
     * @param string|array $elementLabel
     * @param array        $attributes
     */
    public function __construct($elementName, $elementLabel = null, $attributes = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = $elementName;
        }
        $attributes['class'] = 'form-control';

        parent::__construct($elementName, $elementLabel, $attributes);
        $this->_appendName = true;
    }

    /**
     * HTML code to display this datepicker.
     *
     * @return string
     */
    public function toHtml(): string
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }

        $id = $this->getAttribute('id');
        $value = $this->getValue();

        if (!empty($value)) {
            $value = api_format_date($value, DATE_FORMAT_LONG_NO_DAY);
        }

        $label = $this->getLabel();
        $settingRequiredFields = api_get_setting('registration.required_extra_fields_in_inscription', true);
        $requiredFields = 'false' !== $settingRequiredFields ? $settingRequiredFields : [];

        if (!empty($requiredFields) && $requiredFields['options']) {
            $requiredFields = $requiredFields['options'];
        }
        $variable = str_replace('extra_', '',$id);
        $requiredSymbol = '';
        if (!empty($requiredFields) && in_array($variable, $requiredFields)) {
            $requiredSymbol = '<span class="form_required">*</span>';
        }

        return '
        <div>'.$requiredSymbol.$label.'</div>
        <div id="'.$id.'" class="flex items-center mt-1 flatpickr-wrapper" data-wrap="true">
            <input '.$this->_getAttrString($this->_attributes).'
                class="form-control border flex-grow" type="text" value="'.$value.'" placeholder="'.get_lang('Select date').'" data-input>
            <div class="flex space-x-1 ml-2" id="button-addon3">
                <button class="btn btn--secondary-outline mr-2" type="button" data-toggle>
                    <i class="pi pi-calendar pi-lg"></i>
                </button>
                <button class="btn btn--secondary-outline" type="button" data-clear>
                    <i class="pi pi-times pi-lg"></i>
                </button>
            </div>
        </div>
    '.$this->getElementJS();
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        if (empty($value)) {
            return;
        }

        $value = substr($value, 0, 16);
        $this->updateAttributes(
            [
                'value' => $value,
            ]
        );
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

        $altFormat = ($localeCode === 'en') ? 'F d, Y' : 'd F, Y';

        return "<script>
        document.addEventListener('DOMContentLoaded', function () {
            function initializeFlatpickr() {
                const fp = flatpickr('#{$id}', {
                    locale: '{$localeCode}',
                    altInput: true,
                    altFormat: '{$altFormat}',
                    enableTime: false,
                    dateFormat: 'Y-m-d',
                    time_24hr: true,
                    wrap: true
                });

                if ($('label[for=\"".$id."\"]').length > 0) {
                    $('label[for=\"".$id."\"]').hide();
                }

                document.querySelector('label[for=\"' + '{$id}' + '\"]').classList.add('datepicker-label');
            }

            function loadLocale() {
                if ('{$localeCode}' !== 'en') {
                    var script = document.createElement('script');
                    script.src = '/build/flatpickr/l10n/{$localeCode}.js';
                    script.onload = initializeFlatpickr;
                    document.head.appendChild(script);
                } else {
                    initializeFlatpickr();
                }
            }

            loadLocale();
        });
    </script>";
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
        $locale = api_get_setting('language.platform_language');
        $request = Container::getRequest();
        if ($request) {
            $locale = $request->getLocale();
        }

        $userInfo = api_get_user_info();
        if (is_array($userInfo) && !empty($userInfo['language']) && ANONYMOUS != $userInfo['status']) {
            $locale = $userInfo['language'];
        }

        $courseInfo = api_get_course_info();
        if (isset($courseInfo)) {
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
