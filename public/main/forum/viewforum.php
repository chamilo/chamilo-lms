<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumForum;
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
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_FORUM;

api_protect_course_script(true);
api_protect_course_group(GroupManager::GROUP_TOOL_FORUM);

$this_section = SECTION_COURSES;
$nameTools = get_lang('Forums');
$origin = api_get_origin();

require_once 'forumfunction.inc.php';

$userId = api_get_user_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$courseId = api_get_course_int_id();
$groupInfo = GroupManager::get_group_properties($groupId);
$isTutor = GroupManager::is_tutor_of_group($userId, $groupInfo, $courseId);
$isAllowedToEdit = api_is_allowed_to_edit(false, true) && api_is_allowed_to_session_edit(false, true);
$repo = Container::getForumRepository();

$my_forum = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;
$forumEntity = null;
if (!empty($my_forum)) {
    /** @var CForumForum $forumEntity */
    $forumEntity = $repo->find($my_forum);
}

$courseEntity = api_get_course_entity(api_get_course_int_id());
$sessionEntity = api_get_session_entity(api_get_session_id());

// Note: This has to be validated that it is an existing forum.
$current_forum = get_forum_information($my_forum);
$isForumOpenByDateAccess = api_is_date_in_date_range($forumEntity->getStartTime(), $forumEntity->getEndTime());

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

$current_forum_category = $forumEntity->getForumCategory();
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
            ($current_forum_category && false == $current_forum_category->isVisible($courseEntity, $sessionEntity)) ||
            false == $current_forum_category->isVisible($courseEntity, $sessionEntity)
        )
    ) {
        api_not_allowed(true);
    }
} else {
    // Course
    if (!api_is_allowed_to_edit(false, true) && //is a student
        (
            ($current_forum_category && false == $current_forum_category->isVisible($courseEntity, $sessionEntity)) ||
            false == $current_forum_category->isVisible($courseEntity, $sessionEntity)
        )
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
        'name' => get_lang('Assessments'),
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
        'name' => get_lang('Group area').' '.$group_properties['name'],
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('Forum').' '.Security::remove_XSS($current_forum['forum_title']),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => $forumUrl.'index.php?search='.Security::remove_XSS($my_search),
        'name' => get_lang('Forum Categories'),
    ];

    $interbreadcrumb[] = [
        'url' => $forumUrl.'viewforumcategory.php?forumcategory='.$current_forum_category->getIid()
            .'&search='.Security::remove_XSS(urlencode($my_search)),
        'name' => prepare4display($current_forum_category->getCatTitle()),
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => Security::remove_XSS($forumEntity->getForumTitle()),
    ];
}

if ('learnpath' == $origin) {
    Display::display_reduced_header();
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string.
    Display::display_header();
}

/* Actions */
// Change visibility of a forum or a forum category.
if (('invisible' == $my_action || 'visible' == $my_action) &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    $isAllowedToEdit
) {
    $message = change_visibility($_GET['content'], $_GET['id'], $_GET['action']);
}
// Locking and unlocking.
if (('lock' == $my_action || 'unlock' == $my_action) &&
    isset($_GET['content']) && isset($_GET['id']) &&
    $isAllowedToEdit
) {
    $message = change_lock_status($_GET['content'], $_GET['id'], $my_action);
}
// Deleting.
if ('delete' == $my_action &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    $isAllowedToEdit
) {
    $locked = api_resource_is_locked_by_gradebook($_GET['id'], LINK_FORUM_THREAD);
    if (false == $locked) {
        $message = deleteForumCategoryThread($_GET['content'], $_GET['id']);

        // Delete link
        $link_info = GradebookUtils::isResourceInCourseGradebook(
            api_get_course_id(),
            5,
            $_GET['id'],
            api_get_session_id()
        );
        $link_id = $link_info['id'];
        if (false !== $link_info) {
            GradebookUtils::remove_resource_from_course_gradebook($link_id);
        }
    }
}
// Moving.
if ('move' == $my_action && isset($_GET['thread']) &&
    $isAllowedToEdit
) {
    $message = move_thread_form();
}
// Notification.
if ('notify' == $my_action &&
    isset($_GET['content']) &&
    isset($_GET['id']) &&
    api_is_allowed_to_session_edit(false, true)
) {
    $return_message = set_notification($_GET['content'], $_GET['id']);
    echo Display::return_message($return_message, 'confirm', false);
}

// Student list
if ('liststd' == $my_action &&
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

    $table_list = Display::page_subheader(get_lang('Users list of the thread').': '.get_name_thread_by_id($_GET['id']));

    if ($nrorow3 > 0 || -2 == $nrorow3) {
        $url = api_get_cidreq().'&forum='.$my_forum.'&action='
            .Security::remove_XSS($_GET['action']).'&content='
            .Security::remove_XSS($_GET['content'], STUDENT).'&id='.(int) ($_GET['id']);
        $tabs = [
            [
                'content' => get_lang('All learners'),
                'url' => $forumUrl.'viewforum.php?'.$url.'&list=all',
            ],
            [
                'content' => get_lang('Qualified learners'),
                'url' => $forumUrl.'viewforum.php?'.$url.'&list=qualify',
            ],
            [
                'content' => get_lang('Unqualified learners'),
                'url' => $forumUrl.'viewforum.php?'.$url.'&list=notqualify',
            ],
        ];
        $table_list .= Display::tabsOnlyLink($tabs, $active);

        $icon_qualify = 'quiz.png';
        $table_list .= '<center><br /><table class="data_table" style="width:50%">';
        // The column headers (TODO: Make this sortable).
        $table_list .= '<tr >';
        $table_list .= '<th height="24">'.get_lang('First names and last names').'</th>';

        if ('qualify' == $listType) {
            $table_list .= '<th>'.get_lang('Score').'</th>';
        }
        if (api_is_allowed_to_edit(null, true)) {
            $table_list .= '<th>'.get_lang('Grade activity').'</th>';
        }
        $table_list .= '</tr>';
        $max_qualify = showQualify('2', $userId, $_GET['id']);
        $counter_stdlist = 0;

        if (Database::num_rows($student_list) > 0) {
            while ($row_student_list = Database::fetch_array($student_list)) {
                $userInfo = api_get_user_info($row_student_list['id']);
                if (0 == $counter_stdlist % 2) {
                    $class_stdlist = 'row_odd';
                } else {
                    $class_stdlist = 'row_even';
                }
                $table_list .= '<tr class="'.$class_stdlist.'"><td>';
                $table_list .= UserManager::getUserProfileLink($userInfo);

                $table_list .= '</td>';
                if ('qualify' == $listType) {
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
                        .'&forum='.(int) $my_forum.'&thread='
                        .(int) ($_GET['id']).'&user='.$row_student_list['id']
                        .'&user_id='.$row_student_list['id'].'&idtextqualify='
                        .$current_qualify_thread.'">'
                        .Display::return_icon($icon_qualify, get_lang('Grade activity')).'</a></td></tr>';
                }
                ++$counter_stdlist;
            }
        } else {
            if ('qualify' === $listType) {
                $table_list .= '<tr><td colspan="2">'.get_lang('There are no qualified learners').'</td></tr>';
            } else {
                $table_list .= '<tr><td colspan="2">'.get_lang('There are no unqualified learners').'</td></tr>';
            }
        }

        $table_list .= '</table></center>';
        $table_list .= '<br />';
    } else {
        $table_list .= Display::return_message(get_lang('There are no participants'), 'warning');
    }
}

if ('learnpath' == $origin) {
    echo '<div style="height:15px">&nbsp;</div>';
}

/* Display the action messages */
if (!empty($message)) {
    echo Display::return_message($message, 'confirm');
}

/* Action links */
echo '<div class="actions">';
if ('learnpath' != $origin) {
    if (!empty($groupId)) {
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq().'">'
            .Display::return_icon('back.png', get_lang('Back to')
            .' '.get_lang('Groups'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<span style="float:right;">'.search_link().'</span>';
        echo '<a href="'.$forumUrl.'index.php?'.api_get_cidreq().'">'
            .Display::return_icon('back.png', get_lang('Back toForumOverview'), '', ICON_SIZE_MEDIUM)
            .'</a>';
    }
}

// The link should appear when
// 1. the course admin is here
// 2. the course member is here and new threads are allowed
// 3. a visitor is here and new threads AND allowed AND  anonymous posts are allowed
if (api_is_allowed_to_edit(false, true) ||
    (1 == $current_forum['allow_new_threads'] && isset($_user['user_id'])) ||
    (1 == $current_forum['allow_new_threads'] && !isset($_user['user_id']) && 1 == $current_forum['allow_anonymous'])
) {
    if (1 != $forumEntity->getLocked() && 1 != $forumEntity->getLocked()) {
        if (!api_is_anonymous() && !api_is_invitee()) {
            if ($my_forum == (string) ((int) $my_forum)) {
                echo '<a href="'.$forumUrl.'newthread.php?'.api_get_cidreq().'&forum='
                    .Security::remove_XSS($my_forum).'">'
                    .Display::return_icon('new_thread.png', get_lang('Create thread'), '', ICON_SIZE_MEDIUM)
                    .'</a>';
            } else {
                $my_forum = (string) ((int) $my_forum);
                echo '<a href="'.$forumUrl.'newthread.php?'.api_get_cidreq()
                    .'&forum='.$my_forum.'">'
                    .Display::return_icon('new_thread.png', get_lang('Create thread'), '', ICON_SIZE_MEDIUM)
                    .'</a>';
            }
        }
    } else {
        echo get_lang('Forum blocked');
    }
}
echo '</div>';

$descriptionForum = $forumEntity->getForumComment();
$iconForum = Display::return_icon(
    'forum_yellow.png',
    get_lang('Forum'),
    null,
    ICON_SIZE_MEDIUM
);
$html = '';
$html .= '<div class="topic-forum">';
// The current forum
if ('learnpath' != $origin) {
    $html .= Display::tag(
        'h3',
        $iconForum.' '.$forumEntity->getForumTitle(),
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
//$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;
$course_id = api_get_course_int_id();

echo '<div class="forum_display">';
if (is_array($threads)) {
    $html = '';
    $count = 1;
    foreach ($threads as $thread) {
        $threadId = $thread->getIid();
        // Thread who have no replies yet and the only post is invisible should not be displayed to students.
        if (api_is_allowed_to_edit(false, true) ||
            !('0' == $thread->getThreadReplies() && '0' == $thread->isVisible($courseEntity, $sessionEntity))
        ) {
            /*$my_whatsnew_post_info = null;
            if (isset($whatsnew_post_info[$my_forum][$thread['thread_id']])) {
                $my_whatsnew_post_info = $whatsnew_post_info[$my_forum][$thread['thread_id']];
            }
            $newPost = '';
            if (is_array($my_whatsnew_post_info) && !empty($my_whatsnew_post_info)) {
                $newPost = ' '.Display::return_icon('alert.png', get_lang('Forum'), null, ICON_SIZE_SMALL);
            }*/

            //$name = api_get_person_name($thread['firstname'], $thread['lastname']);

            $linkPostForum = '<a href="viewthread.php?'.api_get_cidreq().'&forum='.$my_forum
                ."&thread={$threadId}&search="
                .Security::remove_XSS(urlencode($my_search)).'">'
                .$thread->getThreadTitle().'</a>';
            $html = '';
            $html .= '<div class="panel panel-default forum '.($thread->getThreadSticky() ? 'sticky' : '').'">';
            $html .= '<div class="panel-body">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-6">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-2">';

            // display the author name
            $tab_poster_info = api_get_user_info($thread->getThreadPosterId());
            $poster_username = sprintf(get_lang('Login: %s'), $tab_poster_info['username']);
            $authorName = '';

            if ('learnpath' != $origin) {
                $authorName = display_user_link(
                    $thread->getThreadPosterId(),
                    $tab_poster_info['complete_name'],
                    '',
                    $poster_username
                );
            } else {
                $authorName = Display::tag(
                    'span',
                    $tab_poster_info['complete_name'],
                    [
                        'title' => api_htmlentities($poster_username, ENT_QUOTES),
                    ]
                );
            }

            $iconStatus = $tab_poster_info['icon_status'];
            $last_post_info = get_last_post_by_thread(
                $thread->getCId(),
                $threadId,
                $thread->getForum()->getIid(),
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

            $html .= '<div class="thumbnail">'.display_user_image($thread->getThreadPosterId(), $poster_username, $origin).'</div>';
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

            $html .= '<p>'.Display::dateToStringAgoAndLongDate($thread->getThreadDate()).'</p>';

            if (1 == $forumEntity->isModerated() && api_is_allowed_to_edit(false, true)) {
                $waitingCount = getCountPostsWithStatus(
                    CForumPost::STATUS_WAITING_MODERATION,
                    $current_forum,
                    $thread['thread_id']
                );
                if (!empty($waitingCount)) {
                    $html .= Display::label(
                        get_lang('Posts pending moderation').': '.$waitingCount,
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
                ." {$thread->getThreadReplies()} ".get_lang('Replies').'<br>';
            $html .= Display::return_icon(
                'post-forum.png',
                null,
                null,
                ICON_SIZE_SMALL
            ).' '.$thread->getThreadReplies().' '.get_lang('Views').'<br>';
            $html .= '</div>';

            $last_post_info = get_last_post_by_thread(
                $thread->getCId(),
                $threadId,
                $thread->getForum()->getIid(),
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
            /*if (isset($thread['post_id'])) {
                $attachment_list = get_attachment($thread['post_id']);
            }*/
            $id_attach = !empty($attachment_list) ? $attachment_list['id'] : '';
            $iconsEdit = '';
            if ('learnpath' != $origin) {
                if (api_is_allowed_to_edit(false, true) &&
                    !(api_is_session_general_coach() && $current_forum['session_id'] != $sessionId)
                ) {
                    $iconsEdit .= '<a href="'.$forumUrl.'editthread.php?'.$cidreq
                        .'&forum='.$my_forum.'&thread='
                        .$thread->getIid()
                        .'&id_attach='.$id_attach.'">'
                        .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL).'</a>';
                    if (api_resource_is_locked_by_gradebook($thread->getIid(), LINK_FORUM_THREAD)) {
                        $iconsEdit .= Display::return_icon(
                            'delete_na.png',
                            get_lang('This option is not available because this activity is contained by an assessment, which is currently locked. To unlock the assessment, ask your platform administrator.'),
                            [],
                            ICON_SIZE_SMALL
                        );
                    } else {
                        $iconsEdit .= '<a href="'.api_get_self().'?'.$cidreq.'&forum='
                            .$my_forum.'&action=delete&content=thread&id='
                            .$thread->getIid()."\" onclick=\"javascript:if(!confirm('"
                            .addslashes(api_htmlentities(get_lang('Delete complete thread?'), ENT_QUOTES))
                            ."')) return false;\">"
                            .Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL).'</a>';
                    }

                    $iconsEdit .= return_visible_invisible_icon(
                        'thread',
                        $thread->getIid(),
                        $thread->isVisible($courseEntity, $sessionEntity),
                        [
                            'forum' => $my_forum,
                            'gid' => $groupId,
                        ]
                    );
                    $iconsEdit .= return_lock_unlock_icon(
                        'thread',
                        $thread->getIid(),
                        $thread->getLocked(),
                        [
                            'forum' => $my_forum,
                            'gid' => api_get_group_id(),
                        ]
                    );
                    $iconsEdit .= '<a href="viewforum.php?'.$cidreq.'&forum='
                        .$my_forum
                        .'&action=move&thread='.$threadId.'">'
                        .Display::return_icon('move.png', get_lang('Move Thread'), [], ICON_SIZE_SMALL)
                        .'</a>';
                }
            }
            $iconnotify = 'notification_mail_na.png';
            if (is_array(
                isset($_SESSION['forum_notification']['thread']) ? $_SESSION['forum_notification']['thread'] : null
                )
            ) {
                if (in_array($threadId, $_SESSION['forum_notification']['thread'])) {
                    $iconnotify = 'notification_mail.png';
                }
            }
            $icon_liststd = 'user.png';
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                $iconsEdit .= '<a href="'.api_get_self().'?'.$cidreq.'&forum='
                    .$my_forum
                    ."&action=notify&content=thread&id={$threadId}"
                    .'">'.Display::return_icon($iconnotify, get_lang('Notify me')).'</a>';
            }

            if (api_is_allowed_to_edit(null, true) && 'learnpath' != $origin) {
                $iconsEdit .= '<a href="'.api_get_self().'?'.$cidreq.'&forum='
                    .$my_forum
                    ."&action=liststd&content=thread&id={$threadId}"
                    .'">'.Display::return_icon($icon_liststd, get_lang('Learners list'), [], ICON_SIZE_SMALL)
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
        ++$count;
    }
}

echo '</div>';
echo isset($table_list) ? $table_list : '';

if ('learnpath' != $origin) {
    Display::display_footer();
}
