<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ObjectIcon;

// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$xajax = new xajax();
$xajax->registerFunction('search_users');

$this_section = SECTION_PLATFORM_ADMIN;
$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

$addProcess = isset($_GET['add']) ? Security::remove_XSS($_GET['add']) : null;

$session = api_get_session_entity($sessionId);
SessionManager::protectSession($session);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];
$interbreadcrumb[] = [
    'url' => 'resume_session.php?id_session='.$sessionId,
    'name' => get_lang('Session overview'),
];

$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

// setting the name of the tool
$tool_name = get_lang('Subscribe users to this session');
$add_type = 'unique';
if (isset($_REQUEST['add_type']) && '' != $_REQUEST['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$page = isset($_GET['page']) ? Security::remove_XSS($_GET['page']) : null;

$form = new FormValidator(
    'add_users_to_session',
    'post',
    api_get_self().'?id_session='.$sessionId
);
$form->addHidden('id_session', $sessionId);

$form->addSelectAjax(
    'users',
    get_lang('Users'),
    [],
    [
        'id' => 'users',
        'multiple' => 'multiple',
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_all_roles',
        'class' => 'w-full',
    ]
);

$form->addButtonSave(get_lang('Add'));

$contentForm = $form->returnForm();
if ($form->validate()) {
    $data = $form->getSubmitValues();
    $users = $data['users'] ?? [];

    SessionManager::subscribeUsersToSession(
        $sessionId,
        $users,
        null,
        false
    );
    Display::addFlash(Display::return_message(get_lang('Update successful')));
    header('Location: resume_session.php?id_session='.$sessionId);
    exit;
}

Display::display_header($tool_name);

// URLs for "tabs" (kept for backward compatibility and fallback rendering)
$urlByClasses = api_get_path(WEB_CODE_PATH).'admin/usergroups.php?from_session='.$sessionId;
$urlFromTeachers = api_get_path(WEB_CODE_PATH).'session/add_teachers_to_session.php?id='.$sessionId;
$urlFromStudents = api_get_path(WEB_CODE_PATH).'session/add_students_to_session.php?id='.$sessionId;

// Current page URL (to mark active tab)
$currentUrl = api_get_self().'?id_session='.$sessionId;

$sessionTitle = $session ? (string) $session->getTitle() : '';
$backUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$sessionId;
$tipHtml = ''
    .'<div class="rounded-md border border-gray-20 bg-gray-10 p-3 text-sm text-gray-70">'
    .'  <span class="font-semibold text-gray-90">'.get_lang('Tip').':</span> '
    .   get_lang('You can select multiple users').'. '
    .   get_lang('Use the search box to find users quickly').'.'
    .'</div>';

$pageContent = $tipHtml
    .'<div class="mt-4">'
    .$contentForm
    .'</div>';

if (method_exists('Display', 'sessionSubscriptionPage')) {
    echo Display::sessionSubscriptionPage(
        $sessionId,
        $sessionTitle,
        $backUrl,
        'users',
        $pageContent,
        [
            'users_url' => $currentUrl,
            'classes_url' => $urlByClasses,
            'teachers_url' => $urlFromTeachers,
            'students_url' => $urlFromStudents,
            'header_title' => $tool_name,
        ]
    );
} else {
    echo '<div class="mx-auto w-full p-4 space-y-4">';
    // Header card
    echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
    echo '    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">';
    echo '      <div class="min-w-0">';
    echo '        <h1 class="text-lg font-semibold text-gray-90">'.htmlspecialchars($tool_name, ENT_QUOTES, api_get_system_encoding()).'</h1>';
    echo '        <p class="text-sm text-gray-50">'.htmlspecialchars($sessionTitle, ENT_QUOTES, api_get_system_encoding()).'</p>';
    echo '      </div>';
    echo '      <div class="flex items-center gap-2">';
    echo '        <a href="'.$backUrl.'" class="inline-flex items-center gap-2 rounded-md border border-gray-30 bg-white px-3 py-1.5 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-10">';
    echo            get_lang('Back');
    echo '        </a>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';

    // Tabs (options)
    echo '  <div class="rounded-lg border border-gray-30 bg-white shadow-sm">';
    echo '    <div class="flex flex-wrap items-center gap-2 border-b border-gray-20 px-3 py-2">';

    // Active tab: current page (users)
    echo '      <a href="'.$currentUrl.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold bg-gray-10 text-gray-90">';
    echo            Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Users'));
    echo '        <span>'.get_lang('Users').'</span>';
    echo '      </a>';

    // Other options as "tabs"
    echo '      <a href="'.$urlByClasses.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
    echo            Display::getMdiIcon(ObjectIcon::MULTI_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enrolment by classes'));
    echo '        <span>'.get_lang('Enrolment by classes').'</span>';
    echo '      </a>';

    echo '      <a href="'.$urlFromTeachers.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
    echo            Display::getMdiIcon(ObjectIcon::TEACHER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enroll trainers from existing sessions'));
    echo '        <span>'.get_lang('Enroll trainers from existing sessions').'</span>';
    echo '      </a>';

    echo '      <a href="'.$urlFromStudents.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
    echo            Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enroll students from existing sessions'));
    echo '        <span>'.get_lang('Enroll students from existing sessions').'</span>';
    echo '      </a>';

    echo '    </div>';

    // Form card body
    echo '    <div class="p-4">';
    echo          $pageContent;
    echo '    </div>';
    echo '  </div>';

    echo '</div>';
}
Display::display_footer();
