<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251204120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Languages: insert Lao (lo) if missing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'ພາສາລາວ', 'lao', 'lo', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'lo')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM language WHERE isocode = 'lo'");
    }
}
