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
include('../../../../../../global.inc.php');
include_once(api_get_path(INCLUDE_PATH)."lib/fileUpload.lib.php");

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
// echo "<script>alert('".$sType."')</script>";
if ( ( count($arAllowed) > 0 && !in_array( $sExtension, $arAllowed ) )  || ( count($arDenied) > 0 && in_array( $sExtension, $arDenied ) )){
	SendResults( '202' ) ;
}

$sErrorNumber	= '0' ;
$sFileUrl		= '' ;

// Initializes the counter used to rename the file, if another one with the same name already exists.
$iCounter = 0 ;


/*
// Get the "UserFiles" path.
$GLOBALS["UserFilesPath"] = '' ;

if ( isset( $Config['UserFilesPath'] ) )
	$GLOBALS["UserFilesPath"] = $Config['UserFilesPath'] ;
else if ( isset( $_GET['ServerPath'] ) )
	$GLOBALS["UserFilesPath"] = $_GET['ServerPath'] ;
else
	$GLOBALS["UserFilesPath"] = '/UserFiles/' ;

if ( ! ereg( '/$', $GLOBALS["UserFilesPath"] ) )
	$GLOBALS["UserFilesPath"] .= '/' ;

// The the target directory.
//$sServerDir = GetRootPath() . $GLOBALS["UserFilesPath"];
*/

$currentCourseRepositorySys =  api_get_path(SYS_COURSE_PATH) . $_course["path"]."/";
$currentCourseRepositoryWeb =  api_get_path(WEB_COURSE_PATH) . $_course["path"]."/";

$sServerDir = $currentCourseRepositorySys.'document/';



	// Compose the file path.
	if(!is_dir($sServerDir.$sType))
	{
		mkdir($sServerDir.$sType, 0777);
	}

	// Try to add an extension to the file if it has'nt one
	$sFileName = add_ext_on_mime(stripslashes($oFile['name']),$oFile['type']);

	// Replace dangerous characters
	$sFileName = replace_dangerous_char($sFileName,'strict');

	// Transform any .php file in .phps fo security
	$sFileName = php2phps($sFileName);
	
	
	$sFilePath = $sServerDir.$sType."/" . $sFileName ;

	// If a file with that name already exists.
	/*if ( is_file( $sFilePath ) )
	{
		$iCounter++ ;
		$sFileName = RemoveExtension( $sFileName ) . '(' . $iCounter . ').' . $sExtension ;
		$sErrorNumber = '201' ;
	}
	else
	{*/

		if(!move_uploaded_file( $oFile['tmp_name'], $sFilePath )) $sErrorNumber = '203' ; //check php.ini setting

		if ( is_file( $sFilePath ) )
		{
			$oldumask = umask(0) ;
			chmod( $sFilePath, 0777 ) ;
			umask( $oldumask ) ;
		}
		
		$sFileUrl = $currentCourseRepositoryWeb . 'document/' . $sType . "/" . $sFileName ;

		//break ;
	//}

SendResults( $sErrorNumber, $sFileUrl, $sFileName ) ;
?>