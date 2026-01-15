<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Command\DoctrineMigrationsMigrateCommandDecorator;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240811221400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to update foreign key constraints, drop and create indexes, and alter table structures to ensure data consistency and prevent errors during execution.';
    }

    public function up(Schema $schema): void
    {
        // When enabled, we keep legacy attendance columns to avoid data loss
        // if attendances are being skipped/handled separately.
        $skipAttendances = (bool) getenv(DoctrineMigrationsMigrateCommandDecorator::SKIP_ATTENDANCES_FLAG);

        $this->addSql('SET FOREIGN_KEY_CHECKS = 0;');

        // resource_node
        $this->addSql('ALTER TABLE resource_node DROP FOREIGN KEY IF EXISTS FK_8A5F48FF7EE0A59A');
        $this->addSql('ALTER TABLE resource_node ADD CONSTRAINT FK_8A5F48FF7EE0A59A FOREIGN KEY (resource_format_id) REFERENCES resource_format (id)');

        // user
        if ($schema->getTable('user')->hasColumn('language')) {
            $this->addSql('ALTER TABLE user DROP COLUMN language');
        }

        // settings
        if ($schema->getTable('settings')->hasColumn('title')) {
            $this->addSql('ALTER TABLE settings CHANGE title title LONGTEXT NOT NULL');
        }
        if ($schema->getTable('settings')->hasColumn('comment')) {
            $this->addSql('ALTER TABLE settings CHANGE comment comment LONGTEXT DEFAULT NULL');
        }

        // extra_field
        if ($schema->getTable('extra_field')->hasColumn('helper_text')) {
            $this->addSql('ALTER TABLE extra_field CHANGE helper_text helper_text LONGTEXT DEFAULT NULL');
        }

        // system_template
        if ($schema->getTable('system_template')->hasColumn('language')) {
            $this->addSql('ALTER TABLE system_template CHANGE language language VARCHAR(40) DEFAULT NULL');
        }

        // session_rel_course_rel_user
        $this->addSql('ALTER TABLE session_rel_course_rel_user DROP FOREIGN KEY IF EXISTS FK_720167EA76ED395');
        $this->addSql('ALTER TABLE session_rel_course_rel_user DROP FOREIGN KEY IF EXISTS FK_720167E613FECDF');
        $this->addSql('ALTER TABLE session_rel_course_rel_user ADD CONSTRAINT FK_720167EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session_rel_course_rel_user ADD CONSTRAINT FK_720167E613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');

        // ticket_category_rel_user
        $this->addSql('ALTER TABLE ticket_category_rel_user DROP FOREIGN KEY IF EXISTS FK_5B8A987A76ED395');
        $this->addSql('ALTER TABLE ticket_category_rel_user ADD CONSTRAINT FK_5B8A987A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // track_e_attempt
        $this->addSql('DROP INDEX IF EXISTS course ON track_e_attempt');
        $this->addSql('DROP INDEX IF EXISTS session_id ON track_e_attempt');
        $this->addSql('ALTER TABLE track_e_attempt DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE track_e_attempt DROP COLUMN IF EXISTS session_id');

        // course_request
        $this->addSql('ALTER TABLE course_request DROP FOREIGN KEY IF EXISTS FK_33548A73A76ED395');
        $this->addSql('ALTER TABLE course_request ADD CONSTRAINT FK_33548A73A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // course_rel_user_catalogue
        $this->addSql('ALTER TABLE course_rel_user_catalogue DROP FOREIGN KEY IF EXISTS FK_79CA412EA76ED395');
        $this->addSql('DROP INDEX IF EXISTS course_rel_user_catalogue_user_id ON course_rel_user_catalogue');
        $this->addSql('CREATE INDEX IF NOT EXISTS course_rel_user_catalogue_user_id ON course_rel_user_catalogue (user_id)');
        $this->addSql('ALTER TABLE course_rel_user_catalogue ADD CONSTRAINT FK_79CA412EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE course_rel_user_catalogue DROP FOREIGN KEY IF EXISTS FK_79CA412E91D79BD3');
        $this->addSql('DROP INDEX IF EXISTS course_rel_user_catalogue_c_id ON course_rel_user_catalogue');
        $this->addSql('CREATE INDEX IF NOT EXISTS course_rel_user_catalogue_c_id ON course_rel_user_catalogue (c_id)');
        $this->addSql('ALTER TABLE course_rel_user_catalogue ADD CONSTRAINT FK_79CA412E91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)');

        // ticket_ticket
        $this->addSql('ALTER TABLE ticket_ticket DROP FOREIGN KEY IF EXISTS FK_EB5B2A0D6285C987');
        $this->addSql('ALTER TABLE ticket_ticket DROP FOREIGN KEY IF EXISTS FK_EB5B2A0D6285C231');
        $this->addSql('ALTER TABLE ticket_ticket DROP FOREIGN KEY IF EXISTS FK_EDE2C768613FECDF');
        $this->addSql('ALTER TABLE ticket_ticket DROP FOREIGN KEY IF EXISTS FK_EDE2C768591CC992');
        $this->addSql('DROP INDEX IF EXISTS FK_EB5B2A0D6285C987 ON ticket_ticket');
        $this->addSql('DROP INDEX IF EXISTS FK_EB5B2A0D6285C231 ON ticket_ticket');
        $this->addSql('ALTER TABLE ticket_ticket ADD CONSTRAINT FK_EDE2C768613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_ticket ADD CONSTRAINT FK_EDE2C768591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');

        // skill_rel_item_rel_user
        $this->addSql('ALTER TABLE skill_rel_item_rel_user DROP FOREIGN KEY IF EXISTS FK_D1133E0DA76ED395');
        $this->addSql('ALTER TABLE skill_rel_item_rel_user ADD CONSTRAINT FK_D1133E0DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // usergroup_rel_question
        $this->addSql('ALTER TABLE usergroup_rel_question DROP COLUMN IF EXISTS c_id');

        // fos_group
        $this->addSql('DROP INDEX IF EXISTS UNIQ_4B019DDB5E237E06 ON fos_group');

        // skill_rel_user_comment
        $this->addSql('ALTER TABLE skill_rel_user_comment DROP FOREIGN KEY IF EXISTS FK_7AE9F6B63AF3B65B');
        $this->addSql('ALTER TABLE skill_rel_user_comment ADD CONSTRAINT FK_7AE9F6B63AF3B65B FOREIGN KEY (feedback_giver_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE track_e_attempt_qualify CHANGE marks marks DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE track_e_attempt_qualify DROP FOREIGN KEY IF EXISTS FK_B88BC9BCB5A18F57');
        $this->addSql('ALTER TABLE track_e_attempt_qualify ADD CONSTRAINT FK_B88BC9BCB5A18F57 FOREIGN KEY (exe_id) REFERENCES track_e_exercises (exe_id) ON DELETE CASCADE');

        // ticket_assigned_log
        $this->addSql('ALTER TABLE ticket_assigned_log DROP FOREIGN KEY IF EXISTS FK_54B65868700047D2');
        $this->addSql('ALTER TABLE ticket_assigned_log DROP FOREIGN KEY IF EXISTS FK_54B65868A76ED395');
        $this->addSql('ALTER TABLE ticket_assigned_log ADD CONSTRAINT FK_54B65868700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket_ticket (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_assigned_log ADD CONSTRAINT FK_54B65868A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // course_rel_user
        $this->addSql('ALTER TABLE course_rel_user DROP FOREIGN KEY IF EXISTS FK_92CFD9FEA76ED395');
        $this->addSql('ALTER TABLE course_rel_user ADD CONSTRAINT FK_92CFD9FEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // extra_field_saved_search
        $this->addSql('ALTER TABLE extra_field_saved_search DROP FOREIGN KEY IF EXISTS FK_16ABE32AA76ED395');
        $this->addSql('ALTER TABLE extra_field_saved_search ADD CONSTRAINT FK_16ABE32AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // portfolio
        $this->addSql('ALTER TABLE portfolio DROP FOREIGN KEY IF EXISTS FK_A9ED1062A76ED395');
        $this->addSql('ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED1062A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // permission_rel_role
        $this->addSql('ALTER TABLE permission_rel_role DROP FOREIGN KEY IF EXISTS FK_43723A27FED90CCA');
        $this->addSql('DROP INDEX IF EXISTS idx_43723a27fed90cca ON permission_rel_role');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_14B93D3DFED90CCA ON permission_rel_role (permission_id)');
        $this->addSql('ALTER TABLE permission_rel_role ADD CONSTRAINT FK_43723A27FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id)');

        // track_e_hotpotatoes
        $this->addSql('ALTER TABLE track_e_hotpotatoes ADD COLUMN IF NOT EXISTS score SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE track_e_hotpotatoes ADD COLUMN IF NOT EXISTS max_score SMALLINT NOT NULL');
        $this->addSql('ALTER TABLE track_e_hotpotatoes DROP COLUMN IF EXISTS exe_result');
        $this->addSql('ALTER TABLE track_e_hotpotatoes DROP COLUMN IF EXISTS exe_weighting');

        // notification
        if ($schema->getTable('notification')->hasColumn('title')) {
            $this->addSql('ALTER TABLE notification CHANGE title title VARCHAR(255) DEFAULT NULL');
        }
        if ($schema->getTable('notification')->hasColumn('content')) {
            $this->addSql('ALTER TABLE notification CHANGE content content LONGTEXT DEFAULT NULL');
        }

        // skill_rel_user
        $this->addSql('ALTER TABLE skill_rel_user DROP FOREIGN KEY IF EXISTS FK_79D3D95A5585C142');
        $this->addSql('ALTER TABLE skill_rel_user DROP FOREIGN KEY IF EXISTS FK_79D3D95AA76ED395');
        $this->addSql('ALTER TABLE skill_rel_user DROP FOREIGN KEY IF EXISTS FK_79D3D95A591CC992');
        $this->addSql('ALTER TABLE skill_rel_user DROP FOREIGN KEY IF EXISTS FK_79D3D95A613FECDF');
        $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE');

        // templates
        $this->addSql('ALTER TABLE templates DROP COLUMN IF EXISTS image');

        // ticket_message_attachments
        $this->addSql('ALTER TABLE ticket_message_attachments DROP FOREIGN KEY IF EXISTS FK_70BF9E26537A1329');
        $this->addSql('ALTER TABLE ticket_message_attachments DROP FOREIGN KEY IF EXISTS FK_70BF9E26700047D2');
        $this->addSql('ALTER TABLE ticket_message_attachments ADD CONSTRAINT FK_70BF9E26537A1329 FOREIGN KEY (message_id) REFERENCES ticket_message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_message_attachments ADD CONSTRAINT FK_70BF9E26700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket_ticket (id) ON DELETE CASCADE');

        // session
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY IF EXISTS FK_D044D5D4EF87E278');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY IF EXISTS FK_D044D5D4139DF194');
        $this->addSql('DROP INDEX IF EXISTS idx_id_session_admin_id ON session');
        $this->addSql('ALTER TABLE session DROP COLUMN IF EXISTS session_admin_id');
        $this->addSql('ALTER TABLE session DROP COLUMN IF EXISTS id_coach');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id) ON DELETE CASCADE');

        // permission
        $this->addSql('DROP INDEX IF EXISTS uniq_2dedcc6f989d9b62 ON permission');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_E04992AA989D9B62 ON permission (slug)');

        // ticket_message
        $this->addSql('ALTER TABLE ticket_message DROP FOREIGN KEY IF EXISTS FK_BA71692D700047D2');
        $this->addSql('ALTER TABLE ticket_message ADD CONSTRAINT FK_BA71692D700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket_ticket (id) ON DELETE CASCADE');

        // gradebook_category
        $this->addSql('ALTER TABLE gradebook_category DROP FOREIGN KEY IF EXISTS FK_96A4C705A76ED395');
        $this->addSql('ALTER TABLE gradebook_category ADD CONSTRAINT FK_96A4C705A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // session_rel_user
        $this->addSql('ALTER TABLE session_rel_user DROP FOREIGN KEY IF EXISTS FK_B0D7D4C0A76ED395');
        $this->addSql('ALTER TABLE session_rel_user ADD CONSTRAINT FK_B0D7D4C0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // track_e_downloads
        $this->addSql('DROP INDEX IF EXISTS session_id ON track_e_downloads');
        $this->addSql('ALTER TABLE track_e_downloads DROP COLUMN IF EXISTS session_id');

        // admin
        $this->addSql('DROP INDEX IF EXISTS user_id ON admin');

        // user_rel_user
        if ($schema->getTable('user_rel_user')->hasColumn('last_edit')) {
            $this->addSql('ALTER TABLE user_rel_user DROP COLUMN last_edit');
        }
        if ($schema->getTable('user_rel_user')->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE user_rel_user CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\'');
        }
        if ($schema->getTable('user_rel_user')->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE user_rel_user CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\'');
        }

        // gradebook_comment
        $this->addSql('ALTER TABLE gradebook_comment DROP FOREIGN KEY IF EXISTS FK_C3B70763A76ED395');
        $this->addSql('ALTER TABLE gradebook_comment ADD CONSTRAINT FK_C3B70763A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // course
        if ($schema->getTable('course')->hasColumn('category_code')) {
            $this->addSql('ALTER TABLE course DROP COLUMN category_code');
        }

        // c_attendance
        if ($skipAttendances) {
            $this->write('Skip attendances flag enabled: keeping legacy c_attendance columns (c_id, id, session_id).');
        } else {
            $this->addSql('ALTER TABLE c_attendance DROP COLUMN IF EXISTS c_id');
            $this->addSql('ALTER TABLE c_attendance DROP COLUMN IF EXISTS id');
            $this->addSql('ALTER TABLE c_attendance DROP COLUMN IF EXISTS session_id');
        }

        // c_forum_thread
        $this->addSql('ALTER TABLE c_forum_thread DROP FOREIGN KEY IF EXISTS FK_5DA7884CD4DC43B9');
        $this->addSql('DROP INDEX IF EXISTS idx_forum_thread_forum_id ON c_forum_thread');
        $this->addSql('ALTER TABLE c_forum_thread DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_forum_thread DROP COLUMN IF EXISTS session_id');
        $this->addSql('ALTER TABLE c_forum_thread ADD CONSTRAINT FK_5DA7884CD4DC43B9 FOREIGN KEY (thread_poster_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_calendar_event_attachment
        $this->addSql('ALTER TABLE c_calendar_event_attachment DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_calendar_event_attachment DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_calendar_event_attachment DROP COLUMN IF EXISTS path');
        $this->addSql('ALTER TABLE c_calendar_event_attachment DROP COLUMN IF EXISTS size');

        // c_lp
        $this->addSql('ALTER TABLE c_lp DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_lp DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_lp DROP COLUMN IF EXISTS preview_image');
        $this->addSql('ALTER TABLE c_lp DROP COLUMN IF EXISTS session_id');

        // c_student_publication_rel_document
        $this->addSql('ALTER TABLE c_student_publication_rel_document DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_student_publication_rel_document DROP COLUMN IF EXISTS id');

        // c_student_publication_assignment
        $this->addSql('ALTER TABLE c_student_publication_assignment DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_student_publication_assignment DROP COLUMN IF EXISTS id');

        // c_chat_connected
        $this->addSql('ALTER TABLE c_chat_connected DROP COLUMN IF EXISTS id');

        // c_quiz_answer
        $this->addSql('DROP INDEX IF EXISTS c_id ON c_quiz_answer');
        $this->addSql('ALTER TABLE c_quiz_answer DROP COLUMN IF EXISTS c_id');

        // c_lp_item_view
        $this->addSql('ALTER TABLE c_lp_item_view DROP COLUMN IF EXISTS c_id');

        // c_survey_answer
        $this->addSql('ALTER TABLE c_survey_answer DROP FOREIGN KEY IF EXISTS FK_8A897DD1E27F6BF');
        $this->addSql('ALTER TABLE c_survey_answer DROP FOREIGN KEY IF EXISTS FK_8A897DDB3FE509D');
        $this->addSql('ALTER TABLE c_survey_answer DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_survey_answer DROP COLUMN IF EXISTS answer_id');
        $this->addSql('ALTER TABLE c_survey_answer ADD CONSTRAINT FK_8A897DD1E27F6BF FOREIGN KEY (question_id) REFERENCES c_survey_question (iid)');
        $this->addSql('ALTER TABLE c_survey_answer ADD CONSTRAINT FK_8A897DDB3FE509D FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE CASCADE');

        // c_lp_rel_user
        $this->addSql('ALTER TABLE c_lp_rel_user DROP FOREIGN KEY IF EXISTS FK_AD97516E61220EA6');
        $this->addSql('ALTER TABLE c_lp_rel_user DROP FOREIGN KEY IF EXISTS FK_AD97516EA76ED395');
        $this->addSql('ALTER TABLE c_lp_rel_user ADD CONSTRAINT FK_AD97516E61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE c_lp_rel_user ADD CONSTRAINT FK_AD97516EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_forum_mailcue
        $this->addSql('ALTER TABLE c_forum_mailcue DROP COLUMN IF EXISTS id');

        // c_attendance_result
        $this->addSql('ALTER TABLE c_attendance_result DROP FOREIGN KEY IF EXISTS FK_2C7640A76ED395');
        $this->addSql('ALTER TABLE c_attendance_result DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_attendance_result DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_attendance_result ADD CONSTRAINT FK_2C7640A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_forum_category
        $this->addSql('ALTER TABLE c_forum_category DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_forum_category DROP COLUMN IF EXISTS session_id');
        $this->addSql('ALTER TABLE c_forum_category DROP COLUMN IF EXISTS cat_id');

        // c_thematic
        $this->addSql('ALTER TABLE c_thematic DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_thematic DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_thematic DROP COLUMN IF EXISTS session_id');

        // c_glossary
        $this->addSql('ALTER TABLE c_glossary DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_glossary DROP COLUMN IF EXISTS glossary_id');
        $this->addSql('ALTER TABLE c_glossary DROP COLUMN IF EXISTS session_id');

        // c_link_category
        $this->addSql('ALTER TABLE c_link_category DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_link_category DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_link_category DROP COLUMN IF EXISTS display_order');
        $this->addSql('ALTER TABLE c_link_category DROP COLUMN IF EXISTS session_id');

        // c_announcement
        $this->addSql('ALTER TABLE c_announcement DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_announcement DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_announcement DROP COLUMN IF EXISTS session_id');
        $this->addSql('ALTER TABLE c_announcement CHANGE title title LONGTEXT NOT NULL');

        // c_calendar_event_repeat_not
        $this->addSql('ALTER TABLE c_calendar_event_repeat_not DROP COLUMN IF EXISTS c_id');

        // c_survey
        $this->addSql('ALTER TABLE c_survey DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_survey DROP COLUMN IF EXISTS survey_id');
        $this->addSql('ALTER TABLE c_survey DROP COLUMN IF EXISTS author');
        $this->addSql('ALTER TABLE c_survey DROP COLUMN IF EXISTS session_id');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_survey_code ON c_survey (code)');

        // c_survey_question_option
        $this->addSql('ALTER TABLE c_survey_question_option DROP FOREIGN KEY IF EXISTS FK_C4B6F5F1E27F6BF');
        $this->addSql('DROP INDEX IF EXISTS fk_c4b6f5f1e27f6bf ON c_survey_question_option');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_survey_qo_qid ON c_survey_question_option (question_id)');
        $this->addSql('ALTER TABLE c_survey_question_option ADD CONSTRAINT FK_C4B6F5F1E27F6BF FOREIGN KEY (question_id) REFERENCES c_survey_question (iid) ON DELETE CASCADE');

        // c_attendance_calendar
        $this->addSql('ALTER TABLE c_attendance_calendar DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_attendance_calendar DROP COLUMN IF EXISTS c_id');
        $this->addSql('UPDATE c_attendance_calendar SET blocked = 0 WHERE blocked IS NULL');
        $this->addSql('ALTER TABLE c_attendance_calendar CHANGE blocked blocked TINYINT(1) NOT NULL');

        // c_thematic_plan
        $this->addSql('ALTER TABLE c_thematic_plan DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_thematic_plan DROP COLUMN IF EXISTS id');

        // c_quiz_rel_question
        $this->addSql('ALTER TABLE c_quiz_rel_question DROP COLUMN IF EXISTS c_id');

        // c_course_setting
        $this->addSql('ALTER TABLE c_course_setting DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_course_setting CHANGE value value LONGTEXT DEFAULT NULL');

        // c_group_info
        $this->addSql('ALTER TABLE c_group_info DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_group_info DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_group_info DROP COLUMN IF EXISTS secret_directory');
        $this->addSql('ALTER TABLE c_group_info DROP COLUMN IF EXISTS session_id');

        // c_group_rel_user
        $this->addSql('ALTER TABLE c_group_rel_user DROP FOREIGN KEY IF EXISTS FK_C5D3D49FA76ED395');
        $this->addSql('ALTER TABLE c_group_rel_user DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_group_rel_user ADD CONSTRAINT FK_C5D3D49FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_forum_forum
        $this->addSql('ALTER TABLE c_forum_forum DROP FOREIGN KEY IF EXISTS FK_47A9C9968DFD1EF');
        $this->addSql('ALTER TABLE c_forum_forum DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_forum_forum DROP COLUMN IF EXISTS session_id');
        $this->addSql('DROP INDEX IF EXISTS fk_47a9c9968dfd1ef ON c_forum_forum');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_47A9C9968DFD1EF ON c_forum_forum (lp_id)');
        $this->addSql('ALTER TABLE c_forum_forum ADD CONSTRAINT FK_47A9C9968DFD1EF FOREIGN KEY (lp_id) REFERENCES c_lp (iid) ON DELETE SET NULL');

        // c_wiki_discuss
        $this->addSql('ALTER TABLE c_wiki_discuss DROP COLUMN IF EXISTS id');

        // c_quiz_question_option
        $this->addSql('DROP INDEX IF EXISTS course ON c_quiz_question_option');
        $this->addSql('ALTER TABLE c_quiz_question_option DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_quiz_question_option CHANGE question_id question_id INT DEFAULT NULL');

        // c_tool_intro
        $this->addSql('DROP INDEX IF EXISTS course ON c_tool_intro');
        $this->addSql('ALTER TABLE c_tool_intro DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_tool_intro DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_tool_intro DROP COLUMN IF EXISTS session_id');

        // c_forum_thread_qualify_log
        $this->addSql('ALTER TABLE c_forum_thread_qualify_log DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_forum_thread_qualify_log CHANGE c_id c_id INT NOT NULL');

        // c_thematic_advance
        $this->addSql('ALTER TABLE c_thematic_advance DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_thematic_advance DROP COLUMN IF EXISTS id');

        // c_thematic_advance
        $this->addSql('ALTER TABLE c_thematic_advance DROP FOREIGN KEY IF EXISTS FK_62798E97163DDA15');
        $this->addSql('ALTER TABLE c_thematic_advance ADD CONSTRAINT FK_62798E97163DDA15 FOREIGN KEY (attendance_id) REFERENCES c_attendance (iid) ON DELETE CASCADE');

        // c_student_publication_rel_user
        $this->addSql('ALTER TABLE c_student_publication_rel_user DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_student_publication_rel_user DROP COLUMN IF EXISTS id');

        // c_group_category
        $this->addSql('ALTER TABLE c_group_category DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_group_category DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_group_category DROP COLUMN IF EXISTS display_order');

        // c_announcement_attachment
        $this->addSql('ALTER TABLE c_announcement_attachment DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_announcement_attachment DROP COLUMN IF EXISTS c_id');

        // c_lp_category
        $this->addSql('ALTER TABLE c_lp_category DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_lp_category DROP COLUMN IF EXISTS session_id');

        // c_calendar_event
        $this->addSql('ALTER TABLE c_calendar_event DROP FOREIGN KEY IF EXISTS FK_C_CALENDAR_EVENT_CAREER');
        $this->addSql('ALTER TABLE c_calendar_event DROP FOREIGN KEY IF EXISTS FK_C_CALENDAR_EVENT_PROMOTION');
        $this->addSql('ALTER TABLE c_calendar_event DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_calendar_event DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_calendar_event DROP COLUMN IF EXISTS session_id');
        $this->addSql('DROP INDEX IF EXISTS idx_c_calendar_event_career ON c_calendar_event');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_A0622581B58CDA09 ON c_calendar_event (career_id)');
        $this->addSql('DROP INDEX IF EXISTS idx_c_calendar_event_promotion ON c_calendar_event');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_A0622581139DF194 ON c_calendar_event (promotion_id)');
        $this->addSql('ALTER TABLE c_calendar_event ADD CONSTRAINT FK_C_CALENDAR_EVENT_CAREER FOREIGN KEY (career_id) REFERENCES career (id)');
        $this->addSql('ALTER TABLE c_calendar_event ADD CONSTRAINT FK_C_CALENDAR_EVENT_PROMOTION FOREIGN KEY (promotion_id) REFERENCES promotion (id)');

        // c_student_publication
        $this->addSql('ALTER TABLE c_student_publication DROP FOREIGN KEY IF EXISTS FK_5246F746A76ED395');
        $this->addSql('DROP INDEX IF EXISTS session_id ON c_student_publication');
        $this->addSql('ALTER TABLE c_student_publication DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_student_publication DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_student_publication DROP COLUMN IF EXISTS url');
        $this->addSql('ALTER TABLE c_student_publication DROP COLUMN IF EXISTS url_correction');
        $this->addSql('ALTER TABLE c_student_publication DROP COLUMN IF EXISTS title_correction');
        $this->addSql('ALTER TABLE c_student_publication DROP COLUMN IF EXISTS session_id');
        $this->addSql('ALTER TABLE c_student_publication ADD CONSTRAINT FK_5246F746A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_document
        $this->addSql('ALTER TABLE c_document DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_document DROP COLUMN IF EXISTS path');
        $this->addSql('ALTER TABLE c_document DROP COLUMN IF EXISTS size');
        $this->addSql('ALTER TABLE c_document DROP COLUMN IF EXISTS session_id');
        $this->addSql('ALTER TABLE c_document CHANGE filetype filetype VARCHAR(15) NOT NULL');

        // c_survey_question
        $this->addSql('ALTER TABLE c_survey_question DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_survey_question DROP COLUMN IF EXISTS question_id');
        $this->addSql('ALTER TABLE c_survey_question CHANGE survey_group_pri survey_group_pri INT NOT NULL');

        // c_quiz_rel_category
        $this->addSql('ALTER TABLE c_quiz_rel_category DROP COLUMN IF EXISTS c_id');

        // c_quiz_category
        $this->addSql('ALTER TABLE c_quiz_category DROP FOREIGN KEY IF EXISTS FK_B94C157E91D79BD3');
        $this->addSql('ALTER TABLE c_quiz_category DROP FOREIGN KEY IF EXISTS FK_B94C157E1BAD783F');
        $this->addSql('DROP INDEX IF EXISTS uniq_b94c157e1bad783f ON c_quiz_category');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_2AF3F5101BAD783F ON c_quiz_category (resource_node_id)');
        $this->addSql('DROP INDEX IF EXISTS idx_b94c157e91d79bd3 ON c_quiz_category');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_2AF3F51091D79BD3 ON c_quiz_category (c_id)');
        $this->addSql('ALTER TABLE c_quiz_category ADD CONSTRAINT FK_B94C157E91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE c_quiz_category ADD CONSTRAINT FK_B94C157E1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE');

        // c_dropbox_file
        $this->addSql('ALTER TABLE c_dropbox_file DROP COLUMN IF EXISTS id');

        // c_wiki_mailcue
        $this->addSql('DROP INDEX IF EXISTS c_id ON c_wiki_mailcue');
        $this->addSql('ALTER TABLE c_wiki_mailcue DROP COLUMN IF EXISTS id');
        $this->addSql('CREATE INDEX IF NOT EXISTS c_id ON c_wiki_mailcue (c_id, iid)');

        // c_lp_category_rel_user
        $this->addSql('ALTER TABLE c_lp_category_rel_user DROP FOREIGN KEY IF EXISTS FK_61F0427A76ED395');
        $this->addSql('ALTER TABLE c_lp_category_rel_user DROP FOREIGN KEY IF EXISTS FK_61F042712469DE2');

        // c_lp_category_rel_user
        $this->addSql('ALTER TABLE c_lp_category_rel_user DROP FOREIGN KEY IF EXISTS FK_83D35829A76ED395');
        $this->addSql('ALTER TABLE c_lp_category_rel_user ADD CONSTRAINT FK_83D35829A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX IF EXISTS idx_61f042712469de2 ON c_lp_category_rel_user');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_83D3582912469DE2 ON c_lp_category_rel_user (category_id)');
        $this->addSql('DROP INDEX IF EXISTS idx_61f0427a76ed395 ON c_lp_category_rel_user');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_83D35829A76ED395 ON c_lp_category_rel_user (user_id)');
        $this->addSql('ALTER TABLE c_lp_category_rel_user ADD CONSTRAINT FK_61F042712469DE2 FOREIGN KEY (category_id) REFERENCES c_lp_category (iid)');
        $this->addSql('ALTER TABLE c_lp_category_rel_user ADD CONSTRAINT FK_61F0427A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        // c_lp_view
        $this->addSql('ALTER TABLE c_lp_view DROP FOREIGN KEY IF EXISTS FK_2D2F4F7DA76ED395');
        $this->addSql('DROP INDEX IF EXISTS fk_2d2f4f7da76ed395 ON c_lp_view');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_2D2F4F7DA76ED395 ON c_lp_view (user_id)');
        $this->addSql('ALTER TABLE c_lp_view ADD CONSTRAINT FK_2D2F4F7DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_calendar_event_repeat
        $this->addSql('ALTER TABLE c_calendar_event_repeat DROP COLUMN IF EXISTS c_id');

        // c_attendance_calendar_rel_group
        $this->addSql('ALTER TABLE c_attendance_calendar_rel_group DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_attendance_calendar_rel_group DROP COLUMN IF EXISTS c_id');

        // c_forum_attachment
        $this->addSql('ALTER TABLE c_forum_attachment DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_forum_attachment CHANGE c_id c_id INT NOT NULL');

        // c_survey_invitation
        $this->addSql('ALTER TABLE c_survey_invitation DROP FOREIGN KEY IF EXISTS FK_D0BC7C2A76ED395');
        $this->addSql('ALTER TABLE c_survey_invitation DROP FOREIGN KEY IF EXISTS FK_D0BC7C2B3FE509D');
        $this->addSql('ALTER TABLE c_survey_invitation DROP COLUMN IF EXISTS survey_invitation_id');
        $this->addSql('ALTER TABLE c_survey_invitation DROP COLUMN IF EXISTS survey_code');
        $this->addSql('ALTER TABLE c_survey_invitation DROP COLUMN IF EXISTS user');
        $this->addSql('ALTER TABLE c_survey_invitation ADD CONSTRAINT FK_D0BC7C2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE c_survey_invitation ADD CONSTRAINT FK_D0BC7C2B3FE509D FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE SET NULL');

        // c_student_publication_comment
        $this->addSql('ALTER TABLE c_student_publication_comment DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_student_publication_comment DROP COLUMN IF EXISTS id');

        // c_notebook
        $this->addSql('ALTER TABLE c_notebook DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_notebook DROP COLUMN IF EXISTS course');
        $this->addSql('ALTER TABLE c_notebook DROP COLUMN IF EXISTS session_id');

        // c_forum_notification
        $this->addSql('ALTER TABLE c_forum_notification DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_forum_notification CHANGE c_id c_id INT NOT NULL');

        // c_group_rel_tutor
        $this->addSql('ALTER TABLE c_group_rel_tutor DROP FOREIGN KEY IF EXISTS FK_F6FF71ABA76ED395');
        $this->addSql('ALTER TABLE c_group_rel_tutor DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_group_rel_tutor ADD CONSTRAINT FK_F6FF71ABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_forum_post
        $this->addSql('ALTER TABLE c_forum_post DROP FOREIGN KEY IF EXISTS FK_B5BEF5595BB66C05');
        $this->addSql('DROP INDEX IF EXISTS c_id_visible_post_date ON c_forum_post');
        $this->addSql('ALTER TABLE c_forum_post DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_forum_post DROP COLUMN IF EXISTS post_id');
        $this->addSql('ALTER TABLE c_forum_post ADD CONSTRAINT FK_B5BEF5595BB66C05 FOREIGN KEY (poster_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_attendance_sheet_log
        $this->addSql('ALTER TABLE c_attendance_sheet_log DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_attendance_sheet_log DROP COLUMN IF EXISTS c_id');

        // c_wiki
        $this->addSql('ALTER TABLE c_wiki DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_wiki CHANGE c_id c_id INT NOT NULL');

        // c_forum_thread_qualify
        $this->addSql('ALTER TABLE c_forum_thread_qualify DROP FOREIGN KEY IF EXISTS FK_715FC3A5E5E1B95C');
        $this->addSql('ALTER TABLE c_forum_thread_qualify DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_forum_thread_qualify DROP COLUMN IF EXISTS session_id');
        $this->addSql('ALTER TABLE c_forum_thread_qualify CHANGE c_id c_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_forum_thread_qualify ADD CONSTRAINT FK_715FC3A5E5E1B95C FOREIGN KEY (qualify_user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_quiz
        $this->addSql('DROP INDEX IF EXISTS session_id ON c_quiz');
        $this->addSql('ALTER TABLE c_quiz DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_quiz DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_quiz DROP COLUMN IF EXISTS session_id');
        $this->addSql('ALTER TABLE c_quiz CHANGE hide_attempts_table hide_attempts_table TINYINT(1) DEFAULT 0 NOT NULL');

        // c_link
        $this->addSql('ALTER TABLE c_link DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_link DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_link DROP COLUMN IF EXISTS display_order');
        $this->addSql('ALTER TABLE c_link DROP COLUMN IF EXISTS on_homepage');
        $this->addSql('ALTER TABLE c_link DROP COLUMN IF EXISTS session_id');

        // c_quiz_question_category
        $this->addSql('ALTER TABLE c_quiz_question_category DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_quiz_question_category DROP COLUMN IF EXISTS id');

        // c_course_description
        $this->addSql('ALTER TABLE c_course_description DROP COLUMN IF EXISTS id');
        $this->addSql('ALTER TABLE c_course_description DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_course_description DROP COLUMN IF EXISTS session_id');

        // c_attendance_sheet
        $this->addSql('ALTER TABLE c_attendance_sheet DROP FOREIGN KEY IF EXISTS FK_AD1394FAA76ED395');
        $this->addSql('ALTER TABLE c_attendance_sheet DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_attendance_sheet ADD CONSTRAINT FK_AD1394FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // c_quiz_question
        $this->addSql('ALTER TABLE c_quiz_question DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_quiz_question CHANGE feedback feedback LONGTEXT DEFAULT NULL');

        // c_quiz_question_rel_category
        $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP FOREIGN KEY IF EXISTS FK_A468585C12469DE2');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP FOREIGN KEY IF EXISTS FK_A468585C1E27F6BF');
        $this->addSql('DROP INDEX IF EXISTS idx_qqrc_qid ON c_quiz_question_rel_category');
        $this->addSql('DROP INDEX IF EXISTS `primary` ON c_quiz_question_rel_category');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP COLUMN IF EXISTS iid');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP COLUMN IF EXISTS c_id');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP COLUMN IF EXISTS mandatory');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD CONSTRAINT FK_A468585C12469DE2 FOREIGN KEY (category_id) REFERENCES c_quiz_question_category (iid)');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD CONSTRAINT FK_A468585C1E27F6BF FOREIGN KEY (question_id) REFERENCES c_quiz_question (iid)');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD PRIMARY KEY (question_id, category_id)');

        $this->addSql('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function down(Schema $schema): void {}
}
