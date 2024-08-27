<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240112131200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Changes fields type bigint by integer';
    }

    public function up(Schema $schema): void
    {
        error_log('Changes fields type bigint by integer');

        // Temporarily disable foreign key and unique checks to prevent errors during the schema changes
        $this->addSql('SET FOREIGN_KEY_CHECKS=0;');
        $this->addSql('SET UNIQUE_CHECKS=0;');

        if ($schema->hasTable('user_rel_user')) {
            error_log('Perform the changes in the user_rel_user table');
            $this->addSql('ALTER TABLE user_rel_user CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('resource_right')) {
            error_log('Perform the changes in the resource_right table');
            $this->addSql('ALTER TABLE resource_right CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE resource_link_id resource_link_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('page')) {
            error_log('Perform the changes in the page table');
            $this->addSql('ALTER TABLE page CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE category_id category_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('page_category')) {
            error_log('Perform the changes in the page_category table');
            $this->addSql('ALTER TABLE page_category CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('agenda_event_invitee')) {
            error_log('Perform the changes in the agenda_event_invitee table');
            $this->addSql('ALTER TABLE agenda_event_invitee DROP FOREIGN KEY FK_4F5757FEA35D7AF0');
            $this->addSql('ALTER TABLE agenda_event_invitee CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE invitation_id invitation_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('branch_transaction')) {
            error_log('Perform the changes in the branch_transaction table');
            $this->addSql('ALTER TABLE branch_transaction CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE transaction_id transaction_id INT NOT NULL;');
        }

        if ($schema->hasTable('resource_node')) {
            error_log('Perform the changes in the resource_node table');
            $this->addSql('ALTER TABLE resource_node CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE parent_id parent_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('resource_file')) {
            error_log('Perform the changes in the resource_file table');
            $this->addSql('ALTER TABLE resource_file CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('resource_user_tag')) {
            error_log('Perform the changes in the resource_user_tag table');
            $this->addSql('ALTER TABLE resource_user_tag CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE tag_id tag_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('resource_tag')) {
            error_log('Perform the changes in the resource_tag table');
            $this->addSql('ALTER TABLE resource_tag CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('gradebook_comment')) {
            error_log('Perform the changes in the gradebook_comment table');
            $this->addSql('ALTER TABLE gradebook_comment CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('gradebook_certificate')) {
            error_log('Perform the changes in the gradebook_certificate table');
            $this->addSql('ALTER TABLE gradebook_certificate CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('social_post')) {
            error_log('Perform the changes in the social_post table');
            $this->addSql('ALTER TABLE social_post CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE parent_id parent_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('social_post_feedback')) {
            error_log('Perform the changes in the social_post_feedback table');
            $this->addSql('ALTER TABLE social_post_feedback CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE social_post_id social_post_id INT NOT NULL;');
        }

        if ($schema->hasTable('branch_sync')) {
            error_log('Perform the changes in the branch_sync table');
            $this->addSql('ALTER TABLE branch_sync CHANGE last_sync_trans_id last_sync_trans_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('track_e_lastaccess')) {
            error_log('Perform the changes in the track_e_lastaccess table');
            $this->addSql('ALTER TABLE track_e_lastaccess CHANGE access_id access_id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('notification')) {
            error_log('Perform the changes in the notification table');
            $this->addSql('ALTER TABLE notification CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('message_feedback')) {
            error_log('Perform the changes in the message_feedback table');
            $this->addSql('ALTER TABLE message_feedback CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE message_id message_id INT NOT NULL;');
        }

        if ($schema->hasTable('c_quiz_rel_category')) {
            error_log('Perform the changes in the c_quiz_rel_category table');
            $this->addSql('ALTER TABLE c_quiz_rel_category CHANGE iid iid INT AUTO_INCREMENT NOT NULL;');
        }

        if ($schema->hasTable('c_lp_iv_interaction')) {
            error_log('Perform the changes in the c_lp_iv_interaction table');
            $this->addSql('ALTER TABLE c_lp_iv_interaction CHANGE lp_iv_id lp_iv_id INT NOT NULL;');
        }

        if ($schema->hasTable('c_lp_iv_objective')) {
            error_log('Perform the changes in the c_lp_iv_objective table');
            $this->addSql('ALTER TABLE c_lp_iv_objective CHANGE lp_iv_id lp_iv_id INT NOT NULL;');
        }

        if ($schema->hasTable('user')) {
            error_log('Perform the changes in the user table');
            $this->addSql('ALTER TABLE user CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('illustration')) {
            error_log('Perform the changes in the illustration table');
            $this->addSql('ALTER TABLE illustration CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('personal_file')) {
            error_log('Perform the changes in the personal_file table');
            $this->addSql('ALTER TABLE personal_file CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('access_url')) {
            error_log('Perform the changes in the access_url table');
            $this->addSql('ALTER TABLE access_url CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('social_post_attachments')) {
            error_log('Perform the changes in the social_post_attachments table');
            $this->addSql('ALTER TABLE social_post_attachments CHANGE social_post_id social_post_id INT DEFAULT NULL, CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('message_attachment')) {
            error_log('Perform the changes in the message_attachment table');
            $this->addSql('ALTER TABLE message_attachment CHANGE message_id message_id INT NOT NULL, CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('resource_comment')) {
            error_log('Perform the changes in the resource_comment table');
            $this->addSql('ALTER TABLE resource_comment CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE resource_node_id resource_node_id INT DEFAULT NULL, CHANGE parent_id parent_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('usergroup')) {
            error_log('Perform the changes in the usergroup table');
            $this->addSql('ALTER TABLE usergroup CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('course')) {
            error_log('Perform the changes in the course table');
            $this->addSql('ALTER TABLE course CHANGE resource_node_id resource_node_id INT DEFAULT NULL, CHANGE disk_quota disk_quota INT DEFAULT NULL;');
        }

        if ($schema->hasTable('ticket_message_attachments')) {
            error_log('Perform the changes in the ticket_message_attachments table');
            $this->addSql('ALTER TABLE ticket_message_attachments CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('resource_link')) {
            error_log('Perform the changes in the resource_link table');
            $this->addSql('ALTER TABLE resource_link CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_student_publication_correction')) {
            error_log('Perform the changes in the c_student_publication_correction table');
            $this->addSql('ALTER TABLE c_student_publication_correction CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_forum_attachment')) {
            error_log('Perform the changes in the c_forum_attachment table');
            $this->addSql('ALTER TABLE c_forum_attachment CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_calendar_event')) {
            error_log('Perform the changes in the c_calendar_event table');
            $this->addSql('ALTER TABLE c_calendar_event CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_tool')) {
            error_log('Perform the changes in the c_tool table');
            $this->addSql('ALTER TABLE c_tool CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_glossary')) {
            error_log('Perform the changes in the c_glossary table');
            $this->addSql('ALTER TABLE c_glossary CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_thematic')) {
            error_log('Perform the changes in the c_thematic table');
            $this->addSql('ALTER TABLE c_thematic CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_lp_category')) {
            error_log('Perform the changes in the c_lp_category table');
            $this->addSql('ALTER TABLE c_lp_category CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_shortcut')) {
            error_log('Perform the changes in the c_shortcut table');
            $this->addSql('ALTER TABLE c_shortcut CHANGE shortcut_node_id shortcut_node_id INT DEFAULT NULL, CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_forum_forum')) {
            error_log('Perform the changes in the c_forum_forum table');
            $this->addSql('ALTER TABLE c_forum_forum CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_quiz_question_category')) {
            error_log('Perform the changes in the c_quiz_question_category table');
            $this->addSql('ALTER TABLE c_quiz_question_category CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_student_publication')) {
            error_log('Perform the changes in the c_student_publication table');
            $this->addSql('ALTER TABLE c_student_publication CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_link')) {
            error_log('Perform the changes in the c_link table');
            $this->addSql('ALTER TABLE c_link CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_wiki')) {
            error_log('Perform the changes in the c_wiki table');
            $this->addSql('ALTER TABLE c_wiki CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_quiz_question')) {
            error_log('Perform the changes in the c_quiz_question table');
            $this->addSql('ALTER TABLE c_quiz_question CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_announcement')) {
            error_log('Perform the changes in the c_announcement table');
            $this->addSql('ALTER TABLE c_announcement CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_chat_conversation')) {
            error_log('Perform the changes in the c_chat_conversation table');
            $this->addSql('ALTER TABLE c_chat_conversation CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_course_description')) {
            error_log('Perform the changes in the c_course_description table');
            $this->addSql('ALTER TABLE c_course_description CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_group_category')) {
            error_log('Perform the changes in the c_group_category table');
            $this->addSql('ALTER TABLE c_group_category CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_student_publication_comment')) {
            error_log('Perform the changes in the c_student_publication_comment table');
            $this->addSql('ALTER TABLE c_student_publication_comment CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_quiz')) {
            error_log('Perform the changes in the c_quiz table');
            $this->addSql('ALTER TABLE c_quiz CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_forum_post')) {
            error_log('Perform the changes in the c_forum_post table');
            $this->addSql('ALTER TABLE c_forum_post CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_attendance')) {
            error_log('Perform the changes in the c_attendance table');
            $this->addSql('ALTER TABLE c_attendance CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_link_category')) {
            error_log('Perform the changes in the c_link_category table');
            $this->addSql('ALTER TABLE c_link_category CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_forum_category')) {
            error_log('Perform the changes in the c_forum_category table');
            $this->addSql('ALTER TABLE c_forum_category CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_lp')) {
            error_log('Perform the changes in the c_lp table');
            $this->addSql('ALTER TABLE c_lp CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_document')) {
            error_log('Perform the changes in the c_document table');
            $this->addSql('ALTER TABLE c_document CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_survey')) {
            error_log('Perform the changes in the c_survey table');
            $this->addSql('ALTER TABLE c_survey CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_calendar_event_attachment')) {
            error_log('Perform the changes in the c_calendar_event_attachment table');
            $this->addSql('ALTER TABLE c_calendar_event_attachment CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_announcement_attachment')) {
            error_log('Perform the changes in the c_announcement_attachment table');
            $this->addSql('ALTER TABLE c_announcement_attachment CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_tool_intro')) {
            error_log('Perform the changes in the c_tool_intro table');
            $this->addSql('ALTER TABLE c_tool_intro CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_forum_thread')) {
            error_log('Perform the changes in the c_forum_thread table');
            $this->addSql('ALTER TABLE c_forum_thread CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_group_info')) {
            error_log('Perform the changes in the c_group_info table');
            $this->addSql('ALTER TABLE c_group_info CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_notebook')) {
            error_log('Perform the changes in the c_notebook table');
            $this->addSql('ALTER TABLE c_notebook CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('c_exercise_category')) {
            error_log('Perform the changes in the c_exercise_category table');
            $this->addSql('ALTER TABLE c_exercise_category CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE resource_node_id resource_node_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('message')) {
            error_log('Perform the changes in the message table');
            $this->addSql('ALTER TABLE message CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE parent_id parent_id INT DEFAULT NULL;');
        }

        if ($schema->hasTable('message_rel_user')) {
            error_log('Perform the changes in the message_rel_user table');
            $this->addSql('ALTER TABLE message_rel_user CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE message_id message_id INT NOT NULL;');
        }

        if ($schema->hasTable('message_rel_user_rel_tags')) {
            error_log('Perform the changes in the message_rel_user_rel_tags table');
            $this->addSql('ALTER TABLE message_rel_user_rel_tags CHANGE message_rel_user_id message_rel_user_id INT NOT NULL, CHANGE message_tag_id message_tag_id INT NOT NULL;');
        }

        if ($schema->hasTable('message_tag')) {
            error_log('Perform the changes in the message_tag table');
            $this->addSql('ALTER TABLE message_tag CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        // Re-enable foreign key and unique checks to ensure database integrity
        $this->addSql('SET UNIQUE_CHECKS=1;');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1;');
    }
}
