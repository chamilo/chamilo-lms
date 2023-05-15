<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230216122950 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Alter tables required in configuration';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('ticket_ticket')) {
            $this->addSql(
                'ALTER TABLE ticket_ticket ADD exercise_id INT DEFAULT NULL, ADD lp_id INT DEFAULT NULL'
            );
        }

        if ($schema->hasTable('c_quiz_question_rel_category')) {
            $this->addSql(
                'ALTER TABLE c_quiz_question_rel_category ADD COLUMN mandatory INT DEFAULT 0'
            );
        }

        if (!$schema->hasTable('c_plagiarism_compilatio_docs')) {
            $this->addSql(
                'CREATE TABLE c_plagiarism_compilatio_docs (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, document_id INT NOT NULL, compilatio_id VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
        }

        if (!$schema->hasTable('notification_event')) {
            $this->addSql(
                'CREATE TABLE notification_event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, link LONGTEXT DEFAULT NULL, persistent INT DEFAULT NULL, day_diff INT DEFAULT NULL, event_type VARCHAR(255) NOT NULL, event_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
        }

        if ($schema->hasTable('system_template')) {
            $this->addSql(
                'ALTER TABLE system_template ADD language VARCHAR(40) NOT NULL DEFAULT "english"'
            );
        }

        if (!$schema->hasTable('agenda_event_invitee')) {
            $this->addSql(
                'CREATE TABLE agenda_event_invitee (id BIGINT AUTO_INCREMENT NOT NULL, invitation_id BIGINT DEFAULT NULL, user_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", type VARCHAR(255) NOT NULL, INDEX IDX_4F5757FEA35D7AF0 (invitation_id), INDEX IDX_4F5757FEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE agenda_event_invitee ADD CONSTRAINT FK_4F5757FEA35D7AF0 FOREIGN KEY (invitation_id) REFERENCES agenda_event_invitation (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE agenda_event_invitee ADD CONSTRAINT FK_4F5757FEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL'
            );
        }

        if (!$schema->hasTable('agenda_event_invitation')) {
            $this->addSql(
                'CREATE TABLE agenda_event_invitation (id BIGINT AUTO_INCREMENT NOT NULL, creator_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", type VARCHAR(255) NOT NULL, max_attendees INT DEFAULT 0, INDEX IDX_52A2D5E161220EA6 (creator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE agenda_event_invitation ADD CONSTRAINT FK_52A2D5E161220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (!$schema->hasTable('agenda_reminder')) {
            $this->addSql(
                'CREATE TABLE agenda_reminder (id BIGINT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, event_id INT NOT NULL, date_interval VARCHAR(255) NOT NULL COMMENT "(DC2Type:dateinterval)", sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
        }

        if (!$schema->hasTable('c_wiki_category')) {
            $this->addSql(
                'CREATE TABLE c_wiki_category (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, session_id INT DEFAULT NULL, tree_root INT DEFAULT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, lft INT NOT NULL, lvl INT NOT NULL, rgt INT NOT NULL, INDEX IDX_17F1099A91D79BD3 (c_id), INDEX IDX_17F1099A613FECDF (session_id), INDEX IDX_17F1099AA977936C (tree_root), INDEX IDX_17F1099A727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099A91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099A613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099AA977936C FOREIGN KEY (tree_root) REFERENCES c_wiki_category (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099A727ACA70 FOREIGN KEY (parent_id) REFERENCES c_wiki_category (id) ON DELETE CASCADE'
            );
        }

        if (!$schema->hasTable('c_wiki_rel_category')) {
            $this->addSql(
                'CREATE TABLE c_wiki_rel_category (wiki_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_AC88945BAA948DBE (wiki_id), INDEX IDX_AC88945B12469DE2 (category_id), PRIMARY KEY(wiki_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_rel_category ADD CONSTRAINT FK_AC88945BAA948DBE FOREIGN KEY (wiki_id) REFERENCES c_wiki (iid) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_rel_category ADD CONSTRAINT FK_AC88945B12469DE2 FOREIGN KEY (category_id) REFERENCES c_wiki_category (id) ON DELETE CASCADE'
            );
        }

        if (!$schema->hasTable('notification_event_rel_user')) {
            $this->addSql(
                'CREATE TABLE notification_event_rel_user (id INT UNSIGNED AUTO_INCREMENT NOT NULL, event_id INT unsigned, user_id INT, INDEX FK_EVENT (event_id), INDEX FK_USER (user_id), PRIMARY KEY (id))'
            );
            $this->addSql(
                'ALTER TABLE notification_event_rel_user ADD CONSTRAINT FK_EVENT FOREIGN KEY (event_id) REFERENCES notification_event (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE notification_event_rel_user ADD CONSTRAINT FK_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if ($schema->hasTable('c_survey_answer')) {
            $this->addSql(
                'ALTER TABLE c_survey_answer ADD session_id INT NOT NULL, ADD c_lp_item_id INT NOT NULL'
            );
        }

        if ($schema->hasTable('c_survey_invitation')) {
            $this->addSql(
                'ALTER TABLE c_survey_invitation ADD c_lp_item_id INT NOT NULL'
            );
        }

        if (!$schema->hasTable('message_feedback')) {
            $this->addSql(
                'CREATE TABLE message_feedback (id BIGINT AUTO_INCREMENT NOT NULL, message_id BIGINT NOT NULL, user_id INT NOT NULL, liked TINYINT(1) DEFAULT 0 NOT NULL, disliked TINYINT(1) DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", INDEX IDX_DB0F8049537A1329 (message_id), INDEX IDX_DB0F8049A76ED395 (user_id), INDEX idx_message_feedback_uid_mid (message_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE message_feedback ADD CONSTRAINT FK_DB0F8049537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE message_feedback ADD CONSTRAINT FK_DB0F8049A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (!$schema->hasTable('portfolio_attachment')) {
            $this->addSql(
                'CREATE TABLE portfolio_attachment (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, comment LONGTEXT DEFAULT NULL, size INT NOT NULL, filename VARCHAR(255) NOT NULL, origin_id INT NOT NULL, origin_type INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
        }

        if (!$schema->hasTable('portfolio_comment')) {
            $this->addSql(
                'CREATE TABLE portfolio_comment (id INT AUTO_INCREMENT NOT NULL, author_id INT NOT NULL, item_id INT NOT NULL, tree_root INT DEFAULT NULL, parent_id INT DEFAULT NULL, visibility SMALLINT DEFAULT 1 NOT NULL, content LONGTEXT NOT NULL, date DATETIME NOT NULL COMMENT "(DC2Type:datetime)", is_important TINYINT(1) DEFAULT 0 NOT NULL, lft INT NOT NULL, lvl INT NOT NULL, rgt INT NOT NULL, score DOUBLE PRECISION DEFAULT NULL, is_template TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_C2C17DA2F675F31B (author_id), INDEX IDX_C2C17DA2126F525E (item_id), INDEX IDX_C2C17DA2A977936C (tree_root), INDEX IDX_C2C17DA2727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE portfolio_comment ADD CONSTRAINT FK_C2C17DA2F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE portfolio_comment ADD CONSTRAINT FK_C2C17DA2126F525E FOREIGN KEY (item_id) REFERENCES portfolio (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE portfolio_comment ADD CONSTRAINT FK_C2C17DA2A977936C FOREIGN KEY (tree_root) REFERENCES portfolio_comment (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE portfolio_comment ADD CONSTRAINT FK_C2C17DA2727ACA70 FOREIGN KEY (parent_id) REFERENCES portfolio_comment (id) ON DELETE CASCADE'
            );
        }

        if ($schema->hasTable('portfolio')) {
            $this->addSql(
                'ALTER TABLE portfolio ADD origin INT DEFAULT NULL, ADD origin_type INT DEFAULT NULL, ADD score DOUBLE PRECISION DEFAULT NULL, ADD is_highlighted TINYINT(1) DEFAULT 0 NOT NULL, ADD is_template TINYINT(1) DEFAULT 0 NOT NULL'
            );
        }

        if (!$schema->hasTable('c_attendance_result_comment')) {
            $this->addSql(
                'CREATE TABLE c_attendance_result_comment (iid INT AUTO_INCREMENT NOT NULL, attendance_sheet_id INT NOT NULL, user_id INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", author_user_id INT NOT NULL, INDEX attendance_sheet_id (attendance_sheet_id), INDEX user_id (user_id), PRIMARY KEY(iid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
        }

        if ($schema->hasTable('c_attendance_calendar')) {
            $this->addSql(
                'ALTER TABLE c_attendance_calendar ADD blocked TINYINT(1) DEFAULT NULL'
            );
        }

        if ($schema->hasTable('c_attendance_sheet')) {
            $this->addSql(
                'ALTER TABLE c_attendance_sheet ADD signature VARCHAR(255) DEFAULT NULL'
            );
        }

        if ($schema->hasTable('gradebook_category')) {
            $this->addSql(
                'ALTER TABLE gradebook_category ADD allow_skills_by_subcategory INT DEFAULT 1'
            );
        }

        if ($schema->hasTable('c_quiz')) {
            $this->addSql(
                'ALTER TABLE c_quiz ADD hide_attempts_table TINYINT(1) NOT NULL'
            );
        }

        if ($schema->hasTable('c_lp')) {
            $this->addSql(
                'ALTER TABLE c_lp ADD next_lp_id INT DEFAULT 0 NOT NULL'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('c_lp');
        if ($table->hasColumn('next_lp_id')) {
            $this->addSql('ALTER TABLE c_lp DROP next_lp_id');
        }

        $table = $schema->getTable('c_quiz');
        if ($table->hasColumn('hide_attempts_table')) {
            $this->addSql('ALTER TABLE c_quiz DROP hide_attempts_table');
        }

        $table = $schema->getTable('gradebook_category');
        if ($table->hasColumn('allow_skills_by_subcategory')) {
            $this->addSql('ALTER TABLE c_attendance_sheet DROP allow_skills_by_subcategory');
        }

        $table = $schema->getTable('c_attendance_sheet');
        if ($table->hasColumn('signature')) {
            $this->addSql('ALTER TABLE c_attendance_sheet DROP signature');
        }

        $table = $schema->getTable('c_attendance_calendar');
        if ($table->hasColumn('blocked')) {
            $this->addSql('ALTER TABLE c_attendance_calendar DROP blocked');
        }

        if ($schema->hasTable('c_attendance_result_comment')) {
            $this->addSql(
                'DROP TABLE c_attendance_result_comment'
            );
        }

        $table = $schema->getTable('portfolio');
        if ($table->hasColumn('origin')) {
            $this->addSql('ALTER TABLE portfolio DROP origin');
        }
        if ($table->hasColumn('origin_type')) {
            $this->addSql('ALTER TABLE portfolio DROP origin_type');
        }
        if ($table->hasColumn('score')) {
            $this->addSql('ALTER TABLE portfolio DROP score');
        }
        if ($table->hasColumn('is_highlighted')) {
            $this->addSql('ALTER TABLE portfolio DROP is_highlighted');
        }
        if ($table->hasColumn('is_template')) {
            $this->addSql('ALTER TABLE portfolio DROP is_template');
        }

        if ($schema->hasTable('portfolio_attachment')) {
            $this->addSql(
                'DROP TABLE portfolio_attachment'
            );
        }

        if ($schema->hasTable('portfolio_comment')) {
            $this->addSql(
                'DROP TABLE portfolio_comment'
            );
        }

        if ($schema->hasTable('message_feedback')) {
            $this->addSql(
                'DROP TABLE message_feedback'
            );
        }

        $table = $schema->getTable('c_survey_answer');
        if ($table->hasColumn('session_id')) {
            $this->addSql('ALTER TABLE c_survey_answer DROP session_id');
        }
        if ($table->hasColumn('c_lp_item_id')) {
            $this->addSql('ALTER TABLE c_survey_answer DROP c_lp_item_id');
        }

        $table = $schema->getTable('c_survey_invitation');
        if ($table->hasColumn('c_lp_item_id')) {
            $this->addSql('ALTER TABLE c_survey_invitation DROP c_lp_item_id');
        }

        if ($schema->hasTable('notification_event_rel_user')) {
            $this->addSql(
                'DROP TABLE notification_event_rel_user'
            );
        }

        if ($schema->hasTable('c_wiki_category')) {
            $this->addSql(
                'DROP TABLE c_wiki_category'
            );
        }

        if ($schema->hasTable('c_wiki_rel_category')) {
            $this->addSql(
                'DROP TABLE c_wiki_rel_category'
            );
        }

        $table = $schema->getTable('system_template');
        if ($table->hasColumn('language')) {
            $this->addSql('ALTER TABLE system_template DROP language');
        }

        if ($schema->hasTable('notification_event')) {
            $this->addSql(
                'DROP TABLE notification_event'
            );
        }

        if ($schema->hasTable('c_plagiarism_compilatio_docs')) {
            $this->addSql(
                'DROP TABLE c_plagiarism_compilatio_docs'
            );
        }

        $table = $schema->getTable('ticket_ticket');
        if ($table->hasColumn('exercise_id')) {
            $this->addSql('ALTER TABLE ticket_ticket DROP exercise_id');
        }
        if ($table->hasColumn('lp_id')) {
            $this->addSql('ALTER TABLE ticket_ticket DROP lp_id');
        }

        $table = $schema->getTable('c_quiz_question_rel_category');
        if ($table->hasColumn('mandatory')) {
            $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP mandatory');
        }
    }
}
