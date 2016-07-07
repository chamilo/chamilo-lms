<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix c_quiz_answer's correct field for id_auto
 */
class Version20160707131900 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(" 
            ALTER TABLE c_quiz_answer ADD INDEX idx_cqa_q (question_id)");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
