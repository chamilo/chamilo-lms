<?php
/* For licensing terms, see /license.txt */

/**
 * This script is the Tickets plugin main entry point
 * @package chamilo.plugin.ticket
 */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);

$libPath = api_get_path(LIBRARY_PATH);
$webLibPath = api_get_path(WEB_LIBRARY_PATH);

$this_section = 'tickets';
unset($_SESSION['this_section']);

$table = new SortableTable(
    'TicketProject',
    array('TicketManager', 'getProjectsCount'),
    array('TicketManager', 'getProjects'),
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

$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'ticket/projects.php',
    'name' => get_lang('Projects')
);

switch ($action) {
    case 'delete':
        $tickets = TicketManager::getTicketsFromCriteria(['project' => $id]);
        if (empty($tickets)) {
            TicketManager::deleteProject($id);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        } else {
            Display::addFlash(Display::return_message(get_lang('ThisItemIsRelatedToOtherTickets')));
        }
        header("Location: ".api_get_self());
        exit;
        break;
    case 'add':
        $toolName = get_lang('Add');

        $url = api_get_self().'?action=add';
        $form = TicketManager::getProjectForm($url);
        $formToString = $form->returnForm();
        if ($form->validate()) {
            $values =$form->getSubmitValues();

            $params = [
                'name' => $values['name'],
                'description' => $values['description']
            ];
            TicketManager::addProject($params);

            Display::addFlash(Display::return_message(get_lang('Added')));

            header("Location: ".api_get_self());
            exit;
        }
        break;
    case 'edit':
        $item = TicketManager::getProject($_GET['id']);
        if (empty($item)) {
            api_not_allowed(true);
        }
        $toolName = get_lang('Edit');
        $url = api_get_self().'?action=edit&id='.$id;
        $form = TicketManager::getProjectForm($url);

        $form->setDefaults([
            'name' => $item->getName(),
            'description' => $item->getDescription()
        ]);

        $formToString = $form->returnForm();
        if ($form->validate()) {
            $values =$form->getSubmitValues();

            $params = [
                'name' => $values['name'],
                'description' => $values['description'],
                'sys_lastedit_datetime' => api_get_utc_datetime(),
                'sys_lastedit_user_id' => api_get_user_id()
            ];
            TicketManager::updateProject($_GET['id'], $params);
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
    $result = Display::url(
        get_lang('Tickets'),
        "tickets.php?project_id={$row['id']}",
        ['class' => 'btn btn-small btn-default']
    );

    $result .= Display::url(
        get_lang('Categories'),
        "categories.php?project_id={$row['id']}",
        ['class' => 'btn btn-default']
    );

    $result .= Display::url(
        Display::return_icon('edit.png', get_lang('Edit')),
        "projects.php?action=edit&id={$row['id']}"
    );

    $result .= Display::url(
        Display::return_icon('delete.png', get_lang('Delete')),
        "projects.php?action=delete&id={$row['id']}"
    );

    return $result;
}

$table->set_header(0, '', false);
$table->set_header(1, get_lang('Title'), false);
$table->set_header(2, get_lang('Description'), true, array("style" => "width:200px"));
$table->set_header(3, get_lang('Actions'), true);
$table->set_column_filter('3', 'modify_filter');

Display::display_header('');

$items = [
    [
        'url' => 'projects.php?action=add',
        'content' => Display::return_icon('new_folder.png', null, null, ICON_SIZE_MEDIUM)
    ]
];

echo Display::actions($items);
echo $formToString;
echo $table->return_table();

Display::display_footer();
