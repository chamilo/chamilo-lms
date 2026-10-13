<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Firebase\JWT\JWT;

require_once __DIR__.'/../../main/inc/global.inc.php';

// LTI 1.3 Deep Linking also returns with a cross-site POST. Re-post once
// from the Chamilo origin when the browser did not send the session cookie.
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

if (!empty($_POST['repost'])) {
    unset($_POST['repost'], $_REQUEST['repost']);
}

api_protect_course_script();
api_block_anonymous_users(false);

$jwt = empty($_REQUEST['JWT']) ? '' : $_REQUEST['JWT'];

$em = Database::getManager();
$course = api_get_course_entity(api_get_course_int_id());

try {
    if (empty($jwt)) {
        throw new Exception('Token is missing');
    }

    $jwtParts = explode('.', $jwt, 3);
    $payloadStr = JWT::urlsafeB64Decode($jwtParts[1]);
    $payload = json_decode($payloadStr, true);

    if (empty($payload)) {
        throw new Exception('Token payload is empty');
    }

    if (empty($payload['https://purl.imsglobal.org/spec/lti-dl/claim/data'])) {
        throw new Exception('Data claim is missing');
    }

    if ($payload['aud'] !== ImsLtiPlugin::getIssuerUrl()) {
        throw new Exception('Audience not valid');
    }

    $toolId = str_replace('tool:', '', $payload['https://purl.imsglobal.org/spec/lti-dl/claim/data']);
    /** @var ImsLtiTool $ltiTool */
    $ltiTool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $toolId);

    if (empty($ltiTool)) {
        throw new Exception('LTI tool not found');
    }

    if ($payload['iss'] !== $ltiTool->getClientId()) {
        throw new Exception('Consumer not valid');
    }

    $decodedJwt = JWT::decode($jwt, $ltiTool->publicKey, ['RS256']);

    if (empty($decodedJwt->{'https://purl.imsglobal.org/spec/lti-dl/claim/content_items'})) {
        throw new Exception('Content items are missing');
    }

    foreach ($decodedJwt->{'https://purl.imsglobal.org/spec/lti-dl/claim/content_items'} as $contentItemClaim) {
        /** @var LtiContentItemType|null $contentItem */
        $contentItem = null;

        switch ($contentItemClaim->type) {
            case 'ltiResourceLink':
                $contentItem = new LtiResourceLink($contentItemClaim);
                break;
            default:
                break;
        }

        if (null !== $contentItem) {
            $contentItem->save($ltiTool, $course);
        }
    }
} catch (Exception $exception) {
    $message = Display::return_message($exception->getMessage(), 'error');

    api_not_allowed(true, $message);
}

$plugin = ImsLtiPlugin::create();

Display::addFlash(
    Display::return_message($plugin->get_lang('ToolAdded'), 'success')
);
$courseUrl = api_get_course_url();
?>
<!DOCTYPE html>
<body>
<script>
    (function () {
        var courseUrl = <?php echo json_encode($courseUrl); ?>;

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

        if (window.parent && window.parent !== window) {
            window.parent.location.href = courseUrl;
            return;
        }

        window.location.href = courseUrl;
    }());
</script>
</body>
