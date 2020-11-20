<?php

/* For licensing terms, see /license.txt */

/* To show the plugin course icons you need to add these icons:
 * main/img/icons/22/plugin_name.png
 * main/img/icons/64/plugin_name.png
 * main/img/icons/64/plugin_name_na.png
*/

/**
 * Class BBBPlugin
 * Videoconference plugin with BBB
 */
class BBBPlugin extends Plugin
{
    const INTERFACE_FLASH = 0;
    const INTERFACE_HTML5 = 1;

    const LAUNCH_TYPE_DEFAULT = 0;
    const LAUNCH_TYPE_SET_BY_TEACHER = 1;
    const LAUNCH_TYPE_SET_BY_STUDENT = 2;

    const ROOM_OPEN = 0;
    const ROOM_CLOSE = 1;
    const ROOM_CHECK = 2;

    public $isCoursePlugin = true;

    // When creating a new course this settings are added to the course
    public $course_settings = [
        [
            'name' => 'big_blue_button_record_and_store',
            'type' => 'checkbox',
        ],
        [
            'name' => 'bbb_enable_conference_in_groups',
            'type' => 'checkbox',
        ],
        [
            'name' => 'bbb_force_record_generation',
            'type' => 'checkbox',
        ],
    ];

    /**
     * BBBPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '2.8.2',
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
                'interface' => [
                    'type' => 'select',
                    'options' => [
                        self::INTERFACE_HTML5 => 'HTML5',
                        self::INTERFACE_FLASH => 'Flash',
                    ],
                ],
                'launch_type' => [
                    'type' => 'select',
                    'options' => [
                        self::LAUNCH_TYPE_DEFAULT => 'SetByDefault',
                        self::LAUNCH_TYPE_SET_BY_TEACHER => 'SetByTeacher',
                        self::LAUNCH_TYPE_SET_BY_STUDENT => 'SetByStudent',
                    ],
                    'translate_options' => true, // variables will be translated using the plugin->get_lang
                ],
                'allow_regenerate_recording' => 'boolean',
                // Default course settings, must be the same as $course_settings
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
     * @return BBBPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * @param string $variable
     *
     * @return bool
     */
    public function validateCourseSetting($variable)
    {
        if ($this->get('disable_course_settings') === 'true') {
            return false;
        }

        $result = true;
        switch ($variable) {
            case 'bbb_enable_conference_in_groups':
                $result = $this->get('enable_conference_in_course_groups') === 'true';
                break;
            case 'bbb_force_record_generation':
                $result = $this->get('allow_regenerate_recording') === 'true';
                break;
            case 'big_blue_button_record_and_store':
        }

        return $result;
    }

    /**
     *
     * @return array
     */
    public function getCourseSettings()
    {
        $settings = [];
        if ($this->get('disable_course_settings') !== 'true') {
            $settings = parent::getCourseSettings();
        }

        return $settings;
    }

    /**
     *
     * @return \Plugin
     */
    public function performActionsAfterConfigure()
    {
        $result = $this->get('disable_course_settings') === 'true';
        if ($result) {
            $valueConference = $this->get('bbb_enable_conference_in_groups') === 'true' ? 1 : 0;
            self::update_course_field_in_all_courses('bbb_enable_conference_in_groups', $valueConference);

            $valueForceRecordGeneration = $this->get('bbb_force_record_generation') === 'true' ? 1 : 0;
            self::update_course_field_in_all_courses('bbb_force_record_generation', $valueForceRecordGeneration);

            $valueForceRecordStore = $this->get('big_blue_button_record_and_store') === 'true' ? 1 : 0;
            self::update_course_field_in_all_courses('big_blue_button_record_and_store', $valueForceRecordStore);
        }

        return $this;
    }

    /**
     * Install
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_bbb_meeting (
                id INT unsigned NOT NULL auto_increment PRIMARY KEY,
                c_id INT unsigned NOT NULL DEFAULT 0,
                group_id INT unsigned NOT NULL DEFAULT 0,
                user_id INT unsigned NOT NULL DEFAULT 0,
                meeting_name VARCHAR(255) NOT NULL DEFAULT '',
                attendee_pw VARCHAR(255) NOT NULL DEFAULT '',
                moderator_pw VARCHAR(255) NOT NULL DEFAULT '',
                record INT NOT NULL DEFAULT 0,
                status INT NOT NULL DEFAULT 0,
                created_at VARCHAR(255) NOT NULL,
                closed_at VARCHAR(255) NOT NULL,
                calendar_id INT DEFAULT 0,
                welcome_msg VARCHAR(255) NOT NULL DEFAULT '',
                session_id INT unsigned DEFAULT 0,
                remote_id CHAR(30),
                internal_meeting_id VARCHAR(255) DEFAULT NULL,
                visibility TINYINT NOT NULL DEFAULT 1,
                voice_bridge INT NOT NULL DEFAULT 1,
                access_url INT NOT NULL DEFAULT 1,
                video_url TEXT NULL,
                has_video_m4v TINYINT NOT NULL DEFAULT 0,
                interface INT NOT NULL DEFAULT 0
                )";
        Database::query($sql);

        Database::query(
            "CREATE TABLE IF NOT EXISTS plugin_bbb_room (
                id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                meeting_id int NOT NULL,
                participant_id int(11) NOT NULL,
                in_at datetime,
                out_at datetime,
                interface int NOT NULL DEFAULT 0,
                close INT NOT NULL DEFAULT 0
            );"
        );
        $fieldLabel = 'plugin_bbb_course_users_limit';
        $fieldType = ExtraField::FIELD_TYPE_INTEGER;
        $fieldTitle = $this->get_lang('MaxUsersInConferenceRoom');
        $fieldDefault = '0';
        $extraField = new ExtraField('course');
        $fieldId = CourseManager::create_course_extra_field(
            $fieldLabel,
            $fieldType,
            $fieldTitle,
            $fieldDefault
        );
        $extraField->find($fieldId);
        $extraField->update(
            [
                'id' => $fieldId,
                'variable' => 'plugin_bbb_course_users_limit',
                'changeable' => 1,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
            ]
        );
        $fieldLabel = 'plugin_bbb_session_users_limit';
        $extraField = new ExtraField('session');
        $fieldId = SessionManager::create_session_extra_field(
            $fieldLabel,
            $fieldType,
            $fieldTitle,
            $fieldDefault
        );
        $extraField->find($fieldId);
        $extraField->update(
            [
                'id' => $fieldId,
                'variable' => 'plugin_bbb_session_users_limit',
                'changeable' => 1,
                'visible_to_self' => 1,
                'visible_to_others' => 0,
            ]
        );

        // Installing course settings
        $this->install_course_fields_in_all_courses();
    }

    /**
     * Uninstall
     */
    public function uninstall()
    {
        $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $t_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);

        $variables = [
            'bbb_salt',
            'bbb_host',
            'bbb_tool_enable',
            'enable_global_conference',
            'enable_global_conference_per_user',
            'enable_global_conference_link',
            'disable_download_conference_link',
            'enable_conference_in_course_groups',
            'bbb_plugin',
            'bbb_plugin_host',
            'bbb_plugin_salt',
            'max_users_limit',
            'global_conference_allow_roles',
            'interface',
            'launch_type',
        ];

        $urlId = api_get_current_access_url_id();

        foreach ($variables as $variable) {
            $sql = "DELETE FROM $t_settings WHERE variable = '$variable' AND access_url = $urlId";
            Database::query($sql);
        }

        $em = Database::getManager();
        $sm = $em->getConnection()->getSchemaManager();
        if ($sm->tablesExist('plugin_bbb_meeting')) {
            Database::query("DELETE FROM plugin_bbb_meeting WHERE access_url = $urlId");
        }

        // Only delete tables if it's uninstalled from main url.
        if (1 == $urlId) {
            $extraField = new ExtraField('course');
            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(
                'plugin_bbb_course_users_limit'
            );
            if (!empty($extraFieldInfo)) {
                $extraField->delete($extraFieldInfo['id']);
            }
            $extraField = new ExtraField('session');
            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable(
                'plugin_bbb_session_users_limit'
            );
            if (!empty($extraFieldInfo)) {
                $extraField->delete($extraFieldInfo['id']);
            }

            $sql = "DELETE FROM $t_options WHERE variable = 'bbb_plugin'";
            Database::query($sql);

            // hack to get rid of Database::query warning (please add c_id...)
            $sql = "DELETE FROM $t_tool WHERE name = 'bbb' AND c_id != 0";
            Database::query($sql);

            if ($sm->tablesExist('plugin_bbb_room')) {
                Database::query('DROP TABLE IF EXISTS plugin_bbb_room');
            }
            if ($sm->tablesExist('plugin_bbb_meeting')) {
                Database::query('DROP TABLE IF EXISTS plugin_bbb_meeting');
            }

            // Deleting course settings
            $this->uninstall_course_fields_in_all_courses($this->course_settings);
        }
    }

    /**
     * Update
     */
    public function update()
    {
        $sql = "SHOW COLUMNS FROM plugin_bbb_room WHERE Field = 'close'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE plugin_bbb_room ADD close int unsigned NULL";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }

            Database::update(
                'plugin_bbb_room',
                ['close' => BBBPlugin::ROOM_CLOSE]
            );
        }
    }

    /**
     * Return an array with URL
     *
     * @param string $conferenceUrl
     *
     * @return array
     */
    public function getUrlInterfaceLinks($conferenceUrl)
    {
        $urlList[] = $this->getFlashUrl($conferenceUrl);
        $urlList[] = $this->getHtmlUrl($conferenceUrl);

        return $urlList;
    }

    /**
     * @param string $conferenceUrl
     *
     * @return array
     */
    public function getFlashUrl($conferenceUrl)
    {
        $data = [
            'text' => $this->get_lang('EnterConferenceFlash'),
            'url' => $conferenceUrl.'&interface='.self::INTERFACE_FLASH,
            'icon' => 'resources/img/64/videoconference_flash.png',
        ];

        return $data;
    }

    /**
     * @param string $conferenceUrl
     *
     * @return array
     */
    public function getHtmlUrl($conferenceUrl)
    {
        $data = [
            'text' => $this->get_lang('EnterConferenceHTML5'),
            'url' => $conferenceUrl.'&interface='.self::INTERFACE_HTML5,
            'icon' => 'resources/img/64/videoconference_html5.png',
        ];

        return $data;
    }

    /**
     * Set the course setting in all courses
     *
     * @param bool $variable Course setting to update
     * @param bool $value New values of the course setting
     */
    public function update_course_field_in_all_courses($variable, $value)
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
