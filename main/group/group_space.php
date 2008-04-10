<?php //$Id: group_space.php 14826 2008-04-10 08:10:19Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
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

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
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
include_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php');
require_once (api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php');
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
	$forums_of_groups = get_forums_of_group($current_group['id']);
	if (is_array($forums_of_groups))
	{
		foreach ($forums_of_groups as $key => $value)
		{
			if($value['forum_group_public_private'] == 'public' || ($user_subscribe_to_current_group && $value['forum_group_public_private'] == 'private') || $user_is_tutor || api_is_allowed_to_edit())
			{
				$tools.= Display::return_icon('forum.gif') . ' <a href="../forum/viewforum.php?forum='.$value['forum_id'].'">'.$value['forum_title'].'</a><br />';
			}
		}
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
	$forums_of_groups = get_forums_of_group($current_group['id']);
	if (is_array($forums_of_groups))
	{
		foreach ($forums_of_groups as $key => $value)
		{
			if($value['forum_group_public_private'] == 'public' )
			{
				$tools.= Display::return_icon('forum.gif') . ' <a href="../forum/viewforum.php?forum='.$value['forum_id'].'">'.$value['forum_title'].'</a><br />';
			}
		}
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
echo '<b>'.get_lang("GroupMembers").':</b>';

$table = new SortableTable('group_users', 'get_number_of_group_users', 'get_group_user_data',2);
$parameters = array('cidReq' => $_GET['cidReq'], 'origin'=> $_GET['origin'], 'gidReq' => $_GET['gidReq']);
$table->set_additional_parameters($parameters);
$table->set_header(0, '');
$table->set_header(1, get_lang('LastName'));
$table->set_header(2, get_lang('FirstName'));
$table->set_header(3, get_lang('Email'));
$table->set_column_filter(3, 'email_filter');
$table->set_column_filter(0, 'user_icon_filter');
$table->display();

/**
 * Get the number of subscribed users to the group
 *
 * @return integer
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_number_of_group_users()
{
	global $current_group;
	
	// Database table definition
	$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
	
	// query
	$sql = "SELECT count(id) AS number_of_users
				FROM ".$table_group_user."
				WHERE group_id='".Database::escape_string($current_group['id'])."'";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	$return = Database::fetch_array($result,'ASSOC');
	return $return['number_of_users']; 
}

/**
 * Get the details of the users in a group
 *
 * @param integer $from starting row
 * @param integer $number_of_items number of items to be displayed
 * @param integer $column sorting colum
 * @param integer $direction sorting direction
 * @return array
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function get_group_user_data($from, $number_of_items, $column, $direction)
{
	global $current_group;
	
	// Database table definition
	$table_group_user 	= Database :: get_course_table(TABLE_GROUP_USER);
	$table_user 		= Database :: get_main_table(TABLE_MAIN_USER);
	
	// query
	$sql = "SELECT 
				user.user_id 	AS col0,
				user.lastname 	AS col1,
				user.firstname 	AS col2,
				user.email		AS col3
				FROM ".$table_user." user, ".$table_group_user." group_rel_user 
				WHERE group_rel_user.user_id = user.user_id 
				AND group_rel_user.group_id = '".Database::escape_string($current_group['id'])."'";
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$return = array ();
	$result = api_sql_query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_row($result))
	{
		$return[] = $row;
	}
	return $return; 
}

/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function email_filter($email)
{
	return Display :: encrypted_mailto_link($email, $email);
}

/**
 * Display a user icon that links to the user page
 *
 * @param integer $user_id the id of the user
 * @return html code
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version April 2008
 */
function user_icon_filter($user_id)
{
	global $origin;
	return "<a href='../user/userInfo.php?origin=".$origin."&amp;uInfo=".$user_id."'><img src='../img/members.gif' >";
}

// footer
if ($origin != 'learnpath')
{
	Display::display_footer();
}
?>