<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Installer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * Records the migrations a platform has already incorporated, for a platform whose
 * migration history is empty.
 *
 * Chamilo installs its schema straight from the entity definitions, so an installation
 * created by the installer holds the final schema with no history at all. Doctrine then
 * reads that as "nothing has ever run" and a later doctrine:migrations:migrate tries to
 * replay the whole series over a schema that is already current.
 *
 * This exists for administrators with no shell access on their hosting. It is not the
 * same thing as `doctrine:migrations:version --add --all`, and deliberately so: the code
 * on disk is often newer than the database. A 2.0.x platform that received the 3.0 code
 * would have the 3.0 migrations marked as executed by `--all`, and they would then never
 * run — a silent loss no tool reports afterwards. So the schema decides how far to go.
 */
final class MigrationHistoryRecorder
{
    /**
     * Ordered schema levels, one per migration namespace, each with the structure that
     * proves the namespace is already incorporated.
     *
     * Order matters: the levels are read from the first one, and the first absent
     * fingerprint stops the walk. Every later namespace then stays pending, whatever its
     * own fingerprint says, because a gap means a state nothing here can account for.
     *
     * A fingerprint has to name a structure the **entity definitions** declare too, not
     * merely one a migration creates. Installations are born from
     * `doctrine:schema:create`, which reads the entities, so a table that only exists
     * because a migration ran is absent from a fresh install and proves nothing there.
     * A namespace whose migrations only add settings rows, or a table no entity maps,
     * therefore gets no entry at all.
     *
     * A namespace missing from this list is never recorded, and its migrations run
     * instead. That is the safe direction, and for such a namespace it is also the
     * correct one — a fresh install genuinely needs those migrations.
     *
     * @var array<string, array{table: string, column?: string}>
     */
    private const array SCHEMA_LEVELS = [
        // The 2.x resource system. Absent from every 1.11.x database.
        'V200' => ['table' => 'resource_node'],
        // SCORM 2004 progress tracking, added on the way to 3.0.
        'V210' => ['table' => 'c_lp_item_view', 'column' => 'progress'],
        // Course and session registration invitations, new in 3.0.
        'V300' => ['table' => 'course_invitation'],
    ];

    public function __construct(
        // DoctrineMigrationsBundle registers the factory under its service id only, with
        // no alias to the class, so autowiring cannot find it on its own.
        #[Autowire(service: 'doctrine.migrations.dependency_factory')]
        private readonly DependencyFactory $dependencyFactory
    ) {}

    /**
     * Namespaces whose migrations the given database proves it already carries.
     *
     * Static and connection-driven so the decision can be tested against a schema
     * without a container, the way InstallerGate is tested.
     *
     * @return list<string>
     */
    public static function recordableNamespaces(Connection $connection): array
    {
        $namespaces = [];

        foreach (self::SCHEMA_LEVELS as $namespace => $fingerprint) {
            if (!self::hasFingerprint($connection, $fingerprint)) {
                break;
            }

            $namespaces[] = $namespace;
        }

        return $namespaces;
    }

    /**
     * Tells whether the history holds no executed migration at all.
     *
     * An unreadable or absent metadata table counts as empty: that is precisely the state
     * this service exists to repair.
     */
    public function isEmpty(): bool
    {
        return 0 === $this->countExecuted();
    }

    /**
     * Number of migrations the database has already executed.
     */
    public function countExecuted(): int
    {
        try {
            $storage = $this->dependencyFactory->getMetadataStorage();

            return \count($storage->getExecutedMigrations()->getItems());
        } catch (Throwable) {
            // The metadata table does not exist yet.
            return 0;
        }
    }

    /**
     * Number of migrations recording would mark, without recording anything.
     *
     * Zero means the schema proves nothing, which happens on a 1.11.x database: every
     * migration is genuinely pending there.
     */
    public function countRecordable(): int
    {
        return \count($this->migrationsToRecord());
    }

    /**
     * Number of migrations the code ships that recording would leave pending.
     */
    public function countPendingAfterRecording(): int
    {
        return \count($this->dependencyFactory->getMigrationRepository()->getMigrations()->getItems())
            - $this->countRecordable();
    }

    /**
     * Records the migrations the schema proves, and returns how many were recorded.
     *
     * Refuses to act on a history that already holds rows. That guard is the whole point:
     * on a platform with a partly executed history, marking the rest would flag migrations
     * that never ran, and their schema changes would then never be applied — a silent
     * corruption no tool can detect afterwards.
     *
     * The initial `isEmpty()` check above is not enough on its own to make this call
     * idempotent: two overlapping requests (a double click, two admin tabs) can both pass
     * it before either has inserted a row. Skipping versions `getExecutedMigrations()`
     * already lists closes most of that window; the row itself is still primary-keyed on
     * the version, so a second request that slips through the same window as the first
     * gets a unique-constraint violation on the insert instead of a stale in-memory read
     * — that case is treated as success too, since the goal state (this version marked
     * executed) is already reached by whichever request got there first.
     *
     * @throws MigrationHistoryAlreadyRecordedException when the history is not empty
     */
    public function record(): int
    {
        if (!$this->isEmpty()) {
            throw new MigrationHistoryAlreadyRecordedException('The migration history already holds executed migrations. It must not be rewritten.');
        }

        $storage = $this->dependencyFactory->getMetadataStorage();
        $storage->ensureInitialized();

        $alreadyExecuted = $storage->getExecutedMigrations();
        $recorded = 0;

        foreach ($this->migrationsToRecord() as $version) {
            if ($alreadyExecuted->hasMigration($version)) {
                continue;
            }

            try {
                // Direction and ExecutionResult are marked @internal, but there is no
                // public way to mark a migration as executed: doctrine:migrations:version
                // builds the very same pair. Stay on that exact call so an upstream
                // change breaks here the same day it breaks the command.
                $storage->complete(new ExecutionResult($version, Direction::UP));
                ++$recorded;
            } catch (UniqueConstraintViolationException) {
                // A concurrent request recorded this exact version between the
                // getExecutedMigrations() read above and this insert.
            }
        }

        return $recorded;
    }

    /**
     * Tells whether the database carries the structure a fingerprint names.
     *
     * @param array{table: string, column?: string} $fingerprint
     */
    private static function hasFingerprint(Connection $connection, array $fingerprint): bool
    {
        try {
            $schema = $connection->createSchemaManager();

            if (!$schema->tablesExist([$fingerprint['table']])) {
                return false;
            }

            if (!isset($fingerprint['column'])) {
                return true;
            }

            return isset($schema->listTableColumns($fingerprint['table'])[$fingerprint['column']]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * The versions to record: every shipped migration that belongs to a namespace the
     * schema proves.
     *
     * @return list<Version>
     */
    private function migrationsToRecord(): array
    {
        $namespaces = array_flip(self::recordableNamespaces($this->dependencyFactory->getConnection()));
        $versions = [];

        foreach ($this->dependencyFactory->getMigrationRepository()->getMigrations()->getItems() as $migration) {
            $version = $migration->getVersion();

            if (isset($namespaces[self::namespaceOf((string) $version)])) {
                $versions[] = $version;
            }
        }

        return $versions;
    }

    /**
     * Last namespace segment of a migration class name, which is the schema namespace.
     */
    private static function namespaceOf(string $migrationClass): string
    {
        $segments = explode('\\', trim($migrationClass, '\\'));
        array_pop($segments);

        return (string) array_pop($segments);
    }
}
