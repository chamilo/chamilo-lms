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
$tpl->assign('ajax_url', api_get_path(WEB_AJAX_PATH).'agenda.ajax.php');
$content = $tpl->fetch('default/agenda/month.tpl');
$tpl->assign('content', $content);
$template_file = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template_file);