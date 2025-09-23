<?php

/* For licensing terms, see /license.txt */

/**
 * Class SelectLanguage
 * A dropdown list with all languages to use with QuickForm.
 */
class SelectLanguage extends HTML_QuickForm_select
{
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $options = [],
        $attributes = []
    ) {
        parent::__construct($elementName, $elementLabel, $options, $attributes);

        // Prefer a custom default if provided; otherwise use platform default isocode
        $customDefault = $attributes['set_custom_default'] ?? false;
        $defaultValue = !empty($customDefault) ? $customDefault : api_get_platform_default_isocode();

        // Fetch languages available + platform default (even if not available)
        $languages = api_get_languages_with_platform_default();

        foreach ($languages as $iso => $name) {
            $optAttrs = [];
            if ($defaultValue && $iso === $defaultValue) {
                $optAttrs = ['selected' => 'selected'];
            }
            $this->addOption($name, $iso, $optAttrs);
        }
    }
}
