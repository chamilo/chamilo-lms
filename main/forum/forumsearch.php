<?php  // $Id: document.php 16494 2008-10-10 22:07:36Z yannoo $
 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
*	These files are a complete rework of the forum. The database structure is
*	based on phpBB but all the code is rewritten. A lot of new functionalities
*	are added:
* 	- forum categories and forums can be sorted up or down, locked or made invisible
*	- consistent and integrated forum administration
* 	- forum options: 	are students allowed to edit their post?
* 						moderation of posts (approval)
* 						reply only forums (students cannot create new threads)
* 						multiple forums per group
*	- sticky messages
* 	- new view option: nested view
* 	- quoting a message
*
*	@Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*	@Copyright Ghent University
*	@Copyright Patrick Cool
*
* 	@package dokeos.forum
*/

// name of the language file that needs to be included
$language_file = array (
	'forum',
	'group'
);

// including the global dokeos file
require ('../inc/global.inc.php');

// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// including additional library scripts
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
include('forumfunction.inc.php');
include('forumconfig.inc.php');

//are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
	$origin =  Security::remove_XSS($_GET['origin']);
}

// name of the tool
$nameTools=get_lang('Forum');

// breadcrumbs

if (!empty ($_GET['gidReq'])) {
	$toolgroup = Database::escape_string($_GET['gidReq']);
	api_session_register('toolgroup');
}

if (!empty($_SESSION['toolgroup'])) {
	$_clean['toolgroup']=(int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url" => "../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	$interbreadcrumb[] = array ("url" => "viewforum.php?origin=".$origin."&amp;gidReq=".$_SESSION['toolgroup']."&amp;forum=".Security::remove_XSS($_GET['forum']),"name" => prepare4display($current_forum['forum_title']));
	$interbreadcrumb[]=array('url' => 'forumsearch.php','name' => get_lang('ForumSearch'));
} else {
	$interbreadcrumb[]=array('url' => 'index.php','name' => $nameTools);
	$interbreadcrumb[]=array('url' => 'forumsearch.php','name' => get_lang('ForumSearch'));
}

// Display the header
if ($origin=='learnpath') {
	include(api_get_path(INCLUDE_PATH).'reduced_header.inc.php');
} else {
	Display :: display_header($nameTools);
}

// Display the tool title
api_display_tool_title($nameTools);

// tool introduction

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';

Display::display_introduction_section(TOOL_FORUM,'left');

$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

// tracking
event_access_tool(TOOL_FORUM);

// forum search
forum_search();

// footer
if ($origin!='learnpath') {
	Display :: display_footer();
}
