<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.main 
 */

define('CHAMILO_HOMEPAGE', true);
$language_file = array('courses', 'index');

/* Flag forcing the 'current course' reset, as we're not inside a course anymore. */
// Maybe we should change this into an api function? an example: Coursemanager::unset();
$cidReset = true;

/* Included libraries */
require_once 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.

$userportal = new IndexManager(' ');
$tpl = $userportal->tpl->get_template('layout/layout_two_col.tpl');

//if (!$userportal->tpl->isCached($tpl, api_get_user_id())) {
		
	//@todo all this could be moved in the IndexManager
		
	$courses_list 			= $userportal->return_courses_main_plugin();
	$personal_course_list 	= UserManager::get_personal_session_course_list(api_get_user_id());
	
		
	// Main courses and session list
	ob_start();
	$userportal->return_courses_and_sessions($personal_course_list);
	$courses_and_sessions = ob_get_contents();
	ob_get_clean();	
	$userportal->tpl->assign('plugin_courses_block', 		$userportal->return_courses_main_plugin());
	$userportal->tpl->assign('content', 					$courses_and_sessions);	
	$userportal->tpl->assign('profile_block', 				$userportal->return_profile_block());
	$userportal->tpl->assign('account_block',				$userportal->return_account_block());
	$userportal->tpl->assign('navigation_course_links', 	$userportal->return_navigation_course_links($menu_navigation));
	$userportal->tpl->assign('plugin_courses_right_block', 	$userportal->return_plugin_courses_block());
	$userportal->tpl->assign('reservation_block', 			$userportal->return_reservation_block());
	$userportal->tpl->assign('search_block', 				$userportal->return_search_block());
	$userportal->tpl->assign('classes_block', 				$userportal->return_classes_block());	
/*} else {	
}*/
$userportal->tpl->display($tpl);

