<?php // $Id: document.php 16494 2008-10-10 22:07:36Z yannoo $

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
$language_file = 'forum';
// including the global dokeos init file
require '../inc/global.inc.php';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_CODE_PATH).'inc/lib/javascript/jquery.js" ></script>';
$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">
	$(document).ready(function(){ $(\'.hide-me\').slideUp() });
	function hidecontent(content){ $(content).slideToggle(\'normal\'); }
	</script>';
$htmlHeadXtra[] = '<script type="text/javascript" language="javascript">

		function advanced_parameters() {
			if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
			} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\';
			}
		}
	</script>';


// including the global dokeos file
require '../inc/global.inc.php';

// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// including additional library scripts
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
$nameTools=get_lang('Forum');

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';


/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/


/*
-----------------------------------------------------------
	Header and Breadcrumbs
-----------------------------------------------------------
*/
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$current_forum_category=get_forum_categories($_GET['forumcategory']);
$interbreadcrumb[]=array("url" => "index.php?gradebook=$gradebook&search=".Security::remove_XSS(urlencode(isset($_GET['search'])?$_GET['search']:'')),"name" => $nameTools);
$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id']."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode(isset($_GET['search'])?$_GET['search']:'')),"name" => prepare4display($current_forum_category['cat_title']));


if (!empty($_GET['action']) && !empty($_GET['content'])) {
	if ($_GET['action']=='add' && $_GET['content']=='forum' ) {
		$interbreadcrumb[] = array ("url" => api_get_self().'?'.api_get_cidreq().'&action=add&amp;content=forum', 'name' => get_lang('AddForum'));
	}
}

//are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
	$origin =  Security::remove_XSS($_GET['origin']);
}

if ($origin=='learnpath') {
	include(api_get_path(INCLUDE_PATH).'reduced_header.inc.php');
} else {
	Display :: display_header(null);
	//api_display_tool_title($nameTools);
}

/*
------------------------------------------------------------------------------------------------------
	ACTIONS
------------------------------------------------------------------------------------------------------
*/
$whatsnew_post_info=$_SESSION['whatsnew_post_info'];

/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false,true) AND $current_forum_category['visibility']==0) {
	forum_not_allowed_here();
}

/*
-----------------------------------------------------------
	Action Links
-----------------------------------------------------------
*/
echo '<div class="actions">';
echo '<span style="float:right;">'.search_link().'</span>';
echo '<a href="index.php?gradebook='.$gradebook.'">'.Display::return_icon('back.png',get_lang('BackToForumOverview')).' '.get_lang('BackToForumOverview').'</a>';
if (api_is_allowed_to_edit(false,true)) {
	//echo '<a href="'.api_get_self().'?forumcategory='.$_GET['forumcategory'].'&amp;action=add&amp;content=forumcategory">'.get_lang('AddForumCategory').'</a> | ';
	echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=add&amp;content=forum"> '.Display::return_icon('forum_new.gif', get_lang('AddForum')).' '.get_lang('AddForum').'</a>';
	//echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&forumcategory='.Security::remove_XSS($_GET['forumcategory']).'&amp;action=add&amp;content=forum">'.Display::return_icon('forum_new.gif', get_lang('AddForum')).' '.get_lang('AddForum').'</a>';
}
echo '</div>';

/*
------------------------------------------------------------------------------------------------------
	ACTIONS
------------------------------------------------------------------------------------------------------
*/
$action_forums=isset($_GET['action']) ? $_GET['action'] : '';
if (api_is_allowed_to_edit(false,true)) {
	handle_forum_and_forumcategories();
}

// notification
if ($action_forums == 'notify' AND isset($_GET['content']) AND isset($_GET['id'])) {
	$return_message = set_notification($_GET['content'],$_GET['id']);
	Display :: display_confirmation_message($return_message,false);
}

if ($action_forums!='add') {
	/*
	------------------------------------------------------------------------------------------------------
		RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
	------------------------------------------------------------------------------------------------------
	note: we do this here just after het handling of the actions to be sure that we already incorporate the
	latest changes
	*/
	// Step 1: We store all the forum categories in an array $forum_categories
	$forum_categories=array();
	$forum_category=get_forum_categories($_GET['forumcategory']);

	// step 2: we find all the forums
	$forum_list=array();
	$forum_list=get_forums();

	/*
	------------------------------------------------------------------------------------------------------
		RETRIEVING ALL GROUPS AND THOSE OF THE USER
	------------------------------------------------------------------------------------------------------
	*/
	// the groups of the user
	$groups_of_user=array();
	$groups_of_user=GroupManager::get_group_ids($_course['dbName'], $_user['user_id']);
	// all groups in the course (and sorting them as the id of the group = the key of the array
	$all_groups=GroupManager::get_group_list();
	if(is_array($all_groups)) {
		foreach ($all_groups as $group) {
			$all_groups[$group['id']]=$group;
		}
	}

	/*
	------------------------------------------------------------------------------------------------------
		CLEAN GROUP ID FOR AJAXFILEMANAGER
	------------------------------------------------------------------------------------------------------
	*/
	if(isset($_SESSION['_gid']))
	{
		unset($_SESSION['_gid']);
    }

	/*
	-----------------------------------------------------------
		Display Forum Categories and the Forums in it
	-----------------------------------------------------------
	*/
	echo "<table class=\"data_table\" width='100%'>\n";
	$my_session=isset($_SESSION['id_session']) ? $_SESSION['id_session'] : null;
	$forum_categories_list='';
	echo "\t<tr>\n\t\t<th align=\"left\" ".(api_is_allowed_to_edit()?"colspan='5'":"colspan='6'").">";
	echo '<span class="forum_title">'.prepare4display($forum_category['cat_title']).'</span><br />';
	echo '<span class="forum_description">'.prepare4display($forum_category['cat_comment']).'</span>';
	echo "</th>\n";
	if (api_is_allowed_to_edit(false,true) && !($forum_category['session_id']==0 && intval($my_session)!=0)) {
		echo '<th style="vertical-align: top;" align="center" >';
		echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=edit&amp;content=forumcategory&amp;id=".$forum_category['cat_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
		echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=delete&amp;content=forumcategory&amp;amp;id=".$forum_category['cat_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeleteForumCategory"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
		display_visible_invisible_icon('forumcategory', $forum_category['cat_id'], $forum_category['visibility'], array("forumcategory"=>$_GET['forumcategory']));
		display_lock_unlock_icon('forumcategory',$forum_category['cat_id'], $forum_category['locked'], array("forumcategory"=>$_GET['forumcategory']));
		display_up_down_icon('forumcategory',$forum_category['cat_id'], $forum_categories_list);
		echo "</th>\n";
	}
	echo "\t</tr>\n";

	// step 3: the interim headers (for the forum)
	echo "\t<tr class=\"forum_header\">\n";
	echo "\t\t<td colspan='2'>".get_lang('Forum')."</td>\n";
	echo "\t\t<td>".get_lang('Topics')."</td>\n";
	echo "\t\t<td>".get_lang('Posts')."</td>\n";
	echo "\t\t<td>".get_lang('LastPosts')."</td>\n";
	echo "\t\t<td>".get_lang('Actions')."</td>\n";
	echo "\t</tr>\n";

	// the forums in this category
	$forums_in_category=get_forums_in_category($forum_category['cat_id']);

	// step 4: we display all the forums in this category.
	$forum_count=0;
	foreach ($forum_list as $key=>$forum) {
		if ($forum['forum_category']==$forum_category['cat_id']) {
			// the forum has to be showed if
			// 1.v it is a not a group forum (teacher and student)
			// 2.v it is a group forum and it is public (teacher and student)
			// 3. it is a group forum and it is private (always for teachers only if the user is member of the forum
			// if the forum is private and it is a group forum and the user is not a member of the group forum then it cannot be displayed
			//if (!($forum['forum_group_public_private']=='private' AND !is_null($forum['forum_of_group']) AND !in_array($forum['forum_of_group'], $groups_of_user)))
			//{
			$show_forum=false;
			// SHOULD WE SHOW THIS PARTICULAR FORUM
			// you are teacher => show forum

			if (api_is_allowed_to_edit(false,true)) {
				//echo 'teacher';
				$show_forum=true;
			} else {
				// you are not a teacher
				//echo 'student';
				// it is not a group forum => show forum (invisible forums are already left out see get_forums function)
				if ($forum['forum_of_group']=='0') {
					//echo '-gewoon forum';
					$show_forum=true;
				} else {
					// it is a group forum
					//echo '-groepsforum';
					// it is a group forum but it is public => show
					if ($forum['forum_group_public_private']=='public') {
						$show_forum=true;
						//echo '-publiek';
					} else {
						// it is a group forum and it is private
						//echo '-prive';
						// it is a group forum and it is private but the user is member of the group
						if (in_array($forum['forum_of_group'],$groups_of_user)) {
							//echo '-is lid';
							$show_forum=true;
						} else {
							//echo '-is GEEN lid';
							$show_forum=false;
						}
					}

				}
			}
			//echo '<hr>';
			$form_count=isset($form_count)?$form_count:0;
			if ($show_forum === true) {
				$form_count++;
				echo "\t<tr class=\"forum\">\n";
				echo "\t\t<td width=\"20\">";
				$my_whatsnew_post_info=isset($whatsnew_post_info[$forum['forum_id']])?$whatsnew_post_info[$forum['forum_id']]:null;
				if ($forum['forum_of_group']!=='0') {
					if (is_array($my_whatsnew_post_info) and !empty($my_whatsnew_post_info)) {
						echo icon('../img/forumgroupnew.gif');
					} else {
						echo icon('../img/forumgroup.gif', get_lang('GroupForum'));
					}
				} else {
					if (is_array($my_whatsnew_post_info) and !empty($my_whatsnew_post_info)) {
						echo icon('../img/forum.gif', get_lang('Forum'));
					} else {
						echo icon('../img/forum.gif');
					}
				}
				echo "</td>\n";

				if ($forum['forum_of_group']<>'0')
				{
					$my_all_groups_forum_name=isset($all_groups[$forum['forum_of_group']]['name']) ? $all_groups[$forum['forum_of_group']]['name'] : null;
					$my_all_groups_forum_id=isset($all_groups[$forum['forum_of_group']]['id']) ? $all_groups[$forum['forum_of_group']]['id'] : null;
					$group_title=api_substr($my_all_groups_forum_name,0,30);
					$forum_title_group_addition=' (<a href="../group/group_space.php?'.api_get_cidreq().'&gidReq='.$my_all_groups_forum_id.'" class="forum_group_link">'.get_lang('GoTo').' '.$group_title.'</a>)';
				}
				else
				{
					$forum_title_group_addition='';
				}


				if((!isset($_SESSION['id_session']) || $_SESSION['id_session']==0) && !empty($forum['session_name'])) {
					$session_displayed = ' ('.$forum['session_name'].')';
				} else {
					$session_displayed = '';
				}
				echo "\t\t<td><a href=\"viewforum.php?".api_get_cidreq()."&forum=".$forum['forum_id']."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode(isset($_GET['search'])?$_GET['search']:''))."\" ".class_visible_invisible($forum['visibility']).">".prepare4display($forum['forum_title']).$session_displayed.'</a>'.$forum_title_group_addition.'<br />'.prepare4display($forum['forum_comment'])."</td>\n";

				//$number_forum_topics_and_posts=get_post_topics_of_forum($forum['forum_id']); // deprecated
				// the number of topics and posts
				$my_number_threads=isset($forum['number_of_threads']) ? $forum['number_of_threads'] : '';
				$my_number_posts=isset($forum['number_of_posts']) ? $forum['number_of_posts'] : '';
				echo "\t\t<td>".$my_number_threads."</td>\n";
				echo "\t\t<td>".$my_number_posts."</td>\n";
				// the last post in the forum
				if ($forum['last_poster_name']<>'') {
					$name=$forum['last_poster_name'];
					$poster_id=0;
				} else {
					$name=api_get_person_name($forum['last_poster_firstname'], $forum['last_poster_lastname']);
					$poster_id=$forum['last_poster_id'];
				}
				echo "\t\t<td>";
				if (!empty($forum['last_post_id'])) {
					echo $forum['last_post_date']." ".get_lang('By').' '.display_user_link($poster_id, $name);
				}
				echo "</td>\n";
				echo "\t\t<td NOWRAP align='center'>";
				if (api_is_allowed_to_edit(false,true) && !($forum['session_id']==0 && intval(isset($_SESSION['id_session'])?$_SESSION['id_session']:null)!=0)) {
					echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=edit&amp;content=forum&amp;id=".$forum['forum_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
					echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=delete&amp;content=forum&amp;id=".$forum['forum_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeleteForum"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
					display_visible_invisible_icon('forum',$forum['forum_id'], $forum['visibility'], array("forumcategory"=>$_GET['forumcategory']));
					display_lock_unlock_icon('forum',$forum['forum_id'], $forum['locked'], array("forumcategory"=>$_GET['forumcategory']));
					display_up_down_icon('forum',$forum['forum_id'], $forums_in_category);
				}
				$iconnotify = 'send_mail.gif';
				if (is_array(isset($_SESSION['forum_notification']['forum'])?$_SESSION['forum_notification']['forum']:null))	{
					if (in_array($forum['forum_id'],$_SESSION['forum_notification']['forum'])) {
						$iconnotify = 'send_mail_checked.gif';
					}
				}
				if (!api_is_anonymous()) {
					echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;forumcategory=".Security::remove_XSS($_GET['forumcategory'])."&amp;action=notify&amp;content=forum&amp;id=".$forum['forum_id']."\">".icon('../img/'.$iconnotify,get_lang('NotifyMe'))."</a>";
				}
				echo "</td>\n";
				echo "\t</tr>";
			}
		}
	}
	if (count($forum_list) == 0) {
		echo "\t<tr><td>".get_lang('NoForumInThisCategory')."</td></tr>\n";
	}

	echo "</table>\n";
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
// footer
if ($origin!='learnpath') {
	Display :: display_footer();
}
