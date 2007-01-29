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

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Middle';
$fck_attribute['Config']['IMUploadPath'] = 'upload/forum/';
$fck_attribute['Config']['FlashUploadPath'] = 'upload/forum/';
if(!api_is_allowed_to_edit()) $fck_attribute['Config']['UserStatus'] = 'student';

//error_reporting(E_ALL);
/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
Display :: display_header($nameTools);
api_display_tool_title($nameTools);
//echo '<link href="forumstyles.css" rel="stylesheet" type="text/css" />';

// tool introduction
Display::display_introduction_section(TOOL_FORUM);

/*
------------------------------------------------------------------------------------------------------
	ACTIONS
------------------------------------------------------------------------------------------------------
*/
if (api_is_allowed_to_edit())
{
	$fck_attribute['ToolbarSet'] = 'ForumLight';
	handle_forum_and_forumcategories();
}
get_whats_new();
$whatsnew_post_info=$_SESSION['whatsnew_post_info'];

/*
-----------------------------------------------------------
  			TRACKING
-----------------------------------------------------------
*/
include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
event_access_tool(TOOL_FORUM);


/*
------------------------------------------------------------------------------------------------------
	RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
------------------------------------------------------------------------------------------------------
note: we do this here just after het handling of the actions to be sure that we already incorporate the
latest changes
*/
// Step 1: We store all the forum categories in an array $forum_categories
$forum_categories=array();
$forum_categories_list=get_forum_categories();

// step 2: we find all the forums (only the visible ones if it is a student)
$forum_list=array();
$forum_list=get_forums();

/*
------------------------------------------------------------------------------------------------------
	RETRIEVING ALL GROUPS AND THOSE OF THE USER
------------------------------------------------------------------------------------------------------
*/
// the groups of the user
$groups_of_user=array();
$groups_of_user=GroupManager::get_group_ids($_course['dbName'], $_user['user_id']);
// all groups in the course (and sorting them as the id of the group = the key of the array
$all_groups=GroupManager::get_group_list();
if(is_array($all_groups))
{
	foreach ($all_groups as $group)
	{
		$all_groups[$group['id']]=$group; 
	}
}

/*
------------------------------------------------------------------------------------------------------
	ACTION LINKS
------------------------------------------------------------------------------------------------------
*/
//if (api_is_allowed_to_edit() and !$_GET['action'])
if (api_is_allowed_to_edit())
{
	echo '<a href="'.$_SERVER['PHP_SELF'].'?action=add&amp;content=forumcategory"> '.Display::return_icon('forum_category_new.gif').' '.get_lang('AddForumCategory').'</a> ';
	if (is_array($forum_categories_list))
	{
		echo '<a href="'.$_SERVER['PHP_SELF'].'?action=add&amp;content=forum"> '.Display::return_icon('forum_new.gif').' '.get_lang('AddForum').'</a>';
	}
	//echo ' | <a href="forum_migration.php">'.get_lang('MigrateForum').'</a>';
}

/*
------------------------------------------------------------------------------------------------------
	Display Forum Categories and the Forums in it
------------------------------------------------------------------------------------------------------
*/
echo "<table class='data_table' width='100%'>\n";
// Step 3: we display the forum_categories first
foreach ($forum_categories_list as $forum_category_key => $forum_category)
{
	echo "\t<tr>\n\t\t<th style=\"padding-left:5px;\" align=\"left\" colspan=\"5\">";
	echo '<a href="viewforumcategory.php?forumcategory='.prepare4display($forum_category['cat_id']).'" '.class_visible_invisible(prepare4display($forum_category['visibility'])).'>'.prepare4display($forum_category['cat_title']).'</a><br />';
	if ($forum_category['cat_comment']<>'' AND trim($forum_category['cat_comment'])<>'&nbsp;')
	{
		echo '<span>'.prepare4display($forum_category['cat_comment']).'</span>';
	}
	echo "</th>\n";
	if (api_is_allowed_to_edit())
	{
		echo "\t\t<th>";
		echo "<a href=\"".$_SERVER['PHP_SELF']."?action=edit&amp;content=forumcategory&amp;id=".prepare4display($forum_category['cat_id'])."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
		echo "<a href=\"".$_SERVER['PHP_SELF']."?action=delete&amp;content=forumcategory&amp;id=".prepare4display($forum_category['cat_id'])."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeleteForumCategory")))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
		display_visible_invisible_icon('forumcategory', prepare4display($forum_category['cat_id']), prepare4display($forum_category['visibility']));
		display_lock_unlock_icon('forumcategory',prepare4display($forum_category['cat_id']), prepare4display($forum_category['locked']));
		display_up_down_icon('forumcategory',prepare4display($forum_category['cat_id']), $forum_categories_list);
		echo "</th>\n";
	}
	echo "\t</tr>\n";
	
	// step 4: the interim headers (for the forum)
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
	
	// step 5: we display all the forums in this category.
	$forum_count=0;
	foreach ($forum_list as $key=>$forum)
	{
		// Here we clean the whatnew_post_info array a little bit because to display the icon we 
		// test if $whatsnew_post_info[$forum['forum_id']] is empty or not. 
		foreach ($whatsnew_post_info[$forum['forum_id']] as $key_thread_id => $new_post_array)
		{
			if (empty($whatsnew_post_info[$forum['forum_id']][$key_thread_id]))
			{
				unset($whatsnew_post_info[$forum['forum_id']][$key_thread_id]);
				unset($_SESSION['whatsnew_post_info'][$forum['forum_id']][$key_thread_id]);
			}
		}
		
		// note: this can be speeded up if we transform the $forum_list to an array that uses the forum_category as the key.
		if (prepare4display($forum['forum_category'])==prepare4display($forum_category['cat_id']))
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
						echo icon('../img/forumnew.gif');	
					}
					else 
					{
						echo icon('../img/forum.gif');	
					}
					
				}
				echo "</td>\n";
				if ($forum['forum_of_group']<>'0')
				{
					$group_title=substr($all_groups[$forum['forum_of_group']]['name'],0,30);
					$forum_title_group_addition=' (<a href="../group/group_space.php?gidReq='.$all_groups[$forum['forum_of_group']]['id'].'" class="forum_group_link">'.$group_title.'</a>)';
				}
				else 
				{
					$forum_title_group_addition='';
				}
				
				echo "\t\t<td><a href=\"viewforum.php?forum=".prepare4display($forum['forum_id'])."\" ".class_visible_invisible(prepare4display($forum['visibility'])).">".prepare4display($forum['forum_title']).'</a>'.$forum_title_group_addition.'<br />'.prepare4display($forum['forum_comment'])."</td>\n";
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
				echo "\t\t<td NOWRAP>";
				if (!empty($forum['last_post_id']))
				{
					echo $forum['last_post_date']."<br /> ".get_lang('By').' '.display_user_link($poster_id, $name);
				}
				echo "</td>\n";
				
				
				if (api_is_allowed_to_edit())
				{
					echo "\t\t<td NOWRAP>";
					echo "<a href=\"".$_SERVER['PHP_SELF']."?action=edit&amp;content=forum&amp;id=".$forum['forum_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
					echo "<a href=\"".$_SERVER['PHP_SELF']."?action=delete&amp;content=forum&amp;id=".$forum['forum_id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeleteForum")))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
					display_visible_invisible_icon('forum',$forum['forum_id'], $forum['visibility']);
					display_lock_unlock_icon('forum',$forum['forum_id'], $forum['locked']);
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
}
echo "</table>\n";

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
?>



