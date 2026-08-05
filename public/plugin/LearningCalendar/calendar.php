<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = LearningCalendarPlugin::create();

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$item = $plugin->getCalendar($calendarId);
$plugin->protectCalendar($item);

$template = new Template($item['title']);

$toolbarActions = [];
$toolbarActions[] = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, $plugin->get_lang('BackToMySpace')),
    api_get_path(WEB_CODE_PATH).'my_space/index.php'
);
$toolbarActions[] = Display::url(
    Display::getMdiIcon('format-list-bulleted', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('List')),
    api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/start.php'
);

$eventList = $plugin->getEventTypeList();
$template->assign('events', $eventList);
$template->assign('ajax_url', api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/ajax.php?id='.$calendarId);
$template->assign('plugin_title', $plugin->get_lang('LearningCalendar'));
$template->assign('header', $item['title']);
$template->assign('description', $item['description'] ?? '');
$template->assign('total_hours', (int) $item['total_hours']);
$template->assign('minutes_per_day', (int) $item['minutes_per_day']);
$template->assign('calendar_help', $plugin->get_lang('LearningCalendarSelectRangeHelp'));
$template->assign('calendar_cycle_help', $plugin->get_lang('LearningCalendarCycleHelp'));
$template->assign('calendar_range_help', $plugin->get_lang('LearningCalendarRangeHelp'));

$content = $template->fetch('LearningCalendar/view/calendar.tpl');
$template->assign('actions', Display::toolbarAction('toolbar-calendar', $toolbarActions));
$template->assign('content', $content);

$template->display_one_col_template();
