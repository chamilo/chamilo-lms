<?php
/* For license terms, see /license.txt */
require '../../main/inc/global.inc.php';

api_protect_course_script();

$toolId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($toolId)) {
    api_not_allowed();
}

$imsLtiPlugin = ImsLtiPlugin::create();

$tool = ImsLtiTool::fetch($toolId);

$htmlHeadXtra[] = '<link rel="stylesheet" href="../assets/css/style.css" type="text/css">';

$template = new Template($imsLtiPlugin->get_title());
$template->assign(
    'launch_url',
    api_get_path(WEB_PLUGIN_PATH) . 'ims_lti/form.php?' . http_build_query(['id' => $tool->getId()])
);

$content = $template->fetch('ims_lti/view/start.tpl');

$template->assign('header', $tool->getName());
$template->assign('content', $content);
$template->display_one_col_template();
