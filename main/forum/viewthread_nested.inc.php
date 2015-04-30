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
    $origin =  Security::remove_XSS($_GET['origin']);
}

//delete attachment file
if (isset($_GET['action']) &&
    $_GET['action']=='delete_attach' &&
    isset($_GET['id_attach'])
) {
    delete_attachment(0, $_GET['id_attach']);
}

$rows = get_posts($_GET['thread']);
$rows = calculate_children($rows);
$count = 0;
$clean_forum_id  = intval($_GET['forum']);
$clean_thread_id = intval($_GET['thread']);
$group_id = api_get_group_id();
$locked = api_resource_is_locked_by_gradebook($clean_thread_id, LINK_FORUM_THREAD);
$sessionId = api_get_session_id();
$currentThread = get_thread_information($_GET['thread']);
$userId = api_get_user_id();

foreach ($rows as $post) {
    // The style depends on the status of the message: approved or not.
    if ($post['visible']=='0') {
        $titleclass = 'forum_message_post_title_2_be_approved';
        $messageclass = 'forum_message_post_text_2_be_approved';
        $leftclass = 'forum_message_left_2_be_approved';
    } else {
        $titleclass = 'forum_message_post_title';
        $messageclass = 'forum_message_post_text';
        $leftclass = 'forum_message_left';
    }

    $indent=$post['indent_cnt']*'20';
    echo "<div style=\"margin-left: ".$indent."px;\">";
    echo "<table width=\"100%\" class=\"post\" cellspacing=\"5\" border=\"0\">";
    echo "<tr>";
    echo "<td rowspan=\"3\" class=\"$leftclass\">";

    $username = sprintf(get_lang('LoginX'), $post['username']);
    if ($post['user_id']=='0') {
        $name = $post['poster_name'];
    } else {
        $name = api_get_person_name($post['firstname'], $post['lastname']);
    }

    if (api_get_course_setting('allow_user_image_forum')) {
        echo '<br />'.display_user_image($post['user_id'],$name,$origin).'<br />';
    }
    echo display_user_link($post['user_id'], $name, $origin, $username)."<br />";
    echo api_convert_and_format_date($post['post_date']).'<br /><br />';
    // get attach id
    $attachment_list = get_attachment($post['post_id']);
    $id_attach = !empty($attachment_list) ? $attachment_list['id'] : '';
    // The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
    // The course admin him/herself can do this off course always
    if (GroupManager::is_tutor_of_group(api_get_user_id(), $group_id) ||
        ($current_forum['allow_edit'] == 1 && $row['user_id'] == $userId) ||
        (api_is_allowed_to_edit(false,true) && !(api_is_course_coach() && $current_forum['session_id'] != $sessionId))
    ) {
        if ($locked == false) {
            echo "<a href=\"editpost.php?".api_get_cidreq()."&forum=".$clean_forum_id."&thread=".$clean_thread_id."&post=".$post['post_id']."&id_attach=".$id_attach."\">".
                Display::return_icon('edit.png',get_lang('Edit'), array(), ICON_SIZE_SMALL)."</a>";
        }
    }

    if (GroupManager::is_tutor_of_group(api_get_user_id(), $group_id) ||
        api_is_allowed_to_edit(false, true) &&
        !(api_is_course_coach() && $current_forum['session_id'] != $sessionId)
    ) {
        if ($locked == false) {
            echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forum=".$clean_forum_id."&thread=".$clean_thread_id."&action=delete&content=post&id=".$post['post_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('DeletePost'), ENT_QUOTES))."')) return false;\">".
                Display::return_icon('delete.png',get_lang('Delete'), array(), ICON_SIZE_SMALL)."</a>";
        }
    }

    if (api_is_allowed_to_edit(false, true) &&
        !(api_is_course_coach() &&
        $current_forum['session_id'] != $sessionId
        )
    ) {
        display_visible_invisible_icon(
            'post',
            $post['post_id'],
            $post['visible'],
            array('forum' => $clean_forum_id, 'thread' => $clean_thread_id)
        );

        if ($count>0) {
            echo "<a href=\"viewthread.php?".api_get_cidreq()."&forum=".$clean_forum_id."&thread=".$clean_thread_id."&action=move&origin=".$origin."&post=".$post['post_id']."\">".
                Display::return_icon('move.png',get_lang('MovePost'), array(),ICON_SIZE_SMALL)."</a>";
        }
    }

    $userCanQualify = $currentThread['thread_peer_qualify'] == 1 && $post['poster_id'] != $userId;
    if (api_is_allowed_to_edit(null, true)) {
        $userCanQualify = true;
    }

    if ($userCanQualify) {
        if ($count > 0) {
            $current_qualify_thread = showQualify(
                '1',
                $post['user_id'],
                $_GET['thread']
            );
            if ($locked == false) {
                echo "<a href=\"forumqualify.php?".api_get_cidreq()."&forum=".$clean_forum_id."&thread=".$clean_thread_id."&action=list&post=".$post['post_id']."&user=".$post['user_id']."&user_id=".$post['user_id']."&origin=".$origin."&idtextqualify=".$current_qualify_thread."\" >".
                    Display::return_icon('quiz.gif',get_lang('Qualify'))."</a>";
            }
        }
    }

    if (($current_forum_category && $current_forum_category['locked'] == 0) &&
        $current_forum['locked']==0 && $current_thread['locked']==0 || api_is_allowed_to_edit(false, true)
    ) {
        if ($userId || ($current_forum['allow_anonymous']==1 && !$userId)) {
            if (!api_is_anonymous() && api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="reply.php?'.api_get_cidreq().'&forum='.$clean_forum_id.'&thread='.$clean_thread_id.'&post='.$post['post_id'].'&action=replymessage&origin='. $origin .'">'.
                    Display :: return_icon('message_reply_forum.png', get_lang('ReplyToMessage'))."</a>";
                echo '<a href="reply.php?'.api_get_cidreq().'&forum='.$clean_forum_id.'&thread='.$clean_thread_id.'&post='.$post['post_id'].'&action=quote&origin='. $origin .'">'.
                    Display :: return_icon('quote.gif', get_lang('QuoteMessage'))."</a>";
            }
        }
    } else {
        if ($current_forum_category && $current_forum_category['locked'] == 1) {
            echo get_lang('ForumcategoryLocked').'<br />';
        }
        if ($current_forum['locked']==1) {
            echo get_lang('ForumLocked').'<br />';
        }
        if ($current_thread['locked']==1) {
            echo get_lang('ThreadLocked').'<br />';
        }
    }
    echo "</td>";
    // note: this can be removed here because it will be displayed in the tree
    if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) &&
        !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) &&
        !empty($whatsnew_post_info[$_GET['forum']][$post['thread_id']])
    ) {
        $post_image = Display::return_icon('forumpostnew.gif');
    } else {
        $post_image = Display::return_icon('forumpost.gif');
    }

    if ($post['post_notification']=='1' && $post['poster_id']==$userId) {
        $post_image .= Display::return_icon(
            'forumnotification.gif',
            get_lang('YouWillBeNotified')
        );
    }

    // The post title
    echo "<td class=\"$titleclass\">".prepare4display($post['post_title'])."</td>";
    echo "</tr>";

    // The post message
    echo "<tr>";
    echo "<td class=\"$messageclass\">".prepare4display($post['post_text'])."</td>";
    echo "</tr>";

    // The check if there is an attachment
    $attachment_list = getAllAttachment($post['post_id']);
    if (!empty($attachment_list) && is_array($attachment_list)) {
        foreach ($attachment_list as $attachment) {
            echo '<tr><td height="50%">';
            $realname=$attachment['path'];
            $user_filename=$attachment['filename'];
            echo Display::return_icon('attachment.gif',get_lang('Attachment'));
            echo '<a href="download.php?file=';
            echo $realname;
            echo ' "> '.$user_filename.' </a>';
            echo '<span class="forum_attach_comment" >'.$attachment['comment'].'</span>';
            if (($current_forum['allow_edit'] == 1 && $post['user_id'] == $userId) ||
                (api_is_allowed_to_edit(false, true) && !(api_is_course_coach() && $current_forum['session_id'] != $sessionId))
            ) {
                echo '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'&action=delete_attach&id_attach='.$attachment['id'].'&forum='.$clean_forum_id.'&thread='.$clean_thread_id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\')) return false;">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a><br />';
            }
            echo '</td></tr>';
        }
    }

    // The post has been displayed => it can be removed from the what's new array
    unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
    unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
    echo "</table>";
    echo "</div>";
    $count++;
}
