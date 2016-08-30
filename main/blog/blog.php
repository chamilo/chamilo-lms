<?php
/* For licensing terms, see /license.txt */
/**
 * BLOG HOMEPAGE
 * This file takes care of all blog navigation and displaying.
 * @package chamilo.blogs
 */
require_once '../inc/global.inc.php';

$blog_id = intval($_GET['blog_id']);

if (empty($blog_id)) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_BLOGS;

/* 	ACCESS RIGHTS */
// notice for unauthorized people.
api_protect_course_script(true);

$lib_path = api_get_path(LIBRARY_PATH);
$blog_table_attachment 	= Database::get_course_table(TABLE_BLOGS_ATTACHMENT);

$nameTools  = get_lang('Blogs');
$DaysShort  = api_get_week_days_short();
$DaysLong   = api_get_week_days_long();
$MonthsLong = api_get_months_long();

$action = isset($_GET['action']) ? $_GET['action'] : null;

/*
	PROCESSING
*/

$safe_post_file_comment = isset($_POST['post_file_comment']) ? Security::remove_XSS($_POST['post_file_comment']) : null;
$safe_comment_text      = isset($_POST['comment_text']) ? Security::remove_XSS($_POST['comment_text']) : null;
$safe_comment_title     = isset($_POST['comment_title']) ? Security::remove_XSS($_POST['comment_title']) : null;
$safe_task_name         = isset($_POST['task_name']) ? Security::remove_XSS($_POST['task_name']) : null;
$safe_task_description  = isset($_POST['task_description']) ? Security::remove_XSS($_POST['task_description']) : null;

if (!empty($_POST['new_post_submit'])) {
	Blog:: create_post(
		$_POST['title'],
		$_POST['full_text'],
		$_POST['post_file_comment'],
		$blog_id
	);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('BlogAdded'));
}
if (!empty($_POST['edit_post_submit'])) {
	Blog:: edit_post(
		$_POST['post_id'],
		$_POST['title'],
		$_POST['full_text'],
		$blog_id
	);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('BlogEdited'));
}

if (!empty($_POST['new_comment_submit'])) {
	Blog:: create_comment(
		$_POST['title'],
		$_POST['comment'],
		$_POST['post_file_comment'],
		$blog_id,
		$_GET['post_id'],
		$_POST['comment_parent_id']
	);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('CommentAdded'));
}

if (!empty($_POST['new_task_submit'])) {
	Blog:: create_task(
		$blog_id,
		$safe_task_name,
		$safe_task_description,
		(isset($_POST['chkArticleDelete']) ? $_POST['chkArticleDelete'] : null),
		(isset($_POST['chkArticleEdit']) ? $_POST['chkArticleEdit'] : null),
		(isset($_POST['chkCommentsDelete']) ? $_POST['chkCommentsDelete'] : null),
		(isset($_POST['task_color']) ? $_POST['task_color'] : null)
	);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskCreated'));
}

if (isset($_POST['edit_task_submit'])) {
	Blog:: edit_task(
		$_POST['blog_id'],
		$_POST['task_id'],
		$safe_task_name,
		$safe_task_description,
		$_POST['chkArticleDelete'],
		$_POST['chkArticleEdit'],
		$_POST['chkCommentsDelete'],
		$_POST['task_color']
	);
	$return_message = array(
		'type' => 'confirmation',
		'message' => get_lang('TaskEdited')
	);
}

if (!empty($_POST['assign_task_submit'])) {
	Blog:: assign_task(
		$blog_id,
		$_POST['task_user_id'],
		$_POST['task_task_id'],
		$_POST['task_day']
	);
	$return_message = array(
		'type' => 'confirmation',
		'message' => get_lang('TaskAssigned')
	);
}

if (isset($_POST['assign_task_edit_submit'])) {
	Blog:: edit_assigned_task(
		$blog_id,
		$_POST['task_user_id'],
		$_POST['task_task_id'],
		$_POST['task_day'],
		$_POST['old_user_id'],
		$_POST['old_task_id'],
		$_POST['old_target_date']
	);
	$return_message = array(
		'type' => 'confirmation',
		'message' => get_lang('AssignedTaskEdited')
	);
}
if (!empty($_POST['new_task_execution_submit'])) {
	Blog:: create_comment(
		$safe_comment_title,
		$safe_comment_text,
		$blog_id,
		(int)$_GET['post_id'],
		$_POST['comment_parent_id'],
		$_POST['task_id']
	);
	$return_message = array(
		'type' => 'confirmation',
		'message' => get_lang('CommentCreated')
	);
}
if (!empty($_POST['register'])) {
	if (is_array($_POST['user'])) {
		foreach ($_POST['user'] as $index => $user_id) {
			Blog :: set_user_subscribed((int)$_GET['blog_id'], $user_id);
		}
	}
}
if (!empty($_POST['unregister'])) {
	if (is_array($_POST['user'])) {
		foreach ($_POST['user'] as $index => $user_id) {
			Blog :: set_user_unsubscribed((int)$_GET['blog_id'], $user_id);
		}
	}
}
if (!empty($_GET['register'])) {
	Blog :: set_user_subscribed((int)$_GET['blog_id'], (int)$_GET['user_id']);
	$return_message = array('type' => 'confirmation', 'message' => get_lang('UserRegistered'));
	$flag = 1;
}
if (!empty($_GET['unregister'])) {
	Blog :: set_user_unsubscribed((int)$_GET['blog_id'], (int)$_GET['user_id']);
}

if (isset($_GET['action']) && $_GET['action'] == 'manage_tasks') {
	if (isset($_GET['do']) && $_GET['do'] == 'delete') {
		Blog :: delete_task($blog_id, (int)$_GET['task_id']);
		$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskDeleted'));
	}

	if (isset($_GET['do']) && $_GET['do'] == 'delete_assignment') {
		Blog :: delete_assigned_task($blog_id, intval($_GET['task_id']), intval($_GET['user_id']));
		$return_message = array('type' => 'confirmation', 'message' => get_lang('TaskAssignmentDeleted'));
	}
}

if (isset($_GET['action']) && $_GET['action'] == 'view_post') {
	$task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;

	if (isset($_GET['do']) && $_GET['do'] == 'delete_comment')	{
		if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_delete', $task_id)) {
			Blog :: delete_comment($blog_id, (int)$_GET['post_id'],(int)$_GET['comment_id']);
			$return_message = array('type' => 'confirmation', 'message' => get_lang('CommentDeleted'));
		} else {
			$error = true;
			$message = get_lang('ActionNotAllowed');
		}
	}

	if (isset($_GET['do']) && $_GET['do'] == 'delete_article')	{
		if (api_is_allowed('BLOG_'.$blog_id, 'article_delete', $task_id)) {
			Blog :: delete_post($blog_id, (int)$_GET['article_id']);
			$action = ''; // Article is gone, go to blog home
			$return_message = array('type' => 'confirmation', 'message' => get_lang('BlogDeleted'));
		} else {
			$error = true;
			$message = get_lang('ActionNotAllowed');
		}
	}
	if (isset($_GET['do']) && $_GET['do'] == 'rate') {
		if (isset($_GET['type']) && $_GET['type'] == 'post') {
			if (api_is_allowed('BLOG_'.$blog_id, 'article_rate')) {
				Blog :: add_rating('post', $blog_id, (int)$_GET['post_id'], (int)$_GET['rating']);
				$return_message = array('type' => 'confirmation', 'message' => get_lang('RatingAdded'));
			}
		}
		if (isset($_GET['type']) && $_GET['type'] == 'comment') {
			if (api_is_allowed('BLOG_'.$blog_id, 'article_comments_add')) {
				Blog :: add_rating('comment', $blog_id, (int)$_GET['comment_id'], (int)$_GET['rating']);
				$return_message = array('type' => 'confirmation', 'message' => get_lang('RatingAdded'));
			}
		}
	}
}
/*
	DISPLAY
*/

// Set breadcrumb
switch ($action) {
	case 'new_post' :
		$nameTools = get_lang('NewPost');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            "name" => Blog:: get_blog_title($blog_id),
        );
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'manage_tasks' :
		$nameTools = get_lang('TaskManager');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            "name" => Blog:: get_blog_title($blog_id),
        );
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'manage_members' :
		$nameTools = get_lang('MemberManager');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            "name" => Blog:: get_blog_title($blog_id),
        );
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'manage_rights' :
		$nameTools = get_lang('RightsManager');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            'name' => Blog:: get_blog_title($blog_id),
        );
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'view_search_result' :
		$nameTools = get_lang('SearchResults');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            'name' => Blog:: get_blog_title($blog_id),
        );
		Display :: display_header($nameTools, 'Blogs');
		break;
	case 'execute_task' :
		$nameTools = get_lang('ExecuteThisTask');
        $interbreadcrumb[] = array(
            'url' => "blog.php?blog_id=$blog_id&".api_get_cidreq(),
            'name' => Blog:: get_blog_title($blog_id),
        );
		Display :: display_header($nameTools, 'Blogs');
		break;
	default :
		$nameTools = Blog :: get_blog_title($blog_id);
		Display :: display_header($nameTools, 'Blogs');
}

// feedback messages
if (!empty($return_message)) {
	if ($return_message['type'] == 'confirmation') {
		Display::display_confirmation_message($return_message['message']);
	}
	if ($return_message['type'] == 'error') {
		Display::display_error_message($return_message['message']);
	}
}

// actions
echo '<div class=actions>';
?>
	<a href="<?php echo api_get_self(); ?>?blog_id=<?php echo $blog_id ?>&<?php echo api_get_cidreq(); ?>" title="<?php echo get_lang('Home') ?>">
    <?php echo Display::return_icon('blog.png', get_lang('Home'),'',ICON_SIZE_MEDIUM); ?></a>
	<?php if(api_is_allowed('BLOG_'.$blog_id, 'article_add')) { ?>
    <a href="<?php echo api_get_self(); ?>?action=new_post&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('NewPost') ?>">
    <?php echo Display::return_icon('new_article.png', get_lang('NewPost'),'',ICON_SIZE_MEDIUM); ?></a><?php } ?>
	<?php if(api_is_allowed('BLOG_'.$blog_id, 'task_management')) { ?>
    <a href="<?php echo api_get_self(); ?>?action=manage_tasks&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageTasks') ?>">
    <?php echo Display::return_icon('blog_tasks.png', get_lang('TaskManager'),'',ICON_SIZE_MEDIUM); ?></a><?php } ?>
	<?php if(api_is_allowed('BLOG_'.$blog_id, 'member_management')) { ?>
    <a href="<?php echo api_get_self(); ?>?action=manage_members&amp;blog_id=<?php echo $blog_id ?>" title="<?php echo get_lang('ManageMembers') ?>">
    <?php echo Display::return_icon('blog_admin_users.png', get_lang('MemberManager'),'',ICON_SIZE_MEDIUM); ?></a><?php } ?>
<?php
echo '</div>';

// Tool introduction
Display::display_introduction_section(TOOL_BLOGS);

?>
<div class="blog-title"><h1><?php echo Blog::get_blog_title($blog_id); ?></h1></div>
<div class="sectioncomment"><p><?php echo Blog::get_blog_subtitle($blog_id); ?></p></div>

<div class="row">
	<div class="col-md-3">
        <div class="panel panel-default">
            <div class="panel-heading"><?php echo get_lang('Calendar') ?></div>
            <div class="panel-body">
                <?php
                    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int) date('m');
                    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
                    Blog::display_minimonthcalendar($month, $year, $blog_id);
                ?>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><?php echo get_lang('Search') ?></div>
            <div class="panel-body">
                <form action="blog.php" method="get" enctype="multipart/form-data">
                    <div class="form-group">
                        <input type="hidden" name="blog_id" value="<?php echo $blog_id ?>" />
                        <input type="hidden" name="action" value="view_search_result" />
                        <input type="text" class="form-control" size="20" name="q" value="<?php echo isset($_GET['q']) ? Security::remove_XSS($_GET['q']) : ''; ?>" />
                    </div>
                    <button class="btn btn-default btn-block" type="submit">
                        <em class="fa fa-search"></em> <?php echo get_lang('Search'); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><?php echo get_lang('MyTasks') ?></div>
            <div class="panel-body">
                <?php Blog::get_personal_task_list(); ?>
            </div>
        </div>
	</div>
	<div class="col-md-9">
		<?php

if (isset($error)) {
	Display :: display_error_message($message);
}

if (isset($flag) && $flag == '1') {
	$action = "manage_tasks";
	Blog :: display_assign_task_form($blog_id);
}

$user_task = false;

$course_id = api_get_course_int_id();

if (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) {
	$task_id = (int)$_GET['task_id'];
} else {
	$task_id = 0;
	$tbl_blogs_tasks_rel_user = Database :: get_course_table(TABLE_BLOGS_TASKS_REL_USER);

	$sql = "SELECT COUNT(*) as number
			FROM ".$tbl_blogs_tasks_rel_user."
			WHERE
			    c_id = $course_id AND
				blog_id = ".$blog_id." AND
				user_id = ".api_get_user_id()." AND
				task_id = ".$task_id;

	$result = Database::query($sql);
	$row = Database::fetch_array($result);

	if ($row['number'] == 1)
		$user_task = true;
}

switch ($action) {
	case 'new_post':
		if (api_is_allowed('BLOG_'.$blog_id, 'article_add', $user_task ? $task_id : 0)) {
			// we show the form if
			// 1. no post data
			// 2. there is post data and the required field is empty
			if (!$_POST OR (!empty($_POST) AND empty($_POST['title']))) {
				// if there is post data there is certainly an error in the form
				if ($_POST) {
					Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'));
				}
			Blog :: display_form_new_post($blog_id);
		} else {
				if (isset($_GET['filter']) && !empty($_GET['filter'])) {
					Blog :: display_day_results($blog_id, Database::escape_string($_GET['filter']));
				} else {
					Blog :: display_blog_posts($blog_id);
				}
			}
		} else {
			api_not_allowed();
		}
		break;
	case 'view_post' :
		Blog :: display_post($blog_id, intval($_GET['post_id']));
		break;
	case 'edit_post' :
		$task_id = (isset ($_GET['task_id']) && is_numeric($_GET['task_id'])) ? $_GET['task_id'] : 0;

		if (api_is_allowed('BLOG_'.$blog_id, 'article_edit', $task_id)) {
			// we show the form if
			// 1. no post data
			// 2. there is post data and the required field is empty
			if (!$_POST OR (!empty($_POST) AND empty($_POST['post_title']))) {
				// if there is post data there is certainly an error in the form
				if ($_POST) {
					Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'));
				}
                Blog :: display_form_edit_post($blog_id, intval($_GET['post_id']));
			} else {
				if (isset ($_GET['filter']) && !empty ($_GET['filter'])) {
					Blog :: display_day_results($blog_id, Database::escape_string($_GET['filter']));
				} else {
					Blog :: display_blog_posts($blog_id);
				}
			}
		} else {
			api_not_allowed();
		}

		break;
	case 'manage_members' :
		if (api_is_allowed('BLOG_'.$blog_id, 'member_management')) {
			Blog :: display_form_user_subscribe($blog_id);
			echo '<br /><br />';
			Blog :: display_form_user_unsubscribe($blog_id);
		} else {
			api_not_allowed();
        }

		break;
	case 'manage_rights' :
		Blog :: display_form_user_rights($blog_id);
		break;
	case 'manage_tasks' :
		if (api_is_allowed('BLOG_'.$blog_id, 'task_management')) {
			if (isset($_GET['do']) && $_GET['do'] == 'add') {
				Blog:: display_new_task_form($blog_id);
			}
			if (isset($_GET['do']) && $_GET['do'] == 'assign') {
				Blog:: display_assign_task_form($blog_id);
			}
			if (isset($_GET['do']) && $_GET['do'] == 'edit') {
				Blog:: display_edit_task_form(
					$blog_id,
					intval($_GET['task_id'])
				);
			}
			if (isset($_GET['do']) && $_GET['do'] == 'edit_assignment') {
				Blog :: display_edit_assigned_task_form($blog_id, intval($_GET['task_id']), intval($_GET['user_id']));
			}
			Blog :: display_task_list($blog_id);
			echo '<br /><br />';
			Blog :: display_assigned_task_list($blog_id);
			echo '<br /><br />';
        } else {
            api_not_allowed();
        }

		break;
	case 'execute_task' :
        if (isset ($_GET['post_id'])) {
            Blog:: display_post($blog_id, intval($_GET['post_id']));
        } else {
            Blog:: display_select_task_post($blog_id, intval($_GET['task_id']));
        }
		break;
	case 'view_search_result' :
		Blog :: display_search_results($blog_id, Database::escape_string($_GET['q']));
		break;
    case '':
    default:
		if (isset ($_GET['filter']) && !empty ($_GET['filter'])) {
			Blog :: display_day_results($blog_id, Database::escape_string($_GET['filter']));
		} else {
			Blog :: display_blog_posts($blog_id);
		}
        break;
}
?>
</div>
</div>
<?php

Display::display_footer();
