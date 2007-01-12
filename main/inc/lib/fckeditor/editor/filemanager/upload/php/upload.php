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
if ( ( count($arAllowed) > 0 && !in_array( $sExtension, $arAllowed ) )  || ( count($arDenied) > 0 && in_array( $sExtension, $arDenied ) )){
	SendResults( '202' ) ;
}

$sErrorNumber	= '0' ;
$sFileUrl		= '' ;

// Initializes the counter used to rename the file, if another one with the same name already exists.
$iCounter = 0 ;


$currentCourseRepositorySys =  api_get_path(SYS_COURSE_PATH) . $_course["path"]."/";
$currentCourseRepositoryWeb =  api_get_path(WEB_COURSE_PATH) . $_course["path"]."/";

$sType=strtolower($sType);

if(isset($_SESSION["_course"]["sysCode"])){
	//It's a teacher, so the uoploaded document will be put in course documents
	if(api_is_allowed_to_edit()){
		//set the upload path according to the file type
		if($sType=="mp3"){
			$sServerDir = $currentCourseRepositorySys.'document/audio/';
			$sserverWebath=$currentCourseRepositoryWeb.'document/audio/';
			$sType="audio";
		}
		elseif($sType=="flash"){
			$sServerDir = $currentCourseRepositorySys.'document/flash/';
			$sserverWebath=$currentCourseRepositoryWeb.'document/flash/';
		}
		elseif($sType=="video"){
			$sServerDir = $currentCourseRepositorySys.'document/video/';
			$sserverWebath=$currentCourseRepositoryWeb.'document/video/';
		}
		else{
			$sServerDir = $currentCourseRepositorySys.'document/';
			$sserverWebath=$currentCourseRepositoryWeb.'document/';
		}
	}
	//It's a student, we get the upload path in parameters 
	elseif(isset($_REQUEST['uploadPath']) && $_REQUEST['uploadPath']!=""){
		$sServerDir = $currentCourseRepositorySys.$_REQUEST['uploadPath'];
		$sserverWebath=$currentCourseRepositoryWeb.$_REQUEST['uploadPath'];
	}
	//Default
	else{
		$sServerDir = $currentCourseRepositorySys.'upload/';
		$sserverWebath=$currentCourseRepositoryWeb.'upload/';
	}
	
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

if(!move_uploaded_file( $oFile['tmp_name'], $sServerDir.$sFileName )) $sErrorNumber = '203' ; //check php.ini setting
	
if ( is_file( $sServerDir.$sFileName ) )
{
	$oldumask = umask(0) ;
	chmod( $sFilePath, 0777 ) ;
	umask( $oldumask ) ;
}

//If we are in a course and if it's a teacher who did the upload, we record the uploaded file in database
if(isset($_SESSION["_course"]["sysCode"]) && api_is_allowed_to_edit()){
	
	$document_name= strtr($sFileName,"ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ","aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");
	$document_name=preg_replace('/[^\w\._]/', '_', $document_name);
	$document_size=$oFile["size"];
	
	include_once(api_get_path(LIBRARY_PATH)."fileUpload.lib.php");
	
	if($sType=="flash"){
		$path = "/flash/";
	}
	
	if($sType=="audio"){
		$path = "/audio/";
	}
	
	$doc_id = add_document($_course, $path.$document_name, 'file', $document_size, $document_name);
		
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentCreated', $_user['user_id']);
	
}

SendResults( $sErrorNumber, $sserverWebath.$sFileName, $sFileName ) ;

?>
