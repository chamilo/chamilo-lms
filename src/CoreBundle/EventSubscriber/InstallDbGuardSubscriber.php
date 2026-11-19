<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

use const PHP_SAPI;

/**
 * Sends a request to the web installer when the platform cannot serve it.
 *
 * Two distinct cases, handled on two different events:
 *
 * - An uninstalled code tree (`APP_INSTALLED` is not 1). This is checked on
 *   kernel.request, before anything touches the database or the session, and it
 *   needs no connection. public/index.php already does this for the main front
 *   controller, but public/legacy.php does not, so the guard stays as the shared
 *   gate.
 * - An installed platform whose database is unreachable or empty. This used to be
 *   probed on every request with a `SELECT` that the healthy path paid for on each
 *   page. It is checked on kernel.exception instead: a dead database surfaces as an
 *   exception (a failed connection, or the SessionNotFoundException of #7224), and
 *   only then does the guard step in. The healthy path runs no extra query, and
 *   every other exception reaches ErrorController untouched.
 *
 * The exception listener runs at a high priority so it redirects before the other
 * kernel.exception listeners (and ErrorController) try the same dead database and
 * throw again.
 */
final class InstallDbGuardSubscriber implements EventSubscriberInterface
{
    /**
     * Paths the installer and the asset handlers own; the guard never redirects them.
     */
    private const array EXCLUDED_PREFIXES = [
        '/main/install/',
        '/_wdt',
        '/_profiler',
        '/build/',
        '/bundles/',
        '/favicon',
    ];

    public function __construct(
        private readonly Connection $connection,
        // Defaulted so the service wires with the connection alone. The HTTP kernel events
        // below never fire under the console, but a WebTestCase boots the kernel from the
        // command line, so the CLI skip keeps the guard out of the functional suite.
        private readonly string $sapi = PHP_SAPI
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2048],
            KernelEvents::EXCEPTION => ['onKernelException', 2048],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || 'cli' === $this->sapi) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isExcludedPath($request->getPathInfo())) {
            return;
        }

        if (!$this->isAppInstalled($request)) {
            $this->redirectToInstaller($event);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || 'cli' === $this->sapi || $event->hasResponse()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->isExcludedPath($request->getPathInfo())) {
            return;
        }

        if (!$this->isAppInstalled($request)) {
            $this->redirectToInstaller($event);

            return;
        }

        // The platform claims to be installed. Only an interrupted install belongs in the
        // installer: the database answers but holds no configuration yet. A database that
        // is simply unreachable is an outage; sending every visitor to the installer then
        // is needless. Leave it to ErrorController, which renders the generic error page.
        if ($this->databaseReachableButUninitialized()) {
            $this->redirectToInstaller($event);
        }
    }

    private function isExcludedPath(string $path): bool
    {
        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isAppInstalled(Request $request): bool
    {
        $flag = (string) (
            $request->server->get('APP_INSTALLED')
            ?? $_ENV['APP_INSTALLED']
            ?? getenv('APP_INSTALLED')
            ?? '0'
        );

        return '1' === trim($flag, " \t\n\r\0\x0B'\"");
    }

    /**
     * True only for an interrupted install: the database answers a bare query but holds no
     * configuration yet (no row, or not even a `settings` table, as a 1.11.x database or a
     * half-written schema). An unreachable database returns false, so an outage is left to
     * ErrorController rather than sent to the installer.
     */
    private function databaseReachableButUninitialized(): bool
    {
        try {
            $this->connection->executeQuery($this->connection->getDatabasePlatform()->getDummySelectSQL());
        } catch (Throwable) {
            return false;
        }

        try {
            $hasRow = $this->connection->fetchOne('SELECT 1 FROM settings LIMIT 1');

            return false === $hasRow || null === $hasRow;
        } catch (Throwable) {
            return true;
        }
    }

    private function redirectToInstaller(RequestEvent $event): void
    {
        $event->setResponse(new RedirectResponse('/main/install/index.php', 302));
        $event->stopPropagation();
    }
}
