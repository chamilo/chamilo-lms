<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260731000000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Languages: Add Maltese, Icelandic, Bokmal and Estonian.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'Eesti keel', 'estonian', 'et', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'et')
        ");
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'Malti', 'maltese', 'mt', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'mt')
        ");
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'Íslenska', 'icelandic', 'is_IS', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'is_IS')
        ");
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'Norsk bokmål', 'bokmal', 'nb_NO', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'nb_NO')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM language WHERE isocode = 'nb_NO'");
        $this->addSql("DELETE FROM language WHERE isocode = 'is_IS'");
        $this->addSql("DELETE FROM language WHERE isocode = 'mt'");
        $this->addSql("DELETE FROM language WHERE isocode = 'et'");
    }
}
