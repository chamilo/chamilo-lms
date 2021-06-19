<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);

$categoryId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

$categoryInfo = TicketManager::getCategory($categoryId);

if (empty($categoryInfo)) {
    api_not_allowed(true);
}

$project = TicketManager::getProject($projectId);
if (empty($project)) {
    api_not_allowed(true);
}

$form = new FormValidator('edit', 'post', api_get_self().'?id='.$categoryId.'&project_id='.$projectId);
$form->addHeader($categoryInfo['name']);
$users = UserManager::get_user_list([], ['firstname']);
$users = array_column($users, 'complete_name', 'user_id');

$form->addMultiSelect(
    'users',
    get_lang('Users'),
    $users
);

$usersAdded = TicketManager::getUsersInCategory($categoryId);
if (!empty($usersAdded)) {
    $usersAdded = array_column($usersAdded, 'user_id');
}

$form->setDefaults(['users' => $usersAdded]);
// submit button
$form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $values = $form->exportValues();
    TicketManager::deleteAllUserInCategory($categoryId);
    TicketManager::addUsersToCategory($categoryId, $values['users']);
    Display::addFlash(Display::return_message(get_lang('Update successful')));
    header('Location: '.api_get_self().'?id='.$categoryId.'&project_id='.$projectId);
    exit;
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php?project_id='.$projectId,
    'name' => get_lang('My tickets'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/settings.php',
    'name' => get_lang('Settings'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/projects.php',
    'name' => get_lang('Projects'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/projects.php',
    'name' => $project->getName(),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/categories.php?project_id='.$projectId,
    'name' => get_lang('Categories'),
];

Display::display_header(get_lang('Users'));
$form->display();
