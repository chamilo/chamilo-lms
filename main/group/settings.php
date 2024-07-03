<?php

/* For licensing terms, see /license.txt */

/**
 * This script displays an area where teachers can edit the group properties and member list.
 *
 * @author various contributors
 * @author Roan Embrechts (VUB), partial code cleanup, initial virtual course support
 *
 * @todo course admin functionality to create groups based on who is in which course (or class).
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;

// Notice for unauthorized people.
api_protect_course_script(true);

$group_id = api_get_group_id();
$current_group = GroupManager::get_group_properties($group_id);

if (empty($current_group)) {
    api_not_allowed(true);
}

$nameTools = get_lang('EditGroup');
$interbreadcrumb[] = ['url' => 'group.php?'.api_get_cidreq(), 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_space.php?'.api_get_cidreq(), 'name' => $current_group['name']];
$groupMember = GroupManager::is_tutor_of_group(api_get_user_id(), $current_group);

if (!$groupMember && !api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

// Build form
$form = new FormValidator('group_edit', 'post', api_get_self().'?'.api_get_cidreq());
$form->addElement('hidden', 'action');
$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', $nameTools);
$form->addElement('html', '</div>');
$form->addElement('html', '<div class="col-md-6">');

// Group name
$form->addElement('text', 'name', get_lang('GroupName'));

if (api_get_setting('allow_group_categories') == 'true') {
    $groupCategories = GroupManager::get_categories();
    $categoryList = [];
    //$categoryList[] = null;
    foreach ($groupCategories as $category) {
        $categoryList[$category['id']] = $category['title'];
    }
    $form->addElement('select', 'category_id', get_lang('Category'), $categoryList);
} else {
    $form->addHidden('category_id', 0);
}
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
$form->addElement('textarea', 'description', get_lang('Description'));

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', '');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Members per group
$group = [
    $form->createElement(
        'radio',
        'max_member_no_limit',
        get_lang('GroupLimit'),
        get_lang('NoLimit'),
        GroupManager::MEMBER_PER_GROUP_NO_LIMIT
    ),
    $form->createElement(
        'radio',
        'max_member_no_limit',
        null,
        get_lang('MaximumOfParticipants'),
        1,
        ['id' => 'max_member_selected']
    ),
    $form->createElement('text', 'max_member', null, ['class' => 'span1', 'id' => 'max_member']),
    $form->createElement('static', null, null, ' '.get_lang('GroupPlacesThis')),
];
$form->addGroup($group, 'max_member_group', get_lang('GroupLimit'), null, false);
$form->addRule('max_member_group', get_lang('InvalidMaxNumberOfMembers'), 'callback', 'check_max_number_of_members');

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Self registration
$group = [
    $form->createElement(
        'checkbox',
        'self_registration_allowed',
        get_lang('GroupSelfRegistration'),
        get_lang('GroupAllowStudentRegistration'),
        1
    ),
    $form->createElement(
        'checkbox',
        'self_unregistration_allowed',
        null,
        get_lang('GroupAllowStudentUnregistration'),
        1
    ),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('user.png', get_lang('GroupSelfRegistration')).
    '<span>'.get_lang('GroupSelfRegistration').'</span>',
    null,
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', get_lang('DefaultSettingsForNewGroups'));
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
// Documents settings
$group = [
    $form->createElement('radio', 'doc_state', get_lang('GroupDocument'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'doc_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'doc_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('folder.png', get_lang('GroupDocument')).'<span>'.get_lang('GroupDocument').'</span>',
    null,
    false
);

$allowDocumentGroupAccess = api_get_configuration_value('group_document_access');
if ($allowDocumentGroupAccess) {
    $form->addElement('html', '</div>');
    $form->addElement('html', '<div class="col-md-6">');
    $group = [
        $form->createElement(
            'radio',
            'document_access',
            null,
            get_lang('DocumentGroupShareMode'),
            GroupManager::DOCUMENT_MODE_SHARE
        ),
        $form->createElement(
            'radio',
            'document_access',
            get_lang('GroupDocument'),
            get_lang('DocumentGroupCollaborationMode'),
            GroupManager::DOCUMENT_MODE_COLLABORATION
        ),
        $form->createElement(
            'radio',
            'document_access',
            null,
            get_lang('DocumentGroupReadOnlyMode'),
            GroupManager::DOCUMENT_MODE_READ_ONLY
        ),
    ];
    $form->addGroup(
        $group,
        '',
        Display::return_icon(
            'folder.png',
            get_lang('GroupDocumentAccess')
        ).'<span>'.get_lang('GroupDocumentAccess').'</span>',
        null,
        false
    );
    $form->addElement('html', '</div>');

    $form->addElement('html', '<div class="col-md-12">');
    $form->addElement('header', '');
    $form->addElement('html', '</div>');

    $form->addElement('html', '<div class="col-md-6">');
}

// Work settings
$group = [
    $form->createElement(
        'radio',
        'work_state',
        get_lang('GroupWork'),
        get_lang('NotAvailable'),
        GroupManager::TOOL_NOT_AVAILABLE
    ),
    $form->createElement('radio', 'work_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'work_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE),
];
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

$form->addGroup(
    $group,
    '',
    Display::return_icon('works.png', get_lang('GroupWork')).'<span>'.get_lang('GroupWork').'</span>',
    null,
    false
);

// Calendar settings
$group = [
    $form->createElement('radio', 'calendar_state', get_lang('GroupCalendar'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'calendar_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'calendar_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE),
];

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
$form->addGroup(
    $group,
    '',
    Display::return_icon('agenda.png', get_lang('GroupCalendar')).'<span>'.get_lang('GroupCalendar').'</span>',
    null,
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', '');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Announcements settings
$group = [
    $form->createElement('radio', 'announcements_state', get_lang('GroupAnnouncements'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'announcements_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'announcements_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE),
    $form->createElement('radio', 'announcements_state', null, get_lang('PrivateBetweenUsers'), GroupManager::TOOL_PRIVATE_BETWEEN_USERS),
];

$form->addGroup(
    $group,
    '',
    Display::return_icon('announce.png', get_lang('GroupAnnouncements')).'<span>'.get_lang('GroupAnnouncements').'</span>',
    null,
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Forum settings
$group = [
    $form->createElement('radio', 'forum_state', get_lang('GroupForum'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'forum_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'forum_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('forum.png', get_lang('GroupForum')).'<span>'.get_lang('GroupForum').'</span>',
    null,
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', '');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Wiki settings
$group = [
    $form->createElement('radio', 'wiki_state', get_lang('GroupWiki'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'wiki_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'wiki_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('wiki.png', get_lang('GroupWiki')).'<span>'.get_lang('GroupWiki').'</span>',
    '',
    false
);

$form->addElement('html', '</div>');
$form->addElement('html', '<div class="col-md-6">');

// Chat settings
$group = [
    $form->createElement('radio', 'chat_state', get_lang('Chat'), get_lang('NotAvailable'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'chat_state', null, get_lang('Public'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'chat_state', null, get_lang('Private'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('chat.png', get_lang('Chat')).'<span>'.get_lang('Chat').'</span>',
    null,
    false
);

$form->addElement('html', '</div>');
$form->addElement('html', '<div class="col-md-12">');
// Submit button
$form->addButtonSave(get_lang('SaveSettings'));
$form->addElement('html', '</div>');

if ($form->validate()) {
    $values = $form->exportValues();
    if ($values['max_member_no_limit'] == GroupManager::MEMBER_PER_GROUP_NO_LIMIT) {
        $max_member = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
    } else {
        $max_member = $values['max_member'];
    }
    $self_registration_allowed = isset($values['self_registration_allowed']) ? 1 : 0;
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
        $categoryId,
        isset($values['document_access']) ? $values['document_access'] : 0
    );
    if (isset($_POST['group_members']) &&
        count($_POST['group_members']) > $max_member &&
        $max_member != GroupManager::MEMBER_PER_GROUP_NO_LIMIT
    ) {
        Display::addFlash(Display::return_message(get_lang('GroupTooMuchMembers'), 'warning'));
        header('Location: group.php?'.api_get_cidreq(true, false).'&category='.$categoryId);
    } else {
        Display::addFlash(Display::return_message(get_lang('GroupSettingsModified'), 'success'));
        header('Location: group.php?'.api_get_cidreq(true, false).'&category='.$categoryId);
    }
    exit;
}

$defaults = $current_group;
$category = GroupManager::get_category_from_group($current_group['iid']);
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

Display::display_header($nameTools, 'Group');

$form->setDefaults($defaults);
echo GroupManager::getSettingBar('settings');
echo '<div class="row">';
$form->display();
echo '</div>';

Display::display_footer();
