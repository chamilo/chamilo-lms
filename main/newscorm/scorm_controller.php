<?php //$id: $
/**
 * Script that acts like a controller for all SCORM-related queries
 * @package dokeos.learnpath.scorm
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
$my_action = (!empty($_REQUEST['action'])?$_REQUEST['action']:'');
if(empty($my_action)){
}else{
	switch ($my_action){
		case 'exportpath':
		case 'exportscorm':
		case 'deletepath':
		case 'publishpath':
		case 'editpath':
		case 'add':
		case 'editscorm':
		case 'adminaction':
			include('scorm_admin.php');
			break;
		case 'view':
		case 'next':
		case 'previous':
			include('scorm_view.php');
			break;
		default:
			include('scorm_view.php');
			break;
	}
	exit();
}
?>
