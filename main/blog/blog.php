<?php //$Id: announcements.php 16702 2008-11-10 13:02:30Z elixir_inter $
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
	info@dokeos.com

==============================================================================

    BLOG HOMEPAGE

	This file takes care of all blog navigation and displaying.

	@package dokeos.blogs
==============================================================================
*/

/*
==============================================================================
	INIT
==============================================================================
*/
// name of the language file that needs to be included
$language_file = "blog";
$blog_id = intval($_GET['blog_id']);

include ('../inc/global.inc.php');
$this_section=SECTION_COURSES;


/* ------------	ACCESS RIGHTS ------------ */
// notice for unauthorized people.
api_protect_course_script(true);


//session
if(isset($_GET['id_session']))
{
	$_SESSION['id_session'] = $_GET['id_session'];
}

$lib_path = api_get_path(LIBRARY_PATH);
require_once ($lib_path.'/display.lib.php');
require_once ($lib_path.'/text.lib.php');
require_once ($lib_path.'/blog.lib.php');
require_once ($lib_path.'/fckeditor/fckeditor.php');

$blog_table_attachment 	= Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

$nameTools = get_lang('Blogs');
$DaysShort = array (get_lang('SundayShort'), get_lang('MondayShort'), get_lang('TuesdayShort'), get_lang('WednesdayShort'), get_lang('ThursdayShort'), get_lang('FridayShort'), get_lang('SaturdayShort'));
$DaysLong = array (get_lang('SundayLong'), get_lang('MondayLong'), get_lang('TuesdayLong'), get_lang('WednesdayLong'), get_lang('ThursdayLong'), get_lang('FridayLong'), get_lang('SaturdayLong'));
$MonthsLong = array (get_lang('JanuaryLong'), get_lang('FebruaryLong'), get_lang('MarchLong'), get_lang('AprilLong'), get_lang('MayLong'), get_lang('JuneLong'), get_lang('JulyLong'), get_lang('AugustLong'), get_lang('SeptemberLong'), get_lang('OctoberLong'), get_lang('NovemberLong'), get_lang('DecemberLong'));

$current_page = $_GET['action'];

/*
==============================================================================
	PROCESSING
==============================================================================
*/

$safe_post_title = Security::remove_XSS($_POST['post_title']);
$safe_post_file_comment = Security::remove_XSS($_POST['post_file_comment']);
$safe_post_full_text = Security::remove_XSS(stripslashes(api_html_entity_decode($_POST['post_full_text'])), COURSEMANAGERLOWSECURITY);
$safe_comment_text = Security::remove_XSS(stripslashes(api_html_entity_decode($_POST['comment_text'])), COURSEMANAGERLOWSECURITY);
$safe_comment_title = Security::remove_XSS($_POST['comment_title']);  
$safe_task_name = Security::remove_XSS($_POST['task_name']);
$safe_task_description = Security::remove_XSS($_POST['task_description']);

if (!empty($_POST['new_post_submit']) AND !empty($_POST['post_title']))
{	
	Blog :: create_post($safe_post_title, $safe_post_full_text, $safe_post_file_comment,$blog_id);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('BlogAdded'));
}
if (!empty($_POST['edit_post_submit']))
{
	$safe_post_title = Security::remove_XSS($_POST['post_title']);
	Blog :: edit_post($_POST['post_id'], $safe_post_title, $safe_post_full_text, $blog_id);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('BlogEdited'));
}
if (!empty($_POST['new_comment_submit']))
{
	Blog :: create_comment($safe_comment_title, $safe_comment_text, $safe_post_file_comment,$blog_id, (int)$_GET['post_id'], $_POST['comment_parent_id']);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('CommentAdded'));
}

if (!empty($_POST['new_task_submit']))
{
	Blog :: create_task($blog_id, $safe_task_name, $safe_task_description, $_POST['chkArticleDelete'], $_POST['chkArticleEdit'], $_POST['chkCommentsDelete'], $_POST['task_color']);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskCreated'));
}

if (isset($_POST['edit_task_submit']))
{
	Blog :: edit_task($_POST['blog_id'], $_POST['task_id'], $safe_task_name, $safe_task_description, $_POST['chkArticleDelete'], $_POST['chkArticleEdit'],$_POST['chkCommentsDelete'], $_POST['task_color']);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskEdited'));
}
if (!empty($_POST['assign_task_submit']))
{
	Blog :: assign_task($blog_id, $_POST['task_user_id'], $_POST['task_task_id'], $_POST['task_year']."-".$_POST['task_month']."-".$_POST['task_day']);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskAssigned'));
}

if (isset($_POST['assign_task_edit_submit']))
{
    Blog :: edit_assigned_task($blog_id, $_POST['task_user_id'], $_POST['task_task_id'], $_POST['task_year']."-".$_POST['task_month']."-".$_POST['task_day'], $_POST['old_user_id'], $_POST['old_task_id'], $_POST['old_target_date']);
    $return_message = array('type' => 'confirmation', 'message' => get_lang('AssignedTaskEdited'));
}
if (!empty($_POST['new_task_execution_submit']))
{
	Blog :: create_comment($safe_comment_title, $safe_comment_text, $blog_id, (int)$_GET['post_id'], $_POST['comment_parent_id'], $_POST['task_id']);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('CommentCreated'));
}
if (!empty($_POST['register']))
{	
	if (is_array($_POST['user'])) {
		foreach ($_POST['user'] as $index => $user_id)
		{
			Blog :: set_user_subscribed((int)$_GET['blog_id'], $user_id);
		}
	}
}
if (!empty($_POST['unregister']))
{	
	if (is_array($_POST['user'])) {
		foreach ($_POST['user'] as $index => $user_id)
		{
			Blog :: set_user_unsubscribed((int)$_GET['blog_id'], $user_id);
		}
	}
}
if (!empty($_GET['register']))
{
	Blog :: set_user_subscribed((int)$_GET['blog_id'], (int)$_GET['user_id']);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('UserRegistered'));
	$flag = 1;
}
if (!empty($_GET['unregister']))
{
	Blog :: set_user_unsubscribed((int)$_GET['blog_id'], (int)$_GET['user_id']);
}

if (isset($_GET['action']) && $_GET['action'] == 'manage_tasks')
{
	if (isset($_GET['do']) && $_GET['do'] == 'delete')
	{
		Blog :: delete_task($blog_id, (int)$_GET['task_id']);
		$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskDeleted'));
	}

	if (isset($_GET['do']) && $_GET['do'] == 'delete_assignment')
	{
		Blog :: delete_assigned_task($blog_id, Database::escape_string((int)$_GET['task_id']), Database::escape_string((int)$_GET['user_id']));
		$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskAssignmentDeleted'));
	}
		
}

if (isset($_GET['action']) && $_GET['action'] == 'view_post')
{
	$task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;

	if (isset($_GET['do']) && $_GET['do'] == 'delete_comment')
	{
		if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_delete', $task_id))
		{		
			Blog :: delete_comment($blog_id, (int)$_GET['post_id'],(int)$_GET['comment_id']); 
			$return_message = array('type' => 'confirmation', 'message' => get_lang('CommentDeleted'));
		}
		else
		{
			$error = true;
			$message = get_lang('ActionNotAllowed');
		}
	}

	if (isset($_GET['do']) && $_GET['do'] == 'delete_article')
	{
		if (api_is_allowed('BLOG_'.$blog_id, 'article_delete', $task_id))
		{
			Blog :: delete_post($blog_id, (int)$_GET['article_id']);
			$current_page = ''; // Article is gone, go to blog home
			$return_message = array('type' => 'confirmation', 'message' => get_lang('BlogDeleted'));
		}
		else
		{
			$error = true;
			$message = get_lang('ActionNotAllowed');
		}
	}
	if (isset($_GET['do']) && $_GET['do'] == 'rate')
	{
		if (isset($_GET['type']) && $_GET['type'] == 'post')
		{
			if (api_is_allowed('BLOG_'.$blog_id, 'article_rate'))
			{
				Blog :: add_rating('post', $blog_id, (int)$_GET['post_id'], (int)$_GET['rating']);
				$return_message = array('type' => 'confirmation', 'message' => get_lang('RatingAdded'));
			}
		}
		if (isset($_GET['type']) && $_GET['type'] == 'comment')
		{
			if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_add'))
			{
				Blog :: add_rating('comment', $blog_id, (int)$_GET['comment_id'], (int)$_GET['rating']);
				$return_message = array('type' => 'confirmation', 'message' => get_lang('RatingAdded'));
			}
		}
	}
}
/*
==============================================================================
	DISPLAY
==============================================================================
*/
$htmlHeadXtra[] = '<script src="tbl_change.js" type="text/javascript" language="javascript"></script>';

// Set bredcrumb
switch ($current_page)
{
	case 'new_post' :
		$nameTools = get_lang('NewPost');
		$interbreadcrumb[] = array ('url' => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'manage_tasks' :
		$nameTools = get_lang('TaskManager');
		$interbreadcrumb[] = array ('url' => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'manage_members' :
		$nameTools = get_lang('MemberManager');
		$interbreadcrumb[] = array ('url' => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'manage_rights' :
		$nameTools = get_lang('RightsManager');
		$interbreadcrumb[] = array ('url' => "blog.php?blog_id=$blog_id", 'name' => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'view_search_result' :
		$nameTools = get_lang('SearchResults');
		$interbreadcrumb[] = array ('url' => "blog.php?blog_id=$blog_id", 'name' => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'execute_task' :
		$nameTools = get_lang('ExecuteThisTask');
		$interbreadcrumb[] = array ('url' => "blog.php?blog_id=$blog_id", 'name' => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'Blogs');
		break;
	default :
		$nameTools = Blog :: get_blog_title($blog_id);
		Display :: display_header($nameTools, 'Blogs');
}

// feedback messages
if (!empty($return_message))
{
	if ($return_message['type'] == 'confirmation')
	{
		Display::display_confirmation_message($return_message['message']);
	}
	if ($return_message['type'] == 'error')
	{
		Display::display_error_message($return_message['message']);
	}	
}


// actions
echo '<div class=actions>';
?>
	<a href="<?php echo api_get_self(); ?>?blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('Home') ?>"><?php echo Display::return_icon('blog.gif', get_lang('Home')).get_lang('Home') ?></a>
	<?php if(api_is_allowed('BLOG_'.$blog_id, 'article_add')) { ?><a href="<?php echo api_get_self(); ?>?action=new_post&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('NewPost') ?>"><?php echo Display::return_icon('blog_article.gif', get_lang('NewPost')).get_lang('NewPost') ?></a><?php } ?>
	<?php if(api_is_allowed('BLOG_'.$blog_id, 'task_management')) { ?><a href="<?php echo api_get_self(); ?>?action=manage_tasks&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageTasks') ?>"><?php echo Display::return_icon('blog_tasks.gif', get_lang('TaskManager')).get_lang('TaskManager') ?></a><?php } ?>
	<?php if(api_is_allowed('BLOG_'.$blog_id, 'member_management')) { ?><a href="<?php echo api_get_self(); ?>?action=manage_members&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageMembers') ?>"><?php echo Display::return_icon('blog_user.gif', get_lang('MemberManager')).get_lang('MemberManager') ?></a><?php } ?>
<?php	
echo '</div>';	
		
// Tool introduction
Display::display_introduction_section(TOOL_BLOG);

//Display::display_header($nameTools,'Blogs');
?>
<div class="sectiontitle"><?php echo Blog::get_blog_title($blog_id); ?></div>
<div class="sectioncomment"><?php echo Blog::get_blog_subtitle($blog_id); ?></div>

<table width="100%">
<tr>
	<td width="10%" style="float;left;" class="blog_left" valign="top">
		<?php

$month = (int)$_GET['month'] ? (int)$_GET['month'] : (int) date('m');
$year = (int)$_GET['year'] ? (int)$_GET['year'] : date('Y');
Blog :: display_minimonthcalendar($month, $year, $blog_id);
?>
		<br />

		<br />
		<table width="100%">
			<tr>
				<td class="sectiontitle"><?php echo get_lang('Search') ?></td>
			</tr>
			<tr>
				<td class="blog_menu">
					<form action="blog.php" method="get" enctype="multipart/form-data">
						<input type="hidden" name="blog_id" value="<?php echo $blog_id ?>" />
						<input type="hidden" name="action" value="view_search_result" />
						<input type="text" size="20" name="q" value="<?php echo (isset($_GET['q']) ? $_GET['q'] : ''); ?>" /><button class="search" type="submit"><?php echo get_lang('Search'); ?></button>
					</form>
				</td>
			</tr>
		</table>
		<br />
		<table width="100%">
			<tr>
				<td class="sectiontitle"><?php echo get_lang('MyTasks') ?></td>
			</tr>
			<tr>
				<td class="blog_menu">
					<?php Blog::get_personal_task_list(); ?>
				</td>
			</tr>
		</table>
		<!--
		<br />
		<table width="100%">
			<tr>
				<td class="blog_menu_title"><?php echo get_lang('FavoriteBlogs') ?></td>
			</tr>
			<tr>
				<td class="blog_menu">
					<ul>
						<li>Favorite 1</li>
						<li>Favorite 2</li>
						<li>Favorite 3</li>
					</ul>
				</td>
			</tr>
		</table>
		<br />
		<table width="100%">
			<tr>
				<td class="blog_menu_title"><?php echo get_lang('TopTen') ?></td>
			</tr>
			<tr>
				<td class="blog_menu">
					<ul>
						<li>Blog 1</li>
						<li>Blog 2</li>
						<li>Blog 3</li>
					</ul>
				</td>
			</tr>
		</table>
	-->
	</td>
	<td valign="top" class="blog_right">
		<?php


if ($error)
	Display :: display_error_message($message);

if ($flag == '1')
{
	$current_page = "manage_tasks";
	Blog :: display_assign_task_form($blog_id);
}

$user_task = false;

if (isset ($_GET['task_id']) && is_numeric($_GET['task_id']))
	$task_id = (int)$_GET['task_id'];
else
{
	$task_id = 0;

	$tbl_blogs_tasks_rel_user = Database :: get_course_table(TABLE_BLOGS_TASKS_REL_USER);

	$sql = "
							SELECT COUNT(*) as number
							FROM ".$tbl_blogs_tasks_rel_user."
							WHERE
								blog_id = ".$blog_id." AND
								user_id = ".api_get_user_id()." AND
								task_id = ".$task_id;

	$result = api_sql_query($sql, __LINE__, __FILE__);
	$row = Database::fetch_array($result);

	if ($row['number'] == 1)
		$user_task = true;
}

switch ($current_page)
{
	case 'new_post' :
		if (api_is_allowed('BLOG_'.$blog_id, 'article_add', $user_task ? $task_id : 0))
		{
			// we show the form if 
			// 1. no post data
			// 2. there is post data and the required field is empty
			if (!$_POST OR (!empty($_POST) AND empty($_POST['post_title'])))
			{
				// if there is post data there is certainly an error in the form
				if ($_POST)
				{
					Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'));
				}			
			Blog :: display_form_new_post($blog_id);
		}
		else
		{
				if (isset ($_GET['filter']) && !empty ($_GET['filter']))
				{
					Blog :: display_day_results($blog_id, Database::escape_string($_GET['filter']));
				}
				else
				{
					Blog :: display_blog_posts($blog_id);
				}
			}
		}
		else
		{
			api_not_allowed();
		}
		break;
	case 'view_post' :
		Blog :: display_post($blog_id, Database::escape_string((int)$_GET['post_id']));
		break;
	case 'edit_post' :
		$task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;

		if (api_is_allowed('BLOG_'.$blog_id, 'article_edit', $task_id))
		{
			// we show the form if 
			// 1. no post data
			// 2. there is post data and the required field is empty
			if (!$_POST OR (!empty($_POST) AND empty($_POST['post_title'])))
			{
				// if there is post data there is certainly an error in the form
				if ($_POST)
				{
					Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'));
				}				
			Blog :: display_form_edit_post($blog_id, Database::escape_string((int)$_GET['post_id']));
			}
			else 
			{
				if (isset ($_GET['filter']) && !empty ($_GET['filter']))
				{
					Blog :: display_day_results($blog_id, Database::escape_string($_GET['filter']));
				}
				else
				{
					Blog :: display_blog_posts($blog_id);
				}
			}				
		}
		else
		{
			api_not_allowed();
		}

		break;
	case 'manage_members' :
		if (api_is_allowed('BLOG_'.$blog_id, 'member_management'))
		{
			Blog :: display_form_user_subscribe($blog_id);
			echo '<br /><br />';
			Blog :: display_form_user_unsubscribe($blog_id);
		}
		else
			api_not_allowed();

		break;
	case 'manage_rights' :
		Blog :: display_form_user_rights($blog_id);
		break;
	case 'manage_tasks' :
		if (api_is_allowed('BLOG_'.$blog_id, 'task_management'))
		{
			if (isset($_GET['do']) && $_GET['do'] == 'add')
			{
				Blog :: display_new_task_form($blog_id);
			}
			if (isset($_GET['do']) && $_GET['do'] == 'assign')
			{
				Blog :: display_assign_task_form($blog_id);
			}
			if (isset($_GET['do']) && $_GET['do'] == 'edit')
			{
				Blog :: display_edit_task_form($blog_id, Database::escape_string($_GET['task_id']));
			}
			if (isset($_GET['do']) && $_GET['do'] == 'edit_assignment')
			{
				Blog :: display_edit_assigned_task_form($blog_id, Database::escape_string((int)$_GET['task_id']), Database::escape_string((int)$_GET['user_id']));
			}
			Blog :: display_task_list($blog_id);
			echo '<br /><br />';
			Blog :: display_assigned_task_list($blog_id);
			echo '<br /><br />';
		}
		else
			api_not_allowed();

		break;
	case 'execute_task' :
		if (isset ($_GET['post_id']))
			Blog :: display_post($blog_id, Database::escape_string((int)$_GET['post_id']));
		else
			Blog :: display_select_task_post($blog_id, Database::escape_string((int)$_GET['task_id']));

		break;
	case 'view_search_result' :
		Blog :: display_search_results($blog_id, Database::escape_string($_GET['q']));
		break;
	case '' :
	default :
		if (isset ($_GET['filter']) && !empty ($_GET['filter']))
		{
			Blog :: display_day_results($blog_id, Database::escape_string($_GET['filter']));
		}
		else
		{
			Blog :: display_blog_posts($blog_id);
		}
}
?>
	</td>
</tr>
</table>

<?php
// Display the footer
Display::display_footer();
?>
