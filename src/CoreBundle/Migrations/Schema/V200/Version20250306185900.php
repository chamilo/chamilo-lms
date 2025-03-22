<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250306185900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Removes assigned_by field from skill_rel_user table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill_rel_user DROP COLUMN assigned_by;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill_rel_user ADD COLUMN assigned_by INT NOT NULL DEFAULT 0;');
    }
}
