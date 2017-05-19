<?php
/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com> UI Improvements + lots of bugfixes
 * @package chamilo.forum
 */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_FORUM;

$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

require_once 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$nameTools = get_lang('Forum');
$forumUrl = api_get_path(WEB_CODE_PATH).'forum/';

// Are we in a lp ?
$origin = api_get_origin();
$my_search = null;
$gradebook = null;

/* MAIN DISPLAY SECTION */

/* Retrieving forum and forum category information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum category are stored in the item_property table.
// Note: This has to be validated that it is an existing thread
$current_thread = get_thread_information($_GET['forum'], $_GET['thread']);
// Note: This has to be validated that it is an existing forum.
$current_forum = get_forum_information($current_thread['forum_id']);
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;
/* Header and Breadcrumbs */

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$groupId = api_get_group_id();
$group_properties = GroupManager::get_group_properties($groupId);
$sessionId = api_get_session_id();

$ajaxURL = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq().'&a=change_post_status';
$htmlHeadXtra[] = '<script>
$(function() {
    $("span").on("click", ".change_post_status", function() {
        var updateDiv = $(this).parent();
        var postId = updateDiv.attr("id");
                
        $.ajax({
            url: "'.$ajaxURL.'&post_id="+postId,
            type: "GET",
            success: function(data) {
                updateDiv.html(data);
            }                
        });
    });  
});
    
</script>';

if (!empty($groupId)) {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups')
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group_properties['name']
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.intval($_GET['forum']).'&'.api_get_cidreq()."&search=".Security::remove_XSS(urlencode($my_search)),
        'name' => Security::remove_XSS($current_forum['forum_title'])
    );
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewthread.php?forum='.intval($_GET['forum']).'&'.api_get_cidreq().'&thread='.intval($_GET['thread']),
        'name' => Security::remove_XSS($current_thread['thread_title'])
    );

    Display::display_header('');
} else {
    $my_search = isset($_GET['search']) ? $_GET['search'] : '';
    if ($origin == 'learnpath') {
        Display::display_reduced_header();
    } else {
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&search='.Security::remove_XSS(urlencode($my_search)),
            'name' => $nameTools
        );
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'forum/viewforumcategory.php?forumcategory='.$current_forum_category['cat_id']."&search=".Security::remove_XSS(urlencode($my_search)),
            'name' => Security::remove_XSS($current_forum_category['cat_title'])
        );
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.intval($_GET['forum'])."&search=".Security::remove_XSS(urlencode($my_search)),
            'name' => Security::remove_XSS($current_forum['forum_title'])
        );
        $interbreadcrumb[] = array(
            'url' => '#', 'name' => Security::remove_XSS($current_thread['thread_title'])
        );

        $message = isset($message) ? $message : '';
        // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
        Display::display_header('');
    }
}

/* Is the user allowed here? */

// If the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) &&
    ($current_forum['visibility'] == 0 || $current_thread['visibility'] == 0)
) {
    api_not_allowed(false);
}

/* Actions */
$my_action = isset($_GET['action']) ? $_GET['action'] : '';
if ($my_action == 'delete' &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    (api_is_allowed_to_edit(false, true) ||
        (isset($group_properties['iid']) && GroupManager::is_tutor_of_group(api_get_user_id(), $group_properties)))
) {
    $message = delete_post($_GET['id']);
}
if (($my_action == 'invisible' || $my_action == 'visible') &&
    isset($_GET['id']) &&
    (api_is_allowed_to_edit(false, true) ||
        (isset($group_properties['iid']) && GroupManager::is_tutor_of_group(api_get_user_id(), $group_properties)))
) {
    $message = approve_post($_GET['id'], $_GET['action']);
}
if ($my_action == 'move' && isset($_GET['post'])) {
    $message = move_post_form();
}

/* Display the action messages */

$my_message = isset($message) ? $message : '';
if ($my_message) {
    echo Display::return_message(get_lang($my_message), 'confirm');
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
        echo '<a href="'.$forumUrl.'viewforum.php?forum='
            . intval($_GET['forum']).'&'.api_get_cidreq().'">'
            . Display::return_icon('back.png', get_lang('BackToForum'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    // The reply to thread link should only appear when the forum_category is
    // not locked AND the forum is not locked AND the thread is not locked.
    // If one of the three levels is locked then the link should not be displayed.
    if (($current_forum_category &&
        $current_forum_category['locked'] == 0) &&
        $current_forum['locked'] == 0 &&
        $current_thread['locked'] == 0 ||
        api_is_allowed_to_edit(false, true)
    ) {
        // The link should only appear when the user is logged in or when anonymous posts are allowed.
        if ($_user['user_id'] || ($current_forum['allow_anonymous'] == 1 && !$_user['user_id'])) {
            // reply link
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="'.$forumUrl.'reply.php?'.api_get_cidreq().'&forum='
                    . intval($_GET['forum']).'&thread='
                    . intval($_GET['thread']).'&action=replythread">'
                    . Display::return_icon('reply_thread.png', get_lang('ReplyToThread'), '', ICON_SIZE_MEDIUM)
                    . '</a>';
            }
            // new thread link
            if ((
                    api_is_allowed_to_edit(false, true) &&
                    !(api_is_course_coach() && $current_forum['session_id'] != $sessionId)
                ) ||
                ($current_forum['allow_new_threads'] == 1 && isset($_user['user_id'])) ||
                ($current_forum['allow_new_threads'] == 1 && !isset($_user['user_id']) && $current_forum['allow_anonymous'] == 1)
            ) {
                if ($current_forum['locked'] <> 1 && $current_forum['locked'] <> 1) {
                    echo '&nbsp;&nbsp;';
                } else {
                    echo get_lang('ForumLocked');
                }
            }
        }
    }

    // The different views of the thread.
    if ($origin != 'learnpath') {
        $my_url = '<a href="'.$forumUrl.'viewthread.php?'.api_get_cidreq().'&'.api_get_cidreq()
            . '&forum='.intval($_GET['forum']).'&thread='.intval($_GET['thread'])
            . '&search='.Security::remove_XSS(urlencode($my_search));
        echo $my_url.'&view=flat">'
            . Display::return_icon('forum_listview.png', get_lang('FlatView'), null, ICON_SIZE_MEDIUM)
            . '</a>';
        echo $my_url.'&view=nested">'
            . Display::return_icon('forum_nestedview.png', get_lang('NestedView'), null, ICON_SIZE_MEDIUM)
            . '</a>';
    }
    $my_url = null;

    echo '</div>&nbsp;';

    /* Display Forum Category and the Forum information */
    if (!isset($_SESSION['view'])) {
        $viewMode = $current_forum['default_view'];
    } else {
        $viewMode = $_SESSION['view'];
    }

    $whiteList = array('flat', 'threaded', 'nested');
    if (isset($_GET['view']) && in_array($_GET['view'], $whiteList)) {
        $viewMode = $_GET['view'];
        $_SESSION['view'] = $viewMode;
    }
    if (empty($viewMode)) {
        $viewMode = 'flat';
    }

    if ($current_thread['thread_peer_qualify'] == 1) {
        echo Display::return_message(get_lang('ForumThreadPeerScoringStudentComment'), 'info');
    }

    switch ($viewMode) {
        case 'threaded':
            //no break;
        case 'nested':
            include_once 'viewthread_nested.inc.php';
            break;
        case 'flat':
            //no break
        default:
            include_once 'viewthread_flat.inc.php';
            break;
    }
}

if ($origin != 'learnpath') {
    Display::display_footer();
}
