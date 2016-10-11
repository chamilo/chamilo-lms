<?php
/* For licensing terms, see /license.txt */

/**
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com> UI Improvements + lots of bugfixes
 * @copyright Ghent University
 * @package chamilo.forum
 */

// Are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
    $origin = Security::remove_XSS($_GET['origin']);
}

//delete attachment file
if (isset($_GET['action']) &&
    $_GET['action'] == 'delete_attach' &&
    isset($_GET['id_attach'])
) {
    delete_attachment(0, $_GET['id_attach']);
}

// Decide whether we show the latest post first
$sortDirection = isset($_GET['posts_order']) && $_GET['posts_order'] === 'desc' ? 'DESC' : ($origin != 'learnpath' ? 'ASC' : 'DESC');

$rows = getPosts($current_forum, $_GET['thread'], $sortDirection, true);
$count = 0;
$clean_forum_id = intval($_GET['forum']);
$clean_thread_id = intval($_GET['thread']);
$group_id = api_get_group_id();
$locked = api_resource_is_locked_by_gradebook($clean_thread_id, LINK_FORUM_THREAD);
$sessionId = api_get_session_id();
$currentThread = get_thread_information($clean_forum_id, $_GET['thread']);
$userId = api_get_user_id();
$groupInfo = GroupManager::get_group_properties($group_id);
$postCount = 1;

foreach ($rows as $post) {
    // The style depends on the status of the message: approved or not.
    if ($post['visible'] == '0') {
        $titleclass = 'forum_message_post_title_2_be_approved';
        $messageclass = 'forum_message_post_text_2_be_approved';
        $leftclass = 'forum_message_left_2_be_approved';
    } else {
        $titleclass = 'forum_message_post_title';
        $messageclass = 'forum_message_post_text';
        $leftclass = 'forum_message_left';
    }

    $indent = $post['indent_cnt'];

    $html = '';
    $html .= '<div class="col-md-offset-' . $indent . '" >';
    $html .= '<div class="panel panel-default forum-post">';
    $html .= '<div class="panel-body">';

    $html .= '<div class="row">';
    $html .= '<div class="col-md-2">';


    $username = sprintf(get_lang('LoginX'), $post['username']);
    if ($post['user_id'] == '0') {
        $name = $post['poster_name'];
    } else {
        $name = api_get_person_name($post['firstname'], $post['lastname']);
    }

    if ($origin != 'learnpath') {
        if (api_get_course_setting('allow_user_image_forum')) {
            $html .= '<div class="thumbnail">' . display_user_image($post['user_id'], $name, $origin) . '</div>';
        }

        $html .= Display::tag(
            'h4',
            display_user_link($post['user_id'], $name, $origin, $username),
            array('class' => 'title-username')
        );
    } else {
        if (api_get_course_setting('allow_user_image_forum')) {
            $html .= '<div class="thumbnail">' . display_user_image($post['user_id'], $name, $origin) . '</div>';
        }

        $html .= Display::tag(
            'p',
            $name,
            array(
                'title' => api_htmlentities($username, ENT_QUOTES),
                'class' => 'lead'
            )
        );
    }

    if ($origin != 'learnpath') {
        $html .= Display::tag(
            'p',
            api_convert_and_format_date($post['post_date']),
            array('class' => 'post-date')
        );
    } else {
        $html .= Display::tag(
            'p',
            api_convert_and_format_date($post['post_date'], DATE_TIME_FORMAT_SHORT),
            array('class' => 'text-muted')
        );
    }

    // get attach id
    $attachment_list = get_attachment($post['post_id']);
    $id_attach = !empty($attachment_list) ? $attachment_list['iid'] : '';

    $iconEdit = '';
    // The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
    // The course admin him/herself can do this off course always

    if (
    (isset($groupInfo['iid']) &&GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo['iid'])) ||
        ($current_forum['allow_edit'] == 1 && $post['user_id'] == $userId) ||
        (api_is_allowed_to_edit(false, true) && !(api_is_course_coach() && $current_forum['session_id'] != $sessionId))
    ) {
        if ($locked == false) {
            $iconEdit .= "<a href=\"editpost.php?" . api_get_cidreq()
                . "&forum=$clean_forum_id&thread=$clean_thread_id&post={$post['post_id']}&id_attach=$id_attach"
                . "\">"
                . Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL)
                . "</a>";
        }
    }

    if (
        (isset($groupInfo['iid']) && GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo['iid'])) ||
        api_is_allowed_to_edit(false, true) &&
        !(api_is_course_coach() && $current_forum['session_id'] != $sessionId)
    ) {
        if ($locked == false) {
            $deleteUrl = api_get_self() . '?' . api_get_cidreq() . '&' . http_build_query([
                    'forum' => $clean_forum_id,
                    'thread' => $clean_thread_id,
                    'action' => 'delete',
                    'content' => 'post',
                    'id' => $post['post_id']
                ]);
            $iconEdit .= Display::url(
                Display::return_icon('delete.png', get_lang('Delete'), array(), ICON_SIZE_SMALL),
                $deleteUrl,
                [
                    'onclick' => "javascript:if(!confirm('"
                        . addslashes(api_htmlentities(get_lang('DeletePost'), ENT_QUOTES))
                        . "')) return false;",
                    'id' => "delete-post-{$post['post_id']}"
                ]
            );
        }
    }

    if (
        api_is_allowed_to_edit(false, true) &&
        !(
            api_is_course_coach() &&
            $current_forum['session_id'] != $sessionId
        )
    ) {
        $iconEdit .= return_visible_invisible_icon(
            'post',
            $post['post_id'],
            $post['visible'],
            array(
                'forum' => $clean_forum_id,
                'thread' => $clean_thread_id
            )
        );

        if ($count > 0) {
            $iconEdit .= "<a href=\"viewthread.php?" . api_get_cidreq()
                . "&forum=$clean_forum_id&thread=$clean_thread_id&action=move&post={$post['post_id']}"
                . "\">" . Display::return_icon('move.png', get_lang('MovePost'), array(), ICON_SIZE_SMALL) . "</a>";
        }
    }

    $userCanQualify = $currentThread['thread_peer_qualify'] == 1 && $post['poster_id'] != $userId;
    if (api_is_allowed_to_edit(null, true)) {
        $userCanQualify = true;
    }

    if (empty($currentThread['thread_qualify_max'])) {
        $userCanQualify = false;
    }

    if ($userCanQualify) {
        if ($count > 0) {
            $current_qualify_thread = showQualify(
                '1', $post['user_id'], $_GET['thread']
            );
            if ($locked == false) {
                $iconEdit .= "<a href=\"forumqualify.php?" . api_get_cidreq()
                    . "&forum=$clean_forum_id&thread=$clean_thread_id&action=list&post={$post['post_id']}"
                    . "&user={$post['user_id']}&user_id={$post['user_id']}"
                    . "&idtextqualify=$current_qualify_thread"
                    . "\" >" . Display::return_icon('quiz.gif', get_lang('Qualify')) . "</a>";
            }
        }
    }

    $statusIcon = getPostStatus($current_forum, $post);

    if ($iconEdit != '') {
        $html .= '<div class="tools-icons">' . $iconEdit . ' '.$statusIcon.'</div>';
    }

    if (($current_forum_category && $current_forum_category['locked'] == 0) &&
        $current_forum['locked'] == 0 && $current_thread['locked'] == 0 || api_is_allowed_to_edit(false, true)
    ) {
        if ($userId || ($current_forum['allow_anonymous'] == 1 && !$userId)) {
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                $buttonReply = Display::toolbarButton(
                    get_lang('ReplyToMessage'),
                    'reply.php?' . api_get_cidreq() . '&' . http_build_query([
                        'forum' => $clean_forum_id,
                        'thread' => $clean_thread_id,
                        'post' => $post['post_id'],
                        'action' => 'replymessage'
                    ]),
                    'reply',
                    'primary',
                    ['id' => "reply-to-post-{$post['post_id']}"]
                );

                $buttonQuote = Display::toolbarButton(
                    get_lang('QuoteMessage'),
                    'reply.php?' . api_get_cidreq() . '&' . http_build_query([
                        'forum' => $clean_forum_id,
                        'thread' => $clean_thread_id,
                        'post' => $post['post_id'],
                        'action' => 'quote'
                    ]),
                    'quote-left',
                    'success',
                    ['id' => "quote-post-{$post['post_id']}"]
                );
            }
        }
    } else {
        if ($current_forum_category && $current_forum_category['locked'] == 1) {
            $closedPost = Display::tag(
                'div',
                '<em class="fa fa-exclamation-triangle"></em> ' . get_lang('ForumcategoryLocked'),
                array('class' => 'alert alert-warning post-closed')
            );
        }
        if ($current_forum['locked'] == 1) {
            $closedPost = Display::tag(
                'div',
                '<em class="fa fa-exclamation-triangle"></em> ' . get_lang('ForumLocked'),
                array('class' => 'alert alert-warning post-closed')
            );
        }
        if ($current_thread['locked'] == 1) {
            $closedPost = Display::tag(
                'div',
                '<em class="fa fa-exclamation-triangle"></em> ' . get_lang('ThreadLocked'),
                array('class' => 'alert alert-warning post-closed')
            );
        }

        $html .= $closedPost;
    }

    $html .= '</div>';


    // note: this can be removed here because it will be displayed in the tree
    if (
        isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) &&
        !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) &&
        !empty($whatsnew_post_info[$_GET['forum']][$post['thread_id']])
    ) {
        $post_image = Display::return_icon('forumpostnew.gif');
    } else {
        $post_image = Display::return_icon('forumpost.gif');
    }

    if ($post['post_notification'] == '1' && $post['poster_id'] == $userId) {
        $post_image .= Display::return_icon(
                'forumnotification.gif', get_lang('YouWillBeNotified')
        );
    }

    $html .= '<div class="col-md-10">';
    // The post title

    $titlePost = Display::tag('h3', $post['post_title'], array('class' => 'forum_post_title'));
    $html .= Display::tag('div', $titlePost, array('class' => 'post-header'));

    // the post body

    $html .= Display::tag('div', $post['post_text'], array('class' => 'post-body'));
    $html .= '</div>';

    $html .= '</div>';

    $html .= '<div class="row">';
    $html .= '<div class="col-md-6">';
    // The check if there is an attachment
    $attachment_list = getAllAttachment($post['post_id']);
    if (!empty($attachment_list) && is_array($attachment_list)) {
        foreach ($attachment_list as $attachment) {
            $realname = $attachment['path'];
            $user_filename = $attachment['filename'];
            $html .= Display::return_icon('attachment.gif', get_lang('Attachment'));
            $html .= '<a href="download.php?file=';
            $html .= $realname;
            $html .= ' "> ' . $user_filename . ' </a>';
            $html .= '<span class="forum_attach_comment" >' . $attachment['comment'] . '</span>';
            if (($current_forum['allow_edit'] == 1 && $post['user_id'] == $userId) ||
                (api_is_allowed_to_edit(false, true) && !(api_is_course_coach() && $current_forum['session_id'] != $sessionId))
            ) {
                $html .= '&nbsp;&nbsp;<a href="' . api_get_self() . '?' . api_get_cidreq() . '&action=delete_attach&id_attach='
                    . $attachment['iid'] . '&forum=' . $clean_forum_id . '&thread=' . $clean_thread_id
                    . '" onclick="javascript:if(!confirm(\''
                    . addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)) . '\')) return false;">'
                    . Display::return_icon('delete.gif', get_lang('Delete')) . '</a><br />';
            }
        }
    }

    $html .= '</div>';
    $html .= '<div class="col-md-6 text-right">';
    $html .= $buttonReply . ' ' . $buttonQuote;
    $html .= '</div>';
    $html .= '</div>';
    // The post has been displayed => it can be removed from the what's new array
    unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]);
    unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]);


    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    echo $html;

    $count++;
}
