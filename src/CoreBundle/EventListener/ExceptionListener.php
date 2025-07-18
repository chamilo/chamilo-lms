<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class ExceptionListener
{
    protected Environment $twig;
    protected TokenStorageInterface $tokenStorage;
    protected UrlGeneratorInterface $router;

    public function __construct(Environment $twig, TokenStorageInterface $tokenStorage, UrlGeneratorInterface $router)
    {
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
    }

    public function __invoke(ExceptionEvent $event): void
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if ($exception instanceof NotAllowedException) {
            if (null === $this->tokenStorage->getToken()) {
                $loginUrl = $this->router->generate(
                    'login',
                    ['redirect' => $request->getSchemeAndHttpHost().$request->getRequestUri()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $event->setResponse(new RedirectResponse($loginUrl));

                return;
            }

            $severity = $exception->getSeverity();
            $message = $exception->getMessage();
            if (\in_array($severity, ['info', 'warning', 'success'], true)) {
                $html = $this->twig->render('@ChamiloCore/Exception/not_allowed_message.html.twig', [
                    'message' => $message,
                    'severity' => $severity,
                ]);
                $event->setResponse(new Response($html, 200));

                return;
            }
        }

        if (isset($_SERVER['APP_ENV']) && \in_array($_SERVER['APP_ENV'], ['dev', 'test'], true)) {
            return;
        }

        $message = $this->twig->render(
            '@ChamiloCore/Exception/error.html.twig',
            [
                'exception' => $exception,
            ]
        );

        // Customize your response object to display the exception details
        $response = new Response();
        $response->setContent($message);

        // HttpExceptionInterface is a special type of exception that
        // holds status code and header details
        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // sends the modified response object to the event
        $event->setResponse($response);
    }
}
