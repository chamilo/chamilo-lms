<?php
/* For licensing terms, see /license.txt */

/**
 * Class GradingElectronicPlugin.
 */
class GradingElectronicPlugin extends Plugin
{
    public const EXTRAFIELD_STUDENT_ID = 'fcdice_or_acadis_student_id';
    public const EXTRAFIELD_COURSE_PROVIDER_ID = 'plugin_gradingelectronic_provider_id';
    public const EXTRAFIELD_COURSE_ID = 'plugin_gradingelectronic_course_id';
    public const EXTRAFIELD_COURSE_HOURS = 'plugin_gradingelectronic_coursehours';

    /**
     * {@inheritdoc}
     */
    protected function __construct()
    {
        parent::__construct(
            '0.8',
            'Angel Fernando Quiroz Campos, Julio Montoya',
            [
                'tool_enable' => 'boolean',
            ]
        );
    }

    /**
     * @return \GradingElectronicPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * {@inheritdoc}
     */
    public function get_name()
    {
        return 'grading_electronic';
    }

    /**
     * Actions for install.
     */
    public function install()
    {
        $this->setUpExtraFields();
    }

    /**
     * Actions for uninstall.
     */
    public function uninstall()
    {
        $this->setDownExtraFields();
    }

    /**
     * @return \FormValidator|void
     */
    public function getForm()
    {
        $extraField = new ExtraField('course');
        $courseIdField = $extraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_ID
        );

        if (!$courseIdField) {
            return null;
        }

        $extraFieldValue = new ExtraFieldValue('course');
        $courseIdValue = $extraFieldValue->get_values_by_handler_and_field_variable(
            api_get_course_int_id(),
            self::EXTRAFIELD_COURSE_ID
        );

        $form = new FormValidator('frm_grading_electronic');
        $form->addDateRangePicker(
            'range',
            get_lang('DateRange'),
            true,
            [
                'id' => 'range',
                'format' => 'YYYY-MM-DD',
                'timePicker' => 'false',
                'validate_format' => 'Y-m-d',
            ]
        );
        $form->addText('course', $this->get_lang('CourseId'));
        $form->addButtonDownload(get_lang('Generate'));
        $form->addRule('course', get_lang('ThisFieldIsRequired'), 'required');
        $form->setDefaults([
            'course' => $courseIdValue ? $courseIdValue['value'] : null,
        ]);

        return $form;
    }

    /**
     * Check if the current use is allowed to see the button.
     *
     * @return bool
     */
    public function isAllowed()
    {
        $allowed = api_is_teacher() || api_is_platform_admin() || api_is_course_tutor();

        if (!$allowed) {
            return false;
        }

        $toolIsEnabled = $this->get('tool_enable') === 'true';

        if (!$toolIsEnabled) {
            return false;
        }

        return true;
    }

    /**
     * Create extra fields for this plugin.
     */
    private function setUpExtraFields()
    {
        $uExtraField = new ExtraField('user');

        if (!$uExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_STUDENT_ID
        )) {
            $uExtraField->save([
                'variable' => self::EXTRAFIELD_STUDENT_ID,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->get_lang('StudentId'),
                'visible_to_self' => true,
                'changeable' => true,
            ]);
        }

        $cExtraField = new ExtraField('course');

        if (!$cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_PROVIDER_ID
        )) {
            $cExtraField->save([
                'variable' => self::EXTRAFIELD_COURSE_PROVIDER_ID,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->get_lang('ProviderId'),
                'visible_to_self' => true,
                'changeable' => true,
            ]);
        }

        if (!$cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_ID
        )) {
            $cExtraField->save([
                'variable' => self::EXTRAFIELD_COURSE_ID,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->get_lang('CourseId'),
                'visible_to_self' => true,
                'changeable' => true,
            ]);
        }

        if (!$cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_HOURS
        )) {
            $cExtraField->save([
                'variable' => self::EXTRAFIELD_COURSE_HOURS,
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'display_text' => $this->get_lang('CourseHours'),
                'visible_to_self' => true,
                'changeable' => true,
            ]);
        }
    }

    /**
     * Remove extra fields for this plugin.
     */
    private function setDownExtraFields()
    {
        $uExtraField = new ExtraField('user');
        $studentIdField = $uExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_STUDENT_ID
        );

        if ($studentIdField) {
            $uExtraField->delete($studentIdField['id']);
        }

        $cExtraField = new ExtraField('course');
        $providerIdField = $cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_PROVIDER_ID
        );
        $courseIdField = $cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_ID
        );
        $courseHoursField = $cExtraField->get_handler_field_info_by_field_variable(
            self::EXTRAFIELD_COURSE_HOURS
        );

        if ($providerIdField) {
            $cExtraField->delete($providerIdField['id']);
        }

        if ($courseIdField) {
            $cExtraField->delete($courseIdField['id']);
        }

        if ($courseHoursField) {
            $cExtraField->delete($courseHoursField['id']);
        }
    }
}
