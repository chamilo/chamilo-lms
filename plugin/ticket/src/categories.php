<?php
/* For licensing terms, see /license.txt */

/**
 * This script is the Tickets plugin main entry point
 * @package chamilo.plugin.ticket
 */

$cidReset = true;
// needed in order to load the plugin lang variables
$course_plugin = 'ticket';
require_once '../config.php';

$plugin = TicketPlugin::create();

api_protect_admin_script(true);

$tool_name = $plugin->get_lang('LastEdit');


$libPath = api_get_path(LIBRARY_PATH);
$webLibPath = api_get_path(WEB_LIBRARY_PATH);

$this_section = 'tickets';
unset($_SESSION['this_section']);

$table = new SortableTable(
    'TicketCategories',
    array('TicketManager', 'getCategoriesCount'),
    array('TicketManager', 'get_all_tickets_categories'),
    1
);

if ($table->per_page == 0) {
    $table->per_page = 20;
}

if (isset($_GET['action'])) {
    global $table;
    $action = $_GET['action'];
    switch ($action) {
        case 'assign':
            if ($isAdmin && isset($_GET['ticket_id']))
                TicketManager::assign_ticket_user($_GET['ticket_id'], $user_id);
            break;
        default:
            break;
    }
}

$user_id = api_get_user_id();
$isAdmin = api_is_platform_admin();

Display::display_header($plugin->get_lang('MyTickets'));

$table->set_header(0, $plugin->get_lang('Title'), true);
$table->set_header(1, get_lang('Description'), true, array("style" => "width:200px"));
$table->set_header(2, $plugin->get_lang('TotalTickets'), true);
$table->set_header(3, get_lang('Actions'), true);

echo $table->return_table();

Display::display_footer();
