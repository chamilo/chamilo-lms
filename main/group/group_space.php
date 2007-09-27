<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors
	Copyright (c) Bart Mollet

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* This script shows the group space for one specific group, possibly displaying
* a list of users in the group, subscribe or unsubscribe option, tutors...
*
* @package dokeos.group
* @todo	Display error message if no group ID specified
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = "group";
include ('../inc/global.inc.php');
/*
-----------------------------------------------------------
	Libraries & config files
-----------------------------------------------------------
*/
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$current_group = GroupManager :: get_group_properties($_SESSION['_gid']);
if(!is_array($current_group) ) {
//display some error message
}

$nameTools = get_lang("GroupSpace");
$interbreadcrumb[] = array ("url" => "group.php", "name" => get_lang("GroupManagement"));

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
Display::display_header($nameTools,"Group");


/*
-----------------------------------------------------------
	Actions and Action links
-----------------------------------------------------------
*/
/*
 * User wants to register in this group
 */
if ($_GET['selfReg'] && GroupManager :: is_self_registration_allowed($_SESSION['_user']['user_id'], $current_group['id']))
{
	GroupManager :: subscribe_users($_SESSION['_user']['user_id'], $current_group['id']);
	Display :: display_normal_message(get_lang('GroupNowMember'));
}

/*
 * User wants to unregister from this group
 */
if ($_GET['selfUnReg'] && GroupManager :: is_self_unregistration_allowed($_SESSION['_user']['user_id'], $current_group['id']))
{
	GroupManager :: unsubscribe_users($_SESSION['_user']['user_id'], $current_group['id']);
	Display::display_normal_message(get_lang('StudentDeletesHimself'));
}
/*
 * Edit the group
 */
if (api_is_allowed_to_edit() or GroupManager :: is_tutor($_user['user_id']))
{
	echo "<a href=\"group_edit.php?origin=$origin\">".get_lang("EditGroup")."</a><br/><br/>";
}

/*
 * Register to group
 */
if (GroupManager :: is_self_registration_allowed($_SESSION['_user']['user_id'], $current_group['id']))
{
	echo '<p align="right"><a href="'.api_get_self().'?selfReg=1&amp;group_id='.$current_group['id'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.get_lang("RegIntoGroup").'</a></p>';
}

/*
 * Unregister from group
 */
if (GroupManager :: is_self_unregistration_allowed($_SESSION['_user']['user_id'], $current_group['id']))
{
	echo '<p align="right"><a href="'.api_get_self().'?selfUnReg=1" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.get_lang("StudentUnsubscribe").'</a></p>';
}

if( isset($_GET['action']))
{
	switch( $_GET['action'])
	{
		case 'show_msg':
			Display::display_normal_message($_GET['msg']);
			break;
	}
}





/*
-----------------------------------------------------------
	Main Display Area
-----------------------------------------------------------
*/
$course_code = $_course['sysCode'];
$is_course_member = CourseManager :: is_user_subscribed_in_real_or_linked_course($_SESSION['_user']['user_id'], $course_code);

/*
 * Group title and comment
 */
api_display_tool_title($nameTools.' '.stripslashes($current_group['name']));
if (!empty($current_group['description']))
{
	echo '<blockquote>'.stripslashes($current_group['description']).'</blockquote>';
}



/*
 * Group Tools
 */
// If the user is subscribed to the group or the user is a tutor of the group then
if (api_is_allowed_to_edit() OR GroupManager :: is_user_in_group($_SESSION['_user']['user_id'], $current_group['id']))
{
	$tools = '';
	// Edited by Patrick Cool, 12 feb 2004: hide the forum link if there is no forum for this group (deleted through forum_admin.php)
	if (!is_null($current_group['forum_id']) && $current_group['forum_state'] != TOOL_NOT_AVAILABLE)
	{
		$tools .= "<a href=\"../forum/viewforum.php?".api_get_cidreq()."&amp;origin=$origin&amp;gidReq=".$current_group['id']."&amp;forum=".$current_group['forum_id']."\">".Display::return_icon('forum.gif')."&nbsp;".get_lang("Forums")."</a></div>";
	}
	if( $current_group['doc_state'] != TOOL_NOT_AVAILABLE )
	{
		// link to the documents area of this group
		$tools .= "<div style='margin-bottom: 5px;'><a href=\"../document/document.php?".api_get_cidreq()."&amp;gidReq=".$current_group['id']."\">".Display::return_icon('folder_document.gif')."&nbsp;".get_lang("GroupDocument")."</a></div>";
	}
	if ( $current_group['calendar_state'] != TOOL_NOT_AVAILABLE)
	{
		//link to a group-specific part of agenda
		$tools .= "<div style='margin-bottom: 5px;'><a href=\"../calendar/agenda.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."&amp;group=".$current_group['id']."&amp;acces=0\">".Display::return_icon('agenda.gif')."&nbsp;".get_lang("GroupCalendar")."</a></div>";
	}
	if ( $current_group['work_state'] != TOOL_NOT_AVAILABLE)
	{
		//link to the works area of this group
		$tools .= "<div style='margin-bottom: 5px;'><a href=\"../work/work.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."\">".Display::return_icon('works.gif')."&nbsp;".get_lang("GroupWork")."</a></div>";
	}
	if ( $current_group['announcements_state'] != TOOL_NOT_AVAILABLE)
	{
		//link to a group-specific part of announcements
		$tools .= "<div style='margin-bottom: 5px;'><a href=\"../announcements/announcements.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."\">".Display::return_icon('valves.gif')."&nbsp;".get_lang("GroupAnnouncements")."</a></div>";
	}

	echo '<b>'.get_lang("Tools").':</b>';
	if (!empty($tools))
	{
		echo '<blockquote>'.$tools.'</blockquote>';
	}

}
else
{
	$tools = '';
	if ($current_group['forum_state'] == TOOL_PUBLIC && !is_null($current_group['forum_id']))
	{
		$tools .= "<a href=\"../forum/viewforum.php?".api_get_cidreq()."&amp;origin=$origin&amp;gidReq=".$current_group['id']."&amp;forum=".$current_group['forum_id']."\">".Display::return_icon('forum.gif')."&nbsp;".get_lang("Forums")."</a><br/>";
	}
	if( $current_group['doc_state'] == TOOL_PUBLIC )
	{
		// link to the documents area of this group
		$tools .= "<a href=\"../document/document.php?".api_get_cidreq()."&amp;gidReq=".$current_group['id']."&amp;origin=$origin\">".Display::return_icon('folder_document.gif')."&nbsp;".get_lang("GroupDocument")."</a><br/>";
	}
	if ( $current_group['calendar_state'] == TOOL_PUBLIC )
	{
		//link to a group-specific part of agenda
		$tools .= "<a href=\"../calendar/agenda.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."&amp;group=".$current_group['id']."\">".Display::return_icon('agenda.gif')."&nbsp;".get_lang("GroupCalendar")."</a><br/>";
	}
	if ( $current_group['work_state'] == TOOL_PUBLIC )
	{
		//link to the works area of this group
		$tools .= "<a href=\"../work/work.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."\">".Display::return_icon('works.gif')."&nbsp;".get_lang("GroupWork")."</a><br/>";
	}
	if ( $current_group['announcements_state'] == TOOL_PUBLIC)
	{
		//link to a group-specific part of announcements
		$tools .= "<a href=\"../announcements/announcements.php?".api_get_cidreq()."&amp;toolgroup=".$current_group['id']."&amp;group=".$current_group['id']."\">".Display::return_icon('valves.gif')."&nbsp;".get_lang("GroupAnnouncements")."</a><br/>";
	}
	echo '<b>'.get_lang("Tools").':</b>';
	if (!empty($tools))
	{
		echo '<blockquote>'.$tools.'</blockquote>';
	}
}

/*
 * list all the tutors of the current group
 */
$tutors = GroupManager::get_subscribed_tutors($current_group['id']);
if (count($tutors) == 0)
{
	$tutor_info = get_lang("GroupNoneMasc");
}
else
{
	foreach($tutors as $index => $tutor)
	{
		$tutor_info .= "<div style='margin-bottom: 5px;'><a href='../user/userInfo.php?origin=".$origin."&amp;uInfo=".$tutor['user_id']."'><img src='../img/coachs.gif' align='absbottom'>&nbsp;".$tutor['firstname']." ".$tutor['lastname']."</a></div>";
	}
}
echo '<b>'.get_lang("GroupTutors").':</b>';
if (!empty($tutor_info))
{
	echo '<blockquote>'.$tutor_info.'</blockquote>';
}




/*
 * list all the members of the current group
 */
$tutors = GroupManager::get_subscribed_users($current_group['id']);
if (count($tutors) == 0)
{
	$member_info = get_lang("GroupNoneMasc");
}
else
{
	foreach($tutors as $index => $member)
	{
		$member_info .= "<div style='margin-bottom: 5px;'><a href='../user/userInfo.php?origin=".$origin."&amp;uInfo=".$member['user_id']."'><img src='../img/members.gif' align='absbottom'>&nbsp;".$member['firstname']." ".$member['lastname']."</a></div>";
	}
}
echo '<b>'.get_lang("GroupMembers").':</b><blockquote>'.$member_info.'</blockquote>';
/*
==============================================================================
		FOOTER
==============================================================================
*/
if ($origin != 'learnpath')
{
	Display::display_footer();
}
?>