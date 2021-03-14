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

        $default = $attributes['set_custom_default'] ?? false;
        if (!empty($default)) {
            $defaultValue = $default;
        } else {
            $defaultValue = api_get_setting('platformLanguage');
        }
        // Get all languages
        $languages = api_get_languages();
        foreach ($languages as $iso => $name) {
            $attributes = [];
            if ($iso === $defaultValue) {
                $attributes = ['selected' => 'selected'];
            }
            $this->addOption($name, $iso, $attributes);
        }
    }
}
