<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.group
 */

// Name of the language file that needs to be included
$language_file = 'group';

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

if (!api_is_allowed_to_edit(false,true) || !(isset ($_GET['id']) || isset ($_POST['id']) || isset ($_GET['action']) || isset ($_POST['action']))) {
	api_not_allowed();
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
 * Function to check the number of groups per user
 */
function check_groups_per_user($value) {
	$groups_per_user = $value['groups_per_user'];
	if (isset ($_POST['id']) && intval($groups_per_user) != GroupManager::GROUP_PER_MEMBER_NO_LIMIT && GroupManager::get_current_max_groups_per_user($_POST['id']) > intval($groups_per_user)) {
		return false;
	}
	return true;
}

if (api_get_setting('allow_group_categories') == 'true') {
	if (isset ($_GET['id'])) {
		$category = GroupManager :: get_category($_GET['id']);
		$nameTools = get_lang('EditGroupCategory').': '.$category['title'];
	} else {
		$nameTools = get_lang('AddCategory');
		// Default values for new category
		$category = array(
            'groups_per_user' => 1, 
            'doc_state' => GroupManager::TOOL_PRIVATE, 
            'work_state' => GroupManager::TOOL_PRIVATE, 
            'wiki_state' => GroupManager::TOOL_PRIVATE , 
            'chat_state' => GroupManager::TOOL_PRIVATE, 
            'calendar_state' => GroupManager::TOOL_PRIVATE, 
            'announcements_state'=> GroupManager::TOOL_PRIVATE, 
            'forum_state' => GroupManager::TOOL_PRIVATE, 
            'max_student' => 0);
	}
} else {
	$category = GroupManager :: get_category($_GET['id']);
	$nameTools = get_lang('PropModify');
}

$interbreadcrumb[] = array ('url' => 'group.php', 'name' => get_lang('Groups'));

$course_id = api_get_course_int_id();

// Build the form
if (isset ($_GET['id'])) {
	// Update settings of existing category
	$action = 'update_settings';
	$form = new FormValidator('group_category', 'post', '?id='.$category['id']);
	$form->addElement('header', $nameTools);
	$form->addElement('hidden', 'id');
} else {
    // Checks if the field was created in the table Category. It creates it if is neccesary
    $table_category = Database :: get_course_table(TABLE_GROUP_CATEGORY);
	if (!Database::query("SELECT wiki_state FROM $table_category WHERE c_id = $course_id")) {
    	Database::query("ALTER TABLE $table_category ADD wiki_state tinyint(3) UNSIGNED NOT NULL default '1' WHERE c_id = $course_id");
    }
	// Create a new category
	$action = 'add_category';
	$form = new FormValidator('group_category');
}

$form->addElement('html', '<div class="sectiontitle" >'.$nameTools);
$form->addElement('html', '</div>');

// If categories allowed, show title & description field
if (api_get_setting('allow_group_categories') == 'true') {
	$form->add_textfield('title', get_lang('Title'));
	$form->addElement('textarea', 'description', get_lang('Description'), array('cols' => 50, 'rows' => 6));
} else {
	$form->addElement('hidden', 'title');
	$form->addElement('hidden', 'description');
}

// Action
$form->addElement('hidden', 'action');

// Groups per user
$group = array ();
$group[] = & $form->createElement('static', null, null, get_lang('QtyOfUserCanSubscribe_PartBeforeNumber'));
$possible_values = array ();
for ($i = 1; $i <= 10; $i ++) {
	$possible_values[$i] = $i;
}
$possible_values[GroupManager::GROUP_PER_MEMBER_NO_LIMIT] = get_lang('All');
$group[] = & $form->createElement('select', 'groups_per_user', null, $possible_values);
$group[] = & $form->createElement('static', null, null, get_lang('QtyOfUserCanSubscribe_PartAfterNumber'));
$form->addGroup($group, 'limit_group', get_lang('GroupLimit'), ' ', false);
$form->addRule('limit_group', get_lang('MaxGroupsPerUserInvalid'), 'callback', 'check_groups_per_user');
// Default settings for new groups
//$form->addElement('static', null, '<b>'.get_lang('DefaultSettingsForNewGroups').'</b>');

$form->addElement('html', '<br /><br /><div class="sectiontitle" >'.get_lang('DefaultSettingsForNewGroups'));
$form->addElement('html', '</div>');

// Members per group
$form->addElement('radio', 'max_member_no_limit', get_lang('GroupLimit'), get_lang('NoLimit'), GroupManager::MEMBER_PER_GROUP_NO_LIMIT);
$group = array ();
$group[] = & $form->createElement('radio', 'max_member_no_limit', null, get_lang('MaximumOfParticipants'), 1);
$group[] = & $form->createElement('text', 'max_member', null, array ('size' => 2));
$group[] = & $form->createElement('static', null, null, get_lang('GroupPlacesThis'));
$form->addGroup($group, 'max_member_group', null, '', false);
$form->addRule('max_member_group', get_lang('InvalidMaxNumberOfMembers'), 'callback', 'check_max_number_of_members');

// Self registration
$form->addElement('checkbox', 'self_reg_allowed', get_lang('GroupSelfRegistration'), get_lang('GroupAllowStudentRegistration'), 1);
$form->addElement('checkbox', 'self_unreg_allowed', null, get_lang('GroupAllowStudentUnregistration'), 1);

// Documents settings
$form->addElement('radio', 'doc_state', get_lang('GroupDocument'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'doc_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$form->addElement('radio', 'doc_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);

// Work settings
$form->addElement('radio', 'work_state', get_lang('GroupWork'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'work_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$form->addElement('radio', 'work_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);

// Calendar settings
$form->addElement('radio', 'calendar_state', get_lang('GroupCalendar'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'calendar_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$form->addElement('radio', 'calendar_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);

// Announcements settings
$form->addElement('radio', 'announcements_state', get_lang('GroupAnnouncements'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'announcements_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$form->addElement('radio', 'announcements_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);

// Forum settings
$form->addElement('radio', 'forum_state', get_lang('GroupForum'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'forum_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$form->addElement('radio', 'forum_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);

// Wiki Settings
$form->addElement('radio', 'wiki_state', get_lang('GroupWiki'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'wiki_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$form->addElement('radio', 'wiki_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);

// Chat Settings
$form->addElement('radio', 'chat_state', get_lang('Chat'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE);
$form->addElement('radio', 'chat_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC);
$form->addElement('radio', 'chat_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE);

// Submit
$form->addElement('style_submit_button', 'submit', get_lang('PropModify'), 'class="save"');

// If form validates -> save data
if ($form->validate()) {
	$values = $form->exportValues();
	if ($values['max_member_no_limit'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
		$max_member = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
	} else {
		$max_member = $values['max_member'];
	}
	$self_reg_allowed = isset($values['self_reg_allowed']) ? $values['self_reg_allowed'] : 0;
	$self_unreg_allowed = isset($values['self_unreg_allowed']) ? $values['self_unreg_allowed'] : 0;
	switch ($values['action']) {
		case 'update_settings':
			GroupManager :: update_category($values['id'], $values['title'], $values['description'], $values['doc_state'], $values['work_state'], $values['calendar_state'], $values['announcements_state'], $values['forum_state'], $values['wiki_state'], $values['chat_state'], $self_reg_allowed, $self_unreg_allowed, $max_member, $values['groups_per_user']);
			$msg = urlencode(get_lang('GroupPropertiesModified'));
			header('Location: group.php?action=show_msg&msg='.$msg.'&category='.$values['id']);
			break;
		case 'add_category':
			GroupManager :: create_category($values['title'], $values['description'], $values['doc_state'], $values['work_state'], $values['calendar_state'], $values['announcements_state'], $values['forum_state'], $values['wiki_state'], $values['chat_state'], $self_reg_allowed, $self_unreg_allowed, $max_member, $values['groups_per_user']);
			$msg = urlencode(get_lang('CategoryCreated'));
			header('Location: group.php?action=show_msg&msg='.$msg);
			break;
	}
}

// Else display the form
Display :: display_header($nameTools, 'Group');

// actions bar
echo '<div class="actions">';
echo '<a href="group.php">'.Display::return_icon('back.png', get_lang('BackToGroupList'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$defaults = $category;
$defaults['action'] = $action;
if ($defaults['max_student'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
	$defaults['max_member_no_limit'] = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
} else {
	$defaults['max_member_no_limit'] = 1;
	$defaults['max_member'] = $defaults['max_student'];
}
$form->setDefaults($defaults);
$form->display();

Display :: display_footer();
