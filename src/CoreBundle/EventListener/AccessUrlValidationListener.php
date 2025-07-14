<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

/**
 * This event listener checks whether the current request is using a valid, registered AccessUrl.
 * If not, it redirects to a custom error page (/error/undefined-url).
 */
class AccessUrlValidationListener
{
    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly RouterInterface $router
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        // Only handle the main request, not subrequests.
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Prevent infinite redirection loop to the same error page
        if (str_starts_with($path, '/error/undefined-url')) {
            return;
        }

        // Skip validation for static assets and special files
        $excludedPrefixes = [
            '/build/',                // Webpack build assets
            '/theme/',                // Legacy theme assets
            '/favicon.ico',           // Favicon
            '/robots.txt',            // Robots file
            '/apple-touch-icon.png',  // Mobile shortcut icon
        ];

        foreach ($excludedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        // Skip validation if multi-URL is not enabled
        if (!$this->accessUrlHelper->isMultiple()) {
            return;
        }

        try {
            // Try to get the current AccessUrl from the request
            $accessUrl = $this->accessUrlHelper->getCurrent();

            // If it's null, throw an exception to trigger redirection
            if (null === $accessUrl) {
                throw new RuntimeException('AccessUrl not defined');
            }
        } catch (Throwable $e) {
            // Log host and URI for debugging
            $host = $request->getHost();
            $uri = $request->getUri();

            // Redirect to custom error page
            $url = $this->router->generate('undefined_url_error');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}
