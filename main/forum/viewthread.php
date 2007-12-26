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

// including the global dokeos file
require ('../inc/global.inc.php');

// the section (tabs)
$this_section=SECTION_COURSES;

// including additional library scripts
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
//require_once (api_get_path(LIBRARY_PATH).'resourcelinker.lib.php');
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
if(isset($_GET['origin']))
{
	$origin =  Security::remove_XSS($_GET['origin']);
}


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

$whatsnew_post_info=$_SESSION['whatsnew_post_info'];

/*
-----------------------------------------------------------
	Header and Breadcrumbs
-----------------------------------------------------------
*/
if($origin=='learnpath')
{
	include(api_get_path(INCLUDE_PATH).'reduced_header.inc.php');
} else
{

	$interbreadcrumb[]=array("url" => "index.php","name" => $nameTools);
	$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id'],"name" => prepare4display($current_forum_category['cat_title']));
	$interbreadcrumb[]=array("url" => "viewforum.php?forum=".Security::remove_XSS($_GET['forum']),"name" => prepare4display($current_forum['forum_title']));
	if ($message<>'PostDeletedSpecial')
	{
		$interbreadcrumb[]=array("url" => "viewthread.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread']),"name" => prepare4display($current_thread['thread_title']));
	}
	// the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
	Display :: display_header('');
	api_display_tool_title($nameTools);

}
//echo '<link href="forumstyles.css" rel="stylesheet" type="text/css" />';

/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit() AND ($current_forum['visibility']==0 OR $current_thread['visibility']==0))
{
	forum_not_allowed_here();
}

/*
-----------------------------------------------------------
	Actions
-----------------------------------------------------------
*/
if ($_GET['action']=='delete' AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit())
{
	$message=delete_post($_GET['id']); // note: this has to be cleaned first
}
if (($_GET['action']=='invisible' OR $_GET['action']=='visible') AND isset($_GET['id']) AND api_is_allowed_to_edit())
{
	$message=approve_post($_GET['id'],$_GET['action']); // note: this has to be cleaned first
}
if ($_GET['action']=='move' and isset($_GET['post']))
{
	$message=move_post_form();
}

/*
-----------------------------------------------------------
	Display the action messages
-----------------------------------------------------------
*/
if (isset($message))
{
	Display :: display_confirmation_message(get_lang($message));
}


if ($message<>'PostDeletedSpecial') // in this case the first and only post of the thread is removed
{

	// this increases the number of times the thread has been viewed
	increase_thread_view($_GET['thread']);



	/*
	-----------------------------------------------------------
		Action Links
	-----------------------------------------------------------
	*/
	echo '<div style="float:right;">';
	$my_url = '<a href="viewthread.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']);
	echo $my_url.'&amp;view=flat&origin='.$origin.'">'.get_lang('FlatView').'</a> | ';
	echo $my_url.'&amp;view=threaded&origin='.$origin.'">'.get_lang('ThreadedView').'</a> | ';
	echo $my_url.'&amp;view=nested&origin='.$origin.'">'.get_lang('NestedView').'</a>';
	$my_url = null;
	echo '</div>';
	// the reply to thread link should only appear when the forum_category is not locked AND the forum is not locked AND the thread is not locked.
	// if one of the three levels is locked then the link should not be displayed
	if ($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit())
	{
		// The link should only appear when the user is logged in or when anonymous posts are allowed.
		if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id']))
		{
			echo '<a href="reply.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;action=replythread&origin='.$origin.'">'.get_lang('ReplyToThread').'</a>';
		}
	}
	// note: this is to prevent that some browsers display the links over the table (FF does it but Opera doesn't)
	echo '&nbsp;';


	/*
	-----------------------------------------------------------
		Display Forum Category and the Forum information
	-----------------------------------------------------------
	*/

	if (!$_SESSION['view'])
	{
		$viewmode=$current_forum['default_view'];
	}
	else
	{
		$viewmode=$_SESSION['view'];
	}

	$viewmode_whitelist=array('flat', 'threaded', 'nested');
	if (isset($_GET['view']) and in_array($_GET['view'],$viewmode_whitelist))
	{
		$viewmode=$_GET['view'];
		$_SESSION['view']=$viewmode;
	}
	if(empty($viewmode))
	{
		$viewmode = 'flat';
	}

	/*
	-----------------------------------------------------------
		Display Forum Category and the Forum information
	-----------------------------------------------------------
	*/
	// we are getting all the information about the current forum and forum category.
	// note pcool: I tried to use only one sql statement (and function) for this
	// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table
	echo "<table class=\"data_table\" width='100%'>\n";

	// the forum category
	if($origin!='learnpath')
	{
		echo "\t<tr>\n\t\t<th style=\"padding-left:5px;\" align=\"left\" colspan=\"6\">";
		echo '<a href="index.php?'.api_get_cidreq().'" '.class_visible_invisible($current_forum_category['visibility']).'>'.prepare4display($current_forum_category['cat_title']).'</a><br />';
		echo '<span>'.prepare4display($current_forum_category['cat_comment']).'</span>';
		echo "</th>\n";
		echo "\t</tr>\n";
	}

	// the forum
	echo "\t<tr class=\"forum_header\">\n";
	echo "\t\t<td><a href=\"viewforum.php?".api_get_cidreq()."&forum=".$current_forum['forum_id']."\" ".class_visible_invisible($current_forum['visibility']).">".prepare4display($current_forum['forum_title'])."</a><br />";
	echo '<span>'.prepare4display($current_forum['forum_comment']).'</span>';
	echo "</td>\n";
	echo "\t</tr>\n";

	// the thread
	echo "\t<tr class=\"forum_thread\">\n";
	echo "\t\t<td><span ".class_visible_invisible($current_thread['visibility']).">".prepare4display($current_thread['thread_title'])."</span><br />";
	echo "</td>\n";
	echo "\t</tr>\n";
	echo "</table>";

	echo '<br />';

	switch ($viewmode)
	{
		case 'flat':
			include_once('viewthread_flat.inc.php');
			break;
		case 'threaded':
			include_once('viewthread_threaded.inc.php');
			break;
		case 'nested':
			include_once('viewthread_nested.inc.php');
			break;
		default:
			include_once('viewthread_flat.inc.php');
			break;
	}
} // if ($message<>'PostDeletedSpecial') // in this case the first and only post of the thread is removed



/*
==============================================================================
		FOOTER
==============================================================================
*/
if($origin!='learnpath')
	Display :: display_footer();
?>