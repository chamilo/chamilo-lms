<?php
/**
 *	Chamilo LMS
 *
 *	For a full list of contributors, see "credits.txt".
 *	The full license can be read in "license.txt".
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	See the GNU General Public License for more details.
 */

/**
 * Aditional system config settings for document repositories, the Chamilo LMS
 * @author Juan Carlos Raña
 * @since 31/December/2008
 */

require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

$permissions_for_new_directories = api_get_permissions_for_new_directories();
$permissions_for_new_files = api_get_permissions_for_new_files();

if (!empty($_course['path'])) {
	require_once api_get_path(LIBRARY_PATH).'document.lib.php';
	require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

    // Get the Chamilo session properties. Before ajaximagemanager!!!
    $to_group_id = !empty($_SESSION['_gid']) ? $_SESSION['_gid'] : 0 ;
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
	$is_user_in_group = GroupManager::is_user_in_group($_user['user_id'],$_SESSION['_gid']);
}

$my_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'system');
$user_folder = $my_path['dir'].'my_files/';

// Sanity checks for Chamilo.

// Creation of a user owned folder if it does not exist.
if (!file_exists($user_folder)) {
	// A recursive call of mkdir function.
	@mkdir($user_folder, $permissions_for_new_directories, true);
}

// Creation of repository used by paltform administrators if it does not exist.
if (api_is_platform_admin()) {
	$homepage_folder = api_get_path(SYS_PATH).'home/default_platform_document/';
	if (!file_exists($homepage_folder)) {
		@mkdir($homepage_folder, $permissions_for_new_directories);
	}
}


// Create course shared folders
if (api_is_in_course()) {
	$course_dir      = $_course['path'].'/document';
	$sys_course_path = api_get_path(SYS_COURSE_PATH);
	$base_work_dir   = $sys_course_path.$course_dir;
	$current_session_id = api_get_session_id();

	if($current_session_id==0){
		//Create shared folder. Necessary for courses recycled. Allways session_id should be zero. Allway should be created from a base course, never from a session.
		if (!file_exists($base_work_dir.'/shared_folder')) {
			$usf_dir_title = get_lang('SharedFolder');
			$usf_dir_name = '/shared_folder';
			$to_group_id = 0;
			$visibility = 0;
			create_unexisting_directory($_course, $_user['user_id'], api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
		}
		// Create dynamic user shared folder
		if (!file_exists($base_work_dir.'/shared_folder/sf_user_'.api_get_user_id())) {
				$usf_dir_title = api_get_person_name($_user['firstName'], $_user['lastName']);
				$usf_dir_name = '/shared_folder/sf_user_'.api_get_user_id();
				$to_group_id = 0;
				$visibility = 1;
				create_unexisting_directory($_course, $_user['user_id'], api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
		}
	}
	else{
			//Create shared folder session
			if (!file_exists($base_work_dir.'/shared_folder_session_'.$current_session_id)) {
				$usf_dir_title = get_lang('SharedFolder').' ('.api_get_session_name($current_session_id).')';
				$usf_dir_name = '/shared_folder_session_'.$current_session_id;
				$to_group_id = 0;
				$visibility = 0;
				create_unexisting_directory($_course, $_user['user_id'], api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
			}
			//Create dynamic user shared folder into a shared folder session
			if (!file_exists($base_work_dir.'/shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id())) {
				$usf_dir_title = api_get_person_name($_user['firstName'], $_user['lastName']).' ('.api_get_session_name($current_session_id).')';
				$usf_dir_name = '/shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id();
				$to_group_id = 0;
				$visibility = 1;
				create_unexisting_directory($_course, $_user['user_id'], api_get_session_id(), $to_group_id, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
			}
	}
}