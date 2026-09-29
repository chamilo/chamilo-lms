<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Installer;

use Chamilo\CoreBundle\Installer\InstallerGate;
use Chamilo\CoreBundle\Installer\InstallerState;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for the web installer gate (advisory GHSA-mfgc-693v-xq5v).
 *
 * The gate has to answer two questions at once, and an earlier fix answered only the
 * first: it refused every installed platform, which also refused the legitimate 2.x to
 * 3.x upgrade and left the wizard's own upgrade path unreachable. These cases pin both
 * halves, because breaking either one is silent — a closed gate looks like "security
 * works" until an administrator cannot upgrade, and an open gate looks like "the upgrade
 * works" until an anonymous caller re-triggers a production migration.
 *
 * Doctrine's migration metadata is the only source of truth here. The deprecated
 * chamilo_database_version setting must never come back: a fresh install seeds it with a
 * stale schema default, which is what made the version comparison fail open.
 */
final class InstallerGateTest extends TestCase
{
    private const array MIGRATIONS = [
        'Chamilo\CoreBundle\Migrations\Schema\V200\Version20200101010000',
        'Chamilo\CoreBundle\Migrations\Schema\V300\Version20260101010000',
    ];

    /**
     * Without .env the code tree was never configured, so the wizard must answer.
     * This is the 1.11.x upgrade, which unpacks Chamilo 3 into a new directory.
     */
    public function testNoEnvFileAllowsTheWizard(): void
    {
        $state = InstallerGate::resolve(false, false, $this->databaseWithSettings(), self::MIGRATIONS);

        $this->assertSame(InstallerState::FreshInstall, $state);
        $this->assertFalse($state->isLocked());
    }

    /**
     * APP_INSTALLED is written at step 5, before the migration runs. Refusing the
     * request here would strand an interrupted install with no way back in.
     */
    public function testInstalledFlagWithEmptyDatabaseAllowsTheWizard(): void
    {
        $state = InstallerGate::resolve(true, true, $this->emptyDatabase(), self::MIGRATIONS);

        $this->assertSame(InstallerState::Unfinished, $state);
        $this->assertFalse($state->isLocked());
    }

    /**
     * The upgrade case the earlier fix broke: an installed 2.x platform whose database
     * has not executed the 3.x migrations yet.
     */
    public function testInstalledPlatformWithPendingMigrationsAllowsTheUpgrade(): void
    {
        $connection = $this->databaseWithSettings();
        $this->seedMetadata($connection, [self::MIGRATIONS[0]]);

        $state = InstallerGate::resolve(true, true, $connection, self::MIGRATIONS);

        $this->assertSame(InstallerState::UpgradePending, $state);
        $this->assertFalse($state->isLocked());
        $this->assertTrue($state->isUpgrade());
    }

    /**
     * Pending migrations are not enough on their own. The endpoints carry no
     * authentication, so an upgrade also needs the flag file an administrator creates on
     * the server. Without that deliberate act, an anonymous caller could start a
     * production migration the moment new code is uploaded.
     */
    public function testPendingMigrationsWithoutTheFlagFileLockTheWizard(): void
    {
        $connection = $this->databaseWithSettings();
        $this->seedMetadata($connection, [self::MIGRATIONS[0]]);

        $state = InstallerGate::resolve(true, true, $connection, self::MIGRATIONS, false);

        $this->assertSame(InstallerState::UpgradeNotAuthorised, $state);
        $this->assertTrue($state->isLocked());
        $this->assertFalse($state->isUpgrade());
    }

    /**
     * The flag file only authorises an upgrade; it never opens a platform that has
     * nothing pending.
     */
    public function testTheFlagFileAloneDoesNotOpenAnUpToDatePlatform(): void
    {
        $connection = $this->databaseWithSettings();
        $this->seedMetadata($connection, self::MIGRATIONS);

        $state = InstallerGate::resolve(true, true, $connection, self::MIGRATIONS, true);

        $this->assertSame(InstallerState::UpToDate, $state);
        $this->assertTrue($state->isLocked());
    }

    /**
     * The flag file is read from the project root, never from the document root: a file
     * under public/ could be probed over HTTP to learn that an upgrade is under way.
     */
    public function testTheFlagFileIsReadFromTheGivenDirectory(): void
    {
        $projectDir = sys_get_temp_dir().'/chamilo-upgrade-flag-'.uniqid('', true);
        mkdir($projectDir, 0o777, true);

        $this->assertFalse(InstallerGate::isUpgradeAuthorised($projectDir));

        touch($projectDir.'/'.InstallerGate::UPGRADE_FLAG_FILE);
        $this->assertTrue(InstallerGate::isUpgradeAuthorised($projectDir));

        $this->assertTrue(InstallerGate::revokeUpgradeAuthorisation($projectDir));
        $this->assertFalse(InstallerGate::isUpgradeAuthorised($projectDir));

        // Revoking an authorisation that is already gone is not a failure.
        $this->assertTrue(InstallerGate::revokeUpgradeAuthorisation($projectDir));

        rmdir($projectDir);
    }

    /**
     * The abuse the advisory reported: an anonymous caller re-triggering the migration
     * of a platform that has nothing left to migrate.
     */
    public function testInstalledPlatformWithNothingPendingLocksTheWizard(): void
    {
        $connection = $this->databaseWithSettings();
        $this->seedMetadata($connection, self::MIGRATIONS);

        $state = InstallerGate::resolve(true, true, $connection, self::MIGRATIONS);

        $this->assertSame(InstallerState::UpToDate, $state);
        $this->assertTrue($state->isLocked());
        $this->assertFalse($state->isUpgrade());
    }

    /**
     * An installed platform whose metadata table was never seeded cannot prove that an
     * upgrade is due, so the gate has to fail closed. The administrator seeds it with
     * doctrine:migrations:sync-metadata-storage and doctrine:migrations:version.
     */
    public function testInstalledPlatformWithoutMetadataLocksTheWizard(): void
    {
        $state = InstallerGate::resolve(true, true, $this->databaseWithSettings(), self::MIGRATIONS);

        $this->assertSame(InstallerState::UpToDate, $state);
        $this->assertTrue($state->isLocked());
    }

    /**
     * Found by the real 1.11.x upgrade run, not by reading the code: an existing but
     * empty metadata table is indistinguishable from a fresh install that was never
     * seeded. Reporting 393 pending migrations there opened the installer on an
     * up-to-date platform, and would have let migrate.php replay the whole history over
     * a final schema.
     */
    public function testEmptyMetadataTableLocksTheWizard(): void
    {
        $connection = $this->databaseWithSettings();
        $this->seedMetadata($connection, []);

        $state = InstallerGate::resolve(true, true, $connection, self::MIGRATIONS);

        $this->assertSame(InstallerState::UpToDate, $state);
        $this->assertTrue($state->isLocked());
    }

    /**
     * A 1.11.x database carries an unrelated `version` table. Its rows must never count
     * as executed migrations: that would report a pending upgrade from a leftover table
     * rather than from the schema.
     */
    public function testLegacyVersionTableIsNotReadAsMetadata(): void
    {
        $this->assertFalse(
            InstallerGate::hasPendingMigrations($this->legacyDatabase(), self::MIGRATIONS)
        );
    }

    /**
     * The lockout this fixed, found by the real 1.11.x upgrade run: step 4 writes .env
     * before any migration runs, and from the next request on the gate saw an installed
     * platform whose metadata was unusable, so it answered UpToDate and refused the rest
     * of the wizard. The schema tells the two apart: no resource_node means 1.11.x, and
     * the whole migration history is pending there.
     */
    public function testLegacyDatabaseIsAnUpgradeAndNotAnUpToDatePlatform(): void
    {
        $state = InstallerGate::resolve(true, true, $this->legacyDatabase(), self::MIGRATIONS);

        $this->assertSame(InstallerState::UpgradePending, $state);
        $this->assertFalse($state->isLocked());
        $this->assertTrue($state->isUpgrade());
    }

    /**
     * The 1.11.x upgrade needs the same authorisation as the 2.x one. The wizard asks for
     * it on the requirements step, before the database form: the gate lets the wizard in
     * without the file while .env is still absent, and step 4 writes that .env.
     */
    public function testLegacyDatabaseWithoutTheFlagFileLocksTheWizard(): void
    {
        $state = InstallerGate::resolve(true, true, $this->legacyDatabase(), self::MIGRATIONS, false);

        $this->assertSame(InstallerState::UpgradeNotAuthorised, $state);
        $this->assertTrue($state->isLocked());
    }

    /**
     * A 1.11.x upgrade interrupted partway through the V200 migrations, which is what a
     * closed browser or a dead process leaves behind. resource_node already exists, so
     * the schema no longer says 1.11.x and the metadata rule has to answer instead. The
     * two rules meet with no gap because Doctrine records each migration as it completes,
     * and six complete before the one that creates resource_node
     * (V200\Version20170525122900) — so the metadata is never empty by then. Reproduced
     * against a real 1.11.40 database before this case was written.
     */
    public function testInterruptedLegacyUpgradeStillAllowsTheWizard(): void
    {
        $connection = $this->legacyDatabase();
        // The installer renames the 1.11.x table before Doctrine creates its own.
        $connection->executeStatement('ALTER TABLE version RENAME TO version_1_11');
        $this->seedMetadata($connection, [self::MIGRATIONS[0]]);
        $connection->executeStatement('CREATE TABLE resource_node (id INTEGER PRIMARY KEY)');

        // The answer comes from the metadata, not from the 1.11.x leftovers.
        $this->assertTrue(InstallerGate::hasPendingMigrations($connection, self::MIGRATIONS));
        $this->assertTrue(InstallerGate::isModernSchema($connection));

        $state = InstallerGate::resolve(true, true, $connection, self::MIGRATIONS);

        $this->assertSame(InstallerState::UpgradePending, $state);
        $this->assertFalse($state->isLocked());
        $this->assertTrue($state->isUpgrade());
    }

    /**
     * An unreachable database proves nothing, so it must not lock an administrator out
     * of a recovery. resolve() receives null for that case.
     */
    public function testUnreachableDatabaseAllowsTheWizard(): void
    {
        $state = InstallerGate::resolve(true, true, null, self::MIGRATIONS);

        $this->assertSame(InstallerState::FreshInstall, $state);
        $this->assertFalse($state->isLocked());
    }

    /**
     * Doctrine has stored the version with and without a leading backslash across
     * releases. A mismatch there would report an up-to-date platform as pending.
     */
    public function testLeadingBackslashInStoredVersionStillMatches(): void
    {
        $connection = $this->databaseWithSettings();
        $this->seedMetadata($connection, array_map(static fn (string $m): string => '\\'.$m, self::MIGRATIONS));

        $this->assertFalse(InstallerGate::hasPendingMigrations($connection, self::MIGRATIONS));
    }

    /**
     * The 1.11.x settings table is named settings_current, so the gate must accept it
     * as proof of an initialized database.
     */
    public function testLegacySettingsTableCountsAsInitialized(): void
    {
        $connection = $this->connection();
        $connection->executeStatement('CREATE TABLE settings_current (id INTEGER PRIMARY KEY, variable VARCHAR(255))');
        $connection->executeStatement("INSERT INTO settings_current (variable) VALUES ('platform_language')");

        $this->assertTrue(InstallerGate::isDatabaseInitialized($connection));
    }

    /**
     * resource_node separates a 2.x database from a 1.11.x one without reading the
     * deprecated version setting.
     */
    public function testModernSchemaIsDetectedFromResourceNode(): void
    {
        $connection = $this->connection();
        $this->assertFalse(InstallerGate::isModernSchema($connection));

        $connection->executeStatement('CREATE TABLE resource_node (id INTEGER PRIMARY KEY)');
        $this->assertTrue(InstallerGate::isModernSchema($connection));
    }

    /**
     * The migration list must carry class names, because that is what Doctrine stores
     * in the metadata table. Handing back file paths would never match a stored row and
     * would report every migration as pending.
     */
    public function testMigrationsAreListedAsClassNames(): void
    {
        $directory = sys_get_temp_dir().'/chamilo-installer-gate-'.uniqid('', true);
        mkdir($directory.'/V300', 0o777, true);
        touch($directory.'/V300/Version20260101010000.php');
        touch($directory.'/V300/NotAMigration.php');

        $migrations = InstallerGate::migrationsFromConfiguration(
            ['migrations_paths' => ['Chamilo\CoreBundle\Migrations\Schema\V300' => 'V300']],
            $directory
        );

        $this->assertSame(['Chamilo\CoreBundle\Migrations\Schema\V300\Version20260101010000'], $migrations);

        unlink($directory.'/V300/Version20260101010000.php');
        unlink($directory.'/V300/NotAMigration.php');
        rmdir($directory.'/V300');
        rmdir($directory);
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

    private function emptyDatabase(): Connection
    {
        $connection = $this->connection();
        $connection->executeStatement('CREATE TABLE settings (id INTEGER PRIMARY KEY, variable VARCHAR(255))');

        return $connection;
    }

    /**
     * An installed Chamilo 2.x or 3.x platform: configuration rows and the 2.x schema.
     * resource_node has to be there, because the gate reads its absence as a 1.11.x
     * database whose whole migration history is pending.
     */
    private function databaseWithSettings(): Connection
    {
        $connection = $this->emptyDatabase();
        $connection->executeStatement("INSERT INTO settings (variable) VALUES ('platform_language')");
        $connection->executeStatement('CREATE TABLE resource_node (id INTEGER PRIMARY KEY)');

        return $connection;
    }

    /**
     * A Chamilo 1.11.x database: settings under the old table name, no 2.x schema, and
     * the unrelated `version` table that branch carries.
     */
    private function legacyDatabase(): Connection
    {
        $connection = $this->connection();
        $connection->executeStatement('CREATE TABLE settings_current (id INTEGER PRIMARY KEY, variable VARCHAR(255))');
        $connection->executeStatement("INSERT INTO settings_current (variable) VALUES ('platform_language')");
        $connection->executeStatement('CREATE TABLE version (id INTEGER PRIMARY KEY, version VARCHAR(20))');
        $connection->executeStatement("INSERT INTO version (version) VALUES ('1.11.40')");

        return $connection;
    }

    /**
     * @param list<string> $executedMigrations
     */
    private function seedMetadata(Connection $connection, array $executedMigrations): void
    {
        $connection->executeStatement(
            'CREATE TABLE version (version VARCHAR(1024) NOT NULL, executed_at DATETIME DEFAULT NULL, execution_time INTEGER DEFAULT NULL)'
        );

        foreach ($executedMigrations as $migration) {
            $connection->executeStatement(
                'INSERT INTO version (version, executed_at, execution_time) VALUES (?, ?, ?)',
                [$migration, '2026-01-01 00:00:00', 1]
            );
        }
    }
}
