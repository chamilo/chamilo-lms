<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Unifies 403 handling for HTML pages (legacy/Symfony/Vue shell) with the yellow banner.
 * Keeps the profiler in dev for errors that are NOT 403.
 */
final class ExceptionListener
{
    public function __construct(
        #[Autowire(env: 'APP_ENV')]
        private readonly string $environment,
        private readonly UserHelper $userHelper,
        private readonly UrlGeneratorInterface $router
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        // If another listener already set a Response, do nothing
        if ($event->hasResponse()) {
            return;
        }

        // In dev/test, let the Symfony exception handler/profiler render the page
        if (\in_array($this->environment, ['dev', 'test'])) {
            return;
        }

        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if ($request->hasSession()) {
            $request
                ->getSession()
                ->getFlashBag()
                ->add(
                    'error',
                    $exception->getMessage() ?: 'You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'
                )
            ;
        }

        // Leave /api routes to the JSON listener
        $path = $request->getPathInfo() ?: $request->getRequestUri();
        if (str_starts_with($path, '/api/')) {
            return;
        }

        // 403: legacy NotAllowed or Symfony AccessDenied
        if ($exception instanceof NotAllowedException
            || $exception instanceof AccessDeniedHttpException
        ) {
            // If no token (not logged in), redirect to the login page with "redirect" back param
            if (!$this->userHelper->getCurrent()) {
                // Use only a relative path (path + query) for the redirect parameter
                $redirectPath = $request->getRequestUri() ?: '/';

                $loginUrl = $this->router->generate(
                    'login',
                    ['redirect' => $redirectPath],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $event->setResponse(new RedirectResponse($loginUrl));

                return;
            }

            $indexUrl = $this->router->generate(
                'index',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $event->setResponse(new RedirectResponse($indexUrl));
        }
    }
}
