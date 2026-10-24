<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261023183000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename branches and preserve geographic coordinates for branches and rooms.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('branch_sync')) {
            if ($schema->hasTable('branch')) {
                return;
            }

            throw new RuntimeException('Neither branch_sync nor branch table exists.');
        }

        if ($schema->hasTable('branch')) {
            throw new RuntimeException('Both branch_sync and branch tables exist; manual inspection is required.');
        }

        if (!$schema->hasTable('room')) {
            throw new RuntimeException('The room table is required.');
        }

        $branchColumns = $schema->getTable('branch_sync');
        $roomColumns = $schema->getTable('room');
        if (!$branchColumns->hasColumn('latitude') || !$branchColumns->hasColumn('longitude')
            || $branchColumns->hasColumn('coordinates')) {
            throw new RuntimeException('Unexpected branch_sync coordinate schema; no changes made.');
        }

        if (!$roomColumns->hasColumn('geolocation') || $roomColumns->hasColumn('coordinates')) {
            throw new RuntimeException('Unexpected room coordinate schema; no changes made.');
        }

        $incomplete = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM branch_sync WHERE (latitude IS NULL) <> (longitude IS NULL)'
        );
        if ($incomplete > 0) {
            throw new RuntimeException('Branches with incomplete coordinates exist; resolve before migrating.');
        }

        $this->addSql('ALTER TABLE branch_sync ADD coordinates VARCHAR(255) DEFAULT NULL');
        $this->addSql(
            "UPDATE branch_sync SET coordinates = CONCAT(latitude, ', ', longitude) "
            .'WHERE latitude IS NOT NULL AND longitude IS NOT NULL'
        );
        $this->addSql('ALTER TABLE branch_sync DROP COLUMN latitude, DROP COLUMN longitude');
        $this->addSql('RENAME TABLE branch_sync TO branch');
        $this->addSql('ALTER TABLE room CHANGE geolocation coordinates VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Restoring the original two decimal columns requires a reviewed data migration.
        // Use the documented database backup rather than a lossy automatic rollback.
    }
}
