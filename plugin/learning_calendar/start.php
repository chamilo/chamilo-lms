<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$allow = api_is_platform_admin() || api_is_teacher();

if (!$allow) {
    api_not_allowed(true);
}

$plugin = LearningCalendarPlugin::create();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$formToString = '';

switch ($action) {
    case 'add':
        $form = new FormValidator('calendar', 'post', api_get_self().'?action=add');
        $plugin->getForm($form);
        $form->addButtonSave(get_lang('Save'));
        $formToString = $form->returnForm();

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $params = [
                'title' => $values['title'],
                'total_hours' => $values['total_hours'],
                'minutes_per_day' => $values['minutes_per_day'],
                'description' => $values['description'],
                'author_id' => api_get_user_id(),
            ];
            Database::insert('learning_calendar', $params);
            Display::addFlash(Display::return_message(get_lang('Saved')));
            header('Location: start.php');
            exit;
        }
        break;
    case 'edit':
        $form = new FormValidator('calendar', 'post', api_get_self().'?action=edit&id='.$calendarId);
        $plugin->getForm($form);
        $form->addButtonSave(get_lang('Update'));
        $item = $plugin->getCalendar($calendarId);
        $plugin->protectCalendar($item);

        if (empty($item)) {
            api_not_allowed(true);
        }

        $form->setDefaults($item);
        $formToString = $form->returnForm();

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $params = [
                'title' => $values['title'],
                'total_hours' => $values['total_hours'],
                'minutes_per_day' => $values['minutes_per_day'],
                'description' => $values['description'],
            ];
            Database::update('learning_calendar', $params, ['id = ?' => $calendarId]);
            Display::addFlash(Display::return_message(get_lang('Updated')));
            header('Location: start.php');
            exit;
        }
        break;
    case 'copy':
        $result = $plugin->copyCalendar($calendarId);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Saved')));
        }
        header('Location: start.php');
        exit;

        break;
    case 'delete':
        $result = $plugin->deleteCalendar($calendarId);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }
        header('Location: start.php');
        exit;
        break;
    case 'toggle_visibility':
        $itemId = isset($_REQUEST['lp_item_id']) ? $_REQUEST['lp_item_id'] : 0;
        $lpId = isset($_REQUEST['lp_id']) ? $_REQUEST['lp_id'] : 0;
        $plugin->toggleVisibility($itemId);
        Display::addFlash(Display::return_message(get_lang('Updated')));
        $url = api_get_path(WEB_CODE_PATH).
            'lp/lp_controller.php?action=add_item&type=step&lp_id='.$lpId.'&'.api_get_cidreq();
        header("Location: $url");
        exit;
        break;
}

$htmlHeadXtra[] = api_get_jqgrid_js();

$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_learning_path_calendars';
$columns = [
    get_lang('Title'),
    get_lang('TotalHours'),
    get_lang('MinutesPerDay'),
    get_lang('Actions'),
];

$columnModel = [
    [
        'name' => 'title',
        'index' => 'title',
        'width' => '300',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'total_hours',
        'index' => 'total_hours',
        'width' => '100',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'minutes_per_day',
        'index' => 'minutes_per_day',
        'width' => '100',
        'align' => 'left',
        'sortable' => 'false',
    ],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '150',
        'align' => 'left',
        //'formatter' => 'action_formatter',
        'sortable' => 'false',
    ],
];

$extraParams = [];
$extraParams['autowidth'] = 'true';
// height auto
$extraParams['height'] = 'auto';

$template = new Template();

if (in_array($action, ['add', 'edit'])) {
    $actionLeft = Display::url(
        Display::return_icon(
            'back.png',
            get_lang('Back'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_self().'?'.api_get_cidreq()
    );
} else {
    $actionLeft = Display::url(
        Display::return_icon(
            'add.png',
            get_lang('Add'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_self().'?'.api_get_cidreq().'&action=add'
    );

    $content = '<script>
        $(function() {'.
            Display::grid_js(
                'calendars',
                $url,
                $columns,
                $columnModel,
                $extraParams,
                [],
                '',
                true
            ).'
        });
    </script>';

    $content .= Display::grid_html('calendars');
    $template->assign('grid', $content);
}

$template->assign('form', $formToString);
$actions = Display::toolbarAction('toolbar-calendar', [$actionLeft]);
$content = $template->fetch('learning_calendar/view/start.tpl');
$template->assign('content', $content);
$template->assign('actions', $actions);

$template->display_one_col_template();
