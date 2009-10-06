<?php // $Id$
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2006 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
* Main script for the documents tool
*
* This script allows the user to manage files and directories on a remote http server.
*
* The user can : - upload a file
*
* The script respects the strategical split between process and display, so the first
* part is only processing code (init, process, display preparation) and the second
* part is only display (HTML)
*
* @package dokeos.upload
*/

/**
 * INIT SECTION
*/

// name of the language file that needs to be included
$language_file[] = "document";
$language_file[] = "scorm";
$language_file[] = "scormdocument";
$language_file[] = "learnpath";

// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
<!-- //
function check_unzip() {
	if(document.upload.unzip.checked==true){
	document.upload.if_exists[0].disabled=true;
	document.upload.if_exists[1].checked=true;
	document.upload.if_exists[2].disabled=true;
	}
	else {
	document.upload.if_exists[0].checked=true;
	document.upload.if_exists[0].disabled=false;
	document.upload.if_exists[2].disabled=false;
	}
}
// -->
</script>";

$is_allowed_to_edit = api_is_allowed_to_edit();
if(!$is_allowed_to_edit){
	api_not_allowed(true);
}



/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/

//many useful functions in main_api.lib.php, by default included

require_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');

/*
-----------------------------------------------------------
	Variables
	- some need defining before inclusion of libraries
-----------------------------------------------------------
*/
$courseDir   = $_course['path']."/document";
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;
$noPHP_SELF=true;
$max_filled_space = DocumentManager::get_course_quota();

//what's the current path?
if(isset($_REQUEST['curdirpath'])) {
	$path = $_REQUEST['curdirpath'];
}else{
	$path = '/';
}
// set calling tool
if(isset($_REQUEST['tool'])) {
	$my_tool = $_REQUEST['tool'];
	$_SESSION['my_tool'] = $_REQUEST['tool'];
}elseif(!empty($_SESSION['my_tool'])){
	$my_tool = $_SESSION['my_tool'];
}else{
	$my_tool = 'document';
	$_SESSION['my_tool'] = $my_tool;
}

// Check the path
// If the path is not found (no document id), set the path to /
//if(!DocumentManager::get_document_id($_course,$path)) { $path = '/'; }

//$interbreadcrumb[]=array("url"=>"./document.php?curdirpath=".urlencode($path).$req_gid, "name"=> $langDocuments);

/**
 * Process
 */
event_access_tool(TOOL_UPLOAD);

/**
 *	Prepare the header
 */

$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/upload.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
	var myUpload = new upload(0);
</script>';

/**
 * Now call the corresponding display script, the current script acting like a controller.
 */
switch($my_tool){
	case TOOL_LEARNPATH:
		require('form.scorm.php');
		break;
	//the following cases need to be distinguished later on
	case TOOL_DROPBOX:
	case TOOL_STUDENTPUBLICATION:
	case TOOL_DOCUMENT:
	default:
		require('form.document.php');
		break;
}