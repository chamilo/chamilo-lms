<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\Token;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();

$request = Request::createFromGlobals();

$response = new JsonResponse();

try {
    if ($plugin->get('enabled') !== 'true' ||
        $request->getMethod() !== Request::METHOD_POST ||
        $request->server->get('CONTENT_TYPE') !== 'application/x-www-form-urlencoded'
    ) {
        throw new Exception('invalid_request');
    }

    $clientAssertion = $request->request->get('client_assertion');
    $clientAssertionType = $request->request->get('client_assertion_type');
    $grantType = $request->request->get('grant_type');
    $scope = $request->request->get('scope');

    if ('urn:ietf:params:oauth:client-assertion-type:jwt-bearer' !== $clientAssertionType
        || $grantType !== 'client_credentials'
    ) {
        throw new Exception('unsupported_grant_type');
    }

    $tokenRequest = new LtiTokenRequest();

    try {
        $tokenRequest->validateClientAssertion($clientAssertion);
        $tokenRequest->decodeJwt($clientAssertion);
    } catch (Exception $exception) {
        throw new Exception('invalid_client');
    }

    try {
        $allowedScopes = $tokenRequest->validateScope($scope);
    } catch (Exception $exception) {
        throw new Exception('invalid_scope');
    }

    $token = $tokenRequest->generateToken($allowedScopes);

    $em = Database::getManager();
    $em->persist($token);
    $em->flush();

    $data = [
        'access_token' => $token->getHash(),
        'token_type' => 'Bearer',
        'expires_in' => Token::TOKEN_LIFETIME,
        'scope' => $token->getScopeInString(),
    ];
} catch (Exception $exception) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);

    $data = ['error' => $exception->getMessage()];
}

$response->setData($data);
$response->send();
