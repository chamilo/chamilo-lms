<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com

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
$langFile = "blog";
$blog_id = intval($_GET['blog_id']);

include ('../inc/global.inc.php');

//session
if(isset($_GET['id_session']))
	$_SESSION['id_session'] = $_GET['id_session'];

require_once (api_get_path(LIBRARY_PATH)."/display.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/text.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/blog.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/fckeditor.lib.php");

$nameTools = get_lang("blogs");
$DaysShort = array (get_lang("SundayShort"), get_lang("MondayShort"), get_lang("TuesdayShort"), get_lang("WednesdayShort"), get_lang("ThursdayShort"), get_lang("FridayShort"), get_lang("SaturdayShort"));
$DaysLong = array (get_lang("SundayLong"), get_lang("MondayLong"), get_lang("TuesdayLong"), get_lang("WednesdayLong"), get_lang("ThursdayLong"), get_lang("FridayLong"), get_lang("SaturdayLong"));
$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

$current_page = $_GET['action'];

/*
==============================================================================
	PROCESSING
==============================================================================
*/
if ($_POST['new_post_submit'])
{
	Blog :: create_post(mysql_real_escape_string($_POST['post_title']), mysql_real_escape_string($_POST['post_full_text']), $blog_id);
}
if ($_POST['edit_post_submit'])
{
	Blog :: edit_post(mysql_real_escape_string($_POST['post_id']), mysql_real_escape_string($_POST['post_title']), mysql_real_escape_string($_POST['post_full_text']), $blog_id);
}
if ($_POST['new_comment_submit'])
{
	Blog :: create_comment(mysql_real_escape_string($_POST['comment_title']), mysql_real_escape_string($_POST['comment_text']), $blog_id, mysql_real_escape_string($_GET['post_id']), mysql_real_escape_string($_POST['comment_parent_id']));
}

if ($_POST['new_task_submit'])
{
	Blog :: create_task($blog_id, mysql_real_escape_string($_POST['task_name']), mysql_real_escape_string($_POST['task_description']), mysql_real_escape_string($_POST['chkArticleDelete']), mysql_real_escape_string($_POST['chkArticleEdit']), mysql_real_escape_string($_POST['chkCommentsDelete']), mysql_real_escape_string($_POST['task_color']));
}
if ($_POST['edit_task_submit'])
{
	Blog :: edit_task(mysql_real_escape_string($_POST['blog_id']), mysql_real_escape_string($_POST['task_id']), mysql_real_escape_string($_POST['task_name']), mysql_real_escape_string($_POST['task_description']), mysql_real_escape_string($_POST['chkArticleDelete']), mysql_real_escape_string($_POST['chkArticleEdit']), mysql_real_escape_string($_POST['chkCommentsDelete']), mysql_real_escape_string($_POST['task_color']));
}
if ($_POST['assign_task_submit'])
{
	Blog :: assign_task($blog_id, mysql_real_escape_string($_POST['task_user_id']), mysql_real_escape_string($_POST['task_task_id']), mysql_real_escape_string($_POST['task_year'])."-".mysql_real_escape_string($_POST['task_month'])."-".mysql_real_escape_string($_POST['task_day']));
}

if ($_POST['assign_task_edit_submit'])
{
	Blog :: edit_assigned_task($blog_id, mysql_real_escape_string($_POST['task_user_id']), mysql_real_escape_string($_POST['task_task_id']), mysql_real_escape_string($_POST['task_year'])."-".mysql_real_escape_string($_POST['task_month'])."-".mysql_real_escape_string($_POST['task_day']), mysql_real_escape_string($_POST['old_user_id']), mysql_real_escape_string($_POST['old_task_id']), mysql_real_escape_string($_POST['old_target_date']));
}
if ($_POST['new_task_execution_submit'])
{
	Blog :: create_comment(mysql_real_escape_string($_POST['comment_title']), mysql_real_escape_string($_POST['comment_text']), $blog_id, mysql_real_escape_string($_GET['post_id']), mysql_real_escape_string($_POST['comment_parent_id']), mysql_real_escape_string($_POST['task_id']));
}
if ($_POST['register'])
{
	foreach ($_POST['user'] as $index => $user_id)
	{
		Blog :: set_user_subscribed(mysql_real_escape_string($_GET['blog_id']), mysql_real_escape_string($user_id));
	}
}
if ($_POST['unregister'])
{
	foreach ($_POST['user'] as $index => $user_id)
	{
		Blog :: set_user_unsubscribed(mysql_real_escape_string($_GET['blog_id']), mysql_real_escape_string($user_id));
	}
}
if ($_GET['register'])
{
	Blog :: set_user_subscribed(mysql_real_escape_string($_GET['blog_id']), mysql_real_escape_string($_GET['user_id']));
	$flag = 1;
}
if ($_GET['unregister'])
{
	Blog :: set_user_unsubscribed(mysql_real_escape_string($_GET['blog_id']), mysql_real_escape_string($_GET['user_id']));
}

if ($_GET['action'] == 'manage_tasks')
{
	if ($_GET['do'] == 'delete')
		Blog :: delete_task($blog_id, mysql_real_escape_string($_GET['task_id']));

	if ($_GET['do'] == 'delete_assignment')
		Blog :: delete_assigned_task($blog_id, mysql_real_escape_string($_GET['assignment_id']));
}

if ($_GET['action'] == 'view_post')
{
	$task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;

	if ($_GET['do'] == 'delete_comment')
	{
		if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'article_comments_delete', $task_id))
		{
			Blog :: delete_comment($blog_id, mysql_real_escape_string($_GET['comment_id']));
		}
		else
		{
			$error = true;
			$message = get_lang('ActionNotAllowed');
		}
	}

	if ($_GET['do'] == 'delete_article')
	{
		if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'article_delete', $task_id))
		{
			Blog :: delete_post($blog_id, mysql_real_escape_string($_GET['article_id']));
			$current_page = ''; // Article is gone, go to blog home
		}
		else
		{
			$error = true;
			$message = get_lang('ActionNotAllowed');
		}
	}
	if ($_GET['do'] == 'rate')
	{
		if ($_GET['type'] == 'post')
		{
			if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'article_rate'))
			{
				Blog :: add_rating('post', $blog_id, mysql_real_escape_string($_GET['post_id']), mysql_real_escape_string($_GET['rating']));
			}
		}
		if ($_GET['type'] == 'comment')
		{
			if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'article_comments_add'))
			{
				Blog :: add_rating('comment', $blog_id, mysql_real_escape_string($_GET['comment_id']), mysql_real_escape_string($_GET['rating']));
			}
		}
	}
}
/*
==============================================================================
	DISPLAY
==============================================================================
*/
$htmlHeadXtra[] = "<script src=\"tbl_change.js\" type=\"text/javascript\" language=\"javascript\"></script>";

// Set bredcrumb
switch ($current_page)
{
	case "new_post" :
		$nameTools = get_lang('NewPost');
		$interbreadcrumb[] = array ("url" => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'blogs');
		break;
	case "manage_tasks" :
		$nameTools = get_lang('TaskManager');
		$interbreadcrumb[] = array ("url" => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'blogs');
		break;
	case "manage_members" :
		$nameTools = get_lang('MemberManager');
		$interbreadcrumb[] = array ("url" => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'blogs');
		break;
	case "manage_rights" :
		$nameTools = get_lang('RightsManager');
		$interbreadcrumb[] = array ("url" => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'blogs');
		break;
	case "view_search_result" :
		$nameTools = get_lang('SearchResults');
		$interbreadcrumb[] = array ("url" => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'blogs');
		break;
	case "execute_task" :
		$nameTools = get_lang('ExecuteThisTask');
		$interbreadcrumb[] = array ("url" => "blog.php?blog_id=$blog_id", "name" => Blog :: get_blog_title($blog_id));
		Display :: display_header($nameTools, 'blogs');
		break;
	default :
		$nameTools = Blog :: get_blog_title($blog_id);
		Display :: display_header($nameTools, 'blogs');
}

//Display::display_header($nameTools,'Blogs');
?>
<span class="blog_title"><?php echo Blog::get_blog_title($blog_id) ?></span><br />
<span class="blog_subtitle"><?php echo Blog::get_blog_subtitle($blog_id) ?></span>
<br /><br />
<table width="100%">
<tr>
	<td width="220" class="blog_left" valign="top">
		<?php


$month = $_GET['month'] ? $_GET['month'] : (int) date('m');
$year = $_GET['year'] ? $_GET['year'] : date('Y');
Blog :: display_minimonthcalendar($month, $year, $blog_id);
?>
		<br />
		<table width="100%">
			<tr>
				<td class="blog_menu_title"><?php echo get_lang('ThisBlog') ?></td>
			</tr>
			<tr>
				<td class="blog_menu">
					<ul>
						<li><a href="<?php echo $_SERVER['PHP_SELF'] ?>?blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('Home') ?>"><?php echo get_lang('Home') ?></a></li>
						<?php if(api_is_allowed_to_edit('BLOG_'.$blog_id, 'article_add')) { ?><li><a href="<?php echo $_SERVER['PHP_SELF'] ?>?action=new_post&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('NewPost') ?>"><?php echo get_lang('NewPost') ?></a></li><?php } ?>
						<?php if(api_is_allowed_to_edit('BLOG_'.$blog_id, 'task_management')) { ?><li><a href="<?php echo $_SERVER['PHP_SELF'] ?>?action=manage_tasks&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageTasks') ?>"><?php echo get_lang('TaskManager') ?></a></li> <?php } ?>
						<?php if(api_is_allowed_to_edit('BLOG_'.$blog_id, 'member_management')) { ?><li><a href="<?php echo $_SERVER['PHP_SELF'] ?>?action=manage_members&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageMembers') ?>"><?php echo get_lang('MemberManager') ?></a></li><?php } ?>
					</ul>
				</td>
			</tr>
		</table>
		<br />
		<table width="100%">
			<tr>
				<td class="blog_menu_title"><?php echo get_lang('Search') ?></td>
			</tr>
			<tr>
				<td class="blog_menu">
					<form action="blog.php" method="get" enctype="multipart/form-data">
						<input type="hidden" name="blog_id" value="<?php echo $blog_id ?>" />
						<input type="hidden" name="action" value="view_search_result" />
						<input type="text" size="20" name="q" value="<?php echo (isset($_GET['q']) ? $_GET['q'] : ''); ?>" /><input type="submit" value="Go" />
					</form>
				</td>
			</tr>
		</table>
		<br />
		<table width="100%">
			<tr>
				<td class="blog_menu_title"><?php echo get_lang('MyTasks') ?></td>
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
	$task_id = $_GET['task_id'];
else
{
	$task_id = 0;

	$tbl_blogs_tasks_rel_user = Database :: get_course_table(BLOGS_TASKS_REL_USER);

	$sql = "
							SELECT COUNT(*) as `number`
							FROM ".$tbl_blogs_tasks_rel_user."
							WHERE
								`blog_id` = ".$blog_id." AND
								`user_id` = ".$_user['user_id']." AND
								`task_id` = ".$task_id;

	$result = api_sql_query($sql, __LINE__, __FILE__);
	$row = mysql_fetch_array($result, MYSQL_ASSOC);

	if ($row['number'] == 1)
		$user_task = true;
}

switch ($current_page)
{
	case 'new_post' :
		if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'article_add', $user_task ? $task_id : 0))
		{
			Blog :: display_form_new_post($blog_id);
		}
		else
		{
			api_not_allowed();
		}
		break;
	case 'view_post' :
		Blog :: display_post($blog_id, mysql_real_escape_string($_GET['post_id']));
		break;
	case 'edit_post' :
		$task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;

		if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'article_edit', $task_id))
			Blog :: display_form_edit_post($blog_id, mysql_real_escape_string($_GET['post_id']));
		else
			api_not_allowed();

		break;
	case 'manage_members' :
		if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'member_management'))
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
		if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'task_management'))
		{
			if ($_GET['do'] == 'add')
			{
				Blog :: display_new_task_form($blog_id);
			}
			if ($_GET['do'] == 'assign')
			{
				Blog :: display_assign_task_form($blog_id);
			}
			if ($_GET['do'] == 'edit')
			{
				Blog :: display_edit_task_form($blog_id, mysql_real_escape_string($_GET['task_id']));
			}
			if ($_GET['do'] == 'edit_assignment')
			{
				Blog :: display_edit_assigned_task_form($blog_id, mysql_real_escape_string($_GET['assignment_id']));
			}
			Blog :: display_task_list($blog_id);
			echo '<br /><br />';
			Blog :: display_assigned_task_list($blog_id);
			echo '<br /><br />';
			if (api_is_allowed_to_edit('BLOG_'.$blog_id, 'role_management'))
			{
			?>
				<a href="<?php echo $_SERVER['PHP_SELF'] ?>?action=manage_rights&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageRights') ?>"><?php echo get_lang('RightsManager') ?></a>
			<?php
			}
		}
		else
			api_not_allowed();

		break;
	case 'execute_task' :
		if (isset ($_GET['post_id']))
			Blog :: display_post($blog_id, mysql_real_escape_string($_GET['post_id']));
		else
			Blog :: display_select_task_post($blog_id, mysql_real_escape_string($_GET['task_id']));

		break;
	case 'view_search_result' :
		Blog :: display_search_results($blog_id, mysql_real_escape_string($_GET['q']));
		break;
	case '' :
	default :
		if (isset ($_GET['filter']) && !empty ($_GET['filter']))
		{
			Blog :: display_day_results($blog_id, mysql_real_escape_string($_GET['filter']));
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


// The footer is displayed only if we are not in the learnpath
if ($_GET['origin'] != 'learnpath')
{
	include ($includePath."/claro_init_footer.inc.php");
}
?>