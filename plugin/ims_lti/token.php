<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Firebase\JWT\JWT;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();

try {
    if ($plugin->get('enabled') !== 'true') {
        throw new Exception('unsupported');
    }

    $contenttype = isset($_SERVER['CONTENT_TYPE']) ? explode(';', $_SERVER['CONTENT_TYPE'], 2)[0] : '';

    if ('POST' !== $_SERVER['REQUEST_METHOD'] || $contenttype !== 'application/x-www-form-urlencoded') {
        throw new Exception('invalid_request');
    }

    $clientAssertion = !empty($_POST['client_assertion']) ? $_POST['client_assertion'] : '';
    $clientAssertionType = !empty($_POST['client_assertion_type']) ? $_POST['client_assertion_type'] : '';
    $grantType = !empty($_POST['grant_type']) ? $_POST['grant_type'] : '';
    $scope = !empty($_POST['scope']) ? $_POST['scope'] : '';

    if (empty($clientAssertion) || empty($clientAssertionType) || empty($grantType) || empty($scope)) {
        throw new Exception('invalid_request');
    }

    if ('urn:ietf:params:oauth:client-assertion-type:jwt-bearer' !== $clientAssertionType ||
        $grantType !== 'client_credentials'
    ) {
        throw new Exception('unsupported_grant_type');
    }

    $parts = explode('.', $clientAssertion);

    if (count($parts) !== 3) {
        throw new Exception('invalid_request');
    }

    $payload = JWT::urlsafeB64Decode($parts[1]);
    $claims = json_decode($payload, true);

    if (empty($claims) || empty($claims['sub'])) {
        throw new Exception('invalid_request');
    }

    $em = Database::getManager();

    /** @var ImsLtiTool $tool */
    $tool = $em
        ->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool')
        ->findOneBy(['clientId' => $claims['sub']]);

    if (!$tool || empty($tool->publicKey)) {
        throw new Exception('invalid_client');
    }

    try {
        $jwt = JWT::decode($clientAssertion, $tool->publicKey, ['RS256']);
    } catch (Exception $exception) {
        throw new Exception('invalid_client');
    }

    $requestedScopes = explode(' ', $scope);
    $scopes = $requestedScopes;

    if (empty($scopes)) {
        throw new Exception('invalid_scope');
    }

    $json = [
        'access_token' => '',
        'token_type' => 'Bearer',
        'expires_in' => '',
        'scope' => implode(' ', $scopes),
    ];
} catch (Exception $exception) {
    header("HTTP/1.0 400 Bad Request");

    $json = ['error' => $exception->getMessage()];
}

header('Content-Type: application/json');

echo json_encode($json);
