<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Installer;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * Records every shipped migration as already executed, for a platform whose migration
 * history is empty.
 *
 * Chamilo installs its schema straight from the entity definitions, so an installation
 * created by the installer holds the final schema with no history at all. Doctrine then
 * reads that as "nothing has ever run" and a later doctrine:migrations:migrate tries to
 * replay the whole series over a schema that is already current.
 *
 * This is the same thing as `doctrine:migrations:sync-metadata-storage` followed by
 * `doctrine:migrations:version --add --all`, exposed to administrators who have no shell
 * access on their hosting.
 */
final class MigrationHistoryRecorder
{
    public function __construct(
        // DoctrineMigrationsBundle registers the factory under its service id only, with
        // no alias to the class, so autowiring cannot find it on its own.
        #[Autowire(service: 'doctrine.migrations.dependency_factory')]
        private readonly DependencyFactory $dependencyFactory
    ) {}

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
     * Records every shipped migration as executed, and returns how many were recorded.
     *
     * Refuses to act on a history that already holds rows. That guard is the whole point:
     * on a platform with a partly executed history, marking the rest would flag migrations
     * that never ran, and their schema changes would then never be applied — a silent
     * corruption no tool can detect afterwards.
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

        $recorded = 0;

        foreach ($this->dependencyFactory->getMigrationRepository()->getMigrations()->getItems() as $migration) {
            // Direction and ExecutionResult are marked @internal, but there is no public
            // way to mark a migration as executed: doctrine:migrations:version builds the
            // very same pair. Stay on that exact call so an upstream change breaks here
            // the same day it breaks the command.
            $storage->complete(new ExecutionResult($migration->getVersion(), Direction::UP));
            ++$recorded;
        }

        return $recorded;
    }
}
