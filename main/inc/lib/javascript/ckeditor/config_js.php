<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../../global.inc.php';

$moreButtonsInMaximizedMode = false;
if (api_get_setting('more_buttons_maximized_mode') === 'true') {
    $moreButtonsInMaximizedMode = true;
}

$template = new Template();
$template->setStyleMenuInCkEditor();
$template->assign('moreButtonsInMaximizedMode', $moreButtonsInMaximizedMode);
$courseId = api_get_course_int_id();
$courseCondition = '';
if (!empty($courseId)) {
    $courseCondition = api_get_cidreq();
}
$template->assign('course_condition', $courseCondition);

header('Content-type: application/x-javascript');
$template->display($template->get_template('javascript/editor/ckeditor/config_js.tpl'));
