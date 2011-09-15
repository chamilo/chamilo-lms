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

$tpl = new Template();
$type   = isset($_REQUEST['type']) && in_array($_REQUEST['type'], array('personal', 'course', 'admin')) ?  $_REQUEST['type'] : 'personal';

$agenda_ajax_url = api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?type=personal&';

if (api_is_platform_admin() && $type == 'admin') {	
	$agenda_ajax_url = api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?type=admin&';
}

//if (api_get_course_id() != -1 && $type == 'course') {
if (api_get_course_id() != -1 && isset($_REQUEST['cidReq'])) {
	$agenda_ajax_url = api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?type=course&';
}

$tpl->assign('web_agenda_ajax_url', $agenda_ajax_url);

$content = $tpl->fetch('default/agenda/month.tpl');
$tpl->assign('content', $content);
$template_file = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template_file);