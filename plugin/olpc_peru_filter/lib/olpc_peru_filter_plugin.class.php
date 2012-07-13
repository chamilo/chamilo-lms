<?php
/* For licensing terms, see /license.txt */

/* To show the plugin course icons you need to add these icons in the main/img Chamilo platform
 * main/img/icons/22/plugin_name.png
 * main/img/icons/64/plugin_name.png
 * main/img/icons/64/plugin_name_na.png
*/
class OLPC_Peru_FilterPlugin extends Plugin
{
    public $is_course_plugin = true;
    
    //When creating a new course, these settings are added to the course
    public $course_settings = array(
//                    array('name' => 'big_blue_button_welcome_message',  'type' => 'text'),
      array('name' => 'olpc_peru_filter_filter',  'type' => 'textarea'),
//                    array('name' => 'big_blue_button_record_and_store', 'type' => 'checkbox')
    );

    static function create() {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct() {
        parent::__construct('0.1', 'Yannick Warnier, Aliosh Neira', array('tool_enable' => 'boolean'));
    }

    function install() {
        //Installing course settings
        $this->install_course_fields_in_all_courses(false);
    }

    function uninstall() {        
        //Deleting course settings
        $this->uninstall_course_fields_in_all_courses();
    }
}
