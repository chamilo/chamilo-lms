<?php
/* For licensing terms, see /license.txt */

class TicketPlugin extends Plugin
{
    static function create() {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct() {
        parent::__construct('1.0', 'Kenny Rodas Chavez', array('tool_enable' => 'boolean', 'host' =>'text', 'salt' => 'text'));
    }

    function install() {
    	
    	// Create database tables
    	require_once api_get_path(SYS_PLUGIN_PATH).PLUGIN_NAME.'/database.php';
    	
    	// Create link tab
    	$homep = api_get_path(SYS_PATH).'home/'; //homep for Home Path
    	$menutabs = 'home_tabs'; //menutabs for tabs Menu
    	$menuf = $menutabs;
    	$ext = '.html'; //ext for HTML Extension - when used frequently, variables are faster than hardcoded strings
	    $lang = ''; //el for "Edit Language"
		if (!empty($_SESSION['user_language_choice'])) {
			$lang = $_SESSION['user_language_choice'];
		} elseif (!empty($_SESSION['_user']['language'])) {
			$lang = $_SESSION['_user']['language'];
		} else {
			$lang = api_get_setting('platformLanguage');
		}
		$link_url = api_get_path(WEB_PLUGIN_PATH).'ticket/s/myticket.php';
		
		$home_menu = '<li class="show_menu"><a href="'.$link_url.'" target="_self"><span>Ticket</span></a></li>';
		
    	// Write
		if (file_exists($homep.$menuf.'_'.$lang.$ext)) {
			if (is_writable($homep.$menuf.'_'.$lang.$ext)) {
				$fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');
				fputs($fp, $home_menu);
				fclose($fp);
				if (file_exists($homep.$menuf.$ext)) {
					if (is_writable($homep.$menuf.$ext)) {
						$fpo = fopen($homep.$menuf.$ext, 'w');
						fputs($fpo, $home_menu);
						fclose($fpo);
					}
				}
			} else {
				$errorMsg = get_lang('HomePageFilesNotWritable');
			}
		} else {
			//File does not exist
			$fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');
			fputs($fp, $home_menu);
			fclose($fp);
		}
		
    }

    function uninstall() {
        $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $t_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);

        //New settings

        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_tool_enable'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_salt'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_host'";
        Database::query($sql);

        //Old settings deleting just in case
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin'";
        Database::query($sql);
        $sql = "DELETE FROM $t_options WHERE variable  = 'bbb_plugin'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin_host'";
        Database::query($sql);
        $sql = "DELETE FROM $t_settings WHERE variable = 'bbb_plugin_salt'";
        Database::query($sql);

        //hack to get rid of Database::query warning (please add c_id...)
        $sql = "DELETE FROM $t_tool WHERE name = 'videoconference' AND c_id = c_id";
        Database::query($sql);

        $sql = "DROP TABLE IF EXISTS plugin_bbb_meeting";
        Database::query($sql);

        //Deleting course settings
        $this->uninstall_course_fields_in_all_courses();
    }
}