<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * The V200 migrations from Version20201218132719 on load surveys, wikis, skills, quizzes, glossaries,
 * messages and certificates through the ORM, whose mapping includes columns that later migrations add
 * (V300/V310). Without them, a migration from 1.11 fails on "Unknown column".
 *
 * Each block copies the definition of the later migration, indexes and foreign keys included, under the
 * same guard: that migration then finds the column and leaves it as is. See Version20201210100013 for
 * the same issue with the rooms.
 */
final class Version20201218132718 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early the columns that later migrations add and the V200 ORM hydration needs';
    }

    public function up(Schema $schema): void
    {
        // V310\Version20260924153000
        if ($schema->hasTable('message')) {
            $table = $schema->getTable('message');
            if (!$table->hasColumn('mail_answer')) {
                $this->addSql('ALTER TABLE message ADD mail_answer TINYINT(1) NOT NULL DEFAULT 0');
            }
            if (!$table->hasColumn('mail_inbound_id')) {
                $this->addSql('ALTER TABLE message ADD mail_inbound_id VARCHAR(64) DEFAULT NULL');
            }
            if (!$table->hasIndex('uniq_message_mail_inbound_id')) {
                $this->addSql('CREATE UNIQUE INDEX uniq_message_mail_inbound_id ON message (mail_inbound_id)');
            }
        }

        // V310\Version20260924112000 and V310\Version20260924153000
        if ($schema->hasTable('message_rel_user')) {
            $table = $schema->getTable('message_rel_user');
            if (!$table->hasColumn('mail_tracking_token')) {
                $this->addSql('ALTER TABLE message_rel_user ADD mail_tracking_token VARCHAR(64) DEFAULT NULL');
            }
            if (!$table->hasColumn('mail_opened_at')) {
                $this->addSql('ALTER TABLE message_rel_user ADD mail_opened_at DATETIME DEFAULT NULL');
            }
            if (!$table->hasIndex('uniq_message_rel_user_mail_tracking_token')) {
                $this->addSql('CREATE UNIQUE INDEX uniq_message_rel_user_mail_tracking_token ON message_rel_user (mail_tracking_token)');
            }
            if (!$table->hasColumn('mail_reply_token')) {
                $this->addSql('ALTER TABLE message_rel_user ADD mail_reply_token VARCHAR(64) DEFAULT NULL');
            }
            if (!$table->hasIndex('uniq_message_rel_user_mail_reply_token')) {
                $this->addSql('CREATE UNIQUE INDEX uniq_message_rel_user_mail_reply_token ON message_rel_user (mail_reply_token)');
            }
        }

        // V310\Version20260929131500 and V310\Version20261029065000
        if ($schema->hasTable('c_quiz')) {
            $table = $schema->getTable('c_quiz');
            if (!$table->hasColumn('allow_question_feedback_reports')) {
                $this->addSql('ALTER TABLE c_quiz ADD allow_question_feedback_reports TINYINT(1) DEFAULT 0 NOT NULL');
            }
            if (!$table->hasColumn('show_question_max_score')) {
                $this->addSql('ALTER TABLE c_quiz ADD show_question_max_score TINYINT(1) DEFAULT 1 NOT NULL');
            }
        }

        // V310\Version20260928132000
        if (!$schema->hasTable('c_glossary_category')) {
            $this->addSql(
                'CREATE TABLE c_glossary_category (
                    iid INT AUTO_INCREMENT NOT NULL,
                    resource_node_id INT DEFAULT NULL,
                    title LONGTEXT NOT NULL,
                    UNIQUE INDEX UNIQ_GLOSSARY_CATEGORY_RESOURCE_NODE (resource_node_id),
                    PRIMARY KEY(iid)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_glossary_category ADD CONSTRAINT FK_GLOSSARY_CATEGORY_RESOURCE_NODE FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
        }
        if ($schema->hasTable('c_glossary') && !$schema->getTable('c_glossary')->hasColumn('category_id')) {
            $this->addSql('ALTER TABLE c_glossary ADD category_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_GLOSSARY_CATEGORY ON c_glossary (category_id)');
            $this->addSql(
                'ALTER TABLE c_glossary ADD CONSTRAINT FK_GLOSSARY_CATEGORY FOREIGN KEY (category_id) REFERENCES c_glossary_category (iid) ON DELETE SET NULL'
            );
        }

        // V310\Version20261012013000
        if ($schema->hasTable('c_survey') && !$schema->getTable('c_survey')->hasColumn('results_hidden_from_teacher')) {
            $this->addSql('ALTER TABLE c_survey ADD results_hidden_from_teacher TINYINT(1) NOT NULL DEFAULT 0');
        }

        // V310\Version20261015103000
        if ($schema->hasTable('c_wiki') && !$schema->getTable('c_wiki')->hasColumn('etherpad_id')) {
            $this->addSql('ALTER TABLE c_wiki ADD etherpad_id VARCHAR(255) DEFAULT NULL');
        }

        // V310\Version20261010223000
        if ($schema->hasTable('skill_level') && !$schema->getTable('skill_level')->hasColumn('description')) {
            $this->addSql('ALTER TABLE skill_level ADD description LONGTEXT DEFAULT NULL');
            $this->addSql("UPDATE skill_level SET description = '' WHERE description IS NULL");
            $this->addSql('ALTER TABLE skill_level MODIFY description LONGTEXT NOT NULL');
        }
        if ($schema->hasTable('skill') && !$schema->getTable('skill')->hasColumn('acquirable')) {
            $this->addSql('ALTER TABLE skill ADD acquirable TINYINT(1) DEFAULT 1 NOT NULL');
        }

        // V310\Version20260929203500
        if ($schema->hasTable('skill_rel_user')) {
            $table = $schema->getTable('skill_rel_user');
            if (!$table->hasColumn('status')) {
                $this->addSql('ALTER TABLE skill_rel_user ADD status INT DEFAULT 1 NOT NULL');
            }
            if (!$table->hasColumn('last_status_update_user_id')) {
                $this->addSql('ALTER TABLE skill_rel_user ADD last_status_update_user_id INT DEFAULT NULL');
            }
        }

        // V300\Version20260828150000, V310\Version20261021150000 and V310\Version20261021160000
        if ($schema->hasTable('gradebook_certificate')) {
            $table = $schema->getTable('gradebook_certificate');
            if (!$table->hasColumn('expiry_date')) {
                $this->addSql("ALTER TABLE gradebook_certificate ADD expiry_date DATE DEFAULT NULL COMMENT '(DC2Type:date)'");
            }
            if (!$table->hasIndex('idx_gradebook_certificate_expiry_date')) {
                $this->addSql('CREATE INDEX idx_gradebook_certificate_expiry_date ON gradebook_certificate (expiry_date)');
            }
            if (!$table->hasColumn('user_publication_approval_date')) {
                $this->addSql('ALTER TABLE gradebook_certificate ADD user_publication_approval_date DATETIME DEFAULT NULL');
            }
            if (!$table->hasColumn('user_publication_revocation_date')) {
                $this->addSql('ALTER TABLE gradebook_certificate ADD user_publication_revocation_date DATETIME DEFAULT NULL');
            }
            if (!$table->hasColumn('user_publication_consent_scope')) {
                $this->addSql('ALTER TABLE gradebook_certificate ADD user_publication_consent_scope VARCHAR(40) DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void {}
}
