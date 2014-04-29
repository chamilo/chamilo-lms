<?php
/* For licensing terms, see /license.txt */

/**
 * Additional system config settings for document repositories, the Chamilo LMS
 * @author Juan Carlos Raña
 * @since 31/December/2008
 */

require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

// Disabling access for anonymous users.
api_block_anonymous_users();

$permissions_for_new_directories = api_get_permissions_for_new_directories();
$permissions_for_new_files = api_get_permissions_for_new_files();

$userId = api_get_user_id();
$sessionId = api_get_session_id();

if (!empty($_course['path'])) {
    require_once api_get_path(LIBRARY_PATH).'document.lib.php';
    require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

    // Get the Chamilo session properties. Before ajaximagemanager!!!
    $groupId = api_get_group_id();
    $group_properties = GroupManager::get_group_properties($groupId);
    $is_user_in_group = GroupManager::is_user_in_group($userId, $groupId);
}

$sessionName = null;
if (!empty($sessionId)) {
    $sessionName = api_get_session_name($sessionId);
}

$my_path = UserManager::get_user_picture_path_by_id($userId, 'system');
$user_folder = $my_path['dir'].'my_files/';

// Sanity checks for Chamilo.

// Creation of a user owned folder if it does not exist.
if (!file_exists($user_folder)) {
    // A recursive call of mkdir function.
    @mkdir($user_folder, $permissions_for_new_directories, true);
}

// Creation of repository used by platform administrators if it does not exist.
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
    $userInfo = api_get_user_info();

    if ($sessionId == 0) {
        /* Create shared folder. Necessary for courses recycled.
         Always session_id should be zero. Always should be created from a base course, never from a session.*/
        if (!file_exists($base_work_dir.'/shared_folder')) {
            $usf_dir_title = get_lang('SharedFolder');
            $usf_dir_name = '/shared_folder';
            $visibility = 0;
            create_unexisting_directory($_course, $userId, $sessionId, 0, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
        }
        // Create dynamic user shared folder
        if (!file_exists($base_work_dir.'/shared_folder/sf_user_'.$userId)) {
            $usf_dir_title = $userInfo['complete_name'];
            $usf_dir_name = '/shared_folder/sf_user_'.$userId;
            $visibility = 1;
            create_unexisting_directory($_course, $userId, $sessionId, 0, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
        }
    } else {
        // Create shared folder session
        if (!file_exists($base_work_dir.'/shared_folder_session_'.$sessionId)) {
            $usf_dir_title = get_lang('SharedFolder').' ('.$sessionName.')';
            $usf_dir_name = '/shared_folder_session_'.$sessionId;
            $visibility = 0;
            create_unexisting_directory($_course, $userId, $sessionId, 0, $to_user_id, $base_work_dir, $usf_dir_name, $usf_dir_title, $visibility);
        }
        // Create dynamic user shared folder into a shared folder session.
        if (!file_exists($base_work_dir.'/shared_folder_session_'.$sessionId.'/sf_user_'.$userId)) {
            $usf_dir_title = $userInfo['complete_name'].' ('.$sessionName.')';
            $usf_dir_name = '/shared_folder_session_'.$sessionId.'/sf_user_'.$userId;
            $visibility = 1;
            create_unexisting_directory(
                $_course,
                $userId,
                $sessionId,
                0,
                $to_user_id,
                $base_work_dir,
                $usf_dir_name,
                $usf_dir_title,
                $visibility
            );
        }
    }
}
