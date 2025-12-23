<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20200101040000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add require_unique boolean field to c_attendance (default false).';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_attendance') && $schema->getTable('c_attendance')->hasColumn('require_unique')) {
            return;
        }

        $this->addSql('ALTER TABLE c_attendance ADD COLUMN require_unique BOOLEAN NOT NULL DEFAULT FALSE');

        // ensure existing rows are set (some DBs may already do it with DEFAULT, but keep it explicit).
        $this->addSql('UPDATE c_attendance SET require_unique = FALSE WHERE require_unique IS NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('c_attendance') || !$schema->getTable('c_attendance')->hasColumn('require_unique')) {
            return;
        }

        $this->addSql('ALTER TABLE c_attendance DROP COLUMN require_unique');
    }
}
