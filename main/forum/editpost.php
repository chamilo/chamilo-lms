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

/**
 **************************************************************************
 *						IMPORTANT NOTICE
 * Please do not change anything is this code yet because there are still
 * some significant code that need to happen and I do not have the time to
 * merge files and test it all over again. So for the moment, please do not
 * touch the code
 * 							-- Patrick Cool <patrick.cool@UGent.be>
 **************************************************************************
 */

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array (
	'forum',
	'group'
);

// including the global dokeos file
require ('../inc/global.inc.php');
require_once('../gradebook/lib/gradebook_functions.inc.php');
require_once('../gradebook/lib/be/gradebookitem.class.php');
require_once('../gradebook/lib/be/evaluation.class.php');
require_once('../gradebook/lib/be/abstractlink.class.php');
require_once('../gradebook/lib/gradebook_functions.inc.php');
// the section (tabs)
$this_section=SECTION_COURSES;
// notice for unauthorized people.
api_protect_course_script(true);


// FCKeditor configuration
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '400';

$fck_attribute['Config']['IMUploadPath'] = 'upload/forum/';
$fck_attribute['Config']['FlashUploadPath'] = 'upload/forum/';

// including additional library scripts
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
if(!api_is_allowed_to_edit()) {
	$fck_attribute['Config']['UserStatus'] = 'student';
	$fck_attribute['ToolbarSet'] = 'Forum_Student';
}
else
{
	$fck_attribute['ToolbarSet'] = 'Forum';
}

$nameTools=get_lang('Forum');

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
include('forumconfig.inc.php');
include('forumfunction.inc.php');

// javascript
$htmlHeadXtra[] = '<script>
		
		function advanced_parameters() {
			if(document.getElementById(\'id_qualify\').style.display == \'none\') {
				document.getElementById(\'id_qualify\').style.display = \'block\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';

			} else {
				document.getElementById(\'id_qualify\').style.display = \'none\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
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
// we are getting all the information about the current forum and forum category.
// note pcool: I tried to use only one sql statement (and function) for this
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table
$current_thread=get_thread_information($_GET['thread']); // note: this has to be validated that it is an existing thread
$current_forum=get_forum_information($_GET['forum']); // note: this has to be validated that it is an existing forum.
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);
$current_post=get_post_information($_GET['post']);
/*
-----------------------------------------------------------
	Header and Breadcrumbs
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


if (!empty($_SESSION['toolgroup'])) {
	
	$_clean['toolgroup']=(int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	$interbreadcrumb[] = array("url" => "viewforum.php?origin=".$origin."&amp;gidReq=".$_SESSION['toolgroup']."&amp;forum=".Security::remove_XSS($_GET['forum']),"name" => prepare4display($current_forum['forum_title']));	
	$interbreadcrumb[] = array("url" => "#","name" => get_lang('EditPost'));
	
} else {
	$interbreadcrumb[]=array("url" => "index.php?gradebook=$gradebook","name" => $nameTools);
	$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id'],"name" => prepare4display($current_forum_category['cat_title']));
	$interbreadcrumb[]=array("url" => "viewforum.php?origin=".$origin."&amp;forum=".Security::remove_XSS($_GET['forum']),"name" => prepare4display($current_forum['forum_title']));
	$interbreadcrumb[]=array("url" => "viewthread.php?gradebook=$gradebook&amp;origin=".$origin."&amp;forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".$_GET['thread'],"name" => prepare4display($current_thread['thread_title']));
	$interbreadcrumb[]=array("url" => "#","name" => get_lang('EditPost'));
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
$table_link 			= Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
//are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
	$origin =  Security::remove_XSS($_GET['origin']);
}

if ($origin=='learnpath') {
	include(api_get_path(INCLUDE_PATH).'reduced_header.inc.php');
} else {
	Display :: display_header(null);
	//api_display_tool_title($nameTools);
}
//echo '<link href="forumstyles.css" rel="stylesheet" type="text/css" />';
/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// the user is not allowed here if
// 1. the forumcategory, forum or thread is invisible (visibility==0)
// 2. the forumcategory, forum or thread is locked (locked <>0)
// 3. if anonymous posts are not allowed
// 4. if editing of replies is not allowed
// The only exception is the course manager
// I have split this is several pieces for clarity.
//if (!api_is_allowed_to_edit() AND (($current_forum_category['visibility']==0 OR $current_forum['visibility']==0) OR ($current_forum_category['locked']<>0 OR $current_forum['locked']<>0 OR $current_thread['locked']<>0)))
if (!api_is_allowed_to_edit() AND (($current_forum_category['visibility']==0 OR $current_forum['visibility']==0))) {
	forum_not_allowed_here();
}
if (!api_is_allowed_to_edit() AND ($current_forum_category['locked']<>0 OR $current_forum['locked']<>0 OR $current_thread['locked']<>0)) {
	forum_not_allowed_here();
}
if (!$_user['user_id'] AND $current_forum['allow_anonymous']==0) {
	forum_not_allowed_here();
}
if (!api_is_allowed_to_edit() AND $current_forum['allow_edit']==0) {
	forum_not_allowed_here();
}


// action links
echo '<div class="actions">';
echo '<span style="float:right;">'.search_link().'</span>';
echo '<a href="index.php?gradebook='.$gradebook.'">'.Display::return_icon('back.png').' '.get_lang('BackToForumOverview').'</a>';
echo '<a href="viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'">'.Display::return_icon('forum.gif').' '.get_lang('BackToForum').'</a>';
echo '</div>';

/*
-----------------------------------------------------------
	Display Forum Category and the Forum information
-----------------------------------------------------------
*/
echo "<table class=\"data_table\" width='100%'>\n";
// the forum category
echo "\t<tr>\n\t\t<th align=\"left\" colspan=\"2\">";
echo '<a href="viewforum.php?&origin='.$origin.'&forum='.$current_forum['forum_id'].'" '.class_visible_invisible($current_forum['visibility']).'>'.prepare4display($current_forum['forum_title']).'</a><br />';
echo '<span class="forum_description">'.prepare4display($current_forum['forum_comment']).'</span>';echo "</th>\n";
echo "</th>\n";
echo "\t</tr>\n";
echo '</table>';

// the form for the reply
$values=show_edit_post_form($current_post, $current_thread, $current_forum, isset($_SESSION['formelements'])?$_SESSION['formelements']:'');
if (!empty($values) and isset($_POST['SubmitPost'])) {
	store_edit_post($values);

	$option_chek=isset($values['thread_qualify_gradebook'])?$values['thread_qualify_gradebook']:null;// values 1 or 0
		if ( 1== $option_chek ) {
			$id=$values['thread_id'];
			$title_gradebook=$values['calification_notebook_title'];
			$value_calification=$values['numeric_calification'];
			$weight_calification=$values['weight_calification'];
			$description="";
			$session_id=api_get_session_id();
			$link_id=is_resource_in_course_gradebook(api_get_course_id(),5,$id,$session_id);
			if ( $link_id==false ) {
				add_resource_to_course_gradebook(api_get_course_id(), 5, $id, $title_gradebook,$weight_calification,$value_calification,$description,time(),1,api_get_session_id());	
			} else {
				api_sql_query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE id='.$link_id.'');
			}
				
	}
	
}
// footer
if ($origin!='learnpath') {
	Display :: display_footer();
}