<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script is the Tickets plugin main entry point.
 *
 * @package chamilo.plugin.ticket
 */

// needed in order to load the plugin lang variables
$course_plugin = 'ticket';
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script(true);

$toolName = get_lang('Categories');
$webLibPath = api_get_path(WEB_LIBRARY_PATH);
$user_id = api_get_user_id();
$isAdmin = api_is_platform_admin();

$this_section = 'tickets';
Session::erase('this_section');

$table = new SortableTable(
    'TicketCategories',
    ['TicketManager', 'getCategoriesCount'],
    ['TicketManager', 'getCategories'],
    1
);

if ($table->per_page == 0) {
    $table->per_page = 20;
}

$formToString = '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$projectId = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

$project = TicketManager::getProject($projectId);
if (empty($project)) {
    api_not_allowed(true);
}

Session::write('project_id', $projectId);
$action = isset($_GET['action']) ? $_GET['action'] : '';

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'ticket/tickets.php?project_id='.$projectId,
    'name' => get_lang('MyTickets'),
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

switch ($action) {
    case 'delete':
        $tickets = TicketManager::getTicketsFromCriteria(['category' => $id]);
        if (empty($tickets)) {
            TicketManager::deleteCategory($id);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        } else {
            Display::addFlash(Display::return_message(get_lang('ThisItemIsRelatedToOtherTickets')));
        }
        header("Location: ".api_get_self().'?project_id='.$projectId);
        exit;
        break;
    case 'add':
        $toolName = get_lang('Add');
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'ticket/categories.php',
            'name' => get_lang('Categories'),
        ];
        $url = api_get_self().'?action=add&project_id='.$projectId;
        $form = TicketManager::getCategoryForm($url, $projectId);
        $formToString = $form->returnForm();
        if ($form->validate()) {
            $values = $form->getSubmitValues();

            $params = [
                'name' => $values['name'],
                'description' => $values['description'],
                'total_tickets' => 0,
                'sys_insert_user_id' => api_get_user_id(),
                'sys_insert_datetime' => api_get_utc_datetime(),
                'course_required' => '',
                'project_id' => $projectId,
            ];
            TicketManager::addCategory($params);

            Display::addFlash(Display::return_message(get_lang('Added')));

            header("Location: ".api_get_self().'?project_id='.$projectId);
            exit;
        }
        break;
    case 'edit':
        if (api_get_setting('ticket_allow_category_edition') !== 'true') {
            api_not_allowed();
        }

        $toolName = get_lang('Edit');
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'ticket/categories.php?project_id='.$projectId,
            'name' => get_lang('Categories'),
        ];
        $url = api_get_self().'?action=edit&project_id='.$projectId.'&id='.$id;
        $form = TicketManager::getCategoryForm($url, $projectId);

        $cat = TicketManager::getCategory($_GET['id']);
        $form->setDefaults($cat);
        $formToString = $form->returnForm();
        if ($form->validate()) {
            $values = $form->getSubmitValues();

            $params = [
                'name' => $values['name'],
                'description' => $values['description'],
                'sys_lastedit_datetime' => api_get_utc_datetime(),
                'sys_lastedit_user_id' => api_get_user_id(),
            ];
            $cat = TicketManager::updateCategory($_GET['id'], $params);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header("Location: ".api_get_self().'?project_id='.$projectId);
            exit;
        }
        break;
    default:
        break;
}

/**
 * Build the modify-column of the table.
 *
 * @param   int     The user id
 * @param   string  URL params to add to table links
 * @param   array   Row of elements to alter
 *
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($id, $params, $row)
{
    $projectId = Session::read('project_id');
    $result = '';
    if (api_get_setting('ticket_allow_category_edition') === 'true') {
        $result .= Display::url(
            Display::return_icon('edit.png', get_lang('Edit')),
            "categories.php?action=edit&id={$row['id']}&project_id=".$projectId
        );
    }

    $result .= Display::url(
        Display::return_icon('user.png', get_lang('AssignUser')),
        "categories_add_user.php?id={$row['id']}&project_id=".$projectId
    );

    if (api_get_setting('ticket_allow_category_edition') === 'true') {
        $result .= Display::url(
            Display::return_icon('delete.png', get_lang('Delete')),
            "categories.php?action=delete&id={$row['id']}&project_id=".$projectId
        );
    }

    return $result;
}

$table->set_header(0, '', false);
$table->set_header(1, get_lang('Title'), false);
$table->set_header(2, get_lang('Description'), true, ["style" => "width:200px"]);
$table->set_header(3, get_lang('TotalTickets'), false);
$table->set_header(4, get_lang('Actions'), true);
$table->set_column_filter(4, 'modify_filter');

Display::display_header($toolName);

$items = [
    [
        'url' => 'categories.php?action=add&project_id='.$projectId,
        'content' => Display::return_icon('new_folder.png', null, null, ICON_SIZE_MEDIUM),
    ],
];

echo Display::actions($items);
echo $formToString;
echo $table->return_table();

Display::display_footer();
