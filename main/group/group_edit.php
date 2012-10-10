<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays an area where teachers can edit the group properties and member list.
 *	Groups are also often called "teams" in the Dokeos code.
 *
 *	@author various contributors
 *	@author Roan Embrechts (VUB), partial code cleanup, initial virtual course support
 *	@package chamilo.group
 *	@todo course admin functionality to create groups based on who is in which course (or class).
 */

/*	INIT SECTION */

// Name of the language file that needs to be included
$language_file = 'group';

require '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

/*	Libraries & settings */

$group_id = api_get_group_id();

/*	Constants & variables */
$current_group = GroupManager :: get_group_properties($group_id);

/*	Header */
$nameTools = get_lang('EditGroup');
$interbreadcrumb[] = array ('url' => 'group.php', 'name' => get_lang('Groups'));

$is_group_member = GroupManager :: is_tutor_of_group(api_get_user_id(), $group_id);

if (!api_is_allowed_to_edit(false,true) && !$is_group_member) {
	api_not_allowed(true);
}

/*	FUNCTIONS */

/**
 *  List all users registered to the course
 */
function search_members_keyword($firstname, $lastname, $username, $official_code, $keyword) {
	if (api_strripos($firstname, $keyword) !== false || api_strripos($lastname, $keyword) !== false || api_strripos($username, $keyword) !== false || api_strripos($official_code, $keyword) !== false) {		
		return true;
	} else {
		return false;
	}	
}


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
	if ($max_member_no_limit == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
		return true;
	}
	$max_member = $value['max_member'];
	return is_numeric($max_member);
}

/**
 * Function to check if the number of selected group members is valid
 */
function check_group_members($value) {
	if ($value['max_member_no_limit'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
		return true;
	}
	if (isset($value['max_member']) && isset($value['group_members']) && $value['max_member'] < count($value['group_members'])) {
		return array ('group_members' => get_lang('GroupTooMuchMembers'));
	}
	return true;
}

/*	MAIN CODE */

// Build form
$form = new FormValidator('group_edit', 'post', api_get_self().'?'.api_get_cidreq());

$form->addElement('header', $nameTools);
$form->addElement('hidden', 'action');
$form->addElement('hidden', 'referer');

// Group name
$form->add_textfield('name', get_lang('GroupName'));

// Description
$form->addElement('textarea', 'description', get_lang('Description'), array ('class' => 'span6', 'rows' => 6));

$complete_user_list = GroupManager :: fill_groups_list($current_group['id']);

usort($complete_user_list, 'sort_users');
$possible_users = array();
foreach ($complete_user_list as $index => $user) {
    $possible_users[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')';
}

// Group tutors
$group_tutor_list = GroupManager :: get_subscribed_tutors($current_group['id']);
$selected_users = array();
$selected_tutors = array();
foreach ($group_tutor_list as $index => $user) {    
    $selected_tutors[] = $user['user_id'];
}

$group_tutors_element = $form->addElement('advmultiselect', 'group_tutors', get_lang('GroupTutors'), $possible_users, 'style="width: 280px;"');
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

$group_tutors_element->setButtonAttributes('add', array('class' => 'btn arrowr'));
$group_tutors_element->setButtonAttributes('remove', array('class' => 'btn arrowl'));

// Group members
$group_member_list = GroupManager :: get_subscribed_users($current_group['id']);

$selected_users = array ();
foreach ($group_member_list as $index => $user) {
    $selected_users[] = $user['user_id'];
}

// possible : number_groups_left > 0 and is group member
$possible_users = array();
foreach ($complete_user_list as $index => $user) {
     if ($user['number_groups_left'] > 0 || in_array($user['user_id'], $selected_users) )  {
        $possible_users[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')';
     }
}

$group_members_element = $form->addElement('advmultiselect', 'group_members', get_lang('GroupMembers'), $possible_users, 'style="width: 280px;"');

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
</table>');

$group_members_element->setButtonAttributes('add', array('class' => 'btn arrowr'));
$group_members_element->setButtonAttributes('remove', array('class' => 'btn arrowl'));
$form->addFormRule('check_group_members');


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
$form->addElement('radio', 'max_member_no_limit', get_lang('GroupLimit'), get_lang('NoLimit'), GroupManager::MEMBER_PER_GROUP_NO_LIMIT);
$group = array();
$group[] = & $form->createElement('radio', 'max_member_no_limit', null, get_lang('MaximumOfParticipants'), 1);
$group[] = & $form->createElement('text', 'max_member', null, array('class' => 'span1'));
$group[] = & $form->createElement('static', null, null, get_lang('GroupPlacesThis'));
$form->addGroup($group, 'max_member_group', null, '', false);
$form->addRule('max_member_group', get_lang('InvalidMaxNumberOfMembers'), 'callback', 'check_max_number_of_members');

// Self registration
$group = array();
$group[] = $form->createElement('checkbox', 'self_registration_allowed', get_lang('GroupSelfRegistration'), get_lang('GroupAllowStudentRegistration'), 1);
$group[] = $form->createElement('checkbox', 'self_unregistration_allowed', null, get_lang('GroupAllowStudentUnregistration'), 1);
$form->addGroup($group, '', Display::return_icon('user.png', get_lang('GroupSelfRegistration') , array(), ICON_SIZE_SMALL).' '.get_lang('GroupSelfRegistration'), '', false);

// Documents settings
$group = array();
$group[] = $form->createElement('radio', 'doc_state', get_lang('GroupDocument'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$group[] = $form->createElement('radio', 'doc_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$group[] = $form->createElement('radio', 'doc_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);
$form->addGroup($group, '', Display::return_icon('folder.png', get_lang('GroupDocument') , array(), ICON_SIZE_SMALL).' '.get_lang('GroupDocument'), '', false);

// Work settings
$group = array();
$group[] = $form->createElement('radio', 'work_state', get_lang('GroupWork'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$group[] = $form->createElement('radio', 'work_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$group[] = $form->createElement('radio', 'work_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);
$form->addGroup($group, '', Display::return_icon('work.png', get_lang('GroupWork') , array(), ICON_SIZE_SMALL).' '.get_lang('GroupWork'), '', false);


// Calendar settings
$group = array();
$group[] = $form->createElement('radio', 'calendar_state', get_lang('GroupCalendar'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$group[] = $form->createElement('radio', 'calendar_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$group[] = $form->createElement('radio', 'calendar_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);
$form->addGroup($group, '', Display::return_icon('agenda.png', get_lang('GroupCalendar') , array(), ICON_SIZE_SMALL).' '.get_lang('GroupCalendar'), '', false);

// Announcements settings
$group = array();
$group[] = $form->createElement('radio', 'announcements_state', get_lang('GroupAnnouncements'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$group[] = $form->createElement('radio', 'announcements_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$group[] = $form->createElement('radio', 'announcements_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);
$form->addGroup($group, '', Display::return_icon('announce.png', get_lang('GroupAnnouncements') , array(), ICON_SIZE_SMALL).' '.get_lang('GroupAnnouncements'), '', false);

//Forum settings
$group = array();
$group[] = $form->createElement('radio', 'forum_state', get_lang('GroupForum'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$group[] = $form->createElement('radio', 'forum_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$group[] = $form->createElement('radio', 'forum_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);
$form->addGroup($group, '', Display::return_icon('forum.png', get_lang('GroupForum') , array(), ICON_SIZE_SMALL).' '.get_lang('GroupForum'), '', false);

// Wiki settings
$group = array();
$group[] = $form->createElement('radio', 'wiki_state', get_lang('GroupWiki'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$group[] = $form->createElement('radio', 'wiki_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$group[] = $form->createElement('radio', 'wiki_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);
$form->addGroup($group, '', Display::return_icon('wiki.png', get_lang('GroupWiki') , array(), ICON_SIZE_SMALL).' '.get_lang('GroupWiki'), '', false);

// Chat settings
$group = array();
$group[] = $form->createElement('radio', 'chat_state', get_lang('Chat'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$group[] = $form->createElement('radio', 'chat_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$group[] = $form->createElement('radio', 'chat_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);
$form->addGroup($group, '', Display::return_icon('chat.png', get_lang('Chat') , array(), ICON_SIZE_SMALL).' '.get_lang('Chat'), '', false);

// submit button
$form->addElement('style_submit_button', 'submit', get_lang('PropModify'), 'class="save"');

if ($form->validate()) {
	$values = $form->exportValues();
	if ($values['max_member_no_limit'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
		$max_member = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
	} else {
		$max_member = $values['max_member'];
	}
	$self_registration_allowed   = isset($values['self_registration_allowed']) ? 1 : 0;
	$self_unregistration_allowed = isset($values['self_unregistration_allowed']) ? 1 : 0;
	GroupManager :: set_group_properties($current_group['id'], strip_tags($values['name']), strip_tags($values['description']), $max_member, $values['doc_state'], $values['work_state'], $values['calendar_state'], $values['announcements_state'], $values['forum_state'], $values['wiki_state'], $values['chat_state'], $self_registration_allowed, $self_unregistration_allowed);

	// Storing the tutors (we first remove all the tutors and then add only those who were selected)
	GroupManager :: unsubscribe_all_tutors($current_group['id']);
	if (isset ($_POST['group_tutors']) && count($_POST['group_tutors']) > 0) {
		GroupManager :: subscribe_tutors($values['group_tutors'], $current_group['id']);
	}

	// Storing the users (we first remove all users and then add only those who were selected)
	GroupManager :: unsubscribe_all_users($current_group['id']);
	if (isset ($_POST['group_members']) && count($_POST['group_members']) > 0) {
		GroupManager :: subscribe_users($values['group_members'], $current_group['id']);
	}

	// Returning to the group area (note: this is inconsistent with the rest of chamilo)
    //var_dump(count($_POST['group_members']), $max_member, GroupManager::MEMBER_PER_GROUP_NO_LIMIT);
	$cat = GroupManager :: get_category_from_group($current_group['id']);      
    if (isset($_POST['group_members']) && count($_POST['group_members']) > $max_member && $max_member != GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
        header('Location:group_edit.php?show_message='.get_lang('GroupTooMuchMembers'));
    } else {
        header('Location: '.$values['referer'].'?action=show_msg&msg='.get_lang('GroupSettingsModified').'&category='.$cat['id']);
    }
}

$defaults = $current_group;
$defaults['group_members'] = $selected_users;
$defaults['group_tutors'] = $selected_tutors;
$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;
if ($defaults['maximum_number_of_students'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
	$defaults['max_member_no_limit'] = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
} else {
	$defaults['max_member_no_limit'] = 1;
	$defaults['max_member'] = $defaults['maximum_number_of_students'];
}
$referer = parse_url($_SERVER['HTTP_REFERER']);
$referer = basename($referer['path']);
if ($referer != 'group_space.php' && $referer != 'group.php') {
	$referer = 'group.php';
}

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
	$keyword_name = Security::remove_XSS($_GET['keyword']);
	echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display :: display_header($nameTools, 'Group');
?>
<div class="actions">
<a href="group_space.php"><?php echo Display::return_icon('back.png', get_lang('ReturnTo').' '.get_lang('GroupSpace'),'',ICON_SIZE_MEDIUM); ?></a>
</div>
<?php

if (isset($_GET['show_message'])) {
	echo Display::display_error_message($_GET['show_message']);
}
$defaults['referer'] = $referer;
$form->setDefaults($defaults);
$form->display();

/*	FOOTER */
Display :: display_footer();