<?php
/* For licensing terms, see /license.txt */
/**
 * SessionsBlockSliderPlugin class
 * Plugin to add a sessions slider in homepage
 * @package chamilo.plugin.sessions_slider_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SessionsSliderBlockPlugin extends Plugin
{
    const CONFIG_SHOW_SLIDER = 'show_slider';
    const FIELD_VARIABLE_SHOW_IN_SLIDER = 'show_in_slider';
    const FIELD_VARIABLE_VIDEO = 'video_url_in_slider';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Angel Fernando Quiroz Campos',
            [self::CONFIG_SHOW_SLIDER => 'boolean']
        );
    }

    /**
     * Instance the plugin
     * @staticvar SessionsBlockSliderPlugin $result
     * @return Tour
     */
    public static function create(){
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Returns the "system" name of the plugin in lowercase letters
     * @return string
     */
    public function get_name()
    {
        return 'sessions_slider_block';
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        $this->createExtraFields();
    }

    /**
     * Create the new extra fields
     */
    private function createExtraFields()
    {
        $showInSliderField = new ExtraField('session');
        $showInSliderField->save([
            'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            'variable' => self::FIELD_VARIABLE_SHOW_IN_SLIDER,
            'display_text' => $this->get_lang('ShowInSliderBlock'),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);

        $videoUrlInSliderField = new ExtraField('session');
        $videoUrlInSliderField->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => self::FIELD_VARIABLE_VIDEO,
            'display_text' => $this->get_lang('VideoUrlForSliderBlock'),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
        $this->deleteExtraFields();
    }

    /**
     * Get the extra field information by its variable
     * @param sstring $fieldVariable The field variable
     * @return array The info
     */
    private function getExtraFieldInfo($fieldVariable)
    {
        $extraField = new ExtraField('session');
        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable($fieldVariable);

        return $extraFieldHandler;
    }

    /**
     * Delete extra field and their values
     */
    private function deleteExtraFields()
    {
        $extraFieldInfo = $this->getExtraFieldInfo(self::FIELD_VARIABLE_SHOW_IN_SLIDER);
        $extraFieldExists = $extraFieldInfo !== false;

        if ($extraFieldExists) {
            $extraField = new ExtraField('session');
            $extraField->delete($extraFieldInfo['id']);
        }

        $extraFieldInfo = $this->getExtraFieldInfo(self::FIELD_VARIABLE_VIDEO);
        $extraFieldExists = $extraFieldInfo !== false;

        if ($extraFieldExists) {
            $extraField = new ExtraField('session');
            $extraField->delete($extraFieldInfo['id']);
        }
    }

    /**
     * Get the session to show in slider
     * @return array The session list
     */
    public function getSessionList()
    {
        $showInSliderFieldInfo = $this->getExtraFieldInfo(self::FIELD_VARIABLE_SHOW_IN_SLIDER);

        $fieldValueInfo = new ExtraFieldValue('session');
        $values = $fieldValueInfo->getValuesByFieldId($showInSliderFieldInfo['id']);

        $sessions = [];

        foreach ($values as $valueInfo) {
            $sessions[] = api_get_session_info($valueInfo['item_id']);
        }

        return $sessions;
    }

}
