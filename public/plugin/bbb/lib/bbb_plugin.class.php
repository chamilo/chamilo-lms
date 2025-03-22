<?php

/* For licensing terms, see /license.txt */

/**
 * BigBlueButton plugin configuration class.
 * Handles plugin options and course settings.
 */
class BBBPlugin extends Plugin
{
    const ROOM_OPEN = 0;
    const ROOM_CLOSE = 1;
    const ROOM_CHECK = 2;

    public $isCoursePlugin = true;

    // Default course settings when creating a new course
    public $course_settings = [
        ['name' => 'big_blue_button_record_and_store', 'type' => 'checkbox'],
        ['name' => 'bbb_enable_conference_in_groups', 'type' => 'checkbox'],
        ['name' => 'bbb_force_record_generation', 'type' => 'checkbox'],
        ['name' => 'big_blue_button_students_start_conference_in_groups', 'type' => 'checkbox'],
    ];

    /**
     * BBBPlugin constructor.
     * Defines all available plugin settings.
     */
    protected function __construct()
    {
        parent::__construct(
            '2.9',
            'Julio Montoya, Yannick Warnier, Angel Fernando Quiroz Campos, Jose Angel Ruiz',
            [
                'tool_enable' => 'boolean',
                'host' => 'text',
                'salt' => 'text',
                'enable_global_conference' => 'boolean',
                'enable_global_conference_per_user' => 'boolean',
                'enable_conference_in_course_groups' => 'boolean',
                'enable_global_conference_link' => 'boolean',
                'disable_download_conference_link' => 'boolean',
                'max_users_limit' => 'text',
                'global_conference_allow_roles' => [
                    'type' => 'select',
                    'options' => [
                        PLATFORM_ADMIN => get_lang('Administrator'),
                        COURSEMANAGER => get_lang('Teacher'),
                        STUDENT => get_lang('Student'),
                        STUDENT_BOSS => get_lang('StudentBoss'),
                    ],
                    'attributes' => ['multiple' => 'multiple'],
                ],
                'allow_regenerate_recording' => 'boolean',
                'big_blue_button_record_and_store' => 'checkbox',
                'bbb_enable_conference_in_groups' => 'checkbox',
                'bbb_force_record_generation' => 'checkbox',
                'disable_course_settings' => 'boolean',
                'meeting_duration' => 'text',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * Returns a singleton instance of the plugin.
     */
    public static function create(): self
    {
        static $result = null;
        return $result ??= new self();
    }

    /**
     * Validates if a course setting is enabled depending on global plugin configuration.
     */
    public function validateCourseSetting($variable): bool
    {
        if ($this->get('disable_course_settings') === 'true') {
            return false;
        }

        switch ($variable) {
            case 'bbb_enable_conference_in_groups':
                return $this->get('enable_conference_in_course_groups') === 'true';
            case 'bbb_force_record_generation':
                return $this->get('allow_regenerate_recording') === 'true';
            default:
                return true;
        }
    }

    /**
     * Returns course-level plugin settings if not disabled globally.
     */
    public function getCourseSettings(): array
    {
        if ($this->get('disable_course_settings') === 'true') {
            return [];
        }

        return parent::getCourseSettings();
    }

    /**
     * Performs automatic updates to all course settings after configuration changes.
     */
    public function performActionsAfterConfigure(): self
    {
        if ($this->get('disable_course_settings') === 'true') {
            self::update_course_field_in_all_courses(
                'bbb_enable_conference_in_groups',
                $this->get('enable_conference_in_course_groups') === 'true' ? 1 : 0
            );
            self::update_course_field_in_all_courses(
                'bbb_force_record_generation',
                $this->get('allow_regenerate_recording') === 'true' ? 1 : 0
            );
            self::update_course_field_in_all_courses(
                'big_blue_button_record_and_store',
                $this->get('big_blue_button_record_and_store') === 'true' ? 1 : 0
            );
        }

        return $this;
    }

    /**
     * Updates a course setting value across all existing courses.
     */
    public function update_course_field_in_all_courses($variable, $value): void
    {
        // Update existing courses to add the new course setting value
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id FROM $table ORDER BY id";
        $res = Database::query($sql);
        $courseSettingTable = Database::get_course_table(TABLE_COURSE_SETTING);
        while ($row = Database::fetch_assoc($res)) {
            Database::update(
                $courseSettingTable,
                ['value' => $value],
                ['variable = ? AND c_id = ?' => [$variable, $row['id']]]
            );
        }
    }
}
