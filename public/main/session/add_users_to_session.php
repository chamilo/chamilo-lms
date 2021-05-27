<?php

/* For licensing terms, see /license.txt */

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
        'url' => api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=user_by_role&status='.STUDENT,
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

$link_add_group = Display::url(
    Display::return_icon('multiple.gif', get_lang('Enrolment by classes')).get_lang('Enrolment by classes'),
    api_get_path(WEB_CODE_PATH).'admin/usergroups.php'
);

$newLinks = Display::url(
    Display::return_icon('teacher.png', get_lang('Enroll trainers from existing sessions'), null, ICON_SIZE_TINY).
    get_lang('Enroll trainers from existing sessions'),
    api_get_path(WEB_CODE_PATH).'session/add_teachers_to_session.php?id='.$sessionId
);
$newLinks .= Display::url(
    Display::return_icon('user.png', get_lang('Enroll trainers from existing sessions'), null, ICON_SIZE_TINY).
    get_lang('Enroll students from existing sessions'),
    api_get_path(WEB_CODE_PATH).'session/add_students_to_session.php?id='.$sessionId
);

echo Display::toolbarAction(
    'session_actions',
    [
        $link_add_group.
        $newLinks,
    ]
);

echo '<h2>'.$tool_name.' ('.$session->getName().') </h2>';

echo $contentForm;

Display::display_footer();
