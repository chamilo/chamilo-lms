<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_GROUP;
// Notice for unauthorized people.
api_protect_course_script(true);

$sessionId = api_get_session_id();

if (!api_is_allowed_to_edit(false, true) ||
    !(isset($_GET['id']) ||
    isset($_POST['id']) ||
    isset($_GET['action']) ||
    isset($_POST['action']))
) {
    api_not_allowed(true);
}

if (!empty($sessionId)) {
    api_not_allowed(true);
}

/**
 * Function to check the given max number of members per group.
 */
function check_max_number_of_members($value)
{
    $max_member_no_limit = $value['max_member_no_limit'];
    if (GroupManager::MEMBER_PER_GROUP_NO_LIMIT == $max_member_no_limit) {
        return true;
    }
    $max_member = $value['max_member'];

    return is_numeric($max_member);
}

/**
 * Function to check the number of groups per user.
 *
 * @param $value
 *
 * @return bool
 */
function check_groups_per_user($value)
{
    $groups_per_user = (int) $value['groups_per_user'];
    if (isset($_POST['id']) &&
        GroupManager::GROUP_PER_MEMBER_NO_LIMIT != $groups_per_user &&
        GroupManager::get_current_max_groups_per_user($_POST['id']) > $groups_per_user) {
        return false;
    }

    return true;
}

if (isset($_GET['id'])) {
    $category = GroupManager::get_category($_GET['id']);
    $nameTools = get_lang('Edit group category').': '.$category['title'];
} else {
    $nameTools = get_lang('Add category');
    // Default values for new category
    $category = [
        'groups_per_user' => 1,
        'doc_state' => GroupManager::TOOL_PRIVATE,
        'work_state' => GroupManager::TOOL_PRIVATE,
        'wiki_state' => GroupManager::TOOL_PRIVATE,
        'chat_state' => GroupManager::TOOL_PRIVATE,
        'calendar_state' => GroupManager::TOOL_PRIVATE,
        'announcements_state' => GroupManager::TOOL_PRIVATE,
        'forum_state' => GroupManager::TOOL_PRIVATE,
        'max_student' => 0,
        'document_access' => 0,
    ];
}

$htmlHeadXtra[] = '<script>
$(function() {
    $("#max_member").on("focus", function() {
        $("#max_member_selected").attr("checked", true);
    });
});
 </script>';

$interbreadcrumb[] = ['url' => 'group.php?'.api_get_cidreq(), 'name' => get_lang('Groups')];
$course_id = api_get_course_int_id();

// Build the form
if (isset($_GET['id'])) {
    // Update settings of existing category
    $action = 'update_settings';
    $form = new FormValidator(
        'group_category',
        'post',
        api_get_self().'?id='.$category['iid'].'&'.api_get_cidreq()
    );
    $form->addElement('hidden', 'id');
} else {
    // Create a new category
    $action = 'add_category';
    $form = new FormValidator('group_category');
}

$form->addElement('header', $nameTools);
$form->addElement('html', '<div class="row"><div class="col-md-6">');
$form->addText('title', get_lang('Title'));

// Groups per user
$possible_values = [];
for ($i = 1; $i <= 10; $i++) {
    $possible_values[$i] = $i;
}
$possible_values[GroupManager::GROUP_PER_MEMBER_NO_LIMIT] = get_lang('All');

$group = [
    $form->createElement('select', 'groups_per_user', null, $possible_values, ['id' => 'groups_per_user']),
    $form->createElement('static', null, null, get_lang(' groups')),
];
$form->addGroup($group, 'limit_group', get_lang('A user can be member of maximum'), null, false);
$form->addRule('limit_group', get_lang('The maximum number of groups per user you submitted is invalid. There are now users who are subscribed in more groups than the number you propose.'), 'callback', 'check_groups_per_user');

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
$form->addElement('textarea', 'description', get_lang('Description'), ['rows' => 6]);
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
        get_lang('Limit'),
        get_lang('No limitation'),
        GroupManager::MEMBER_PER_GROUP_NO_LIMIT
    ),
    $form->createElement(
        'radio',
        'max_member_no_limit',
        null,
        get_lang('Maximum number of members'),
        1,
        ['id' => 'max_member_selected']
    ),
    $form->createElement('text', 'max_member', null, ['class' => 'span1', 'id' => 'max_member']),
    $form->createElement('static', null, null, ' '.get_lang('seats (optional)')),
];
$form->addGroup($group, 'max_member_group', get_lang('Limit'), null, false);
$form->addRule('max_member_group', get_lang('Please enter a valid number for the maximum number of members.'), 'callback', 'check_max_number_of_members');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
// Self registration
$group = [
    $form->createElement(
        'checkbox',
        'self_reg_allowed',
        get_lang('Registration'),
        get_lang('Learners are allowed to self-register in groups'),
    ),
    $form->createElement(
        'checkbox',
        'self_unreg_allowed',
        null,
        get_lang('Learners are allowed to unregister themselves from groups')
    ),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('user.png', get_lang('Registration')).' '.get_lang('Registration'),
    null,
    false
);
$form->addElement('html', '</div>');
$form->addElement('hidden', 'action');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', get_lang('Default settings for new groups'));
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Documents settings.
$group = [
    $form->createElement('radio', 'doc_state', get_lang('Documents'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'doc_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'doc_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('folder.png', get_lang('Documents')).' '.get_lang('Documents'),
    null,
    false
);

$allowDocumentGroupAccess = api_get_configuration_value('group_category_document_access');
if ($allowDocumentGroupAccess) {
    $form->addElement('html', '</div>');
    $form->addElement('html', '<div class="col-md-6">');
    $group = [
        $form->createElement(
            'radio',
            'document_access',
            null,
            get_lang('Share mode'),
            GroupManager::DOCUMENT_MODE_SHARE
        ),
        $form->createElement(
            'radio',
            'document_access',
            get_lang('Documents'),
            get_lang('Collaboration mode'),
            GroupManager::DOCUMENT_MODE_COLLABORATION
        ),
        $form->createElement(
            'radio',
            'document_access',
            null,
            get_lang('Read only mode'),
            GroupManager::DOCUMENT_MODE_READ_ONLY
        ),
    ];
    $form->addGroup(
        $group,
        '',
        Display::return_icon(
            'folder.png',
            get_lang('DocumentsAccess')
        ).'<span>'.get_lang('DocumentsAccess').'</span>',
        null,
        false
    );
    $form->addElement('html', '</div>');
}

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', '');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Work settings.
$group = [
    $form->createElement('radio', 'work_state', get_lang('Assignments'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'work_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'work_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('work.png', get_lang('Assignments'), [], ICON_SIZE_SMALL).' '.get_lang('Assignments'),
    '',
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Calendar settings.
$group = [
    $form->createElement('radio', 'calendar_state', get_lang('Agenda'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'calendar_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'calendar_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('agenda.png', get_lang('Agenda')).' '.get_lang('Agenda'),
    null,
    false
);
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', '');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Announcements settings.
$group = [
    $form->createElement('radio', 'announcements_state', get_lang('Announcements'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'announcements_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'announcements_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
    $form->createElement('radio', 'announcements_state', null, get_lang('PrivateBetweenUsers'), GroupManager::TOOL_PRIVATE_BETWEEN_USERS),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('announce.png', get_lang('Announcements')).' '.get_lang('Announcements'),
    null,
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Forum settings.
$group = [
    $form->createElement('radio', 'forum_state', get_lang('Group Forum'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'forum_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'forum_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('forum.png', get_lang('Group Forum')).' '.get_lang('Group Forum'),
    null,
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', '');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Wiki settings.
$group = [
    $form->createElement('radio', 'wiki_state', get_lang('Wiki'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'wiki_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'wiki_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('wiki.png', get_lang('Wiki')).' '.get_lang('Wiki'),
    null,
    false
);
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Chat settings.
$group = [
    $form->createElement('radio', 'chat_state', get_lang('Chat'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'chat_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'chat_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('chat.png', get_lang('Chat')).' '.get_lang('Chat'),
    null,
    false
);

$form->addElement('html', '</div>');

// Submit
if (isset($_GET['id'])) {
    $form->addButtonUpdate(get_lang('Edit'), 'submit');
} else {
    $form->addButtonSave(get_lang('Add'), 'submit');
}

$currentUrl = api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq();

// If form validates -> save data
if ($form->validate()) {
    $values = $form->exportValues();
    if (GroupManager::MEMBER_PER_GROUP_NO_LIMIT == $values['max_member_no_limit']) {
        $max_member = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
    } else {
        $max_member = $values['max_member'];
    }

    $self_reg_allowed = $values['self_reg_allowed'] ?? 0;
    $self_unreg_allowed = $values['self_unreg_allowed'] ?? 0;

    switch ($values['action']) {
        case 'update_settings':
            GroupManager::update_category(
                $_GET['id'],
                $values['title'],
                $values['description'],
                $values['doc_state'],
                $values['work_state'],
                $values['calendar_state'],
                $values['announcements_state'],
                $values['forum_state'],
                $values['wiki_state'],
                $values['chat_state'],
                $self_reg_allowed,
                $self_unreg_allowed,
                $max_member,
                $values['groups_per_user'],
                $values['document_access'] ?? 0
            );
            Display::addFlash(Display::return_message(get_lang('Group settings have been modified')));
            header('Location: '.$currentUrl.'&category='.$values['id']);
            exit;
        case 'add_category':
            GroupManager::create_category(
                $values['title'],
                $values['description'],
                $values['doc_state'],
                $values['work_state'],
                $values['calendar_state'],
                $values['announcements_state'],
                $values['forum_state'],
                $values['wiki_state'],
                $values['chat_state'],
                $self_reg_allowed,
                $self_unreg_allowed,
                $max_member,
                $values['groups_per_user'],
                $values['document_access'] ?? 0
            );
            Display::addFlash(Display::return_message(get_lang('Category created')));
            header('Location: '.$currentUrl);
            exit;

            break;
    }
}

// Else display the form
Display::display_header($nameTools, 'Group');

$actions = '<a href="group.php">'.
    Display::return_icon('back.png', get_lang('Back to Groups list'), '', ICON_SIZE_MEDIUM).'</a>';
echo Display::toolbarAction('toolbar', [$actions]);

$defaults = $category;
$defaults['action'] = $action;
if (GroupManager::MEMBER_PER_GROUP_NO_LIMIT == $defaults['max_student']) {
    $defaults['max_member_no_limit'] = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
} else {
    $defaults['max_member_no_limit'] = 1;
    $defaults['max_member'] = $defaults['max_student'];
}
$form->setDefaults($defaults);
$form->display();

Display::display_footer();
