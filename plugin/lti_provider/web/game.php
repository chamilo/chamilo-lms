<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
use Packback\Lti1p3;

$launch = LtiProvider::create()->launch();

$htmlHeadXtra[] = api_get_css(
    api_get_path(WEB_PLUGIN_PATH).'lti_provider/web/static/breakout.css'
);

$template = new Template('Game demo');

$courseCode = $launch->getLaunchData()['https://purl.imsglobal.org/spec/lti/claim/context']['label'];
$diff = 'normal';
if ($launch->isDeepLinkLaunch()) {
    $diff = $launch->getLaunchData()['https://purl.imsglobal.org/spec/lti/claim/custom']['difficulty'];
}
$username = $launch->getLaunchData()['given_name'];
$template->assign('launch', $launch);
$template->assign('courseCode', $courseCode);
$template->assign('diff', $diff);
$template->assign('username', $username);

$content = $template->fetch('lti_provider/web/view/game.tpl');
$template->assign('content', $content);
$template->display_no_layout_template();


?>
