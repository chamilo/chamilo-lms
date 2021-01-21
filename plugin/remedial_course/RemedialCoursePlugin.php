<?php

/* For licensing terms, see /license.txt */
require_once __DIR__.'/../../main/inc/global.inc.php';

/**
 * Class RemedialCoursePlugin
 */
class RemedialCoursePlugin extends Plugin
{
    const SETTING_ENABLED = 'enabled';

    public function __construct()
    {
        $settings = [
            self::SETTING_ENABLED => 'boolean',
        ];
        parent::__construct(
            '1.0',
            'Carlos Alvarado',
            $settings
        );
        $this->setSettings();
    }

    /**
     * Create a new instance of RemedialCoursePlugin.
     *
     * @return RemedialCoursePlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Perform the plugin installation.
     */
    public function install()
    {
        $this->saveRemedialField();
        $this->saveAdvanceRemedialField();
    }

    /**
     * Save the arrangement for remedialcourselist, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function saveRemedialField()
    {
        $extraField = new ExtraField('exercise');
        $remedialcourselist = $extraField->get_handler_field_info_by_field_variable('remedialcourselist');
        if (false === $remedialcourselist) {
            $extraField->save([
                'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => 'remedialcourselist',
                'display_text' => 'remedialCourseList',
                'default_value' => 1,
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
            ]);
        }
    }

    /**
     * Save the arrangement for remedialadvancecourselist, it is adjusted internally so that the values
     * match the necessary ones.
     */
    public function saveAdvanceRemedialField()
    {
        $extraField = new ExtraField('exercise');
        $advancedcourselist = $extraField->get_handler_field_info_by_field_variable('advancedcourselist');
        if (false === $advancedcourselist) {
            $extraField->save([
                'field_type' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                'variable' => 'advancedcourselist',
                'display_text' => 'advancedCourseList',
                'default_value' => 1,
                'field_order' => 0,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
                'changeable' => 1,
                'filter' => 0,
            ]);
        }
    }

    /**
     * Set default_value to 0.
     */
    public function uninstall()
    {
    }

    /**
     * Set the  settings.
     */
    private function setSettings()
    {
        if ('true' !== $this->get(self::SETTING_ENABLED)) {
            return;
        }
    }
}
