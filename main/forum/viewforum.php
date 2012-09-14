<?php
/* For licensing terms, see /license.txt */

/**
 * These files are a complete rework of the forum. The database structure is
 * based on phpBB but all the code is rewritten. A lot of new functionalities
 * are added:
 * - forum categories and forums can be sorted up or down, locked or made invisible
 * - consistent and integrated forum administration
 * - forum options:     are students allowed to edit their post?
 *                      moderation of posts (approval)
 *                      reply only forums (students cannot create new threads)
 *                      multiple forums per group
 * - sticky messages
 * - new view option: nested view
 * - quoting a message
 *
 * @Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @Copyright Ghent University
 * @Copyright Patrick Cool
 *
 *  @package chamilo.forum
 */

use \ChamiloSession as Session;


// Language files that need to be included.
$language_file = array('forum', 'group');

// Including the global initialization file.
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_FORUM;

// Notification for unauthorized people.
api_protect_course_script(true);

// The section (tabs).
$this_section = SECTION_COURSES;

$nameTools = get_lang('ToolForum');

// Are we in a lp ?
$origin = '';
$origin_string = '';
if (isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
    $origin_string = '&amp;origin='.$origin;
}

/* Including necessary files */
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$userid  = api_get_user_id();

/* MAIN DISPLAY SECTION */

$group_id = api_get_group_id();

$my_forum = isset($_GET['forum']) ? $_GET['forum'] : '';

$current_forum = get_forum_information($my_forum); // Note: This has to be validated that it is an existing forum.

if (empty($current_forum)) {
    api_not_allowed();
}

$current_forum_category = get_forumcategory_information($current_forum['forum_category']);

$is_group_tutor = false;

if (!empty($group_id)) {
    //Group info & group category info
    $group_properties           = GroupManager::get_group_properties($group_id);
        
    //User has access in the group?
    $user_has_access_in_group   = GroupManager::user_has_access($userid, $group_id, GROUP_TOOL_FORUM);
    
    $is_group_tutor = GroupManager::is_tutor_of_group(api_get_user_id(), $group_id);
        
    //Course
    if (!api_is_allowed_to_edit(false, true) AND  //is a student
        (($current_forum_category && $current_forum_category['visibility'] == 0) OR $current_forum['visibility'] == 0 OR !$user_has_access_in_group)            
    ) {
        api_not_allowed();
    }
} else {
    //Course
    if (!api_is_allowed_to_edit(false, true) AND  //is a student
        (($current_forum_category && $current_forum_category['visibility'] == 0) OR $current_forum['visibility'] == 0) //forum category or forum visibility is false
    ) {
        api_not_allowed();
    }
}

/* Header and Breadcrumbs */

$my_search = isset($_GET['search']) ? $_GET['search'] : '';
$my_action = isset($_GET['action']) ? $_GET['action'] : '';

if (isset($_SESSION['gradebook'])){
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}

if (!empty($_GET['gidReq'])) {
    $toolgroup = Database::escape_string($_GET['gidReq']);
    Session::write('toolgroup',$toolgroup);
}

if ($origin == 'group') {    
    
    $interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array('url'=>'../group/group_space.php?gidReq='.$_SESSION['toolgroup'], 'name'=> get_lang('GroupSpace').' '.$group_properties['name']);
    $interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Forum').' '.Security::remove_XSS($current_forum['forum_title']));
} else {
    $interbreadcrumb[] = array('url' => 'index.php?gradebook='.$gradebook.'&amp;search='.Security::remove_XSS($my_search), 'name' => get_lang('ForumCategories'));
    $interbreadcrumb[] = array('url' => 'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id'].'&amp;search='.Security::remove_XSS(urlencode($my_search)), 'name' => prepare4display($current_forum_category['cat_title']));
    $interbreadcrumb[] = array('url' => '#', 'name' => Security::remove_XSS($current_forum['forum_title']));
}

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string.
    Display :: display_header('');    
}

/* Actions */
// Change visibility of a forum or a forum category.
if (($my_action == 'invisible' OR $my_action=='visible') AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit(false, true) && api_is_allowed_to_session_edit(false, true)) {
    $message = change_visibility($_GET['content'], $_GET['id'], $_GET['action']); // Note: This has to be cleaned first.
}
// Locking and unlocking.
if (($my_action == 'lock' OR $my_action == 'unlock') AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit(false, true) && api_is_allowed_to_session_edit(false, true)) {
    $message = change_lock_status($_GET['content'], $_GET['id'], $my_action); // Note: This has to be cleaned first.
}
// Deleting.
if ($my_action == 'delete' AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit(false, true) && api_is_allowed_to_session_edit(false, true)) {
    
    $locked = api_resource_is_locked_by_gradebook($_GET['id'], LINK_FORUM_THREAD);
    if ($locked == false) {    
        $message = delete_forum_forumcategory_thread($_GET['content'], $_GET['id']); // Note: This has to be cleaned first.
        // Delete link    
        require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
        $link_info = is_resource_in_course_gradebook(api_get_course_id(), 5 , intval($_GET['id']), api_get_session_id());
        $link_id = $link_info['id'];
        if ($link_info !== false) {
            remove_resource_from_course_gradebook($link_id);
        }
    }
}
// Moving.
if ($my_action == 'move' AND isset($_GET['thread']) AND api_is_allowed_to_edit(false, true) && api_is_allowed_to_session_edit(false, true)) {
    $message = move_thread_form();
}
// Notification.
if ($my_action == 'notify' AND isset($_GET['content']) AND isset($_GET['id']) && api_is_allowed_to_session_edit(false, true)) {
    $return_message = set_notification($_GET['content'], $_GET['id']);
    Display :: display_confirmation_message($return_message, false);
}

// Student list

if ($my_action == 'liststd' AND isset($_GET['content']) AND isset($_GET['id']) AND (api_is_allowed_to_edit(null, true) || $is_group_tutor)) {
    $active = null;
    switch ($_GET['list']) {
        case 'qualify':
            $student_list = get_thread_users_qualify($_GET['id']);
            $nrorow3 = -2;
            $active = 2;
            break;
        case 'notqualify':
            $student_list = get_thread_users_not_qualify($_GET['id']);
            $nrorow3 = -2;
            $active = 3;
            break;
        default:
            $student_list = get_thread_users_details($_GET['id']);
            $nrorow3 = Database::num_rows($student_list);
            $active = 1;
            break;
    }
    
    $table_list = Display::page_subheader(get_lang('ThreadUsersList').': '.get_name_thread_by_id($_GET['id']));
    
    if ($nrorow3 > 0 || $nrorow3 == -2) {
        $url = 'cidReq='.Security::remove_XSS($_GET['cidReq']).'&amp;forum='.Security::remove_XSS($my_forum).'&amp;action='.Security::remove_XSS($_GET['action']).'&amp;content='.Security::remove_XSS($_GET['content'],STUDENT).'&amp;id='.intval($_GET['id']);
        $tabs = array(
                        array('content' =>  get_lang('AllStudents'),
                               'url'    =>   'viewforum.php?'.$url.'&amp;origin='.$origin.'&amp;list=all'),
                        array('content' =>  get_lang('StudentsQualified'),
                               'url'    =>   'viewforum.php?'.$url.'&amp;origin='.$origin.'&amp;list=qualify'),
                        array('content' =>  get_lang('StudentsNotQualified'),
                               'url'    =>   'viewforum.php?'.$url.'&amp;origin='.$origin.'&amp;list=notqualify'),
            );
        $table_list .= Display::tabs_only_link($tabs, $active);

        $icon_qualify = 'blog_new.gif';
        $table_list .= '<center><br /><table class="data_table" style="width:50%">';
        // The column headers (TODO: Make this sortable).
        $table_list .= '<tr >';
        $table_list .= '<th height="24">'.get_lang('NamesAndLastNames').'</th>';

        if ($_GET['list'] == 'qualify') {
            $table_list.= '<th>'.get_lang('Qualification').'</th>';
        }
        if (api_is_allowed_to_edit(null, true)) {
            $table_list.= '<th>'.get_lang('Qualify').'</th>';
        }
        $table_list .= '</tr>';
        $max_qualify = show_qualify('2', $userid, $_GET['id']);
        $counter_stdlist = 0;

        if (Database::num_rows($student_list) > 0) {
            while ($row_student_list=Database::fetch_array($student_list)) {
                if ($counter_stdlist % 2 == 0) {
                    $class_stdlist = 'row_odd';
                } else {
                    $class_stdlist = 'row_even';
                }
                $name_user_theme = api_get_person_name($row_student_list['firstname'], $row_student_list['lastname']);
                $table_list .= '<tr class="'.$class_stdlist.'"><td><a href="../user/userInfo.php?uInfo='.$row_student_list['user_id'].'&amp;tipo=sdtlist&amp;'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).$origin_string.'">'.$name_user_theme.'</a></td>';
                if ($_GET['list'] == 'qualify') {
                    $table_list .= '<td>'.$row_student_list['qualify'].'/'.$max_qualify.'</td>';
                }
                if (api_is_allowed_to_edit(null, true)) {
                    $current_qualify_thread = show_qualify('1', $row_student_list['user_id'], $_GET['id']);
                    $table_list .= '<td><a href="forumqualify.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).'&amp;thread='.Security::remove_XSS($_GET['id']).'&amp;user='.$row_student_list['user_id'].'&amp;user_id='.$row_student_list['user_id'].'&amp;idtextqualify='.$current_qualify_thread.'&amp;origin='.$origin.'">'.Display::return_icon($icon_qualify, get_lang('Qualify')).'</a></td></tr>';
                }
                $counter_stdlist++;
            }
        } else {
            if ($_GET['list'] == 'qualify') {
                $table_list .= '<tr><td colspan="2">'.get_lang('ThereIsNotQualifiedLearners').'</td></tr>';
            } else {
                $table_list .= '<tr><td colspan="2">'.get_lang('ThereIsNotUnqualifiedLearners').'</td></tr>';
            }
        }

        $table_list .= '</table></center>';
        $table_list .= '<br />';
    } else {
        $table_list .= Display::return_message(get_lang('NoParticipation'), 'warning');
    }
}

if ($origin == 'learnpath') {
    echo '<div style="height:15px">&nbsp;</div>';
}

/* Display the action messages */

if (!empty($message)) {
    Display :: display_confirmation_message($message);
}

/* Action links */

echo '<div class="actions">';

if ($origin != 'learnpath') {
    
    if ($origin=='group') {
        echo '<a href="../group/group_space.php?'.api_get_cidreq().'&amp;gradebook='.$gradebook.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('Groups'),'',ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<span style="float:right;">'.search_link().'</span>';
        echo '<a href="index.php">'.Display::return_icon('back.png', get_lang('BackToForumOverview'), '', ICON_SIZE_MEDIUM).'</a>';
    }    
}


// The link should appear when
// 1. the course admin is here
// 2. the course member is here and new threads are allowed
// 3. a visitor is here and new threads AND allowed AND  anonymous posts are allowed
if (api_is_allowed_to_edit(false, true) OR ($current_forum['allow_new_threads'] == 1 AND isset($_user['user_id'])) OR ($current_forum['allow_new_threads'] == 1 AND !isset($_user['user_id']) AND $current_forum['allow_anonymous'] == 1)) {
	if ($current_forum['locked'] <> 1 AND $current_forum['locked'] <> 1) {
		if (!api_is_anonymous()) {
			if ($my_forum == strval(intval($my_forum))) {
				echo '<a href="newthread.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).$origin_string.'">'.Display::return_icon('new_thread.png',get_lang('NewTopic'),'',ICON_SIZE_MEDIUM).'</a>';
		    } else {
		    	$my_forum = strval(intval($my_forum));
				echo '<a href="newthread.php?'.api_get_cidreq().'&amp;forum='.$my_forum.$origin_string.'">'.Display::return_icon('new_thread.png',get_lang('NewTopic'),'',ICON_SIZE_MEDIUM).'</a>';
			}
		}
	} else {
		echo get_lang('ForumLocked');
	}
}
echo '</div>';


/* Display */

echo '<table class="forum_table" >';

// The current forum
if ($origin != 'learnpath') {
    echo '<thead><tr><th class="forum_head" colspan="7">';
    if (!empty ($current_forum_category['cat_title'])) {
        //echo '<span class="forum_low_description">'.prepare4display($current_forum_category['cat_title'])."</span><br />";
    }
    echo '<span class="forum_title">'.prepare4display($current_forum['forum_title']).'</span>';
    if (!empty ($current_forum['forum_comment'])) {
        echo '<br /><span class="forum_description">'.prepare4display($current_forum['forum_comment']).'</span>';
    }
    echo '</th></tr></thead>';
}

// The column headers (TODO: Make this sortable).
echo '<tr class="forum_threadheader">';
echo '<td></td>';
echo '<td>'.get_lang('Title').'</td>';
echo '<td>'.get_lang('Replies').'</td>';
echo '<td>'.get_lang('Views').'</td>';
echo '<td>'.get_lang('Author').'</td>';
echo '<td>'.get_lang('LastPost').'</td>';
echo '<td>'.get_lang('Actions').'</td>';
echo '</tr>';

// Getting al the threads
$threads = get_threads($my_forum); // Note: This has to be cleaned first.


$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;

$course_id = api_get_course_int_id();

$counter = 0;
if (is_array($threads)) {
    foreach ($threads as $row) {
        // Thread who have no replies yet and the only post is invisible should not be displayed to students.
        if (api_is_allowed_to_edit(false, true) OR !($row['thread_replies'] == '0' AND $row['visible'] == '0')) {
            if ($counter % 2 == 0) {
                 $class = 'row_odd';
            } else {
                $class = 'row_even';
            }
            echo "<tr class=\"$class\">";
            echo '<td>';
            $my_whatsnew_post_info = isset($whatsnew_post_info[$my_forum][$row['thread_id']]) ? $whatsnew_post_info[$my_forum][$row['thread_id']] : null;
            if (is_array($my_whatsnew_post_info) && !empty($my_whatsnew_post_info)) {
                echo Display::return_icon('forumthread.gif');
            } else {
                echo Display::return_icon('forumthread.gif');
            }

            if ($row['thread_sticky'] == 1) {
                echo Display::return_icon('exclamation.gif');
            }
            echo '</td>';
            echo '<td>';
            echo '<a href="viewthread.php?'.api_get_cidreq().'&amp;gradebook='.Security::remove_XSS($_GET['gradebook']).'&amp;forum='.Security::remove_XSS($my_forum).'&amp;origin='.$origin.'&amp;thread='.$row['thread_id'].$origin_string.'&amp;search='.Security::remove_XSS(urlencode($my_search)).'" '.class_visible_invisible($row['visibility']).'>'.prepare4display($row['thread_title']).'</a></td>';
            echo '<td>'.$row['thread_replies'].'</td>';
            echo '<td>'.$row['thread_views'].'</td>';
            // display the author name
            $tab_poster_info = api_get_user_info($row['user_id']);
            $poster_username = sprintf(get_lang('LoginX'), $tab_poster_info['username']);
            if ($origin != 'learnpath') {
                echo '<td>'.display_user_link($row['user_id'], api_get_person_name($row['firstname'], $row['lastname']), '', $poster_username).'</td>';
            } else {
                echo '<td>'.Display::tag('span', api_get_person_name($row['firstname'], $row['lastname']), array("title"=>api_htmlentities($poster_username, ENT_QUOTES))).'</td>';
            }            
            
            if ($row['last_poster_user_id'] == '0') {
                $name = $row['poster_name'];
                $last_poster_username = "";
            } else {
                $name = api_get_person_name($row['last_poster_firstname'], $row['last_poster_lastname']);
                $tab_last_poster_info = api_get_user_info($row['last_poster_user_id']);
                $last_poster_username = sprintf(get_lang('LoginX'), $tab_last_poster_info['username']);
            }
            // If the last post is invisible and it is not the teacher who is looking then we have to find the last visible post of the thread.
            if (($row['visible'] == '1' OR api_is_allowed_to_edit(false, true)) && $origin != 'learnpath') {
                $last_post = api_convert_and_format_date($row['thread_date']).' '.get_lang('By').' '.display_user_link($row['last_poster_user_id'], $name, '', $last_poster_username);
            } elseif ($origin != 'learnpath') {
                $last_post_sql = "SELECT post.*, user.firstname, user.lastname, user.username FROM $table_posts post, $table_users user WHERE post.poster_id=user.user_id AND visible='1' AND thread_id='".$row['thread_id']."' AND post.c_id=".api_get_course_int_id()." ORDER BY post_id DESC";
                $last_post_result = Database::query($last_post_sql);
                $last_post_row = Database::fetch_array($last_post_result);
                $name = api_get_person_name($last_post_row['firstname'], $last_post_row['lastname']);
                $last_post_info_username = sprintf(get_lang('LoginX'), $last_post_row['username']);
                $last_post = api_convert_and_format_date($last_post_row['post_date']).' '.get_lang('By').' '.display_user_link($last_post_row['poster_id'], $name, '', $last_post_info_username);
            } else {
                $last_post_sql = "SELECT post.*, user.firstname, user.lastname, user.username FROM $table_posts post, $table_users user WHERE post.poster_id=user.user_id AND visible='1' AND thread_id='".$row['thread_id']."' AND post.c_id=".api_get_course_int_id()." ORDER BY post_id DESC";
                $last_post_result = Database::query($last_post_sql);
                $last_post_row = Database::fetch_array($last_post_result);
                $last_post_info_username = sprintf(get_lang('LoginX'), $last_post_row['username']);
                $name = api_get_person_name($last_post_row['firstname'], $last_post_row['lastname']);
                $last_post = api_convert_and_format_date($last_post_row['post_date']).' '.get_lang('By').' '.Display::tag('span', $name, array("title"=>api_htmlentities($last_post_info_username, ENT_QUOTES)));
            }

            echo '<td>'.$last_post.'</td>';
            echo '<td class="td_actions">';
            // Get attachment id.
            $attachment_list = get_attachment($row['post_id']);
            $id_attach = !empty($attachment_list) ? $attachment_list['id'] : '';

            $sql_post_id = "SELECT post_id FROM $table_posts WHERE c_id = $course_id AND post_title='".Database::escape_string($row['thread_title'])."'";
            $result_post_id = Database::query($sql_post_id);
            $row_post_id = Database::fetch_array($result_post_id);

            if ($origin != 'learnpath') {
                if (api_is_allowed_to_edit(false, true) && !(api_is_course_coach() && $current_forum['session_id'] != $_SESSION['id_session'])) {
                    echo '<a href="editpost.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).'&amp;thread='.Security::remove_XSS($row['thread_id']).'&amp;post='.$row_post_id['post_id'].'&amp;gidReq='.$_SESSION['toolgroup'].'&amp;origin='.$origin.'&amp;id_attach='.$id_attach.'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL).'</a>';
                    
                    if (api_resource_is_locked_by_gradebook($row['thread_id'], LINK_FORUM_THREAD)) {
                        echo Display::return_icon('delete_na.png', get_lang('ResourceLockedByGradebook'), array(), ICON_SIZE_SMALL);
                    } else {
                        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).'&amp;action=delete&amp;content=thread&amp;gidReq='.$_SESSION['toolgroup'].'&amp;id='.$row['thread_id'].$origin_string."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('DeleteCompleteThread'), ENT_QUOTES))."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a>';
                    }
                    
                    display_visible_invisible_icon('thread', $row['thread_id'], $row['visibility'], array('forum' => $my_forum, 'origin' => $origin, 'gidReq' => $_SESSION['toolgroup']));
                    display_lock_unlock_icon('thread', $row['thread_id'], $row['locked'], array('forum' => $my_forum, 'origin' => $origin, 'gidReq' => $_SESSION['toolgroup']));
                    echo '<a href="viewforum.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).'&amp;action=move&amp;gidReq='.$_SESSION['toolgroup'].'&amp;thread='.$row['thread_id'].$origin_string.'">'.Display::return_icon('move.png', get_lang('MoveThread'), array(), ICON_SIZE_SMALL).'</a>';
                }
            }
            $iconnotify = 'send_mail.gif';
            if (is_array(isset($_SESSION['forum_notification']['thread']) ? $_SESSION['forum_notification']['thread'] : null)) {
                if (in_array($row['thread_id'], $_SESSION['forum_notification']['thread'])) {
                    $iconnotify = 'send_mail_checked.gif';
                }
            }
            $icon_liststd = 'user.png';
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).'&amp;origin='.$origin.'&amp;action=notify&amp;content=thread&amp;gidReq='.$_SESSION['toolgroup'].'&amp;id='.$row['thread_id'].'">'.Display::return_icon($iconnotify, get_lang('NotifyMe')).'</a>';
            }

            if (api_is_allowed_to_edit(null,true) && $origin != 'learnpath') {
                echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).'&amp;origin='.$origin.'&amp;action=liststd&amp;content=thread&amp;gidReq='.$_SESSION['toolgroup'].'&amp;id='.$row['thread_id'].'">'.Display::return_icon($icon_liststd,get_lang('StudentList'), array(), ICON_SIZE_SMALL).'</a>';
            }
            echo '</td></tr>';
        }
        $counter++;
    }
}
echo '</table>';
echo isset($table_list) ? $table_list : '';

/* FOOTER */

if ($origin != 'learnpath') {
    Display :: display_footer();
}