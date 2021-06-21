<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php',
    'name' => get_lang('My tickets'),
];

$action = isset($_GET['action']) ? $_GET['action'] : 'projects';

Display::display_header(get_lang('Settings'));

$actions = Display::url(
    Display::return_icon('back.png', get_lang('Tickets'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_CODE_PATH).'ticket/tickets.php'
);
$sections = TicketManager::getSettingsMenuItems();
foreach ($sections as $item) {
    $actions .= Display::url(
        Display::return_icon($item['icon'], $item['content'], [], ICON_SIZE_MEDIUM),
        $item['url']
    );
}

echo Display::toolbarAction('ticket', [$actions]);

Display::display_footer();
