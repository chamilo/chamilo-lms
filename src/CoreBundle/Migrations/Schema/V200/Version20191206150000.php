<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20191206150000.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V200
 */
class Version20191206150000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->getEntityManager();

        $this->addSql('ALTER TABLE extra_field ADD helper_text text DEFAULT NULL AFTER display_text');

    }

    public function down(Schema $schema)
    {
    }
}
