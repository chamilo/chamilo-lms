<?php // $Id: chat.php 10141 2006-11-23 05:12:52Z gmludo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

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
==============================================================================
*	Frameset of the Chat tool
*
*	@author Ludovic Gasc
*	@package dokeos.chat
*	@todo improve multi-language support
*	@todo stock each chat text file into each course
*	@todo remove duplicate libs (XAJAX, PEAR...)
==============================================================================
*/
/*
-----------------------------------------------------------
	Init section
-----------------------------------------------------------
*/
$langFile='chat';
include('../inc/global.inc.php');

$this_section=SECTION_COURSES;

$nameTools=get_lang('Chat');
if ($_GET["origin"] != 'whoisonline')
{
	api_protect_course_script();
}
else
{
	$origin = $_SESSION['origin'];
	$target = $_SESSION['target'];
	$_SESSION['origin']=$_GET["origin"];
	$_SESSION['target']=$_GET["target"];
}

/*
-----------------------------------------------------------
	Tracking
-----------------------------------------------------------
*/
include('../inc/lib/events.lib.inc.php');
event_access_tool(TOOL_CHAT);

/*
-----------------------------------------------------------
	Main section
-----------------------------------------------------------
*/

require_once './phpfreechat/src/phpfreechat.class.php';


// initialisation of phpfreechat
$courseinfo = api_get_course_info();
//print_r($courseinfo);
$params['serverid'] = (string) $courseinfo['id']; // used to identify the chat
$params['nick'] = $_SESSION['_user']['firstName'].' '.$_SESSION['_user']['lastName'];
$params['title'] = $courseinfo['name'];
//$params['channel'] = $courseinfo['name'];
$params['frozen_nick'] = true;
$params['max_nick_len'] = 100;
$params['height'] = "300px";
$params['refresh_delay'] = 2000;
$params['xajaxpath'] = api_get_path(LIBRARY_PATH).'xajax/';
echo $params['xajaxpath'];
// $params['debug'] = true;
// $params['debugxajax'] = true;

$params['language'] = Database::get_language_isocode($courseinfo['language']).'_'.strtoupper(Database::get_language_isocode($courseinfo['language']));
// In phpfreechat, some translations depends of the country

$chat = new phpFreeChat($params);

$htmlHeadXtra[] = $chat->printJavascript();
$htmlHeadXtra[] = $chat->printStyle();

	Display::display_header($nameTools,"Chat");

	$chat->printChat();

	Display::display_footer();
?>