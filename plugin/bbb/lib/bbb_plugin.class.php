<?php

class BBBPlugin extends Plugin
{        
    public $variables = array(
                    'big_blue_button_meeting_name',
                    'big_blue_button_attendee_password',
                    'big_blue_button_moderator_password',
                    'big_blue_button_welcome_message',
                    'big_blue_button_max_students_allowed', 
    );
    
    static function create() {
        static $result = null;
        return $result ? $result : $result = new self();
    }
    
    protected function __construct() {
        parent::__construct('2.0', 'Julio Montoya, Yannick Warnier', array('tool_enable' => 'boolean', 'host' =>'text', 'salt' => 'text'));
    }
    
    function course_install($course_id) {    
        if (empty($course_id)) {
            return false;
        }
        $t_course = Database::get_course_table(TABLE_COURSE_SETTING);       
        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_welcome_message','','plugins')";
        $r = Database::query($sql_course);

        /*
        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_meeting_name','','plugins')";
        $r = Database::query($sql_course);
        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_attendee_password','','plugins')";
        $r = Database::query($sql_course);
        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_moderator_password','','plugins')";
        $r = Database::query($sql_course);    

        //New BBB settings

        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_max_students_allowed','','plugins')";
        $r = Database::query($sql_course);

        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_open_new_window','','plugins')";
        $r = Database::query($sql_course);

        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_student_must_wait_until_moderator','','plugins')";
        $r = Database::query($sql_course);

        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_join_start_date','','plugins')";    
        $r = Database::query($sql_course);

        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_join_end_date','','plugins')";    
        $r = Database::query($sql_course);*/

        $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, 'big_blue_button_record_and_store','','plugins')";    
        $r = Database::query($sql_course);   

        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $sql_course = "INSERT INTO $t_tool VALUES ($course_id, NULL, 'videoconference','../../plugin/bbb/start.php','visio.gif','".string2binary(api_get_setting('course_create_active_tools', 'videoconference'))."','0','squaregrey.gif','NO','_self','plugin','0')";
        $r = Database::query($sql_course);        
    }
    
    function install() {        
        $t_course = Database::get_course_table(TABLE_COURSE_SETTING);

        $table = Database::get_main_table('plugin_bbb_meeting');
        $sql = "CREATE TABLE $table ( 
                id INT unsigned NOT NULL auto_increment PRIMARY KEY, 
                c_id INT unsigned NOT NULL DEFAULT 0,
                meeting_name VARCHAR(255) NOT NULL DEFAULT '', 
                attendee_pw VARCHAR(255) NOT NULL DEFAULT '',
                moderator_pw VARCHAR(255) NOT NULL DEFAULT '', 
                record INT NOT NULL DEFAULT 0,
                status INT NOT NULL DEFAULT 0,
                created_at VARCHAR(255) NOT NULL,
                calendar_id INT DEFAULT 0,
                welcome_msg VARCHAR(255) NOT NULL DEFAULT '')";
        Database::query($sql);

        // Update existing courses to add conference settings
        $t_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id, code FROM $t_courses ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $course_id = $row['id'];
 
           foreach ($this->variables as $variable) {
                $sql = "SELECT value FROM $t_course WHERE c_id = $course_id AND variable = '$variable' ";
                $result = Database::query($sql);
                if (!Database::num_rows($result)) {
                    $sql_course = "INSERT INTO $t_course (c_id, variable,value,category) VALUES ($course_id, '$variable','','plugins')";
                    $r = Database::query($sql_course);
                }
            }
            $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
            $sql = "SELECT name FROM $t_tool WHERE c_id = $course_id AND name = 'videoconference' ";
            $result = Database::query($sql);
            if (!Database::num_rows($result)) {
                $sql_course = "INSERT INTO $t_tool VALUES ($course_id, NULL, 'videoconference','../../plugin/bbb/start.php','visio.gif','".string2binary(api_get_setting('course_create_active_tools', 'videoconference'))."','0','squaregrey.gif','NO','_self','plugin','0')";
                $r = Database::query($sql_course);    
            }    
        }
    }
    
    function uninstall() {
        $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $t_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);        
        
        //New settings
        
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_tool_enable'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_salt'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_host'";
        Database::query($sql);
        
        //Old settings

        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin'";
        Database::query($sql);
        $sql = "DELETE FROM $t_options WHERE variable = 'bbb_plugin'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin_host'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin_salt'";
        Database::query($sql);
        
        $sql = "DROP TABLE IF EXISTS plugin_bbb_meeting";
        Database::query($sql);
        
        // update existing courses to add conference settings
        $t_courses = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id, code FROM $t_courses ORDER BY id";
        $res = Database::query($sql);
        while ($row = Database::fetch_assoc($res)) {
            $t_course = Database::get_course_table(TABLE_COURSE_SETTING);
            // $variables is loaded in the config.php file
            foreach ($this->variables as $variable) {
                $sql_course = "DELETE FROM $t_course WHERE c_id = " . $row['id'] . " AND variable = '$variable'";
                $r = Database::query($sql_course);
            }

            $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
            $sql_course = "DELETE FROM $t_tool WHERE  c_id = " . $row['id'] . " AND link = '../../plugin/bbb/start.php'";
            $r = Database::query($sql_course);
        }
    }
}