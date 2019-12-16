<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150527114220
 * Lp category.
 */
class Version20150527114220 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS c_lp_category (iid INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, name VARCHAR(255) NOT NULL, position INT NOT NULL, PRIMARY KEY(iid)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_lp ADD category_id INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE c_lp_category');
        $this->addSql('ALTER TABLE c_lp DROP category_id');
    }
}
