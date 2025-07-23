<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201216122010 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add export_allowed column to c_lp_item table for selective LP item PDF export';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_lp_item
                ADD export_allowed TINYINT(1) NOT NULL DEFAULT 0
        ');
        $this->write('Added export_allowed column to c_lp_item table.');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE c_lp_item
                DROP COLUMN export_allowed
        ');
        $this->write('Removed export_allowed column from c_lp_item table.');
    }
}
