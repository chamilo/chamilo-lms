<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Set null to post_parent_id when it is 0 on c_forum_post table
 */
class Version20160808110200 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE c_forum_post SET post_parent_id = NULL WHERE post_parent_id = 0");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('UPDATE c_forum_post SET post_parent_id = 0 WHERE post_parent_id = NULL');
    }
}
