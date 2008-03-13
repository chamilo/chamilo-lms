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
	include('../inc/global.inc.php');
	$this_section=SECTION_COURSES;
	/* ------------	ACCESS RIGHTS ------------ */
	// notice for unauthorized people.
	api_protect_course_script(true);
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

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Middle';
$fck_attribute['Config']['IMUploadPath'] = 'upload/forum/';
$fck_attribute['Config']['FlashUploadPath'] = 'upload/forum/';
if(!api_is_allowed_to_edit())
{
	$fck_attribute['Config']['UserStatus'] = 'student';
}

/*
-----------------------------------------------------------
	Header and Breadcrumbs
-----------------------------------------------------------
*/
$current_forum_category=get_forum_categories($_GET['forumcategory']);
$interbreadcrumb[]=array("url" => "index.php","name" => $nameTools);
$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id'],"name" => prepare4display($current_forum_category['cat_title']));

Display :: display_header();
api_display_tool_title($nameTools);

/*
------------------------------------------------------------------------------------------------------
	ACTIONS
------------------------------------------------------------------------------------------------------
*/
$whatsnew_post_info=$_SESSION['whatsnew_post_info'];

/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit() AND $current_forum_category['visibility']==0)
{
	forum_not_allowed_here();
}

/*
------------------------------------------------------------------------------------------------------
	ACTIONS
------------------------------------------------------------------------------------------------------
*/
if (api_is_allowed_to_edit())
{
	handle_forum_and_forumcategories();
}

/*
------------------------------------------------------------------------------------------------------
	RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
------------------------------------------------------------------------------------------------------
note: we do this here just after het handling of the actions to be sure that we already incorporate the
latest changes
*/
// Step 1: We store all the forum categories in an array $forum_categories
$forum_categories=array();
$forum_category=get_forum_categories($_GET['forumcategory']);

// step 2: we find all the forums
$forum_list=array();
$forum_list=get_forums();
/*
------------------------------------------------------------------------------------------------------
	RETRIEVING ALL GROUPS OF THE USER
------------------------------------------------------------------------------------------------------
*/
$groups_of_user=array();
$groups_of_user=GroupManager::get_group_ids($_course['dbName'], $_user['user_id']);
//my_print_r($groups_of_user);



/*
-----------------------------------------------------------
	Action Links
-----------------------------------------------------------
*/
if (api_is_allowed_to_edit())
{
	//echo '<a href="'.api_get_self().'?forumcategory='.$_GET['forumcategory'].'&amp;action=add&amp;content=forumcategory">'.get_lang('AddForumCategory').'</a> | ';
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&forumcategory='.Security::remove_XSS($_GET['forumcategory']).'&amp;action=add&amp;content=forum">'.Display::return_icon('forum_new.gif').' '.get_lang('AddForum').'</a>';
}

/*
-----------------------------------------------------------
	Display Forum Categories and the Forums in it
-----------------------------------------------------------
*/
echo "<table class=\"data_table\" width='100%'>\n";
echo "\t<tr>\n\t\t<th style=\"padding-left:5px;\" align=\"left\" colspan=\"5\">";
echo '<a href="#" '.class_visible_invisible($forum_category['visibility']).'>'.prepare4display($forum_category['cat_title']).'</a><br />';
echo '<span>'.prepare4display($forum_category['cat_comment']).'</span>';
echo "</th>\n";
if (api_is_allowed_to_edit())
{
	echo "\t\t<th>";
	echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=edit&amp;content=forumcategory&amp;id=".$forum_category['cat_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
	echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=delete&amp;content=forumcategory&amp;amp;id=".$forum_category['cat_id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeleteForumCategory"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
	display_visible_invisible_icon('forumcategory', $forum_category['cat_id'], $forum_category['visibility'], array("forumcategory"=>$_GET['forumcategory']));
	display_lock_unlock_icon('forumcategory',$forum_category['cat_id'], $forum_category['locked'], array("forumcategory"=>$_GET['forumcategory']));
	display_up_down_icon('forumcategory',$forum_category['cat_id'], $forum_categories_list);
	echo "</th>\n";
}
echo "\t</tr>\n";

// step 3: the interim headers (for the forum)
echo "\t<tr class=\"forum_header\">\n";
echo "\t\t<td colspan='2'>".get_lang('Forum')."</td>\n";
echo "\t\t<td>".get_lang('Topics')."</td>\n";
echo "\t\t<td>".get_lang('Posts')."</td>\n";
echo "\t\t<td>".get_lang('LastPosts')."</td>\n";
if (api_is_allowed_to_edit())
{
	echo "\t\t<td>".get_lang('Actions')."</td>\n";
}
echo "\t</tr>\n";

// the forums in this category
$forums_in_category=get_forums_in_category($forum_category['cat_id']);

// step 4: we display all the forums in this category.
$forum_count=0;
foreach ($forum_list as $key=>$forum)
{
	if ($forum['forum_category']==$forum_category['cat_id'])
	{
		// the forum has to be showed if
		// 1.v it is a not a group forum (teacher and student)
		// 2.v it is a group forum and it is public (teacher and student)
		// 3. it is a group forum and it is private (always for teachers only if the user is member of the forum
		// if the forum is private and it is a group forum and the user is not a member of the group forum then it cannot be displayed
		//if (!($forum['forum_group_public_private']=='private' AND !is_null($forum['forum_of_group']) AND !in_array($forum['forum_of_group'], $groups_of_user)))
		//{
		$show_forum=false;
		// SHOULD WE SHOW THIS PARTICULAR FORUM
		// you are teacher => show forum

		if (api_is_allowed_to_edit())
		{
			//echo 'teacher';
			$show_forum=true;
		}
		// you are not a teacher
		else
		{
			//echo 'student';
			// it is not a group forum => show forum (invisible forums are already left out see get_forums function)
			if ($forum['forum_of_group']=='0')
			{
				//echo '-gewoon forum';
				$show_forum=true;
			}
			// it is a group forum
			else
			{
				//echo '-groepsforum';
				// it is a group forum but it is public => show
				if ($forum['forum_group_public_private']=='public')
				{
					$show_forum=true;
					//echo '-publiek';
				}
				// it is a group forum and it is private
				else
				{
					//echo '-prive';
					// it is a group forum and it is private but the user is member of the group
					if (in_array($forum['forum_of_group'],$groups_of_user))
					{
						//echo '-is lid';
						$show_forum=true;
					}
					else
					{
						//echo '-is GEEN lid';
						$show_forum=false;
					}
				}

			}
		}
		//echo '<hr>';

		if ($show_forum)
		{
			$form_count++;
			echo "\t<tr class=\"forum\">\n";
			echo "\t\t<td width=\"20\">";
			if ($forum['forum_of_group']!=='0')
			{
				if (is_array($whatsnew_post_info[$forum['forum_id']]) and !empty($whatsnew_post_info[$forum['forum_id']]))
				{
					echo icon('../img/forumgroupnew.gif');
				}
				else
				{
					echo icon('../img/forumgroup.gif');
				}
			}
			else
			{
				if (is_array($whatsnew_post_info[$forum['forum_id']]) and !empty($whatsnew_post_info[$forum['forum_id']]))
				{
					echo icon('../img/forum.gif');
				}
				else
				{
					echo icon('../img/forum.gif');
				}
			}
			echo "</td>\n";
			echo "\t\t<td><a href=\"viewforum.php?".api_get_cidreq()."&forum=".$forum['forum_id']."\" ".class_visible_invisible($forum['visibility']).">".prepare4display($forum['forum_title']).'</a><br />'.prepare4display($forum['forum_comment'])."</td>\n";
			//$number_forum_topics_and_posts=get_post_topics_of_forum($forum['forum_id']); // deprecated
			// the number of topics and posts
			echo "\t\t<td>".$forum['number_of_threads']."</td>\n";
			echo "\t\t<td>".$forum['number_of_posts']."</td>\n";
			// the last post in the forum
			if ($forum['last_poster_name']<>'')
			{
				$name=$forum['last_poster_name'];
				$poster_id=0;
			}
			else
			{
				$name=$forum['last_poster_firstname'].' '.$forum['last_poster_lastname'];
				$poster_id=$forum['last_poster_id'];
			}
			echo "\t\t<td>";
			if (!empty($forum['last_post_id']))
			{
				echo $forum['last_post_date']." ".get_lang('By').' '.display_user_link($poster_id, $name);
			}
			echo "</td>\n";
			if (api_is_allowed_to_edit())
			{
				echo "\t\t<td NOWRAP align='center'>";
				echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=edit&amp;content=forum&amp;id=".$forum['forum_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
				echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=delete&amp;content=forum&amp;id=".$forum['forum_id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeleteForum"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
				display_visible_invisible_icon('forum',$forum['forum_id'], $forum['visibility'], array("forumcategory"=>$_GET['forumcategory']));
				display_lock_unlock_icon('forum',$forum['forum_id'], $forum['locked'], array("forumcategory"=>$_GET['forumcategory']));
				display_up_down_icon('forum',$forum['forum_id'], $forums_in_category);
				echo "</td>\n";
			}
			echo "\t</tr>";
		}
	}
}
if (count($forum_list)==0)
{
	echo "\t<tr><td>".get_lang('NoForumInThisCategory')."</td></tr>\n";
}

echo "</table>\n";

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
?>



