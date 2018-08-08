<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160930144400
 * Fix track_e_hotspot ussing c_quiz_answer iid
 * @package Application\Migrations\Schema\V111
 */
class Version20160930144400 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        error_log('Version20160930144400');
        $this->addSql('
            UPDATE track_e_hotspot h 
            SET h.hotspot_answer_id = (
                SELECT qa.iid
                FROM c_quiz_answer qa
                WHERE h.c_id = qa.c_id AND
                    h.hotspot_question_id = qa.question_id AND 
                    h.hotspot_answer_id = qa.id
            )
        ');

        $this->addSql('ALTER TABLE extra_field ADD visible_to_others TINYINT(1) DEFAULT 0, CHANGE visible visible_to_self TINYINT(1) DEFAULT 0');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('
            UPDATE track_e_hotspot h 
            SET h.hotspot_answer_id = (
                SELECT qa.id
                FROM c_quiz_answer qa
                WHERE
                    h.c_id = qa.c_id AND
                    h.hotspot_question_id = qa.question_id AND 
                    h.hotspot_answer_id = qa.iid
            )
        ');
    }
}
