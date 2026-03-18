<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\LtiBundle\Entity\ExternalTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$em = Container::getEntityManager();

/** @var ExternalTool|null $tool */
$tool = isset($_GET['id']) ? $em->find(ExternalTool::class, (int) $_GET['id']) : null;

if (!$tool) {
    api_not_allowed(true);
}

$user = api_get_user_entity(api_get_user_id());

if (!$user) {
    api_not_allowed(true);
}

$imsLtiPlugin = ImsLtiPlugin::create();

$pageTitle = Security::remove_XSS($tool->getTitle());
$publicKey = $imsLtiPlugin->getToolPublicKey($tool);

$is1p3 = !empty($publicKey)
    && !empty($tool->getClientId())
    && !empty($tool->getLoginUrl())
    && !empty($tool->getRedirectUrl());

$courseId = isset($_GET['cid']) ? (int) $_GET['cid'] : (int) api_get_course_int_id();
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : (int) api_get_session_id();
$groupId = isset($_GET['gid']) ? (int) $_GET['gid'] : (int) api_get_group_id();
$gradebook = isset($_GET['gradebook']) ? (int) $_GET['gradebook'] : 0;

$contextParams = [
    'id' => $tool->getId(),
    'cid' => $courseId,
    'sid' => $sessionId,
    'gid' => $groupId,
    'gradebook' => $gradebook,
];

if ($is1p3) {
    $launchUrl = api_get_path(WEB_PLUGIN_PATH).'ImsLti/login.php?'.http_build_query($contextParams);
} else {
    $launchUrl = api_get_path(WEB_PLUGIN_PATH).'ImsLti/form.php?'.http_build_query($contextParams);
}

if ('window' === $tool->getDocumentTarget()) {
    header('Location: '.$launchUrl);
    exit;
}

$template = new Template($pageTitle);
$template->assign('tool', $tool);
$template->assign('launch_url', $launchUrl);

$content = $template->fetch('ImsLti/view/start.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
