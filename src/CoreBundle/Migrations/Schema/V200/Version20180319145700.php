<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20180319145700.
 *
 * Add indexes related to course surveys
 */
class Version20180319145700 extends AbstractMigrationChamilo
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE INDEX idx_survey_q_qid ON c_survey_question (question_id)');
        $this->addSql('CREATE INDEX idx_survey_code ON c_survey (code)');
        $this->addSql('CREATE INDEX idx_survey_inv_code ON c_survey_invitation (survey_code)');
        $this->addSql('CREATE INDEX idx_survey_qo_qid ON c_survey_question_option (question_id)');
    }

    public function down(Schema $schema)
    {
    }
}
