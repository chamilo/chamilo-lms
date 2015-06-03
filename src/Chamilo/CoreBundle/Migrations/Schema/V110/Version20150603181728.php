<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Doctrine\DBAL\Schema\Schema;

/**
 * Lp changes
 */
class Version20150603181728 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp ADD max_attempts INT NOT NULL, ADD subscribe_users INT NOT NULL DEFAULT 0');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp DROP max_attempts, DROP subscribe_users');
    }
}
