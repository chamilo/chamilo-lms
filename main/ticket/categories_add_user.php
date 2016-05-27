<?php
/* For licensing terms, see /license.txt */

/**
 *
 * @package chamilo.plugin.ticket
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$plugin = TicketPlugin::create();

api_protect_admin_script(true);

$categoryId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$categoryInfo = TicketManager::getCategory($categoryId);

if (empty($categoryInfo)) {
    api_not_allowed(true);
}

$form = new FormValidator('edit', 'post', api_get_self().'?id='.$categoryId);
$form->addHeader($categoryInfo['name']);
$users = UserManager::get_user_list([], ['firstname']);
$users = array_column($users, 'complete_name', 'user_id');

$form->addElement(
    'advmultiselect',
    'users',
    get_lang('Users'),
    $users,
    'style="width: 280px;"'
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
    Display::addFlash(Display::return_message(get_lang('Updated')));
    header("Location: ".api_get_self()."?id=".$categoryId);
    exit;
}

$interbreadcrumb[] = array('url' => 'myticket.php', 'name' => get_lang('MyTickets'));
$interbreadcrumb[] = array('url' => 'categories.php', 'name' => get_lang('Categories'));
Display::display_header(get_lang('Users'));
$form->display();
