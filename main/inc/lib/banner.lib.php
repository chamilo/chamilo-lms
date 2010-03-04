<?php
/* For licensing terms, see /license.txt */
require_once(api_get_path(SYS_CODE_PATH).'inc/banner.inc.php');

/**
 * Determines the possible tabs (=sections) that are available.
 * This function is used when creating the tabs in the third header line and all the sections
 * that do not appear there (as determined by the platform admin on the Dokeos configuration settings page)
 * will appear in the right hand menu that appears on several other pages
 *
 * @return array containing all the possible tabs
 *
 * @version Dokeos 1.8.4
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function get_tabs() {
	global $_course, $rootAdminWeb, $_user;

	// Campus Homepage
	$navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
	$navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');

	// My Courses
	if(api_get_setting('use_session_mode')=='true') {
		if(api_is_allowed_to_create_course()) {
			// Link to my courses for teachers
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		} else {
			// Link to my courses for students
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		}

	} else {
		// Link to my courses
		$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
		$navigation['mycourses']['title'] = get_lang('MyCourses');
	}

	// My Profile
	$navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myprofile']['title'] = get_lang('ModifyProfile');

	// Link to my agenda
	$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myagenda']['title'] = get_lang('MyAgenda');

	// Gradebook
	if (api_get_setting('gradebook_enable') == 'true') {
		$navigation['mygradebook']['url'] = api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
		$navigation['mygradebook']['title'] = get_lang('MyGradebook');
	}

	// Reporting
	if(api_is_allowed_to_create_course() || $_user['status']==DRH) {
		// Link to my space
		$navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/';
		$navigation['session_my_space']['title'] = get_lang('MySpace');
	} else {
		// Link to my progress
		$navigation['session_my_progress']['url'] = api_get_path(WEB_CODE_PATH).'auth/my_progress.php';
		$navigation['session_my_progress']['title'] = get_lang('MyProgress');
	}
	
	// Social
	if (api_get_setting('allow_social_tool')=='true') {
			$navigation['social']['url'] = api_get_path(WEB_CODE_PATH).'social/home.php';
			$navigation['social']['title'] = get_lang('SocialNetwork');
	}
	
	// Dashboard
	if (api_is_platform_admin() || $_user['status']==DRH) {
			$navigation['dashboard']['url'] = api_get_path(WEB_CODE_PATH).'dashboard/index.php';
			$navigation['dashboard']['title'] = get_lang('Dashboard');
	}

	// Platform administration
	if (api_is_platform_admin(true)) {
		//$navigation['platform_admin']['url'] = $rootAdminWeb;
		$navigation['platform_admin']['url'] = api_get_path(WEB_CODE_PATH).'admin/';
		$navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
	}

	return $navigation;
}

?>
