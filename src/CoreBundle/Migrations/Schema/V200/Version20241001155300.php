<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20241001155300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create and modify tables for peer assessment, autogroups, learning paths, group relations, and student publications.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_peer_autogroup_rel_student_publication (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                student_publication_id INT DEFAULT NULL,
                group_id INT DEFAULT NULL,
                peer_autogroup_id INT DEFAULT NULL,
                vote TINYINT(1) DEFAULT 0,
                date_vote DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                student_publication_parent_id INT DEFAULT NULL,
                student_publication_folder_id INT DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_52659CE4A76ED395 (user_id),
                INDEX IDX_52659CE42F50351C (student_publication_id),
                INDEX IDX_52659CE4FE54D947 (group_id),
                CONSTRAINT FK_52659CE4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
                CONSTRAINT FK_52659CE42F50351C FOREIGN KEY (student_publication_id) REFERENCES c_student_publication (iid) ON DELETE SET NULL,
                CONSTRAINT FK_52659CE4FE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE SET NULL
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_lp_user_access (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                lp_id INT DEFAULT NULL,
                start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                is_open_without_date TINYINT(1) DEFAULT 0,
                PRIMARY KEY(id),
                INDEX IDX_7CAC73F7A76ED395 (user_id),
                INDEX IDX_7CAC73F768DFD1EF (lp_id),
                CONSTRAINT FK_7CAC73F7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL,
                CONSTRAINT FK_7CAC73F768DFD1EF FOREIGN KEY (lp_id) REFERENCES c_lp (iid) ON DELETE SET NULL
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_group_rel_usergroup (
                id INT AUTO_INCREMENT NOT NULL,
                group_id INT NOT NULL,
                usergroup_id INT NOT NULL,
                session_id INT DEFAULT NULL,
                c_id INT DEFAULT NULL,
                ready_autogroup TINYINT(1) NOT NULL,
                PRIMARY KEY(id),
                INDEX IDX_AEE272A8FE54D947 (group_id),
                INDEX IDX_AEE272A8D2112630 (usergroup_id),
                INDEX IDX_AEE272A8613FECDF (session_id),
                INDEX IDX_AEE272A891D79BD3 (c_id),
                CONSTRAINT FK_AEE272A8FE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE,
                CONSTRAINT FK_AEE272A8D2112630 FOREIGN KEY (usergroup_id) REFERENCES usergroup (id) ON DELETE CASCADE,
                CONSTRAINT FK_AEE272A8613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE,
                CONSTRAINT FK_AEE272A891D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_group_category_rel_user (
                id INT NOT NULL,
                group_category_id INT NOT NULL,
                population_type SMALLINT NOT NULL,
                population_id INT NOT NULL,
                status_in_category SMALLINT NOT NULL,
                PRIMARY KEY(id, group_category_id),
                INDEX IDX_4D66D81337FE8223 (group_category_id),
                CONSTRAINT FK_4D66D81337FE8223 FOREIGN KEY (group_category_id) REFERENCES c_group_category (iid) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_peer_assessment (
                id INT AUTO_INCREMENT NOT NULL,
                c_id INT DEFAULT NULL,
                group_category_id INT DEFAULT NULL,
                max_correction_per_student INT DEFAULT 0,
                state INT DEFAULT 0,
                start_work_repository_option INT DEFAULT 0,
                end_work_repository_option INT DEFAULT NULL,
                start_correction_option INT DEFAULT 0,
                end_correction_option INT DEFAULT 0,
                distribute_correction_option INT DEFAULT 0 NOT NULL,
                end_repository_option INT DEFAULT NULL,
                examiner_role_condition TINYINT(1) DEFAULT 0,
                student_access_to_correction TINYINT(1) DEFAULT 0,
                comment_constraint TINYINT(1) DEFAULT 0,
                correct_own_work TINYINT(1) DEFAULT 0,
                correct_benchmark_work TINYINT(1) DEFAULT 0,
                distribution_algorithm TINYINT(1) DEFAULT 0,
                send_work_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                send_work_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                start_correction_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                end_correction_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                PRIMARY KEY(id),
                INDEX IDX_8532634391D79BD3 (c_id),
                INDEX IDX_8532634337FE8223 (group_category_id),
                CONSTRAINT FK_8532634391D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE,
                CONSTRAINT FK_8532634337FE8223 FOREIGN KEY (group_category_id) REFERENCES c_group_category (iid) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_peer_assessment_log (
                id INT AUTO_INCREMENT NOT NULL,
                peer_assessment_id INT DEFAULT NULL,
                user_id INT DEFAULT NULL,
                date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                description VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_71C6D04B672C3733 (peer_assessment_id),
                INDEX IDX_71C6D04BA76ED395 (user_id),
                CONSTRAINT FK_71C6D04B672C3733 FOREIGN KEY (peer_assessment_id) REFERENCES c_peer_assessment (id) ON DELETE CASCADE,
                CONSTRAINT FK_71C6D04BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_peer_assessment_rel_student_publication (
                id INT AUTO_INCREMENT NOT NULL,
                peer_assessment_id INT DEFAULT NULL,
                student_publication_id INT DEFAULT NULL,
                group_id INT DEFAULT NULL,
                student_publication_folder_id INT DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_1B078BC7672C3733 (peer_assessment_id),
                INDEX IDX_1B078BC72F50351C (student_publication_id),
                INDEX IDX_1B078BC7FE54D947 (group_id),
                CONSTRAINT FK_1B078BC7672C3733 FOREIGN KEY (peer_assessment_id) REFERENCES c_peer_assessment (id) ON DELETE CASCADE,
                CONSTRAINT FK_1B078BC72F50351C FOREIGN KEY (student_publication_id) REFERENCES c_student_publication (iid) ON DELETE CASCADE,
                CONSTRAINT FK_1B078BC7FE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_autogroup_user_invitation (
                id INT AUTO_INCREMENT NOT NULL,
                group_category_id INT NOT NULL,
                group_id INT NOT NULL,
                user_id INT NOT NULL,
                confirm TINYINT(1) DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_84AB498037FE8223 (group_category_id),
                INDEX IDX_84AB4980FE54D947 (group_id),
                INDEX IDX_84AB4980A76ED395 (user_id),
                CONSTRAINT FK_84AB498037FE8223 FOREIGN KEY (group_category_id) REFERENCES c_group_category (iid) ON DELETE CASCADE,
                CONSTRAINT FK_84AB4980FE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE,
                CONSTRAINT FK_84AB4980A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_peer_assessment_correction (
                id INT AUTO_INCREMENT NOT NULL,
                peer_assessment_id INT DEFAULT NULL,
                student_group_id INT DEFAULT NULL,
                examiner_id INT DEFAULT NULL,
                examiner_group_id INT DEFAULT NULL,
                total_score INT DEFAULT NULL,
                maximum_score INT DEFAULT NULL,
                delivered TINYINT(1) DEFAULT NULL,
                examiner_folder_id INT DEFAULT NULL,
                examiner_document_id INT DEFAULT NULL,
                completed TINYINT(1) DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_AFB0F2B7672C3733 (peer_assessment_id),
                INDEX IDX_AFB0F2B74DDF95DC (student_group_id),
                CONSTRAINT FK_AFB0F2B7672C3733 FOREIGN KEY (peer_assessment_id) REFERENCES c_peer_assessment (id) ON DELETE CASCADE,
                CONSTRAINT FK_AFB0F2B74DDF95DC FOREIGN KEY (student_group_id) REFERENCES usergroup (id) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_peer_assessment_criteria (
                id INT AUTO_INCREMENT NOT NULL,
                peer_assessment_id INT DEFAULT NULL,
                title VARCHAR(255) DEFAULT NULL,
                description LONGTEXT DEFAULT NULL,
                score INT DEFAULT NULL,
                position INT DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_5025776B672C3733 (peer_assessment_id),
                CONSTRAINT FK_5025776B672C3733 FOREIGN KEY (peer_assessment_id) REFERENCES c_peer_assessment (id) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS c_peer_assessment_correction_criteria (
                id INT AUTO_INCREMENT NOT NULL,
                peer_assessment_correction_id INT DEFAULT NULL,
                peer_assessment_criteria_id INT DEFAULT NULL,
                comment LONGTEXT DEFAULT NULL,
                score INT DEFAULT NULL,
                PRIMARY KEY(id),
                INDEX IDX_C1AB8C19D723148D (peer_assessment_correction_id),
                INDEX IDX_C1AB8C1962488999 (peer_assessment_criteria_id),
                CONSTRAINT FK_C1AB8C19D723148D FOREIGN KEY (peer_assessment_correction_id) REFERENCES c_peer_assessment_correction (id) ON DELETE CASCADE,
                CONSTRAINT FK_C1AB8C1962488999 FOREIGN KEY (peer_assessment_criteria_id) REFERENCES c_peer_assessment_criteria (id) ON DELETE CASCADE
            )
        ");

        $this->addSql("
            ALTER TABLE c_student_publication
            ADD IF NOT EXISTS student_delete_own_publication TINYINT(1) DEFAULT 0,
            ADD IF NOT EXISTS default_visibility TINYINT(1) DEFAULT 0,
            ADD IF NOT EXISTS extensions LONGTEXT DEFAULT NULL
        ");

        $this->addSql("
            ALTER TABLE c_group_category
            ADD IF NOT EXISTS min_student INT DEFAULT NULL,
            ADD IF NOT EXISTS begin_inscription_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
            ADD IF NOT EXISTS end_inscription_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
            ADD IF NOT EXISTS only_me TINYINT(1) DEFAULT 0 NOT NULL,
            ADD COLUMN peer_assessment INT (11) DEFAULT '0',
            ADD COLUMN allow_coach_change_options_groups TINYINT(1) DEFAULT 0 NOT NULL AFTER peer_assessment,
            ADD COLUMN allow_change_group_name INT(11) DEFAULT 1 NULL AFTER allow_coach_change_options_groups,
            ADD COLUMN allow_autogroup TINYINT(1) DEFAULT 0 NOT NULL AFTER allow_change_group_name
        ");

        $this->addSql("
            ALTER TABLE c_lp
            ADD IF NOT EXISTS subscribe_user_by_date TINYINT(1) DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS display_not_allowed_lp TINYINT(1) DEFAULT 0
        ");

        $this->addSql("
            ALTER TABLE c_lp_rel_user
            ADD IF NOT EXISTS group_id INT NOT NULL,
            ADD IF NOT EXISTS start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
            ADD IF NOT EXISTS end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
            ADD IF NOT EXISTS is_open_without_date TINYINT(1) DEFAULT 0 NOT NULL
        ");

        $this->addSql("
            ALTER TABLE c_group_rel_user
            ADD COLUMN ready_autogroup TINYINT(1) NOT NULL AFTER role
        ");

        $this->addSql("
            ALTER TABLE c_student_publication
            ADD COLUMN group_category_id INT DEFAULT 0 NULL AFTER post_group_id
        ");

        $this->addSql("
            CREATE INDEX IF NOT EXISTS IDX_AD97516EFE54D947 ON c_lp_rel_user (group_id)
        ");

        $this->addSql("
            ALTER TABLE c_lp_rel_user
            MODIFY group_id INT DEFAULT NULL
        ");

        $this->addSql("
            UPDATE c_lp_rel_user
            SET group_id = NULL
            WHERE group_id = 0
        ");

        $this->addSql("
            ALTER TABLE c_lp_rel_user
            ADD CONSTRAINT FK_AD97516EFE54D947 FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE
        ");

        $this->addSql("
            ALTER TABLE c_quiz
            ADD IF NOT EXISTS display_chart_degree_certainty INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS send_email_chart_degree_certainty INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS not_display_balance_percentage_categorie_question INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS display_chart_degree_certainty_category INT DEFAULT 0 NOT NULL,
            ADD IF NOT EXISTS gather_questions_categories INT DEFAULT 0 NOT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS c_peer_autogroup_rel_student_publication");
        $this->addSql("DROP TABLE IF EXISTS c_lp_user_access");
        $this->addSql("DROP TABLE IF EXISTS c_group_rel_usergroup");
        $this->addSql("DROP TABLE IF EXISTS c_group_category_rel_user");
        $this->addSql("DROP TABLE IF EXISTS c_peer_assessment");
        $this->addSql("DROP TABLE IF EXISTS c_peer_assessment_log");
        $this->addSql("DROP TABLE IF EXISTS c_peer_assessment_rel_student_publication");
        $this->addSql("DROP TABLE IF EXISTS c_autogroup_user_invitation");
        $this->addSql("DROP TABLE IF EXISTS c_peer_assessment_correction");
        $this->addSql("DROP TABLE IF EXISTS c_peer_assessment_criteria");
        $this->addSql("DROP TABLE IF EXISTS c_peer_assessment_correction_criteria");

        $this->addSql("
            ALTER TABLE c_student_publication
            DROP IF EXISTS student_delete_own_publication,
            DROP IF EXISTS default_visibility,
            DROP IF EXISTS group_category_id,
            DROP IF EXISTS extensions
        ");

        $this->addSql("
            ALTER TABLE c_group_category
            DROP COLUMN min_student,
            DROP COLUMN begin_inscription_date,
            DROP COLUMN end_inscription_date,
            DROP COLUMN only_me,
            DROP COLUMN peer_assessment,
            DROP COLUMN allow_coach_change_options_groups,
            DROP COLUMN allow_change_group_name,
            DROP COLUMN allow_autogroup
        ");

        $this->addSql("
            ALTER TABLE c_group_rel_user
            DROP COLUMN ready_autogroup
        ");


        $this->addSql("
            ALTER TABLE c_lp
            DROP IF EXISTS subscribe_user_by_date,
            DROP IF EXISTS display_not_allowed_lp
        ");

        $this->addSql("
            ALTER TABLE c_lp_rel_user
            DROP IF EXISTS group_id,
            DROP IF EXISTS start_date,
            DROP IF EXISTS end_date,
            DROP IF EXISTS is_open_without_date
        ");

        $this->addSql("
            ALTER TABLE c_quiz
            DROP IF EXISTS display_chart_degree_certainty,
            DROP IF EXISTS send_email_chart_degree_certainty,
            DROP IF EXISTS not_display_balance_percentage_categorie_question,
            DROP IF EXISTS display_chart_degree_certainty_category,
            DROP IF EXISTS gather_questions_categories
        ");
    }
}
