<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Tool changes.
 */
class Version20150610143426 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_tool ADD description LONGTEXT, ADD custom_icon VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_tool DROP description, DROP custom_icon');
    }
}
