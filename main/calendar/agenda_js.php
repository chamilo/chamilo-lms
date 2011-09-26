<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.calendar
 */
/**
 * INIT SECTION
 */

// name of the language file that needs to be included
$language_file = array('agenda','group');

// use anonymous mode when accessing this course tool
$use_anonymous = true;

require_once '../inc/global.inc.php';

$htmlHeadXtra[] = api_get_jquery_ui_js();
$htmlHeadXtra[] = api_get_js('qtip2/jquery.qtip.min.js');
$htmlHeadXtra[] = api_get_js('fullcalendar/fullcalendar.min.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/fullcalendar/fullcalendar.css');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/qtip2/jquery.qtip.min.css');

$tpl	= new Template();
$type  	= isset($_REQUEST['type']) && in_array($_REQUEST['type'], array('personal', 'course', 'admin')) ?  $_REQUEST['type'] : 'personal';

if (api_is_platform_admin() && $type == 'admin') {	
	$type = 'admin';
}
//if (api_get_course_id() != -1 && $type == 'course') {
if (isset($_REQUEST['cidReq']) && !empty($_REQUEST['cidReq'])) {	
	$type = 'course';
}

$can_add_events = 0;
if (api_is_platform_admin() && $type == 'admin') {
	$can_add_events = 1;
}
if (api_is_allowed_to_edit() && $type == 'course') {
	$can_add_events = 1;	
}
if (!api_is_anonymous() && $type == 'personal') {
	$can_add_events = 1;
}

//Setting translations
$day_short 		= api_get_week_days_short();
$days 			= api_get_week_days_long();
$months 		= api_get_months_long();
$months_short 	= api_get_months_short();

$tpl->assign('month_names', json_encode($months));
$tpl->assign('month_names_short', json_encode($months_short));
$tpl->assign('day_names', json_encode($days));
$tpl->assign('day_names_short', json_encode($day_short));

$tpl->assign('button_text', json_encode(array('today'=>get_lang('Today'), 'month'=>get_lang('Month'), 'week'=>get_lang('Week'), 'day'=>get_lang('Day'))));



$tpl->assign('type', $type);
$tpl->assign('can_add_events', $can_add_events);
 
$agenda_ajax_url = api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?type='.$type.'&';
$tpl->assign('web_agenda_ajax_url', $agenda_ajax_url);
$content = $tpl->fetch('default/agenda/month.tpl');
$tpl->assign('content', $content);
$template_file = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template_file);