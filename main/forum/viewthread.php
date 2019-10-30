<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CForumPost;

/**
 * @author Julio Montoya <gugli100@gmail.com> UI Improvements + lots of bugfixes
 *
 * @package chamilo.forum
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_FORUM;

$this_section = SECTION_COURSES;

// Notification for unauthorized people.
api_protect_course_script(true);

require_once 'forumfunction.inc.php';

$nameTools = get_lang('Forum');
$forumUrl = api_get_path(WEB_CODE_PATH).'forum/';

// Are we in a lp ?
$origin = api_get_origin();
$_user = api_get_user_info();
$my_search = null;

$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;
$threadId = isset($_GET['thread']) ? (int) $_GET['thread'] : 0;

/* MAIN DISPLAY SECTION */
/* Retrieving forum and forum category information */

// We are getting all the information about the current forum and forum category.
// Note pcool: I tried to use only one sql statement (and function) for this,
// but the problem is that the visibility of the forum AND forum category are stored in the item_property table.
// Note: This has to be validated that it is an existing thread
$current_thread = get_thread_information($forumId, $threadId);
// Note: This has to be validated that it is an existing forum.
$current_forum = get_forum_information($current_thread['forum_id']);
$current_forum_category = get_forumcategory_information($current_forum['forum_category']);
$whatsnew_post_info = isset($_SESSION['whatsnew_post_info']) ? $_SESSION['whatsnew_post_info'] : null;

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
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

/* Actions */
$my_action = isset($_GET['action']) ? $_GET['action'] : '';

$logInfo = [
    'tool' => TOOL_FORUM,
    'tool_id' => $forumId,
    'tool_id_detail' => $threadId,
    'action' => !empty($my_action) ? $my_action : 'view-thread',
    'action_details' => isset($_GET['content']) ? $_GET['content'] : '',
];
Event::registerLog($logInfo);

$currentUrl = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?forum='.$forumId.'&'.api_get_cidreq().'&thread='.$threadId;

switch ($my_action) {
    case 'delete':
        if (
            isset($_GET['content']) &&
            isset($_GET['id']) &&
            (api_is_allowed_to_edit(false, true) ||
                (isset($group_properties['iid']) && GroupManager::is_tutor_of_group(api_get_user_id(), $group_properties)))
        ) {
            $message = delete_post($_GET['id']);
            Display::addFlash(Display::return_message(get_lang($message)));
        }
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'invisible':
    case 'visible':
        if (isset($_GET['id']) &&
            (api_is_allowed_to_edit(false, true) ||
                (isset($group_properties['iid']) && GroupManager::is_tutor_of_group(api_get_user_id(), $group_properties)))
        ) {
            $message = approve_post($_GET['id'], $_GET['action']);
            Display::addFlash(Display::return_message(get_lang($message)));
        }
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'move':
        if (isset($_GET['post'])) {
            $message = move_post_form();
            Display::addFlash(Display::return_message(get_lang($message)));
        }
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'report':
        $postId = isset($_GET['post_id']) ? $_GET['post_id'] : 0;

        $result = reportPost($postId, $current_forum, $current_thread);
        Display::addFlash(Display::return_message(get_lang('Reported')));
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'ask_revision':
        if (api_get_configuration_value('allow_forum_post_revisions')) {
            $postId = isset($_GET['post_id']) ? $_GET['post_id'] : 0;
            $result = savePostRevision($postId);
            Display::addFlash(Display::return_message(get_lang('Saved.')));
        }
        header('Location: '.$currentUrl);
        exit;
        break;
}

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
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?forum='.$forumId.'&'.api_get_cidreq()."&search=".Security::remove_XSS(urlencode($my_search)),
        'name' => Security::remove_XSS($current_forum['forum_title']),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'forum/viewthread.php?forum='.$forumId.'&'.api_get_cidreq().'&thread='.$threadId,
        'name' => Security::remove_XSS($current_thread['thread_title']),
    ];
} else {
    $my_search = isset($_GET['search']) ? $_GET['search'] : '';
    if ($origin != 'learnpath') {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&search='.Security::remove_XSS(
                    urlencode($my_search)
                ),
            'name' => $nameTools,
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(
                    WEB_CODE_PATH
                ).'forum/viewforumcategory.php?forumcategory='.$current_forum_category['cat_id']."&search=".Security::remove_XSS(
                    urlencode($my_search)
                ),
            'name' => Security::remove_XSS($current_forum_category['cat_title']),
        ];
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.$forumId."&search=".Security::remove_XSS(urlencode($my_search)),
            'name' => Security::remove_XSS($current_forum['forum_title']),
        ];
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => Security::remove_XSS($current_thread['thread_title']),
        ];
    }
}

// If the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false, true) &&
    ($current_forum['visibility'] == 0 || $current_thread['visibility'] == 0)
) {
    api_not_allowed();
}
// this increases the number of times the thread has been viewed
increase_thread_view($threadId);

if ($origin == 'learnpath') {
    $template = new Template('', false, false, true, true, false);
} else {
    $template = new Template();
}

$actions = '<span style="float:right;">'.search_link().'</span>';
if ($origin != 'learnpath') {
    $actions .= '<a href="'.$forumUrl.'viewforum.php?forum='.$forumId.'&'.api_get_cidreq().'">'
        .Display::return_icon('back.png', get_lang('Back to forum'), '', ICON_SIZE_MEDIUM).'</a>';
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
            $actions .= '<a href="'.$forumUrl.'reply.php?'.api_get_cidreq().'&forum='.$forumId.'&thread='
                .$threadId.'&action=replythread">'
                .Display::return_icon('reply_thread.png', get_lang('Reply to this thread'), '', ICON_SIZE_MEDIUM)
                .'</a>';
        }
        // new thread link
        if ((
            api_is_allowed_to_edit(false, true) &&
            !(api_is_session_general_coach() && $current_forum['session_id'] != $sessionId)) ||
            ($current_forum['allow_new_threads'] == 1 && isset($_user['user_id'])) ||
            ($current_forum['allow_new_threads'] == 1 && !isset($_user['user_id']) && $current_forum['allow_anonymous'] == 1)
        ) {
            if ($current_forum['locked'] != 1 && $current_forum['locked'] != 1) {
                $actions .= '&nbsp;&nbsp;';
            } else {
                $actions .= get_lang('Forum blocked');
            }
        }
    }
}

$template->assign('forum_actions', $actions);
$template->assign('origin', api_get_origin());

/* Display Forum Category and the Forum information */
if (!isset($_SESSION['view'])) {
    $viewMode = $current_forum['default_view'];
} else {
    $viewMode = $_SESSION['view'];
}

$whiteList = ['flat', 'threaded', 'nested'];
if (isset($_GET['view']) && in_array($_GET['view'], $whiteList)) {
    $viewMode = $_GET['view'];
    $_SESSION['view'] = $viewMode;
}

if (empty($viewMode)) {
    $viewMode = 'flat';
}

if ($current_thread['thread_peer_qualify'] == 1) {
    Display::addFlash(Display::return_message(get_lang('To get the expected score in this forum, your contribution will have to be scored by another student, and you will have to score at least 2 other student\'s contributions. Until you reach this objective, even if scored, your contribution will show as a 0 score in the global grades for this course.'), 'info'));
}

$allowReport = reportAvailable();

// Are we in a lp ?
$origin = api_get_origin();
//delete attachment file
if (isset($_GET['action']) &&
    $_GET['action'] == 'delete_attach' &&
    isset($_GET['id_attach'])
) {
    delete_attachment(0, $_GET['id_attach']);
}

$origin = api_get_origin();
$sessionId = api_get_session_id();
$_user = api_get_user_info();
$userId = api_get_user_id();
$groupId = api_get_group_id();

// Decide whether we show the latest post first
$sortDirection = isset($_GET['posts_order']) && $_GET['posts_order'] === 'desc' ? 'DESC' : ($origin != 'learnpath' ? 'ASC' : 'DESC');
$posts = getPosts($current_forum, $threadId, $sortDirection, true);
$count = 0;
$group_id = api_get_group_id();
$locked = api_resource_is_locked_by_gradebook($threadId, LINK_FORUM_THREAD);
$sessionId = api_get_session_id();
$currentThread = get_thread_information($forumId, $threadId);
$userId = api_get_user_id();
$groupInfo = GroupManager::get_group_properties($group_id);
$postCount = 1;
$allowUserImageForum = api_get_course_setting('allow_user_image_forum');

// The user who posted it can edit his thread only if the course admin allowed this in the properties
// of the forum
// The course admin him/herself can do this off course always
$tutorGroup = GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo);

$postList = [];
foreach ($posts as $post) {
    $posterId = isset($post['user_id']) ? $post['user_id'] : 0;
    $username = '';
    if (isset($post['username'])) {
        $username = sprintf(get_lang('Login: %s'), $post['username']);
    }

    $name = $post['complete_name'];
    if (empty($posterId)) {
        $name = $post['poster_name'];
    }

    $post['user_data'] = '';
    if ($origin != 'learnpath') {
        if ($allowUserImageForum) {
            $post['user_data'] = '<div class="thumbnail">'.
                display_user_image($posterId, $name, $origin).'</div>';
        }

        $post['user_data'] .= Display::tag(
            'h4',
            display_user_link($posterId, $name, $origin, $username),
            ['class' => 'title-username']
        );

        $_user = api_get_user_info($posterId);
        $iconStatus = $_user['icon_status'];
        $post['user_data'] .= '<div class="user-type text-center">'.$iconStatus.'</div>';
    } else {
        if ($allowUserImageForum) {
            $post['user_data'] .= '<div class="thumbnail">'.
                display_user_image($posterId, $name, $origin).'</div>';
        }

        $post['user_data'] .= Display::tag(
            'p',
            $name,
            [
                'title' => api_htmlentities($username, ENT_QUOTES),
                'class' => 'lead',
            ]
        );
    }

    if ($origin != 'learnpath') {
        $post['user_data'] .= Display::tag(
            'p',
            Display::dateToStringAgoAndLongDate($post['post_date']),
            ['class' => 'post-date']
        );
    } else {
        $post['user_data'] .= Display::tag(
            'p',
            Display::dateToStringAgoAndLongDate($post['post_date']),
            ['class' => 'text-muted']
        );
    }

    // get attach id
    $attachment_list = get_attachment($post['post_id']);
    $id_attach = !empty($attachment_list) ? $attachment_list['iid'] : '';

    $iconEdit = '';
    $editButton = '';
    $askForRevision = '';

    if ((isset($groupInfo['iid']) && $tutorGroup) ||
        ($current_forum['allow_edit'] == 1 && $posterId == $userId) ||
        (api_is_allowed_to_edit(false, true) &&
        !(api_is_session_general_coach() && $current_forum['session_id'] != $sessionId))
    ) {
        if ($locked == false && postIsEditableByStudent($current_forum, $post)) {
            $editUrl = api_get_path(WEB_CODE_PATH).'forum/editpost.php?'.api_get_cidreq();
            $editUrl .= "&forum=$forumId&thread=$threadId&post={$post['post_id']}&id_attach=$id_attach";
            $iconEdit .= "<a href='".$editUrl."'>"
                .Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL)
                ."</a>";

            $editButton = Display::toolbarButton(
                get_lang('Edit'),
                $editUrl,
                'pencil',
                'default'
            );
        }
    }

    if ((isset($groupInfo['iid']) && $tutorGroup) ||
        api_is_allowed_to_edit(false, true) &&
        !(api_is_session_general_coach() && $current_forum['session_id'] != $sessionId)
    ) {
        if ($locked == false) {
            $deleteUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query(
                [
                    'forum' => $forumId,
                    'thread' => $threadId,
                    'action' => 'delete',
                    'content' => 'post',
                    'id' => $post['post_id'],
                ]
            );
            $iconEdit .= Display::url(
                Display::return_icon('delete.png', get_lang('Delete'), [], ICON_SIZE_SMALL),
                $deleteUrl,
                [
                    'onclick' => "javascript:if(!confirm('"
                        .addslashes(api_htmlentities(get_lang('Are you sure you want to delete this post? Deleting this post will also delete the replies on this post. Please check the threaded view to see which posts will also be deleted'), ENT_QUOTES))
                        ."')) return false;",
                    'id' => "delete-post-{$post['post_id']}",
                ]
            );
        }
    }

    if (api_is_allowed_to_edit(false, true) &&
        !(
            api_is_session_general_coach() &&
            $current_forum['session_id'] != $sessionId
        )
    ) {
        $iconEdit .= return_visible_invisible_icon(
            'post',
            $post['post_id'],
            $post['visible'],
            [
                'forum' => $forumId,
                'thread' => $threadId,
            ]
        );

        if ($count > 0) {
            $iconEdit .= "<a href=\"viewthread.php?".api_get_cidreq()
                ."&forum=$forumId&thread=$threadId&action=move&post={$post['post_id']}"
                ."\">".Display::return_icon('move.png', get_lang('Move post'), [], ICON_SIZE_SMALL)."</a>";
        }
    }

    $userCanQualify = $currentThread['thread_peer_qualify'] == 1 && $post['poster_id'] != $userId;
    if (api_is_allowed_to_edit(null, true)) {
        $userCanQualify = true;
    }

    $postIsARevision = false;
    $flagRevision = '';

    if ($post['poster_id'] == $userId) {
        $revision = getPostRevision($post['post_id']);
        if (empty($revision)) {
            $askForRevision = getAskRevisionButton($post['post_id'], $current_thread);
        } else {
            $postIsARevision = true;
            $languageId = api_get_language_id(strtolower($revision));
            $languageInfo = api_get_language_info($languageId);
            if ($languageInfo) {
                $languages = api_get_language_list_for_flag();
                $flagRevision = '<span class="flag-icon flag-icon-'.$languages[$languageInfo['english_name']].'"></span> ';
            }
        }
    } else {
        if (postNeedsRevision($post['post_id'])) {
            $askForRevision = giveRevisionButton($post['post_id'], $current_thread);
        } else {
            $revision = getPostRevision($post['post_id']);
            if (!empty($revision)) {
                $postIsARevision = true;
                $languageId = api_get_language_id(strtolower($revision));
                $languageInfo = api_get_language_info($languageId);
                if ($languageInfo) {
                    $languages = api_get_language_list_for_flag();
                    $flagRevision = '<span class="flag-icon flag-icon-'.$languages[$languageInfo['english_name']].'"></span> ';
                }
            }
        }
    }

    $post['is_a_revision'] = $postIsARevision;
    $post['flag_revision'] = $flagRevision;

    if (empty($currentThread['thread_qualify_max'])) {
        $userCanQualify = false;
    }

    if ($userCanQualify) {
        if ($count > 0) {
            $current_qualify_thread = showQualify(
                '1',
                $posterId,
                $threadId
            );
            if ($locked == false) {
                $iconEdit .= "<a href=\"forumqualify.php?".api_get_cidreq()
                    ."&forum=$forumId&thread=$threadId&action=list&post={$post['post_id']}"
                    ."&user={$post['user_id']}&user_id={$post['user_id']}"
                    ."&idtextqualify=$current_qualify_thread"
                    ."\" >".Display::return_icon('quiz.png', get_lang('Grade activity'))."</a>";
            }
        }
    }

    $reportButton = '';
    if ($allowReport) {
        $reportButton = getReportButton($post['post_id'], $current_thread);
    }

    $statusIcon = getPostStatus($current_forum, $post);
    if (!empty($iconEdit)) {
        $post['user_data'] .= "<div class='tools-icons'> $iconEdit $statusIcon </div>";
    } else {
        if (!empty(strip_tags($statusIcon))) {
            $post['user_data'] .= "<div class='tools-icons'> $statusIcon </div>";
        }
    }

    $buttonReply = '';
    $buttonQuote = '';
    $waitingValidation = '';

    if (($current_forum_category && $current_forum_category['locked'] == 0) &&
        $current_forum['locked'] == 0 && $current_thread['locked'] == 0 || api_is_allowed_to_edit(false, true)
    ) {
        if ($userId || ($current_forum['allow_anonymous'] == 1 && !$userId)) {
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                $buttonReply = Display::toolbarButton(
                    get_lang('Reply to this message'),
                    'reply.php?'.api_get_cidreq().'&'.http_build_query([
                        'forum' => $forumId,
                        'thread' => $threadId,
                        'post' => $post['post_id'],
                        'action' => 'replymessage',
                    ]),
                    'reply',
                    'primary',
                    ['id' => "reply-to-post-{$post['post_id']}"]
                );

                $buttonQuote = Display::toolbarButton(
                    get_lang('Quote this message'),
                    'reply.php?'.api_get_cidreq().'&'.http_build_query([
                        'forum' => $forumId,
                        'thread' => $threadId,
                        'post' => $post['post_id'],
                        'action' => 'quote',
                    ]),
                    'quote-left',
                    'success',
                    ['id' => "quote-post-{$post['post_id']}"]
                );

                if ($current_forum['moderated'] && !api_is_allowed_to_edit(false, true)) {
                    if (empty($post['status']) || $post['status'] == CForumPost::STATUS_WAITING_MODERATION) {
                        $buttonReply = '';
                        $buttonQuote = '';
                    }
                }
            }
        }
    } else {
        $closedPost = '';
        if ($current_forum_category && $current_forum_category['locked'] == 1) {
            $closedPost = Display::tag(
                'div',
                '<em class="fa fa-exclamation-triangle"></em> '.get_lang('Forum category Locked'),
                ['class' => 'alert alert-warning post-closed']
            );
        }
        if ($current_forum['locked'] == 1) {
            $closedPost = Display::tag(
                'div',
                '<em class="fa fa-exclamation-triangle"></em> '.get_lang('Forum blocked'),
                ['class' => 'alert alert-warning post-closed']
            );
        }
        if ($current_thread['locked'] == 1) {
            $closedPost = Display::tag(
                'div',
                '<em class="fa fa-exclamation-triangle"></em> '.get_lang('Thread is locked.'),
                ['class' => 'alert alert-warning post-closed']
            );
        }

        $post['user_data'] .= $closedPost;
    }

    // note: this can be removed here because it will be displayed in the tree
    if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) &&
        !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) &&
        !empty($whatsnew_post_info[$forumId][$post['thread_id']])
    ) {
        $post_image = Display::return_icon('forumpostnew.gif');
    } else {
        $post_image = Display::return_icon('forumpost.gif');
    }

    if ($post['post_notification'] == '1' && $post['poster_id'] == $userId) {
        $post_image .= Display::return_icon(
            'forumnotification.gif',
            get_lang('You will be notified')
        );
    }

    $post['current'] = false;
    if (isset($_GET['post_id']) && $_GET['post_id'] == $post['post_id']) {
        $post['current'] = true;
    }

    // Replace Re: with an icon
    $search = [
        get_lang('Re:'),
        'Re:',
        'RE:',
        'AW:',
        'Aw:',
    ];
    $replace = '<span>'.Display::returnFontAwesomeIcon('mail-reply').'</span>';
    $post['post_title'] = str_replace($search, $replace, Security::remove_XSS($post['post_title']));

    // The post title
    $titlePost = Display::tag('h3', $post['post_title'], ['class' => 'forum_post_title']);
    $post['post_title'] = '<a name="post_id_'.$post['post_id'].'"></a>';
    $post['post_title'] .= Display::tag('div', $titlePost, ['class' => 'post-header']);

    // the post body
    $post['post_data'] = Display::tag('div', $post['post_text'], ['class' => 'post-body']);

    // The check if there is an attachment
    $post['post_attachments'] = '';
    $attachment_list = getAllAttachment($post['post_id']);
    if (!empty($attachment_list) && is_array($attachment_list)) {
        foreach ($attachment_list as $attachment) {
            $user_filename = $attachment['filename'];
            $post['post_attachments'] .= Display::return_icon('attachment.gif', get_lang('Attachment'));
            $post['post_attachments'] .= '<a href="download.php?file=';
            $post['post_attachments'] .= $attachment['path'];
            $post['post_attachments'] .= ' "> '.$user_filename.' </a>';
            $post['post_attachments'] .= '<span class="forum_attach_comment" >'.$attachment['comment'].'</span>';
            if (($current_forum['allow_edit'] == 1 && $post['user_id'] == $userId) ||
                (api_is_allowed_to_edit(false, true) && !(api_is_session_general_coach() && $current_forum['session_id'] != $sessionId))
            ) {
                $post['post_attachments'] .= '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete_attach&id_attach='
                    .$attachment['iid'].'&forum='.$forumId.'&thread='.$threadId
                    .'" onclick="javascript:if(!confirm(\''
                    .addslashes(api_htmlentities(get_lang('Please confirm your choice'), ENT_QUOTES)).'\')) return false;">'
                    .Display::return_icon('delete.png', get_lang('Delete')).'</a><br />';
            }
        }
    }

    $post['post_buttons'] = "$askForRevision $editButton $reportButton $buttonReply $buttonQuote $waitingValidation";
    $postList[] = $post;

    // The post has been displayed => it can be removed from the what's new array
    unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]);
    unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]);
    $count++;
}

$template->assign('posts', $postList);

$formToString = '';
$showForm = true;
if (!api_is_allowed_to_edit(false, true) &&
    (($current_forum_category && $current_forum_category['visibility'] == 0) || $current_forum['visibility'] == 0)
) {
    $showForm = false;
}

if (!api_is_allowed_to_edit(false, true) &&
    (
        ($current_forum_category && $current_forum_category['locked'] != 0) ||
            $current_forum['locked'] != 0 || $current_thread['locked'] != 0
    )
) {
    $showForm = false;
}

if (!$_user['user_id'] && $current_forum['allow_anonymous'] == 0) {
    $showForm = false;
}

if ($current_forum['forum_of_group'] != 0) {
    $show_forum = GroupManager::user_has_access(
        api_get_user_id(),
        $current_forum['forum_of_group'],
        GroupManager::GROUP_TOOL_FORUM
    );
    if (!$show_forum) {
        $showForm = false;
    }
}

if ($showForm) {
    $form = show_add_post_form(
        $current_forum,
        'replythread',
        null,
        false
    );
    $formToString = $form->returnForm();
}

$template->assign('form', $formToString);

$layout = $template->get_template('forum/posts.tpl');

$template->display($layout);
