<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/LtiProvider.php';
use \IMSGlobal\LTI;

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->is_deep_link_launch()) {
    throw new Exception("Must be a deep link!");
}
$resource = LTI\LTI_Deep_Link_Resource::new()
    ->set_url(api_get_path(WEB_PLUGIN_PATH)."lti_provider/web/game.php")
    ->set_custom_params(['difficulty' => $_REQUEST['diff']])
    ->set_title('Breakout ' . $_REQUEST['diff'] . ' mode!');

$launch->get_deep_link()
    ->output_response_form([$resource]);
