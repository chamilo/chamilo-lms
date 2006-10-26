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
$langFile = 'forum';
require ('../inc/global.inc.php');
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
$current_forum=get_forum_information($_GET['forum']); // note: this has to be validated that it is an existing forum. 
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);



/*
-----------------------------------------------------------
	Header and Breadcrumbs
-----------------------------------------------------------
*/
$interbreadcrumb[]=array("url" => "index.php","name" => $nameTools);
$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id'],"name" => prepare4display($current_forum_category['cat_title']));
$interbreadcrumb[]=array("url" => "viewforum.php?forum=".$_GET['forum'],"name" => prepare4display($current_forum['forum_title']));

Display :: display_header();
api_display_tool_title($nameTools);
//echo '<link href="forumstyles.css" rel="stylesheet" type="text/css" />';

/*
-----------------------------------------------------------
	Actions
-----------------------------------------------------------
*/
// Change visibility of a forum or a forum category
if (($_GET['action']=='invisible' OR $_GET['action']=='visible') AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit())
{
	$message=change_visibility($_GET['content'], $_GET['id'],$_GET['action']);// note: this has to be cleaned first
}
if (($_GET['action']=='lock' OR $_GET['action']=='unlock') AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit())
{
	$message=change_lock_status($_GET['content'], $_GET['id'],$_GET['action']);// note: this has to be cleaned first
}
if ($_GET['action']=='delete'  AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit())
{
	$message=delete_forum_forumcategory_thread($_GET['content'],$_GET['id']); // note: this has to be cleaned first
}
if ($_GET['action']=='move' and isset($_GET['thread']))
{
	$message=move_thread_form();
}


/*
-----------------------------------------------------------
	Is the user allowed here? 
-----------------------------------------------------------
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here. 
if (!api_is_allowed_to_edit() AND ($current_forum_category['visibility']==0 OR $current_forum['visibility']==0))
{
	forum_not_allowed_here();
}


/*
-----------------------------------------------------------
	Display the action messages
-----------------------------------------------------------
*/
if (isset($message))
{
	Display :: display_normal_message($message);
}


/*
-----------------------------------------------------------
	Action Links
-----------------------------------------------------------
*/
// The link should appear when
// 1. the course admin is here
// 2. the course member is here and new threads are allowed
// 3. a visitor is here and new threads AND allowed AND  anonymous posts are allowed
if (api_is_allowed_to_edit() OR ($current_forum['allow_new_threads']==1 AND isset($_uid)) OR ($current_forum['allow_new_threads']==1 AND !isset($_uid) AND $current_forum['allow_anonymous']==1))
{
	echo '<a href="newthread.php?forum='.$_GET['forum'].'">'.get_lang('NewTopic').'</a>';
}

/*
-----------------------------------------------------------
					Display 
-----------------------------------------------------------
*/
echo "<table width='100%'>\n";

// the forum category
echo "\t<tr class=\"forum_category\">\n\t\t<td colspan=\"7\">";
echo '<a href="index.php" '.class_visible_invisible($current_forum_category['visibility']).'>'.prepare4display($current_forum_category['cat_title']).'</a><br />';
echo '<span>'.prepare4display($current_forum_category['cat_comment']).'</span>';
echo "</td>\n";
echo "\t</tr>\n";

// the forum 
echo "\t<tr class=\"forum_header\">\n";
echo "\t\t<td colspan=\"7\">".prepare4display($current_forum['forum_title'])."<br />";
echo '<span>'.prepare4display($current_forum['forum_comment']).'</span>';
echo "</td>\n";
echo "\t</tr>\n";

// The column headers (to do: make this sortable)
echo "\t<tr class=\"forum_threadheader\">\n";
echo "\t\t<td></td>\n";
echo "\t\t<td>".get_lang('Title')."</td>\n";
echo "\t\t<td>".get_lang('Replies')."</td>\n";
echo "\t\t<td>".get_lang('Author')."</td>\n";
echo "\t\t<td>".get_lang('Views')."</td>\n";
echo "\t\t<td>".get_lang('LastPost')."</td>\n";
if (api_is_allowed_to_edit())
{
	echo "\t\t<td>".get_lang('Actions')."</td>\n";
}
echo "\t</tr>\n";

// getting al the threads
$threads=get_threads($_GET['forum']); // note: this has to be cleaned first

$whatsnew_post_info=$_SESSION['whatsnew_post_info'];

foreach ($threads as $row)
{
	// thread who have no replies yet and the only post is invisible should not be displayed to students. 
	if (api_is_allowed_to_edit() OR  !($row['thread_replies']=='0' AND $row['visible']=='0'))
	{ 
		echo "\t<tr>\n";
		echo "\t\t<td>";
		if (is_array($whatsnew_post_info[$_GET['forum']][$row['thread_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$row['thread_id']]))
		{
			echo icon('../img/forumthreadnew.gif');	
		}
		else 
		{
			echo icon('../img/forumthread.gif');	
		}
	
		if ($row['thread_sticky']==1)
		{
			echo icon('../img/exclamation.gif');
		}
		echo "</td>\n";
		echo "\t\t<td><a href=\"viewthread.php?forum=".$_GET['forum']."&amp;thread=".$row['thread_id']."\" ".class_visible_invisible($row['visibility']).">".prepare4display($row['thread_title'])."</a></td>\n";
		echo "\t\t<td>".$row['thread_replies']."</td>\n";
		if ($row['user_id']=='0')
		{
			$name=prepare4display($row['thread_poster_name']);
		}
		else 
		{
			$name=$row['firstname'].' '.$row['lastname'];
		}
		echo "\t\t<td>".display_user_link($row['user_id'], $name)."</td>\n";
		echo "\t\t<td>".$row['thread_views']."</td>\n";
		if ($row['last_poster_user_id']=='0')
		{
			$name=$row['poster_name'];
		}
		else 
		{
			$name=$row['last_poster_firstname'].' '.$row['last_poster_lastname'];
		}
		// if the last post is invisible and it is not the teacher who is looking then we have to find the last visible post of the thread
		if ($row['visible']=='1' OR api_is_allowed_to_edit())
		{		
			$last_post=$row['thread_date']." ".get_lang('By').' '.display_user_link($row['last_poster_user_id'], $name);
		}
		else 
		{
			$last_post_sql="SELECT post.*, user.firstname, user.lastname FROM $table_posts post, $table_users user WHERE post.poster_id=user.user_id AND visible='1' AND thread_id='".$row['thread_id']."' ORDER BY post_id DESC";
			$last_post_result=api_sql_query($last_post_sql, __LINE__, __FILE__);
			$last_post_row=mysql_fetch_array($last_post_result);
			$name=$last_post_row['firstname'].' '.$last_post_row['lastname'];
			$last_post=$last_post_row['post_date']." ".get_lang('By').' '.display_user_link($last_post_row['poster_id'], $name);
		}
		echo "\t\t<td>".$last_post."</td>\n";
		if (api_is_allowed_to_edit())
		{
			echo "\t\t<td>";
			echo "<a href=\"".$_SERVER['PHP_SELF']."?forum=".$_GET['forum']."&amp;action=delete&amp;content=thread&amp;id=".$row['thread_id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeleteCompleteThread")))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
			display_visible_invisible_icon('thread', $row['thread_id'], $row['visibility'], array("forum"=>$_GET['forum']));
			display_lock_unlock_icon('thread',$row['thread_id'], $row['locked'], array("forum"=>$_GET['forum']));
			echo "<a href=\"viewforum.php?forum=".$_GET['forum']."&amp;action=move&amp;thread=".$row['thread_id']."\">".icon('../img/forummovepost.gif',get_lang('Edit'))."</a>";
			echo "</td>\n";
		}
		echo "\t</tr>\n";
	}
}

echo "</table>";

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
?>



