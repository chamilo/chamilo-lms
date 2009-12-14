<?php
/**
 * This class response to the Ajax calls
 * 
 */
require_once '../global.inc.php';
$action = $_GET['a'];
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

switch ($action) {	
	case 'search_tags':
			
		/* For licensing terms, see /dokeos_license.txt */		
		$field_id = intval($_GET['field_id']);
		$tag = $_GET['tag'];
		echo UserManager::get_tags($tag, $field_id,'json','10');			
		break;
	default:
		echo '';
}
exit;
?>