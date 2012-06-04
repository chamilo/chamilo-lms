<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.forum
 */
$course = api_get_course_info();
$rows = get_thread_user_post($course['code'], $current_thread['thread_id'], $_GET['user']);
if (isset($rows)) {
    $counter = 1;
    foreach ($rows as $row) {
        if ($row['status']=='0') {
            $style =" id = 'post".$post_en."' class=\"hide-me\" style=\"border:1px solid red; display:none; background-color:#F7F7F7; width:95%; margin: 0px 0px 4px 40px; \" ";
            $url_post ='';
        } else {
            $style = "";
            $post_en = $row['post_parent_id'];
        }

        if ($row['user_id'] == '0') {
            $name=prepare4display($row['poster_name']);
        } else {
            $name=api_get_person_name($row['firstname'], $row['lastname']);
        }
        if ($counter == 1) {
            echo Display::page_subheader($name);
        }

        echo "<div ".$style."><table class=\"data_table\">";
        // the style depends on the status of the message: approved or not
        //echo 'dd'.$row['status'];


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

        echo '<br /><b>'.  api_convert_and_format_date($row['post_date'], DATE_TIME_FORMAT_LONG).'</b><br />';

        if (api_is_allowed_to_edit(null,true)) {
            echo $url_post;
        }

        echo "</td>";

        // The post title
        echo "<td class=\"$titleclass\">".prepare4display($row['post_title'])."</td>";
        echo "</tr>";

        // The post message
        echo "<tr >";
        echo "<td class=\"$messageclass\">".prepare4display($row['post_text'])."</td>";
        echo "</tr>";

        // The check if there is an attachment
        $attachment_list=get_attachment($row['post_id']);

        if (!empty($attachment_list)) {
            echo '<tr ><td height="50%">';
            $realname=$attachment_list['path'];
            $user_filename=$attachment_list['filename'];

            echo Display::return_icon('attachment.gif',get_lang('Attachment'));
            echo '<a href="download.php?file=';
            echo $realname;
            echo ' "> '.$user_filename.' </a>';
            echo '<span class="forum_attach_comment" >'.$attachment_list['comment'].'</span><br />';
            echo '</td></tr>';
        }

        // The post has been displayed => it can be removed from the what's new array
        unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
        unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']]);
        unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
        unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']]);
        echo "</table></div>";
        $counter++;
    }
}

$userid = (int)$_GET['user_id'];
$userinf=api_get_user_info($userid);
$current_thread = get_thread_information($_GET['thread']);
$threadid = $current_thread['thread_id'];
$qualify = (int)$_POST['idtextqualify'];
//return Max qualify thread
$max_qualify=show_qualify('2', $userid,$threadid);
$current_qualify_thread=show_qualify('1',$userid,$threadid);
if (isset($_POST['idtextqualify'])) {
    store_theme_qualify($userid,$threadid,$qualify,$_SESSION['_user']['user_id'],date('Y-m-d H:i:s'),'');
}
$result = get_statistical_information($current_thread['thread_id'], $_GET['user_id'], $_GET['cidReq']);

if ($userinf['status']!='1') {
    echo '<div class="forum-qualification-input-box">';
    require_once 'forumbody.inc.php';
    echo '</div>';
}
