<?php

/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$request = HttpRequest::createFromGlobals();

$response = new JsonResponse([], Response::HTTP_METHOD_NOT_ALLOWED);

if ('POST' === $request->getMethod()) {
    $token = base64_encode(uniqid());

    $response->setStatusCode(Response::HTTP_OK);
    $response->setData(['auth-token' => $token]);
}

$response->send();
