<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260923000000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Languages: Add Azerbaijani.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'Azərbaycanca', 'azerbaijani', 'az', 0
            WHERE NOT EXISTS (SELECT 1 FROM language WHERE isocode = 'az')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM language WHERE isocode = 'az'");
    }
}
