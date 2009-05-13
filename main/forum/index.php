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

// including the global dokeos file
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
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;<img src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
			} else {
					document.getElementById(\'options\').style.display = \'none\';
					document.getElementById(\'plus_minus\').innerHTML=\'&nbsp;<img src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
			}		
		}
	</script>';	
// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// including additional library scripts
require_once(api_get_path(LIBRARY_PATH).'/text.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
$nameTools=get_lang('Forums');

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '400';

$fck_attribute['Config']['IMUploadPath'] = 'upload/forum/';
$fck_attribute['Config']['FlashUploadPath'] = 'upload/forum/';
$fck_attribute['Config']['InDocument'] = false;		
$fck_attribute['Config']['CreateDocumentDir'] = '../../courses/'.api_get_course_path().'/document/';

if(!api_is_allowed_to_edit(false,true)) {
	$fck_attribute['Config']['UserStatus'] = 'student';
	$fck_attribute['ToolbarSet'] = 'Forum_Student';
}
else
{
	$fck_attribute['ToolbarSet'] = 'Forum';
}

/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

if (!empty($_GET['gradebook']) && $_GET['gradebook']=='view' ) {
	$_SESSION['gradebook']=Security::remove_XSS($_GET['gradebook']);
	$gradebook=	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
	unset($_SESSION['gradebook']);
	$gradebook=	'';
} 

if (!empty($gradebook) && $gradebook=='view') {	
	$interbreadcrumb[] = array (
		'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
		'name' => get_lang('Gradebook')
	);
}

$search_forum=isset($_GET['search']) ? Security::remove_XSS($_GET['search']) : '';
$interbreadcrumb[]=array("url" => "index.php?gradebook=$gradebook&search=".$search_forum,"name" => $nameTools);

if (isset($_GET['action']) && $_GET['action']=='add' ) {
		
	switch ($_GET['content']) {
		case 'forum':	$interbreadcrumb[] = array ("url" => api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forum', 'name' => get_lang('AddForum')); break;
		case 'forumcategory':$interbreadcrumb[] = array ("url" => api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forumcategory', 'name' => get_lang('AddForumCategory'));break;
		default: break;
	}
}
 
Display :: display_header('');

// api_display_tool_title($nameTools);
//echo '<link href="forumstyles.css" rel="stylesheet" type="text/css" />';

// Tool introduction
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';
Display::display_introduction_section(TOOL_FORUM,'left');
$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

$form_count=0;


/*
------------------------------------------------------------------------------------------------------
	ACTIONS
------------------------------------------------------------------------------------------------------
*/
$get_actions=isset($_GET['action']) ? $_GET['action'] : '';
if (api_is_allowed_to_edit(false,true)) {
	$fck_attribute['Width'] = '98%';
	$fck_attribute['Height'] = '200';
	$fck_attribute['ToolbarSet'] = 'Forum';
	handle_forum_and_forumcategories();
}

// notification
if (isset($_GET['action']) && $_GET['action'] == 'notify' AND isset($_GET['content']) AND isset($_GET['id'])) {
	$return_message = set_notification($_GET['content'],$_GET['id']);
	Display :: display_confirmation_message($return_message,false);
}

	get_whats_new();
	$whatsnew_post_info = array();
	$whatsnew_post_info = $_SESSION['whatsnew_post_info'];
		
	/*
	-----------------------------------------------------------
	  			TRACKING
	-----------------------------------------------------------
	*/
	
	include(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
	event_access_tool(TOOL_FORUM);
	
	
	/*
	------------------------------------------------------------------------------------------------------
		RETRIEVING ALL THE FORUM CATEGORIES AND FORUMS
	------------------------------------------------------------------------------------------------------
	note: we do this here just after het handling of the actions to be sure that we already incorporate the
	latest changes
	*/
	// Step 1: We store all the forum categories in an array $forum_categories
	$forum_categories=array();
	$forum_categories_list=get_forum_categories();
	
	// step 2: we find all the forums (only the visible ones if it is a student)
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
	if (!api_is_anonymous()) {
		$all_groups=GroupManager::get_group_list();
		if(is_array($all_groups)) {
			foreach ($all_groups as $group) {
				$all_groups[$group['id']]=$group;
			}
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
	------------------------------------------------------------------------------------------------------
		ACTION LINKS
	------------------------------------------------------------------------------------------------------
	*/
		$session_id=isset($_SESSION['id_session']) ? $_SESSION['id_session'] : false;
		//if (api_is_allowed_to_edit() and !$_GET['action'])
		echo '<div class="actions">';
		echo '<span style="float:right;">'.search_link().'</span>';
		if (api_is_allowed_to_edit(false,true)) {
			echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forumcategory"> '.Display::return_icon('forum_category_new.gif', get_lang('AddForumCategory')).' '.get_lang('AddForumCategory').'</a>';
			if (is_array($forum_categories_list) and !empty($forum_categories_list)) {
				echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&gradebook='.$gradebook.'&action=add&amp;content=forum"> '.Display::return_icon('forum_new.gif', get_lang('AddForum')).' '.get_lang('AddForum').'</a>';
			}
			//echo ' | <a href="forum_migration.php">'.get_lang('MigrateForum').'</a>';
		}
		echo '</div>';

	/*
	------------------------------------------------------------------------------------------------------
		Display Forum Categories and the Forums in it
	------------------------------------------------------------------------------------------------------
	*/
	echo '<table class="data_table">'."\n";
	// Step 3: we display the forum_categories first
	if(is_array($forum_categories_list)) {
		foreach ($forum_categories_list as $forum_category_key => $forum_category) {
			if((!isset($_SESSION['id_session']) || $_SESSION['id_session']==0) && !empty($forum_category['session_name'])) {
				$session_displayed = ' ('.$forum_category['session_name'].')';
			} else {
				$session_displayed = '';
			}
			
			echo "\t<tr>\n\t\t<th style=\"padding-left:5px;\" align=\"left\" colspan=\"5\">";
			echo '<a href="viewforumcategory.php?'.api_get_cidreq().'&forumcategory='.prepare4display($forum_category['cat_id']).'" '.class_visible_invisible(prepare4display($forum_category['visibility'])).'>'.prepare4display($forum_category['cat_title']).$session_displayed.'</a><br />';
			
			if ($forum_category['cat_comment']<>'' AND trim($forum_category['cat_comment'])<>'&nbsp;') {  
				echo '<span class="forum_description">'.prepare4display($forum_category['cat_comment']).'</span>';
			}			
			echo "</th>\n";
			
			echo '<th style="vertical-align: top;" align="center" >';
			if (api_is_allowed_to_edit(false,true) && !($forum_category['session_id']==0 && intval($session_id)!=0)) {
				echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=edit&amp;content=forumcategory&amp;id=".prepare4display($forum_category['cat_id'])."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
				echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=delete&amp;content=forumcategory&amp;id=".prepare4display($forum_category['cat_id'])."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeleteForumCategory"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
				display_visible_invisible_icon('forumcategory', prepare4display($forum_category['cat_id']), prepare4display($forum_category['visibility']));
				display_lock_unlock_icon('forumcategory',prepare4display($forum_category['cat_id']), prepare4display($forum_category['locked']));
				display_up_down_icon('forumcategory',prepare4display($forum_category['cat_id']), $forum_categories_list);
			}
			echo '</th>';				
			echo "\t</tr>\n";
		
			// step 4: the interim headers (for the forum)
			echo "\t<tr class=\"forum_header\">\n";
			echo "\t\t<td colspan=\"2\">".get_lang('Forum')."</td>\n";
			echo "\t\t<td>".get_lang('Topics')."</td>\n";
			echo "\t\t<td>".get_lang('Posts')."</td>\n";
			echo "\t\t<td>".get_lang('LastPosts')."</td>\n";
			echo "\t\t<td>".get_lang('Actions')."</td>\n";
			echo "\t</tr>\n";
		
			// the forums in this category
			$forums_in_category=get_forums_in_category($forum_category['cat_id']);
		
			// step 5: we display all the forums in this category.
			$forum_count=0;			
			
			foreach ($forum_list as $key=>$forum) {
				// Here we clean the whatnew_post_info array a little bit because to display the icon we
				// test if $whatsnew_post_info[$forum['forum_id']] is empty or not.
				if (!empty($whatsnew_post_info)) {
					if (is_array(isset($whatsnew_post_info[$forum['forum_id']])?$whatsnew_post_info[$forum['forum_id']]:null)) {
						foreach ($whatsnew_post_info[$forum['forum_id']] as $key_thread_id => $new_post_array) {
							if (empty($whatsnew_post_info[$forum['forum_id']][$key_thread_id]))	{
								unset($whatsnew_post_info[$forum['forum_id']][$key_thread_id]);
								unset($_SESSION['whatsnew_post_info'][$forum['forum_id']][$key_thread_id]);
							}
						}
					}							
				}
				
				// note: this can be speeded up if we transform the $forum_list to an array that uses the forum_category as the key.
				if (prepare4display($forum['forum_category'])==prepare4display($forum_category['cat_id'])) { 
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
					} else {// you are not a teacher
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
							} else if ($forum['forum_group_public_private']=='private') {
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
							} else {
								$show_forum=false;
							}
		
						}
					}
				
					
					//echo '<hr>';
		
					if ($show_forum) {
						$form_count++;
						$mywhatsnew_post_info=isset($whatsnew_post_info[$forum['forum_id']]) ? $whatsnew_post_info[$forum['forum_id']]: null;
						echo "\t<tr class=\"forum\">\n";
						
						// Showing the image	
						if(!empty($forum['forum_image'])) {
							$image_path = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/forum/images/'.$forum['forum_image'];												

							/*
							$image_size = @getimagesize($image_path);
							*/
							$image_size = @getimagesize(api_url_to_local_path($image_path));

							$img_attributes = '';
							if (!empty($image_size)) {
								if ($image_size[0] > 100 || $image_size[1] > 100) {
									//limit display width and height to 100px
									$img_attributes = 'width="100" height="100"';	
								}
								echo "<img src=\"$image_path\" $img_attributes>";
							}
						}														
						echo "</td>\n";										
						echo "\t\t<td width=\"20\">";
						
						if ($forum['forum_of_group']!=='0') {
							if (is_array($mywhatsnew_post_info) and !empty($mywhatsnew_post_info)) {
								echo icon('../img/forumgroupnew.gif');
							} else {
								echo icon('../img/forumgroup.gif', get_lang('GroupForum'));
							}
						} else {
														
								if (is_array($mywhatsnew_post_info) and !empty($mywhatsnew_post_info)) {
									echo icon('../img/forum.gif', get_lang('Forum'));
								} else {
									echo icon('../img/forum.gif');
								}
							
						}
						echo "</td>\n";
						if ($forum['forum_of_group']<>'0') {
							$my_all_groups_forum_name=isset($all_groups[$forum['forum_of_group']]['name']) ? $all_groups[$forum['forum_of_group']]['name'] : null;
							$my_all_groups_forum_id=isset($all_groups[$forum['forum_of_group']]['id']) ? $all_groups[$forum['forum_of_group']]['id'] : null;
							$group_title=api_substr($my_all_groups_forum_name,0,30);

							$forum_title_group_addition=' (<a href="../group/group_space.php?'.api_get_cidreq().'&gidReq='.$forum['forum_of_group'].'" class="forum_group_link">'.get_lang('GoTo').' '.$group_title.'</a>)';

						} else {
							$forum_title_group_addition='';
						}
						
						if((!isset($_SESSION['id_session']) || $_SESSION['id_session']==0) && !empty($forum['session_name'])) {
							$session_displayed = ' ('.$forum['session_name'].')';
						} else {
							$session_displayed = '';
						}
						$forum['forum_of_group']==0?$groupid='':$groupid=$forum['forum_of_group'];
						
						echo "\t\t<td><a href=\"viewforum.php?".api_get_cidreq()."&gidReq=".Security::remove_XSS($groupid)."&forum=".prepare4display($forum['forum_id'])."\" ".class_visible_invisible(prepare4display($forum['visibility'])).">".prepare4display($forum['forum_title']).$session_displayed.'</a>'.$forum_title_group_addition.'<br />'.prepare4display($forum['forum_comment'])."</td>\n";
						//$number_forum_topics_and_posts=get_post_topics_of_forum($forum['forum_id']); // deprecated
						// the number of topics and posts
						$number_threads=isset($forum['number_of_threads']) ? $forum['number_of_threads'] : null;
						$number_posts  =isset($forum['number_of_posts']) ? $forum['number_of_posts'] : null;
						echo "\t\t<td>".$number_threads."</td>\n";
						echo "\t\t<td>".$number_posts."</td>\n";
						// the last post in the forum
						if ($forum['last_poster_name']<>'') {
							$name=$forum['last_poster_name'];
							$poster_id=0;
						} else {
							$name=$forum['last_poster_firstname'].' '.$forum['last_poster_lastname'];
							$poster_id=$forum['last_poster_id'];
						}
						echo "\t\t<td nowrap=\"nowrap\">";
						
						if (!empty($forum['last_post_id'])) {
							echo $forum['last_post_date']."<br /> ".get_lang('By').' '.display_user_link($poster_id, $name);
						}
						echo "</td>\n";
						echo "\t\t<td nowrap=\"nowrap\" align=\"center\">";
						if (api_is_allowed_to_edit(false,true) && !($forum['session_id']==0 && intval($session_id)!=0)) {
							echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=edit&amp;content=forum&amp;id=".$forum['forum_id']."\">".icon('../img/edit.gif',get_lang('Edit'))."</a>";
							echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=delete&amp;content=forum&amp;id=".$forum['forum_id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("DeleteForum"),ENT_QUOTES,$charset))."')) return false;\">".icon('../img/delete.gif',get_lang('Delete'))."</a>";
							display_visible_invisible_icon('forum',$forum['forum_id'], $forum['visibility']);
							display_lock_unlock_icon('forum',$forum['forum_id'], $forum['locked']);
							display_up_down_icon('forum',$forum['forum_id'], $forums_in_category);
						}
						$iconnotify = 'send_mail.gif';
						$session_forum_noti=isset($_SESSION['forum_notification']['forum']) ? $_SESSION['forum_notification']['forum'] : false;
						if (is_array($session_forum_noti)) {
							if (in_array($forum['forum_id'],$session_forum_noti)) {
								$iconnotify = 'send_mail_checked.gif';
							}
						}
						if (!api_is_anonymous()) {		
							echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&gradebook=$gradebook&action=notify&amp;content=forum&amp;id=".$forum['forum_id']."\">".icon('../img/'.$iconnotify,get_lang('NotifyMe'))."</a>";
						}
						echo "</td>\n";
						echo "\t</tr>";
					}
				}					
			}
			
			if (count($forum_list)==0) {
				echo "\t<tr><td>".get_lang('NoForumInThisCategory')."</td>".(api_is_allowed_to_edit(false,true)?'<td colspan="6"></td>':'<td colspan="6"></td>')."</tr>\n";
			}
		}
	}
	echo "</table>\n";

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
