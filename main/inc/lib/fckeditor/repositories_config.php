<?php

/**
 * Aditional system config settings for document repositories, the Dokeos LMS
 * @author Juan Carlos Ra�a
 * @since 31/December/2008
 */

require_once api_get_path(LIBRARY_PATH).'/fileUpload.lib.php';

$permissions_for_new_directories = api_get_setting('permissions_for_new_directories');
$permissions_for_new_directories = octdec(!empty($permissions_for_new_directories) ? $permissions_for_new_directories : '0777');

$permissions_for_new_files = api_get_setting('permissions_for_new_files');
$permissions_for_new_files = octdec(!empty($permissions_for_new_files) ? $permissions_for_new_files : '0777');

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
if (api_is_in_course())
{
	$course_shared_folder = api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/';

	if (!file_exists($course_shared_folder))
	{
		@mkdir(api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/', 0777);
		$doc_id = add_document($_course, '/shared_folder', 'folder', 0, 'shared_folder');
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'visible', api_get_user_id());
	}

	// Added by Ivan Tcholakov.
	// When the current user is inside a course, his/her own hidden folder is created (if it does not exist) under shared_folder.
	// This new folder is to be used by the online editor when the user is not in group-specific context.
	// The name of the newly created folder is the user_id, the title is created from user names (first name and last name).
	if (!file_exists($course_shared_folder.'/'.api_get_user_id()))
	{
		@mkdir(api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/'.api_get_user_id().'/', 0777);
		$doc_id = add_document($_course, '/shared_folder/'.api_get_user_id(), 'folder', 0, $_user['firstName'].' '.$_user['lastName']);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id());
	}
}

/**
 * Aditional system config settings for document repositories, the Dokeos LMS
 * @author Juan Carlos Ra�a
 * @since 31/December/2008
 */

require_once api_get_path(LIBRARY_PATH).'/fileUpload.lib.php';

$permissions_for_new_directories = api_get_setting('permissions_for_new_directories');
$permissions_for_new_directories = octdec(!empty($permissions_for_new_directories) ? $permissions_for_new_directories : '0777');

$permissions_for_new_files = api_get_setting('permissions_for_new_files');
$permissions_for_new_files = octdec(!empty($permissions_for_new_files) ? $permissions_for_new_files : '0777');

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
if (api_is_in_course())
{
	$course_shared_folder = api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/';

	if (!file_exists($course_shared_folder))
	{ 
		@mkdir(api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/', 0777);
		$doc_id = add_document($_course, '/shared_folder', 'folder', 0, 'shared_folder');
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'visible', api_get_user_id());
	}

	// Added by Ivan Tcholakov.
	// When the current user is inside a course, his/her own hidden folder is created (if it does not exist) under shared_folder.
	// This new folder is to be used by the online editor when the user is not in group-specific context.
	// The name of the newly created folder is the user_id, the title is created from user names (first name and last name).
	if (!file_exists($course_shared_folder.'/'.api_get_user_id())) {
		//@todo call the create_unexisting_directory function and replace this code
		$new_user_dir = api_get_path(SYS_PATH).'courses/'.$_course['path'].'/document/shared_folder/'.api_get_user_id().'/';				
		@mkdir($new_user_dir);
		$perm = api_get_setting('permissions_for_new_directories');
		$perm = octdec(!empty($perm)?$perm:'0770');
		chmod($new_user_dir,$perm);		
		$doc_id = add_document($_course, '/shared_folder/'.api_get_user_id(), 'folder', 0, $_user['firstName'].' '.$_user['lastName']);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', api_get_user_id());
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', api_get_user_id());
	}
}

?>