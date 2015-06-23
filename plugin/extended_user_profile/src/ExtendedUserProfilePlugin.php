<?php
/* For licensing terms, see /license.txt */
/**
 * ExtendedUserProfilePlugin class
 * Plugin to add (date of birth, national ID, Gender, work study place) user extra fields
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.extended_user_profile
 */
class ExtendedUserProfilePlugin extends Plugin
{
    const VARIABLE_DATE_OF_BIRTH = 'date_of_birth';
    const VARIABLE_NATIONAL_ID = 'national_id';
    const VARIABLE_GENDER = 'gender';
    const VARIABLE_WORKSTUDY_PLACE = 'work_or_study_place';
    const VARIABLE_OFFICER_POSITION = 'officer_position';

    protected $strings;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('1.0', 'Angel Fernando Quiroz Campos');
    }

    /**
     * Instance the plugin
     * @return ExtendedUserProfilePlugin The class instance
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        $this->createExtraFields();
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
        $this->deleteExtraFields();
    }

    /**
     * Returns the "system" name of the plugin in lowercase letters
     * @return string
     */
    public function get_name()
    {
        return 'extended_user_profile';
    }

    /**
     * Create the new extra fields
     */
    private function createExtraFields()
    {
        $className = get_class($this);

        $dateOfBirthField = new ExtraField('user');
        $dateOfBirthField->save([
            'field_type' => ExtraField::FIELD_TYPE_DATE,
            'variable' => self::VARIABLE_DATE_OF_BIRTH,
            'display_text' => get_plugin_lang('DateOfBirth', $className),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);

        $nationalIdField = new ExtraField('user');
        $nationalIdField->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => self::VARIABLE_NATIONAL_ID,
            'display_text' => get_plugin_lang('NationalID', $className),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);

        $genderOptions = array(
            get_plugin_lang('Male', $className),
            get_plugin_lang('Female', $className)
        );

        $nationalIdField = new ExtraField('user');
        $nationalIdField->save([
            'field_type' => ExtraField::FIELD_TYPE_SELECT,
            'variable' => self::VARIABLE_GENDER,
            'display_text' => get_plugin_lang('Gender', $className),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null,
            'field_options' => implode('; ', $genderOptions)
        ]);

        $nationalIdField = new ExtraField('user');
        $nationalIdField->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => self::VARIABLE_WORKSTUDY_PLACE,
            'display_text' => get_plugin_lang('WorkStudyPlace', $className),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);

        $officerPosition = new Extrafield('user');
        $officerPosition->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => self::VARIABLE_OFFICER_POSITION,
            'display_text' => get_plugin_lang('OfficePosition', $className),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);
    }

    /**
     * Get the variables of new extra fields
     * @return array
     */
    public function getExtraFieldVariables()
    {
        return [
            self::VARIABLE_DATE_OF_BIRTH,
            self::VARIABLE_NATIONAL_ID,
            self::VARIABLE_GENDER,
            self::VARIABLE_WORKSTUDY_PLACE,
            self::VARIABLE_OFFICER_POSITION
        ];
    }

    /**
     * Get the extra field information by its variable
     * @return array The info
     */
    private function getExtraFieldInfo($variable)
    {
        $extraField = new ExtraField('user');
        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable($variable);

        return $extraFieldHandler;
    }

    /**
     * Delete extra fields and their values
     */
    private function deleteExtraFields()
    {
        $variables = $this->getExtraFieldVariables();

        foreach ($variables as $variable) {
            $extraFieldInfo = $this->getExtraFieldInfo($variable);
            $extraFieldExists = $extraFieldInfo !== false;

            if (!$extraFieldExists) {
                continue;
            }

            $extraField = new ExtraField('user');
            $extraField->delete($extraFieldInfo['id']);
        }
    }

}
