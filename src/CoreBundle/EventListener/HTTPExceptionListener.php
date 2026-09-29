<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Renders HttpExceptions as JSON for endpoints that are consumed by XHR rather than by a
 * browser navigation: the API ('/api/') and the legacy jqGrid endpoint (model.ajax.php).
 * Without this, a 403/404 raised in model.ajax.php would be turned into an HTML redirect by
 * ExceptionListener, which the grid cannot parse — leaving it silently empty. Returning a
 * proper JSON status lets the caller degrade gracefully instead.
 *
 * Runs before ExceptionListener (registration order in listeners.yml); once it sets a
 * response, ExceptionListener bails on its hasResponse() guard.
 */
final class HTTPExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $uri = $event->getRequest()->getRequestUri();

        $isJsonEndpoint = str_contains($uri, '/api/')
            || str_contains($uri, 'model.ajax.php');

        if (!($exception instanceof HttpException) || !$isJsonEndpoint) {
            return;
        }

        $response = new JsonResponse([
            'error' => $exception->getMessage(),
        ]);
        $response->setStatusCode($exception->getStatusCode());

        $event->setResponse($response);
    }
}
