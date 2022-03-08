<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20180904175500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate track_e_exercises, track_e_login';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE track_e_exercises SET expired_time_control = NULL WHERE CAST(expired_time_control AS CHAR(20)) = "0000-00-00 00:00:00"'
        );
        $this->addSql('DELETE FROM track_e_exercises WHERE exe_user_id = 0 OR exe_user_id IS NULL');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE exe_user_id exe_user_id INT NOT NULL');

        $this->addSql('UPDATE track_e_exercises SET session_id = NULL WHERE session_id = 0');

        $this->addSql('DELETE FROM track_e_exercises WHERE session_id IS NOT NULL AND session_id NOT IN (SELECT id FROM session)');
        $this->addSql('ALTER TABLE track_e_exercises CHANGE session_id session_id INT NOT NULL');

        if (!$schema->hasTable('attempt_file')) {
            $this->addSql("CREATE TABLE attempt_file (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', attempt_id INT DEFAULT NULL, asset_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)', comment LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_4F22BDF0B191BE6B (attempt_id), INDEX IDX_4F22BDF05DA1941 (asset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;");
            $this->addSql('ALTER TABLE attempt_file ADD CONSTRAINT FK_4F22BDF0B191BE6B FOREIGN KEY (attempt_id) REFERENCES track_e_attempt (id) ON DELETE CASCADE;');
            $this->addSql('ALTER TABLE attempt_file ADD CONSTRAINT FK_4F22BDF05DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) ON DELETE CASCADE;');
        }

        if (!$schema->hasTable('attempt_feedback')) {
            $this->addSql("CREATE TABLE attempt_feedback (id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)', attempt_id INT DEFAULT NULL, user_id INT DEFAULT NULL, asset_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)', comment LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_BA30B2FEB191BE6B (attempt_id), INDEX IDX_BA30B2FEA76ED395 (user_id), INDEX IDX_BA30B2FE5DA1941 (asset_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;");
            $this->addSql('ALTER TABLE attempt_feedback ADD CONSTRAINT FK_BA30B2FEB191BE6B FOREIGN KEY (attempt_id) REFERENCES track_e_attempt (id) ON DELETE CASCADE;');
            $this->addSql('ALTER TABLE attempt_feedback ADD CONSTRAINT FK_BA30B2FEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
            $this->addSql('ALTER TABLE attempt_feedback ADD CONSTRAINT FK_BA30B2FE5DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id) ON DELETE CASCADE;');
        }

        $table = $schema->getTable('track_e_login');
        if (!$table->hasIndex('idx_track_e_login_date')) {
            $this->addSql('CREATE INDEX idx_track_e_login_date ON track_e_login (login_date)');
        }

        $this->addSql('DELETE FROM track_e_login WHERE login_user_id NOT IN (SELECT id FROM user)');
        $this->addSql('ALTER TABLE track_e_login CHANGE login_user_id login_user_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_C8EA20EB743CDE8')) {
            $this->addSql('ALTER TABLE track_e_login ADD CONSTRAINT FK_C8EA20EB743CDE8 FOREIGN KEY (login_user_id) REFERENCES user (id) ON DELETE CASCADE');
        }

        $table = $schema->getTable('track_e_default');
        if (!$table->hasIndex('idx_default_user_id')) {
            $this->addSql('CREATE INDEX idx_default_user_id ON track_e_default (default_user_id)');
        }

        $this->addSql('UPDATE track_e_default SET default_date = NOW() WHERE default_date is NULL OR default_date = 0');
        $this->addSql('DELETE FROM track_e_default WHERE default_user_id NOT IN (SELECT id FROM user)');
        $this->addSql('ALTER TABLE track_e_default CHANGE default_date default_date DATETIME NOT NULL');

        $table = $schema->getTable('track_e_course_access');
        if (!$table->hasIndex('user_course_session_date')) {
            $this->addSql(
                'CREATE INDEX user_course_session_date ON track_e_course_access (user_id, c_id, session_id, login_course_date)'
            );
        }

        $this->addSql('DELETE FROM track_e_course_access WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('ALTER TABLE track_e_course_access CHANGE user_id user_id INT DEFAULT NULL');
        if (!$table->hasForeignKey('FK_E8C05DC5A76ED395')) {
            $this->addSql(
                'ALTER TABLE track_e_course_access ADD CONSTRAINT FK_E8C05DC5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        $table = $schema->getTable('track_e_access');
        if (!$table->hasIndex('user_course_session_date')) {
            $this->addSql(
                'CREATE INDEX user_course_session_date ON track_e_access (access_user_id, c_id, access_session_id, access_date)'
            );
        }

        $table = $schema->hasTable('track_e_access_complete');
        if (false === $table) {
            $this->addSql(
                'CREATE TABLE track_e_access_complete (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, date_reg DATETIME NOT NULL, tool VARCHAR(255) NOT NULL, tool_id INT NOT NULL, tool_id_detail INT NOT NULL, action VARCHAR(255) NOT NULL, action_details VARCHAR(255) NOT NULL, current_id INT NOT NULL, ip_user VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, session_id INT NOT NULL, c_id INT NOT NULL, ch_sid VARCHAR(255) NOT NULL, login_as INT NOT NULL, info LONGTEXT NOT NULL, url LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
            $this->addSql('ALTER TABLE track_e_access_complete ADD CONSTRAINT FK_57FAFDBFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('CREATE INDEX IDX_57FAFDBFA76ED395 ON track_e_access_complete (user_id)');
        }
        //$this->addSql('ALTER TABLE track_e_hotpotatoes CHANGE exe_result score SMALLINT NOT NULL');
        //$this->addSql('ALTER TABLE track_e_hotpotatoes CHANGE exe_weighting max_score SMALLINT NOT NULL');

        $table = $schema->getTable('track_e_exercises');

        $this->addSql('ALTER TABLE track_e_exercises CHANGE session_id session_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE session_id session_id INT DEFAULT NULL');
        $this->addSql('UPDATE track_e_attempt SET session_id = NULL WHERE session_id = 0');
        $this->addSql('DELETE FROM track_e_attempt WHERE session_id IS NOT NULL AND session_id NOT IN (select id FROM session)');

        $this->addSql('UPDATE track_e_exercises SET session_id = NULL WHERE session_id = 0');
        $this->addSql('DELETE FROM track_e_exercises WHERE session_id IS NOT NULL AND session_id NOT IN (select id FROM session)');

        $this->addSql('UPDATE track_e_exercises SET exe_user_id = NULL WHERE exe_user_id = 0');
        $this->addSql('DELETE FROM track_e_exercises WHERE exe_user_id IS NOT NULL AND exe_user_id NOT IN (select id FROM user)');

        if (!$table->hasForeignKey('FK_AA0DA082613FECDF')) {
            $this->addSql('ALTER TABLE track_e_exercises ADD CONSTRAINT FK_AA0DA082613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');
        }

        if (!$table->hasForeignKey('FK_AA0DA082613FECDF')) {
            $this->addSql('ALTER TABLE track_e_exercises ADD CONSTRAINT FK_AA0DA08291D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');
        }

        if (!$table->hasForeignKey('FK_AA0DA082F6A6790')) {
            $this->addSql('ALTER TABLE track_e_exercises ADD CONSTRAINT FK_AA0DA082F6A6790 FOREIGN KEY (exe_user_id) REFERENCES user (id) ON DELETE CASCADE ');
        }

        if ($table->hasColumn('exe_weighting')) {
            $this->addSql('ALTER TABLE track_e_exercises CHANGE exe_weighting max_score DOUBLE PRECISION NOT NULL');
        }
        if ($table->hasColumn('exe_result')) {
            $this->addSql('ALTER TABLE track_e_exercises CHANGE exe_result score DOUBLE PRECISION NOT NULL');
        }

        if (!$table->hasColumn('blocked_categories')) {
            $this->addSql('ALTER TABLE track_e_exercises ADD blocked_categories LONGTEXT DEFAULT NULL');
        }

        $this->addSql('ALTER TABLE track_e_exercises CHANGE exe_exo_id exe_exo_id INT DEFAULT NULL');
        $this->addSql("UPDATE track_e_exercises SET exe_exo_id = NULL WHERE exe_exo_id NOT IN (SELECT iid FROM c_quiz)");

        if (!$table->hasForeignKey('FK_AA0DA082B9773F9E')) {
            $this->addSql('ALTER TABLE track_e_exercises ADD CONSTRAINT FK_AA0DA082B9773F9E FOREIGN KEY (exe_exo_id) REFERENCES c_quiz (iid) ON DELETE SET NULL');
        }

        if (!$table->hasIndex('IDX_AA0DA082B9773F9E')) {
            $this->addSql('CREATE INDEX IDX_AA0DA082B9773F9E ON track_e_exercises (exe_exo_id)');
        }

        $table = $schema->getTable('track_e_hotspot');

        $this->addSql('DELETE FROM track_e_hotspot WHERE c_id NOT IN (SELECT id FROM course)');

        if (!$table->hasForeignKey('FK_A89CC3B691D79BD3')) {
            $this->addSql(
                'ALTER TABLE track_e_hotspot ADD CONSTRAINT FK_A89CC3B691D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)'
            );
        }
        if (false === $table->hasIndex('IDX_A89CC3B691D79BD3')) {
            $this->addSql('CREATE INDEX IDX_A89CC3B691D79BD3 ON track_e_hotspot (c_id)');
        }

        $table = $schema->getTable('track_e_attempt');

        if (!$table->hasIndex('course')) {
            $this->addSql('DROP INDEX course ON track_e_attempt;');
        }

        if (!$table->hasIndex('session_id')) {
            $this->addSql('DROP INDEX session_id ON track_e_attempt;');
        }

        $this->addSql('DELETE FROM track_e_attempt WHERE c_id NOT IN (SELECT id FROM course)');
        $this->addSql('DELETE FROM track_e_attempt WHERE user_id NOT IN (SELECT id FROM user)');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE c_id c_id INT DEFAULT NULL');

        $this->addSql('UPDATE track_e_attempt SET tms = NOW() WHERE tms IS NULL OR tms = 0');
        $this->addSql('ALTER TABLE track_e_attempt CHANGE tms tms DATETIME NOT NULL');
        $this->addSql('DELETE FROM track_e_attempt WHERE exe_id = 0 OR exe_id IS NULL');
        $this->addSql('DELETE FROM track_e_attempt WHERE exe_id NOT IN (select exe_id FROM track_e_exercises)');

        if (!$table->hasForeignKey('FK_F8C342C3B5A18F57')) {
            $this->addSql(
                'ALTER TABLE track_e_attempt ADD CONSTRAINT FK_F8C342C3B5A18F57 FOREIGN KEY (exe_id) REFERENCES track_e_exercises (exe_id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('idx_track_e_attempt_tms')) {
            $this->addSql('CREATE INDEX idx_track_e_attempt_tms ON track_e_attempt (tms)');
        }

        if (!$table->hasColumn('seconds_spent')) {
            $this->addSql('ALTER TABLE track_e_attempt ADD seconds_spent INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        }

        if (!$table->hasForeignKey('FK_F8C342C3A76ED395')) {
            $this->addSql('ALTER TABLE track_e_attempt ADD CONSTRAINT FK_F8C342C3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
        }

        if (!$schema->hasTable('track_e_exercise_confirmation')) {
            $this->addSql(
                "CREATE TABLE track_e_exercise_confirmation (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, course_id INT NOT NULL, attempt_id INT NOT NULL, quiz_id INT NOT NULL, session_id INT NOT NULL, confirmed TINYINT(1) DEFAULT '0' NOT NULL, questions_count INT NOT NULL, saved_answers_count INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_980C28C7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;"
            );
            $this->addSql(
                'ALTER TABLE track_e_exercise_confirmation ADD CONSTRAINT FK_980C28C7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;'
            );
        }

        $table = $schema->getTable('track_e_attempt_recording');
        if (false === $table->hasColumn('answer')) {
            $this->addSql('ALTER TABLE track_e_attempt_recording ADD answer LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
