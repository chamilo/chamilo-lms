<?php
/* For license terms, see /license.txt */

use Chamilo\LtiBundle\Entity\ExternalTool;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/OAuth1.php';

header('Content-Type: application/xml');

$url = api_get_path(WEB_PATH).'lti/os';

$em = Database::getManager();
$toolRepo = $em->getRepository(ExternalTool::class);

$headers = ImsLtiOAuth1::getHeaders();

if (empty($headers['Authorization'])) {
    error_log('Authorization header missed');

    exit;
}

$authParams = ImsLtiOAuth1::parseAuthorizationHeader($headers['Authorization']);

if (empty($authParams) || empty($authParams['oauth_consumer_key']) || empty($authParams['oauth_signature'])) {
    error_log('Authorization params not found');

    exit;
}

$tools = $toolRepo->findBy(['consumerKey' => $authParams['oauth_consumer_key']]);
$toolIsFound = false;

/** @var ExternalTool $tool */
foreach ($tools as $tool) {
    if ((string) $tool->getConsumerKey() !== (string) $authParams['oauth_consumer_key']) {
        continue;
    }

    $signatureIsValid = ImsLtiOAuth1::validateRequest(
        'POST',
        $url,
        $authParams,
        (string) $tool->getSharedSecret()
    );

    if ($signatureIsValid) {
        $toolIsFound = true;

        break;
    }
}

if (false === $toolIsFound) {
    error_log('Tool not found. Signature is not valid');

    exit;
}

$body = file_get_contents('php://input');
$bodyHash = ImsLtiOAuth1::buildBodyHash($body);

if ($bodyHash !== $authParams['oauth_body_hash']) {
    error_log('Authorization request not valid');

    exit;
}

$plugin = ImsLtiPlugin::create();

$process = $plugin->processServiceRequest();

echo $process;
