<?php
// By Arnaud Ligot <arnaud@cblue.be>
// Based on work done for old videoconference application

// params:
// action=list cidReq=course_Code cwd=folder		result: json output

// I have about 30 minutes to write this peace of code so if somebody has more time, feel free to rewrite it...



/* See license terms in /license.txt */

/* FIX for IE cache when using https */
session_cache_limiter("none");

/*==== DEBUG ====*/
$debug=0;


if ($debug>0)
{
	// dump the request
	$v = array_keys(get_defined_vars());
	error_log(var_export($v, true),3, '/tmp/log');

	foreach (array_keys(get_defined_vars()) as $k) {
		if ($k == 'GLOBALS')
			continue;
		error_log($k, 3, '/tmp/log');
		error_log(var_export($$k, true), 3, '/tmp/log');
	}

}

/*==== INCLUDE ====*/
require_once '../inc/global.inc.php';
api_block_anonymous_users();
require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."document.lib.php");
require_once ("../newscorm/learnpath.class.php");

/*==== Variables initialisation ====*/
$action = $_REQUEST["action"]; //safe as only used in if()'s
$seek = array('/','%2F','..');
$destroy = array('','','');
$cidReq = str_replace($seek,$destroy,$_REQUEST["cidReq"]);
$cidReq = Security::remove_XSS($cidReq);

$user_id = api_get_user_id();
$coursePath = api_get_path(SYS_COURSE_PATH).$cidReq.'/document';
$_course = CourseManager::get_course_information($cidReq);
if ($_course == null) die ("problem when fetching course information");

// stupid variable initialisation for old version of DocumentManager functions.
$_course['path'] = $_course['directory'];
$_course['dbName'] = $_course['db_name'];

$is_manager = (CourseManager::get_user_in_course_status($user_id, $cidReq) == COURSEMANAGER);

if ($debug>0) error_log($coursePath, 0);

// FIXME: check security around $_REQUEST["cwd"]
$cwd = $_REQUEST["cwd"];


// treat /..
$nParent = 0; // the number of /.. into the url
while (substr($cwd, -3, 3) == "/..")
{
	// go to parent directory
	$cwd= substr($cwd, 0, -3);
	if (strlen($cwd) == 0) $cwd="/";
	$nParent++;
}
for (;$nParent >0; $nParent--){
	$cwd = (strrpos($cwd,'/')>-1 ? substr($cwd, 0, strrpos($cwd,'/')) : $cwd);
}

if (strlen($cwd) == 0) $cwd="/";

if (Security::check_abs_path($cwd,api_get_path(SYS_PATH)))
	die();


if ($action == "list")
{
	/*==== List files ====*/
	if ($debug>0) error_log("sending file list",0);
	
	// get files list
	$files = DocumentManager::get_all_document_data($_course, $cwd, 0, NULL, false);

	// adding download link to files
	foreach($files as $k=>$f) 
		if ($f['filetype'] == 'file')
			$files[$k]['download'] = api_get_path(WEB_CODE_PATH)."/document/document.php?cidReq=$cidReq&action=download&id=".urlencode($f['path']);
	print json_encode($files);
	exit;
}
?>
