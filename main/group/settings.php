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

require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool  = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$group_id = api_get_group_id();
$current_group = GroupManager::get_group_properties($group_id);

$nameTools = get_lang('EditGroup');
$interbreadcrumb[] = array('url' => 'group.php', 'name' => get_lang('Groups'));
$interbreadcrumb[] = array('url' => 'group_space.php?'.api_get_cidReq(), 'name' => $current_group['name']);
$is_group_member = GroupManager::is_tutor_of_group(api_get_user_id(), $group_id);

if (!api_is_allowed_to_edit(false, true) && !$is_group_member) {
    api_not_allowed(true);
}

// Build form
$form = new FormValidator('group_edit', 'post', api_get_self().'?'.api_get_cidreq());
$form->addElement('hidden', 'action');
$form->addElement('html', '<div class="span12">');
$form->addElement('header', $nameTools);
$form->addElement('html', '</div>');
$form->addElement('html', '<div class="span6">');

// Group name
$form->addElement('text', 'name', get_lang('GroupName'));

if (api_get_setting('allow_group_categories') == 'true') {
    $groupCategories = GroupManager::get_categories();
    $categoryList = array();
    //$categoryList[] = null;
    foreach ($groupCategories as $category) {
        $categoryList[$category['id']] = $category['title'];
    }
    $form->addElement('select', 'category_id', get_lang('Category'), $categoryList);
}

// Members per group
$group = array(
    $form->createElement('radio', 'max_member_no_limit', get_lang('GroupLimit'), get_lang('NoLimit'), GroupManager::MEMBER_PER_GROUP_NO_LIMIT),
    $form->createElement('radio', 'max_member_no_limit', null, get_lang('MaximumOfParticipants'), 1, array('id' => 'max_member_selected')),
    $form->createElement('text', 'max_member', null, array('class' => 'span1', 'id' => 'max_member')),
    $form->createElement('static', null, null, ' '.get_lang('GroupPlacesThis'))
);
$form->addGroup($group, 'max_member_group', get_lang('GroupLimit'), '', false);
$form->addRule('max_member_group', get_lang('InvalidMaxNumberOfMembers'), 'callback', 'check_max_number_of_members');

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="span6">');
// Description
$form->addElement('textarea', 'description', get_lang('Description'), array ('class' => 'span6', 'rows' => 6));
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="span12">');
$form->addElement('header', get_lang('DefaultSettingsForNewGroups'));
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="span6">');

// Self registration
$group = array(
    $form->createElement('checkbox', 'self_registration_allowed', get_lang('GroupSelfRegistration'), get_lang('GroupAllowStudentRegistration'), 1),
    $form->createElement('checkbox', 'self_unregistration_allowed', null, get_lang('GroupAllowStudentUnregistration'), 1)
);
$form->addGroup($group, '', Display::return_icon('user.png', get_lang('GroupSelfRegistration'), array(), ICON_SIZE_SMALL).' '.get_lang('GroupSelfRegistration'), '', false);

// Documents settings
$group = array(
    $form->createElement('radio', 'doc_state', get_lang('GroupDocument'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'doc_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'doc_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE)
);
$form->addGroup($group, '', Display::return_icon('folder.png', get_lang('GroupDocument'), array(), ICON_SIZE_SMALL).' '.get_lang('GroupDocument'), '', false);

// Work settings
$group = array(
    $form->createElement('radio', 'work_state', get_lang('GroupWork'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'work_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'work_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE)
);
$form->addGroup($group, '', Display::return_icon('work.png', get_lang('GroupWork'), array(), ICON_SIZE_SMALL).' '.get_lang('GroupWork'), '', false);

// Calendar settings
$group = array(
    $form->createElement('radio', 'calendar_state', get_lang('GroupCalendar'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'calendar_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'calendar_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE)
);
$form->addGroup($group, '', Display::return_icon('agenda.png', get_lang('GroupCalendar'), array(), ICON_SIZE_SMALL).' '.get_lang('GroupCalendar'), '', false);

$form->addElement('html', '</div>');
$form->addElement('html', '<div class="span6">');

// Announcements settings
$group = array(
    $form->createElement('radio', 'announcements_state', get_lang('GroupAnnouncements'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'announcements_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'announcements_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE)
);
$form->addGroup($group, '', Display::return_icon('announce.png', get_lang('GroupAnnouncements'), array(), ICON_SIZE_SMALL).' '.get_lang('GroupAnnouncements'), '', false);

// Forum settings
$group = array(
    $form->createElement('radio', 'forum_state', get_lang('GroupForum'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'forum_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'forum_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE)
);
$form->addGroup($group, '', Display::return_icon('forum.png', get_lang('GroupForum'), array(), ICON_SIZE_SMALL).' '.get_lang('GroupForum'), '', false);

// Wiki settings
$group = array(
    $form->createElement('radio', 'wiki_state', get_lang('GroupWiki'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'wiki_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'wiki_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE)
);
$form->addGroup($group, '', Display::return_icon('wiki.png', get_lang('GroupWiki'), array(), ICON_SIZE_SMALL).' '.get_lang('GroupWiki'), '', false);

// Chat settings
$group = array(
    $form->createElement('radio', 'chat_state', get_lang('Chat'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'chat_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'chat_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE)
);
$form->addGroup($group, '', Display::return_icon('chat.png', get_lang('Chat'), array(), ICON_SIZE_SMALL).' '.get_lang('Chat'), '', false);

$form->addElement('html', '</div>');
$form->addElement('html', '<div class="span12">');
// Submit button
$form->addElement('style_submit_button', 'submit', get_lang('SaveSettings'), 'class="save"');
$form->addElement('html', '</div>');

if ($form->validate()) {
    $values = $form->exportValues();
    if ($values['max_member_no_limit'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
        $max_member = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
    } else {
        $max_member = $values['max_member'];
    }
    $self_registration_allowed   = isset($values['self_registration_allowed']) ? 1 : 0;
    $self_unregistration_allowed = isset($values['self_unregistration_allowed']) ? 1 : 0;
    $categoryId = isset($values['category_id']) ? $values['category_id'] : null;

    GroupManager::set_group_properties(
        $current_group['id'],
        $values['name'],
        $values['description'],
        $max_member,
        $values['doc_state'],
        $values['work_state'],
        $values['calendar_state'],
        $values['announcements_state'],
        $values['forum_state'],
        $values['wiki_state'],
        $values['chat_state'],
        $self_registration_allowed,
        $self_unregistration_allowed,
        $categoryId
    );
    if (isset($_POST['group_members']) && count($_POST['group_members']) > $max_member && $max_member != GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
        header('Location: group.php?'.api_get_cidreq(true, false).'&action=warning_message&msg='.get_lang('GroupTooMuchMembers'));
    } else {
        header('Location: group.php?'.api_get_cidreq(true, false).'&action=success_message&msg='.get_lang('GroupSettingsModified').'&category='.$cat['id']);
    }
    exit;
}

$defaults = $current_group;
$category = GroupManager::get_category_from_group($group_id);
if (!empty($category)) {
    $defaults['category_id'] = $category['id'];
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;
if ($defaults['maximum_number_of_students'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
    $defaults['max_member_no_limit'] = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
} else {
    $defaults['max_member_no_limit'] = 1;
    $defaults['max_member'] = $defaults['maximum_number_of_students'];
}

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display :: display_header($nameTools, 'Group');

//@todo fix this
if (isset($_GET['show_message_warning'])) {
    echo Display::display_warning_message($_GET['show_message_warning']);
}

if (isset($_GET['show_message_sucess'])) {
    echo Display::display_normal_message($_GET['show_message_sucess']);
}

$form->setDefaults($defaults);
echo GroupManager::getSettingBar('settings');
echo '<div class="row">';
$form->display();
echo '</div>';

Display :: display_footer();
