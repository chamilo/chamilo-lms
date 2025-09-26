<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250925215200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'ConferenceActivity: add nullable JSON `metrics` column.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE conference_activity ADD metrics JSON DEFAULT NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        // Remove column on rollback
        $this->addSql('ALTER TABLE conference_activity DROP COLUMN metrics');
    }
}
