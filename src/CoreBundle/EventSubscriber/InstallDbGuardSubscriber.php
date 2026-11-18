<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

use const PHP_SAPI;

final class InstallDbGuardSubscriber implements EventSubscriberInterface
{
    private const string VERSION_KEY = 'chamilo_database_version';

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

            // Doctrine's migration metadata is the primary signal. Rows there mean the
            // database went through the migrations, or is going through them right now:
            // either way the installer must stay reachable, because APP_INSTALLED=1 is
            // written to .env at step 5, before the migration runs. Redirecting on a
            // half-finished migration would loop.
            //
            // A 1.11.x database carries an unrelated `version` table, but it never reaches
            // this point: it has no `settings` table, so detectSettingsTable() above has
            // already redirected it.
            if ($this->hasMigrationMetadata()) {
                $isHealthy = true;

                return;
            }

            // Fallback for an installation created before the installer seeded that
            // metadata: the presence of chamilo_database_version is the only marker it
            // has. Never its value — that setting is deprecated and carries a stale
            // default. Column names differ across the 1.11.x and 2.x schemas.
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
                // No metadata and no marker: a 1.11.x database before its migration.
                $this->redirectToInstaller($event);

                return;
            }

            $isHealthy = true;
        } catch (Throwable) {
            // The database is unreachable on an installed platform: an outage, not a
            // greenfield server. Show a self-contained error page in place instead of
            // sending every visitor to the installer, which would be needless and, during
            // an outage, is the path a fresh install could abuse. The reachable but
            // not-initialized states above keep their own redirect to the installer.
            $this->serveDatabaseOutagePage($event);
        }
    }

    /**
     * Tells whether Doctrine's migration metadata table holds any executed migration.
     */
    private function hasMigrationMetadata(): bool
    {
        try {
            return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM version') > 0;
        } catch (Throwable) {
            // The table does not exist yet.
            return false;
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

    /**
     * Serves a self-contained error page for a database outage: no database, no settings,
     * no theme and no Twig, so it renders when nothing else can. It stays in place at the
     * requested URL rather than redirecting to the installer.
     */
    private function serveDatabaseOutagePage(RequestEvent $event): void
    {
        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<meta name="robots" content="noindex,nofollow">'
            .'<title>Something seems to have gone wrong</title>'
            .'<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
            .'background:#f7f8fa;color:#2c3e50;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;'
            .'line-height:1.5}.box{max-width:32rem;margin:1.5rem;padding:2.5rem 2rem;background:#fff;'
            .'border-radius:.75rem;box-shadow:0 1px 3px rgba(0,0,0,.08);text-align:center}'
            .'h1{font-size:1.25rem;margin:.5rem 0}p{color:#6b7280;margin:.25rem 0}</style></head>'
            .'<body><div class="box"><h1>Something seems to have gone wrong</h1>'
            .'<p>Please try again later.</p></div></body></html>';

        $event->setResponse(new Response($html, Response::HTTP_SERVICE_UNAVAILABLE));
        $event->stopPropagation();
    }
}
