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
    array('TicketManager', 'getCategories'),
    1
);

if ($table->per_page == 0) {
    $table->per_page = 20;
}

$formToString = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (isset($_GET['action'])) {
    global $table;
    $action = $_GET['action'];
    switch ($action) {
        case 'delete':
            TicketManager::deleteCategory($id);

            Display::addFlash(Display::return_message(get_lang('Deleted')));
            header("Location: ".api_get_self());
            break;
        case 'add':
            $url = api_get_self().'?action=add';
            $form = TicketManager::getCategoryForm($url);
            $formToString = $form->returnForm();
            if ($form->validate()) {
                $values =$form->getSubmitValues();

                $params = [
                    'name' => $values['name'],
                    'description' => $values['description'],
                    'total_tickets' => 0,
                    'sys_insert_user_id' => api_get_user_id(),
                    'sys_insert_datetime' => api_get_utc_datetime()
                ];
                TicketManager::addCategory($params);

                Display::addFlash(Display::return_message(get_lang('Added')));

                header("Location: ".api_get_self());
                exit;
            }
            break;
        case 'edit':
            $url = api_get_self().'?action=edit&id='.$id;
            $form = TicketManager::getCategoryForm($url);

            $cat = TicketManager::getCategory($_GET['id']);
            $form->setDefaults($cat);
            $formToString = $form->returnForm();
            if ($form->validate()) {
                $values =$form->getSubmitValues();

                $params = [
                    'name' => $values['name'],
                    'description' => $values['description'],
                    'sys_lastedit_datetime' => api_get_utc_datetime(),
                    'sys_lastedit_user_id' => api_get_user_id()
                ];
                $cat = TicketManager::updateCategory($_GET['id'], $params);
                Display::addFlash(Display::return_message(get_lang('Updated')));
                header("Location: ".api_get_self());
                exit;
            }
            break;
        default:
            break;
    }
}

$user_id = api_get_user_id();
$isAdmin = api_is_platform_admin();

/**
 * Build the modify-column of the table
 * @param   int     The user id
 * @param   string  URL params to add to table links
 * @param   array   Row of elements to alter
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($id, $params, $row)
{
    $result = Display::url(
        Display::return_icon('edit.png', get_lang('Edit')),
        "categories.php?action=edit&id={$row['id']}"
    );

    $result .= Display::url(
        Display::return_icon('user.png', get_lang('AssignUser')),
        "categories_add_user.php?id={$row['id']}"
    );

    $result .= Display::url(
        Display::return_icon('delete.png', get_lang('Delete')),
        "categories.php?action=delete&id={$row['id']}"
    );

	return $result;
}

$table->set_header(0, '', false);
$table->set_header(1, $plugin->get_lang('Title'), false);
$table->set_header(2, get_lang('Description'), true, array("style" => "width:200px"));
$table->set_header(3, $plugin->get_lang('TotalTickets'), false);
$table->set_header(4, get_lang('Actions'), true);
$table->set_column_filter(4, 'modify_filter');

$interbreadcrumb[] = array('url' => 'myticket.php', 'name' => $plugin->get_lang('MyTickets'));

Display::display_header($plugin->get_lang('Categories'));

$items = [
    [
        'url' => 'categories.php?action=add',
        'content' => Display::return_icon('new_folder.png', null, null, ICON_SIZE_MEDIUM),
    ]
];

echo Display::actions($items);
echo $formToString;
echo $table->return_table();

Display::display_footer();
