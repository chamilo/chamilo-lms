<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require_once __DIR__.'/../../main/inc/global.inc.php';

$request = Request::createFromGlobals();

$response = new JsonResponse(
    null,
    Response::HTTP_OK,
    ['content-type' => 'application/json']
);

try {
    $pathInfo = $request->getPathInfo();

    if (empty($pathInfo) || '/' === $pathInfo) {
        throw new BadRequestHttpException('Path info is missing.');
    }

    $resource = LtiNamesRoleProvisioningService::getResource($request, $response);
    $resource->validate();
    $resource->process();
} catch (HttpExceptionInterface $exception) {
    foreach ($exception->getHeaders() as $headerKey => $headerValue) {
        $response->headers->set($headerKey, $headerValue);
    }

    $response
        ->setStatusCode($exception->getStatusCode())
        ->setData(
            [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
                'request' => [
                    'method' => $request->getMethod(),
                    'url' => $request->getRequestUri(),
                    'accept' => $request->headers->get('accept'),
                ],
            ]
        );
}

$response->prepare($request);
$response->send();
