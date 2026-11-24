<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

// The service path follows the script name (ags2.php/{course}/lineitems/...). global.inc.php
// hands the request to the kernel, which has no route for that path nor for the PUT and DELETE
// methods, so keep both apart while bootstrapping.
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$servicePath = (string) ($_SERVER['PATH_INFO'] ?? '');

if ('' === $servicePath && isset($_SERVER['SCRIPT_NAME'])) {
    $uriPath = (string) parse_url($requestUri, PHP_URL_PATH);

    if (str_starts_with($uriPath, (string) $_SERVER['SCRIPT_NAME'])) {
        $servicePath = substr($uriPath, strlen((string) $_SERVER['SCRIPT_NAME']));
    }
}

if ('' !== $servicePath && isset($_SERVER['SCRIPT_NAME'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME']
        .(empty($_SERVER['QUERY_STRING']) ? '' : '?'.$_SERVER['QUERY_STRING']);
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    unset($_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO']);
}

if (!in_array($requestMethod, ['GET', 'POST'], true)) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

require_once __DIR__.'/../../main/inc/global.inc.php';

$request = Container::getRequest();
$request->setMethod($requestMethod);

$response = new JsonResponse(
    null,
    Response::HTTP_OK,
    ['content-type' => 'application/json']
);

try {
    if ('' === $servicePath || '/' === $servicePath) {
        throw new BadRequestHttpException('Path info is missing.');
    }

    $resource = LtiAssignmentGradesService::getResource($request, $response, $servicePath);
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
                    'url' => $requestUri,
                    'accept' => $request->headers->get('accept'),
                ],
            ]
        );
}

$response->prepare($request);
$response->send();
