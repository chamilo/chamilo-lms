<?php
/* See license terms in /license.txt */

/* FIX for IE cache when using https */
session_cache_limiter("none");

/**
 * This script gives information to the videoconference scripts (in OpenLaszlo)
 * to use the right URL and ports for the videoconference.
 */
require_once('../newscorm/learnpath.class.php');
if($debug>0) error_log('New LP - Included learnpath',0);
require_once('../newscorm/learnpathItem.class.php');
if($debug>0) error_log('New LP - Included learnpathItem',0);
require_once('../newscorm/scorm.class.php');
if($debug>0) error_log('New LP - Included scorm',0);
require_once('../newscorm/scormItem.class.php');
if($debug>0) error_log('New LP - Included scormItem',0);
require_once('../newscorm/aicc.class.php');
if($debug>0) error_log('New LP - Included aicc',0);
require_once('../newscorm/aiccItem.class.php');
if($debug>0) error_log('New LP - Included aiccItem',0);

require("../../main/inc/global.inc.php");
require_once('get_translation.lib.php');
api_block_anonymous_users();

//$confkey = "0123456789abcdef0123456789abcdef";
$confkey = api_get_setting('service_visio','visio_pass');
$challenge = api_generate_password(32); //generate a 32 characters-long challenge key

require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
printf ('<?xml version="1.0" encoding="UTF-8" ?>');

printf('<dokeosobject>');

printf('<courseobject>');
foreach ($_SESSION['_course'] as $key => $val)
		printf('<%s>%s</%s>',$key,api_utf8_encode($val),$key);
printf('</courseobject>');

printf('<userobject>');
foreach ($_SESSION['_user'] as $key => $val)
	if ($key != "auth_source")
	{
		if (( $key == "lastName" || $key == "firstName" ) && strlen($val) == 0)
			$val = get_lang('Unknown');
		printf('<%s>%s</%s>',$key,api_utf8_encode($val),$key);
	}

printf('<sid>%s</sid>', session_id());
$isadmin =((CourseManager::get_user_in_course_status($_SESSION['_user']['user_id'], $_SESSION['_course']['sysCode']) == COURSEMANAGER)||
		api_is_platform_admin() ||
		api_is_course_tutor() ||
		api_is_course_admin() ||
		api_is_course_coach() ? "true" : "false");
printf('<key>%s</key>', md5($confkey.$challenge));
printf('<challenge>%s</challenge>', $challenge);
printf('<isUploadAllowed>%s</isUploadAllowed>', $isadmin);
printf('<canStartModerator>%s</canStartModerator>',($isadmin=='true' || $_SESSION["roomType"] == "conference")?'true':'false');
printf('<mustStartModerator>%s</mustStartModerator>',($isadmin=='true' || $_SESSION["roomType"] == "conference")?'true':'false');
printf('</userobject>');

printf('<config>');
printf('<host>'.api_get_setting('service_visio','visio_host').'</host>');
printf('<port>'.api_get_setting('service_visio','visio_port').'</port>');
printf('</config>');

$path = preg_replace('/^([^:]*:\/\/)/','',api_get_path(WEB_PATH));
$path = str_replace('/','_',$path);
printf('<roomConfig>');
printf('<portal>%s</portal>', $path);
printf('<roomType>%s</roomType>', $_SESSION['roomType']); // fixme remove +
printf('</roomConfig>');

printf('<recorderparams>');

if(isset($_SESSION['oLP']))
{
	switch ($_SESSION['whereami'])
	{
		case 'lp/build' :
			$student_view = 'false';
			break;
		default :
			$student_view = 'true';
		break;
	}
	$document_id = $_SESSION['oLP']->current;
}

printf('<studentview>%s</studentview>',$student_view);
printf('<documentid>%s</documentid>',$document_id);
printf('</recorderparams>');
printf('<languageobject>');
printf(get_language_file_as_xml($language_interface));
printf('</languageobject>');
printf('</dokeosobject>');
?>
