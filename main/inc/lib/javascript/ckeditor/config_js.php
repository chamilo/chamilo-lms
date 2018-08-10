<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

require_once __DIR__.'/../../../global.inc.php';

$moreButtonsInMaximizedMode = false;
if (api_get_setting('more_buttons_maximized_mode') === 'true') {
    $moreButtonsInMaximizedMode = true;
}

$template = new Template();
$template->assign(
    'bootstrap_css',
    api_get_path(WEB_PUBLIC_PATH).'assets/bootstrap/dist/css/bootstrap.min.css'
);
$template->assign(
    'font_awesome_css',
    api_get_path(WEB_PUBLIC_PATH).'assets/fontawesome/css/font-awesome.min.css'
);
$template->assign(
    'css_editor',
    ChamiloApi::getEditorBlockStylePath()
);
$template->assign('moreButtonsInMaximizedMode', $moreButtonsInMaximizedMode);
$courseId = api_get_course_int_id();
$courseCondition = '';
if (!empty($courseId)) {
    $courseCondition = api_get_cidreq();
}
$template->assign('course_condition', $courseCondition);

header('Content-type: application/x-javascript');
$template->display($template->get_template('javascript/editor/ckeditor/config_js.tpl'));
