<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261023200000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add a branch floor-plan resource reference and normalized room polygons.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('branch') || !$schema->hasTable('room') || !$schema->hasTable('resource_node')) {
            throw new RuntimeException('Stage 1 branch/room tables and resource_node must exist before this migration.');
        }

        $branchHasPlan = $schema->getTable('branch')->hasColumn('floor_plan_id');
        $roomHasMap = $schema->getTable('room')->hasColumn('room_map');

        if ($branchHasPlan && $roomHasMap) {
            return;
        }

        if ($branchHasPlan || $roomHasMap) {
            throw new RuntimeException('Partial floor-plan schema detected; manual review required.');
        }

        $this->addSql('ALTER TABLE branch ADD floor_plan_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_BRANCH_FLOOR_PLAN ON branch (floor_plan_id)');
        $this->addSql('ALTER TABLE branch ADD CONSTRAINT FK_BRANCH_FLOOR_PLAN FOREIGN KEY (floor_plan_id) REFERENCES resource_node (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE room ADD room_map JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Deliberately preserve floor-plan references and room geometries.
        // A rollback requires review and restoration from the documented backup.
    }
}
