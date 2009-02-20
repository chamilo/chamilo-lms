<?php 
/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2005 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: upload.php
 * 	This is the "File Uploader" for PHP.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

$language_file = array('create_course');
include '../../../../../../global.inc.php';

require_once api_get_path(INCLUDE_PATH).'lib/fckeditor/repositories_config.php';

require('config.php') ;
require('util.php') ;

 // This is the function that sends the results of the uploading process.
function SendResults( $errorNumber, $fileUrl = '', $fileName = '', $customMsg = '' )
{
	echo '<script type="text/javascript">' ;
	echo 'window.parent.OnUploadCompleted(' . $errorNumber . ',"' . str_replace( '"', '\\"', $fileUrl ) . '","' . str_replace( '"', '\\"', $fileName ) . '", "' . str_replace( '"', '\\"', $customMsg ) . '") ;' ;
	echo '</script>' ;
	exit ;
}

function check_and_create_resource_directory($repository_path, $resource_directory, $resource_directory_name)
{
	global $permissions_for_new_directories;

	$resource_directory_full_path = substr($repository_path, 0, strlen($repository_path) - 1) . $resource_directory . '/';

	if (!is_dir($resource_directory_full_path))
	{
		if (@mkdir($resource_directory_full_path, $permissions_for_new_directories))
		{
			// While we are in a course: Registering the newly created folder in the course's database.
			if (api_is_in_course())
			{
				global $_course, $_user;
				global $group_properties, $to_group_id;
				$group_directory = !empty($group_properties['directory']) ? $group_properties['directory'] : '';

				$doc_id = add_document($_course, $group_directory.$resource_directory, 'folder', 0, $resource_directory_name);
				api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'FolderCreated', $_user['user_id'], $to_group_id);
			}
			return true;
		}
		return false;
	}
	return true;
}

// Check if this uploader has been enabled.
if ( !$Config['Enabled'] )
	SendResults( '1', '', '', 'This file uploader is disabled. Please check the "editor/filemanager/upload/php/config.php" file' ) ;

// Check if the file has been correctly uploaded.
if ( !isset( $_FILES['NewFile'] ) || is_null( $_FILES['NewFile']['tmp_name'] ) || $_FILES['NewFile']['name'] == '' )
	SendResults( '202' ) ;

// Get the posted file.
$oFile = $_FILES['NewFile'] ;

// Get the uploaded file name and extension.
$sFileName = $oFile['name'] ;
$sOriginalFileName = $sFileName ;
$sExtension = substr( $sFileName, ( strrpos($sFileName, '.') + 1 ) ) ;
$sExtension = strtolower( $sExtension ) ;

// The the file type (from the QueryString, by default 'File').
$sType = isset( $_GET['Type'] ) ? $_GET['Type'] : 'File' ;

// Get the allowed and denied extensions arrays.
$arAllowed	= $Config['AllowedExtensions'][$sType] ;
$arDenied	= $Config['DeniedExtensions'][$sType] ;

// Check if it is an allowed extension.
if ( ( count($arAllowed) > 0 && !in_array( $sExtension, $arAllowed ) )  || ( count($arDenied) > 0 && in_array( $sExtension, $arDenied ) )){
	SendResults( '202' ) ;
}

$sErrorNumber	= '0' ;
$sFileUrl		= '' ;

// Initializes the counter used to rename the file, if another one with the same name already exists.
$iCounter = 0 ;

$sType=strtolower($sType);

// Choosing the repository to be used.
if (api_is_in_course())
{
	if (!api_is_in_group())
	{
		// 1. We are inside a course and not in a group.
		if (api_is_allowed_to_edit())
		{
			// 1.1. Teacher
			$sServerDir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/';
			$sserverWebath = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
		}
		else
		{
			// 1.2. Student
			$sServerDir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/shared_folder/sf_user_'.api_get_user_id().'/';
			$sserverWebath = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/sf_user_'.api_get_user_id().'/';
		}
	}
	else
	{
		// 2. Inside a course and inside a group.
		$sServerDir = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
		$sserverWebath = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
	}
}
else
{
	if (api_is_platform_admin() && $_SESSION['this_section'] == 'platform_admin')
	{
		// 3. Platform administration activities.
		$sServerDir = $_configuration['root_sys'].'home/default_platform_document/';
		$sserverWebath = $_configuration['root_web'].'home/default_platform_document/';
	}
	else
	{
		// 4. The user is outside courses.
		$sServerDir = $_configuration['root_sys'].'main/upload/users/'.api_get_user_id().'/my_files/';
		$sserverWebath = $_configuration['root_web'].'main/upload/users/'.api_get_user_id().'/my_files/';
	}
}

// Set the upload path according to the resource type.
if ($sType == 'audio')
{
	check_and_create_resource_directory($sServerDir, '/audio', get_lang('Audio'));
	$sServerDir = $sServerDir.'audio/';
	$sserverWebath = $sserverWebath.'audio/';
	$path = '/audio/';
}
elseif ($sType == 'mp3')
{
	$sType = 'audio';
	check_and_create_resource_directory($sServerDir, '/audio', get_lang('Audio'));
	$sServerDir = $sServerDir.'audio/';
	$sserverWebath = $sserverWebath.'audio/';
	$path = '/audio/';
}
elseif ($sType == 'flash')
{
	check_and_create_resource_directory($sServerDir, '/flash', get_lang('Flash'));
	$sServerDir = $sServerDir.'flash/';
	$sserverWebath = $sserverWebath.'flash/';
	$path = '/flash/';
}
elseif ($sType == 'images')
{
	check_and_create_resource_directory($sServerDir, '/images', get_lang('Images'));
	$sServerDir = $sServerDir.'images/';
	$sserverWebath = $sserverWebath.'images/';
	$path = '/images/';
}
elseif ($sType == 'video')
{
	check_and_create_resource_directory($sServerDir, '/video', get_lang('Video'));
	$sServerDir = $sServerDir.'video/';
	$sserverWebath = $sserverWebath.'video/';
	$path = '/video/';
}
elseif ($sType == 'video/flv')
{
	check_and_create_resource_directory($sServerDir, '/video', get_lang('Video'));
	check_and_create_resource_directory($sServerDir, '/video/flv', 'flv');
	$sServerDir = $sServerDir.'video/flv/';
	$sserverWebath = $sserverWebath.'video/flv/';
	$path = '/video/flv/';
}
else
{
	$path = '/';
}

// Try to add an extension to the file if it has'nt one
$sFileName = add_ext_on_mime(stripslashes($oFile['name']),$oFile['type']);

// Replace dangerous characters
$sFileName = replace_dangerous_char($sFileName,'strict');

// Transform any .php file in .phps for security
$sFileName = php2phps($sFileName);

if ( is_file( $sServerDir.$sFileName ) ){
	$dotIndex = strrpos($sFileName, '.');
	$ext = '';
	if(is_int($dotIndex)) 
	{
		$ext = substr($sFileName, $dotIndex);
		$base = substr($sFileName, 0, $dotIndex);
	}
	$counter = 0;
	while(is_file($sServerDir.$sFileName)) 
	{
		$counter++;
		$sFileName = $base.'_'.$counter.$ext;
	}
}

if (!move_uploaded_file( $oFile['tmp_name'], $sServerDir.$sFileName )) $sErrorNumber = '203' ; // Check php.ini setting.
	
if ( is_file( $sServerDir.$sFileName ) )
{
	$oldumask = umask(0) ;
	chmod( $sServerDir.$sFileName, $permissions_for_new_files ) ;
	umask( $oldumask ) ;

	// If we are in a course we record the uploaded file in database.
	if (api_is_in_course())
	{
		$document_name = $sFileName;
		$document_size=$oFile["size"];
		$group_directory = !empty($group_properties['directory']) ? $group_properties['directory'] : '';
	
		$doc_id = add_document($_course, $group_directory.$path.$document_name, 'file', $document_size, $document_name);
		api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentCreated', $_user['user_id'], $to_group_id);
	}
}

SendResults( $sErrorNumber, $sserverWebath.$sFileName, $sFileName ) ;

?>
