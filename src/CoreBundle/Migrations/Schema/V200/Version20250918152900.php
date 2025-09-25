<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250918152900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Languages: insert Burmese (my_MM) if missing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'မြန်မာဘာသာ', 'burmese', 'my_MM', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'my_MM')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM language WHERE isocode = 'my_MM'");
    }
}
