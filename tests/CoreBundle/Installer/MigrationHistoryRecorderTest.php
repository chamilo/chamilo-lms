<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Installer;

use Chamilo\CoreBundle\Installer\MigrationHistoryRecorder;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigrationsRepository;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Exception;
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

    /**
     * Regression test for a duplicate-key error reported in production: two overlapping
     * requests to the admin "record migration history" action both read the history as
     * empty before either had inserted a row, so both tried to record the same versions.
     *
     * record()'s own isEmpty() guard cannot catch this -- by the time either request
     * checks it, the history genuinely is still empty -- so this exercises the recording
     * loop itself with a storage double that reproduces the loser's exact experience: the
     * version it is about to insert was, in the moment between its own read and its own
     * insert, already inserted by the other request. Recording must treat that version as
     * already handled rather than let the resulting unique-constraint violation fail the
     * whole batch and leave the remaining, genuinely pending versions unrecorded.
     */
    public function testAVersionWonByAConcurrentRequestIsSkippedNotFailed(): void
    {
        $winnerVersion = new Version('Chamilo\CoreBundle\Migrations\Schema\V300\Version20260728130000');
        $ownVersion = new Version('Chamilo\CoreBundle\Migrations\Schema\V300\Version20260728140000');

        $migration = new class extends AbstractMigration {
            public function __construct() {}

            public function up(Schema $schema): void {}
        };

        $repository = new class($winnerVersion, $ownVersion, $migration) implements MigrationsRepository {
            public function __construct(
                private readonly Version $winnerVersion,
                private readonly Version $ownVersion,
                private readonly AbstractMigration $migration,
            ) {}

            public function hasMigration(string $version): bool
            {
                return true;
            }

            public function getMigration(Version $version): AvailableMigration
            {
                return new AvailableMigration($version, $this->migration);
            }

            public function getMigrations(): AvailableMigrationsSet
            {
                return new AvailableMigrationsSet([
                    new AvailableMigration($this->winnerVersion, $this->migration),
                    new AvailableMigration($this->ownVersion, $this->migration),
                ]);
            }
        };

        $storage = new class($winnerVersion) implements MetadataStorage {
            public int $completed = 0;

            public function __construct(
                private readonly Version $winnerVersion
            ) {}

            public function ensureInitialized(): void {}

            public function getExecutedMigrations(): ExecutedMigrationsList
            {
                // Neither version has been recorded from this request's point of view --
                // this is precisely how the history looks right before the race.
                return new ExecutedMigrationsList([]);
            }

            public function complete(ExecutionResult $result): void
            {
                ++$this->completed;

                if ($result->getVersion()->equals($this->winnerVersion)) {
                    // Another, concurrent request already inserted this exact version
                    // between our getExecutedMigrations() read and this insert.
                    throw new UniqueConstraintViolationException(new class extends Exception implements DriverException {
                        public function getSQLState(): string
                        {
                            return '23000';
                        }
                    }, null);
                }
            }

            public function reset(): void {}
        };

        $connection = $this->chamilo30Database();
        $dependencyFactory = DependencyFactory::fromConnection(
            new ConfigurationArray([]),
            new ExistingConnection($connection)
        );
        $dependencyFactory->setService(MetadataStorage::class, $storage);
        $dependencyFactory->setService(MigrationsRepository::class, $repository);

        $recorder = new MigrationHistoryRecorder($dependencyFactory);

        $recorded = $recorder->record();

        self::assertSame(1, $recorded, 'Only the version this request actually won should count as recorded.');
        self::assertSame(2, $storage->completed, 'Both versions must still be attempted -- the race loser is not skipped in advance.');
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
