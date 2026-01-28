<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CGroup;

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
$interbreadcrumb[] = ['url' => 'group_space.php?'.api_get_cidreq(), 'name' => $groupEntity->getTitle()];

$groupMember = GroupManager::isTutorOfGroup(api_get_user_id(), $groupEntity);
if (!$groupMember && !api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

// Build form
$form = new FormValidator('group_edit', 'post', api_get_self().'?'.api_get_cidreq());
$form->addElement('hidden', 'action');

if (method_exists($form, 'updateAttributes')) {
    $form->updateAttributes(['class' => 'space-y-8']);
}

// Top container + title + group title + tabs
$form->addHtml('<div class="mx-auto wd-full px-4 sm:px-6 lg:px-8">');
$form->addHtml('<div class="mb-6">');
$form->addHtml('<h1 class="text-2xl font-semibold text-gray-900">'.Security::remove_XSS($nameTools).'</h1>');
$form->addHtml('<p class="mt-1 text-sm text-gray-600">'.Security::remove_XSS($groupEntity->getTitle()).'</p>');
$form->addHtml('</div>');
$form->addHtml(GroupManager::renderGroupTabs('settings'));

/**
 * Section: Basic group information
 */
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-white p-6 shadow-sm">');
$form->addHtml('<div class="grid grid-cols-1 gap-6 md:grid-cols-2">');

// Group name
$form->addHtml('<div>');
$form->addElement('text', 'name', get_lang('Group name'), [
    'autocomplete' => 'off',
]);
$form->addHtml('</div>');

// Category
if ('true' === api_get_setting('allow_group_categories')) {
    $groupCategories = GroupManager::get_categories();
    $categoryList = [];
    foreach ($groupCategories as $category) {
        $categoryList[$category['iid']] = $category['title'];
    }

    $form->addHtml('<div>');
    $form->addSelect('category_id', get_lang('Category'), $categoryList);
    $form->addHtml('</div>');
} else {
    $form->addHidden('category_id', 0);
    $form->addHtml('<div></div>');
}

// Description (full width)
$form->addHtml('<div class="md:col-span-2">');
$form->addElement('textarea', 'description', get_lang('Description'));
$form->addHtml('</div>');

$form->addHtml('</div>');
$form->addHtml('</div>');

/**
 * Section: Limit + Registration
 */
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-white p-6 shadow-sm">');
$form->addHtml('<div class="grid grid-cols-1 gap-6 md:grid-cols-2">');

// Limit
$form->addHtml('<div>');
$form->addHtml('<h2 class="text-sm font-semibold text-gray-900 mb-3">'.get_lang('Limit').'</h2>');

$limitGroup = [
    $form->createElement(
        'radio',
        'max_member_no_limit',
        null,
        get_lang('No limitation'),
        GroupManager::MEMBER_PER_GROUP_NO_LIMIT,
    ),
    $form->createElement(
        'radio',
        'max_member_no_limit',
        null,
        get_lang('Maximum number of members'),
        1,
        ['id' => 'max_member_selected']
    ),
    $form->createElement('text', 'max_member', null, [
        'id' => 'max_member',
        'inputmode' => 'numeric',
        'autocomplete' => 'off',
    ]),
    $form->createElement('static', null, null, ' <span class="text-sm text-gray-600">'.get_lang('seats (optional)').'</span>'),
];

$form->addGroup($limitGroup, 'max_member_group', null, '<br>', false);
$form->addRule(
    'max_member_group',
    get_lang('Please enter a valid number for the maximum number of members.'),
    'callback',
    'check_max_number_of_members'
);

$form->addHtml('</div>');

// Registration
$form->addHtml('<div>');
$form->addHtml(
    '<h2 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::MEMBER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Registration')).
    '<span>'.get_lang('Registration').'</span>'.
    '</h2>'
);

$registrationGroup = [
    $form->createElement(
        'checkbox',
        'self_registration_allowed',
        null,
        get_lang('Learners are allowed to self-register in groups'),
    ),
    $form->createElement(
        'checkbox',
        'self_unregistration_allowed',
        null,
        get_lang('Learners are allowed to unregister themselves from groups'),
    ),
];

$form->addGroup($registrationGroup, '', null, '<br>', false);

$form->addHtml('</div>');

$form->addHtml('</div>');
$form->addHtml('</div>');

/**
 * Section: Default settings for new groups
 */
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-white p-6 shadow-sm">');
$form->addHtml('<h2 class="text-base font-semibold text-gray-900 mb-6">'.get_lang('Default settings for new groups').'</h2>');
$form->addHtml('<div class="grid grid-cols-1 gap-6 md:grid-cols-2">');

// Documents tool visibility
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
$form->addHtml(
    '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::DOCUMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Documents')).
    '<span>'.get_lang('Documents').'</span>'.
    '</div>'
);

$docGroup = [
    $form->createElement('radio', 'doc_state', null, get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'doc_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'doc_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup($docGroup, '', null, '<br>', false);
$form->addHtml('</div>');

// Document access (optional feature)
$allowDocumentGroupAccess = ('true' === api_get_setting('document.group_document_access'));
if ($allowDocumentGroupAccess) {
    $form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
    $form->addHtml(
        '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
        Display::getMdiIcon(ToolIcon::DOCUMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Document access')).
        '<span>'.get_lang('Document access').'</span>'.
        '</div>'
    );

    $docAccessGroup = [
        $form->createElement('radio', 'document_access', null, get_lang('Share mode'), GroupManager::DOCUMENT_MODE_SHARE),
        $form->createElement('radio', 'document_access', null, get_lang('Collaboration mode'), GroupManager::DOCUMENT_MODE_COLLABORATION),
        $form->createElement('radio', 'document_access', null, get_lang('Read only mode'), GroupManager::DOCUMENT_MODE_READ_ONLY),
    ];
    $form->addGroup($docAccessGroup, '', null, '<br>', false);
    $form->addHtml('</div>');
}

// Assignments tool visibility
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
$form->addHtml(
    '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::ASSIGNMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Assignments')).
    '<span>'.get_lang('Assignments').'</span>'.
    '</div>'
);

$workGroup = [
    $form->createElement('radio', 'work_state', null, get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'work_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'work_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup($workGroup, '', null, '<br>', false);
$form->addHtml('</div>');

// Agenda tool visibility
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
$form->addHtml(
    '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::AGENDA, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Agenda')).
    '<span>'.get_lang('Agenda').'</span>'.
    '</div>'
);

$agendaGroup = [
    $form->createElement('radio', 'calendar_state', null, get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'calendar_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'calendar_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup($agendaGroup, '', null, '<br>', false);
$form->addHtml('</div>');

// Announcements tool visibility
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
$form->addHtml(
    '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::ANNOUNCEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Announcements')).
    '<span>'.get_lang('Announcements').'</span>'.
    '</div>'
);

$announcementsGroup = [
    $form->createElement('radio', 'announcements_state', null, get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'announcements_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'announcements_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
    $form->createElement('radio', 'announcements_state', null, get_lang('Private between users'), GroupManager::TOOL_PRIVATE_BETWEEN_USERS),
];
$form->addGroup($announcementsGroup, '', null, '<br>', false);
$form->addHtml('</div>');

// Forum tool visibility
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
$form->addHtml(
    '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::FORUM, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Group Forum')).
    '<span>'.get_lang('Group Forum').'</span>'.
    '</div>'
);

$forumGroup = [
    $form->createElement('radio', 'forum_state', null, get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'forum_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'forum_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup($forumGroup, '', null, '<br>', false);
$form->addHtml('</div>');

// Wiki tool visibility
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
$form->addHtml(
    '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::WIKI, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Wiki')).
    '<span>'.get_lang('Wiki').'</span>'.
    '</div>'
);

$wikiGroup = [
    $form->createElement('radio', 'wiki_state', null, get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'wiki_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'wiki_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup($wikiGroup, '', null, '<br>', false);
$form->addHtml('</div>');

// Chat tool visibility
$form->addHtml('<div class="rounded-lg border border-gray-50 bg-gray-20 p-4">');
$form->addHtml(
    '<div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">'.
    Display::getMdiIcon(ToolIcon::CHAT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Chat')).
    '<span>'.get_lang('Chat').'</span>'.
    '</div>'
);

$chatGroup = [
    $form->createElement('radio', 'chat_state', null, get_lang('Not available'), GroupManager::TOOL_NOT_AVAILABLE),
    $form->createElement('radio', 'chat_state', null, get_lang('Public access (access authorized to any member of the course)'), GroupManager::TOOL_PUBLIC),
    $form->createElement('radio', 'chat_state', null, get_lang('Private access (access authorized to group members only)'), GroupManager::TOOL_PRIVATE),
];
$form->addGroup($chatGroup, '', null, '<br>', false);
$form->addHtml('</div>');

$form->addHtml('</div>');

// Submit
$form->addHtml('<div class="mt-8 flex justify-end">');
$form->addButtonSave(get_lang('Save settings'));
$form->addHtml('</div>');

$form->addHtml('</div>');
$form->addHtml('</div>'); // container

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

$searchAlertHtml = '';
if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
    $keyword_name = Security::remove_XSS($_GET['keyword']);
    $searchAlertHtml = '<div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 mt-4">'.
        '<div class="rounded-md border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">'.
        get_lang('Search results for:').' <span class="font-medium italic">'.$keyword_name.'</span>'.
        '</div>'.
        '</div>';
}

Display::display_header($nameTools, 'Group');

if (!empty($searchAlertHtml)) {
    echo $searchAlertHtml;
}

$form->setDefaults($defaults);
$form->display();

Display::display_footer();
