<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Controller\Scim\AbstractScimController;
use Chamilo\CoreBundle\Exception\ScimException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ScimExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/scim/v2')) {
            return;
        }

        $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        $detail = Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];

        $throwable = $event->getThrowable();

        if ($throwable instanceof ScimException) {
            $status = $throwable->getStatusCode();
            $detail = $throwable->getMessage();
        } elseif ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $detail = $throwable->getMessage() ?: Response::$statusTexts[$status] ?? 'Error';
        }

        $response = new JsonResponse(
            [
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'detail' => $detail,
                'status' => (string) $status,
                'message' => $throwable->getMessage(),
            ],
            $status
        );

        $response->headers->set('Content-Type', AbstractScimController::SCIM_CONTENT_TYPE);

        $event->setResponse($response);
    }
}
