<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\EventSubscriber\InstallDbGuardSubscriber;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * The guard's whole contract. An uninstalled tree is sent to the installer on
 * kernel.request, before any database or session is touched. On kernel.exception only an
 * interrupted install — the database answers but holds no configuration — goes to the
 * installer; an unreachable database is an outage and is left to ErrorController, which
 * renders the generic error page rather than sending every visitor to the installer.
 */
final class InstallDbGuardSubscriberTest extends TestCase
{
    public function testUninstalledRequestIsSentToTheInstaller(): void
    {
        $event = $this->requestEvent($this->request('/home', installed: false));

        $this->guard($this->unusedConnection())->onKernelRequest($event);

        $this->assertRedirectsToInstaller($event->getResponse());
    }

    public function testInstalledRequestIsNotTouchedAndNeverQueriesTheDatabase(): void
    {
        $event = $this->requestEvent($this->request('/home', installed: true));

        // unusedConnection() fails the test if the request path opens any query.
        $this->guard($this->unusedConnection())->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testInstallerPathIsNeverRedirected(): void
    {
        $event = $this->requestEvent($this->request('/main/install/index.php', installed: false));

        $this->guard($this->unusedConnection())->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testSubRequestIsIgnored(): void
    {
        $event = $this->requestEvent($this->request('/home', installed: false), HttpKernelInterface::SUB_REQUEST);

        $this->guard($this->unusedConnection())->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testExceptionWithUnreachableDatabaseIsLeftToErrorController(): void
    {
        $event = $this->exceptionEvent($this->request('/home', installed: true));

        $this->guard($this->unreachableDatabase())->onKernelException($event);

        self::assertFalse(
            $event->hasResponse(),
            'An outage must show the error page, not redirect every visitor to the installer.'
        );
    }

    public function testExceptionWithReachableButEmptyDatabaseIsSentToTheInstaller(): void
    {
        $event = $this->exceptionEvent($this->request('/home', installed: true));

        $this->guard($this->reachableEmptyDatabase())->onKernelException($event);

        $this->assertRedirectsToInstaller($event->getResponse());
    }

    public function testExceptionWithHealthyDatabaseIsLeftToErrorController(): void
    {
        $event = $this->exceptionEvent($this->request('/home', installed: true));

        $this->guard($this->reachableInitializedDatabase())->onKernelException($event);

        self::assertFalse($event->hasResponse(), 'A normal error on a healthy platform must reach ErrorController.');
    }

    public function testExceptionOnTheInstallerPathIsIgnored(): void
    {
        $event = $this->exceptionEvent($this->request('/main/install/index.php', installed: true));

        $this->guard($this->unusedConnection())->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }

    private function guard(Connection $connection): InstallDbGuardSubscriber
    {
        // A served request never runs under the console SAPI; the default 'cli' would
        // make the guard skip every case.
        return new InstallDbGuardSubscriber($connection, 'fpm-fcgi');
    }

    private function request(string $path, bool $installed): Request
    {
        $request = Request::create($path);
        $request->server->set('APP_INSTALLED', $installed ? '1' : '0');

        return $request;
    }

    private function requestEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $type);
    }

    private function exceptionEvent(Request $request): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException('boom')
        );
    }

    private function reachableInitializedDatabase(): Connection
    {
        $connection = $this->sqlite();
        $connection->executeStatement('CREATE TABLE settings (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('INSERT INTO settings (id) VALUES (1)');

        return $connection;
    }

    private function reachableEmptyDatabase(): Connection
    {
        $connection = $this->sqlite();
        $connection->executeStatement('CREATE TABLE settings (id INTEGER PRIMARY KEY)');

        return $connection;
    }

    private function sqlite(): Connection
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);
    }

    /**
     * A lazy connection to a closed port: a real Connection object whose platform resolves
     * from the pinned server version without a socket, but whose first query fails. This is
     * the exact shape the live guard holds during a database outage.
     */
    private function unreachableDatabase(): Connection
    {
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return DriverManager::getConnection(
            [
                'driver' => 'pdo_mysql',
                'host' => '127.0.0.1',
                'port' => 1,
                'dbname' => 'chamilo',
                'user' => 'nobody',
                'password' => '',
                'serverVersion' => '8.0',
            ],
            $configuration
        );
    }

    private function unusedConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('fetchOne');
        $connection->expects(self::never())->method('executeQuery');

        return $connection;
    }

    private function assertRedirectsToInstaller(mixed $response): void
    {
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/main/install/index.php', $response->getTargetUrl());
        self::assertSame(302, $response->getStatusCode());
    }
}
