<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260114132800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add AI tutor conversation and message tables';
    }

    public function up(Schema $schema): void
    {
        // ai_tutor_conversation
        if (!$schema->hasTable('ai_tutor_conversation')) {
            // Create table + indexes
            $this->addSql(<<<'SQL'
            CREATE TABLE ai_tutor_conversation (
              id INT AUTO_INCREMENT NOT NULL,
              user_id INT NOT NULL,
              course_id INT NOT NULL,
              session_id INT DEFAULT NULL,
              ai_provider VARCHAR(50) NOT NULL,
              provider_conversation_id VARCHAR(255) DEFAULT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              last_message_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
              INDEX IDX_EC011F42A76ED395 (user_id),
              INDEX IDX_EC011F42613FECDF (session_id),
              INDEX idx_ai_tutor_conv_user_course (user_id, course_id),
              INDEX idx_ai_tutor_conv_course (course_id),
              INDEX idx_ai_tutor_conv_last_message (last_message_at),
              UNIQUE INDEX uniq_ai_tutor_conv_user_course_provider (user_id, course_id, ai_provider),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE ai_tutor_conversation ADD CONSTRAINT FK_EC011F42A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE ai_tutor_conversation ADD CONSTRAINT FK_EC011F42591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE ai_tutor_conversation ADD CONSTRAINT FK_EC011F42613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
        } else {
            $table = $schema->getTable('ai_tutor_conversation');

            // Indexes
            if (!$table->hasIndex('idx_ai_tutor_conv_user_course')) {
                $this->addSql('CREATE INDEX idx_ai_tutor_conv_user_course ON ai_tutor_conversation (user_id, course_id)');
            }
            if (!$table->hasIndex('idx_ai_tutor_conv_course')) {
                $this->addSql('CREATE INDEX idx_ai_tutor_conv_course ON ai_tutor_conversation (course_id)');
            }
            if (!$table->hasIndex('idx_ai_tutor_conv_last_message')) {
                $this->addSql('CREATE INDEX idx_ai_tutor_conv_last_message ON ai_tutor_conversation (last_message_at)');
            }
            if (!$table->hasIndex('uniq_ai_tutor_conv_user_course_provider')) {
                $this->addSql('CREATE UNIQUE INDEX uniq_ai_tutor_conv_user_course_provider ON ai_tutor_conversation (user_id, course_id, ai_provider)');
            }

            // FKs
            if (!$table->hasForeignKey('FK_EC011F42A76ED395')) {
                $this->addSql('ALTER TABLE ai_tutor_conversation ADD CONSTRAINT FK_EC011F42A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            }
            if (!$table->hasForeignKey('FK_EC011F42591CC992')) {
                $this->addSql('ALTER TABLE ai_tutor_conversation ADD CONSTRAINT FK_EC011F42591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
            }
            if (!$table->hasForeignKey('FK_EC011F42613FECDF')) {
                $this->addSql('ALTER TABLE ai_tutor_conversation ADD CONSTRAINT FK_EC011F42613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL');
            }
        }

        // ai_tutor_message
        if (!$schema->hasTable('ai_tutor_message')) {
            // Create table + indexes
            $this->addSql(<<<'SQL'
            CREATE TABLE ai_tutor_message (
              id INT AUTO_INCREMENT NOT NULL,
              conversation_id INT NOT NULL,
              role VARCHAR(20) NOT NULL,
              content LONGTEXT NOT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
              INDEX IDX_EF7001E69AC0396 (conversation_id),
              INDEX idx_ai_tutor_msg_conv_created (conversation_id, created_at),
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
            SQL);

            $this->addSql('ALTER TABLE ai_tutor_message ADD CONSTRAINT FK_EF7001E69AC0396 FOREIGN KEY (conversation_id) REFERENCES ai_tutor_conversation (id) ON DELETE CASCADE');
        } else {
            $table = $schema->getTable('ai_tutor_message');

            if (!$table->hasIndex('IDX_EF7001E69AC0396')) {
                $this->addSql('CREATE INDEX IDX_EF7001E69AC0396 ON ai_tutor_message (conversation_id)');
            }
            if (!$table->hasIndex('idx_ai_tutor_msg_conv_created')) {
                $this->addSql('CREATE INDEX idx_ai_tutor_msg_conv_created ON ai_tutor_message (conversation_id, created_at)');
            }

            if (!$table->hasForeignKey('FK_EF7001E69AC0396')) {
                $this->addSql('ALTER TABLE ai_tutor_message ADD CONSTRAINT FK_EF7001E69AC0396 FOREIGN KEY (conversation_id) REFERENCES ai_tutor_conversation (id) ON DELETE CASCADE');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Drop dependent table first.
        if ($schema->hasTable('ai_tutor_message')) {
            $this->addSql('DROP TABLE ai_tutor_message');
        }

        if ($schema->hasTable('ai_tutor_conversation')) {
            $this->addSql('DROP TABLE ai_tutor_conversation');
        }
    }
}
