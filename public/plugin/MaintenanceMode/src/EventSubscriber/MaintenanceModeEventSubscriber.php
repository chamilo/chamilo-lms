<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container as ChamiloContainer;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class MaintenanceModeEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PluginHelper $pluginHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 128],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isAlwaysAllowedRequest($event)) {
            $this->bootstrapLegacyContainer($event);

            return;
        }

        if (!$this->isPluginEnabled()) {
            $this->bootstrapLegacyContainer($event);

            return;
        }

        $response = $this->createMaintenanceResponse();

        if ($request->request->getBoolean('load_legacy')) {
            $this->bootstrapLegacyContainer($event);
            $response->send();
            exit;
        }

        $event->setResponse($response);
    }

    private function isPluginEnabled(): bool
    {
        try {
            return $this->pluginHelper->isPluginEnabled('MaintenanceMode');
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAlwaysAllowedRequest(RequestEvent $event): bool
    {
        $request = $event->getRequest();

        $candidates = [
            $request->getPathInfo(),
            parse_url($request->getRequestUri(), PHP_URL_PATH) ?: '',
            parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '',
            $_SERVER['SCRIPT_NAME'] ?? '',
            $_SERVER['PHP_SELF'] ?? '',
        ];

        foreach ($candidates as $path) {
            if ($this->isAlwaysAllowedPath((string) $path)) {
                return true;
            }
        }

        return false;
    }

    private function isAlwaysAllowedPath(string $path): bool
    {
        if ('/' === $path || '' === $path) {
            return false;
        }

        foreach ($this->getAllowedPathPrefixes() as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * These paths must remain available so admins can login, load assets and disable the plugin.
     *
     * Legacy admin pages are direct PHP scripts loaded through public/main/inc/global.inc.php.
     * In that flow, getPathInfo() may not always be enough, so the subscriber also checks
     * REQUEST_URI, SCRIPT_NAME and PHP_SELF before blocking the request.
     */
    private function getAllowedPathPrefixes(): array
    {
        return [
            '/main/admin',
            '/main/inc',
            '/main/default_course_document',
            '/main/img',
            '/main/template',
            '/main/auth',
            '/main/authentication',
            '/login',
            '/logout',
            '/admin',
            '/plugin/MaintenanceMode',
            '/build',
            '/bundles',
            '/css',
            '/images',
            '/img',
            '/js',
            '/libs',
            '/favicon',
            '/robots.txt',
            '/_profiler',
            '/_wdt',
        ];
    }

    private function bootstrapLegacyContainer(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->request->getBoolean('load_legacy')) {
            return;
        }

        $kernel = $event->getKernel();

        if (!method_exists($kernel, 'getContainer')) {
            return;
        }

        try {
            $container = $kernel->getContainer();

            ChamiloContainer::setContainer($container);
            ChamiloContainer::setLegacyServices($container);
        } catch (\Throwable) {
            // Keep the request flow unchanged if legacy services are not ready yet.
        }
    }

    private function createMaintenanceResponse(): Response
    {
        return new Response(
            $this->getMaintenanceHtml(),
            Response::HTTP_SERVICE_UNAVAILABLE,
            [
                'Retry-After' => '3600',
                'X-Robots-Tag' => 'noindex, nofollow',
                'Cache-Control' => 'no-cache, private',
                'Content-Type' => 'text/html; charset=UTF-8',
            ]
        );
    }

    private function getMaintenanceHtml(): string
    {
        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Maintenance mode</title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;background:#f9fafb;color:#111827;">
    <main style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
        <section style="max-width:42rem;border:1px solid #e5e7eb;border-radius:1rem;background:white;padding:2rem;box-shadow:0 1px 3px rgba(0,0,0,.08);">
            <p style="margin:0 0 .5rem 0;font-size:.875rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#2563eb;">Maintenance mode</p>
            <h1 style="margin:0 0 1rem 0;font-size:2rem;line-height:1.2;">The platform is temporarily unavailable</h1>
            <p style="margin:0;color:#4b5563;line-height:1.6;">Please try again later. Administrators can still access the administration area to disable maintenance mode.</p>
        </section>
    </main>
</body>
</html>
HTML;
    }
}
