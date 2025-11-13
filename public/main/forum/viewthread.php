<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

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

// Ajax libs
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);

// Recover Thread/Forum IDs
$threadId = isset($_REQUEST['thread']) ? (int) ($_REQUEST['thread']) : 0;
$forumId = isset($_REQUEST['forum']) ? (int) ($_REQUEST['forum']) : 0;

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq();

// Ajax: delete attachment
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

// Root labels used in breadcrumbs (consistent naming)
$forumsRootLabel = get_lang('Forums');
$forumUrl        = api_get_path(WEB_CODE_PATH).'forum/';

// Context
$origin = api_get_origin();
$_user = api_get_user_info();
$my_search = null;
$moveForm = '';

$forumId = isset($_GET['forum']) ? (int) $_GET['forum'] : 0;
$postId = isset($_GET['post_id']) ? $_GET['post_id'] : 0;
$threadId = isset($_GET['thread']) ? (int) $_GET['thread'] : 0;

$repo = Container::getForumRepository();
/** @var CForum|null $forumEntity */
$forumEntity = !empty($forumId) ? $repo->find($forumId) : null;

$repoThread = Container::getForumThreadRepository();
/** @var CForumThread|null $threadEntity */
$threadEntity = $repoThread->find($threadId);

if (empty($threadEntity)) {
    $url = api_get_path(WEB_CODE_PATH).'forum/viewforum.php?'.api_get_cidreq().'&forum='.$forumId;
    header('Location: '.$url);
    exit;
}

$repoPost = Container::getForumPostRepository();
/** @var CForumPost|null $postEntity */
$postEntity = !empty($postId) ? $repoPost->find($postId) : null;

$courseEntity  = api_get_course_entity(api_get_course_int_id());
$sessionEntity = api_get_session_entity(api_get_session_id());

$current_forum_category = $forumEntity ? $forumEntity->getForumCategory() : null;

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$groupId     = api_get_group_id();
$groupEntity = !empty($groupId) ? api_get_group_entity($groupId) : null;

$sessionId = api_get_session_id();

$ajaxURL = api_get_path(WEB_AJAX_PATH).'forum.ajax.php?'.api_get_cidreq().'&a=change_post_status';
$htmlHeadXtra[] = '<script>
// Ajax: toggle post status (visible/invisible)
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

$my_action = $_GET['action'] ?? '';

// Log (kept)
$logInfo = [
    'tool'            => TOOL_FORUM,
    'tool_id'         => $forumId,
    'tool_id_detail'  => $threadId,
    'action'          => !empty($my_action) ? $my_action : 'view-thread',
    'action_details'  => isset($_GET['content']) ? $_GET['content'] : '',
];
Event::registerLog($logInfo);

$currentUrl = api_get_path(WEB_CODE_PATH).'forum/viewthread.php?forum='.$forumId.'&'.api_get_cidreq().'&thread='.$threadId;

// Actions
switch ($my_action) {
    case 'delete_attach':
        delete_attachment($_GET['post'], $_GET['id_attach']);
        header('Location: '.$currentUrl);
        exit;

    case 'delete':
        if (
            isset($_GET['content'], $_GET['id']) &&
            (api_is_allowed_to_edit(false, true) ||
                ($groupEntity && GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity)))
        ) {
            /** @var CForumPost|null $postEntity */
            $postEntity = $repoPost->find($_GET['id']);
            if (null !== $postEntity) {
                deletePost($postEntity);
            }
        }
        header('Location: '.$currentUrl);
        exit;

    case 'invisible':
    case 'visible':
        if (
            isset($_GET['id']) &&
            (api_is_allowed_to_edit(false, true) ||
                ($groupEntity && GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity)))
        ) {
            /** @var CForumPost|null $postEntity */
            $postEntity = $repoPost->find($_GET['id']);
            $message = approvePost($postEntity, $_GET['action']);
            Display::addFlash(Display::return_message(get_lang($message)));
        }
        header('Location: '.$currentUrl);
        exit;

    case 'move':
        if (isset($_GET['post'])) {
            $form = move_post_form();
            if ($form->validate()) {
                $values = $form->exportValues();
                store_move_post($values);

                $currentUrl = api_get_path(WEB_CODE_PATH).
                    'forum/viewthread.php?forum='.$forumId.'&'.api_get_cidreq().'&thread='.$threadId;

                header('Location: '.$currentUrl);
                exit;
            }
            $moveForm = $form->returnForm();
        }

        break;

    case 'report':
        $result = reportPost($postEntity, $forumEntity, $threadEntity);
        Display::addFlash(Display::return_message(get_lang('Reported')));
        header('Location: '.$currentUrl);
        exit;

    case 'ask_revision':
        if ('true' === api_get_setting('forum.allow_forum_post_revisions')) {
            $result = savePostRevision($postEntity);
            Display::addFlash(Display::return_message(get_lang('Saved.')));
        }
        header('Location: '.$currentUrl);
        exit;
}

// Breadcrumbs
$my_search = isset($_GET['search']) ? $_GET['search'] : '';

if (!empty($groupId)) {
    // Group breadcrumbs
    $interbreadcrumb[] = [
        'url'  => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url'  => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('Group area').' '.($groupEntity ? $groupEntity->getTitle() : ''),
    ];
    // Link back to the forum (threads list)
    $interbreadcrumb[] = [
        'url'  => $forumUrl.'viewforum.php?'.api_get_cidreq().'&forum='.$forumId.'&search='.Security::remove_XSS(urlencode($my_search)),
        'name' => $forumEntity ? Security::remove_XSS($forumEntity->getTitle()) : get_lang('Forum'),
    ];
    // Current thread (not linked)
    $interbreadcrumb[] = [
        'url'  => '#',
        'name' => Security::remove_XSS($threadEntity->getTitle()),
    ];
} else {
    // Forums index
    $interbreadcrumb[] = [
        'url'  => $forumUrl.'index.php?'.api_get_cidreq().'&search='.Security::remove_XSS(urlencode($my_search)),
        'name' => $forumsRootLabel,
    ];
    // Category (if available)
    if ($current_forum_category && $current_forum_category->getIid()) {
        $interbreadcrumb[] = [
            'url'  => $forumUrl.'viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.$current_forum_category->getIid(),
            'name' => Security::remove_XSS($current_forum_category->getTitle()),
        ];
    }
    // Forum (threads list)
    if ($forumEntity) {
        $interbreadcrumb[] = [
            'url'  => $forumUrl.'viewforum.php?'.api_get_cidreq().'&forum='.$forumId.'&search='.Security::remove_XSS(urlencode($my_search)),
            'name' => Security::remove_XSS($forumEntity->getTitle()),
        ];
    }
    // Current thread (not linked)
    $interbreadcrumb[] = [
        'url'  => '#',
        'name' => Security::remove_XSS($threadEntity->getTitle()),
    ];
}

// Visibility constraints
if (!api_is_allowed_to_create_course()
    && (
        !$forumEntity->isVisible($courseEntity)
        || !$threadEntity->isVisible($courseEntity)
    )
) {
    api_not_allowed();
}
$repoThread->increaseView($threadEntity);

// Template
if ('learnpath' === $origin) {
    $template = new Template('', false, false, true, true, false);
} else {
    $template = new Template();
}

// Top actions (kept as-is, could be compacted later if desired)
$actions = '<span style="float:right;">'.search_link().'</span>';
if ('learnpath' != $origin) {
    $actions .= '<a href="'.$forumUrl.'viewforum.php?forum='.$forumId.'&'.api_get_cidreq().'">'
        .Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to forum')).'</a>';
}

if (($current_forum_category && 0 == $current_forum_category->getLocked())
    && 0 == $forumEntity->getLocked() && 0 == $threadEntity->getLocked() || api_is_allowed_to_edit(false, true)
) {
    if ($_user['user_id'] || (1 == $forumEntity->getAllowAnonymous() && !$_user['user_id'])) {
        if ('learnpath' == $origin && !empty($threadId)) {
            $actions .= '<a href="'.$forumUrl.'viewforum.php?forum='.$forumId.'&'.api_get_cidreq().'">'
                .Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to forum')).'</a>';
        }

        if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
            $actions .= '<a href="'.$forumUrl.'reply.php?'.api_get_cidreq().'&forum='.$forumId.'&thread='.$threadId.'&action=replythread">'
                .Display::getMdiIcon('reply', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Reply to this thread')).'</a>';
        }
        if ((
            api_is_allowed_to_edit(false, true) && !api_is_session_general_coach()
        )
            || (1 == $forumEntity->getAllowNewThreads() && isset($_user['user_id']))
            || (1 == $forumEntity->getAllowNewThreads() && !isset($_user['user_id']) && 1 == $forumEntity->getAllowAnonymous())
        ) {
            if (1 != $forumEntity->getLocked() && 1 != $forumEntity->getLocked()) {
                $actions .= '&nbsp;&nbsp;';
            } else {
                $actions .= get_lang('Forum blocked');
            }
        }
    }
}

$actions = Display::toolbarAction('toolbar', [$actions]);

$template->assign('forum_actions', $actions);
$template->assign('origin', api_get_origin());

/* Display Forum Category and Forum information */
if (!isset($_SESSION['view'])) {
    $viewMode = $forumEntity->getDefaultView();
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

// Info notice (peer qualify)
if ($threadEntity->isThreadPeerQualify()) {
    Display::addFlash(Display::return_message(
        get_lang('To get the expected score in this forum, your contribution will have to be scored by another student, and you will have to score at least 2 other students\' contributions. Until you reach this objective, even if scored, your contribution will show as a 0 score in the global grades for this course.'),
        'info'
    ));
}

$allowReport = reportAvailable();
$origin = api_get_origin();
$sessionId = api_get_session_id();
$_user = api_get_user_info();
$userId = api_get_user_id();
$groupId = api_get_group_id();

// Posts ordering
$sortDirection = isset($_GET['posts_order']) && 'desc' === $_GET['posts_order'] ? 'DESC' : ('learnpath' != $origin ? 'ASC' : 'DESC');
$posts = getPosts($forumEntity, $threadId, $sortDirection, true);
$count = 0;
$group_id = api_get_group_id();
$locked = api_resource_is_locked_by_gradebook($threadId, LINK_FORUM_THREAD);
$sessionId = api_get_session_id();
$userId = api_get_user_id();
$postCount = 1;
$allowUserImageForum = api_get_course_setting('allow_user_image_forum');
$tutorGroup = false;
$groupEntity = null;
if (!empty($group_id)) {
    $groupEntity = api_get_group_entity($group_id);
    $tutorGroup = GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity);
}

$opPostId = (int) $repoPost
    ->createQueryBuilder('fp')
    ->select('MIN(fp.iid)')
    ->where('fp.thread = :thread')
    ->setParameter('thread', $threadId)
    ->getQuery()
    ->getSingleScalarResult()
;

$postList = [];
foreach ($posts as $post) {
    /** @var CForumPost $postEntity */
    $postEntity = $post['entity'];

    // user may be null (deleted)
    $posterUser = $postEntity->getUser();
    $posterId = $posterUser ? $posterUser->getId() : 0;

    $username = '';
    if (isset($post['username'])) {
        $username = sprintf(get_lang('Login: %s'), $post['username']);
    }

    $post['user_data'] = '';
    $post['author'] = $posterUser;

    if ('learnpath' !== $origin) {
        $post['post_date_to_display'] = Display::tag(
            'p',
            Display::dateToStringAgoAndLongDate($post['post_date']),
            ['class' => 'post-date']
        );
    } else {
        $post['post_date_to_display'] = Display::tag(
            'p',
            Display::dateToStringAgoAndLongDate($post['post_date']),
            ['class' => 'text-muted']
        );
    }

    // Identify OP (first post in the thread)
    $isOp = ((int) $post['post_id'] === $opPostId);
    $post['post_is_first_in_thread'] = $isOp; // exposed for template if needed

    // Attachment id (first)
    $attachment_list = get_attachment($post['post_id']);
    $id_attach = !empty($attachment_list) ? $attachment_list['iid'] : '';

    $iconEdit = '';
    $editButton = '';
    $askForRevision = '';

    if (($groupEntity && $tutorGroup)
        || (1 == $forumEntity->getAllowEdit() && $posterId == $userId)
        || (api_is_allowed_to_edit(false, true) && !api_is_session_general_coach())
    ) {
        // pass entity to postIsEditableByStudent() (array caused fatal when calling getStatus())
        if (false == $locked && postIsEditableByStudent($forumEntity, $postEntity)) {
            $editUrl = api_get_path(WEB_CODE_PATH).'forum/editpost.php?'.api_get_cidreq()."&forum=$forumId&thread=$threadId&post={$post['post_id']}&id_attach=$id_attach";
            $editButton = Display::url(
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                $editUrl,
                [
                    'class' => 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15 focus:outline-none focus:ring-2 focus:ring-primary',
                    'title' => get_lang('Edit'),
                    'aria-label' => get_lang('Edit'),
                ]
            );
        }
    }

    if (($groupEntity && $tutorGroup) || (api_is_allowed_to_edit(false, true) && !api_is_session_general_coach())) {
        if (false == $locked) {
            $deleteUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
                'forum' => $forumId,
                'thread' => $threadId,
                'action' => 'delete',
                'content' => 'post',
                'id' => $post['post_id'],
            ]);
            $iconEdit .= Display::url(
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')),
                $deleteUrl,
                [
                    'class' => 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15',
                    'title' => get_lang('Delete'),
                    'aria-label' => get_lang('Delete'),
                    'onclick' => "if(!confirm('".addslashes(api_htmlentities(get_lang('Are you sure you want to delete this post? Deleting this post will also delete the replies on this post. Please check the threaded view to see which posts will also be deleted'), \ENT_QUOTES))."')) return false;",
                    'id' => "delete-post-{$post['post_id']}",
                ]
            );
        }
    }

    // Visibility / Move icons
    if (api_is_allowed_to_edit(false, true) && !api_is_session_general_coach()) {
        $iconEdit .= returnVisibleInvisibleIcon(
            'post',
            $post['post_id'],
            $post['visible'],
            [
                'forum' => $forumId,
                'thread' => $threadId,
            ]
        );

        if ($count > 0) {
            $iconEdit .= '<a class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15" href="viewthread.php?'.api_get_cidreq()."&forum=$forumId&thread=$threadId&action=move&post={$post['post_id']}".'">'
                .Display::getMdiIcon(ActionIcon::MOVE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Move post')).'</a>';
        }
    }

    $userCanQualify = 1 == $threadEntity->isThreadPeerQualify() && $posterId != $userId;
    if (api_is_allowed_to_edit(null, true)) {
        $userCanQualify = true;
    }

    $postIsARevision = false;
    $flagRevision    = '';

    if ($posterId == $userId) {
        $revision = getPostRevision($post['post_id']);
        if (empty($revision)) {
            // Compact "ask revision" icon linking to ?action=ask_revision
            if ('true' === api_get_setting('forum.allow_forum_post_revisions')) {
                $askRevisionUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
                        'forum' => $forumId,
                        'thread' => $threadId,
                        'action' => 'ask_revision',
                        'post_id' => $post['post_id'],
                    ]);
                $askForRevision = Display::url(
                    Display::getMdiIcon('history', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Ask for revision')),
                    $askRevisionUrl,
                    [
                        'class' => 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15',
                        'title' => get_lang('Ask for revision'),
                        'aria-label' => get_lang('Ask for revision'),
                    ]
                );
            }
        } else {
            $postIsARevision = true;
            $languageId   = api_get_language_id(strtolower($revision));
            $languageInfo = api_get_language_info($languageId);
            if ($languageInfo) {
                $languages   = api_get_language_list_for_flag();
                $flagRevision = '<span class="flag-icon flag-icon-'.$languages[$languageInfo['english_name']].'"></span> ';
            }
        }
    } else {
        if (postNeedsRevision($postEntity)) {
            $askForRevision = getGiveRevisionButton($post['post_id'], $threadEntity);
        } else {
            $revision = getPostRevision($post['post_id']);
            if (!empty($revision)) {
                $postIsARevision = true;
                $languageId   = api_get_language_id(strtolower($revision));
                $languageInfo = api_get_language_info($languageId);
                if ($languageInfo) {
                    $languages   = api_get_language_list_for_flag();
                    $flagRevision = '<span class="flag-icon flag-icon-'.$languages[$languageInfo['english_name']].'"></span> ';
                }
            }
        }
    }

    $post['is_a_revision'] = $postIsARevision;
    $post['flag_revision'] = $flagRevision;

    if (empty($threadEntity->getThreadQualifyMax())) {
        $userCanQualify = false;
    }

    if ($userCanQualify) {
        if ($count > 0) {
            $current_qualify_thread = showQualify('1', $posterId, $threadId);
            $userIdTmp = $posterUser ? $posterUser->getId() : 0;
            if (false == $locked) {
                $iconEdit .= '<a class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15" href="forumqualify.php?'.api_get_cidreq()
                    ."&forum=$forumId&thread=$threadId&action=list&post={$post['post_id']}"
                    ."&user={$userIdTmp}&user_id={$userIdTmp}"
                    ."&idtextqualify=$current_qualify_thread"
                    .'">'.Display::getMdiIcon(ToolIcon::QUIZ, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Grade activity')).'</a>';
            }
        }
    }

    // REPORT (icon-only)
    $reportButton = '';
    if ($allowReport) {
        $reportUrl = api_get_self().'?'.api_get_cidreq().'&'.http_build_query([
            'forum' => $forumId,
            'thread' => $threadId,
            'action' => 'report',
            'post' => $post['post_id'],
        ]);
        $reportButton = Display::url(
            Display::getMdiIcon(ToolIcon::MESSAGE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Report')),
            $reportUrl,
            [
                'class' => 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15',
                'title' => get_lang('Report'),
                'aria-label' => get_lang('Report'),
            ]
        );
    }

    $statusIcon = getPostStatus($forumEntity, $post);
    $post['tool_icons'] = '';
    if (!empty($iconEdit)) {
        $post['tool_icons'] = "$iconEdit $statusIcon";
    } else {
        if (!empty(strip_tags($statusIcon))) {
            $post['tool_icons'] = $statusIcon;
        }
    }

    $buttonReply = '';
    $buttonQuote = '';
    $waitingValidation = '';

    if (!$isOp) {
        if (($current_forum_category && 0 == $current_forum_category->getLocked())
            && 0 == $forumEntity->getLocked() && 0 == $threadEntity->getLocked() || api_is_allowed_to_edit(false, true)
        ) {
            if ($userId || (1 == $forumEntity->getAllowAnonymous() && !$userId)) {
                if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                    $replyUrl = 'reply.php?'.api_get_cidreq().'&'.http_build_query([
                        'forum' => $forumId,
                        'thread' => $threadId,
                        'post' => $post['post_id'],
                        'action' => 'replymessage',
                    ]);
                    $quoteUrl = 'reply.php?'.api_get_cidreq().'&'.http_build_query([
                        'forum' => $forumId,
                        'thread' => $threadId,
                        'post' => $post['post_id'],
                        'action' => 'quote',
                    ]);

                    // students only get reply/quote on validated posts
                    if ($forumEntity->isModerated() && !api_is_allowed_to_edit(false, true)) {
                        if (empty($post['status']) || CForumPost::STATUS_WAITING_MODERATION == $post['status']) {
                            $replyUrl = '';
                            $quoteUrl = '';
                        }
                    }

                    if (!empty($replyUrl)) {
                        $buttonReply = Display::url(
                            Display::getMdiIcon('reply', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Reply to this message')),
                            $replyUrl,
                            [
                                'class' => 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15',
                                'title' => get_lang('Reply to this message'),
                                'aria-label' => get_lang('Reply to this message'),
                                'id' => "reply-to-post-{$post['post_id']}",
                            ]
                        );
                    }

                    if (!empty($quoteUrl)) {
                        $buttonQuote = Display::url(
                            Display::getMdiIcon('comment-quote', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Quote this message')),
                            $quoteUrl,
                            [
                                'class' => 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-25 hover:bg-gray-15',
                                'title' => get_lang('Quote this message'),
                                'aria-label' => get_lang('Quote this message'),
                                'id' => "quote-post-{$post['post_id']}",
                            ]
                        );
                    }
                }
            }
        } else {
            // Locked informational banners (kept)
            $closedPost = '';
            if ($current_forum_category && 1 == $current_forum_category->getLocked()) {
                $closedPost = Display::tag('div', '<em class="fa fa-exclamation-triangle"></em> '.get_lang('Forum category Locked'), ['class' => 'alert alert-warning post-closed']);
            }
            if (1 == $forumEntity->getLocked()) {
                $closedPost = Display::tag('div', '<em class="fa fa-exclamation-triangle"></em> '.get_lang('Forum blocked'), ['class' => 'alert alert-warning post-closed']);
            }
            if (1 == $threadEntity->getLocked()) {
                $closedPost = Display::tag('div', '<em class="fa fa-exclamation-triangle"></em> '.get_lang('Thread is locked.'), ['class' => 'alert alert-warning post-closed']);
            }
            $post['user_data'] .= $closedPost;
        }
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
    $replace = '<span>'.Display::getMdiIcon('reply', 'ch-tool-icon', '', ICON_SIZE_SMALL).'</span>';
    $post['post_title'] = str_replace($search, $replace, Security::remove_XSS($post['post_title']));

    // Title
    $titlePost = Display::tag('h3', $post['post_title'], ['class' => 'forum_post_title']);
    $post['post_title'] = '<a name="post_id_'.$post['post_id'].'"></a>';
    $post['post_title'] .= Display::tag('div', $titlePost, ['class' => 'post-header']);

    // Body
    $post['post_data'] = Display::tag('div', $post['post_text'], ['class' => 'post-body']);

    // Attachments
    $post['post_attachments'] = '';
    $attachments = $postEntity->getAttachments();
    if ($attachments) {
        $repoAttach = Container::getForumAttachmentRepository();

        /** @var CForumAttachment $attachment */
        foreach ($attachments as $attachment) {
            $post['post_attachments'] .= Display::getMdiIcon('paperclip', 'ch-tool-icon', '', ICON_SIZE_SMALL);
            $url = $repoAttach->getResourceFileDownloadUrl($attachment).'?'.api_get_cidreq();
            $post['post_attachments'] .= Display::url($attachment->getFilename(), $url);
            $post['post_attachments'] .= '<span class="forum_attach_comment">'.$attachment->getComment().'</span>';
            if ((1 == $forumEntity->getAllowEdit() && $post['user_id'] == $userId)
                || (api_is_allowed_to_edit(false, true) && !api_is_session_general_coach())
            ) {
                $post['post_attachments'] .= '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete_attach&id_attach='.$attachment->getIid().'&forum='.$forumId.'&thread='.$threadId.'&post='.$post['post_id'].'" onclick="if(!confirm(\''.addslashes(api_htmlentities(get_lang('Please confirm your choice'), \ENT_QUOTES)).'\')) return false;">'.Display::getMdiIcon('delete', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a><br />';
            }
        }
    }

    $buttons = array_filter([
        $askForRevision,
        $editButton,
        $reportButton,
        $buttonReply,
        $buttonQuote,
        $waitingValidation,
    ], static function ($html) {
        return !empty($html) && '' !== trim($html);
    });

    // Compose bottom button row
    $post['post_buttons'] = Display::tag(
        'div',
        implode('', $buttons),
        ['class' => 'flex items-center gap-1 justify-end']
    );

    $postList[] = $post;
    $count++;
}

$template->assign('posts', $postList);

$formToString = '';
$showForm = true;
if (!api_is_allowed_to_edit(false, true)
    && (($current_forum_category && 0 == !$current_forum_category->isVisible($courseEntity)) || !$forumEntity->isVisible($courseEntity))
) {
    $showForm = false;
}

if (!api_is_allowed_to_edit(false, true)
    && (
        ($current_forum_category && 0 != $current_forum_category->getLocked())
            || 0 != $forumEntity->getLocked() || 0 != $threadEntity->getLocked()
    )
) {
    $showForm = false;
}

if (!$_user['user_id'] && 0 == $forumEntity->getAllowAnonymous()) {
    $showForm = false;
}

if (0 != $forumEntity->getForumOfGroup()) {
    $show_forum = GroupManager::userHasAccess(
        api_get_user_id(),
        api_get_group_entity($forumEntity->getForumOfGroup()),
        GroupManager::GROUP_TOOL_FORUM
    );
    if (!$show_forum) {
        $showForm = false;
    }
}

if ($showForm) {
    $form = show_add_post_form(
        $forumEntity,
        $threadEntity,
        null,
        null,
        null
    );
    $formToString = $form->returnForm();
}

$template->assign('form', $formToString);
$template->assign('move_form', $moveForm);

$layout = $template->get_template('forum/posts.tpl');

$template->display($layout);
