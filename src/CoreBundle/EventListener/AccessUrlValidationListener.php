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

        // Do not run access URL validation during installation.
        // This avoids crashes when APP_INSTALLED is incorrect or the DB schema is not ready.
        if (\is_string($path) && str_starts_with($path, '/main/install/')) {
            return;
        }

        // Prevent infinite redirection loop to the same error page
        if (\is_string($path) && str_starts_with($path, '/error/undefined-url')) {
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
            if (\is_string($path) && str_starts_with($path, $prefix)) {
                return;
            }
        }

        // If the database is not ready yet (fresh install), AccessUrlHelper may throw
        // while trying to query access_url table. In that case, we must silently skip.
        try {
            // Skip validation if multi-URL is not enabled
            if (!$this->accessUrlHelper->isMultiple()) {
                return;
            }
        } catch (Throwable $e) {
            error_log('AccessUrlValidationListener: DB not ready, skipping access URL validation. '.$e->getMessage());

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
            // Log host and URI for debugging (avoid exposing details to the user)
            $host = $request->getHost();
            $uri = $request->getUri();
            error_log(\sprintf('AccessUrlValidationListener: undefined access URL for host "%s", uri "%s". %s', $host, $uri, $e->getMessage()));

            // Redirect to custom error page
            $url = $this->router->generate('undefined_url_error');
            $event->setResponse(new RedirectResponse($url));
        }
    }
}
