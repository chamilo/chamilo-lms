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

if ($_GET['post'])
{
	$display_post_id=$_GET['post']; // note: this has to be cleaned first
}
else 
{
	// we need to display the first post
	reset($rows);
	$current=current($rows);
	$display_post_id=$current['post_id'];
}

// the style depends on the status of the message: approved or not
if ($rows[$display_post_id]['visible']=='0')
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

// --------------------------------------
// 		Displaying the message 
// --------------------------------------

// we mark the image we are displaying as set
unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]);

echo "<table width=\"100%\"  class=\"post\"  cellspacing=\"5\" border=\"0\">\n";
echo "\t<tr>\n";
echo "\t\t<td rowspan=\"3\" class=\"$leftclass\">";
if ($rows[$display_post_id]['user_id']=='0')
{
	$name=prepare4display($rows[$display_post_id]['poster_name']);
}
else 
{
	$name=$rows[$display_post_id]['firstname'].' '.$rows[$display_post_id]['lastname'];
}
echo display_user_link($rows[$display_post_id]['user_id'], $name).'<br />';
echo $rows[$display_post_id]['post_date'].'<br /><br />';
// The user who posted it can edit his thread only if the course admin allowed this in the properties of the forum
// The course admin him/herself can do this off course always
if (($current_forum['allow_edit']==1 AND $rows[$display_post_id]['user_id']==$_uid) or api_is_allowed_to_edit())
{
	echo "<a href=\"editpost.php?forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."&amp;post=".$rows[$display_post_id]['post_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>\n";
}
if (api_is_allowed_to_edit())
{
	echo "<a href=\"".$_SERVER['PHP_SELF']."?forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."&amp;action=delete&amp;content=post&amp;id=".$rows[$display_post_id]['post_id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeletePost")))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>\n";
	display_visible_invisible_icon('post', $rows[$display_post_id]['post_id'], $rows[$display_post_id]['visible'],array('forum'=>$_GET['forum'],'thread'=>$_GET['thread'], 'post'=>$_GET['post'] ));
	echo "\n";
	echo "<a href=\"viewthread.php?forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."&amp;action=move&amp;post=".$rows[$display_post_id]['post_id']."\">".icon('../img/forummovepost.gif',get_lang('Edit'))."</a>\n";
}
echo '<br /><br />';
//if (($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0) OR api_is_allowed_to_edit())
if ($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit())
{
	if ($_uid OR ($current_forum['allow_anonymous']==1 AND !$_uid))
	{
		echo '<a href="reply.php?forum='.$_GET['forum'].'&amp;thread='.$_GET['thread'].'&amp;post='.$rows[$display_post_id]['post_id'].'&amp;action=replymessage">'.get_lang('ReplyToMessage').'</a><br />';
		echo '<a href="reply.php?forum='.$_GET['forum'].'&amp;thread='.$_GET['thread'].'&amp;post='.$rows[$display_post_id]['post_id'].'&amp;action=quote">'.get_lang('QuoteMessage').'</a><br /><br />';
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
if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$rows[$display_post_id]['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$rows[$display_post_id]['thread_id']]))
{
	$post_image=icon('../img/forumpostnew.gif');
}
else 
{
	$post_image=icon('../img/forumpost.gif');
}
if ($rows[$display_post_id]['post_notification']=='1' AND $rows[$display_post_id]['poster_id']==$_uid)
{
	$post_image.=icon('../img/forumnotification.gif',get_lang('YouWillBeNotified'));
}
// The post title
echo "\t\t<td class=\"$titleclass\">".$post_image." ".prepare4display($rows[$display_post_id]['post_title'])."</td>\n";
echo "\t</tr>\n";	

// The post message
echo "\t<tr>\n";
echo "\t\t<td class=\"$messageclass\">".prepare4display($rows[$display_post_id]['post_text'])."</td>\n";
echo "\t</tr>\n";

/*
// The added resources
echo "<tr><td>";
if (check_added_resources("forum_post", $rows[$display_post_id]["post_id"]))
{
	
	echo "<i>".get_lang("AddedResources")."</i><br/>";
	if ($rows[$display_post_id]['visible']=='0')
	{
		$addedresource_style="invisible";
	}
	display_added_resources("forum_post", $rows[$display_post_id]["post_id"], $addedresource_style);
}
echo "</td></tr>";
*/


// The post has been displayed => it can be removed from the what's new array
unset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
unset($_SESSION['whatsnew_post_info'][$current_forum['forum_id']][$current_thread['thread_id']][$row['post_id']]);
echo "</table>";

// --------------------------------------
// 		Displaying the thread (structure)
// --------------------------------------

echo "<div class=\"structure\">".get_lang('Structure')."</div>";
$counter=0;
foreach ($rows as $post)
{
	$counter++;
	$indent=$post['indent_cnt']*'20';
	echo "<div style=\"margin-left: ".$indent."px;\">";
	if (isset($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$current_forum['forum_id']][$current_thread['thread_id']][$post['post_id']]) and !empty($whatsnew_post_info[$_GET['forum']][$post['thread_id']]))
	{
		$post_image=icon('../img/forumpostnew.gif');
	}
	else 
	{
		$post_image=icon('../img/forumpost.gif');
	}
	echo $post_image;
	if ($_GET['post']==$post['post_id'] OR ($counter==1 AND !isset($_GET['post'])))
	{
		echo '<strong>'.prepare4display($post['post_title']).'</strong></div>';
	}
	else 
	{
		if ($post['visible']=='0')
		{
			$class=' class="invisible"';
		}
		else 
		{
			$class='';
		}
		echo "<a href=\"viewthread.php?forum=".$_GET['forum']."&amp;thread=".$_GET['thread']."&amp;post=".$post['post_id']."\" $class>".prepare4display($post['post_title'])."</a></div>\n";
	}	
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