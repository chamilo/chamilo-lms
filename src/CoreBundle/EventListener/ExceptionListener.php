<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

/**
 * Unifies 403 handling for HTML pages (legacy/Symfony/Vue shell) with the yellow banner.
 * Keeps the profiler in dev for errors that are NOT 403.
 */
final class ExceptionListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $router
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        // If another listener already set a Response, do nothing
        if ($event->hasResponse()) {
            return;
        }

        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Leave /api routes to the JSON listener
        $path = $request->getPathInfo() ?? $request->getRequestUri();
        if (\is_string($path) && str_starts_with($path, '/api/')) {
            return;
        }

        // 403: legacy NotAllowed or Symfony AccessDenied
        if ($exception instanceof NotAllowedException
            || $exception instanceof AccessDeniedHttpException
            || $exception instanceof AccessDeniedException
        ) {
            // If no token (not logged in), redirect to login with "redirect" back param
            if (null === $this->tokenStorage->getToken()) {
                $loginUrl = $this->router->generate(
                    'login',
                    ['redirect' => $request->getSchemeAndHttpHost().$request->getRequestUri()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $event->setResponse(new RedirectResponse($loginUrl));

                return;
            }

            $message = $exception instanceof NotAllowedException
                ? $exception->getMessage()
                : 'You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.';

            $severity = $exception instanceof NotAllowedException ? $exception->getSeverity() : 'warning';

            $html = $this->twig->render('@ChamiloCore/Exception/not_allowed_message.html.twig', [
                'message' => $message,
                'severity' => $severity,
            ]);

            // Important: status 403 for consistency
            $event->setResponse(new Response($html, Response::HTTP_FORBIDDEN));

            return;
        }

        // In dev/test, let the Symfony exception handler/profiler render the page
        if (isset($_SERVER['APP_ENV']) && \in_array($_SERVER['APP_ENV'], ['dev', 'test'], true)) {
            return;
        }

        $message = $this->twig->render(
            '@ChamiloCore/Exception/error.html.twig',
            [
                'exception' => $exception,
            ]
        );

        // Build a generic error response
        $response = new Response();
        $response->setContent($message);

        // HttpExceptionInterface carries status code and headers
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Send the modified response back to the event
        $event->setResponse($response);
    }
}
