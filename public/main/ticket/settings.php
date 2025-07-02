<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

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
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Tickets')),
    api_get_path(WEB_CODE_PATH).'ticket/tickets.php'
);
$sections = TicketManager::getSettingsMenuItems();
foreach ($sections as $item) {
    $actions .= Display::url(
        Display::getMdiIcon($item['icon'], 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $item['content']),
        $item['url']
    );
}

echo Display::toolbarAction('ticket', [$actions]);

Display::display_footer();
