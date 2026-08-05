<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260619214500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add normalized migration support structures for legacy LP metadata, LP completion and forum feedback.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_lp_view')) {
            $table = $schema->getTable('c_lp_view');

            if (!$table->hasColumn('completion_date')) {
                $this->addSql('ALTER TABLE c_lp_view ADD completion_date DATE DEFAULT NULL');
            }
        }

        $this->ensureExtraField(
            ExtraField::LP_FIELD_TYPE,
            ExtraField::FIELD_TYPE_TEXT,
            'lab_title',
            'Lab title'
        );

        $this->ensureExtraField(
            ExtraField::LP_FIELD_TYPE,
            ExtraField::FIELD_TYPE_TEXT,
            'lab_week',
            'Lab week'
        );

        $this->ensureExtraField(
            ExtraField::LP_VIEW_TYPE,
            ExtraField::FIELD_TYPE_CHECKBOX,
            'manual_completion',
            'Manual completion'
        );

        if (!$schema->hasTable('c_forum_thread_feedback')) {
            $this->addSql(<<<'SQL'
CREATE TABLE c_forum_thread_feedback (
    iid INT AUTO_INCREMENT NOT NULL,
    thread_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    author_id INT DEFAULT NULL,
    qualification_id INT DEFAULT NULL,
    legacy_comment_id INT DEFAULT NULL,
    feedback LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    INDEX idx_cft_feedback_thread_user (thread_id, user_id),
    INDEX idx_cft_feedback_author (author_id),
    INDEX idx_cft_feedback_qualification (qualification_id),
    UNIQUE INDEX uniq_cft_feedback_legacy_comment (legacy_comment_id),
    PRIMARY KEY(iid)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);

            $this->addSql('ALTER TABLE c_forum_thread_feedback ADD CONSTRAINT FK_CFT_FEEDBACK_THREAD FOREIGN KEY (thread_id) REFERENCES c_forum_thread (iid) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE c_forum_thread_feedback ADD CONSTRAINT FK_CFT_FEEDBACK_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE c_forum_thread_feedback ADD CONSTRAINT FK_CFT_FEEDBACK_AUTHOR FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE SET NULL');

            if ($schema->hasTable('c_forum_thread_qualify')) {
                $this->addSql('ALTER TABLE c_forum_thread_feedback ADD CONSTRAINT FK_CFT_FEEDBACK_QUALIFICATION FOREIGN KEY (qualification_id) REFERENCES c_forum_thread_qualify (iid) ON DELETE SET NULL');
            }
        }

        if (!$schema->hasTable('user_activity_status_archive')) {
            $this->addSql(<<<'SQL'
CREATE TABLE user_activity_status_archive (
    iid INT AUTO_INCREMENT NOT NULL,
    legacy_tracking_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    session_time_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
    session_time_raw VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    INDEX idx_user_activity_archive_user_time (user_id, session_time_at),
    UNIQUE INDEX uniq_user_activity_archive_legacy_tracking (legacy_tracking_id),
    PRIMARY KEY(iid)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);

            $this->addSql('ALTER TABLE user_activity_status_archive ADD CONSTRAINT FK_USER_ACTIVITY_ARCHIVE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        }

        if (!$schema->hasTable('automatic_unregistration_log')) {
            $this->addSql(<<<'SQL'
CREATE TABLE automatic_unregistration_log (
    iid INT AUTO_INCREMENT NOT NULL,
    legacy_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    course_id INT DEFAULT NULL,
    legacy_course_id INT DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
    deleted_at_raw VARCHAR(255) DEFAULT NULL,
    last_access_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
    last_access_raw VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
    INDEX idx_auto_unregistration_user_course (user_id, course_id),
    INDEX idx_auto_unregistration_deleted_at (deleted_at),
    UNIQUE INDEX uniq_auto_unregistration_legacy_id (legacy_id),
    PRIMARY KEY(iid)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);

            $this->addSql('ALTER TABLE automatic_unregistration_log ADD CONSTRAINT FK_AUTO_UNREGISTRATION_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE automatic_unregistration_log ADD CONSTRAINT FK_AUTO_UNREGISTRATION_COURSE FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
        // These structures can contain migrated completion evidence, LP metadata and audit data.
    }

    private function ensureExtraField(int $itemType, int $valueType, string $variable, string $displayText): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(1) FROM extra_field WHERE variable = :variable AND item_type = :item_type',
            [
                'variable' => $variable,
                'item_type' => $itemType,
            ]
        );

        if ($exists > 0) {
            return;
        }

        $this->addSql(
            'INSERT INTO extra_field (item_type, value_type, variable, display_text, description, visible_to_self, visible_to_others, changeable, filter, created_at, auto_remove) VALUES (:item_type, :value_type, :variable, :display_text, :description, :visible_to_self, :visible_to_others, :changeable, :filter, NOW(), :auto_remove)',
            [
                'item_type' => $itemType,
                'value_type' => $valueType,
                'variable' => $variable,
                'display_text' => $displayText,
                'description' => '',
                'visible_to_self' => 0,
                'visible_to_others' => 0,
                'changeable' => 0,
                'filter' => 0,
                'auto_remove' => 0,
            ]
        );
    }
}
