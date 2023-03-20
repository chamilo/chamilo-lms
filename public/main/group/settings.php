<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGroup;

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

api_protect_course_script(true);

$group_id = api_get_group_id();
$current_group = GroupManager::get_group_properties($group_id);
$groupRepo = Container::getGroupRepository();
/** @var CGroup $groupEntity */
$groupEntity = $groupRepo->find($group_id);

if (null === $groupEntity) {
    api_not_allowed(true);
}

$nameTools = get_lang('Edit this group');
$interbreadcrumb[] = ['url' => 'group.php?'.api_get_cidreq(), 'name' => get_lang('Groups')];
$interbreadcrumb[] = ['url' => 'group_space.php?'.api_get_cidreq(), 'name' => $groupEntity->getName()];
$groupMember = GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity);

if (!$groupMember && !api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

// Build form
$form = new FormValidator('group_edit', 'post', api_get_self().'?'.api_get_cidreq());
$form->addElement('hidden', 'action');

$form->addHtml('<div class="row">');
$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', $nameTools);
$form->addHtml('</div>');
$form->addHtml('</div>');

$form->addHtml('<div class="row">');
$form->addElement('html', '<div class="col-md-6">');

// Group name
$form->addElement('text', 'name', get_lang('Group name'));

if ('true' === api_get_setting('allow_group_categories')) {
    $groupCategories = GroupManager::get_categories();
    $categoryList = [];
    foreach ($groupCategories as $category) {
        $categoryList[$category['iid']] = $category['title'];
    }
    $form->addSelect('category_id', get_lang('Category'), $categoryList);
} else {
    $form->addHidden('category_id', 0);
}
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
$form->addElement('textarea', 'description', get_lang('Description'));
$form->addHtml('</div>');
$form->addHtml('</div>');

$form->addHtml('<div class="row">');
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
$form->addRule(
    'max_member_group',
    get_lang('Please enter a valid number for the maximum number of members.'),
    'callback',
    'check_max_number_of_members'
);
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Self registration
$group = [
    $form->createElement(
        'checkbox',
        'self_registration_allowed',
        get_lang('Registration'),
        get_lang('Learners are allowed to self-register in groups')
    ),
    $form->createElement(
        'checkbox',
        'self_unregistration_allowed',
        null,
        get_lang('Learners are allowed to unregister themselves from groups'),
    ),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('user.png', get_lang('Registration')).
    '<span>'.get_lang('Registration').'</span>',
    null,
    false
);

$form->addElement('html', '</div>');
$form->addHtml('</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('header', get_lang('Default settings for new groups'));
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
// Documents settings
$group = [
    $form->createElement('radio', 'doc_state', get_lang('Documents'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'doc_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'doc_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('folder.png', get_lang('Documents')).'<span>'.get_lang('Documents').'</span>',
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
        get_lang('Assignments'),
        get_lang('Not available'),
        GroupManager::TOOL_NOT_AVAILABLE
    ),
    $form->createElement('radio', 'work_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'work_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-12">');
$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

$form->addGroup(
    $group,
    '',
    Display::return_icon('works.png', get_lang('Assignments')).'<span>'.get_lang('Assignments').'</span>',
    null,
    false
);

// Calendar settings
$group = [
    $form->createElement('radio', 'calendar_state', get_lang('Agenda'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'calendar_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'calendar_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');
$form->addGroup(
    $group,
    '',
    Display::return_icon('agenda.png', get_lang('Agenda')).'<span>'.get_lang('Agenda').'</span>',
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
    $form->createElement('radio', 'announcements_state', get_lang('Announcements'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'announcements_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'announcements_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
    $form->createElement('radio', 'announcements_state', null, get_lang('Private between users'), GroupManager::TOOL_PRIVATE_BETWEEN_USERS),
];

$form->addGroup(
    $group,
    '',
    Display::return_icon('announce.png', get_lang('Announcements')).'<span>'.get_lang('Announcements').'</span>',
    null,
    false
);

$form->addElement('html', '</div>');

$form->addElement('html', '<div class="col-md-6">');

// Forum settings
$group = [
    $form->createElement('radio', 'forum_state', get_lang('Group Forum'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'forum_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'forum_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('forum.png', get_lang('Group Forum')).'<span>'.get_lang('Group Forum').'</span>',
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
    $form->createElement('radio', 'wiki_state', get_lang('Wiki'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'wiki_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'wiki_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup(
    $group,
    '',
    Display::return_icon('wiki.png', get_lang('Wiki')).'<span>'.get_lang('Wiki').'</span>',
    '',
    false
);

$form->addElement('html', '</div>');
$form->addElement('html', '<div class="col-md-6">');

// Chat settings
$group = [
    $form->createElement('radio', 'chat_state', get_lang('Chat'), get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'chat_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'chat_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
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
$form->addButtonSave(get_lang('Save settings'));
$form->addElement('html', '</div>');

if ($form->validate()) {
    $values = $form->exportValues();
    $max_member = $values['max_member'];
    if (GroupManager::MEMBER_PER_GROUP_NO_LIMIT == $values['max_member_no_limit']) {
        $max_member = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
    }
    $self_registration_allowed = isset($values['self_registration_allowed']) ? 1 : 0;
    $self_unregistration_allowed = isset($values['self_unregistration_allowed']) ? 1 : 0;
    $categoryId = $values['category_id'] ?? null;

    GroupManager::set_group_properties(
        $group_id,
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
        $values['document_access'] ?? 0
    );
    if (isset($_POST['group_members']) &&
        count($_POST['group_members']) > $max_member &&
        GroupManager::MEMBER_PER_GROUP_NO_LIMIT != $max_member
    ) {
        Display::addFlash(
            Display::return_message(
                get_lang(
                    'Number proposed exceeds max. that you allowed (you can modify in the group settings). Group composition has not been modified'
                ),
                'warning'
            )
        );
        header('Location: group.php?'.api_get_cidreq(true, false).'&category='.$categoryId);
    } else {
        Display::addFlash(Display::return_message(get_lang('Group settings modified'), 'success'));
        header('Location: group.php?'.api_get_cidreq(true, false).'&category='.$categoryId);
    }
    exit;
}

$defaults = $current_group;
$category = GroupManager::get_category_from_group($current_group['iid']);
if (!empty($category)) {
    $defaults['category_id'] = $category['iid'];
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$defaults['action'] = $action;
if (GroupManager::MEMBER_PER_GROUP_NO_LIMIT == $defaults['maximum_number_of_students']) {
    $defaults['max_member_no_limit'] = GroupManager::MEMBER_PER_GROUP_NO_LIMIT;
} else {
    $defaults['max_member_no_limit'] = 1;
    $defaults['max_member'] = $defaults['maximum_number_of_students'];
}

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    echo '<br/>'.get_lang('Search results for:').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display::display_header($nameTools, 'Group');

$form->setDefaults($defaults);
echo GroupManager::getSettingBar('settings');
$form->display();

Display :: display_footer();
