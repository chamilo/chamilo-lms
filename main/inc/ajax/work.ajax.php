<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';


$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
	case 'get_work_user_list':        
       

		break;	
    default:
        echo '';
}
exit;