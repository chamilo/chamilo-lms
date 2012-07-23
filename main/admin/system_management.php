<?php

/**
 * 
 * 
 * @package chamilo.admin
 * @author Laurent Opprecht <laurent@opprecht.info>
 * @license see /license.txt
 */
$language_file = array('admin');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once __DIR__ . '/admin_page.class.php';

class SystemManagementPage extends AdminPage
{

    const PARAM_ACTION = 'action';
    const PARAM_SECURITY_TOKEN = 'sec_token';
    const ACTION_DEFAULT = 'list';
    const ACTION_SECURITY_FAILED = 'security_failed';

    function get_action()
    {
        $result = Request::get(self::PARAM_ACTION, self::ACTION_DEFAULT);
        if ($result != self::ACTION_DEFAULT) {
            $passed = Security::check_token('get');
            Security::clear_token();
            $result = $passed ? $result : self::ACTION_SECURITY_FAILED;
        }
        return $result;
    }

    function url($params)
    {
        $token = Security::get_token();
        $params[self::PARAM_SECURITY_TOKEN] = $token;
        return Uri::here($params);
    }

    function display_default()
    {
        $message = get_lang('RemoveOldDatabaseMessage');
        $message_table = get_lang('RemoveOldTables');
        
        $message_table .= "<br />".implode(' , ', self::get_tables_to_delete());        
        $url = $this->url(array(self::PARAM_ACTION => 'drop_old_databases'));
        $url_table = $this->url(array(self::PARAM_ACTION => 'drop_old_tables'));
        
        $go = get_lang('Go');
        $access_url_id = api_get_current_access_url_id();
        $message2 = '';
        
        if ($access_url_id === 1) {
            if (api_is_windows_os()) {
                $message2 .= get_lang('SpaceUsedOnSystemCannotBeMeasuredOnWindows');
            } else {
                $dir = api_get_path(SYS_PATH);
                $du = exec('du -sh ' . $dir, $err);
                list($size, $none) = explode("\t", $du);
                $limit = $_configuration[$url]['hosting_limit_disk_space'];
                $message2 .= sprintf(get_lang('TotalSpaceUsedByPortalXLimitIsYMB'), $size, $limit);
            }
        }
        if (!empty($message2)) {
            $message2 = '<li>' . $message2 . '</li>';
        }
        echo <<<EOT
        <ul>
        <li>
            <div>$message</div>        
            <a class="btn" href=$url>$go</a>
        </li>
        <li>
            <div>$message_table</div>        
            <a class="btn" href=$url_table>$go</a>
        </li>
        $message2
        </ul>
EOT;
    }

    function display_security_failed()
    {
        Display::display_error_message(get_lang('NotAuthorized'));
    }

    function display_content()
    {
        $action = $this->get_action();
        switch ($action) {
            case self::ACTION_DEFAULT:
                $this->display_default();
                return;

            case self::ACTION_SECURITY_FAILED:
                $this->display_security_failed();
                return;

            default:
                $f = array($this, $action);
                if (is_callable($f)) {
                    call_user_func($f);
                    return;
                } else {
                    Display::display_error_message(get_lang('UnknownAction'));
                }
                return;
        }
    }

    /**
     *
     * @return ResultSet 
     */
    function get_old_databases()
    {
        $course_db = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT id, code, db_name, directory, course_language FROM $course_db WHERE target_course_code IS NULL AND db_name IS NOT NULL ORDER BY code";
        return new ResultSet($sql);
    }
    
    function drop_old_tables()
    {
        $tables_to_remove = self::get_tables_to_delete();
        
        $number_tables_deleted = 0;
        $tables_deleted = '';
        foreach ($tables_to_remove as $table) {
            //Deleting  tables
            $drop_table = "DROP TABLE $table";            
            $success = Database::query($drop_table);    
            $success =true;
            if ($success) {
                $tables_deleted .= $table.'<br />';
                $number_tables_deleted++;
            }
        }
        
        Display::display_confirmation_message(get_lang('OldTablesDeleted') . ' ' . $number_tables_deleted);
        Display::display_confirmation_message($tables_deleted, false);
    }
    
    function get_tables_to_delete() {
        $tables_to_remove = array(
            Database::get_main_table(TABLE_MAIN_CLASS),
            Database::get_main_table(TABLE_MAIN_CLASS_USER),
            Database::get_main_table(TABLE_MAIN_COURSE_CLASS),
        );
        return $tables_to_remove;
    }

    function drop_old_databases()
    {
        $result = array();
        $courses = $this->get_old_databases();
        $course_db = Database::get_main_table(TABLE_MAIN_COURSE);
        foreach ($courses as $course) {
            $drop_statement = 'DROP DATABASE ' . $course['db_name'];
            $success = Database::query($drop_statement);
            if ($success) {
                /*
                 * Note that Database::update do not supports null statements so
                 * we do it by hand here.
                 */
                $id = $course['id'];                
                $update_statement = "UPDATE $course_db SET db_name = NULL WHERE id = $id";
                Database::query($update_statement);
                $result[] = $course['db_name'];
            }
        }
        
        Display::display_confirmation_message(get_lang('OldDatabasesDeleted') . ' ' . count($result));
        
        
        return $result;
    }

}

$page = new SystemManagementPage(get_lang('SystemManagement'));
$page->display();
