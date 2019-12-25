<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\Token;
use Firebase\JWT\JWT;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();

$tokenRequest = new LtiTokenRequest();

try {
    if ($plugin->get('enabled') !== 'true') {
        throw new Exception('unsupported');
    }

    if ('POST' !== $_SERVER['REQUEST_METHOD'] ||
        empty($_SERVER['CONTENT_TYPE']) ||
        $_SERVER['CONTENT_TYPE'] !== 'application/x-www-form-urlencoded'
    ) {
        throw new Exception('invalid_request');
    }

    $clientAssertion = !empty($_POST['client_assertion']) ? $_POST['client_assertion'] : '';
    $clientAssertionType = !empty($_POST['client_assertion_type']) ? $_POST['client_assertion_type'] : '';
    $grantType = !empty($_POST['grant_type']) ? $_POST['grant_type'] : '';
    $scope = !empty($_POST['scope']) ? $_POST['scope'] : '';

    if (empty($clientAssertionType) || empty($grantType)) {
        throw new Exception('invalid_request');
    }

    if ('urn:ietf:params:oauth:client-assertion-type:jwt-bearer' !== $clientAssertionType ||
        $grantType !== 'client_credentials'
    ) {
        throw new Exception('unsupported_grant_type');
    }

    $tool = $tokenRequest->validateClientAssertion($clientAssertion);

    try {
        $jwt = JWT::decode($clientAssertion, $tool->publicKey, ['RS256']);
    } catch (Exception $exception) {
        throw new Exception('invalid_client');
    }

    $allowedScopes = $tokenRequest->validateScope($scope, $tool);

    $now = time();

    $token = new Token();
    $token
        ->generateHash()
        ->setTool($tool)
        ->setScope($allowedScopes)
        ->setCreatedAt($now)
        ->setExpiresAt($now + Token::TOKEN_LIFETIME);

    $em = Database::getManager();
    $em->persist($token);
    $em->flush();

    $json = [
        'access_token' => $token->getHash(),
        'token_type' => 'Bearer',
        'expires_in' => Token::TOKEN_LIFETIME,
        'scope' => $token->getScopeInString(),
    ];
} catch (Exception $exception) {
    header("HTTP/1.0 400 Bad Request");

    $json = ['error' => $exception->getMessage()];
}

header('Content-Type: application/json');

echo json_encode($json);
