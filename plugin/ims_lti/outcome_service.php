<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once './OAuthSimple.php';

header('Content-Type: application/xml');

if (empty($_GET['t'])) {
    exit;
}

$em = Database::getManager();
/** @var ImsLtiTool $tool */
$tool = $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', (int) $_GET['t']);

if (empty($tool)) {
    exit;
}

$body = file_get_contents('php://input');
$bodyHash = OAuthSimple::generateBodyHash($body);

$url = api_get_path(WEB_PATH).'ims_lti/outcome_service/'.$tool->getId();
$headers = getallheaders();

$params = OAuthSimple::getAuthorizationParams($headers['Authorization']);

if (empty($params)) {
    exit;
}

$oauth = new OAuthSimple(
    $params['oauth_consumer_key'],
    $tool->getSharedSecret()
);
$oauth->setAction('POST');
$oauth->setSignatureMethod('HMAC-SHA1');
$result = $oauth->sign(
    [
        'path' => $url,
        'parameters' => [
            'oauth_body_hash' => $params['oauth_body_hash'],
            'oauth_nonce' => $params['oauth_nonce'],
            'oauth_timestamp' => $params['oauth_timestamp'],
            'oauth_signature_method' => $params['oauth_signature_method'],
        ],
    ]
);

$signatureValid = urldecode($result['signature']) == $params['oauth_signature'];
$bodyHashValid = $bodyHash === $params['oauth_body_hash'];

if (!$signatureValid || !$bodyHashValid) {
    exit;
}

$plugin = ImsLtiPlugin::create();

$process = $plugin->processServiceRequest();

echo $process;
