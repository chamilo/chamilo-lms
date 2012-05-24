<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
	case 'update_changeable_setting':        
        if (api_is_global_platform_admin()) {            
            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $params = array('variable = ?' =>  array($_GET['id']));                
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
