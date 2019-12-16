<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Agenda.
 */
class Version20150709083710 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_calendar_event ADD color VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_calendar_event DROP color');
    }
}
