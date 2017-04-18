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
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya <gugli100@gmail.com> UI Improvements + lots of bugfixes
 *
 * @package chamilo.forum
 */

$forumUrl = api_get_path(WEB_CODE_PATH).'forum/';
$_user = api_get_user_info();
$sortDirection = isset($_GET['posts_order']) && $_GET['posts_order'] === 'desc' ? 'DESC' : 'ASC';
$rows = getPosts($current_forum, $_GET['thread'], $sortDirection, true);
$sessionId = api_get_session_id();
$currentThread = get_thread_information($current_forum['forum_id'], $_GET['thread']);
$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
$userId = api_get_user_id();

if (isset($_GET['post']) && $_GET['post']) {
    $display_post_id = intval($_GET['post']);
} else {
    // We need to display the first post
    reset($rows);
    $current = current($rows);
    $display_post_id = $current['post_id'];
}

// Are we in a lp ?
$origin = api_get_origin();
// Delete attachment file.
if (
    isset($_GET['action']) &&
    $_GET['action'] == 'delete_attach' &&
    isset($_GET['id_attach'])
) {
    delete_attachment(0, $_GET['id_attach']);
    if (!isset($_GET['thread'])) {
        exit;
    }
}

// Displaying the thread (structure)

$thread_structure = "<div class=\"structure\">".get_lang('Structure')."</div>";
$counter = 0;
$count = 0;
$prev_next_array = array();

$forumId  = intval($_GET['forum']);
$threadId = intval($_GET['thread']);
$groupId = api_get_group_id();

foreach ($rows as $post) {
    $counter++;
    $indent = $post['indent_cnt'] * '20';
    $thread_structure .= "<div style=\"margin-left: ".$indent."px;\">";

    if (
        !empty($whatsnew_post_info[$forumId][$post['thread_id']]) &&
        isset($whatsnew_post_info[$forumId][$threadId][$post['post_id']]) &&
        !empty($whatsnew_post_info[$forumId][$threadId][$post['post_id']])
    ) {
        $post_image = Display::return_icon('forumpostnew.gif');
    } else {
        $post_image = Display::return_icon('forumpost.gif');
    }
    $thread_structure .= $post_image;
    if (
        isset($_GET['post']) &&
        $_GET['post'] == $post['post_id'] || (
            $counter == 1 AND !isset($_GET['post'])
        )
    ) {
        $thread_structure .= '<strong>'.prepare4display($post['post_title']).'</strong>';
        $prev_next_array[] = $post['post_id'];
    } else {
        $count_loop = ($count == 0) ? '&id=1' : '';
        $thread_structure .= Display::url(
            prepare4display($post['post_title']),
            'viewthread.php?'.api_get_cidreq()."$count_loop&".http_build_query([
                'forum' => $forumId,
                'thread' => $threadId,
                'post' => $post['post_id']
            ]),
            ['class' => empty($post['visible']) ? 'text-muted' : null]
        );

        $prev_next_array[] = $post['post_id'];
    }

    $thread_structure .= '</div>';
    $count++;
}

$locked = api_resource_is_locked_by_gradebook($threadId, LINK_FORUM_THREAD);

/* NAVIGATION CONTROLS */

$current_id = array_search($display_post_id, $prev_next_array);
$max = count($prev_next_array);
$next_id = $current_id + 1;
$prev_id = $current_id - 1;

// Text
$first_message = get_lang('FirstMessage');
$last_message = get_lang('LastMessage');
$next_message = get_lang('NextMessage');
$prev_message = get_lang('PrevMessage');

// Images
$first_img = Display::return_icon(
    'action_first.png',
    get_lang('FirstMessage'),
    array('style' => 'vertical-align: middle;')
);
$last_img = Display::return_icon(
    'action_last.png',
    get_lang('LastMessage'),
    array('style' => 'vertical-align: middle;')
);
$prev_img = Display::return_icon(
    'action_prev.png',
    get_lang('PrevMessage'),
    array('style' => 'vertical-align: middle;')
);
$next_img = Display::return_icon(
    'action_next.png',
    get_lang('NextMessage'),
    array('style' => 'vertical-align: middle;')
);

$class_prev = '';
$class_next = '';

// Links
$first_href = $forumUrl.'viewthread.php?'.api_get_cidreq().
    '&forum='.$forumId.'&thread='.$threadId.
    '&gradebook='.$gradebook.'&id=1&post='.$prev_next_array[0];
$last_href 	= $forumUrl.'viewthread.php?'.api_get_cidreq().
    '&forum='.$forumId.'&thread='.$threadId.
    '&gradebook='.$gradebook.'&post='.$prev_next_array[$max - 1];
$prev_href	= $forumUrl.'viewthread.php?'.api_get_cidreq().
    '&forum='.$forumId.'&thread='.$threadId.
    '&gradebook='.$gradebook.'&post='.$prev_next_array[$prev_id];
$next_href	= $forumUrl.'viewthread.php?'.api_get_cidreq().
    '&forum='.$forumId.'&thread='.$threadId.
    '&post='.$prev_next_array[$next_id];

echo '<center style="margin-top: 10px; margin-bottom: 10px;">';
// Go to: first and previous
if (((int) $current_id) > 0) {
    echo '<a href="'.$first_href.'" '.$class.' title='.
        $first_message.'>'.$first_img.' '.$first_message.'</a>';
    echo '<a href="'.$prev_href.'" '.$class_prev.' title='.
        $prev_message.'>'.$prev_img.' '.$prev_message.'</a>';
} else {
    echo '<strong class="text-muted">'.
        $first_img.' '.$first_message.'</strong>';
    echo '<strong class="text-muted">'.
        $prev_img.' '.$prev_message.'</strong>';
}

// Current counter
echo ' [ '.($current_id + 1).' / '.$max.' ] ';

// Go to: next and last
if (($current_id + 1) < $max) {
    echo '<a href="'.$next_href.'" '.$class_next.' title='.$next_message.'>'.$next_message.' '.$next_img.'</a>';
    echo '<a href="'.$last_href.'" '.$class.' title='.$last_message.'>'.$last_message.' '.$last_img.'</a>';
} else {
    echo '<strong class="text-muted">'.$next_message.' '.$next_img.'</strong>';
    echo '<strong class="text-muted">'.$last_message.' '.$last_img.'</strong>';
}
echo '</center>';

// The style depends on the status of the message: approved or not
if ($rows[$display_post_id]['visible'] == '0') {
    $titleclass = 'forum_message_post_title_2_be_approved';
    $messageclass = 'forum_message_post_text_2_be_approved';
    $leftclass = 'forum_message_left_2_be_approved';
} else {
    $titleclass = 'forum_message_post_title';
    $messageclass = 'forum_message_post_text';
    $leftclass = 'forum_message_left';
}

// Displaying the message

// We mark the image we are displaying as set
unset($whatsnew_post_info[$forumId][$threadId][$rows[$display_post_id]['post_id']]);

echo "<table width=\"100%\" class=\"forum_table\" cellspacing=\"5\" border=\"0\">";
echo "<tr>";
echo "<td rowspan=\"3\" class=\"$leftclass\">";
$username = sprintf(get_lang('LoginX'), $rows[$display_post_id]['username']);
if ($rows[$display_post_id]['user_id'] == '0') {
    $name = prepare4display($rows[$display_post_id]['poster_name']);
} else {
    $name = api_get_person_name(
        $rows[$display_post_id]['firstname'],
        $rows[$display_post_id]['lastname']
    );
}

if (api_get_course_setting('allow_user_image_forum')) {
    echo '<br />'.display_user_image($rows[$display_post_id]['user_id'], $name, $origin).'<br />';
}
echo display_user_link(
    $rows[$display_post_id]['user_id'],
    $name,
    $origin,
    $username
)."<br />";

echo api_convert_and_format_date(
    $rows[$display_post_id]['post_date']
).'<br /><br />';
// Get attach id
$attachment_list = get_attachment($display_post_id);
$id_attach = !empty($attachment_list) ? $attachment_list['id'] : '';
$groupInfo = GroupManager::get_group_properties($groupId);

// The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
// The course admin him/herself can do this off course always
if (
(isset($groupInfo['iid']) && GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo)) || (
        $current_forum['allow_edit'] == 1 &&
        $row['user_id'] == $_user['user_id']
    ) || (
        api_is_allowed_to_edit(false, true) && !(
            api_is_course_coach() &&
            $current_forum['session_id'] != $sessionId
        )
    )
) {
    if ($locked == false) {
        echo "<a href=\"editpost.php?".api_get_cidreq().
            "&forum=".$forumId."&thread=".$threadId.
            "&post=".$rows[$display_post_id]['post_id'].
            "&id_attach=".$id_attach."\">".
            Display::return_icon(
                'edit.png',
                get_lang('Edit'),
                array(),
                ICON_SIZE_SMALL
            ).'</a>';
    }
}


// Verified the post minor
$my_post = getPosts($current_forum, $_GET['thread']);
$id_posts = array();

if (!empty($my_post) && is_array($my_post)) {
    foreach ($my_post as $post_value) {
        $id_posts[] = $post_value['post_id'];
    }
    sort($id_posts, SORT_NUMERIC);
    reset($id_posts);
    // The post minor
    $post_minor = (int) $id_posts[0];
}

if (
    (isset($groupInfo['iid']) && GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo)) ||
    api_is_allowed_to_edit(false, true) &&
    !(api_is_course_coach() && $current_forum['session_id'] != $sessionId)
) {
    if ($locked == false) {
        echo "<a href=\"".api_get_self()."?".api_get_cidreq().
            "&forum=".$forumId."&thread=".$threadId.
            "&action=delete&content=post&id=".
            $rows[$display_post_id]['post_id'].
            "\" onclick=\"javascript:if(!confirm('".
            addslashes(api_htmlentities(get_lang('DeletePost'), ENT_QUOTES)).
            "')) return false;\">".Display::return_icon(
                'delete.png',
                get_lang('Delete'),
                array(),
                ICON_SIZE_SMALL
            )."</a>";
    }
    echo return_visible_invisible_icon(
        'post',
        $rows[$display_post_id]['post_id'],
        $rows[$display_post_id]['visible'],
        array(
            'forum' => $forumId,
            'thread' => $threadId,
            'post' => Security::remove_XSS($_GET['post'])
        )
    );

    if (!isset($_GET['id']) && $post_id > $post_minor) {
        echo "<a href=\"viewthread.php?".api_get_cidreq().
            "&forum=".$forumId."&thread=".$threadId.
            "&action=move&post=".
            $rows[$display_post_id]['post_id']."\">".
            Display::return_icon(
                'move.png',
                get_lang('MovePost'),
                array(),
                ICON_SIZE_SMALL
            )."</a>";

    }
}

$userCanQualify = $currentThread['thread_peer_qualify'] == 1 && $rows[$display_post_id]['poster_id'] != $userId;
if (api_is_allowed_to_edit(null, true)) {
    $userCanQualify = true;
}

if (empty($currentThread['thread_qualify_max'])) {
    $userCanQualify = false;
}

if ($userCanQualify) {
    if ($post_id > $post_minor) {
        $current_qualify_thread = showQualify(
            '1',
            $rows[$display_post_id]['user_id'],
            $_GET['thread']
        );

        if ($locked == false) {
            echo "<a href=\"forumqualify.php?".api_get_cidreq().
                "&forum=".$forumId."&thread=".$threadId.
                "&action=list&post=".$rows[$display_post_id]['post_id'].
                "&user=".$rows[$display_post_id]['user_id']."&user_id=".
                $rows[$display_post_id]['user_id'].
                "&idtextqualify=".$current_qualify_thread.
                "\" >".Display::return_icon(
                    'quiz.png',
                    get_lang('Qualify')
                )."</a>";
        }
    }
}
if (($current_forum_category && $current_forum_category['locked'] == 0) &&
    $current_forum['locked'] == 0 &&
    $current_thread['locked'] == 0 || api_is_allowed_to_edit(false, true)
) {
    if ($_user['user_id'] ||
        ($current_forum['allow_anonymous'] == 1 && !$_user['user_id'])
    ) {
        if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
            echo '<a href="reply.php?'.api_get_cidreq().
                '&forum='.$forumId.'&thread='.$threadId.
                '&post='.$rows[$display_post_id]['post_id'].
                '&action=replymessage">'.
                Display::return_icon(
                    'message_reply_forum.png',
                    get_lang('ReplyToMessage')
                )."</a>";
            echo '<a href="reply.php?'.api_get_cidreq().
                '&forum='.$forumId.'&thread='.$threadId.
                '&post='.$rows[$display_post_id]['post_id'].
                '&action=quote">'.
                Display::return_icon(
                    'quote.gif',
                    get_lang('QuoteMessage')
                )."</a>";
        }
    }
} else {
    if ($current_forum_category && $current_forum_category['locked'] == 1) {
        echo get_lang('ForumcategoryLocked').'<br />';
    }
    if ($current_forum['locked'] == 1) {
        echo get_lang('ForumLocked').'<br />';
    }
    if ($current_thread['locked'] == 1) {
        echo get_lang('ThreadLocked').'<br />';
    }
}

echo "</td>";
// Note: this can be removed here because it will be displayed in the tree
if (
    isset($whatsnew_post_info[$forumId][$threadId][$rows[$display_post_id]['post_id']]) AND
    !empty($whatsnew_post_info[$forumId][$threadId][$rows[$display_post_id]['post_id']]) AND
    !empty($whatsnew_post_info[$_GET['forum']][$rows[$display_post_id]['thread_id']])
) {
    $post_image = Display::return_icon('forumpostnew.gif');
} else {
    $post_image = Display::return_icon('forumpost.gif');
}
if (
    $rows[$display_post_id]['post_notification'] == '1' AND
    $rows[$display_post_id]['poster_id'] == $_user['user_id']
) {
    $post_image .= Display::return_icon('forumnotification.gif', get_lang('YouWillBeNotified'));
}
// The post title
echo "<td class=\"$titleclass\">".
    prepare4display($rows[$display_post_id]['post_title'])."</td>";
echo "</tr>";

// The post message
echo "<tr>";
echo "<td class=\"$messageclass\">".
    prepare4display($rows[$display_post_id]['post_text'])."</td>";
echo "</tr>";

// The check if there is an attachment
$attachment_list = getAllAttachment($display_post_id);
if (!empty($attachment_list) && is_array($attachment_list)) {
    foreach ($attachment_list as $attachment) {
        echo '<tr><td height="50%">';
        $realname = $attachment['path'];
        $user_filename = $attachment['filename'];
        echo Display::return_icon('attachment.gif', get_lang('Attachment'));
        echo '<a href="download.php?file=';
        echo $realname;
        echo ' "> '.$user_filename.' </a>';
        echo '<span class="forum_attach_comment">'.
            Security::remove_XSS($attachment['comment'], STUDENT).'</span>';

        if (
            ($current_forum['allow_edit'] == 1 && $rows[$display_post_id]['user_id'] == $_user['user_id']) ||
            (api_is_allowed_to_edit(false, true) && !(api_is_course_coach() && $current_forum['session_id'] != $sessionId))
        ) {
            echo '&nbsp;&nbsp;<a href="'.api_get_self().'?'.
                api_get_cidreq().'&action=delete_attach&id_attach='.$attachment['id'].'&forum='.$forumId.
                '&thread='.$threadId.
                '" onclick="javascript:if(!confirm(\''.
                addslashes(api_htmlentities(
                    get_lang('ConfirmYourChoice'), ENT_QUOTES)
                ).'\')) return false;">'.Display::return_icon(
                    'delete.gif',
                    get_lang('Delete')
                ).'</a><br />';
        }
        echo '</td></tr>';
    }
}

// The post has been displayed => it can be removed from the what's new array
if (isset($whatsnew_post_info[$forumId][$threadId][$row['post_id']])) {
    unset($whatsnew_post_info[$forumId][$threadId][$row['post_id']]);
    unset($_SESSION['whatsnew_post_info'][$forumId][$threadId][$row['post_id']]);
}
echo "</table>";

echo $thread_structure;
