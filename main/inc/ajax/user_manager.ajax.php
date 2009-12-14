<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls 
 */
require_once '../global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
$action = $_GET['a'];

switch ($action) {	
	case 'search_tags':
		if (api_is_anonymous()){
			echo '';
			break;
		} else {		
			$field_id = intval($_GET['field_id']);
			$tag = $_GET['tag'];
			echo UserManager::get_tags($tag, $field_id,'json','10');			
			break;
		}
	default:
		echo '';
}
exit;
?>