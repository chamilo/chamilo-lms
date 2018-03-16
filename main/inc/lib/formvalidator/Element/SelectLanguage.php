<?php
/* For licensing terms, see /license.txt */

/**
 * Class SelectLanguage
 * A dropdown list with all languages to use with QuickForm.
 */
class SelectLanguage extends HTML_QuickForm_select
{
    /**
     * Class constructor.
     */
    public function __construct(
        $elementName = null,
        $elementLabel = null,
        $options = [],
        $attributes = []
    ) {
        parent::__construct($elementName, $elementLabel, $options, $attributes);

        $default = isset($attributes['set_custom_default']) ? $attributes['set_custom_default'] : false;

        // Get all languages
        $languages = api_get_languages();
        foreach ($languages as $index => $name) {
            if (!empty($default)) {
                $defaultValue = $default;
            } else {
                $defaultValue = api_get_setting('platformLanguage');
            }
            if ($languages[$index] == $defaultValue) {
                $this->addOption($name, $index, ['selected' => 'selected']);
            } else {
                $this->addOption($name, $index);
            }
        }
    }
}
