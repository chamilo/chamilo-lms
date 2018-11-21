<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

header('Content-Type: application/xml');

$url = api_get_path(WEB_PATH).'lti/os';

$em = Database::getManager();
$toolRepo = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool');

$headers = OAuthUtil::get_headers();

if (empty($headers['Authorization'])) {
    error_log('Authorization header missed');

    exit;
}

$authParams = OAuthUtil::split_header($headers['Authorization']);

if (empty($authParams) || empty($authParams['oauth_consumer_key']) || empty($authParams['oauth_signature'])) {
    error_log('Authorization params not found');

    exit;
}

$tools = $toolRepo->findBy(['consumerKey' => $authParams['oauth_consumer_key']]);
$toolIsFound = false;

/** @var ImsLtiTool $tool */
foreach ($tools as $tool) {
    $consumer = new OAuthConsumer($tool->getConsumerKey(), $tool->getSharedSecret());
    $hmacMethod = new OAuthSignatureMethod_HMAC_SHA1();

    $request = OAuthRequest::from_request('POST', $url);
    $request->sign_request($hmacMethod, $consumer, '');
    $signature = $request->get_parameter('oauth_signature');

    if ($signature === $authParams['oauth_signature']) {
        $toolIsFound = true;

        break;
    }
}

if (false === $toolIsFound) {
    error_log('Tool not found. Signature is not valid');

    exit;
}

$body = file_get_contents('php://input');
$bodyHash = base64_encode(sha1($body, true));

if ($bodyHash !== $authParams['oauth_body_hash']) {
    error_log('Authorization request not valid');

    exit;
}

$plugin = ImsLtiPlugin::create();

$process = $plugin->processServiceRequest();

echo $process;
