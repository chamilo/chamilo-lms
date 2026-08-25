<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

// Deep Linking returns from the external tool with a cross-site POST.
// With SameSite cookie restrictions, the first POST may arrive without the
// existing Chamilo session cookie. Re-post the exact LTI response once from
// a Chamilo page so that the browser sends the first-party session cookie.
if (!api_get_user_id() && empty($_POST['repost'])) {
    header_remove('Set-Cookie');
    $repostAction = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>LTI Deep Linking</title>
    </head>
    <body>
        <form id="ltiRepostForm" method="post" action="<?php echo $repostAction; ?>">
            <?php foreach ($_POST as $name => $value) { ?>
                <?php if (is_scalar($value)) { ?>
                    <input type="hidden"
                           name="<?php echo htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'); ?>"
                           value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>">
                <?php } ?>
            <?php } ?>
            <input type="hidden" name="repost" value="1">
            <noscript><button type="submit">Continue</button></noscript>
        </form>
        <script>document.getElementById('ltiRepostForm').submit();</script>
    </body>
    </html>
    <?php
    exit;
}

// The helper flag is not part of the signed LTI response.
if (!empty($_POST['repost'])) {
    unset($_POST['repost'], $_REQUEST['repost']);
}

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

// Build the OAuth request from the original signed LTI parameters only.
// The local `repost` helper must never become part of the OAuth signature.
// Query parameters in the Deep Linking return URL (for example cidReq)
// are part of the OAuth parameter set and therefore must be included.
$oauthParameters = array_merge($_GET, $_POST);
unset($oauthParameters['repost']);

$request = OAuthRequest::from_request(
    'POST',
    api_get_path(WEB_PLUGIN_PATH).'ims_lti/item_return.php',
    $oauthParameters
);
$request->sign_request($hmacMethod, $consumer, '');
$signature = $request->get_parameter('oauth_signature');
$receivedSignature = isset($_POST['oauth_signature']) ? (string) $_POST['oauth_signature'] : '';

if (empty($receivedSignature) || !hash_equals((string) $signature, $receivedSignature)) {
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

$courseUrl = api_get_course_url();
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
        (function () {
            var courseUrl = <?php echo json_encode($courseUrl); ?>;

            // Deep Linking opened in a new window: refresh the original
            // Chamilo course and close the selection window.
            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.location.href = courseUrl;
                } catch (e) {
                    // Fall through to a redirect in this window.
                }

                window.close();

                if (!window.closed) {
                    window.location.href = courseUrl;
                }

                return;
            }

            // Deep Linking opened in an iframe.
            if (window.parent && window.parent !== window) {
                window.parent.location.href = courseUrl;
                return;
            }

            // Fallback for a normal top-level window.
            window.location.href = courseUrl;
        }());
    </script>
</body>
</html>
