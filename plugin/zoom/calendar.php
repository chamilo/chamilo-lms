<?php

/* For license terms, see /license.txt */

$course_plugin = 'zoom'; // needed in order to load the plugin lang variables

$cidReset = true;

require_once __DIR__.'/config.php';

api_protect_admin_script();

$plugin = ZoomPlugin::create();
$toolName = $plugin->get_lang('ZoomVideoConferences');

$defaultView = api_get_setting('default_calendar_view');

if (empty($defaultView)) {
    $defaultView = 'month';
}

$regionValue = api_get_language_isocode();

$htmlHeadXtra[] = api_get_asset('qtip2/jquery.qtip.min.js');
$htmlHeadXtra[] = api_get_asset('fullcalendar/dist/fullcalendar.js');
$htmlHeadXtra[] = api_get_asset('fullcalendar/dist/locale-all.js');
$htmlHeadXtra[] = api_get_css_asset('fullcalendar/dist/fullcalendar.min.css');
$htmlHeadXtra[] = api_get_css_asset('qtip2/jquery.qtip.min.css');

$tpl = new Template($toolName);

$tpl->assign('web_agenda_ajax_url', 'calendar.ajax.php?sec_token='.Security::get_token());
$tpl->assign('default_view', $defaultView);
$tpl->assign('region_value', 'en' === $regionValue ? 'en-GB' : $regionValue);

$onHoverInfo = Agenda::returnOnHoverInfo();
$tpl->assign('on_hover_info', $onHoverInfo);

$extraSettings = Agenda::returnFullCalendarExtraSettings();

$tpl->assign('fullcalendar_settings', $extraSettings);

$content = $tpl->fetch('zoom/view/calendar.tpl');

$tpl->assign('actions', $plugin->getToolbar());
$tpl->assign('content', $content);
$tpl->display_one_col_template();
