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
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function get_tabs() {
	global $_course;

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
	$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php?view=month&'.(!empty($_course['path']) ? 'coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myagenda']['title'] = get_lang('MyAgenda');

	// Gradebook
	if (api_get_setting('gradebook_enable') == 'true') {
		$navigation['mygradebook']['url'] = api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
		$navigation['mygradebook']['title'] = get_lang('MyGradebook');
	}

	// Reporting
	if(api_is_allowed_to_create_course() || api_is_drh() || api_is_session_admin()) {
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
        
        require_once api_get_path(LIBRARY_PATH).'message.lib.php';
        require_once api_get_path(LIBRARY_PATH).'social.lib.php';
                
        // get count unread message and total invitations
        $count_unread_message = MessageManager::get_number_of_messages(true);
        

        $number_of_new_messages_of_friend   = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
        $group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION,false);
        $group_pending_invitations = 0;
        if (!empty($group_pending_invitations )) {        
	        $group_pending_invitations = count($group_pending_invitations);
        }
        $total_invitations = intval($number_of_new_messages_of_friend) + $group_pending_invitations + intval($count_unread_message);
        $total_invitations = (!empty($total_invitations)?' ('.$total_invitations.')':'');
        
        
		$navigation['social']['title'] = get_lang('SocialNetwork'). $total_invitations;
	}
	
	// Dashboard
	if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
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