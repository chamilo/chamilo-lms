<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251212121212 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Languages: insert Armenian (hy), Irish (ga), Nepali (ne), Albanian (sq) and Tamil (ta) if missing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'հայերեն', 'armenian', 'hy', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'hy')
        ");
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'Gaeilge', 'irish', 'ga', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'ga')
        ");
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'नेपाली', 'nepali', 'ne', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'ne')
        ");
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'shqip', 'albanian', 'sq', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'sq')
        ");
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'தமிழ்', 'tamil', 'ta', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'ta')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM language WHERE isocode = 'ta'");
        $this->addSql("DELETE FROM language WHERE isocode = 'sq'");
        $this->addSql("DELETE FROM language WHERE isocode = 'ne'");
        $this->addSql("DELETE FROM language WHERE isocode = 'ga'");
        $this->addSql("DELETE FROM language WHERE isocode = 'hy'");
    }
}
