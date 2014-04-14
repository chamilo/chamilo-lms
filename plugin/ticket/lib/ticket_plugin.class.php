<?php
/* For licensing terms, see /license.txt */
/**
 * Class TicketPlugin definition file
 * @package chamilo.plugin.ticket
 */
/**
 * Class TicketPlugin
 */
class TicketPlugin extends Plugin
{
    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct()
    {
        parent::__construct('1.0', 'Kenny Rodas Chavez, Genesis Lopez, Francis Gonzales, Yannick Warnier', array('tool_enable' => 'boolean'));
    }

    public function install()
    {
        // Create database tables
        require_once api_get_path(SYS_PLUGIN_PATH).PLUGIN_NAME.'/database.php';

        // Create link tab
//        $homep = api_get_path(SYS_PATH).'home/'; //homep for Home Path
//        $menutabs = 'home_tabs'; //menutabs for tabs Menu
//        $menuf = $menutabs;
//        $ext = '.html'; //ext for HTML Extension - when used frequently, variables are faster than hardcoded strings
//        $lang = ''; //el for "Edit Language"
//        if (!empty($_SESSION['user_language_choice'])) {
//            $lang = $_SESSION['user_language_choice'];
//        } elseif (!empty($_SESSION['_user']['language'])) {
//            $lang = $_SESSION['_user']['language'];
//        } else {
//            $lang = api_get_setting('platformLanguage');
//        }
//        $link_url = api_get_path(WEB_PLUGIN_PATH).'ticket/s/myticket.php';
//
//        $home_menu = '<li class="show_menu"><a href="'.$link_url.'" target="_self"><span>Ticket</span></a></li>';
//
//        // Write
//        if (file_exists($homep.$menuf.'_'.$lang.$ext)) {
//            if (is_writable($homep.$menuf.'_'.$lang.$ext)) {
//                $fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');
//                fputs($fp, $home_menu);
//                fclose($fp);
//                if (file_exists($homep.$menuf.$ext)) {
//                    if (is_writable($homep.$menuf.$ext)) {
//                        $fpo = fopen($homep.$menuf.$ext, 'w');
//                        fputs($fpo, $home_menu);
//                        fclose($fpo);
//                    }
//                }
//            } else {
//                $errorMsg = get_lang('HomePageFilesNotWritable');
//            }
//        } else {
//            //File does not exist
//            $fp = fopen($homep.$menuf.'_'.$lang.$ext, 'w');
//            fputs($fp, $home_menu);
//            fclose($fp);
//        }

    }

    public function uninstall()
    {
        $tblSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        $t_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
        $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
        $tblTicketTicket = Database::get_main_table(TABLE_TICKET_TICKET);
        $tblTicketStatus = Database::get_main_table(TABLE_TICKET_STATUS);
        $tblTicketProject = Database::get_main_table(TABLE_TICKET_PROJECT);
        $tblTicketPriority = Database::get_main_table(TABLE_TICKET_PRIORITY);
        $tblTicketMesAttch = Database::get_main_table(TABLE_TICKET_MESSAGE_ATTACHMENTS);
        $tblTicketMessage = Database::get_main_table(TABLE_TICKET_MESSAGE);
        $tblTicketCategory = Database::get_main_table(TABLE_TICKET_CATEGORY);
        $tblTicketAssgLog = Database::get_main_table(TABLE_TICKET_ASSIGNED_LOG);

        //Delete settings
        $sql = "DELETE FROM $tblSettings WHERE variable = 'ticket_tool_enable'";
        Database::query($sql);
        
        
        $sql = "DROP TABLE IF EXISTS $tblTicketTicket";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketStatus";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketProject";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketPriority";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketMesAttch";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketMessage";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketCategory";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketAssgLog";
        Database::query($sql);
        $sql = "DROP TABLE IF EXISTS $tblTicketTicket";
        Database::query($sql);
    }
}