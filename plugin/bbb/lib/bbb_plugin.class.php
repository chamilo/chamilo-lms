<?php
/* For licensing terms, see /license.txt */
/* To show the plugin course icons you need to add these icons:
 * main/img/icons/22/plugin_name.png
 * main/img/icons/64/plugin_name.png
 * main/img/icons/64/plugin_name_na.png
*/
/**
 * Videoconference plugin with BBB
 */
//namespace Chamilo\Plugin\BBB;
/**
 * Class BBBPlugin
 */
class BBBPlugin extends Plugin
{
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
        ]
    ];

    /**
     * BBBPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '2.5',
            'Julio Montoya, Yannick Warnier, Angel Fernando Quiroz Campos',
            [
                'tool_enable' => 'boolean',
                'host' => 'text',
                'salt' => 'text',
                'enable_global_conference' => 'boolean',
                'enable_global_conference_per_user' => 'boolean',
                'enable_conference_in_course_groups' => 'boolean',
                'enable_global_conference_link' => 'boolean'
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * @param string $variable
     * @return bool
     */
    public function validateCourseSetting($variable)
    {
        if ($variable === 'bbb_enable_conference_in_groups') {
            if ($this->get('enable_conference_in_course_groups') === 'true') {

                return true;
            } else {
                return false;
            }
        }

        return true;
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
     * Install
     */
    public function install()
    {
        $table = Database::get_main_table('plugin_bbb_meeting');
        $sql = "CREATE TABLE IF NOT EXISTS $table (
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
                visibility TINYINT NOT NULL DEFAULT 1,
                voice_bridge INT NOT NULL DEFAULT 1,
                access_url INT NOT NULL DEFAULT 1,
                video_url TEXT NULL,
                has_video_m4v TINYINT NOT NULL DEFAULT 0
                )";
        Database::query($sql);

        Database::query(
            "CREATE TABLE IF NOT EXISTS plugin_bbb_room (
                id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                meeting_id int(10) unsigned NOT NULL,
                participant_id int(11) NOT NULL,
                in_at datetime NOT NULL,
                out_at datetime NOT NULL,
                FOREIGN KEY (meeting_id) REFERENCES plugin_bbb_meeting (id),
                FOREIGN KEY (participant_id) REFERENCES user (id)
            );"
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
            'enable_global_conference_link',
            'enable_conference_in_course_groups',
            'bbb_plugin',
            'bbb_plugin_host',
            'bbb_plugin_salt'
        ];

        foreach ($variables as $variable) {
            $sql = "DELETE FROM $t_settings WHERE variable = '$variable'";
            Database::query($sql);
        }

        $sql = "DELETE FROM $t_options WHERE variable = 'bbb_plugin'";
        Database::query($sql);

        // hack to get rid of Database::query warning (please add c_id...)
        $sql = "DELETE FROM $t_tool WHERE name = 'bbb' AND c_id != 0";
        Database::query($sql);

        Database::query('DROP TABLE IF EXISTS plugin_bbb_room');

        $t = Database::get_main_table('plugin_bbb_meeting');
        $sql = "DROP TABLE IF EXISTS $t";
        Database::query($sql);

        // Deleting course settings
        $this->uninstall_course_fields_in_all_courses($this->course_settings);
    }
}
