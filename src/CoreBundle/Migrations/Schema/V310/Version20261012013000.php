<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261012013000 extends AbstractMigrationChamilo
{
    private const string QUESTION_PARENT_OPTION_FK = 'FK_CSTQ_PARENT_OPTION';

    public function getDescription(): string
    {
        return 'Add admin-authored survey templates and the per-survey results-hidden-from-teacher flag.';
    }

    public function up(Schema $schema): void
    {
        $liveSchema = $this->connection->createSchemaManager()->introspectSchema();

        if ($liveSchema->hasTable('c_survey')) {
            $surveyTable = $liveSchema->getTable('c_survey');
            if (!$surveyTable->hasColumn('results_hidden_from_teacher')) {
                $this->addSql(
                    'ALTER TABLE c_survey ADD results_hidden_from_teacher TINYINT(1) NOT NULL DEFAULT 0',
                );
            }
        }

        $templateExists = $liveSchema->hasTable('c_survey_template');
        if (!$templateExists) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_survey_template (
    id INT AUTO_INCREMENT NOT NULL,
    access_url_id INT NOT NULL,
    created_by_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle LONGTEXT DEFAULT NULL,
    intro LONGTEXT DEFAULT NULL,
    survey_thanks LONGTEXT DEFAULT NULL,
    anonymous VARCHAR(10) NOT NULL DEFAULT '0',
    shuffle TINYINT(1) NOT NULL DEFAULT 0,
    one_question_per_page TINYINT(1) NOT NULL DEFAULT 0,
    display_question_number TINYINT(1) NOT NULL DEFAULT 1,
    is_mandatory TINYINT(1) NOT NULL DEFAULT 0,
    duration INT DEFAULT NULL,
    visible TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    INDEX IDX_CST_ACCESS_URL (access_url_id),
    INDEX IDX_CST_CREATED_BY (created_by_id),
    INDEX idx_survey_template_catalog (access_url_id, visible),
    PRIMARY KEY(id),
    CONSTRAINT FK_CST_ACCESS_URL FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE,
    CONSTRAINT FK_CST_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        $questionExists = $liveSchema->hasTable('c_survey_template_question');
        $questionHadParentOptionFk = $questionExists
            && $liveSchema->getTable('c_survey_template_question')->hasForeignKey(self::QUESTION_PARENT_OPTION_FK);

        if (!$questionExists) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_survey_template_question (
    id INT AUTO_INCREMENT NOT NULL,
    template_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    parent_option_id INT DEFAULT NULL,
    question LONGTEXT NOT NULL,
    comment LONGTEXT DEFAULT NULL,
    type VARCHAR(250) NOT NULL,
    display VARCHAR(10) NOT NULL DEFAULT 'vertical',
    sort INT NOT NULL,
    max_value INT DEFAULT NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    INDEX IDX_CSTQ_TEMPLATE (template_id),
    INDEX IDX_CSTQ_PARENT (parent_id),
    INDEX IDX_CSTQ_PARENT_OPTION (parent_option_id),
    INDEX idx_survey_template_question_sort (template_id, sort),
    PRIMARY KEY(id),
    CONSTRAINT FK_CSTQ_TEMPLATE FOREIGN KEY (template_id) REFERENCES c_survey_template (id) ON DELETE CASCADE,
    CONSTRAINT FK_CSTQ_PARENT FOREIGN KEY (parent_id) REFERENCES c_survey_template_question (id) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        $optionExists = $liveSchema->hasTable('c_survey_template_question_option');
        if (!$optionExists) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_survey_template_question_option (
    id INT AUTO_INCREMENT NOT NULL,
    template_question_id INT NOT NULL,
    option_text LONGTEXT NOT NULL,
    sort INT NOT NULL,
    value INT NOT NULL,
    INDEX IDX_CSTQO_QUESTION (template_question_id),
    INDEX idx_survey_template_option_sort (template_question_id, sort),
    PRIMARY KEY(id),
    CONSTRAINT FK_CSTQO_QUESTION FOREIGN KEY (template_question_id) REFERENCES c_survey_template_question (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$questionHadParentOptionFk) {
            $this->addSql(
                'ALTER TABLE c_survey_template_question ADD CONSTRAINT '.self::QUESTION_PARENT_OPTION_FK.' FOREIGN KEY (parent_option_id) REFERENCES c_survey_template_question_option (id) ON DELETE SET NULL',
            );
        }
    }

    public function down(Schema $schema): void
    {
        $liveSchema = $this->connection->createSchemaManager()->introspectSchema();

        if ($liveSchema->hasTable('c_survey_template_question')) {
            $questionTable = $liveSchema->getTable('c_survey_template_question');
            if ($questionTable->hasForeignKey(self::QUESTION_PARENT_OPTION_FK)) {
                $this->addSql(
                    'ALTER TABLE c_survey_template_question DROP FOREIGN KEY '.self::QUESTION_PARENT_OPTION_FK,
                );
            }
        }

        if ($liveSchema->hasTable('c_survey_template_question_option')) {
            $this->addSql('DROP TABLE c_survey_template_question_option');
        }
        if ($liveSchema->hasTable('c_survey_template_question')) {
            $this->addSql('DROP TABLE c_survey_template_question');
        }
        if ($liveSchema->hasTable('c_survey_template')) {
            $this->addSql('DROP TABLE c_survey_template');
        }

        if ($liveSchema->hasTable('c_survey')) {
            $surveyTable = $liveSchema->getTable('c_survey');
            if ($surveyTable->hasColumn('results_hidden_from_teacher')) {
                $this->addSql('ALTER TABLE c_survey DROP results_hidden_from_teacher');
            }
        }
    }
}
