<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();

$em = Database::getManager();

/** @var ImsLtiTool $tool */
$tool = isset($_GET['id']) ? $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', intval($_GET['id'])) : null;

if (!$tool) {
    api_not_allowed(true);
}

$imsLtiPlugin = ImsLtiPlugin::create();

$template = new Template($tool->getName());
$template->assign('tool', $tool);
$template->assign(
    'launch_url',
    api_get_path(WEB_PLUGIN_PATH).'ims_lti/form.php?'.http_build_query(['id' => $tool->getId()])
);

$content = $template->fetch('ims_lti/view/start.tpl');

$template->assign('header', $tool->getName());
$template->assign('content', $content);
$template->display_one_col_template();
