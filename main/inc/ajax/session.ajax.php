<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$language_file[] = 'admin';
require_once '../global.inc.php';

$action = $_REQUEST['a'];

//$user_id = api_get_user_id();

switch ($action) {    
    case  'get_user_sessions':
        if (api_is_platform_admin()) {
            $user_id = intval($_POST['user_id']);            
            $list_sessions = SessionManager::get_sessions_by_user($user_id);
            if (!empty($list_sessions)) {
                foreach($list_sessions as $session_item) {                
                    echo $session_item['session_name'].'<br />';
                }
            } else {            
                echo get_lang('NoSessionsForThisUser');
            } 
            unset($list_sessions);
        }
        break;
    default:
        echo '';
}
exit;
