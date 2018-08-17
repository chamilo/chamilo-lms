<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160808160000
 * Set ponderation and destination for questions and answers
 * @package Application\Migrations\Schema\V111
 */
class Version20160808160000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE c_quiz_answer SET destination = NULL WHERE TRIM(destination) = ''");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("UPDATE c_quiz_answer SET destination = '' WHERE destination IS NULL");
    }
}