<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Username changes.
 */
class Version20150519153200 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE session_rel_user ADD COLUMN registered_at DATETIME NOT NULL');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE session_rel_user DROP COLUMN registered_at');
    }
}
