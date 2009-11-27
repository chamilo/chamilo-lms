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

// the section (tabs)
$this_section=SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

// including additional library scripts
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
//require_once (api_get_path(LIBRARY_PATH).'resourcelinker.lib.php');
$nameTools=get_lang('Forum');

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';


//are we in a lp ?
$origin = '';
if (isset($_GET['origin'])) {
	$origin =  Security::remove_XSS($_GET['origin']);
}


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
$current_thread=get_thread_information($_GET['thread']); // note: this has to be validated that it is an existing thread
$current_forum=get_forum_information($current_thread['forum_id']); // note: this has to be validated that it is an existing forum.
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);

$whatsnew_post_info=$_SESSION['whatsnew_post_info'];

/*
-----------------------------------------------------------
	Header and Breadcrumbs
-----------------------------------------------------------
*/



if (!empty($_GET['gradebook']) && $_GET['gradebook']=='view' ) {
	$_SESSION['gradebook']=Security::remove_XSS($_GET['gradebook']);
	$gradebook=	$_SESSION['gradebook'];
} /*elseif (empty($_GET['gradebook'])) {
	unset($_SESSION['gradebook']);
	$gradebook=	'';
} */

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[] = array (
		'url' => '../gradebook/' . $_SESSION['gradebook_dest'],
		'name' => get_lang('Gradebook')
	);
}

if (!empty($_SESSION['toolgroup'])) {

	$_clean['toolgroup']=(int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array("url"=>"../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array("url"=>"../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	$interbreadcrumb[] = array("url"=>"viewforum.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;gidReq=".$_SESSION['toolgroup']."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode($my_search)),"name" => prepare4display($current_forum['forum_title']));
	$interbreadcrumb[] = array("url"=>"viewthread.php?forum=".Security::remove_XSS($_GET['forum'])."&gradebook=".$gradebook."&amp;thread=".Security::remove_XSS($_GET['thread']),"name" => prepare4display($current_thread['thread_title']));

	Display :: display_header('');
	api_display_tool_title($nameTools);

} else {

	$my_search=isset($_GET['search']) ? $_GET['search'] : '';


	if ($origin=='learnpath') {
		include(api_get_path(INCLUDE_PATH).'reduced_header.inc.php');
	} else {

		$interbreadcrumb[]=array("url" => "index.php?gradebook=$gradebook&search=".Security::remove_XSS(urlencode($my_search)),"name" => $nameTools);
		$interbreadcrumb[]=array("url" => "viewforumcategory.php?forumcategory=".$current_forum_category['cat_id']."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode($my_search)),"name" => prepare4display($current_forum_category['cat_title']));
		$interbreadcrumb[]=array("url" => "viewforum.php?forum=".Security::remove_XSS($_GET['forum'])."&amp;origin=".$origin."&amp;search=".Security::remove_XSS(urlencode($my_search)),"name" => prepare4display($current_forum['forum_title']));
		$message = isset($message) ? $message : '';
		// the last element of the breadcrumb navigation is already set in interbreadcrumb, so give empty string
		Display :: display_header('');
		api_display_tool_title($nameTools);
	}
}
/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false,true) AND ($current_forum['visibility']==0 OR $current_thread['visibility']==0)) {
	forum_not_allowed_here();
}

/*
-----------------------------------------------------------
	Actions
-----------------------------------------------------------
*/
$my_action = isset($_GET['action']) ? $_GET['action'] : '';
if ($my_action=='delete' AND isset($_GET['content']) AND isset($_GET['id']) AND api_is_allowed_to_edit(false,true)) {
	$message=delete_post($_GET['id']); // note: this has to be cleaned first
}
if (($my_action=='invisible' OR $my_action=='visible') AND isset($_GET['id']) AND api_is_allowed_to_edit(false,true)) {
	$message=approve_post($_GET['id'],$_GET['action']); // note: this has to be cleaned first
}
if ($my_action=='move' AND isset($_GET['post'])) {
	$message=move_post_form();
}

/*
-----------------------------------------------------------
	Display the action messages
-----------------------------------------------------------
*/
$my_message = isset($message) ? $message : '';
if ($my_message) {
	Display :: display_confirmation_message(get_lang($my_message));
}

if ($my_message<>'PostDeletedSpecial') {
	// in this case the first and only post of the thread is removed
	// this increases the number of times the thread has been viewed
	increase_thread_view($_GET['thread']);
	/*
	-----------------------------------------------------------
		Action Links
	-----------------------------------------------------------
	*/
	if ($origin=='learnpath') {
		echo '<div style="height:15px">&nbsp;</div>';
	}
	echo '<div class="actions">';
	echo '<span style="float:right;">'.search_link().'</span>';
	if ($origin != 'learnpath') {
		echo '<a href="index.php?gradebook='.$gradebook.'">'.Display::return_icon('back.png',get_lang('BackToForumOverview')).' '.get_lang('BackToForumOverview').'</a>';
		echo '<a href="viewforum.php?&forum='.Security::remove_XSS($_GET['forum']).'&amp;gidReq='.$_SESSION['toolgroup'].'">'.Display::return_icon('forum.gif',get_lang('BackToForum')).' '.get_lang('BackToForum').'</a>';
	}
	// the reply to thread link should only appear when the forum_category is not locked AND the forum is not locked AND the thread is not locked.
	// if one of the three levels is locked then the link should not be displayed
	if ($current_forum_category['locked']==0 AND $current_forum['locked']==0 AND $current_thread['locked']==0 OR api_is_allowed_to_edit(false,true)) {
		// The link should only appear when the user is logged in or when anonymous posts are allowed.
		if ($_user['user_id'] OR ($current_forum['allow_anonymous']==1 AND !$_user['user_id'])) {
			//reply link
			if (!api_is_anonymous() && api_is_allowed_to_session_edit(false,true)) {
				echo '<a href="reply.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;action=replythread&origin='.$origin.'">'.Display::return_icon('forumthread_new.gif',get_lang('ReplyToThread')).get_lang('ReplyToThread').'</a>';
			}
			//new thread link
			if ((api_is_allowed_to_edit(false,true) && !(api_is_course_coach() && $current_forum['session_id']!=$_SESSION['id_session'])) OR ($current_forum['allow_new_threads']==1 AND isset($_user['user_id'])) OR ($current_forum['allow_new_threads']==1 AND !isset($_user['user_id']) AND $current_forum['allow_anonymous']==1)) {
				if ($current_forum['locked'] <> 1 AND $current_forum['locked'] <> 1) {
					echo '&nbsp;&nbsp;';
/*					if ( isset($_GET['gradebook']) && $_GET['gradebook']!=""){
						$info_thread=get_thread_information($_GET['thread']);
						echo '<a href="newthread.php?'.api_get_cidreq().'&forum='.$info_thread['forum_id'].'&origin='.$origin.'&gradebook='.Security::remove_XSS($_GET['gradebook']).'">'.Display::return_icon('forumthread_new.gif', get_lang('NewTopic')).' '.get_lang('NewTopic').'</a>';
					} else {
						echo '<a href="newthread.php?'.api_get_cidreq().'&forum='.Security::remove_XSS($_GET['forum']).'&origin='.$origin.'">'.Display::return_icon('forumthread_new.gif', get_lang('NewTopic')).' '.get_lang('NewTopic').'</a>';
					} */
				} else {
					echo get_lang('ForumLocked');
				}
			}
		}
	}

	// the different views of the thread
	if ($origin != 'learnpath') {
		$my_url = '<a href="viewthread.php?'.api_get_cidreq().'&amp;forum='.Security::remove_XSS($_GET['forum']).'&amp;origin='.$origin.'&amp;gradebook='.$gradebook.'&amp;thread='.Security::remove_XSS($_GET['thread']).'&amp;search='.Security::remove_XSS(urlencode($my_search));
		echo $my_url.'&amp;view=flat&origin='.$origin.'&amp;gradebook='.$gradebook.'">'.Display::return_icon('forum_listview.gif',get_lang('FlatView')).get_lang('FlatView').'</a>';
		echo $my_url.'&amp;view=threaded&origin='.$origin.'&amp;gradebook='.$gradebook.'">'.Display::return_icon('forum_threadedview.gif',get_lang('ThreadedView')).get_lang('ThreadedView').'</a>';
		echo $my_url.'&amp;view=nested&origin='.$origin.'&amp;gradebook='.$gradebook.'">'.Display::return_icon('forum_nestedview.gif',get_lang('NestedView')).get_lang('NestedView').'</a>';
	}
	$my_url = null;

	echo '</div>&nbsp;';


	/*
	-----------------------------------------------------------
		Display Forum Category and the Forum information
	-----------------------------------------------------------
	*/

	if (!isset($_SESSION['view']))	{
		$viewmode=$current_forum['default_view'];
	} else {
		$viewmode=$_SESSION['view'];
	}

	$viewmode_whitelist=array('flat', 'threaded', 'nested');
	if (isset($_GET['view']) and in_array($_GET['view'],$viewmode_whitelist)) {
		$viewmode=$_GET['view'];
		$_SESSION['view']=$viewmode;
	}
	if(empty($viewmode)) {
		$viewmode = 'flat';
	}

	/*
	-----------------------------------------------------------
		Display Forum Category and the Forum information
	-----------------------------------------------------------
	*/
	// we are getting all the information about the current forum and forum category.
	// note pcool: I tried to use only one sql statement (and function) for this
	// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table
	echo "<table class=\"data_table\" width='100%'>\n";

	// the thread
	echo "\t<tr>\n\t\t<th style=\"padding-left:5px;\" align=\"left\" colspan=\"6\">";
	echo '<span class="forum_title">'.prepare4display($current_thread['thread_title']).'</span><br />';

	if ($origin!='learnpath') {
		echo '<span class="forum_low_description">'.prepare4display($current_forum_category['cat_title']).' - ';
	}

	echo prepare4display($current_forum['forum_title']).'<br />';
	echo "</th>\n";
	echo "\t</tr>\n";
	echo '<span>'.prepare4display(isset($current_thread['thread_comment'])?$current_thread['thread_comment']:'').'</span>';
	echo "</table>";

	switch ($viewmode) {
		case 'flat':
			include_once('viewthread_flat.inc.php');
			break;
		case 'threaded':
			include_once('viewthread_threaded.inc.php');
			break;
		case 'nested':
			include_once('viewthread_nested.inc.php');
			break;
		default:
			include_once('viewthread_flat.inc.php');
			break;
	}
} // if ($message<>'PostDeletedSpecial') // in this case the first and only post of the thread is removed

/*
==============================================================================
		FOOTER
==============================================================================
*/
if ($origin!='learnpath') {
	Display :: display_footer();
}