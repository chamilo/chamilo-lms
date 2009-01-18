<?php

/**
 * Aditional system config settings for document repositories, the Dokeos LMS
 * @author Juan Carlos Raña
 * @since 31/December/2008
 */

require_once api_get_path(LIBRARY_PATH).'/fileUpload.lib.php';

if (!empty($_course['path']))
{
	require_once api_get_path(LIBRARY_PATH).'/document.lib.php';
	require_once api_get_path(LIBRARY_PATH).'/groupmanager.lib.php';

    // Get the Dokeos session properties. Before ajaximagemanager!!!
    $to_group_id = !empty($_SESSION['_gid']) ? $_SESSION['_gid'] : 0 ;
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
	$is_user_in_group = GroupManager::is_user_in_group($_user['user_id'],$_SESSION['_gid']);
}

$user_folder = api_get_path(SYS_PATH).'main/upload/users/'.api_get_user_id().'/my_files/';

// Sanity checks for Dokeos.

// Creation of a user owned folder if it does not exist.
if (!file_exists($user_folder))
{
	// A recursive call of mkdir function.
	@mkdir(api_get_path(SYS_PATH).'main/upload/users/'.api_get_user_id().'/my_files/', 0777, true);
}

// Creation of repository used by paltform administrators if it does not exist.
if (api_is_platform_admin())
{
	$homepage_folder = api_get_path(SYS_PATH).'home/default_platform_document/';

	if (!file_exists($homepage_folder))
	{
		@mkdir(api_get_path(SYS_PATH).'home/default_platform_document/', 0777);
	}
}

// Creation in the course document repository of a shared folder if it does not exist.
if ($_course['id'])
{
	$course_shared_folder = api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/';

	if (!file_exists($course_shared_folder))
	{
		@mkdir(api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/', 0777);
		$doc_id = add_document($_course, '/shared_folder', 'folder', 0, 'shared_folder');
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'visible', api_get_user_id());
	}
}

?>