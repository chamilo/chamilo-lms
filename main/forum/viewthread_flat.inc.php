<?php
/* For licensing terms, see /license.txt */
/**
 * This script manages the display of forum threads in flat view
 * @copyright Julio Montoya <gugli100@gmail.com> UI Improvements + lots of bugfixes
 * @package chamilo.forum
 */
//delete attachment file
if ((isset($_GET['action']) && $_GET['action']=='delete_attach') && isset($_GET['id_attach'])) {
    delete_attachment(0,$_GET['id_attach']);
}
if (isset($current_thread['thread_id'])){

    $rows = get_posts($current_thread['thread_id']);
    $increment=0;

    $clean_forum_id  = intval($_GET['forum']);
    $clean_thread_id = intval($_GET['thread']);
    $locked = api_resource_is_locked_by_gradebook($clean_thread_id, LINK_FORUM_THREAD);
    if (!empty($rows)) {
        foreach ($rows as $row) {

            echo '<table width="100%" class="forum_table" cellspacing="5" border="0">';
            // the style depends on the status of the message: approved or not
            if ($row['visible']=='0') {
                $titleclass='forum_message_post_title_2_be_approved';
                $messageclass='forum_message_post_text_2_be_approved';
                $leftclass='forum_message_left_2_be_approved';
            } else {
                $titleclass='forum_message_post_title';
                $messageclass='forum_message_post_text';
                $leftclass='forum_message_left';
            }
            echo "<tr>";
            echo "<td rowspan=\"3\" class=\"$leftclass\">";

            if ($row['user_id']=='0') {
                $name = prepare4display($row['poster_name']);
            } else {
                $name = api_get_person_name($row['firstname'], $row['lastname']);
            }
            $username = sprintf(get_lang('LoginX'), $row['username']);

            if ($origin!='learnpath') {
                if (api_get_course_setting('allow_user_image_forum')) {
                    echo '<br />'.display_user_image($row['user_id'],$name).'<br />';
                }
                echo display_user_link($row['user_id'], $name, '', $username).'<br />';
            } else {
                echo Display::tag('span', $name, array('title'=>api_htmlentities($username, ENT_QUOTES))).'<br />';
            }

            $group_id = api_get_group_id();

            echo api_convert_and_format_date($row['post_date']).'<br /><br />';
            // get attach id
            $attachment_list=get_attachment($row['post_id']);
            $id_attach = !empty($attachment_list)?$attachment_list['id']:'';
            // The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
            // The course admin him/herself can do this off course always
            if ( GroupManager::is_tutor_of_group(api_get_user_id(), $group_id) OR  ($current_forum['allow_edit']==1 AND $row['user_id']==$_user['user_id']) or (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session']))) {
                if (api_is_allowed_to_session_edit(false,true)) {
                    if ($locked == false) {
                        echo "<a href=\"editpost.php?".api_get_cidreq()."&amp;gidReq=".Security::remove_XSS($_GET['gidReq'])."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;post=".$row['post_id']."&amp;origin=".$origin."&amp;edit=edition&amp;id_attach=".$id_attach."\">".Display::return_icon('edit.png',get_lang('Edit'), array(), ICON_SIZE_SMALL)."</a>";
                    }
                }
            }

            if ($origin != 'learnpath') {
                if (GroupManager::is_tutor_of_group(api_get_user_id(), $group_id) OR api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])) {
                    if ($locked == false) {
                        echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;action=delete&amp;content=post&amp;id=".$row['post_id']."&amp;origin=".$origin."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('DeletePost'), ENT_QUOTES))."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'),array(),  ICON_SIZE_SMALL)."</a>";
                    }
                }
                if (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])) {
                    display_visible_invisible_icon('post', $row['post_id'], $row['visible'],array('forum'=>$clean_forum_id,'thread'=>$clean_thread_id, 'origin'=>$origin ));
                    echo "";
                    if ($increment>0) {
                        echo "<a href=\"viewthread.php?".api_get_cidreq()."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;action=move&amp;post=".$row['post_id']."&amp;origin=".$origin."\">".Display::return_icon('move.png',get_lang('MovePost'), array(), ICON_SIZE_SMALL)."</a>";
                    }
                }
            }

            $user_status = api_get_status_of_user_in_course($row['user_id'], api_get_course_id());
            $current_qualify_thread = show_qualify('1', $row['poster_id'],$_GET['thread']);

            if (api_is_allowed_to_edit(null,true) && $origin != 'learnpath') {
                $my_forum_id = $clean_forum_id;
                if (isset($_GET['gradebook'])) {
                    $info_thread = get_thread_information($clean_thread_id);
                    $my_forum_id = $info_thread['forum_id'];
                }
                if ($increment > 0 && $locked == false) {
                     echo "<a href=\"forumqualify.php?".api_get_cidreq()."&amp;forum=".$my_forum_id."&amp;thread=".$clean_thread_id."&amp;action=list&amp;post=".$row['post_id']."&amp;user=".$row['poster_id']."&amp;user_id=".$row['poster_id']."&amp;origin=".$origin."&amp;idtextqualify=".$current_qualify_thread."&amp;gradebook=".Security::remove_XSS($_GET['gradebook'])."\" >".
                            Display::return_icon('quiz.gif',get_lang('Qualify'))."</a> ";
                }
            }

            if (($current_forum_category && $current_forum_category['locked']==0) AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit(false,true)) {
                if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id'])) {
                    if (!api_is_anonymous() && api_is_allowed_to_session_edit(false,true)) {
                        echo '<a href="reply.php?'.api_get_cidreq().'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;post='.$row['post_id'].'&amp;action=replymessage&amp;origin='.$origin.'">'.Display :: return_icon('message_reply_forum.png', get_lang('ReplyToMessage'))."</a>";
                        echo '<a href="reply.php?'.api_get_cidreq().'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;post='.$row['post_id'].'&amp;action=quote&amp;origin='.$origin.'">'.Display :: return_icon('quote.gif', get_lang('QuoteMessage'))."</a>";
                    }
                }
            } else {
                if ($current_forum_category && $current_forum_category['locked']==1) {
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
            // prepare the notification icon
            if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$row['thread_id']])) {
                $post_image=Display::return_icon('forumpostnew.gif');
            } else {
                $post_image=Display::return_icon('forumpost.gif');
            }
            if ($row['post_notification']=='1' AND $row['poster_id']==$_user['user_id']) {
                $post_image.=Display::return_icon('forumnotification.gif',get_lang('YouWillBeNotified'));
            }
            // The post title

            echo "</tr>";

            //  The post title
            echo "<tr>";
            echo Display::tag('td', prepare4display($row['post_title']), array('class'=>'forum_message_post_title'));
            echo "</tr>";

            // The post message
            echo "<tr>";

            // see comments inside forumfunction.inc.php to lower filtering and allow more visual changes
            echo "<td class=\"$messageclass\">".prepare4display($row['post_text'])."</td>";
            echo "</tr>";

            // The check if there is an attachment

            $attachment_list = get_attachment($row['post_id']);
            if (!empty($attachment_list)) {
                echo '<tr><td colspan="2" height="50%">';
                $realname=$attachment_list['path'];
                $user_filename=$attachment_list['filename'];

                echo Display::return_icon('attachment.gif',get_lang('Attachment'));
                echo '<a href="download.php?file='.$realname.'"> '.$user_filename.' </a>';

                if (($current_forum['allow_edit']==1 AND $row['user_id']==$_user['user_id']) or (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])))	{
                    echo '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;origin='.Security::remove_XSS($_GET['origin']).'&amp;action=delete_attach&amp;id_attach='.$attachment_list['id'].'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES)).'\')) return false;">'.Display::return_icon('delete.png',get_lang('Delete'), array(), ICON_SIZE_SMALL).'</a><br />';
                }
                echo '<span class="forum_attach_comment" >'.$attachment_list['comment'].'</span>';
                echo '</td></tr>';
            }




            // The post has been displayed => it can be removed from the what's new array
            unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
            unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']]);
            unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
            unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']]);
            echo "</table>";
            $increment++;
        }
    }
}