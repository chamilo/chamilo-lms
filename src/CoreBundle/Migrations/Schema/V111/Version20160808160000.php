<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160808160000
 * Set ponderation and destination for questions and answers.
 */
class Version20160808160000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE c_quiz_answer SET destination = NULL WHERE TRIM(destination) = ''");
    }

    public function down(Schema $schema)
    {
        $this->addSql("UPDATE c_quiz_answer SET destination = '' WHERE destination IS NULL");
    }
}
