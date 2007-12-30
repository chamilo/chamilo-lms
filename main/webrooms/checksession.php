<?php
/**
 * Created on 08.11.2006
 * This script gives information to the videoconference scripts (in OpenLaszlo)
 * to use the right URL and ports for the videoconference.
 */
require_once('../newscorm/learnpath.class.php');
require_once('../newscorm/learnpathItem.class.php');
require_once('../newscorm/scorm.class.php');
require_once('../newscorm/scormItem.class.php');
require_once('../newscorm/aicc.class.php');
require_once('../newscorm/aiccItem.class.php');
require_once('get_translation.lib.php');

include("../../main/inc/global.inc.php");
api_block_anonymous_users();

require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
printf ('<?xml version="1.0" encoding="UTF-8" ?>');

printf('<dokeosobject>');

printf('<courseobject>');
if(count($_SESSION['course'])>0)
{
	foreach ($_SESSION['_course'] as $key => $val)
	{	
		printf('<%s>%s</%s>',$key,utf8_encode($val),$key);
	}
}
printf('</courseobject>');

printf('<userobject>');
if(count($_SESSION['_user'])>0)
{
	foreach ($_SESSION['_user'] as $key => $val) 
	{
		if ($key != "auth_source") 
		{
			printf('<%s>%s</%s>',$key,utf8_encode($val),$key);
		}
	}
}
printf('<sid>%s</sid>', session_id());
printf('<isUploadAllowed>%s</isUploadAllowed>', (CourseManager::get_user_in_course_status($_SESSION['_user']['user_id'], $_SESSION['_course']['sysCode']) == COURSEMANAGER) ? "true" : "false");
printf('</userobject>');

printf('<config>');
printf('<rmpthostlocal>'.api_get_setting('service_visio','visio_rtmp_host_local').'</rmpthostlocal>');
printf('<iswebrtmp>'.api_get_setting('service_visio','visio_is_web_rtmp').'</iswebrtmp>');
printf('<rtmpport>'.api_get_setting('service_visio','visio_rtmp_port').'</rtmpport>');
printf('<rtmpTunnelport>'.api_get_setting('service_visio','visio_rtmp_tunnel_port').'</rtmpTunnelport>');
printf('</config>');


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

printf('  <studentview>%s</studentview>',$student_view);
printf('  <documentid>%s</documentid>',$document_id);
printf('</recorderparams>');
printf('<languageobject>');
printf(get_language_file_as_xml($language_interface));
printf('</languageobject>');
printf('</dokeosobject>');
?>