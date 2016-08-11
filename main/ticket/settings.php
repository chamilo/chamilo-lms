<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php',
    'name' => get_lang('MyTickets')
);

$action = isset($_GET['action']) ? $_GET['action'] : 'projects';

Display::display_header(get_lang('Settings'));

echo TicketManager::getSettingsMenu();

Display::display_footer();
