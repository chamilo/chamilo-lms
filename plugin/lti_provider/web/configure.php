<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
use Packback\Lti1p3;

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->isDeepLinkLaunch()) {
    throw new Exception("Must be a deep link!");
}

$resource = LtiDeepLinkResource::new()
    ->setUrl(api_get_path(WEB_PLUGIN_PATH)."lti_provider/web/game.php")
    ->setCustomParams(['difficulty' => $_REQUEST['diff']])
    ->setTitle('Breakout ' . $_REQUEST['diff'] . ' mode!');

$launch->getDeepLink()
    ->outputResponseForm([$resource]);
