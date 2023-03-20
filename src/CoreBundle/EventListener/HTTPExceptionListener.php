<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class HTTPExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!($exception instanceof HttpException) ||
            false === strpos($event->getRequest()->getRequestUri(), '/api/')
        ) {
            return;
        }

        $response = new JsonResponse([
            'error' => $exception->getMessage(),
        ]);
        $response->setStatusCode($exception->getStatusCode());

        $event->setResponse($response);
    }
}
