<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(false);
api_block_anonymous_users(false);

if (empty($_POST['content_items']) || empty($_POST['data'])) {
    api_not_allowed(false);
}

$toolId = str_replace('tool:', '', $_POST['data']);

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();
/** @var Course $course */
$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
/** @var ImsLtiTool|null $ltiTool */
$ltiTool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $toolId);

if (!$ltiTool) {
    api_not_allowed();
}

$consumer = new OAuthConsumer(
    $_POST['oauth_consumer_key'],
    $ltiTool->getSharedSecret()
);
$hmacMethod = new OAuthSignatureMethod_HMAC_SHA1();

$request = OAuthRequest::from_request('POST', api_get_path(WEB_PLUGIN_PATH).'ims_lti/item_return.php');
$request->sign_request($hmacMethod, $consumer, '');
$signature = $request->get_parameter('oauth_signature');

if ($signature !== $_POST['oauth_signature']) {
    api_not_allowed();
}

$contentItems = json_decode($_POST['content_items'], true);
$contentItems = $contentItems['@graph'];

foreach ($contentItems as $contentItem) {
    if ('LtiLinkItem' === $contentItem['@type']) {
        if ('application/vnd.ims.lti.v1.ltilink' === $contentItem['mediaType']) {
            $plugin->saveItemAsLtiLink($contentItem, $ltiTool, $course);

            Display::addFlash(
                Display::return_message($plugin->get_lang('ToolAdded'), 'success')
            );
        }
    }
}

$currentUrl = api_get_path(WEB_PLUGIN_PATH).'ims_lti/start.php?id='.$ltiTool->getId();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <script>
        window.parent.location.href = '<?php echo $currentUrl; ?>';
    </script>
</body>
</html>
