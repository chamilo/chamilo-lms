<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos S.A.
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
*	These files are a complete rework of the forum. The database structure is 
*	based on phpBB but all the code is rewritten. A lot of new functionalities
*	are added:
* 	- forum categories and forums can be sorted up or down, locked or made invisible
*	- consistent and integrated forum administration
* 	- forum options: 	are students allowed to edit their post? 
* 						moderation of posts (approval)
* 						reply only forums (students cannot create new threads)
* 						multiple forums per group
*	- sticky messages
* 	- new view option: nested view
* 	- quoting a message
*	
*	@Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*	@Copyright Ghent University
*	@Copyright Patrick Cool
* 
* 	@package dokeos.forum
*/

/**
 **************************************************************************
 *						IMPORTANT NOTICE
 * Please do not change anything is this code yet because there are still
 * some significant code that need to happen and I do not have the time to
 * merge files and test it all over again. So for the moment, please do not
 * touch the code
 * 							-- Patrick Cool <patrick.cool@UGent.be>
 ************************************************************************** 
 */


$rows=get_posts($_GET['thread']); // note: this has to be cleaned first
$rows=calculate_children($rows);




foreach ($rows as $post)
{
	// the style depends on the status of the message: approved or not
	if ($post['visible']=='0')
	{
		$titleclass='forum_message_post_title_2_be_approved';
		$messageclass='forum_message_post_text_2_be_approved';
		$leftclass='forum_message_left_2_be_approved';	
	}
	else 
	{
		$titleclass='forum_message_post_title';
		$messageclass='forum_message_post_text';
		$leftclass='forum_message_left';		
	}
	
	$indent=$post['indent_cnt']*'20';
	echo "<div style=\"margin-left: ".$indent."px;\">";
	echo "<table width=\"100%\"  class=\"post\" cellspacing=\"5\" border=\"0\">\n";
	echo "\t<tr>\n";
	echo "\t\t<td rowspan=\"3\" class=\"$leftclass\">";
	if ($post['user_id']=='0')
	{
		$name=$post['poster_name'];
	}
	else 
	{
		$name=$post['firstname'].' '.$post['lastname'];
	}	
	echo display_user_link($post['user_id'], $name).'<br />';
	echo $post['post_date'].'<br /><br />';
	// The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
	// The course admin him/herself can do this off course always
	if (($current_forum['allow_edit']==1 AND $post['user_id']==$_user['user_id']) or api_is_allowed_to_edit())
	{
		echo "<a href=\"editpost.php?".api_get_cidreq()."&forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."&amp;post=".$post['post_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>\n";
	}
	if (api_is_allowed_to_edit())
	{
		echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."&amp;action=delete&amp;content=post&amp;id=".$post['post_id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeletePost"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>\n";
		display_visible_invisible_icon('post', $post['post_id'], $post['visible'],array('forum'=>$_GET['forum'],'thread'=>$_GET['thread'] ));
		echo "\n";
		echo "<a href=\"viewthread.php?".api_get_cidreq()."&forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."&amp;action=move&amp;post=".$post['post_id']."\">".icon('../img/deplacer_fichier.gif',get_lang('Edit'))."</a>";
	}
	echo '<br /><br />';
	//if (($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0) OR api_is_allowed_to_edit())
	if ($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit())
	{
		if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id']))
		{
			echo '<a href="reply.php?'.api_get_cidreq().'&forum='.$_GET['forum'].'&amp;thread='.$_GET['thread'].'&amp;post='.$post['post_id'].'&amp;action=replymessage">'.get_lang('ReplyToMessage').'</a><br />';
			echo '<a href="reply.php?'.api_get_cidreq().'&forum='.$_GET['forum'].'&amp;thread='.$_GET['thread'].'&amp;post='.$post['post_id'].'&amp;action=quote">'.get_lang('QuoteMessage').'</a><br /><br />';
		}
	}
	else 
	{
		if ($current_forum_category['locked']==1)
	{
			echo get_lang('ForumcategoryLocked').'<br />';
		}
		if ($current_forum['locked']==1)
		{
			echo get_lang('ForumLocked').'<br />';
	}
		if ($current_thread['locked']==1)
		{
			echo get_lang('ThreadLocked').'<br />';
	}				
	}
	echo "</td>\n";
	// note: this can be removed here because it will be displayed in the tree
	if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$post['thread_id']]))
	{
		$post_image=icon('../img/forumpostnew.gif');
	}
	else 
	{
		$post_image=icon('../img/forumpost.gif');
	}
	if ($post['post_notification']=='1' AND $post['poster_id']==$_user['user_id'])
	{
		$post_image.=icon('../img/forumnotification.gif',get_lang('YouWillBeNotified'));
	}		
	// The post title
	echo "\t\t<td class=\"$titleclass\">".prepare4display($post['post_title'])."</td>\n";
	echo "\t</tr>\n";	
	
	// The post message
	echo "\t<tr>\n";
	echo "\t\t<td class=\"$messageclass\">".prepare4display($post['post_text'])."</td>\n";
	echo "\t</tr>\n";
	
	/*
	// The added resources
	echo "<tr><td>";
	if (check_added_resources("forum_post", $post["post_id"]))
	{
		
		echo "<i>".get_lang("AddedResources")."</i><br/>";
		if ($post['visible']=='0')
		{
			$addedresource_style="invisible";
		}
		display_added_resources("forum_post", $post["post_id"], $addedresource_style);
		
	}
	echo "</td></tr>";	
	*/
	
	// The post has been displayed => it can be removed from the what's new array
	unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
	unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
	echo "</table>\n";
	echo "</div>";
}



/**
* This function builds an array of all the posts in a given thread where the key of the array is the post_id
* It also adds an element children to the array which itself is an array that contains all the id's of the first-level children
* @return an array containing all the information on the posts of a thread
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function calculate_children($rows)
{
	foreach($rows as $row)
	{
		$rows_with_children[$row["post_id"]]=$row;
		$rows_with_children[$row["post_parent_id"]]["children"][]=$row["post_id"];
	}
	$rows=$rows_with_children;
	$sorted_rows=array(0=>array());
	_phorum_recursive_sort($rows, $sorted_rows);
	unset($sorted_rows[0]);
	return $sorted_rows;
}

function _phorum_recursive_sort($rows, &$threads, $seed=0, $indent=0)
{
	if($seed>0)
	{
		$threads[$rows[$seed]["post_id"]]=$rows[$seed];
		$threads[$rows[$seed]["post_id"]]["indent_cnt"]=$indent;
		$indent++;
	}

	if(isset($rows[$seed]["children"]))
	{
		foreach($rows[$seed]["children"] as $child)
		{
			_phorum_recursive_sort($rows, $threads, $child, $indent);
		}
	}
}

?>