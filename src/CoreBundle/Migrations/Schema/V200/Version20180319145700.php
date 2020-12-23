<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
 * Survey changes.
 */
class Version20180319145700 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $survey = $schema->getTable('c_survey');
        if (false === $survey->hasColumn('is_mandatory')) {
            $this->addSql('ALTER TABLE c_survey ADD COLUMN is_mandatory TINYINT(1) DEFAULT "0" NOT NULL');
        }

        if (false === $survey->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_survey ADD resource_node_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_survey ADD CONSTRAINT FK_F246DB301BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_F246DB301BAD783F ON c_survey (resource_node_id);');
        }

        $this->addSql('ALTER TABLE c_survey CHANGE avail_from avail_from DATETIME DEFAULT NULL;');
        $this->addSql('ALTER TABLE c_survey CHANGE avail_till avail_till DATETIME DEFAULT NULL;');

        /*if (!$survey->hasIndex('idx_survey_code')) {
            $this->addSql('CREATE INDEX idx_survey_code ON c_survey (code)');
        }*/

        $this->addSql('ALTER TABLE c_survey_invitation CHANGE reminder_date reminder_date DATETIME DEFAULT NULL');
        $this->addSql(
            'UPDATE c_survey_invitation SET reminder_date = NULL WHERE CAST(reminder_date AS CHAR(20)) = "0000-00-00 00:00:00"'
        );

        $table = $schema->getTable('c_survey_invitation');
        if (false === $table->hasColumn('answered_at')) {
            $this->addSql('ALTER TABLE c_survey_invitation ADD answered_at DATETIME DEFAULT NULL;');
        }
        if (false === $table->hasIndex('idx_survey_inv_code')) {
            $this->addSql('CREATE INDEX idx_survey_inv_code ON c_survey_invitation (survey_code)');
        }

        $table = $schema->getTable('c_survey_question');
        if (false === $table->hasColumn('is_required')) {
            $table
                ->addColumn('is_required', Types::BOOLEAN)
                ->setDefault(false);
        }
        if (false === $table->hasIndex('idx_survey_q_qid')) {
            $this->addSql('CREATE INDEX idx_survey_q_qid ON c_survey_question (question_id)');
        }

        if (false === $table->hasColumn('parent_id')) {
            $this->addSql('ALTER TABLE c_survey_question ADD parent_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_survey_question ADD CONSTRAINT FK_92F05EE7727ACA70 FOREIGN KEY (parent_id) REFERENCES c_survey_question (iid);');
            $this->addSql('CREATE INDEX IDX_92F05EE7727ACA70 ON c_survey_question (parent_id);');
        }

        if (false === $table->hasColumn('parent_option_id')) {
            $this->addSql('ALTER TABLE c_survey_question ADD parent_option_id INT DEFAULT NULL;');
            $this->addSql('ALTER TABLE c_survey_question ADD CONSTRAINT FK_92F05EE7568F3281 FOREIGN KEY (parent_option_id) REFERENCES c_survey_question_option (iid)');
            $this->addSql('CREATE INDEX IDX_92F05EE7568F3281 ON c_survey_question (parent_option_id);');
        }

        $table = $schema->getTable('c_survey_question_option');
        if (false === $table->hasIndex('idx_survey_qo_qid')) {
            $this->addSql('CREATE INDEX idx_survey_qo_qid ON c_survey_question_option (question_id)');
        }

        $em = $this->getEntityManager();

        $sql = 'SELECT s.* FROM c_survey s
                INNER JOIN extra_field_values efv
                ON s.iid = efv.item_id
                INNER JOIN extra_field ef
                ON efv.field_id = ef.id
                WHERE
                    ef.variable = "is_mandatory" AND
                    ef.extra_field_type = '.ExtraField::SURVEY_FIELD_TYPE.' AND
                    efv.value = 1
        ';

        $result = $em->getConnection()->executeQuery($sql);
        $data = $result->fetchAllAssociative();
        if ($data) {
            foreach ($data as $item) {
                $id = $item['iid'];
                $this->addSql("UPDATE c_survey SET is_mandatory = 1 WHERE iid = $id");
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
