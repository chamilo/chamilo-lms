<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version20250310215900 saves the BBB and Zoom activities through the ORM, whose mapping includes
 * conference_activity.metrics: Version20250925215200 only adds it later, and then leaves it as is.
 * See Version20201218132718.
 */
final class Version20250310215800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early the conference activity metrics that the migration of the BBB and Zoom plugins needs';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('conference_activity') && !$schema->getTable('conference_activity')->hasColumn('metrics')) {
            $this->addSql("ALTER TABLE conference_activity ADD metrics JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
        }
    }

    public function down(Schema $schema): void {}
}
