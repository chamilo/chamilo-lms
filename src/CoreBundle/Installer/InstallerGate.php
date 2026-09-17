<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Installer;

use Doctrine\DBAL\Connection;
use Throwable;

use const PATHINFO_FILENAME;

/**
 * Decides what the unauthenticated web installer may do.
 *
 * The installer carries no authentication, so an installed and up-to-date platform must
 * never expose it (GHSA-mfgc-693v-xq5v). An installed platform that still has pending
 * Doctrine migrations is a legitimate upgrade and stays reachable, which is what makes
 * the 2.x to 3.x web upgrade work.
 *
 * The decision never reads chamilo_database_version. That setting is deprecated, and a
 * fresh install seeds it with a stale schema default, so it once let the gate fail open.
 * Doctrine's own migration metadata answers instead.
 *
 * A 1.11.x database is the one case that metadata cannot answer, because its `version`
 * table is not Doctrine's. The schema answers there: a configured database without the
 * 2.x tables has its whole migration history pending.
 */
final class InstallerGate
{
    /**
     * Tables that prove the platform already holds its configuration. The 1.11.x name
     * comes second: that branch stores its settings in `settings_current`, and 2.x and
     * later store them in `settings`.
     */
    private const array SETTINGS_TABLES = ['settings_current', 'settings'];

    /**
     * Created by the V200 migrations and absent from every 1.11.x database.
     */
    private const string MODERN_SCHEMA_TABLE = 'resource_node';

    /**
     * Doctrine's migration metadata table, and the columns it must have. A 1.11.x
     * database has an unrelated `version` table with other columns.
     */
    private const string METADATA_TABLE = 'version';

    private const array METADATA_COLUMNS = ['version', 'executed_at', 'execution_time'];

    /**
     * Name of the file an administrator creates in the project root to authorise an
     * upgrade. It sits outside the document root on purpose: nobody can probe over HTTP
     * whether a platform is currently open for upgrading.
     */
    public const string UPGRADE_FLAG_FILE = 'UPGRADE_ENABLED';

    /**
     * Resolves what the installer may do for the given environment and database.
     *
     * A null connection means the database is unreachable. Nothing then proves the
     * platform is installed, so the wizard stays open rather than locking an
     * administrator out of a recovery.
     *
     * @param list<string> $availableMigrations every migration class the code tree ships
     * @param bool         $upgradeAuthorised   whether the flag file is present
     */
    public static function resolve(
        bool $envFileExists,
        bool $appInstalled,
        ?Connection $connection,
        array $availableMigrations,
        bool $upgradeAuthorised = true
    ): InstallerState {
        if (!$envFileExists || !$appInstalled || !$connection instanceof Connection) {
            return InstallerState::FreshInstall;
        }

        if (!self::isDatabaseInitialized($connection)) {
            return InstallerState::Unfinished;
        }

        // A configured database without the 2.x schema is a 1.11.x platform, and every
        // migration the code tree ships is pending there. Doctrine's metadata cannot say
        // so: the 1.11.x `version` table carries other columns, so it reads as unusable
        // metadata and the platform would look up-to-date. The schema answers instead,
        // and it never mistakes an installed 2.x or 3.x platform for this case.
        if (!self::isModernSchema($connection)) {
            return $upgradeAuthorised
                ? InstallerState::UpgradePending
                : InstallerState::UpgradeNotAuthorised;
        }

        if (self::hasPendingMigrations($connection, $availableMigrations)) {
            // Pending migrations alone are not enough. The endpoints carry no
            // authentication, so the upgrade also needs a deliberate act on the server:
            // an administrator creating the flag file.
            return $upgradeAuthorised
                ? InstallerState::UpgradePending
                : InstallerState::UpgradeNotAuthorised;
        }

        return InstallerState::UpToDate;
    }

    /**
     * Tells whether the flag file that authorises an upgrade is present.
     *
     * Only an administrator creates it. The wizard never writes it for itself: the file's
     * whole value is that its presence proves a human decided on the server, and a wizard
     * that could create it would leave that presence proving nothing.
     */
    public static function isUpgradeAuthorised(string $projectDir): bool
    {
        return is_file(rtrim($projectDir, '/').'/'.self::UPGRADE_FLAG_FILE);
    }

    /**
     * Removes the flag file once the upgrade is over, so the endpoints close behind it.
     *
     * Returns false when the file is still there, which happens on a read-only project
     * root. The caller then tells the administrator to delete it by hand.
     */
    public static function revokeUpgradeAuthorisation(string $projectDir): bool
    {
        $flagFile = rtrim($projectDir, '/').'/'.self::UPGRADE_FLAG_FILE;

        if (!is_file($flagFile)) {
            return true;
        }

        return @unlink($flagFile) && !is_file($flagFile);
    }

    /**
     * Tells whether the platform tables already hold configuration rows.
     *
     * Row presence is preferred over schema introspection: it is cheaper, and it also
     * answers for a 1.11.x database, whose settings table carries the other name.
     */
    public static function isDatabaseInitialized(Connection $connection): bool
    {
        foreach (self::SETTINGS_TABLES as $table) {
            try {
                $hasAnySetting = $connection->fetchOne(
                    'SELECT 1 FROM '.$connection->quoteIdentifier($table).' LIMIT 1'
                );

                if (false !== $hasAnySetting && null !== $hasAnySetting) {
                    return true;
                }
            } catch (Throwable) {
                // Table absent or unreadable: try the next one.
            }
        }

        return false;
    }

    /**
     * Tells whether the code tree carries migrations the database has not executed yet.
     *
     * Missing or unusable metadata returns false on purpose. Nothing then proves an
     * upgrade is pending, and an installed platform must fail closed rather than expose
     * the installer. `doctrine:migrations:sync-metadata-storage` followed by
     * `doctrine:migrations:version --add --all` seeds that metadata.
     *
     * @param list<string> $availableMigrations every migration class the code tree ships
     */
    public static function hasPendingMigrations(Connection $connection, array $availableMigrations): bool
    {
        if ([] === $availableMigrations || !self::isMetadataUsable($connection)) {
            return false;
        }

        try {
            $executed = $connection->fetchFirstColumn(
                'SELECT version FROM '.$connection->quoteIdentifier(self::METADATA_TABLE)
            );
        } catch (Throwable) {
            return false;
        }

        // An empty metadata table proves nothing: a fresh install that was never seeded
        // looks exactly like a database that has executed no migration at all. Reporting
        // every migration as pending there would open the installer on an up-to-date
        // platform, and let it replay the whole history over a final schema.
        if ([] === $executed) {
            return false;
        }

        $executed = array_flip(array_map(self::normalizeVersion(...), $executed));

        foreach ($availableMigrations as $migration) {
            if (!isset($executed[self::normalizeVersion($migration)])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells whether the database already carries the Chamilo 2.x schema.
     *
     * This separates a modern upgrade from a 1.11.x one without reading the deprecated
     * version setting.
     */
    public static function isModernSchema(Connection $connection): bool
    {
        try {
            return $connection->createSchemaManager()->tablesExist([self::MODERN_SCHEMA_TABLE]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Lists every migration class a Doctrine migrations configuration array declares.
     *
     * Paths are read relative to the configuration file, exactly as Doctrine reads them.
     *
     * @param array<string, mixed> $configuration the array a migrations.php file returns
     *
     * @return list<string>
     */
    public static function migrationsFromConfiguration(array $configuration, string $configurationDir): array
    {
        $migrations = [];

        /** @var array<string, string> $paths */
        $paths = $configuration['migrations_paths'] ?? [];

        foreach ($paths as $namespace => $path) {
            $absolutePath = realpath(rtrim($configurationDir, '/').'/'.$path);

            if (false === $absolutePath) {
                continue;
            }

            foreach (glob($absolutePath.'/Version*.php') ?: [] as $file) {
                $migrations[] = rtrim($namespace, '\\').'\\'.pathinfo($file, PATHINFO_FILENAME);
            }
        }

        sort($migrations);

        return array_values(array_unique($migrations));
    }

    /**
     * Tells whether the `version` table is Doctrine's own metadata table.
     */
    private static function isMetadataUsable(Connection $connection): bool
    {
        try {
            $schema = $connection->createSchemaManager();

            if (!$schema->tablesExist([self::METADATA_TABLE])) {
                return false;
            }

            $columns = $schema->listTableColumns(self::METADATA_TABLE);

            foreach (self::METADATA_COLUMNS as $column) {
                if (!isset($columns[$column])) {
                    return false;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Doctrine stores a version with a leading backslash in some releases. Compare the
     * bare class name so both spellings match.
     */
    private static function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), '\\');
    }
}
