<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Username changes
 */
class Version201505191532 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE session_rel_user ADD COLUMN registered_at DATETIME NOT NULL');
        $this->addSql("UPDATE settings_current SET selected_value = '1.10.0.40' WHERE variable = 'chamilo_database_version'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE session_rel_user DROP COLUMN registered_at');
        $this->addSql("UPDATE settings_current SET selected_value = '1.10.0.39' WHERE variable = 'chamilo_database_version'");
    }
}
