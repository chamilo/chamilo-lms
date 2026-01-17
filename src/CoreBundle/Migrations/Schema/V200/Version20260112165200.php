<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260112165200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add Spanish (Mexico) language (es_MX) to language table when missing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO language (original_name, english_name, isocode, available)
            SELECT 'Español (México)', 'spanish (mexico)', 'es_MX', 1
            WHERE NOT EXISTS (
                SELECT 1 FROM language WHERE isocode = 'es_MX'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM language
            WHERE isocode = 'es_MX'
              AND original_name = 'Español (México)'
        ");
    }
}
