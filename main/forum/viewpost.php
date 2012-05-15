<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.forum
 */
// Language file that needs to be included.
$language_file = 'forum';

// Including the global initialization file.
require_once '../inc/global.inc.php';

// The section (tabs).
$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

$nameTools = get_lang('ToolForum');

// Including necessary files.
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$htmlHeadXtra[] = '<script language="javascript">
                                        $(document).ready(function(){ $(\'.hide-me\').slideUp() });
                                    function hidecontent(content){ $(content).slideToggle(\'normal\'); }
                                    </script>';

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
}

/* MAIN DISPLAY SECTION */

/* Retrieving forum and forum categorie information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table.
$current_thread = get_thread_information($_GET['thread']); // Note: This has to be validated that it is an existing thread.
$current_forum = get_forum_information($current_thread['forum_id']); // Note: This has to be validated that it is an existing forum.
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$whatsnew_post_info = $_SESSION['whatsnew_post_info'];

/* Header and Breadcrumbs */

if (isset($_SESSION['gradebook'])){
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    $interbreadcrumb[] = array('url' => 'index.php?gradebook='.$gradebook.'&amp;search='.Security::remove_XSS(urlencode($_GET['search'])), 'name' => $nameTools);
    $interbreadcrumb[] = array('url' => 'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id'].'&amp;search='.Security::remove_XSS(urlencode($_GET['search'])), 'name' => prepare4display($current_forum_category['cat_title']));
    $interbreadcrumb[] = array('url' => 'viewforum.php?forum='.Security::remove_XSS($_GET['forum']).'&amp;origin='.$origin.'&amp;search='.Security::remove_XSS(urlencode($_GET['search'])), 'name' => prepare4display($current_forum['forum_title']));

    // the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
    Display :: display_header('');
    api_display_tool_title($nameTools);
}

/* Is the user allowed here? */

// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) AND ($current_forum['visibility'] == 0 OR $current_thread['visibility'] == 0)) {
    $forum_allow = forum_not_allowed_here();
    if ($forum_allow === false) {
        exit;
    }
}

/* Actions */

if ($_GET['action'] == 'delete' && isset($_GET['content']) && isset($_GET['id']) && api_is_allowed_to_edit(false, true)) {
    $message = delete_post($_GET['id']); // Note: This has to be cleaned first.
}
if (($_GET['action'] == 'invisible' || $_GET['action'] == 'visible') && isset($_GET['id']) && api_is_allowed_to_edit(false, true)) {
    $message = approve_post($_GET['id'], $_GET['action']); // Note: This has to be cleaned first.
}
if ($_GET['action'] == 'move' && isset($_GET['post'])) {
    $message = move_post_form();
}

/* Display the action messages */

if (!empty($message)) {
    Display :: display_confirmation_message(get_lang($message));
}

if ($message != 'PostDeletedSpecial') { // In this case the first and only post of the thread is removed.
    // This increases the number of times the thread has been viewed.
    increase_thread_view($_GET['thread']);

    /* Action Links */

    echo '<div style="float:right;">';
    $my_url = '<a href="viewthread.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;origin='.$origin.'&amp;gradebook='.$gradebook.'&amp;search='.Security::remove_XSS(urlencode($_GET['search']));
    echo $my_url.'&amp;view=flat&amp;origin='.$origin.'">'.get_lang('FlatView').'</a> | ';
    echo $my_url.'&amp;view=threaded&amp;origin='.$origin.'">'.get_lang('ThreadedView').'</a> | ';
    echo $my_url.'&amp;view=nested&amp;origin='.$origin.'">'.get_lang('NestedView').'</a>';
    $my_url = null;
    echo '</div>';
    // The reply to thread link should only appear when the forum_category is not locked AND the forum is not locked AND the thread is not locked.
    // If one of the three levels is locked then the link should not be displayed.
    if (($current_forum_category && $current_forum_category['locked'] == 0) AND $current_forum['locked'] == 0 AND $current_thread['locked'] == 0 OR api_is_allowed_to_edit(false, true)) {
        // The link should only appear when the user is logged in or when anonymous posts are allowed.
        if ($_user['user_id'] OR ($current_forum['allow_anonymous'] == 1 AND !$_user['user_id'])) {
            // reply link
            echo '<a href="reply.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;action=replythread&amp;origin='.$origin.'">'.get_lang('ReplyToThread').'</a>';

            // new thread link
            if (api_is_allowed_to_edit(false, true) OR ($current_forum['allow_new_threads'] == 1 AND isset($_user['user_id'])) OR ($current_forum['allow_new_threads'] == 1 AND !isset($_user['user_id']) AND $current_forum['allow_anonymous'] == 1)) {
                if ($current_forum['locked'] <> 1 AND $current_forum['locked'] <> 1) {
                    echo '&nbsp;&nbsp;';
                    /*echo '<a href="newthread.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).$origin_string.'">'.Display::return_icon('new_thread.png','','',ICON_SIZE_MEDIUM).'</a>';*/
                } else {
                    echo get_lang('ForumLocked');
                }
            }
        }
    }
    // Note: This is to prevent that some browsers display the links over the table (FF does it but Opera doesn't).
    echo '&nbsp;';

    /* Display Forum Category and the Forum information */

    if (!$_SESSION['view']) {
        $viewmode = $current_forum['default_view'];
    } else {
        $viewmode = $_SESSION['view'];
    }

    $viewmode_whitelist=array('flat', 'threaded', 'nested');
    if (isset($_GET['view']) && in_array($_GET['view'], $viewmode_whitelist)) {
        $viewmode = Database::escape_string($_GET['view']);
        $_SESSION['view'] = $viewmode;
    }
    if (empty($viewmode)) {
        $viewmode = 'flat';
    }

    /* Display Forum Category and the Forum information */

    // we are getting all the information about the current forum and forum category.
    // note pcool: I tried to use only one sql statement (and function) for this
    // but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table
    echo "<table class=\"data_table\" width=\"100%\">\n";

    // The thread
    echo "\t<tr>\n\t\t<th style=\"padding-left:5px;\" align=\"left\" colspan=\"6\">";
    echo '<span class="forum_title">'.prepare4display($current_thread['thread_title']).'</span><br />';

    if ($origin!='learnpath') {
        echo '<span class="forum_low_description">'.prepare4display($current_forum_category['cat_title']).' - ';
    }

    echo prepare4display($current_forum['forum_title']).'<br />';
    echo "</th>\n";
    echo "\t</tr>\n";
    echo '<span>'.prepare4display($current_thread['thread_comment']).'</span>';
    echo "</table>";

    include_once('viewpost.inc.php');
} // if ($message != 'PostDeletedSpecial') // In this case the first and only post of the thread is removed.

/* FOOTER */

if ($origin != 'learnpath') {
    Display :: display_footer();
}
