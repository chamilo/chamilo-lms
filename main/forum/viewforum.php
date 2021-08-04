<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CForumPost;

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
 * - quoting a message.
 *
 * @Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @Copyright Ghent University
 * @Copyright Patrick Cool
 *
 *  @package chamilo.forum
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_FORUM;

// Notification for unauthorized people.
api_protect_course_script(true);
api_protect_course_group(GroupManager::GROUP_TOOL_FORUM);

// The section (tabs).
$this_section = SECTION_COURSES;
$nameTools = get_lang('ToolForum');

// Are we in a lp ?
$origin = api_get_origin();

require_once 'forumfunction.inc.php';

$userId = api_get_user_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$courseId = api_get_course_int_id();
$groupInfo = GroupManager::get_group_properties($groupId);
$isTutor = GroupManager::is_tutor_of_group($userId, $groupInfo, $courseId);
$isAllowedToEdit = api_is_allowed_to_edit(false, true) && api_is_allowed_to_session_edit(false, true);

/* MAIN DISPLAY SECTION */

$my_forum = isset($_GET['forum']) ? (int) $_GET['forum'] : '';
// Note: This has to be validated that it is an existing forum.
$current_forum = get_forum_information($my_forum);
$isForumOpenByDateAccess = api_is_date_in_date_range($current_forum['start_time'], $current_forum['end_time']);

if (!$isForumOpenByDateAccess && !$isAllowedToEdit) {
    if ($origin) {
        api_not_allowed(true);
    } else {
        api_not_allowed(true);
    }
}

if (empty($current_forum)) {
    api_not_allowed();
}

$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$is_group_tutor = false;

if (!empty($groupId)) {
    //Group info & group category info
    $group_properties = GroupManager::get_group_properties($groupId);
    $is_group_tutor = GroupManager::is_tutor_of_group(
        api_get_user_id(),
        $group_properties
    );

    // Course
    if (!api_is_allowed_to_edit(false, true) && //is a student
        (
            ($current_forum_category && $current_forum_category['visibility'] == 0) ||
            $current_forum['visibility'] == 0
        )
    ) {
        api_not_allowed(true);
    }
} else {
    // Course
    if (!api_is_allowed_to_edit(false, true) && (
        ($current_forum_category && $current_forum_category['visibility'] == 0) ||
        $current_forum['visibility'] == 0
        ) //forum category or forum visibility is false
    ) {
        api_not_allowed(true);
    }
}

/* Header and Breadcrumbs */
$my_search = isset($_GET['search']) ? $_GET['search'] : '';
$my_action = isset($_GET['action']) ? $_GET['action'] : '';

$logInfo = [
    'tool' => TOOL_FORUM,
    'tool_id' => $my_forum,
    'tool_id_detail' => 0,
    'action' => !empty($my_action) ? $my_action : 'list-threads',
    'action_details' => isset($_GET['content']) ? $_GET['content'] : '',
];
Event::registerLog($logInfo);

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$forumUrl = api_get_path(WEB_CODE_PATH).'forum/';

if (!empty($groupId)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group_properties['name'],
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('Forum').' '.Security::remove_XSS($current_forum['forum_title']),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => $forumUrl.'index.php?search='.Security::remove_XSS($my_search),
        'name' => get_lang('ForumCategories'),
    ];
    $interbreadcrumb[] = [
        'url' => $forumUrl.'viewforumcategory.php?forumcategory='.$current_forum_category['cat_id']
            .'&search='.Security::remove_XSS(urlencode($my_search)),
        'name' => Security::remove_XSS(prepare4display($current_forum_category['cat_title'])),
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => Security::remove_XSS($current_forum['forum_title']),
    ];
}

if ($origin == 'learnpath') {
    Display::display_reduced_header();
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string.
    Display::display_header();
}

/* Actions */
// Change visibility of a forum or a forum category.
if (($my_action == 'invisible' || $my_action == 'visible') &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    $isAllowedToEdit
) {
    $message = change_visibility($_GET['content'], $_GET['id'], $_GET['action']);
}
// Locking and unlocking.
if (($my_action == 'lock' || $my_action == 'unlock') &&
    isset($_GET['content']) && isset($_GET['id']) &&
    $isAllowedToEdit
) {
    $message = change_lock_status($_GET['content'], $_GET['id'], $my_action);
}
// Deleting.
if ($my_action == 'delete' &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    $isAllowedToEdit
) {
    $locked = api_resource_is_locked_by_gradebook($_GET['id'], LINK_FORUM_THREAD);
    if ($locked == false) {
        $message = deleteForumCategoryThread($_GET['content'], $_GET['id']);

        // Delete link
        $link_info = GradebookUtils::isResourceInCourseGradebook(
            api_get_course_id(),
            5,
            $_GET['id'],
            api_get_session_id()
        );
        $link_id = $link_info['id'];
        if ($link_info !== false) {
            GradebookUtils::remove_resource_from_course_gradebook($link_id);
        }
    }
}
// Moving.
if ($my_action == 'move' && isset($_GET['thread']) &&
    $isAllowedToEdit
) {
    $message = move_thread_form();
}
// Notification.
if ($my_action == 'notify' &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    api_is_allowed_to_session_edit(false, true)
) {
    $return_message = set_notification($_GET['content'], $_GET['id']);
    echo Display::return_message($return_message, 'confirm', false);
}

// Student list
if ($my_action == 'liststd' &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    (api_is_allowed_to_edit(null, true) || $is_group_tutor)
) {
    $active = null;
    $listType = isset($_GET['list']) ? $_GET['list'] : null;

    switch ($listType) {
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
        $url = api_get_cidreq().'&forum='.$my_forum.'&action='
            .Security::remove_XSS($_GET['action']).'&content='
            .Security::remove_XSS($_GET['content'], STUDENT).'&id='.intval($_GET['id']);
        $tabs = [
            [
                'content' => get_lang('AllStudents'),
                'url' => $forumUrl.'viewforum.php?'.$url.'&list=all',
            ],
            [
                'content' => get_lang('StudentsQualified'),
                'url' => $forumUrl.'viewforum.php?'.$url.'&list=qualify',
            ],
            [
                'content' => get_lang('StudentsNotQualified'),
                'url' => $forumUrl.'viewforum.php?'.$url.'&list=notqualify',
            ],
        ];
        $table_list .= Display::tabsOnlyLink($tabs, $active);

        $icon_qualify = 'quiz.png';
        $table_list .= '<center><br /><table class="table table-hover table-striped data_table" style="width:50%">';
        // The column headers (TODO: Make this sortable).
        $table_list .= '<tr >';
        $table_list .= '<th height="24">'.get_lang('NamesAndLastNames').'</th>';

        if ($listType == 'qualify') {
            $table_list .= '<th>'.get_lang('Qualification').'</th>';
        }
        if (api_is_allowed_to_edit(null, true)) {
            $table_list .= '<th>'.get_lang('Qualify').'</th>';
        }
        $table_list .= '</tr>';
        $max_qualify = showQualify('2', $userId, $_GET['id']);
        $counter_stdlist = 0;

        if (Database::num_rows($student_list) > 0) {
            while ($row_student_list = Database::fetch_array($student_list)) {
                $userInfo = api_get_user_info($row_student_list['id']);
                if ($counter_stdlist % 2 == 0) {
                    $class_stdlist = 'row_odd';
                } else {
                    $class_stdlist = 'row_even';
                }
                $table_list .= '<tr class="'.$class_stdlist.'"><td>';
                $table_list .= UserManager::getUserProfileLink($userInfo);

                $table_list .= '</td>';
                if ($listType == 'qualify') {
                    $table_list .= '<td>'.$row_student_list['qualify'].'/'.$max_qualify.'</td>';
                }
                if (api_is_allowed_to_edit(null, true)) {
                    $current_qualify_thread = showQualify(
                        '1',
                        $row_student_list['id'],
                        $_GET['id']
                    );
                    $table_list .= '<td>
                        <a href="'.$forumUrl.'forumqualify.php?'.api_get_cidreq()
                        .'&forum='.intval($my_forum).'&thread='
                        .intval($_GET['id']).'&user='.$row_student_list['id']
                        .'&user_id='.$row_student_list['id'].'&idtextqualify='
                        .$current_qualify_thread.'">'
                        .Display::return_icon($icon_qualify, get_lang('Qualify')).'</a></td></tr>';
                }
                $counter_stdlist++;
            }
        } else {
            if ($listType === 'qualify') {
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
    echo Display::return_message($message, 'confirm');
}

/* Action links */
echo '<div class="actions">';
if ($origin != 'learnpath') {
    if (!empty($groupId)) {
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq().'">'
            .Display::return_icon('back.png', get_lang('BackTo')
            .' '.get_lang('Groups'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<span style="float:right;">'.search_link().'</span>';
        echo '<a href="'.$forumUrl.'index.php?'.api_get_cidreq().'">'
            .Display::return_icon('back.png', get_lang('BackToForumOverview'), '', ICON_SIZE_MEDIUM)
            .'</a>';
    }
}

// The link should appear when
// 1. the course admin is here
// 2. the course member is here and new threads are allowed
// 3. a visitor is here and new threads AND allowed AND  anonymous posts are allowed
if (api_is_allowed_to_edit(false, true) ||
    ($current_forum['allow_new_threads'] == 1 && isset($_user['user_id'])) ||
    ($current_forum['allow_new_threads'] == 1 && !isset($_user['user_id']) && $current_forum['allow_anonymous'] == 1)
) {
    if ($current_forum['locked'] != 1 && $current_forum['locked'] != 1) {
        if (!api_is_anonymous() && !api_is_invitee()) {
            if ($my_forum == strval(intval($my_forum))) {
                echo '<a href="'.$forumUrl.'newthread.php?'.api_get_cidreq().'&forum='
                    .Security::remove_XSS($my_forum).'">'
                    .Display::return_icon('new_thread.png', get_lang('NewTopic'), '', ICON_SIZE_MEDIUM)
                    .'</a>';
            } else {
                $my_forum = strval(intval($my_forum));
                echo '<a href="'.$forumUrl.'newthread.php?'.api_get_cidreq()
                    .'&forum='.$my_forum.'">'
                    .Display::return_icon('new_thread.png', get_lang('NewTopic'), '', ICON_SIZE_MEDIUM)
                    .'</a>';
            }
        }
    } else {
        echo get_lang('ForumLocked');
    }
}
echo '</div>';

/* Display */
$titleForum = Security::remove_XSS($current_forum['forum_title']);
$descriptionForum = $current_forum['forum_comment'];
$iconForum = Display::return_icon(
    'forum_yellow.png',
    get_lang('Forum'),
    null,
    ICON_SIZE_MEDIUM
);
$html = '';
$html .= '<div class="topic-forum">';
// The current forum
if ($origin != 'learnpath') {
    $html .= Display::tag(
        'h3',
        $iconForum.' '.$titleForum,
        [
            'class' => 'title-forum', ]
    );

    if (!empty($descriptionForum)) {
        $html .= Display::tag(
            'p',
            Security::remove_XSS($descriptionForum),
            [
                'class' => 'description',
            ]
        );
    }
}

$html .= '</div>';
echo $html;

// Getting al the threads
$threads = get_threads($my_forum);
$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;
$course_id = api_get_course_int_id();

$hideNotifications = api_get_course_setting('hide_forum_notifications') == 1;

echo '<div class="forum_display">';
if (is_array($threads)) {
    $html = '';
    $count = 1;
    foreach ($threads as $row) {
        // Thread who have no replies yet and the only post is invisible should not be displayed to students.
        if (api_is_allowed_to_edit(false, true) ||
            !($row['thread_replies'] == '0' && $row['visibility'] == '0')
        ) {
            $my_whatsnew_post_info = null;

            if (isset($whatsnew_post_info[$my_forum][$row['thread_id']])) {
                $my_whatsnew_post_info = $whatsnew_post_info[$my_forum][$row['thread_id']];
            }

            $newPost = '';
            if (is_array($my_whatsnew_post_info) && !empty($my_whatsnew_post_info)) {
                $newPost = ' '.Display::return_icon('alert.png', get_lang('Forum'), null, ICON_SIZE_SMALL);
            }

            $name = api_get_person_name($row['firstname'], $row['lastname']);

            $linkPostForum = '<a href="viewthread.php?'.api_get_cidreq().'&forum='.$my_forum
                ."&thread={$row['thread_id']}&search="
                .Security::remove_XSS(urlencode($my_search)).'">'
                .Security::remove_XSS($row['thread_title']).'</a>';
            $html = '';
            $html .= '<div class="panel panel-default forum '.($row['thread_sticky'] ? 'sticky' : '').'">';
            $html .= '<div class="panel-body">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-6">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-2">';

            // display the author name
            $tab_poster_info = api_get_user_info($row['user_id']);
            $poster_username = sprintf(get_lang('LoginX'), $tab_poster_info['username']);
            $authorName = '';

            if ($origin != 'learnpath') {
                $authorName = display_user_link(
                    $row['user_id'],
                    api_get_person_name($row['firstname'], $row['lastname']),
                    '',
                    $poster_username
                );
            } else {
                $authorName = Display::tag(
                    'span',
                    api_get_person_name(
                        $row['firstname'],
                        $row['lastname']
                    ),
                    [
                        'title' => api_htmlentities($poster_username, ENT_QUOTES),
                    ]
                );
            }

            $_user = api_get_user_info($row['user_id']);
            $iconStatus = $_user['icon_status'];
            $last_post_info = get_last_post_by_thread(
                $row['c_id'],
                $row['thread_id'],
                $row['forum_id'],
                api_is_allowed_to_edit()
            );
            $last_post = null;
            if ($last_post_info) {
                $poster_info = api_get_user_info($last_post_info['poster_id']);
                $post_date = api_convert_and_format_date($last_post_info['post_date']);
                $last_post = $post_date.'<br>'.get_lang('By').' '.display_user_link(
                    $last_post_info['poster_id'],
                    $poster_info['complete_name'],
                    '',
                    $poster_info['username']
                );
            }

            $html .= '<div class="thumbnail">'.display_user_image($row['user_id'], $name, $origin).'</div>';
            $html .= '</div>';
            $html .= '<div class="col-md-10">';
            $html .= Display::tag(
                'h3',
                $linkPostForum,
                [
                    'class' => 'title',
                ]
            );
            $html .= '<p>'.get_lang('By').' '.$iconStatus.' '.$authorName.'</p>';

            if ($last_post_info) {
                $html .= '<p>'.Security::remove_XSS(cut($last_post_info['post_text'], 140)).'</p>';
            }

            $html .= '<p>'.Display::dateToStringAgoAndLongDate($row['insert_date']).'</p>';

            if ($current_forum['moderated'] == 1 && api_is_allowed_to_edit(false, true)) {
                $waitingCount = getCountPostsWithStatus(
                    CForumPost::STATUS_WAITING_MODERATION,
                    $current_forum,
                    $row['thread_id']
                );
                if (!empty($waitingCount)) {
                    $html .= Display::label(
                        get_lang('PostsPendingModeration').': '.$waitingCount,
                        'warning'
                    );
                }
            }

            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';

            $html .= '<div class="col-md-6">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-4">'
                .Display::return_icon('post-forum.png', null, null, ICON_SIZE_SMALL)
                ." {$row['thread_replies']} ".get_lang('Replies').'<br>';
            $html .= Display::return_icon(
                'post-forum.png',
                null,
                null,
                ICON_SIZE_SMALL
            ).' '.$row['thread_views'].' '.get_lang('Views').'<br>'.$newPost;
            $html .= '</div>';

            $last_post_info = get_last_post_by_thread(
                $row['c_id'],
                $row['thread_id'],
                $row['forum_id'],
                api_is_allowed_to_edit()
            );
            $last_post = null;

            if ($last_post_info) {
                $poster_info = api_get_user_info($last_post_info['poster_id']);
                $post_date = Display::dateToStringAgoAndLongDate($last_post_info['post_date']);
                $last_post = $post_date.'<br>'.get_lang('By').' '.display_user_link(
                    $last_post_info['poster_id'],
                    $poster_info['complete_name'],
                    '',
                    $poster_info['username']
                );
            }

            $html .= '<div class="col-md-5">'
                .Display::return_icon('post-item.png', null, null, ICON_SIZE_TINY)
                .' '.$last_post;
            $html .= '</div>';
            $html .= '<div class="col-md-3">';
            $cidreq = api_get_cidreq();

            // Get attachment id.
            if (isset($row['post_id'])) {
                $attachment_list = get_attachment($row['post_id']);
            }
            $id_attach = !empty($attachment_list) ? $attachment_list['id'] : '';
            $iconsEdit = '';
            if ($origin != 'learnpath') {
                if (api_is_allowed_to_edit(false, true) &&
                    !(api_is_session_general_coach() && $current_forum['session_id'] != $sessionId)
                ) {
                    $iconsEdit .= '<a href="'.$forumUrl.'editthread.php?'.$cidreq
                        .'&forum='.$my_forum.'&thread='
                        .intval($row['thread_id'])
                        .'&id_attach='.$id_attach.'">'
                        .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                    if (api_resource_is_locked_by_gradebook($row['thread_id'], LINK_FORUM_THREAD)) {
                        $iconsEdit .= Display::return_icon(
                            'delete_na.png',
                            get_lang('ResourceLockedByGradebook'),
                            [],
                            ICON_SIZE_SMALL
                        );
                    } else {
                        $iconsEdit .= '<a href="'.api_get_self().'?'.$cidreq.'&forum='
                            .$my_forum.'&action=delete&content=thread&id='
                            .$row['thread_id']."\" onclick=\"javascript:if(!confirm('"
                            .addslashes(api_htmlentities(get_lang('DeleteCompleteThread'), ENT_QUOTES))
                            ."')) return false;\">"
                            .Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';
                    }

                    $iconsEdit .= return_visible_invisible_icon(
                        'thread',
                        $row['thread_id'],
                        $row['visibility'],
                        [
                            'forum' => $my_forum,
                            'gidReq' => $groupId,
                        ]
                    );
                    $iconsEdit .= return_lock_unlock_icon(
                        'thread',
                        $row['thread_id'],
                        $row['locked'],
                        [
                            'forum' => $my_forum,
                            'gidReq' => api_get_group_id(),
                        ]
                    );
                    $iconsEdit .= '<a href="viewforum.php?'.$cidreq.'&forum='
                        .$my_forum
                        .'&action=move&thread='.$row['thread_id'].'">'
                        .Display::return_icon('move.png', get_lang('MoveThread'), [], ICON_SIZE_SMALL)
                        .'</a>';
                }
            }
            $iconnotify = 'notification_mail_na.png';
            if (is_array(
                isset($_SESSION['forum_notification']['thread']) ? $_SESSION['forum_notification']['thread'] : null
                )
            ) {
                if (in_array($row['thread_id'], $_SESSION['forum_notification']['thread'])) {
                    $iconnotify = 'notification_mail.png';
                }
            }
            $icon_liststd = 'user.png';
            if (!api_is_anonymous() &&
                api_is_allowed_to_session_edit(false, true) &&
                !$hideNotifications
            ) {
                $iconsEdit .= '<a href="'.api_get_self().'?'.$cidreq.'&forum='
                    .$my_forum
                    ."&action=notify&content=thread&id={$row['thread_id']}"
                    .'">'.Display::return_icon($iconnotify, get_lang('NotifyMe')).'</a>';
            }

            if (api_is_allowed_to_edit(null, true) && $origin != 'learnpath') {
                $iconsEdit .= '<a href="'.api_get_self().'?'.$cidreq.'&forum='
                    .$my_forum
                    ."&action=liststd&content=thread&id={$row['thread_id']}"
                    .'">'.Display::return_icon($icon_liststd, get_lang('StudentList'), [], ICON_SIZE_SMALL)
                    .'</a>';
            }
            $html .= $iconsEdit;
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            echo $html;
        }
        $count++;
    }
}

echo '</div>';
echo isset($table_list) ? $table_list : '';

if ($origin != 'learnpath') {
    Display::display_footer();
}
