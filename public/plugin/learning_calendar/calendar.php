<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$plugin = LearningCalendarPlugin::create();
$item = $plugin->getCalendar($calendarId);
$plugin->protectCalendar($item);

$isoCode = api_get_language_isocode();
$htmlHeadXtra[] = api_get_asset('bootstrap-year-calendar/js/bootstrap-year-calendar.js');
$calendarLanguage = 'en';
if ($isoCode !== 'en') {
    $file = 'bootstrap-year-calendar/js/languages/bootstrap-year-calendar.'.$isoCode.'.js';
    $path = api_get_path(SYS_PUBLIC_PATH).'assets/'.$file;
    if (file_exists($path)) {
        $htmlHeadXtra[] = api_get_asset($file);
        $calendarLanguage = $isoCode;
    }
}

$htmlHeadXtra[] = api_get_css_asset('bootstrap-year-calendar/css/bootstrap-year-calendar.css');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$formToString = '';

$template = new Template();
$actionLeft = Display::url(
    Display::return_icon(
        'back.png',
        get_lang('Add'),
        null,
        ICON_SIZE_MEDIUM
    ),
    api_get_path(WEB_PLUGIN_PATH).'learning_calendar/start.php'
);

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);

$eventList = $plugin->getEventTypeList();
$template->assign('events', $eventList);
$template->assign('calendar_language', $calendarLanguage);
$template->assign('ajax_url', api_get_path(WEB_PLUGIN_PATH).'learning_calendar/ajax.php?id='.$calendarId);
$template->assign('header', $item['title']);
$content = $template->fetch('learning_calendar/view/calendar.tpl');
$template->assign('actions', $actions);
$template->assign('content', $content);

$template->display_one_col_template();
