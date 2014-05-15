<?php
/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com> UI Improvements + lots of bugfixes
 * @package chamilo.forum
 */

// Language file that needs to be included.
$language_file = array ('forum', 'group');

// Including the global initialization file.
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_FORUM;

// The section (tabs.)
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$nameTools = get_lang('Forum');
$forumUrl = api_get_path(WEB_CODE_PATH).'forum/';

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
}
$my_search = null;
$gradebook = null;

/* MAIN DISPLAY SECTION */

/* Retrieving forum and forum category information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
// Note: This has to be validated that it is an existing thread
$current_thread	= get_thread_information($_GET['thread']);
// Note: This has to be validated that it is an existing forum.
$current_forum	= get_forum_information($current_thread['forum_id']);
$current_forum_category	= get_forumcategory_information($current_forum['forum_category']);
$whatsnew_post_info	= isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null; // This variable should be deprecated?

/* Header and Breadcrumbs */

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
        'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

if ($origin == 'group') {
    $session_toolgroup = api_get_group_id();
    $group_properties = GroupManager :: get_group_properties($session_toolgroup);
    $interbreadcrumb[] = array('url'=>'../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url'=>'../group/group_space.php?gidReq='.$session_toolgroup, 'name'=> get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array('url'=>'viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.$session_toolgroup.'&amp;origin='.$origin.'&amp;search='.Security::remove_XSS(urlencode($my_search)), 'name' => Security::remove_XSS($current_forum['forum_title']));
    $interbreadcrumb[] = array('url'=>'viewthread.php?forum='.Security::remove_XSS($_GET['forum']).'&amp;gradebook='.$gradebook.'&amp;thread='.Security::remove_XSS($_GET['thread']), 'name' => Security::remove_XSS($current_thread['thread_title']));

    Display :: display_header('');
} else {
    $my_search = isset($_GET['search']) ? $_GET['search'] : '';
    if ($origin == 'learnpath') {
        Display::display_reduced_header();
    } else {
        $interbreadcrumb[] = array('url' => 'index.php?'.(isset($gradebook)?'gradebook='.$gradebook.'&amp;':'').'search='.Security::remove_XSS(urlencode($my_search)), 'name' => $nameTools);
        $interbreadcrumb[] = array('url' => 'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id'].'&amp;origin='.$origin.'&amp;search='.Security::remove_XSS(urlencode($my_search)), 'name' => Security::remove_XSS($current_forum_category['cat_title']));
        $interbreadcrumb[] = array('url' => 'viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'&amp;origin='.$origin.'&amp;search='.Security::remove_XSS(urlencode($my_search)), 'name' => Security::remove_XSS($current_forum['forum_title']));
        $interbreadcrumb[] = array('url' => '#', 'name' => Security::remove_XSS($current_thread['thread_title']));

        $message = isset($message) ? $message : '';
        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        Display :: display_header('');
    }
}

/* Is the user allowed here? */

// If the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) AND ($current_forum['visibility'] == 0 OR $current_thread['visibility'] == 0)) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}

/* Actions */
$group_id = api_get_group_id();
$my_action = isset($_GET['action']) ? $_GET['action'] : '';
if ($my_action == 'delete' AND isset($_GET['content']) AND isset($_GET['id']) AND (api_is_allowed_to_edit(false, true) OR GroupManager::is_tutor_of_group(api_get_user_id(), $group_id))) {
    $message = delete_post($_GET['id']);
}
if (($my_action == 'invisible' OR $my_action == 'visible') AND isset($_GET['id']) AND (api_is_allowed_to_edit(false, true) OR GroupManager::is_tutor_of_group(api_get_user_id(), $group_id))) {
    $message = approve_post($_GET['id'], $_GET['action']);
}
if ($my_action == 'move' AND isset($_GET['post'])) {
    $message = move_post_form();
}

/* Display the action messages */

$my_message = isset($message) ? $message : '';
if ($my_message) {
    Display :: display_confirmation_message(get_lang($my_message));
}

if ($my_message != 'PostDeletedSpecial') {
    // in this case the first and only post of the thread is removed
    // this increases the number of times the thread has been viewed
    increase_thread_view($_GET['thread']);

    /* Action Links */

    if ($origin == 'learnpath') {
        echo '<div style="height:15px">&nbsp;</div>';
    }
    echo '<div class="actions">';
    echo '<span style="float:right;">'.search_link().'</span>';
    if ($origin != 'learnpath') {
        echo '<a href="'.$forumUrl.'viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'&'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('BackToForum'), '', ICON_SIZE_MEDIUM).'</a>';

    }
    // The reply to thread link should only appear when the forum_category is not locked AND the forum is not locked AND the thread is not locked.
    // If one of the three levels is locked then the link should not be displayed.
    if (($current_forum_category && $current_forum_category['locked'] == 0) AND $current_forum['locked'] == 0 AND $current_thread['locked'] == 0 OR api_is_allowed_to_edit(false, true)) {
        // The link should only appear when the user is logged in or when anonymous posts are allowed.
        if ($_user['user_id'] OR ($current_forum['allow_anonymous'] == 1 AND !$_user['user_id'])) {
            // reply link
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="'.$forumUrl.'reply.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&thread='.Security::remove_XSS($_GET['thread']).'&amp;action=replythread">'.
                    Display::return_icon('reply_thread.png', get_lang('ReplyToThread'), '', ICON_SIZE_MEDIUM).'</a>';
            }
            // new thread link
            if ((api_is_allowed_to_edit(false, true) && !(api_is_course_coach() && $current_forum['session_id'] != $_SESSION['id_session'])) OR ($current_forum['allow_new_threads'] == 1 AND isset($_user['user_id'])) OR ($current_forum['allow_new_threads'] == 1 AND !isset($_user['user_id']) AND $current_forum['allow_anonymous'] == 1)) {
                if ($current_forum['locked'] <> 1 AND $current_forum['locked'] <> 1) {
                    echo '&nbsp;&nbsp;';
                } else {
                    echo get_lang('ForumLocked');
                }
            }
        }
    }

    // The different views of the thread.
    if ($origin != 'learnpath') {
        $my_url = '<a href="'.$forumUrl.'viewthread.php?'.api_get_cidreq().'&'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&thread='.Security::remove_XSS($_GET['thread']).'&search='.Security::remove_XSS(urlencode($my_search));
        echo $my_url.'&amp;view=flat">'.Display::return_icon('forum_listview.gif', get_lang('FlatView')).get_lang('FlatView').'</a>';
        echo $my_url.'&amp;view=threaded">'.Display::return_icon('forum_threadedview.gif', get_lang('ThreadedView')).get_lang('ThreadedView').'</a>';
        echo $my_url.'&amp;view=nested">'.Display::return_icon('forum_nestedview.gif', get_lang('NestedView')).get_lang('NestedView').'</a>';
    }
    $my_url = null;

    echo '</div>&nbsp;';

    /* Display Forum Category and the Forum information */

    if (!isset($_SESSION['view']))	{
        $viewmode = $current_forum['default_view'];
    } else {
        $viewmode = $_SESSION['view'];
    }

    $viewmode_whitelist = array('flat', 'threaded', 'nested');
    if (isset($_GET['view']) && in_array($_GET['view'], $viewmode_whitelist)) {
        $viewmode = $_GET['view'];
        $_SESSION['view'] = $viewmode;
    }
    if (empty($viewmode)) {
        $viewmode = 'flat';
    }

    if (isset($_GET['msg']) && isset($_GET['type'])) {
    	switch($_GET['type']) {
    		case 'error':
    			Display::display_error_message($_GET['msg']);
    			break;
    		case 'confirmation':
    			Display::display_confirmation_message($_GET['msg']);
    			break;
    	}
    }
    switch ($viewmode) {
        case 'flat':
            include_once 'viewthread_flat.inc.php';
            break;
        case 'threaded':
            include_once 'viewthread_threaded.inc.php';
            break;
        case 'nested':
            include_once 'viewthread_nested.inc.php';
            break;
        default:
            include_once 'viewthread_flat.inc.php';
            break;
    }
}

/* FOOTER */

if ($origin != 'learnpath') {
    Display :: display_footer();
}
