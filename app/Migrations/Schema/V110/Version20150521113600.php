<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
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
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_forum_thread MODIFY thread_replies int NULL');
        $this->addSql('ALTER TABLE c_forum_thread MODIFY thread_views int NULL');
    }
}
