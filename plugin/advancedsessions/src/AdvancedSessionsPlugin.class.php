<?php
/* For licensing terms, see /license.txt */

/**
 * The Advanced Session allow add sessions' extra fields 
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.advancedSessions
 */
class AdvancedSessionsPlugin extends Plugin
{

    const FIELD_NAME = 'as_description';
    const FIELD_TITLE = 'ASDescription';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        parent::__construct('1.0', 'Angel Fernando Quiroz Campos');
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return Tour
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     * @return void
     */
    public function install()
    {
        $this->createSessionFields();
    }

    /**
     * Uninstall the plugin
     * @return void
     */
    public function uninstall()
    {
        $this->removeSessionFields();
    }

    private function createSessionFields()
    {
        SessionManager::create_session_extra_field(self::FIELD_NAME, ExtraField::FIELD_TYPE_TEXTAREA, self::FIELD_TITLE);
    }

    private function removeSessionFields()
    {
        $sessionField = new ExtraField('session');
        $fieldInfo = $sessionField->get_handler_field_info_by_field_variable(self::FIELD_NAME);

        if (!empty($fieldInfo)) {
            $sessionField->delete($fieldInfo['id']);
        }
    }

    public static function getFieldInfo()
    {
        $sessionField = new ExtraField('session');
        $fieldInfo = $sessionField->get_handler_field_info_by_field_variable(self::FIELD_NAME);

        return $fieldInfo;
    }

    public static function hasDescriptionField()
    {
        $fieldInfo = self::getFieldInfo();

        return empty($fieldInfo) ? false : true;
    }

    public static function saveSessionFieldValue($id, $description)
    {
        $id = intval($id);
        $fieldValuesTable = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);

        $fieldValue = new ExtraFieldValue('session');
        $descriptionValue = $fieldValue->get_values_by_handler_and_field_variable($id, self::FIELD_NAME, false);

        if ($descriptionValue === false) {
            $fieldInfo = self::getFieldInfo();

            if (empty($fieldInfo)) {
                return;
            }

            $attributes = array(
                'session_id' => $id,
                'field_id' => $fieldInfo['id'],
                'field_value' => $description,
                'tms' => api_get_utc_datetime()
            );

            Database::insert($fieldValuesTable, $attributes);
        } else {
            $attributes = array(
                'field_value' => $description,
                'tms' => api_get_utc_datetime()
            );

            Database::update($fieldValuesTable, $attributes, array(
                'id = ?' => $descriptionValue['id']
            ));
        }
    }

    public static function getSessionDescription($sessionId) {
        $sessionId = intval($sessionId);

        $fieldValue = new ExtraFieldValue('session');
        $description = $fieldValue->get_values_by_handler_and_field_variable($sessionId, self::FIELD_NAME, false);

        return $description !== false ? $description['field_value'] : get_lang('None');
    }
}
