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

$languageList = api_get_languages();
$list = [];
foreach ($languageList['all'] as $language) {
    $list[] = $language['isocode'].':'.$language['original_name'];
}

$template->assign('language_list', implode("','", $list));

$enterMode = api_get_configuration_value('ck_editor_enter_mode_value');
if (!empty($enterMode)) {
    $template->assign('enter_mode', $enterMode);
}

header('Content-type: application/x-javascript');
$template->display($template->get_template('javascript/editor/ckeditor/config_js.tpl'));
