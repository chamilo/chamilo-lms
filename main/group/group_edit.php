<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors
	Copyright (c) Bart Mollet, Hogeschool Gent
	
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
*	This script displays an area where teachers can edit the group properties and member list.
*	Groups are also often called "teams" in the Dokeos code.
*
*	@author various contributors
*	@author Roan Embrechts (VUB), partial code cleanup, initial virtual course support
	@package dokeos.group
*	@todo course admin functionality to create groups based on who is in which course (or class).
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
$this_section = SECTION_COURSES;

/*
-----------------------------------------------------------
	Libraries & settings
-----------------------------------------------------------
*/
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
/*
-----------------------------------------------------------
	Constants & variables
-----------------------------------------------------------
*/
$current_group = GroupManager :: get_group_properties($_SESSION['_gid']);
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
$nameTools = get_lang('EditGroup');
$interbreadcrumb[] = array ("url" => "group.php", "name" => get_lang('Groups'));

if (!api_is_allowed_to_edit(false,true)) {
	api_not_allowed(true);
}
/*
============================================================================== 
		FUNCTIONS
============================================================================== 
*/

/**
 * function to sort users after getting the list in the db. Necessary because there are 2 or 3 queries. Called by usort()
 */

function sort_users($user_a, $user_b) {
	if (api_sort_by_first_name()) {
		$cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
		if ($cmp !== 0) {
			return $cmp;
		} else {
			$cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
			if ($cmp !== 0) {
				return $cmp;
			} else {
				return api_strcmp($user_a['username'], $user_b['username']);
			}
		}
	} else {
		$cmp = api_strcmp($user_a['lastname'], $user_b['lastname']);
		if ($cmp !== 0) {
			return $cmp;
		} else {
			$cmp = api_strcmp($user_a['firstname'], $user_b['firstname']);
			if ($cmp !== 0) {
				return $cmp;
			} else {
				return api_strcmp($user_a['username'], $user_b['username']);
			}
		}
	}
}

/**
 * Function to check the given max number of members per group 
 */
function check_max_number_of_members($value) {
	$max_member_no_limit = $value['max_member_no_limit'];
	if ($max_member_no_limit == MEMBER_PER_GROUP_NO_LIMIT) {
		return true;
	}
	$max_member = $value['max_member'];
	return is_numeric($max_member);
}
/**
 * Function to check if the number of selected group members is valid
 */
function check_group_members($value) {
	if ($value['max_member_no_limit'] == MEMBER_PER_GROUP_NO_LIMIT) {
		return true;
	}
	if (isset($value['max_member']) && isset($value['group_members']) && $value['max_member'] < count($value['group_members'])) {
		return array ('group_members' => get_lang('GroupTooMuchMembers'));
	}
	return true;
}
/*
============================================================================== 
		MAIN CODE
============================================================================== 
*/

// Build form
$form = new FormValidator('group_edit');
$form->addElement('header', '', $nameTools);
$form->addElement('hidden', 'action');
$form->addElement('hidden', 'referer');
// Group name
$form->add_textfield('name', get_lang('GroupName'));

// Description
$form->addElement('textarea', 'description', get_lang('GroupDescription'), array ('cols' => 50, 'rows' => 6));

// Tutors: this has been replaced with the new tutors code
//$tutors = GroupManager :: get_all_tutors();
//$possible_tutors[0] = get_lang('GroupNoTutor');
//foreach ($tutors as $index => $tutor)
//{
//	$possible_tutors[$tutor['user_id']] = api_get_person_name($tutor['lastname'], $tutor['firstname']);
//}
//$group = array ();
//$group[] = & $form->createElement('select', 'tutor_id', null, $possible_tutors);
//$group[] = & $form->createElement('static', null, null, '&nbsp;&nbsp;<a href="../user/user.php">'.get_lang('AddTutors').'</a>');
//$form->addGroup($group, 'tutor_group', get_lang('GroupTutor'), '', false);

// Members per group
$form->addElement('radio', 'max_member_no_limit', get_lang('GroupLimit'), get_lang('NoLimit'), MEMBER_PER_GROUP_NO_LIMIT);
$group = array ();
$group[] = & $form->createElement('radio', 'max_member_no_limit', null, get_lang('Max'), 1);
$group[] = & $form->createElement('text', 'max_member', null, array ('size' => 2));
$group[] = & $form->createElement('static', null, null, get_lang('GroupPlacesThis'));
$form->addGroup($group, 'max_member_group', null, '', false);
$form->addRule('max_member_group', get_lang('InvalidMaxNumberOfMembers'), 'callback', 'check_max_number_of_members');

// Self registration
$form->addElement('checkbox', 'self_registration_allowed', get_lang('GroupSelfRegistration'), get_lang('GroupAllowStudentRegistration'), 1);
$form->addElement('checkbox', 'self_unregistration_allowed', null, get_lang('GroupAllowStudentUnregistration'), 1);

// Documents settings
$form->addElement('radio', 'doc_state', get_lang('GroupDocument'), get_lang('NotAvailable'), TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'doc_state', null, get_lang('Public'), TOOL_PUBLIC);
$form->addElement('radio', 'doc_state', null, get_lang('Private'), TOOL_PRIVATE);

// Work settings
$form->addElement('radio', 'work_state', get_lang('GroupWork'), get_lang('NotAvailable'), TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'work_state', null, get_lang('Public'), TOOL_PUBLIC);
$form->addElement('radio', 'work_state', null, get_lang('Private'), TOOL_PRIVATE);

// Calendar settings
$form->addElement('radio', 'calendar_state', get_lang('GroupCalendar'), get_lang('NotAvailable'), TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'calendar_state', null, get_lang('Public'), TOOL_PUBLIC);
$form->addElement('radio', 'calendar_state', null, get_lang('Private'), TOOL_PRIVATE);

// Announcements settings
$form->addElement('radio', 'announcements_state', get_lang('GroupAnnouncements'), get_lang('NotAvailable'), TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'announcements_state', null, get_lang('Public'), TOOL_PUBLIC);
$form->addElement('radio', 'announcements_state', null, get_lang('Private'), TOOL_PRIVATE);

//Forum settings
$form->addElement('radio', 'forum_state', get_lang('GroupForum'), get_lang('NotAvailable'), TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'forum_state', null, get_lang('Public'), TOOL_PUBLIC);
$form->addElement('radio', 'forum_state', null, get_lang('Private'), TOOL_PRIVATE);

// Wiki settings
$form->addElement('radio', 'wiki_state', get_lang('GroupWiki'), get_lang('NotAvailable'), TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'wiki_state', null, get_lang('Public'), TOOL_PUBLIC);
$form->addElement('radio', 'wiki_state', null, get_lang('Private'), TOOL_PRIVATE);

// getting all the users
if (isset($_SESSION['id_session'])) {
	$complete_user_list = CourseManager :: get_user_list_from_course_code($_course['id'],true,$_SESSION['id_session']);
	$complete_user_list2 = CourseManager :: get_coach_list_from_course_code($_course['id'],$_SESSION['id_session']);
	$complete_user_list = array_merge($complete_user_list,$complete_user_list2);
} else {
	$complete_user_list = CourseManager :: get_user_list_from_course_code($_course['id']);
}

usort($complete_user_list, 'sort_users');


$possible_users = array ();
foreach ($complete_user_list as $index => $user) {
	$possible_users[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')';
}

//print_r($complete_user_list2);
// Group tutors
$group_tutor_list = GroupManager :: get_subscribed_tutors($current_group['id']);
$selected_users = array ();
$selected_tutors = array();
foreach ($group_tutor_list as $index => $user) {
	//$possible_users[$user['user_id']] = api_get_person_name($user['firstname'], .$user['lastname']);
	$selected_tutors[] = $user['user_id'];
}

$group_tutors_element = $form->addElement('advmultiselect', 'group_tutors', get_lang('GroupTutors'), $possible_users, 'style="width: 225px;"');
$group_tutors_element->setElementTemplate('
{javascript}
<table{class}>
<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->
<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->
<tr>
  <td valign="top">{unselected}</td>
  <td align="center">{add}<br /><br />{remove}</td>
  <td valign="top">{selected}</td>
</tr>
</table>
');

// Group members
$group_member_list = GroupManager :: get_subscribed_users($current_group['id']);
$selected_users = array ();
foreach ($group_member_list as $index => $user) {
	//$possible_users[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
	$selected_users[] = $user['user_id'];
}
$group_members_element = $form->addElement('advmultiselect', 'group_members', get_lang('GroupMembers'), $possible_users, 'style="width: 225px;"');

$group_members_element->setElementTemplate('
{javascript}
<table{class}>
<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->
<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->
<tr>
  <td valign="top">{unselected}</td>
  <td align="center">{add}<br /><br />{remove}</td>
  <td valign="top">{selected}</td>
</tr>
</table>
');


$form->addFormRule('check_group_members');

// submit button
$form->addElement('style_submit_button', 'submit', get_lang('PropModify'), 'class="save"');

if ($form->validate()) {
	$values = $form->exportValues();
	if ($values['max_member_no_limit'] == MEMBER_PER_GROUP_NO_LIMIT) {
		$max_member = MEMBER_PER_GROUP_NO_LIMIT;
	} else {
		$max_member = $values['max_member'];
	}
	$self_registration_allowed = isset ($values['self_registration_allowed']) ? 1 : 0;
	$self_unregistration_allowed = isset ($values['self_unregistration_allowed']) ? 1 : 0;
	GroupManager :: set_group_properties($current_group['id'], strip_tags($values['name']), strip_tags($values['description']), $max_member, $values['doc_state'], $values['work_state'], $values['calendar_state'], $values['announcements_state'], $values['forum_state'],$values['wiki_state'], $self_registration_allowed, $self_unregistration_allowed);
	
	// storing the tutors (we first remove all the tutors and then add only those who were selected)
	GroupManager :: unsubscribe_all_tutors($current_group['id']);
	if (isset ($_POST['group_tutors']) && count($_POST['group_tutors']) > 0) {
		GroupManager :: subscribe_tutors($values['group_tutors'], $current_group['id']);
	}	
	
	// storing the users (we first remove all users and then add only those who were selected)
	GroupManager :: unsubscribe_all_users($current_group['id']);
	if (isset ($_POST['group_members']) && count($_POST['group_members']) > 0) {
		GroupManager :: subscribe_users($values['group_members'], $current_group['id']);
	}
	
	// returning to the group area (note: this is inconsistent with the rest of dokeos)
	$cat = GroupManager :: get_category_from_group($current_group['id']);
	header('Location: '.$values['referer'].'?action=show_msg&msg='.get_lang('GroupSettingsModified').'&category='.$cat['id']);

}
$defaults = $current_group;
$defaults['group_members'] = $selected_users;
$defaults['group_tutors'] = $selected_tutors;
isset($_GET['action'])?$action=$_GET['action']:$action='';
$defaults['action'] = $action;
if ($defaults['maximum_number_of_students'] == MEMBER_PER_GROUP_NO_LIMIT) {
	$defaults['max_member_no_limit'] = MEMBER_PER_GROUP_NO_LIMIT;
} else {
	$defaults['max_member_no_limit'] = 1;
	$defaults['max_member'] = $defaults['maximum_number_of_students'];
}
$referer = parse_url($_SERVER['HTTP_REFERER']);
$referer = basename($referer['path']);
if ($referer != 'group_space.php' && $referer != 'group.php') {
	$referer = 'group.php';
}
if (isset($_POST['group_members'])) {
	if (count($_POST['group_members'])<=$defaults['max_member']) {
		//
	} else {
				header('Location:group_edit.php?show_message='.get_lang('GroupTooMuchMembers'));
	}
}
Display :: display_header($nameTools, "Group");
?>

<div class="actions">
<a href="group_space.php"><?php  echo Display::return_icon('back.png',get_lang('ReturnTo').' '.get_lang('GroupSpace')).get_lang('ReturnTo').' '.get_lang('GroupSpace') ?></a>
</div>

<?php

if (isset($_GET['show_message'])) {
	echo Display::display_error_message(get_lang($_GET['show_message']));
}
$defaults['referer'] = $referer;
$form->setDefaults($defaults);
$form->display();
/*
============================================================================== 
		FOOTER 
============================================================================== 
*/
Display :: display_footer();
?>
