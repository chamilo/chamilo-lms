<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

require_once api_get_path(LIBRARY_PATH).'skill.lib.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

$skill = new Skill();

switch ($action) {
	case 'add':		
		$skill->add($_REQUEST['name'], $_REQUEST['description'], $_REQUEST['parent']);
		break;		
	
    default:
        echo '';
}
exit;