<?php
/* For licensing terms, see /license.txt */

/* To show the plugin course icons you need to add these icons in the main/img Chamilo platform
 * main/img/icons/22/plugin_name.png
 * main/img/icons/64/plugin_name.png
 * main/img/icons/64/plugin_name_na.png
*/
class OLPC_Peru_FilterPlugin extends Plugin
{
    public $blacklist_enabled_file = '/var/sqg/blacklists';
    public $blacklists_dir = '/var/squidGuard/blacklists';

    public $is_course_plugin = true;
    
    //When creating a new course, these settings are added to the course
    public $course_settings = array(
//                    array('name' => 'big_blue_button_welcome_message',  'type' => 'text'),
//                    array('name' => 'big_blue_button_record_and_store', 'type' => 'checkbox')
    );
    public $course_settings_callback = true;
    public $error = '';

    static function create() {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct() {
        parent::__construct('0.1', 'Yannick Warnier, Aliosh Neira', array('tool_enable' => 'boolean'));
        
        $this->course_settings = array();
        $list = $this->get_blacklist_options();
        foreach ($list as $k => $v) {
            $this->course_settings[] =
              array('group'=> 'olpc_peru_filter_filter', 'name' => $k,  'type' => 'checkbox', 'init_value' => $v);
        }
        require_once dirname(__FILE__).'/../config.php';
        if (!empty($blacklist_enabled_file)) {
            $this->blacklist_enabled_file = $blacklist_enabled_file;
        }
        if (!empty($blacklists_dir)) {
            $this->blacklists_dir = $blacklists_dir;
        }
    }

    function install() {
        //Installing course settings
        $this->install_course_fields_in_all_courses(false);
    }

    function uninstall() {        
        //Deleting course settings
        $this->uninstall_course_fields_in_all_courses();
    }
    /**
     * Caller for the install_course_fields() function
     * @param int The course's integer ID
     * @param boolean Whether to add a tool link on the course homepage
     * @return void
     */
    function course_install($course_id, $add_tool_link = true) {
        //force ignoring the tools table insertion for this plugin
        $this->install_course_fields($course_id, false);
    }

    function course_settings_updated($values = array()) {
        if (!is_array($values) or count($values)==0) {
            return false;
        }
        $this->set_blacklist_options($values['olpc_peru_filter_filter']);
    }
    /**
     * Get a list of options (checked and unchecked) for blacklists as coming
     * from the Squid files
     */
    function get_blacklist_options() {
        $categories = $blacklists = array();
        if (!is_dir($this->blacklists_dir)) { 
            $this->error = 'Could not find blacklists dir '.$this->blacklists_dir;
            return $blacklists; 
        }
        if (!is_file($this->blacklist_enabled_file)) { 
            $this->error = 'Could not find blacklists dir '.$this->blacklists_dir;
            return $blacklists; 
        }
        $list = scandir($this->blacklists_dir);
        foreach ($list as $file) {
            if (substr($file,0,1) == '.' or $file == 'custom_blacklist' or is_dir($this->blacklists_dir.'/'.$file)) {
                continue;
            }
            $categories[] = $file;
        }
        sort($categories);
        $current_blacklist = file($this->blacklist_enabled_file);
        $current_blacklist = array_map('trim', $current_blacklist);
        foreach ($categories as $category) {
            foreach ($current_blacklist as $blacklisted) {
                $checked = 0;
                if ($category == trim($blacklisted)) {
                    $checked = 1;
                    $blacklists[$category] = $checked;
                    break;
                }
                $blacklists[$category] = $checked;
            }
        }
        return $blacklists;
    }
    /**
     * Given an array of blacklist => 0/1, save the new blacklist file to disk
     * @param array Array of blacklists names
     * @return boolean False on error, True on success
     */
    function set_blacklist_options($values) {
        if (!is_array($values)) { return false; }
        if (!is_writeable($this->blacklist_enabled_file)) { return false; }
        $new_blacklist = '';
        foreach ($values as $k => $v) {
            if ($v) {
                $new_blacklist .= $k."\n";
            }
        }
        $r = @file_put_contents($this->blacklist_enabled_file,$new_blacklist);
        //todo check the value or $r in $php_errormsg
        return true;
    }
}
