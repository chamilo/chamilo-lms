<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
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

// name of the language file that needs to be included
$language_file = array (
	'forum',
	'group'
);

// including the global dokeos file
require '../inc/global.inc.php';

// notice for unauthorized people.
api_protect_course_script(true);

// the section (tabs)
$this_section=SECTION_COURSES;

// including additional library scripts
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
$nameTools=get_lang('Forum');


//are we in a lp ?
$origin = '';
$origin_string='';
if (isset($_GET['origin'])) {
	$origin =  Security::remove_XSS($_GET['origin']);
	$origin_string = '&origin='.$origin;
}

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$userid=api_get_user_id();
$userinf=api_get_user_info($userid);

/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/


/*
-----------------------------------------------------------
	Retrieving forum and forum categorie information
-----------------------------------------------------------
*/
// we are getting all the information about the current forum and forum category.
// note pcool: I tried to use only one sql statement (and function) for this
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table

$my_forum_group=isset($_GET['gidReq'])?$_GET['gidReq']:'';
$my_forum=isset($_GET['forum'])?$_GET['forum']:'';
$val=GroupManager::user_has_access($userid,$my_forum_group,GROUP_TOOL_FORUM);

if(!empty($my_forum_group)){
		if (api_is_allowed_to_edit(false,true) || $val==true) {
			$current_forum=get_forum_information($my_forum); // note: this has to be validated that it is an existing forum.
			$current_forum_category=get_forumcategory_information($current_forum['forum_category']);
		}
} else {
	$result=get_forum_information($my_forum);
	if($result['forum_of_group']==0){
		$current_forum=get_forum_information($my_forum); // note: this has to be validated that it is an existing forum.
		$current_forum_category=get_forumcategory_information($current_forum['forum_category']);
	}	
}


/*
-----------------------------------------------------------
	Header and Breadcrumbs
-----------------------------------------------------------
*/
$my_search=isset($_GET['search'])?$_GET['search']:'';
$my_action=isset($_GET['action'])?$_GET['action']:'';


if (!empty($_GET['gidReq'])) {
	$toolgroup = Database::escape_string($_GET['gidReq']);
	api_session_register('toolgroup');
}

if (!empty($_SESSION['toolgroup'])) {
	$_clean['toolgroup']=(int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	//$interbreadcrumb[]=array("url" => "index.php?search=".Security::remove_XSS($my_search),"name" => $nameTools);
	//$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id']."&amp;search=".Security::remove_XSS(urlencode($my_search)),"name" => prepare4display($current_forum_category['cat_title']));
	$interbreadcrumb[]=array("url" => "#","name" => prepare4display($current_forum['forum_title'])); 
	//viewforum.php?forum=".Security::remove_XSS($my_forum)."&amp;origin=".$origin."&amp;gidReq=".$_SESSION['toolgroup']."&amp;search=".Security::remove_XSS(urlencode($my_search)),

} else {
	$interbreadcrumb[]=array("url" => "index.php?search=".Security::remove_XSS($my_search),"name" => $nameTools);
	$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id']."&amp;search=".Security::remove_XSS(urlencode($my_search)),"name" => prepare4display($current_forum_category['cat_title']));
	$interbreadcrumb[]=array("url" => "#","name" => prepare4display($current_forum['forum_title'])); 
	//viewforum.php?forum=".Security::remove_XSS($my_forum)."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode($my_search))
}

if ($origin=='learnpath') {
	include(api_get_path(INCLUDE_PATH).'reduced_header.inc.php');
} else {
	// the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
	Display :: display_header('');
	//api_display_tool_title($nameTools);
}

/*
-----------------------------------------------------------
	Actions
-----------------------------------------------------------
*/
$table_link 			= Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
// Change visibility of a forum or a forum category
if (($my_action=='invisible' OR $my_action=='visible') AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit(false,true)) {
	$message=change_visibility($_GET['content'], $_GET['id'],$_GET['action']);// note: this has to be cleaned first
}
// locking and unlocking
if (($my_action=='lock' OR $my_action=='unlock') AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit(false,true)) {
	$message=change_lock_status($_GET['content'], $_GET['id'],$my_action);// note: this has to be cleaned first
}
// deleting
if ($my_action=='delete'  AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit(false,true)) {
	$message=delete_forum_forumcategory_thread($_GET['content'],$_GET['id']); // note: this has to be cleaned first
	//delete link
	$sql_link='DELETE FROM '.$table_link.' WHERE ref_id='.Security::remove_XSS($_GET['id']).' and type=5 and course_code="'.api_get_course_id().'";';
	api_sql_query($sql_link);
}
// moving
if ($my_action=='move' and isset($_GET['thread']) AND api_is_allowed_to_edit(false,true)) {
	$message=move_thread_form();
}
// notification
if ($my_action == 'notify' AND isset($_GET['content']) AND isset($_GET['id'])) {
	$return_message = set_notification($_GET['content'],$_GET['id']);
	Display :: display_confirmation_message($return_message,false);
}

// student list 

if ($my_action == 'liststd' AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit()) {
	
	switch($_GET['list']) {	
		case "qualify":
			$student_list=get_thread_users_qualify($_GET['id']);			
			$nrorow3 =-2;
			break;
		case "notqualify":
			$student_list=get_thread_users_not_qualify($_GET['id']);				
			$nrorow3 =-2;				
			break;
		default:
			$student_list=get_thread_users_details($_GET['id']);
			$nrorow3 = Database::num_rows($student_list);
			break;			
	}	
	$table_list = '<p><br /><h3>'.get_lang('ThreadUsersList').'&nbsp;:'.get_name_thread_by_id($_GET['id']).'</h3>';
	if ($nrorow3>0 || $nrorow3==-2) {		
		$url = 'cidReq='.Security::remove_XSS($_GET['cidReq']).'&forum='.Security::remove_XSS($my_forum).'&action='.Security::remove_XSS($_GET['action']).'&content='.Security::remove_XSS($_GET['content']).'&id='.Security::remove_XSS($_GET['id']);
		$table_list.= '<br />
				 <div style="width:50%">
				 <table class="data_table" border="0">
					<tr>
						<th height="22"><a href="viewforum.php?'.$url.'&origin='.$origin.'&list=all">'.get_lang('AllStudents').'</a></th>
						<th><a href="viewforum.php?'.$url.'&origin='.$origin.'&list=qualify">'.get_lang('StudentsQualified').'</a></th>
						<th><a href="viewforum.php?'.$url.'&origin='.$origin.'&list=notqualify">'.get_lang('StudentsNotQualified').'</a></th>					
					</tr>
				 </table></div>
				 <div style="border:1px solid gray; width:99%; margin-top:5px; padding:4px; float:left">
				 ';
				 
		$icon_qualify = 'blog_new.gif';		
		$table_list.= '<center><br /><table class="data_table" style="width:50%">';
		// The column headers (to do: make this sortable)
		$table_list.= '<tr >';
		$table_list.= '<th height="24">'.get_lang('NamesAndLastNames').'</th>';
	
		if ($_GET['list']=='qualify') {		
			$table_list.= '<th>'.get_lang('Qualification').'</th>';
		}		
		if (api_is_allowed_to_edit()) {
			$table_list.= '<th>'.get_lang('Qualify').'</th>';
		}	
		$table_list.= '</tr>';
		$max_qualify=show_qualify('2',$_GET['cidReq'],$my_forum,$userid,$_GET['id']);
		$counter_stdlist=0;	
			
		if (Database::num_rows($student_list)>0) {
			while ($row_student_list=Database::fetch_array($student_list)) {
				if ($counter_stdlist%2==0) {
						 $class_stdlist="row_odd";
				} else {
						$class_stdlist="row_even";
				}
				$name_user_theme = 	$row_student_list['firstname'].' '.$row_student_list['lastname'];					
				$table_list.= '<tr class="$class_stdlist"><td><a href="../user/userInfo.php?uInfo='.$row_student_list['user_id'].'&tipo=sdtlist&'.api_get_cidreq().'&forum='.Security::remove_XSS($my_forum).$origin_string.'">'.$name_user_theme.'</a></td>';
				if ($_GET['list']=='qualify') {
					$table_list.= '<td>'.$row_student_list['qualify'].'/'.$max_qualify.'</td>';
				}
				if (api_is_allowed_to_edit()) {
					$current_qualify_thread=show_qualify('1',$_GET['cidReq'],$my_forum,$row_student_list['user_id'],$_GET['id']);					
					$table_list.= '<td><a href="forumqualify.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($my_forum).'&thread='.Security::remove_XSS($_GET['id']).'&user='.$row_student_list['user_id'].'&user_id='.$row_student_list['user_id'].'&idtextqualify='.$current_qualify_thread.'&origin='.$origin.'">'.icon('../img/'.$icon_qualify,get_lang('Qualify')).'</a></td></tr>';
				}
				$counter_stdlist++;
			}	
		} else {
			if ($_GET['list']=='qualify'){
				$table_list.='<tr><td colspan="2">'.get_lang('ThereIsNotQualifiedLearners').'</td></tr>';	
			} else {
				$table_list.='<tr><td colspan="2">'.get_lang('ThereIsNotUnqualifiedLearners').'</td></tr>';
			}				
		}
				
		$table_list.= '</table></center>';
		$table_list .= '<br /></div>';
	} else {
		$table_list .= get_lang('NoParticipation');
	}
}


/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false,true) AND ($current_forum_category['visibility']==0 OR $current_forum['visibility']==0)) {
	forum_not_allowed_here();
}


/*
-----------------------------------------------------------
	Display the action messages
-----------------------------------------------------------
*/
if (!empty($message)) {
	Display :: display_confirmation_message($message);
}


/*
-----------------------------------------------------------
	Action Links
-----------------------------------------------------------
*/
echo '<div class="actions">';
if (empty($_SESSION['toolgroup'])){
	echo '<span style="float:right;">'.search_link().'</span>';
}
echo '<a href="index.php">'.Display::return_icon('back.png').' '.get_lang('BackToForumOverview').'</a>';
// The link should appear when
// 1. the course admin is here
// 2. the course member is here and new threads are allowed
// 3. a visitor is here and new threads AND allowed AND  anonymous posts are allowed
if (api_is_allowed_to_edit(false,true) OR ($current_forum['allow_new_threads']==1 AND isset($_user['user_id'])) OR ($current_forum['allow_new_threads']==1 AND !isset($_user['user_id']) AND $current_forum['allow_anonymous']==1)) {
	if ($current_forum['locked'] <> 1 AND $current_forum['locked'] <> 1) { 
		echo '<a href="newthread.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($my_forum).$origin_string.'">'.Display::return_icon('forumthread_new.gif',get_lang('NewTopic')).' '.get_lang('NewTopic').'</a>';
	} else {
		echo get_lang('ForumLocked');
	}
}
echo '</div>';

/*
-----------------------------------------------------------
					Display
-----------------------------------------------------------
*/
echo "<table class=\"data_table\" >\n";

// the current forum 
if ($origin != 'learnpath') {
	echo "\t<tr>\n\t\t<th align=\"left\" colspan=\"7\">";	
	echo '<span class="forum_title">'.prepare4display($current_forum['forum_title']).'</span>';
		
	if (!empty ($current_forum['forum_comment'])) {
		echo '<br><span class="forum_description">'.prepare4display($current_forum['forum_comment']).'</span>';
	}
	
	if (!empty ($current_forum_category['cat_title'])) {
		echo '<br /><span class="forum_low_description">'.prepare4display($current_forum_category['cat_title'])."</span><br />";
	}	
	echo "</th>\n";
	echo "\t</tr>\n";
}

echo "</th>\n";
echo "\t</tr>\n";

// The column headers (to do: make this sortable)
echo "\t<tr class=\"forum_threadheader\">\n";
echo "\t\t<td></td>\n";
echo "\t\t<td>".get_lang('Title')."</td>\n";
echo "\t\t<td>".get_lang('Replies')."</td>\n";
echo "\t\t<td>".get_lang('Views')."</td>\n";
echo "\t\t<td>".get_lang('Author')."</td>\n";
echo "\t\t<td>".get_lang('LastPost')."</td>\n";
echo "\t\t<td>".get_lang('Actions')."</td>\n";
echo "\t</tr>\n";

// getting al the threads
$threads=get_threads($my_forum); // note: this has to be cleaned first

$whatsnew_post_info=isset($_SESSION['whatsnew_post_info'])?$_SESSION['whatsnew_post_info']:null;

$counter=0;
if(is_array($threads)) {
	foreach ($threads as $row) {
		// thread who have no replies yet and the only post is invisible should not be displayed to students.
		if (api_is_allowed_to_edit(false,true) OR  !($row['thread_replies']=='0' AND $row['visible']=='0')) {
			if($counter%2==0) {
				 $class="row_odd";
			} else {
				$class="row_even";
			}
			echo "\t<tr class=\"$class\">\n";
			echo "\t\t<td>";
			$my_whatsnew_post_info=isset($whatsnew_post_info[$my_forum][$row['thread_id']])?$whatsnew_post_info[$my_forum][$row['thread_id']]:null;
			if (is_array($my_whatsnew_post_info) and !empty($my_whatsnew_post_info)) {
				echo icon('../img/forumthread.gif');
			} else {
				echo icon('../img/forumthread.gif');
			}
	
			if ($row['thread_sticky']==1) {
				echo icon('../img/exclamation.gif');
			}
			echo "</td>\n";
			echo "\t\t<td>";			
			echo "<a href=\"viewthread.php?".api_get_cidreq()."&forum=".Security::remove_XSS($my_forum)."&amp;origin=".$origin."&amp;thread=".$row['thread_id'].$origin_string."&amp;search=".Security::remove_XSS(urlencode($my_search))."\" ".class_visible_invisible($row['visibility']).">".prepare4display($row['thread_title'])."</a></td>\n";
			echo "\t\t<td>".$row['thread_replies']."</td>\n";
			if ($row['user_id']=='0') {
				$name=prepare4display($row['thread_poster_name']);
			} else {
				$name=$row['firstname'].' '.$row['lastname'];
			}			
			echo "\t\t<td>".$row['thread_views']."</td>\n";
			if ($row['last_poster_user_id']=='0') {
				$name=$row['poster_name'];  
			} else {
				$name=$row['last_poster_firstname'].' '.$row['last_poster_lastname'];
			}
			
			if($origin != 'learnpath') {
				echo "\t\t<td>".display_user_link($row['user_id'], $row['firstname'].' '.$row['lastname'])."</td>\n";
			} else {
				echo "\t\t<td>".$row['firstname'].' '.$row['lastname']."</td>\n";
			}
			
			// if the last post is invisible and it is not the teacher who is looking then we have to find the last visible post of the thread
			if (($row['visible']=='1' OR api_is_allowed_to_edit(false,true)) && $origin!='learnpath') {
				$last_post=$row['thread_date']." ".get_lang('By').' '.display_user_link($row['last_poster_user_id'], $name);
			} elseif ($origin!='learnpath') {
				$last_post_sql="SELECT post.*, user.firstname, user.lastname FROM $table_posts post, $table_users user WHERE post.poster_id=user.user_id AND visible='1' AND thread_id='".$row['thread_id']."' ORDER BY post_id DESC";
				$last_post_result=api_sql_query($last_post_sql, __FILE__, __LINE__);
				$last_post_row=Database::fetch_array($last_post_result);
				$name=$last_post_row['firstname'].' '.$last_post_row['lastname'];
				$last_post=$last_post_row['post_date']." ".get_lang('By').' '.display_user_link($last_post_row['poster_id'], $name);
			} else {
				$last_post_sql="SELECT post.*, user.firstname, user.lastname FROM $table_posts post, $table_users user WHERE post.poster_id=user.user_id AND visible='1' AND thread_id='".$row['thread_id']."' ORDER BY post_id DESC";
				$last_post_result=api_sql_query($last_post_sql, __FILE__, __LINE__);
				$last_post_row=Database::fetch_array($last_post_result);
				$name=$last_post_row['firstname'].' '.$last_post_row['lastname'];
				$last_post=$last_post_row['post_date']." ".get_lang('By').' '.$name;
			}
			echo "\t\t<td>".$last_post."</td>\n";
			echo "\t\t<td>";	
			// get attach id
			$attachment_list=get_attachment($row['post_id']);
			$id_attach = !empty($attachment_list)?$attachment_list['id']:'';
							
			if (api_is_allowed_to_edit(false,true) && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])) {
				echo "<a href=\"editpost.php?".api_get_cidreq()."&forum=".Security::remove_XSS($my_forum)."&amp;thread=".Security::remove_XSS($row['thread_id'])."&amp;post=".$row['post_id']."&amp;gidReq=".$_SESSION['toolgroup']."&origin=".$origin."&id_attach=".$id_attach."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>\n";
				echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forum=".Security::remove_XSS($my_forum)."&amp;action=delete&amp;content=thread&amp;gidReq=".$_SESSION['toolgroup']."&amp;id=".$row['thread_id'].$origin_string."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("DeleteCompleteThread"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
				display_visible_invisible_icon('thread', $row['thread_id'], $row['visibility'], array("forum"=>$my_forum,'origin'=>$origin,"gidReq"=>$_SESSION['toolgroup']));
				display_lock_unlock_icon('thread',$row['thread_id'], $row['locked'], array("forum"=>$my_forum,'origin'=>$origin,"gidReq"=>$_SESSION['toolgroup']));
				echo "<a href=\"viewforum.php?".api_get_cidreq()."&forum=".Security::remove_XSS($my_forum)."&amp;action=move&amp;gidReq=".$_SESSION['toolgroup']."&amp;thread=".$row['thread_id'].$origin_string."\">".icon('../img/deplacer_fichier.gif',get_lang('MoveThread'))."</a>";
			}
			$iconnotify = 'send_mail.gif';
			if (is_array(isset($_SESSION['forum_notification']['thread'])?$_SESSION['forum_notification']['thread']:null)) {
				if (in_array($row['thread_id'],$_SESSION['forum_notification']['thread'])) {
					$iconnotify = 'send_mail_checked.gif';
				}
			}
			$icon_liststd = 'group.gif';
			echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;forum=".Security::remove_XSS($my_forum)."&origin=".$origin."&amp;action=notify&amp;content=thread&amp;gidReq=".$_SESSION['toolgroup']."&amp;id=".$row['thread_id']."\">".icon('../img/'.$iconnotify,get_lang('NotifyMe'))."</a>";
			if (api_is_allowed_to_edit()) {
				echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($my_forum).'&origin='.$origin.'&amp;action=liststd&amp;content=thread&amp;gidReq='.$_SESSION['toolgroup'].'&amp;id='.$row['thread_id'].'">'.icon('../img/'.$icon_liststd,get_lang('StudentList')).'</a>';			
			}
			echo "</td>\n";
			echo "\t</tr>\n";
		}
		$counter++;


	}
}
echo "</table>";
echo isset($table_list)?$table_list:'';
/*
==============================================================================
		FOOTER
==============================================================================
*/
if ($origin != 'learnpath') {
	Display :: display_footer();
}