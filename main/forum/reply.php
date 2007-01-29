<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
	Copyright (c) 2006 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
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


/*
-----------------------------------------------------------
	Language Initialisation
-----------------------------------------------------------
*/
// name of the language file that needs to be included 
$language_file = 'forum';
require ('../inc/global.inc.php');

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Middle';
$fck_attribute['Config']['IMUploadPath'] = 'upload/forum/';
$fck_attribute['Config']['FlashUploadPath'] = 'upload/forum/';
if(!api_is_allowed_to_edit()) $fck_attribute['Config']['UserStatus'] = 'student';

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
$current_forum=get_forum_information($current_thread['forum_id']); // note: this has to be validated that it is an existing forum. 
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);

/*
-----------------------------------------------------------
	Breadcrumbs
-----------------------------------------------------------
*/
$interbreadcrumb[]=array("url" => "index.php","name" => $nameTools);
$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id'],"name" => $current_forum_category['cat_title']);
$interbreadcrumb[]=array("url" => "viewforum.php?forum=".$_GET['forum'],"name" => $current_forum['forum_title']);
$interbreadcrumb[]=array("url" => "viewthread.php?forum=".$_GET['forum']."&amp;thread=".$_GET['thread'],"name" => $current_thread['thread_title']);
$interbreadcrumb[]=array("url" => "reply.php?forum=".$_GET['forum']."&amp;thread=".$_GET['thread'],"name" => get_lang('Reply'));

/*
-----------------------------------------------------------
	Resource Linker
-----------------------------------------------------------
*/
if (isset($_POST['add_resources']) AND $_POST['add_resources']==get_lang('Resources'))
{
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
Display :: display_header();
api_display_tool_title($nameTools);
//echo '<link href="forumstyles.css" rel="stylesheet" type="text/css" />';

/*
-----------------------------------------------------------
	Is the user allowed here? 
-----------------------------------------------------------
*/
// the user is not allowed here if
// 1. the forumcategory, forum or thread is invisible (visibility==0
// 2. the forumcategory, forum or thread is locked (locked <>0)
// 3. if anonymous posts are not allowed
// The only exception is the course manager
// I have split this is several pieces for clarity.  
//if (!api_is_allowed_to_edit() AND (($current_forum_category['visibility']==0 OR $current_forum['visibility']==0) OR ($current_forum_category['locked']<>0 OR $current_forum['locked']<>0 OR $current_thread['locked']<>0)))
if (!api_is_allowed_to_edit() AND (($current_forum_category['visibility']==0 OR $current_forum['visibility']==0)))
{
	forum_not_allowed_here();
}
if (!api_is_allowed_to_edit() AND ($current_forum_category['locked']<>0 OR $current_forum['locked']<>0 OR $current_thread['locked']<>0))
{
	forum_not_allowed_here();
}
if (!$_user['user_id'] AND $current_forum['allow_anonymous']==0)
{
	forum_not_allowed_here();
}


/*
-----------------------------------------------------------
	Display forms / Feedback Messages
-----------------------------------------------------------
*/
//handle_forum_and_forumcategories();

/*
-----------------------------------------------------------
	Display Forum Category and the Forum information
-----------------------------------------------------------
*/
echo "<table class=\"data_table\" width='100%'>\n";

// the forum category
echo "\t<tr>\n\t\t<th style=\"padding-left:5px;\" align=\"left\" colspan=\"2\">";
echo '<a href="index.php" '.class_visible_invisible($current_forum_category['visibility']).'>'.$current_forum_category['cat_title'].'</a><br />';
echo '<span>'.$current_forum_category['cat_comment'].'</span>';
echo "</th>\n";
echo "\t</tr>\n";

// the forum 
echo "\t<tr class=\"forum_header\">\n";
echo "\t\t<td colspan=\"2\">";
echo '<a href="viewforum.php?forum='.$current_forum['forum_id'].'" '.class_visible_invisible($current_forum['visibility']).'>'.$current_forum['forum_title'].'</a><br />';
echo '<span>'.$current_forum['forum_comment'].'</span>';
echo "</td>\n";
echo "\t</tr>\n";

echo '</table>';

// the form for the reply
$values=show_add_post_form($_GET['action'], $_GET['post'], $_SESSION['formelements']); // note: this has to be cleaned first
if (!empty($values) AND $_POST['SubmitPost'])
{
	store_reply($values);
}

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
?>



