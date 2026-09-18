<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Installer;

use Chamilo\CoreBundle\Installer\MigrationHistoryRecorder;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Pins how far the administration page's "record migration history" action may go.
 *
 * The action exists for hosting with no shell, and its whole risk is the opposite of the
 * one it repairs: the code on disk is usually newer than the database, so recording every
 * shipped migration would flag the ones the upgrade still has to run. They would then
 * never run, and nothing reports it afterwards — the platform simply lacks the columns
 * they create. So each namespace is recorded only when the schema proves the database
 * already carries it, and the first namespace that cannot be proven stops the walk.
 *
 * The fingerprints are structures that a release introduced, so they answer for databases
 * the installer created from the entity definitions, which never executed a migration at
 * all. That is the state this action repairs.
 */
final class MigrationHistoryRecorderTest extends TestCase
{
    /**
     * A 1.11.x database proves nothing: it has none of the 2.x schema, and every
     * migration is genuinely pending there.
     */
    public function testLegacyDatabaseRecordsNothing(): void
    {
        $namespaces = MigrationHistoryRecorder::recordableNamespaces($this->connection());

        self::assertSame([], $namespaces);
    }

    /**
     * The 2.0.x case this action was asked for: the database holds the 2.x schema, the
     * code tree is a 3.x one. Only the 2.x namespace may be recorded, so the newer
     * migrations stay pending and the update executes them.
     */
    public function testTwoPointZeroDatabaseRecordsOnlyItsOwnNamespace(): void
    {
        $namespaces = MigrationHistoryRecorder::recordableNamespaces($this->chamilo20Database());

        self::assertSame(['V200'], $namespaces);
    }

    /**
     * A platform installed with 3.0.0, whose installer did not seed the history. Every
     * namespace that release shipped is proven by the schema, so all of them are
     * recorded and the platform ends up ready for the next update.
     */
    public function testThreePointZeroDatabaseRecordsThroughItsOwnNamespace(): void
    {
        $namespaces = MigrationHistoryRecorder::recordableNamespaces($this->chamilo30Database());

        self::assertSame(['V200', 'V210', 'V300'], $namespaces);
    }

    /**
     * A gap must stop the walk, whatever the later fingerprints say. Nothing accounts for
     * a database that carries 3.0 structures without the ones that came before them, and
     * recording the later namespace there would hide real pending work.
     */
    public function testAGapInTheLevelsStopsTheWalk(): void
    {
        $connection = $this->chamilo20Database();
        $connection->executeStatement('CREATE TABLE course_invitation (id INTEGER PRIMARY KEY)');

        $namespaces = MigrationHistoryRecorder::recordableNamespaces($connection);

        self::assertSame(['V200'], $namespaces);
    }

    /**
     * A fingerprint that names a column is not answered by the table alone. The 2.x to
     * 3.0 path adds columns to tables that already existed, so a present table proves
     * nothing about the namespace that altered it.
     */
    public function testATableWithoutTheFingerprintColumnIsNotProof(): void
    {
        $connection = $this->chamilo20Database();
        $connection->executeStatement('CREATE TABLE c_lp_item_view (iid INTEGER PRIMARY KEY)');

        $namespaces = MigrationHistoryRecorder::recordableNamespaces($connection);

        self::assertSame(['V200'], $namespaces);
    }

    private function connection(): Connection
    {
        // Without the factory DBAL 3 emits a deprecation, which the CI counts as a
        // failure. It goes through Configuration: the parameter array has a declared
        // shape, and an extra key there is an error for Psalm.
        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        return DriverManager::getConnection(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $configuration
        );
    }

    /**
     * A Chamilo 2.0.x database: the 2.x resource system, and none of the structures the
     * releases after it introduced.
     */
    private function chamilo20Database(): Connection
    {
        $connection = $this->connection();
        $connection->executeStatement('CREATE TABLE resource_node (id INTEGER PRIMARY KEY)');

        return $connection;
    }

    /**
     * A Chamilo 3.0.0 database, as the installer creates it from the entity definitions.
     */
    private function chamilo30Database(): Connection
    {
        $connection = $this->chamilo20Database();
        $connection->executeStatement('CREATE TABLE c_lp_item_view (iid INTEGER PRIMARY KEY, progress DOUBLE DEFAULT NULL)');
        $connection->executeStatement('CREATE TABLE course_invitation (id INTEGER PRIMARY KEY)');

        return $connection;
    }
}
