<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260611183000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add legacy migration support structures for LP progress, forum comments and tracking.';
    }

    public function up(Schema $schema): void
    {
        $this->addLpViewCompletionDate($schema);
        $this->createTrackProgressTable($schema);
        $this->createLpScheduleTable($schema);
        $this->createForumThreadCommentTable($schema);
        $this->createUserSessionTrackingTable($schema);
        $this->createAutomaticUnregistrationTable($schema);
        $this->createTrackEExercisesBackupTable($schema);
        $this->createQuizQuestionNoDuplicatesTable($schema);
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
        //
        // These structures can contain migrated legal/tracking/certificate evidence.
        // Dropping them in a rollback could delete imported legacy data.
    }

    private function addLpViewCompletionDate(Schema $schema): void
    {
        if (!$schema->hasTable('c_lp_view')) {
            return;
        }

        $table = $schema->getTable('c_lp_view');

        if (!$table->hasColumn('compdate')) {
            $this->addSql('ALTER TABLE c_lp_view ADD compdate DATE DEFAULT NULL');
        }
    }

    private function createTrackProgressTable(Schema $schema): void
    {
        if ($schema->hasTable('track_progress')) {
            $table = $schema->getTable('track_progress');

            if (!$table->hasIndex('idx_track_progress_course_user_lp')) {
                $this->addSql('CREATE INDEX idx_track_progress_course_user_lp ON track_progress (cId, userId, lpId)');
            }

            if (!$table->hasIndex('idx_track_progress_lp')) {
                $this->addSql('CREATE INDEX idx_track_progress_lp ON track_progress (lpId)');
            }

            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE track_progress (
    progressId BIGINT AUTO_INCREMENT NOT NULL,
    cId INT NOT NULL,
    userId BIGINT NOT NULL,
    lpId INT NOT NULL,
    complete VARCHAR(250) NOT NULL,
    INDEX idx_track_progress_course_user_lp (cId, userId, lpId),
    INDEX idx_track_progress_lp (lpId),
    PRIMARY KEY(progressId)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
    }

    private function createLpScheduleTable(Schema $schema): void
    {
        if ($schema->hasTable('paramedic')) {
            $table = $schema->getTable('paramedic');

            if (!$table->hasIndex('idx_lp_schedule_course_lp')) {
                $this->addSql('CREATE INDEX idx_lp_schedule_course_lp ON paramedic (cId, lpId)');
            }

            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE paramedic (
    id INT AUTO_INCREMENT NOT NULL,
    cId BIGINT NOT NULL,
    lpId BIGINT NOT NULL,
    title VARCHAR(100) DEFAULT NULL,
    weekofday VARCHAR(100) DEFAULT NULL,
    INDEX idx_lp_schedule_course_lp (cId, lpId),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
    }

    private function createForumThreadCommentTable(Schema $schema): void
    {
        if ($schema->hasTable('message_comment')) {
            $table = $schema->getTable('message_comment');

            if (!$table->hasIndex('idx_message_comment_forum_thread')) {
                $this->addSql('CREATE INDEX idx_message_comment_forum_thread ON message_comment (forum_id, thread_id)');
            }

            if (!$table->hasIndex('idx_message_comment_receiver')) {
                $this->addSql('CREATE INDEX idx_message_comment_receiver ON message_comment (receiver_id)');
            }

            if (!$table->hasIndex('idx_message_comment_sender')) {
                $this->addSql('CREATE INDEX idx_message_comment_sender ON message_comment (sender_id)');
            }

            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE message_comment (
    id BIGINT AUTO_INCREMENT NOT NULL,
    sender_id BIGINT NOT NULL,
    receiver_id BIGINT NOT NULL,
    forum_id BIGINT NOT NULL,
    thread_id BIGINT NOT NULL,
    comment BLOB NOT NULL,
    INDEX idx_message_comment_forum_thread (forum_id, thread_id),
    INDEX idx_message_comment_receiver (receiver_id),
    INDEX idx_message_comment_sender (sender_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
    }

    private function createUserSessionTrackingTable(Schema $schema): void
    {
        if ($schema->hasTable('tracking_user')) {
            $table = $schema->getTable('tracking_user');

            if (!$table->hasIndex('idx_user_session_tracking_user_active')) {
                $this->addSql('CREATE INDEX idx_user_session_tracking_user_active ON tracking_user (userId, isActive)');
            }

            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE tracking_user (
    trackingId BIGINT AUTO_INCREMENT NOT NULL,
    userId BIGINT NOT NULL,
    sessionTime VARCHAR(200) NOT NULL,
    isActive INT NOT NULL DEFAULT 1,
    INDEX idx_user_session_tracking_user_active (userId, isActive),
    PRIMARY KEY(trackingId)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
    }

    private function createAutomaticUnregistrationTable(Schema $schema): void
    {
        if ($schema->hasTable('unregister_automatic')) {
            $table = $schema->getTable('unregister_automatic');

            if (!$table->hasIndex('idx_automatic_unregistration_user_course')) {
                $this->addSql('CREATE INDEX idx_automatic_unregistration_user_course ON unregister_automatic (userId, cId)');
            }

            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE unregister_automatic (
    id BIGINT AUTO_INCREMENT NOT NULL,
    userId INT NOT NULL,
    cId INT NOT NULL,
    dateDeleted VARCHAR(500) NOT NULL,
    lastaccess VARCHAR(500) NOT NULL,
    INDEX idx_automatic_unregistration_user_course (userId, cId),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
    }

    private function createTrackEExercisesBackupTable(Schema $schema): void
    {
        if ($schema->hasTable('track_e_exercises_backup')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE track_e_exercises_backup (
    exe_id INT AUTO_INCREMENT NOT NULL,
    exe_user_id INT DEFAULT NULL,
    exe_date DATETIME NOT NULL,
    c_id INT NOT NULL,
    exe_exo_id INT NOT NULL,
    exe_result DOUBLE PRECISION NOT NULL,
    exe_weighting DOUBLE PRECISION NOT NULL,
    user_ip VARCHAR(39) NOT NULL,
    status VARCHAR(20) NOT NULL,
    data_tracking LONGTEXT NOT NULL,
    start_date DATETIME NOT NULL,
    steps_counter SMALLINT NOT NULL,
    session_id SMALLINT NOT NULL,
    orig_lp_id INT NOT NULL,
    orig_lp_item_id INT NOT NULL,
    exe_duration INT NOT NULL,
    expired_time_control DATETIME DEFAULT NULL,
    orig_lp_item_view_id INT NOT NULL,
    questions_to_check LONGTEXT NOT NULL,
    INDEX idx_exercises_backup_user (exe_user_id),
    INDEX idx_exercises_backup_course (c_id),
    INDEX idx_exercises_backup_session (session_id),
    PRIMARY KEY(exe_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
    }

    private function createQuizQuestionNoDuplicatesTable(Schema $schema): void
    {
        if ($schema->hasTable('c_quiz_question_noduplicates')) {
            return;
        }

        $this->addSql(<<<'SQL'
CREATE TABLE c_quiz_question_noduplicates (
    iid INT AUTO_INCREMENT NOT NULL,
    c_id INT NOT NULL,
    id INT DEFAULT NULL,
    question LONGTEXT NOT NULL,
    description LONGTEXT DEFAULT NULL,
    ponderation DOUBLE PRECISION NOT NULL DEFAULT 0,
    position INT NOT NULL,
    type TINYINT NOT NULL,
    picture VARCHAR(50) DEFAULT NULL,
    level INT NOT NULL,
    extra VARCHAR(255) DEFAULT NULL,
    question_code VARCHAR(10) DEFAULT NULL,
    INDEX idx_question_nodup_course (c_id),
    INDEX idx_question_nodup_position (position),
    PRIMARY KEY(iid)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
SQL);
    }
}
