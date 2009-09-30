<?php

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
$language_file = array('forum','document');

// including the global dokeos file
require_once '../inc/global.inc.php';
require_once '../gradebook/lib/gradebook_functions.inc.php';
// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// including additional library scripts
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
$nameTools=get_lang('Forum');


/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
include('forumconfig.inc.php');
include('forumfunction.inc.php');

//are we in a lp ?
$origin = '';
if(isset($_GET['origin'])) {
	$origin =  Security::remove_XSS($_GET['origin']);
}

// javascript
$htmlHeadXtra[] = '<script>

		function advanced_parameters() {
				if(document.getElementById(\'id_qualify\').style.display == \'none\') {
				document.getElementById(\'id_qualify\').style.display = \'block\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';

			} else {
				document.getElementById(\'id_qualify\').style.display = \'none\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
			}
		}
</script>';
/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/
/*
-----------------------------------------------------------
	Retrieving forum and forum categorie information
-----------------------------------------------------------
*/
$current_forum=get_forum_information($_GET['forum']); // note: this has to be validated that it is an existing forum.
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);

/*
-----------------------------------------------------------
	Breadcrumbs
-----------------------------------------------------------
*/

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

if (!empty($_GET['gidReq'])) {
	$toolgroup = Database::escape_string($_GET['gidReq']);
	api_session_register('toolgroup');
}

if (!empty($_SESSION['toolgroup'])) {

	$_clean['toolgroup']=(int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	$interbreadcrumb[]=array("url" => "viewforum.php?origin=".$origin."&amp;gidReq=".$_SESSION['toolgroup']."&forum=".Security::remove_XSS($_GET['forum']),"name" => $current_forum['forum_title']);
	$interbreadcrumb[]=array("url" => "newthread.php?origin=".$origin."&forum=".Security::remove_XSS($_GET['forum']),"name" => get_lang('NewTopic'));
} else {
	$interbreadcrumb[]=array("url" => "index.php?gradebook=$gradebook","name" => $nameTools);
	$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id'],"name" => $current_forum_category['cat_title']);
	$interbreadcrumb[]=array("url" => "viewforum.php?origin=".$origin."&forum=".Security::remove_XSS($_GET['forum']),"name" => $current_forum['forum_title']);
	$interbreadcrumb[]=array("url" => "newthread.php?origin=".$origin."&forum=".Security::remove_XSS($_GET['forum']),"name" => get_lang('NewTopic'));
}
/*
-----------------------------------------------------------
	Resource Linker
-----------------------------------------------------------
*/
if (isset($_POST['add_resources']) AND $_POST['add_resources']==get_lang('Resources')) {
	$_SESSION['formelements']=$_POST;
	$_SESSION['origin']=$_SERVER['REQUEST_URI'];
	$_SESSION['breadcrumbs']=$interbreadcrumb;
	header("Location: ../resourcelinker/resourcelinker.php");
}

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
if($origin=='learnpath') {
	include(api_get_path(INCLUDE_PATH).'reduced_header.inc.php');
} else {
	Display :: display_header(null);
	//api_display_tool_title($nameTools);
}
/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// the user is not allowed here if:
// 1. the forumcategory or forum is invisible (visibility==0) and the user is not a course manager
// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
// 3. new threads are not allowed and the user is not a course manager
// 4. anonymous posts are not allowed and the user is not logged in
// I have split this is several pieces for clarity.

if (!api_is_allowed_to_edit(false,true) && (($current_forum_category['visibility']==0 || $current_forum['visibility']==0))) {
	forum_not_allowed_here();
}
// 2. the forumcategory or forum is locked (locked <>0) and the user is not a course manager
if (!api_is_allowed_to_edit(false,true) AND ($current_forum_category['locked']<>0 OR $current_forum['locked']<>0)) {
	forum_not_allowed_here();
}
// 3. new threads are not allowed and the user is not a course manager
if (!api_is_allowed_to_edit(false,true) AND $current_forum['allow_new_threads']<>1) {
	forum_not_allowed_here();
}
// 4. anonymous posts are not allowed and the user is not logged in
if (!$_user['user_id']  AND $current_forum['allow_anonymous']<>1) {
	forum_not_allowed_here();
}

/*
-----------------------------------------------------------
	Display forms / Feedback Messages
-----------------------------------------------------------
*/
handle_forum_and_forumcategories();
// action links
echo '<div class="actions">';
echo '<span style="float:right;">'.search_link().'</span>';
echo '<a href="index.php?gradebook='.$gradebook.'">'.Display::return_icon('back.png',get_lang('BackToForumOverview')).' '.get_lang('BackToForumOverview').'</a>';
echo '<a href="viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'">'.Display::return_icon('forum.gif',get_lang('BackToForum')).' '.get_lang('BackToForum').'</a>';
echo '</div>';

/*
-----------------------------------------------------------
	Display Forum Category and the Forum information
-----------------------------------------------------------
*/
echo "<table class=\"data_table\" width='100%'>\n";

if ($origin != 'learnpath') {
	echo "\t<tr>\n\t\t<th align=\"left\"  colspan=\"2\">";

	echo '<span class="forum_title">'.prepare4display($current_forum['forum_title']).'</span>';

	if (!empty ($current_forum['forum_comment'])) {
		echo '<br><span class="forum_description">'.prepare4display($current_forum['forum_comment']).'</span>';
	}

	if (!empty ($current_forum_category['cat_title'])) {
		echo '<br /><span class="forum_low_description">'.prepare4display($current_forum_category['cat_title'])."</span><br />";
	}
	echo "</th>\n";
	echo "\t</tr>\n";
}
echo '</table>';

$values=show_add_post_form('newthread','', isset($_SESSION['formelements'])?$_SESSION['formelements']:null);

if (!empty($values) and isset($values['SubmitPost'])) {
	//add new thread in table forum_thread
	store_thread($values);
}

/*
==============================================================================
		FOOTER
==============================================================================
*/
if ($origin!='learnpath') {
	Display :: display_footer();
}