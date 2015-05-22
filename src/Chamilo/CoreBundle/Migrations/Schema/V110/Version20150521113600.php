<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Username changes
 */
class Version20150521113600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_forum_thread MODIFY thread_replies int UNSIGNED NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE c_forum_thread MODIFY thread_views int UNSIGNED NOT NULL DEFAULT 0');

        $this->addSql("
            UPDATE settings_current SET selected_value = '1.10.0.40' WHERE variable = 'chamilo_database_version'
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_forum_thread MODIFY thread_replies int NULL');
        $this->addSql('ALTER TABLE c_forum_thread MODIFY thread_views int NULL');

        $this->addSql("
            UPDATE settings_current SET selected_value = '1.10.0.39' WHERE variable = 'chamilo_database_version'
        ");
    }

}
