<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V200;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170904145500
 * @package Application\Migrations\Schema\V200
 */
class Version20170904145500 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('DELETE FROM c_group_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
    }

    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
