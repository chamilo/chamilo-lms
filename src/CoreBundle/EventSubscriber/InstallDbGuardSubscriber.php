<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

use const PHP_SAPI;

final class InstallDbGuardSubscriber implements EventSubscriberInterface
{
    private const VERSION_KEY = 'chamilo_database_version';

    public function __construct(
        private readonly Connection $connection
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 2048]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || PHP_SAPI === 'cli') {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        if (
            str_starts_with($path, '/main/install/')
            || str_starts_with($path, '/_wdt')
            || str_starts_with($path, '/_profiler')
            || str_starts_with($path, '/build/')
            || str_starts_with($path, '/bundles/')
            || str_starts_with($path, '/favicon')
        ) {
            return;
        }

        $installedFlag = (string) (
            $request->server->get('APP_INSTALLED')
            ?? $_ENV['APP_INSTALLED']
            ?? getenv('APP_INSTALLED')
            ?? '0'
        );
        $installedFlag = trim($installedFlag, " \t\n\r\0\x0B'\"");

        if ('1' !== $installedFlag) {
            $this->redirectToInstaller($event);

            return;
        }

        // Cache "healthy" per PHP-FPM worker
        static $isHealthy = false;
        if ($isHealthy) {
            return;
        }

        // Cache which settings table to use per PHP-FPM worker
        static $settingsTable = null;

        try {
            // If DB is down/unreachable, this will fail quickly
            $this->connection->executeQuery($this->connection->getDatabasePlatform()->getDummySelectSQL());

            if (null === $settingsTable) {
                $settingsTable = $this->detectSettingsTable();
                if (null === $settingsTable) {
                    $this->redirectToInstaller($event);

                    return;
                }
            }

            // Cheap proof: at least 1 row
            $hasRow = $this->connection->fetchOne("SELECT 1 FROM {$settingsTable} LIMIT 1");
            if (false === $hasRow || null === $hasRow) {
                $this->redirectToInstaller($event);

                return;
            }

            // Read DB version (try common column names; only runs until first success)
            $version = null;
            foreach (['selected_value', 'value', 'c_value'] as $col) {
                try {
                    $version = $this->connection->fetchOne(
                        "SELECT {$col} FROM {$settingsTable} WHERE variable = :var LIMIT 1",
                        ['var' => self::VERSION_KEY]
                    );
                    if (!empty($version)) {
                        break;
                    }
                } catch (Throwable) {
                    // Try next column
                }
            }

            if (empty($version)) {
                $this->redirectToInstaller($event);

                return;
            }

            $isHealthy = true;
        } catch (Throwable) {
            $this->redirectToInstaller($event);
        }
    }

    private function detectSettingsTable(): ?string
    {
        // Fallback to settings
        try {
            $this->connection->fetchOne('SELECT 1 FROM settings LIMIT 1');

            return 'settings';
        } catch (Throwable) {
        }

        return null;
    }

    private function redirectToInstaller(RequestEvent $event): void
    {
        $event->setResponse(new RedirectResponse('/main/install/index.php', 302));
        $event->stopPropagation();
    }
}
