<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$allow = api_is_allowed_to_edit();

if (!$allow) {
    api_not_allowed(true);
}

$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

$item = LpCalendarPlugin::getCalendar($calendarId);

if (empty($item)) {
    api_not_allowed(true);
}

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

$plugin = LpCalendarPlugin::create();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$formToString = '';

switch ($action) {

}

$template = new Template();

$actionLeft = Display::url(
    Display::return_icon(
        'back.png',
        get_lang('Add'),
        null,
        ICON_SIZE_MEDIUM
    ),
    api_get_path(WEB_PLUGIN_PATH).'lp_calendar/start.php'
);

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);
$template->assign('calendar_language', $calendarLanguage);

$template->assign('ajax_url', api_get_path(WEB_PLUGIN_PATH).'lp_calendar/ajax.php?id='.$calendarId);

$template->assign('header', $item['title']);
$content = $template->fetch('lp_calendar/view/calendar.tpl');
$template->assign('actions', $actions);
$template->assign('content', $content);

$template->display_one_col_template();
