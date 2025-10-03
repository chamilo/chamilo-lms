<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();

$em = Database::getManager();

/** @var ImsLtiTool $tool */
$tool = isset($_GET['id']) ? $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', intval($_GET['id'])) : null;
$user = api_get_user_entity(
    api_get_user_id()
);

if (!$tool) {
    api_not_allowed(true);
}

$imsLtiPlugin = ImsLtiPlugin::create();

$pageTitle = Security::remove_XSS($tool->getName());
$publicKey = ImsLtiPlugin::getToolPublicKey($tool);

$is1p3 = !empty($publicKey) && !empty($tool->getClientId()) &&
    !empty($tool->getLoginUrl()) && !empty($tool->getRedirectUrl());

if ($is1p3) {
    $launchUrl = api_get_path(WEB_PLUGIN_PATH).'ims_lti/login.php?id='.$tool->getId();
} else {
    $launchUrl = api_get_path(WEB_PLUGIN_PATH).'ims_lti/form.php?'.http_build_query(['id' => $tool->getId()]);
}

if ($tool->getDocumentTarget() == 'window') {
    header("Location: $launchUrl");
    exit;
}

$template = new Template($pageTitle);
$template->assign('tool', $tool);

$template->assign('launch_url', $launchUrl);

$content = $template->fetch('ims_lti/view/start.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
