<?php
/* See license terms in /license.txt */

/**
 * Class OpenMeetingsPlugin.
 */
class OpenMeetingsPlugin extends Plugin
{
    public $isCoursePlugin = true;

    //When creating a new course this settings are added to the course
    public $course_settings = [[
        'name' => 'openmeetings_record_and_store',
        'type' => 'checkbox',
    ]];

    protected function __construct()
    {
        parent::__construct('2.0', 'Francis Gonzales', ['tool_enable' => 'boolean', 'host' => 'text', 'user' => 'text', 'pass' => 'text']);
    }

    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        $table = Database::get_main_table('plugin_openmeetings');
        // id is the internal unique ID (keeps track of historical sessions
        // status is 0 for closed, 1 for open (available)
        // room_id is a reference to the meeting ID on the OpenMeetings server.
        // Any c_id + session_id occurence gets a unique new meeting ID to avoid issues with the number of rooms, as indicated in https://issues.apache.org/jira/browse/OPENMEETINGS-802#comment-13860340
        $sql = "CREATE TABLE IF NOT EXISTS $table (
                id INT unsigned NOT NULL auto_increment PRIMARY KEY,
                c_id INT unsigned NOT NULL DEFAULT 0,
                session_id INT unsigned NOT NULL DEFAULT 0,
                room_id INT unsigned NOT NULL DEFAULT 0,
                meeting_name VARCHAR(255) NOT NULL DEFAULT '',
                attendee_pw VARCHAR(255) NOT NULL DEFAULT '',
                moderator_pw VARCHAR(255) NOT NULL DEFAULT '',
                record INT NOT NULL DEFAULT 0,
                status INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                closed_at DATETIME,
                calendar_id INT DEFAULT 0,
                welcome_msg TEXT NOT NULL DEFAULT '')";
        Database::query($sql);

        //Installing course settings
        $this->install_course_fields_in_all_courses();
    }

    public function uninstall()
    {
        $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $t_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);

        //New settings

        $sql = "DELETE FROM $t_settings WHERE variable = 'openmeetings_tool_enable'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'openmeetings_pass'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'openmeetings_user'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'openmeetings_host'";
        Database::query($sql);

        //Old settings deleting just in case
        $sql = "DELETE FROM $t_settings WHERE variable = 'openmeetings_plugin'";
        Database::query($sql);
        $sql = "DELETE FROM $t_options WHERE variable  = 'openmeetings_plugin'";
        Database::query($sql);
//        $sql = "DELETE FROM $t_settings WHERE variable = 'openmeetings_plugin_host'";
//        Database::query($sql);
//        $sql = "DELETE FROM $t_settings WHERE variable = 'openmeetings_plugin_salt'";
//        Database::query($sql);

        //hack to get rid of Database::query warning (please add c_id...)
        $sql = "DELETE FROM $t_tool WHERE name = 'openmeetings' AND c_id = c_id";
        Database::query($sql);

        $t = Database::get_main_table('plugin_openmeetings');
        $sql = "DROP TABLE IF EXISTS $t";
        Database::query($sql);

        //Deleting course settings
        $this->uninstall_course_fields_in_all_courses($this->course_settings);
    }

    /**
     * @param int  $course_id
     * @param bool $add_tool_link
     */
    public function course_install($course_id, $add_tool_link = true)
    {
        //force ignoring the tools table insertion for this plugin
        $this->install_course_fields($course_id, $add_tool_link);
    }
}
