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

require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

$rows = get_posts($_GET['thread']); // Note: This has to be cleaned first.
$rows = calculate_children($rows);


if ($_GET['post']) {
    $display_post_id = intval($_GET['post']); // note: this has to be cleaned first
} else {
    // we need to display the first post
    reset($rows);
    $current = current($rows);
    $display_post_id = $current['post_id'];
}

// Are we in a lp ?
$origin = '';
if(isset($_GET['origin'])) {
    $origin =  Security::remove_XSS($_GET['origin']);
}

// Delete attachment file.
if ((isset($_GET['action']) && $_GET['action']=='delete_attach') && isset($_GET['id_attach'])) {
    delete_attachment(0,$_GET['id_attach']);
}

// 		Displaying the thread (structure)

$thread_structure="<div class=\"structure\">".get_lang('Structure')."</div>";
$counter=0;
$count=0;
$prev_next_array=array();

$clean_forum_id  = intval($_GET['forum']);
$clean_thread_id = intval($_GET['thread']);

foreach ($rows as $post) {
    $counter++;
    $indent=$post['indent_cnt']*'20';
    $thread_structure.= "<div style=\"margin-left: ".$indent."px;\">";

    if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$post['thread_id']])) {
        $post_image=Display::return_icon('forumpostnew.gif');
    } else {
        $post_image=Display::return_icon('forumpost.gif');
    }
    $thread_structure.= $post_image;
    if ($_GET['post']==$post['post_id'] OR ($counter==1 AND !isset($_GET['post']))) {
        $thread_structure.='<strong>'.prepare4display($post['post_title']).'</strong></div>';
        $prev_next_array[]=$post['post_id'];
    } else {
        if ($post['visible']=='0') {
            $class=' class="invisible"';
        } else {
            $class='';
        }
        $count_loop=($count==0)?'&amp;id=1' : '';
        $thread_structure.= "<a href=\"viewthread.php?".api_get_cidreq()."&amp;gidReq=".Security::remove_XSS($_GET['gidReq'])."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;post=".$post['post_id']."&amp;origin=$origin$count_loop\" $class>".prepare4display($post['post_title'])."</a></div>";
        $prev_next_array[]=$post['post_id'];
    }
    $count++;
}

/* NAVIGATION CONTROLS */

$current_id=array_search($display_post_id,$prev_next_array);
$max=count($prev_next_array);
$next_id=$current_id+1;
$prev_id=$current_id-1;

// text
$first_message=get_lang('FirstMessage');
$last_message=get_lang('LastMessage');
$next_message=get_lang('NextMessage');
$prev_message=get_lang('PrevMessage');

// images
$first_img 	= Display::return_icon('action_first.png',get_lang('FirstMessage'), array('style' => 'vertical-align: middle;'));
$last_img 	= Display::return_icon('action_last.png',get_lang('LastMessage'), array('style' => 'vertical-align: middle;'));
$prev_img 	= Display::return_icon('action_prev.png',get_lang('PrevMessage'), array('style' => 'vertical-align: middle;'));
$next_img 	= Display::return_icon('action_next.png',get_lang('NextMessage'), array('style' => 'vertical-align: middle;'));

// links
$first_href = 'viewthread.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;id=1&amp;post='.$prev_next_array[0];
$last_href 	= 'viewthread.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;post='.$prev_next_array[$max-1];
$prev_href	= 'viewthread.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;post='.$prev_next_array[$prev_id];
$next_href	= 'viewthread.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;gradebook='.$gradebook.'&amp;origin='.$origin.'&amp;post='.$prev_next_array[$next_id];

echo '<center style="margin-top: 10px; margin-bottom: 10px;">';
//go to: first and previous
if ((int)$current_id > 0)
{
    echo '<a href="'.$first_href.'" '.$class.' title='.$first_message.'>'.$first_img.' '.$first_message.'</a>';
    echo '<a href="'.$prev_href.'" '.$class_prev.' title='.$prev_message.'>'.$prev_img.' '.$prev_message.'</a>';
}
else
{
    echo '<b><span class="invisible">'.$first_img.' '.$first_message.'</b></span>';
    echo '<b><span class="invisible">'.$prev_img.' '.$prev_message.'</b></span>';
}

//  current counter
echo  ' [ '.($current_id+1).' / '.$max.' ] ';

// go to: next and last
if (($current_id+1) < $max) {
    echo '<a href="'.$next_href.'" '.$class_next.' title='.$next_message.'>'.$next_message.' '.$next_img.'</a>';
    echo '<a href="'.$last_href.'" '.$class.' title='.$last_message.'>'.$last_message.' '.$last_img.'</a>';
} else {
    echo '<b><span class="invisible">'.$next_message.' '.$next_img.'</b></span>';
    echo '<b><span class="invisible">'.$last_message.' '.$last_img.'</b></span>';
}
echo '</center>';

//--------------------------------------------------------------------------------------------

// the style depends on the status of the message: approved or not
if ($rows[$display_post_id]['visible']=='0') {
    $titleclass='forum_message_post_title_2_be_approved';
    $messageclass='forum_message_post_text_2_be_approved';
    $leftclass='forum_message_left_2_be_approved';
} else {
    $titleclass='forum_message_post_title';
    $messageclass='forum_message_post_text';
    $leftclass='forum_message_left';
}

// 		Displaying the message

// we mark the image we are displaying as set
unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]);

echo "<table width=\"100%\"  class=\"forum_table\"  cellspacing=\"5\" border=\"0\">";
echo "<tr>";
echo "<td rowspan=\"3\" class=\"$leftclass\">";
if ($rows[$display_post_id]['user_id']=='0') {
    $name=prepare4display($rows[$display_post_id]['poster_name']);
} else {
    $name=api_get_person_name($rows[$display_post_id]['firstname'], $rows[$display_post_id]['lastname']);
}

if (api_get_course_setting('allow_user_image_forum')) {echo '<br />'.display_user_image($rows[$display_post_id]['user_id'],$name, $origin).'<br />';	}
echo display_user_link($rows[$display_post_id]['user_id'], $name, $origin).'<br />';
echo api_convert_and_format_date($rows[$display_post_id]['post_date']).'<br /><br />';
// get attach id
$attachment_list=get_attachment($display_post_id);
$id_attach = !empty($attachment_list)?$attachment_list['id']:'';

// The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
// The course admin him/herself can do this off course always
if (($current_forum['allow_edit']==1 AND $rows[$display_post_id]['user_id']==$_user['user_id']) or (api_is_allowed_to_edit(false,true) && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session']))) {
    echo "<a href=\"editpost.php?".api_get_cidreq()."&amp;gidReq=".Security::remove_XSS($_GET['gidReq'])."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;origin=".$origin."&amp;post=".$rows[$display_post_id]['post_id']."&amp;id_attach=".$id_attach."\">".Display::return_icon('edit.png',get_lang('Edit'), array(), 22)."</a>";
}
if (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])) {
    echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;gidReq=".Security::remove_XSS($_GET['gidReq'])."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;action=delete&amp;content=post&amp;id=".$rows[$display_post_id]['post_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('DeletePost'), ENT_QUOTES))."')) return false;\">".Display::return_icon('delete.png',get_lang('Delete'), array(), 22)."</a>";
    display_visible_invisible_icon('post', $rows[$display_post_id]['post_id'], $rows[$display_post_id]['visible'],array('forum'=>$clean_forum_id,'thread'=>$clean_thread_id, 'post'=>Security::remove_XSS($_GET['post']) ));
    echo "";
    //verified the post minor
    $my_post=get_posts($_GET['thread']);
    $id_posts=array();

    foreach ($my_post as $post_value) {
        $id_posts[]=$post_value['post_id'];
    }

    sort($id_posts,SORT_NUMERIC);
    reset($id_posts);
    //the post minor
    $post_minor=(int)$id_posts[0];
    $post_id = isset($_GET['post'])?(int)$_GET['post']:0;
    if (!isset($_GET['id']) && $post_id>$post_minor) {
        echo "<a href=\"viewthread.php?".api_get_cidreq()."&amp;gidReq=".Security::remove_XSS($_GET['gidReq'])."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;origin=".$origin."&amp;action=move&amp;post=".$rows[$display_post_id]['post_id']."\">".Display::return_icon('move.png',get_lang('MovePost'), array(), 22)."</a>";
    }
}
$userinf=api_get_user_info($rows[$display_post_id]['user_id']);
$user_status=api_get_status_of_user_in_course($rows[$display_post_id]['user_id'],api_get_course_id());
if (api_is_allowed_to_edit(null,true)) {
    if($post_id>$post_minor ) {
        if($user_status!=1) {
            $current_qualify_thread=show_qualify('1',$_GET['cidReq'],$_GET['forum'],$rows[$display_post_id]['user_id'],$_GET['thread']);
            echo "<a href=\"forumqualify.php?".api_get_cidreq()."&amp;gidReq=".Security::remove_XSS($_GET['gidReq'])."&amp;forum=".$clean_forum_id."&amp;thread=".$clean_thread_id."&amp;action=list&amp;post=".$rows[$display_post_id]['post_id']."&amp;user=".$rows[$display_post_id]['user_id']."&amp;user_id=".$rows[$display_post_id]['user_id']."&amp;origin=".$origin."&amp;idtextqualify=".$current_qualify_thread."\" >".Display::return_icon('new_test_small.gif',get_lang('Qualify'))."</a>";
        }
    }
}
//echo '<br /><br />';
//if (($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0) OR api_is_allowed_to_edit())
if ($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit(false,true)) {
    if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id'])) {
        if (!api_is_anonymous() && api_is_allowed_to_session_edit(false,true)) {
            echo '<a href="reply.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;post='.$rows[$display_post_id]['post_id'].'&amp;action=replymessage&amp;origin='. $origin .'">'.Display :: return_icon('message_reply_forum.png', get_lang('ReplyToMessage'))."</a>";
            echo '<a href="reply.php?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'&amp;post='.$rows[$display_post_id]['post_id'].'&amp;action=quote&amp;origin='. $origin .'">'.Display :: return_icon('quote.gif', get_lang('QuoteMessage'))."</a>";
        }
    }
} else {
    if ($current_forum_category['locked']==1) {
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
if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$rows[$display_post_id]['thread_id']])) {
    $post_image=Display::return_icon('forumpostnew.gif');
} else {
    $post_image=Display::return_icon('forumpost.gif');
}
if ($rows[$display_post_id]['post_notification']=='1' AND $rows[$display_post_id]['poster_id']==$_user['user_id']) {
    $post_image.=Display::return_icon('forumnotification.gif',get_lang('YouWillBeNotified'));
}
// The post title
echo "<td class=\"$titleclass\">".prepare4display($rows[$display_post_id]['post_title'])."</td>";
echo "</tr>";

// The post message
echo "<tr>";
echo "<td class=\"$messageclass\">".prepare4display($rows[$display_post_id]['post_text'])."</td>";
echo "</tr>";

// The check if there is an attachment
$attachment_list = get_attachment($display_post_id);

if (!empty($attachment_list)) {
    echo '<tr><td height="50%">';
    $realname=$attachment_list['path'];
    $user_filename=$attachment_list['filename'];

    echo Display::return_icon('attachment.gif',get_lang('Attachment'));
    echo '<a href="download.php?file=';
    echo $realname;
    echo ' "> '.$user_filename.' </a>';
    echo '<span class="forum_attach_comment" >'.Security::remove_XSS($attachment_list['comment'], STUDENT).'</span>';
    if (($current_forum['allow_edit']==1 AND $rows[$display_post_id]['user_id']==$_user['user_id']) or (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])))	{
        echo '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;origin='.Security::remove_XSS($_GET['origin']).'&amp;action=delete_attach&amp;id_attach='.$attachment_list['id'].'&amp;forum='.$clean_forum_id.'&amp;thread='.$clean_thread_id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTESt)).'\')) return false;">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a><br />';
    }
    echo '</td></tr>';
}

// The post has been displayed => it can be removed from the what's new array
unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
echo "</table>";

// 		Displaying the thread (structure)

echo $thread_structure;
