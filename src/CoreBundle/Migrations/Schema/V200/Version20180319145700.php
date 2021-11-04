<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

class Version20180319145700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate Survey tables';
    }

    public function up(Schema $schema): void
    {
        $survey = $schema->getTable('c_survey');

        if ($survey->hasIndex('session_id')) {
            $this->addSql(' DROP INDEX session_id ON c_survey;');
        }

        if ($survey->hasIndex('course')) {
            $this->addSql(' DROP INDEX course ON c_survey;');
        }

        if (false === $survey->hasColumn('is_mandatory')) {
            $this->addSql('ALTER TABLE c_survey ADD COLUMN is_mandatory TINYINT(1) DEFAULT "0" NOT NULL');
        }

        $this->addSql('ALTER TABLE c_survey CHANGE code code varchar(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey CHANGE parent_id parent_id INT DEFAULT NULL');
        $this->addSql('UPDATE c_survey SET parent_id = NULL WHERE parent_id = 0');

        if (!$survey->hasColumn('lft')) {
            $this->addSql(
                'ALTER TABLE c_survey ADD lft INT DEFAULT NULL, ADD rgt INT DEFAULT NULL, ADD lvl INT DEFAULT NULL'
            );
        }

        if (!$survey->hasForeignKey('FK_F246DB30727ACA70')) {
            $this->addSql(
                'ALTER TABLE c_survey ADD CONSTRAINT FK_F246DB30727ACA70 FOREIGN KEY (parent_id) REFERENCES c_survey (iid) ON DELETE CASCADE '
            );
        }
        if (!$survey->hasIndex('IDX_F246DB30727ACA70')) {
            $this->addSql('CREATE INDEX IDX_F246DB30727ACA70 ON c_survey (parent_id)');
        }

        if (false === $survey->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_survey ADD resource_node_id BIGINT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_survey ADD CONSTRAINT FK_F246DB301BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_F246DB301BAD783F ON c_survey (resource_node_id);');
        }

        $this->addSql('ALTER TABLE c_survey CHANGE avail_from avail_from DATETIME DEFAULT NULL;');
        $this->addSql('ALTER TABLE c_survey CHANGE avail_till avail_till DATETIME DEFAULT NULL;');

        $table = $schema->getTable('c_survey_answer');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_survey_answer;');
        }

        $this->addSql('ALTER TABLE c_survey_answer CHANGE survey_id survey_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey_answer CHANGE question_id question_id INT DEFAULT NULL');
        $this->addSql('DELETE FROM c_survey_answer WHERE question_id NOT IN (select iid from c_survey_question)');

        //$this->addSql('ALTER TABLE c_survey_answer CHANGE option_id option_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_8A897DDB3FE509D')) {
            $this->addSql('ALTER TABLE c_survey_answer ADD CONSTRAINT FK_8A897DDB3FE509D FOREIGN KEY (survey_id) REFERENCES c_survey (iid);');
        }

        if (!$table->hasForeignKey('FK_8A897DD1E27F6BF')) {
            $this->addSql('ALTER TABLE c_survey_answer ADD CONSTRAINT FK_8A897DD1E27F6BF FOREIGN KEY (question_id) REFERENCES c_survey_question (iid) ON DELETE CASCADE');
        }

        /*if (!$table->hasForeignKey('FK_8A897DDA7C41D6F')) {
            $this->addSql('ALTER TABLE c_survey_answer ADD CONSTRAINT FK_8A897DDA7C41D6F FOREIGN KEY (option_id) REFERENCES c_survey_question_option (iid);');
        }*/

        if (!$table->hasIndex('IDX_8A897DDB3FE509D')) {
            $this->addSql('CREATE INDEX IDX_8A897DDB3FE509D ON c_survey_answer (survey_id);');
        }

        if (!$table->hasIndex('IDX_8A897DD1E27F6BF')) {
            $this->addSql('CREATE INDEX IDX_8A897DD1E27F6BF ON c_survey_answer (question_id);');
        }

        // option_id is a long text
        /*if (!$table->hasIndex('IDX_8A897DDA7C41D6F')) {
            $this->addSql('CREATE INDEX IDX_8A897DDA7C41D6F ON c_survey_answer (option_id);');
        }*/

        /*if (!$survey->hasIndex('idx_survey_code')) {
            $this->addSql('CREATE INDEX idx_survey_code ON c_survey (code)');
        }*/

        $table = $schema->getTable('c_survey_invitation');

        $this->addSql('ALTER TABLE c_survey_invitation CHANGE reminder_date reminder_date DATETIME DEFAULT NULL');
        $this->addSql(
            'UPDATE c_survey_invitation SET reminder_date = NULL WHERE CAST(reminder_date AS CHAR(20)) = "0000-00-00 00:00:00"'
        );

        // c_survey_invitation.user_id
        if (!$table->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE c_survey_invitation ADD user_id INT DEFAULT NULL');

            $this->addSql('ALTER TABLE c_survey_invitation CHANGE user user VARCHAR(255) DEFAULT NULL');
            $this->addSql('UPDATE c_survey_invitation SET user_id = user WHERE user IN (SELECT id FROM user)');
        }

        if (!$table->hasForeignKey('FK_D0BC7C2A76ED395')) {
            $this->addSql(
                'ALTER TABLE c_survey_invitation ADD CONSTRAINT FK_D0BC7C2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_D0BC7C2A76ED395')) {
            $this->addSql('CREATE INDEX IDX_D0BC7C2A76ED395 ON c_survey_invitation (user_id)');
        }

        if (!$table->hasColumn('answered_at')) {
            $this->addSql('ALTER TABLE c_survey_invitation ADD answered_at DATETIME DEFAULT NULL;');
        }

        if (!$table->hasColumn('survey_id')) {
            $this->addSql('ALTER TABLE c_survey_invitation ADD survey_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_survey_invitation ADD CONSTRAINT FK_D0BC7C2B3FE509D FOREIGN KEY (survey_id) REFERENCES c_survey (iid)');
            $this->addSql('CREATE INDEX IDX_D0BC7C2B3FE509D ON c_survey_invitation (survey_id)');
        }

        if ($table->hasIndex('idx_survey_inv_code')) {
            $this->addSql('DROP INDEX idx_survey_inv_code ON c_survey_invitation;');
        }

        $this->addSql('ALTER TABLE c_survey_invitation CHANGE c_id c_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey_invitation CHANGE session_id session_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey_invitation CHANGE group_id group_id INT DEFAULT NULL');

        $this->addSql('DELETE FROM c_survey_invitation WHERE c_id IS NULL OR c_id = 0 ');
        $this->addSql('UPDATE c_survey_invitation SET session_id = NULL WHERE session_id = 0 ');
        $this->addSql('UPDATE c_survey_invitation SET group_id = NULL WHERE group_id = 0 ');

        $this->addSql('DELETE FROM c_survey_invitation WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)');

        if (!$table->hasForeignKey('FK_D0BC7C291D79BD3')) {
            $this->addSql(
                'ALTER TABLE c_survey_invitation ADD CONSTRAINT FK_D0BC7C291D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_D0BC7C2613FECDF')) {
            $this->addSql(
                'ALTER TABLE c_survey_invitation ADD CONSTRAINT FK_D0BC7C2613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_D0BC7C2FE54D947')) {
            $this->addSql(
                'ALTER TABLE c_survey_invitation ADD CONSTRAINT FK_D0BC7C2FE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_D0BC7C2613FECDF')) {
            $this->addSql('CREATE INDEX IDX_D0BC7C2613FECDF ON c_survey_invitation (session_id)');
        }

        if (!$table->hasIndex('IDX_D0BC7C2FE54D947')) {
            $this->addSql('CREATE INDEX IDX_D0BC7C2FE54D947 ON c_survey_invitation (group_id)');
        }

        $table = $schema->getTable('c_survey_question');

        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_survey_question');
        }

        if (!$table->hasColumn('is_required')) {
            $table
                ->addColumn('is_required', Types::BOOLEAN)
                ->setDefault(false)
            ;
        }
        /*if (false === $table->hasIndex('idx_survey_q_qid')) {
            $this->addSql('CREATE INDEX idx_survey_q_qid ON c_survey_question (question_id)');
        }*/

        $this->addSql('ALTER TABLE c_survey_question CHANGE survey_id survey_id INT DEFAULT NULL;');

        if (!$table->hasForeignKey('FK_92F05EE7B3FE509D')) {
            $this->addSql('ALTER TABLE c_survey_question ADD CONSTRAINT FK_92F05EE7B3FE509D FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE CASCADE');
        }

        if (!$table->hasIndex('IDX_92F05EE7B3FE509D')) {
            $this->addSql('CREATE INDEX IDX_92F05EE7B3FE509D ON c_survey_question (survey_id);');
        }

        if ($table->hasIndex('idx_survey_q_qid')) {
            $this->addSql('DROP INDEX idx_survey_q_qid ON c_survey_question;');
        }

        if (!$table->hasColumn('parent_id')) {
            $this->addSql('ALTER TABLE c_survey_question ADD parent_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_survey_question ADD CONSTRAINT FK_92F05EE7727ACA70 FOREIGN KEY (parent_id) REFERENCES c_survey_question (iid) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_92F05EE7727ACA70 ON c_survey_question (parent_id);');
        }

        if (!$table->hasColumn('parent_option_id')) {
            $this->addSql('ALTER TABLE c_survey_question ADD parent_option_id INT DEFAULT NULL;');
            $this->addSql('ALTER TABLE c_survey_question ADD CONSTRAINT FK_92F05EE7568F3281 FOREIGN KEY (parent_option_id) REFERENCES c_survey_question_option (iid)');
            $this->addSql('CREATE INDEX IDX_92F05EE7568F3281 ON c_survey_question (parent_option_id);');
        }

        $table = $schema->getTable('c_survey_question_option');
        if ($table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON c_survey_question_option');
        }

        /*if (false === $table->hasIndex('idx_survey_qo_qid')) {
            $this->addSql('CREATE INDEX idx_survey_qo_qid ON c_survey_question_option (question_id)');
        }*/

        $this->addSql('ALTER TABLE c_survey_question_option CHANGE question_id question_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey_question_option CHANGE survey_id survey_id INT DEFAULT NULL;');

        if (false === $table->hasForeignKey('FK_C4B6F5F1E27F6BF')) {
            $this->addSql('ALTER TABLE c_survey_question_option ADD CONSTRAINT FK_C4B6F5F1E27F6BF FOREIGN KEY (question_id) REFERENCES c_survey_question (iid) ON DELETE CASCADE');
        }

        if (false === $table->hasForeignKey('FK_C4B6F5FB3FE509D')) {
            $this->addSql('ALTER TABLE c_survey_question_option ADD CONSTRAINT FK_C4B6F5FB3FE509D FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE CASCADE');
        }

        if (false === $table->hasIndex('IDX_C4B6F5FB3FE509D')) {
            $this->addSql('CREATE INDEX IDX_C4B6F5FB3FE509D ON c_survey_question_option (survey_id);');
        }

        $em = $this->getEntityManager();
        $sql = 'SELECT * FROM c_survey ';
        $result = $em->getConnection()->executeQuery($sql);
        $data = $result->fetchAllAssociative();
        $surveyList = [];
        if ($data) {
            foreach ($data as $item) {
                $surveyList[$item['c_id']][$item['code']] = $item['iid'];
            }
        }

        // Replace survey_code with new survey_id.
        $sql = 'SELECT * FROM c_survey_invitation ';
        $result = $em->getConnection()->executeQuery($sql);
        $data = $result->fetchAllAssociative();
        if ($data) {
            foreach ($data as $item) {
                $id = $item['iid'];
                $courseId = $item['c_id'];
                $code = $item['survey_code'];

                $surveyId = $surveyList[$courseId][$code] ?? null;
                if (empty($surveyId)) {
                    continue;
                }

                $this->addSql("UPDATE c_survey_invitation SET survey_id = $surveyId WHERE iid = $id");
            }
        }

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
                $this->addSql("UPDATE c_survey SET is_mandatory = 1 WHERE iid = {$id}");
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
