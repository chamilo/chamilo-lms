<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
*	@Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*	@Copyright Ghent University
*	@Copyright Patrick Cool
* 
* 	@package dokeos.forum
*/
//are we in a lp ?
$origin = '';
if(isset($_GET['origin']))
{
    $origin =  Security::remove_XSS($_GET['origin']);
}

//delete attachment file
if ((isset($_GET['action']) && $_GET['action']=='delete_attach') && isset($_GET['id_attach'])) {	
	delete_attachment(0,$_GET['id_attach']);
}

$rows=get_posts($_GET['thread']); // note: this has to be cleaned first
$rows=calculate_children($rows);
$count=0;
foreach ($rows as $post) {
	// the style depends on the status of the message: approved or not
	if ($post['visible']=='0') {
		$titleclass='forum_message_post_title_2_be_approved';
		$messageclass='forum_message_post_text_2_be_approved';
		$leftclass='forum_message_left_2_be_approved';	
	} else {
		$titleclass='forum_message_post_title';
		$messageclass='forum_message_post_text';
		$leftclass='forum_message_left';		
	}
	
	$indent=$post['indent_cnt']*'20';
	echo "<div style=\"margin-left: ".$indent."px;\">";
	echo "<table width=\"100%\"  class=\"post\" cellspacing=\"5\" border=\"0\">\n";
	echo "\t<tr>\n";
	echo "\t\t<td rowspan=\"3\" class=\"$leftclass\">";
	if ($post['user_id']=='0') {
		$name=$post['poster_name'];
	} else {
		$name=$post['firstname'].' '.$post['lastname'];
	}	
	if (api_get_course_setting('allow_user_image_forum')) {
		echo '<br />'.display_user_image($post['user_id'],$name,$origin).'<br />';
	}
	echo display_user_link($post['user_id'], $name, $origin).'<br />';
	echo $post['post_date'].'<br /><br />';
	// get attach id
	$attachment_list=get_attachment($post['post_id']);
	$id_attach = !empty($attachment_list)?$attachment_list['id']:'';
	// The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
	// The course admin him/herself can do this off course always
	if (($current_forum['allow_edit']==1 AND $post['user_id']==$_user['user_id']) or (api_is_allowed_to_edit(false,true) && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session']))) {
		echo "<a href=\"editpost.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;origin=".$origin."&amp;post=".$post['post_id']."&id_attach=".$id_attach."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>\n";
	}
	if (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])) {
		echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;action=delete&amp;content=post&amp;id=".$post['post_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeletePost"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>\n";
		display_visible_invisible_icon('post', $post['post_id'], $post['visible'],array('forum'=>Security::remove_XSS($_GET['forum']),'thread'=>Security::remove_XSS($_GET['thread']) ));
		echo "\n";
		if ($count>0) {
			echo "<a href=\"viewthread.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;action=move&amp;origin=".$origin."&amp;post=".$post['post_id']."\">".icon('../img/deplacer_fichier.gif',get_lang('MovePost'))."</a>";	
		}
	}
	$userinf=api_get_user_info($post['user_id']);
	$user_status=api_get_status_of_user_in_course($post['user_id'],api_get_course_id());
	if (api_is_allowed_to_edit()) {	
		if ($count>0 && $user_status!=1) {						
			$current_qualify_thread=show_qualify('1',$_GET['cidReq'],$_GET['forum'],$post['user_id'],$_GET['thread']);
			echo "<a href=\"forumqualify.php?".api_get_cidreq()."&forum=".Security::remove_XSS($_GET['forum'])."&amp;thread=".Security::remove_XSS($_GET['thread'])."&amp;action=list&amp;post=".$post['post_id']."&amp;user=".$post['user_id']."&user_id=".$post['user_id']."&origin=".$origin."&idtextqualify=".$current_qualify_thread."\" >".icon('../img/new_test_small.gif',get_lang('Qualify'))."</a>\n";			
		}
	}
	echo '<br /><br />';
	//if (($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0) OR api_is_allowed_to_edit())
	if ($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit(false,true)) {
		if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id'])) {
			if (!api_is_anonymous()) {
				echo '<a href="reply.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;post='.$post['post_id'].'&amp;action=replymessage&amp;origin='. $origin .'">'.get_lang('ReplyToMessage').'</a><br />';
				echo '<a href="reply.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;post='.$post['post_id'].'&amp;action=quote&amp;origin='. $origin .'">'.get_lang('QuoteMessage').'</a><br /><br />';
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
	echo "</td>\n";
	// note: this can be removed here because it will be displayed in the tree
	if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$post['thread_id']])) {
		$post_image=icon('../img/forumpostnew.gif');
	} else {
		$post_image=icon('../img/forumpost.gif');
	}
	if ($post['post_notification']=='1' AND $post['poster_id']==$_user['user_id']) {
		$post_image.=icon('../img/forumnotification.gif',get_lang('YouWillBeNotified'));
	}		
	// The post title
	echo "\t\t<td class=\"$titleclass\">".prepare4display($post['post_title'])."</td>\n";
	echo "\t</tr>\n";	
	
	// The post message
	echo "\t<tr>\n";
	echo "\t\t<td class=\"$messageclass\">".prepare4display($post['post_text'])."</td>\n";
	echo "\t</tr>\n";
	

	// The check if there is an attachment
	$attachment_list=get_attachment($post['post_id']);	
	
	if (!empty($attachment_list)) {
		echo '<tr><td height="50%">';	
		$realname=$attachment_list['path'];			
		$user_filename=$attachment_list['filename'];
						
		echo Display::return_icon('attachment.gif',get_lang('Attachment'));
		echo '<a href="download.php?file=';		
		echo $realname;
		echo ' "> '.$user_filename.' </a>';
		echo '<span class="forum_attach_comment" >'.$attachment_list['comment'].'</span>';
		if (($current_forum['allow_edit']==1 AND $post['user_id']==$_user['user_id']) or (api_is_allowed_to_edit(false,true)  && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])))	{
		echo '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;origin='.Security::remove_XSS($_GET['origin']).'&amp;action=delete_attach&amp;id_attach='.$attachment_list['id'].'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a><br />';
		}	
		echo '</td></tr>';		
	}
	
	// The post has been displayed => it can be removed from the what's new array
	unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
	unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
	echo "</table>\n";
	echo "</div>";
	$count++;
}

/**
* This function builds an array of all the posts in a given thread where the key of the array is the post_id
* It also adds an element children to the array which itself is an array that contains all the id's of the first-level children
* @return an array containing all the information on the posts of a thread
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function calculate_children($rows) {
	foreach($rows as $row) {
		$rows_with_children[$row["post_id"]]=$row;
		$rows_with_children[$row["post_parent_id"]]["children"][]=$row["post_id"];
	}
	$rows=$rows_with_children;
	$sorted_rows=array(0=>array());
	_phorum_recursive_sort($rows, $sorted_rows);
	unset($sorted_rows[0]);
	return $sorted_rows;
}

function _phorum_recursive_sort($rows, &$threads, $seed=0, $indent=0) {
	if($seed>0) {
		$threads[$rows[$seed]["post_id"]]=$rows[$seed];
		$threads[$rows[$seed]["post_id"]]["indent_cnt"]=$indent;
		$indent++;
	}

	if(isset($rows[$seed]["children"])) {
		foreach($rows[$seed]["children"] as $child) {
			_phorum_recursive_sort($rows, $threads, $child, $indent);
		}
	}
}
