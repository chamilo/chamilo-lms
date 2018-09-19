<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150527120703
 * LP autolunch -> autolaunch.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V11010
 */
class Version20150527120703 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp CHANGE COLUMN autolunch autolaunch INT NOT NULL DEFAULT 0');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp CHANGE COLUMN autolaunch autolunch INT NOT NULL');
    }
}
