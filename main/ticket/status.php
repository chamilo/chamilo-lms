<?php
/* For licensing terms, see /license.txt */

/**
 * This script is the Tickets plugin main entry point
 * @package chamilo.plugin.ticket
 */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);

$toolName = get_lang('Status');

$libPath = api_get_path(LIBRARY_PATH);
$webLibPath = api_get_path(WEB_LIBRARY_PATH);

$this_section = 'tickets';
unset($_SESSION['this_section']);

$table = new SortableTable(
    'TicketProject',
    array('TicketManager', 'getStatusCount'),
    array('TicketManager', 'getStatusAdminList'),
    1
);

if ($table->per_page == 0) {
    $table->per_page = 20;
}

$formToString = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php',
    'name' => get_lang('MyTickets')
);
$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'ticket/settings.php',
    'name' => get_lang('Settings')
);

switch ($action) {
    case 'delete':
        $tickets = TicketManager::getTicketsFromCriteria(['status' => $id]);
        if (empty($tickets)) {
            TicketManager::deleteStatus($id);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        } else {
            Display::addFlash(Display::return_message(get_lang('ThisItemIsRelatedToOtherTickets'), 'warning'));
        }

        header("Location: ".api_get_self());
        exit;
        break;
    case 'add':
        $toolName = get_lang('Add');
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'ticket/status.php',
            'name' => get_lang('Status')
        );
        $url = api_get_self().'?action=add';
        $form = TicketManager::getStatusForm($url);
        $formToString = $form->returnForm();
        if ($form->validate()) {
            $values = $form->getSubmitValues();

            $params = [
                'name' => $values['name'],
                'description' => $values['description']
            ];
            TicketManager::addStatus($params);
            Display::addFlash(Display::return_message(get_lang('Added')));

            header("Location: ".api_get_self());
            exit;
        }
        break;
    case 'edit':
        $toolName = get_lang('Edit');
        $interbreadcrumb[] = array(
            'url' => api_get_path(WEB_CODE_PATH).'ticket/status.php',
            'name' => get_lang('Status')
        );
        $url = api_get_self().'?action=edit&id='.$id;
        $form = TicketManager::getStatusForm($url);

        $item = TicketManager::getStatus($_GET['id']);
        $form->setDefaults([
            'name' => $item->getName(),
            'description' => $item->getDescription()
        ]);
        $formToString = $form->returnForm();
        if ($form->validate()) {
            $values = $form->getSubmitValues();

            $params = [
                'name' => $values['name'],
                'description' => $values['description']
            ];
            $cat = TicketManager::updateStatus($_GET['id'], $params);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header("Location: ".api_get_self());
            exit;
        }
        break;
    default:
        break;
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
    $id = $row['id'];
    $code = $row['code'];

    $result = Display::url(
        Display::return_icon('edit.png', get_lang('Edit')),
        api_get_self()."?action=edit&id={$id}"
    );

    if (!in_array($code, TicketManager::getDefaultStatusList())) {
        $result .= Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            api_get_self()."?action=delete&id={$id}"
        );
    }

	return $result;
}

$table->set_header(0, '', false);
$table->set_header(1, get_lang('Title'), false);
$table->set_header(2, get_lang('Description'), true, array("style" => "width:200px"));
$table->set_header(3, get_lang('Actions'), true);
$table->set_column_filter('3', 'modify_filter');

Display::display_header($toolName);

$items = [
    [
        'url' => 'status.php?action=add',
        'content' => Display::return_icon('new_folder.png', null, null, ICON_SIZE_MEDIUM)
    ]
];

echo Display::actions($items);
echo $formToString;
echo $table->return_table();

Display::display_footer();
