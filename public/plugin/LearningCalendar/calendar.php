<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\FileHelper;

require_once __DIR__.'/../../main/inc/global.inc.php';

$calendarId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$plugin = LearningCalendarPlugin::create();
$item = $plugin->getCalendar($calendarId);
$plugin->protectCalendar($item);

$isoCode = api_get_language_isocode();
$htmlHeadXtra[] = api_get_asset('bootstrap-year-calendar/js/bootstrap-year-calendar.js');
$calendarLanguage = 'en';
if ('en' !== $isoCode) {
    $file = 'bootstrap-year-calendar/js/languages/bootstrap-year-calendar.'.$isoCode.'.js';
    $path = api_get_path(SYS_PUBLIC_PATH).'assets/'.$file;
    if (Container::$container->get(FileHelper::class)->exists($path)) {
        $htmlHeadXtra[] = api_get_asset($file);
        $calendarLanguage = $isoCode;
    }
}

$htmlHeadXtra[] = api_get_css_asset('bootstrap-year-calendar/css/bootstrap-year-calendar.css');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$formToString = '';

$template = new Template();
$actionLeft = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add')),
    api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/start.php'
);

$actions = Display::toolbarAction('toolbar-forum', [$actionLeft]);

$eventList = $plugin->getEventTypeList();
$template->assign('events', $eventList);
$template->assign('calendar_language', $calendarLanguage);
$template->assign('ajax_url', api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/ajax.php?id='.$calendarId);
$template->assign('header', $item['title']);
$content = $template->fetch('LearningCalendar/view/calendar.tpl');
$template->assign('actions', $actions);
$template->assign('content', $content);

$template->display_one_col_template();
