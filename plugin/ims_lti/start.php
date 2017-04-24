<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$toolId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($toolId)) {
    if (api_is_platform_admin()) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/list.php');
        exit;
    }

    api_not_allowed(true);
    exit;
}

api_protect_course_script();

$imsLtiPlugin = ImsLtiPlugin::create();

$tool = ImsLtiTool::fetch($toolId);

$htmlHeadXtra[] = '<link rel="stylesheet" href="../assets/css/style.css" type="text/css">';

$template = new Template($imsLtiPlugin->get_title());
$template->assign(
    'launch_url',
    api_get_path(WEB_PLUGIN_PATH).'ims_lti/form.php?'.http_build_query(['id' => $tool->getId()])
);

$content = $template->fetch('ims_lti/view/start.tpl');

$template->assign('header', $tool->getName());
$template->assign('content', $content);
$template->display_one_col_template();
