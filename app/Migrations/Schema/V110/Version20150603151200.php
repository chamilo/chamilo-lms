<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Session date changes
 */
class Version20150603151200 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_forum_forum ADD lp_id INTEGER UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE c_forum_thread ADD lp_item_id INTEGER UNSIGNED NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_forum_forum DROP lp_id');
        $this->addSql('ALTER TABLE c_forum_thread DROP lp_item_id');
    }

}
