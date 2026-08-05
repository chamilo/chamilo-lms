<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = LearningCalendarPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$allow = api_is_platform_admin() || api_is_teacher();

if (!$allow) {
    api_not_allowed(true);
}

$action = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : '';
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
                'title' => trim((string) $values['title']),
                'total_hours' => (int) $values['total_hours'],
                'minutes_per_day' => (int) $values['minutes_per_day'],
                'description' => (string) $values['description'],
                'author_id' => api_get_user_id(),
            ];
            Database::insert('learning_calendar', $params);
            Display::addFlash(Display::return_message(get_lang('Saved.')));
            header('Location: start.php');
            exit;
        }

        break;
    case 'edit':
        $item = $plugin->getCalendar($calendarId);
        $plugin->protectCalendar($item);

        $form = new FormValidator('calendar', 'post', api_get_self().'?action=edit&id='.$calendarId);
        $plugin->getForm($form);
        $form->addButtonSave(get_lang('Update'));
        $form->setDefaults($item);
        $formToString = $form->returnForm();

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $params = [
                'title' => trim((string) $values['title']),
                'total_hours' => (int) $values['total_hours'],
                'minutes_per_day' => (int) $values['minutes_per_day'],
                'description' => (string) $values['description'],
            ];
            Database::update('learning_calendar', $params, ['id = ?' => $calendarId]);
            Display::addFlash(Display::return_message(get_lang('Update successful')));
            header('Location: start.php');
            exit;
        }

        break;
    case 'copy':
        $result = $plugin->copyCalendar($calendarId);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Saved.')));
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
        $itemId = isset($_REQUEST['lp_item_id']) ? (int) $_REQUEST['lp_item_id'] : 0;
        $lpId = isset($_REQUEST['lp_id']) ? (int) $_REQUEST['lp_id'] : 0;
        $plugin->toggleVisibility($itemId);
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        $url = api_get_path(WEB_CODE_PATH).
            'lp/lp_controller.php?action=add_item&type=step&lp_id='.$lpId.'&'.api_get_cidreq();
        header("Location: $url");
        exit;

        break;
}

$template = new Template($plugin->get_lang('LearningCalendar'));

$toolbarActions = [];
$toolbarActions[] = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $plugin->get_lang('BackToMySpace')),
    api_get_path(WEB_CODE_PATH).'my_space/index.php'
);

if (in_array($action, ['add', 'edit'], true)) {
    $toolbarActions[] = Display::url(
        Display::getMdiIcon('format-list-bulleted', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
        api_get_self()
    );
} else {
    $toolbarActions[] = Display::url(
        Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')),
        api_get_self().'?action=add'
    );
}

$calendarList = [];
if (!in_array($action, ['add', 'edit'], true)) {
    foreach ($plugin->getCalendarList() as $calendar) {
        $calendarId = (int) $calendar['id'];
        $calendarList[] = [
            'id' => $calendarId,
            'title' => $calendar['title'],
            'description' => $calendar['description'],
            'total_hours' => (int) $calendar['total_hours'],
            'minutes_per_day' => (int) $calendar['minutes_per_day'],
            'event_count' => (int) $calendar['event_count'],
            'user_count' => (int) $calendar['user_count'],
            'view_url' => api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/calendar.php?id='.$calendarId,
            'users_url' => api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/calendar_users.php?id='.$calendarId,
            'edit_url' => api_get_self().'?action=edit&id='.$calendarId,
            'copy_url' => api_get_self().'?action=copy&id='.$calendarId,
            'delete_url' => api_get_self().'?action=delete&id='.$calendarId,
        ];
    }
}

$template->assign('is_form_view', in_array($action, ['add', 'edit'], true));
$template->assign('form', $formToString);
$template->assign('calendars', $calendarList);
$template->assign('page_title', $plugin->get_lang('LearningCalendar'));
$template->assign('page_subtitle', $plugin->get_lang('LearningCalendarDescription'));
$template->assign('empty_message', $plugin->get_lang('NoLearningCalendarAvailable'));
$template->assign('delete_confirm', get_lang('Please confirm your choice'));
$template->assign('add_url', api_get_self().'?action=add');
$template->assign('back_url', api_get_path(WEB_CODE_PATH).'my_space/index.php');
$template->assign('back_label', $plugin->get_lang('BackToMySpace'));

$actions = Display::toolbarAction('toolbar-calendar', $toolbarActions);
$content = $template->fetch('LearningCalendar/view/start.tpl');
$template->assign('content', $content);
$template->assign('actions', $actions);

$template->display_one_col_template();
