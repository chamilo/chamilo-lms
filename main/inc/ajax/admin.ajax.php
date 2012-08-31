<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'user_exists':        
        $user_info = api_get_user_info($_REQUEST['id']);
        if (empty($user_info)) {
            echo 0;
        } else {
            echo 1;
        }
        break;
    case 'find_coaches':
        $coaches = SessionManager::get_coaches_by_keyword($_REQUEST['tag']);
        $json_coaches = array();
        if (!empty($coaches)) {
            foreach ($coaches as $coach) {
                $json_coaches[] = array('key' => $coach['user_id'], 'value' => api_get_person_name($coach['firstname'], $coach['lastname']));
            }
        }
        echo json_encode($json_coaches);
        break;
	case 'update_changeable_setting':
        $url_id = api_get_current_access_url_id();        
        if (api_is_global_platform_admin() && $url_id == 1) {            
            if (isset($_GET['id']) && !empty($_GET['id'])) {                
                $params = array('variable = ? ' =>  array($_GET['id']));
                $data = api_get_settings_params($params);                
                if (!empty($data)) {
                    foreach ($data as $item) {                
                        $params = array('id' =>$item['id'], 'access_url_changeable' => $_GET['changeable']);
                        api_set_setting_simple($params);        
                    }
                }                
                echo '1';
            }        
        }
        break;
}
