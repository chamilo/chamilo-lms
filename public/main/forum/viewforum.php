<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CForumPost;

require_once __DIR__.'/../inc/global.inc.php';
api_protect_course_group(GroupManager::GROUP_TOOL_FORUM);
api_protect_course_script(true);
$nameTools = get_lang('Forums');
$origin = api_get_origin();

$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
$htmlHeadXtra[] = '<script>

function check_unzip() {
    if (document.upload.unzip.checked){
        document.upload.if_exists[0].disabled=true;
        document.upload.if_exists[1].checked=true;
        document.upload.if_exists[2].disabled=true;
    } else {
        document.upload.if_exists[0].checked=true;
        document.upload.if_exists[0].disabled=false;
        document.upload.if_exists[2].disabled=false;
    }
}
function setFocus() {
    $("#title_file").focus();
}
</script>';
// The next javascript script is to manage ajax upload file
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

// Recover Thread ID, will be used to generate delete attachment URL to do ajax
$threadId = isset($_REQUEST['thread']) ? (int) ($_REQUEST['thread']) : 0;
$forumId = isset($_REQUEST['forum']) ? (int) ($_REQUEST['forum']) : 0;

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq();
// The next javascript script is to delete file by ajax
$htmlHeadXtra[] = '<script>
$(function () {
    $(document).on("click", ".deleteLink", function(e) {
        e.preventDefault();
        e.stopPropagation();
        var l = $(this);
        var id = l.closest("tr").attr("id");
        var filename = l.closest("tr").find(".attachFilename").html();
        if (confirm("'.get_lang('Are you sure to delete').'", filename)) {
            $.ajax({
                type: "POST",
                url: "'.$ajaxUrl.'&a=delete_file&attachId=" + id +"&thread='.$threadId.'&forum='.$forumId.'",
                dataType: "json",
                success: function(data) {
                    if (data.error == false) {
                        l.closest("tr").remove();
                        if ($(".files td").length < 1) {
                            $(".files").closest(".control-group").hide();
                        }
                    }
                }
            })
        }
    });
});
</script>';
$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;

$viewForumUrl = api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.$forumId;
$message = handleForum($viewForumUrl);
$table_list = '';
$userId = api_get_user_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();
$courseId = api_get_course_int_id();
//$groupInfo = GroupManager::get_group_properties($groupId);
//$isTutor = GroupManager::is_tutor_of_group($userId, $groupInfo, $courseId);
$isAllowedToEdit = api_is_allowed_to_edit(false, true) && api_is_allowed_to_session_edit(false, true);
$repo = Container::getForumRepository();

/** @var CForumForum $forumEntity */
$forumEntity = $repo->find($forumId);
$courseEntity = api_get_course_entity(api_get_course_int_id());
$sessionEntity = api_get_session_entity(api_get_session_id());
$isForumOpenByDateAccess = api_is_date_in_date_range($forumEntity->getStartTime(), $forumEntity->getEndTime());
$url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq();

if (!$isForumOpenByDateAccess && !$isAllowedToEdit) {
    if ($origin) {
        api_not_allowed(true);
    } else {
        api_not_allowed(true);
    }
}

$category = $forumEntity->getForumCategory();
$is_group_tutor = false;

if (!empty($groupId)) {
    $groupEntity = api_get_group_entity($groupId);
    $is_group_tutor = GroupManager::isTutorOfGroup(
        api_get_user_id(),
        $groupEntity
    );

    // Course
    if (!api_is_allowed_to_edit(false, true) && //is a student
        (
            ($category && false == $category->isVisible($courseEntity, $sessionEntity)) ||
            false == $category->isVisible($courseEntity, $sessionEntity)
        )
    ) {
        api_not_allowed(true);
    }
} else {
    // Course
    if (!api_is_allowed_to_edit(false, true) && //is a student
        (
            ($category && false == $category->isVisible($courseEntity, $sessionEntity)) ||
            false == $category->isVisible($courseEntity, $sessionEntity)
        )
    ) {
        api_not_allowed(true);
    }
}

/* Header and Breadcrumbs */
$my_search = $_GET['search'] ?? '';
$my_action = $_GET['action'] ?? '';

$logInfo = [
    'tool' => TOOL_FORUM,
    'tool_id' => $forumId,
    'action' => !empty($my_action) ? $my_action : 'list-threads',
    'action_details' => $_GET['content'] ?? '',
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
        'name' => get_lang('Group area').' '.$groupEntity->getName(),
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => get_lang('Forum').' '.Security::remove_XSS($forumEntity->getForumTitle()),
    ];
} else {
    $interbreadcrumb[] = [
        'url' => $forumUrl.'index.php?'.api_get_cidreq(),
        'name' => get_lang('Forum Categories'),
    ];

    $interbreadcrumb[] = [
        'url' => $forumUrl.'viewforumcategory.php?forumcategory='.$category->getIid().'&'.api_get_cidreq(),
        'name' => prepare4display($category->getCatTitle()),
    ];
    $interbreadcrumb[] = [
        'url' => '#',
        'name' => Security::remove_XSS($forumEntity->getForumTitle()),
    ];
}

if ('learnpath' === $origin) {
    Display::display_reduced_header();
} else {
    // The last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string.
    Display::display_header();
}

// Student list
if ('liststd' === $my_action &&
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
        $url = api_get_cidreq().'&forum='.$forumId.'&action='
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

        $table_list .= '<center><br /><table class="data_table" style="width:50%">';
        // The column headers (TODO: Make this sortable).
        $table_list .= '<tr >';
        $table_list .= '<th height="24">'.get_lang('First names and last names').'</th>';

        if ('qualify' === $listType) {
            $table_list .= '<th>'.get_lang('Score').'</th>';
        }
        if (api_is_allowed_to_edit(null, true)) {
            $table_list .= '<th>'.get_lang('Grade activity').'</th>';
        }
        $table_list .= '</tr>';
        $max_qualify = showQualify('2', $userId, $_GET['id']);
        $counter = 0;
        $icon = Display::return_icon('quiz.png', get_lang('Grade activity'));
        if (Database::num_rows($student_list) > 0) {
            while ($row_student_list = Database::fetch_array($student_list)) {
                $userInfo = api_get_user_info($row_student_list['id']);
                $class_stdlist = 'row_even';
                if (0 == $counter % 2) {
                    $class_stdlist = 'row_odd';
                }
                $table_list .= '<tr class="'.$class_stdlist.'"><td>';
                $table_list .= UserManager::getUserProfileLink($userInfo);
                $table_list .= '</td>';
                if ('qualify' === $listType) {
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
                        .'&forum='.$forumId.'&thread='
                        .(int) ($_GET['id']).'&user='.$row_student_list['id']
                        .'&user_id='.$row_student_list['id'].'&idtextqualify='
                        .$current_qualify_thread.'">'
                        .$icon.'</a>
                        </td></tr>';
                }
                $counter++;
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

if ('learnpath' === $origin) {
    echo '<div style="height:15px">&nbsp;</div>';
}

$actions = '';
if ('learnpath' !== $origin) {
    if (!empty($groupId)) {
        $actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq().'">'
            .Display::return_icon('back.png', get_lang('Back to')
            .' '.get_lang('Groups'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        $actions .= '<span style="float:right;">'.search_link().'</span>';
        $actions .= '<a href="'.$forumUrl.'index.php?'.api_get_cidreq().'">'
            .Display::return_icon('back.png', get_lang('Back toForumOverview'), '', ICON_SIZE_MEDIUM)
            .'</a>';
    }
}

// The link should appear when
// 1. the course admin is here
// 2. the course member is here and new threads are allowed
// 3. a visitor is here and new threads AND allowed AND  anonymous posts are allowed
if (api_is_allowed_to_edit(false, true) ||
    (1 == $forumEntity->getAllowNewThreads() && isset($_user['user_id'])) ||
    (1 == $forumEntity->getAllowNewThreads() && !isset($_user['user_id']) && 1 == $forumEntity->getAllowAnonymous())
) {
    if (1 != $forumEntity->getLocked() && 1 != $forumEntity->getLocked()) {
        if (!api_is_anonymous() && !api_is_invitee()) {
            $actions .= '<a href="'.$forumUrl.'newthread.php?'.api_get_cidreq().'&forum='
                .$forumId.'">'
                .Display::return_icon('new_thread.png', get_lang('Create thread'), '', ICON_SIZE_MEDIUM)
                .'</a>';
        }
    } else {
        $actions .= get_lang('Forum blocked');
    }
}

echo Display::toolbarAction('toolbar', [$actions]);

if (!empty($message)) {
    echo $message;
}

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
$threads = get_threads($forumId);
$course_id = api_get_course_int_id();
$illustrationRepo = Container::getIllustrationRepository();

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
            $linkPostForum = '<a href="viewthread.php?'.api_get_cidreq().'&forum='.$forumId
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
            $author = $thread->getUser();
            $completeName = UserManager::formatUserFullName($author);
            $poster_username = sprintf(get_lang('Login: %s'), $thread->getUser()->getUsername());
            $authorName = '';
            if ('learnpath' !== $origin) {
                $authorName = displayUserLink($author);
            } else {
                $authorName = Display::tag(
                    'span',
                    $completeName,
                    [
                        'title' => api_htmlentities($poster_username, ENT_QUOTES),
                    ]
                );
            }

            $iconStatus = $author->getIconStatus();
            if ($thread->getThreadLastPost()) {
                $post_date = api_convert_and_format_date($thread->getThreadLastPost()->getPostDate()->format('Y-m-d H:i:s'));
                $last_post = $post_date.'<br>'.get_lang('By').' '.displayUserLink($thread->getThreadLastPost()->getUser());
            }

            $html .= displayUserImage($thread->getUser());
            $html .= '</div>';
            $html .= '<div class="col-md-10">';
            $html .= Display::tag(
                'h3',
                $linkPostForum,
                [
                    'class' => 'title',
                ]
            );
            $html .= '<p>'.get_lang('By').' <img src="'.$iconStatus.'" /> '.$authorName.'</p>';

            if ($thread->getThreadLastPost()) {
                $html .= '<p>'.Security::remove_XSS(cut($thread->getThreadLastPost()->getPostText(), 140)).'</p>';
            }

            $html .= '<p>'.Display::dateToStringAgoAndLongDate($thread->getThreadDate()).'</p>';

            if (1 == $forumEntity->isModerated() && api_is_allowed_to_edit(false, true)) {
                $waitingCount = getCountPostsWithStatus(
                    CForumPost::STATUS_WAITING_MODERATION,
                    $forumEntity,
                    $thread->getIid()
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
            $last_post = null;
            if ($thread->getThreadLastPost()) {
                $post_date = api_convert_and_format_date($thread->getThreadLastPost()->getPostDate()->format('Y-m-d H:i:s'));
                $last_post = $post_date.'<br>'.get_lang('By').' '.displayUserLink(
                    $thread->getThreadLastPost()->getUser()
                );
            }

            $html .= '<div class="col-md-5">'
                .Display::return_icon('post-item.png', null, null, ICON_SIZE_TINY)
                .' '.$last_post;
            $html .= '</div>';
            $html .= '<div class="col-md-3">';
            $id_attach = !empty($attachment_list) ? $attachment_list['id'] : '';
            $iconsEdit = '';
            if ('learnpath' !== $origin) {
                if (api_is_allowed_to_edit(false, true)
                    /*&& @todo fix session validation
                    !(
                        api_is_session_general_coach() &&
                        $current_forum['session_id'] != $sessionId
                    )*/
                ) {
                    $iconsEdit .= '<a href="'.$forumUrl.'editthread.php?'.api_get_cidreq()
                        .'&forum='.$forumId.'&thread='
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
                        $iconsEdit .= '<a href="'.$url.'&forum='.$forumId.'&action=delete_thread&content=thread&id='
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
                            'forum' => $forumId,
                            'gid' => $groupId,
                        ]
                    );
                    $iconsEdit .= return_lock_unlock_icon(
                        'thread',
                        $thread->getIid(),
                        $thread->getLocked(),
                        [
                            'forum' => $forumId,
                            'gid' => api_get_group_id(),
                        ]
                    );
                    $iconsEdit .= '<a href="'.$viewForumUrl.'&forum='.$forumId.'&action=move_thread&thread='.$threadId.'">'
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
                $iconsEdit .= '<a href="'.$url.'&forum='.$forumId."&action=notify&content=thread&id={$threadId}".'">'.
                    Display::return_icon($iconnotify, get_lang('Notify me')).'</a>';
            }

            if (api_is_allowed_to_edit(null, true) && 'learnpath' != $origin) {
                $iconsEdit .= '<a href="'.$viewForumUrl.'&forum='.$forumId."&action=liststd&content=thread&id={$threadId}".'">'.
                    Display::return_icon($icon_liststd, get_lang('Learners list'), [], ICON_SIZE_SMALL)
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
echo $table_list;

Display::display_footer();
