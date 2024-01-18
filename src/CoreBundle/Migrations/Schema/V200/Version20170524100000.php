<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20170524100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace "name" with "title" fields in tables';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('block')) {
            $this->addSql(
                'ALTER TABLE block CHANGE name title varchar(255) COLLATE "utf8_unicode_ci" NULL'
            );
        }

        if ($schema->hasTable('branch_sync')) {
            $this->addSql(
                'ALTER TABLE branch_sync CHANGE branch_name title VARCHAR(250) NOT NULL'
            );
        }

        if ($schema->hasTable('c_attendance')) {
            $this->addSql(
                'ALTER TABLE c_attendance CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('c_blog')) {
            $this->addSql(
                'ALTER TABLE c_blog CHANGE blog_name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('c_dropbox_category')) {
            $this->addSql(
                'ALTER TABLE c_dropbox_category CHANGE cat_name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('c_exercise_category')) {
            $this->addSql(
                'ALTER TABLE c_exercise_category CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_forum_category')) {
            $this->addSql(
                'ALTER TABLE c_forum_category CHANGE cat_title title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_forum_forum')) {
            $this->addSql(
                'ALTER TABLE c_forum_forum CHANGE forum_title title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_forum_post')) {
            $this->addSql(
                'ALTER TABLE c_forum_post CHANGE post_title title VARCHAR(250) NOT NULL'
            );
        }

        if ($schema->hasTable('c_forum_thread')) {
            $this->addSql(
                'ALTER TABLE c_forum_thread CHANGE thread_title title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_glossary')) {
            $this->addSql(
                'ALTER TABLE c_glossary CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('c_group_info')) {
            $this->addSql(
                'ALTER TABLE c_group_info CHANGE name title VARCHAR(100) NOT NULL'
            );
        }

        if ($schema->hasTable('c_link_category')) {
            $this->addSql(
                'ALTER TABLE c_link_category CHANGE category_title title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_lp')) {
            $this->addSql(
                'ALTER TABLE c_lp CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_lp_category')) {
            $this->addSql(
                'ALTER TABLE c_lp_category CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('c_online_link')) {
            $this->addSql(
                'ALTER TABLE c_online_link CHANGE name title VARCHAR(50) NOT NULL'
            );
        }

        if ($schema->hasTable('c_quiz_question_option')) {
            $this->addSql(
                'ALTER TABLE c_quiz_question_option CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('c_survey_group')) {
            $this->addSql(
                'ALTER TABLE c_survey_group CHANGE name title VARCHAR(100) NOT NULL'
            );
        }

        if ($schema->hasTable('c_tool')) {
            $this->addSql(
                'ALTER TABLE c_tool CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('career')) {
            $this->addSql(
                'ALTER TABLE career CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('chat_video')) {
            $this->addSql(
                'DROP INDEX idx_chat_video_room_name ON chat_video'
            );
            $this->addSql(
                'ALTER TABLE chat_video CHANGE room_name title VARCHAR(255) NOT NULL'
            );
            $this->addSql(
                'CREATE INDEX idx_chat_video_title ON chat_video (title)'
            );
        }

        if ($schema->hasTable('class_item')) {
            $this->addSql(
                'ALTER TABLE class_item CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('course_category')) {
            $this->addSql(
                'ALTER TABLE course_category CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('course_module')) {
            $this->addSql(
                'ALTER TABLE course_module CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('course_type')) {
            $this->addSql(
                'ALTER TABLE course_type CHANGE name title VARCHAR(50) NOT NULL'
            );
        }

        if ($schema->hasTable('grade_model')) {
            $this->addSql(
                'ALTER TABLE grade_model CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('gradebook_linkeval_log')) {
            $this->addSql(
                'ALTER TABLE gradebook_linkeval_log CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('gradebook_category')) {
            $this->addSql(
                'ALTER TABLE gradebook_category CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('gradebook_evaluation')) {
            $this->addSql(
                'ALTER TABLE gradebook_evaluation CHANGE name title LONGTEXT NOT NULL'
            );
        }

        if ($schema->hasTable('mail_template')) {
            $this->addSql(
                'ALTER TABLE mail_template CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('usergroup')) {
            $this->addSql(
                'ALTER TABLE usergroup CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('sequence_row_entity')) {
            $this->addSql(
                'ALTER TABLE sequence_row_entity CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('ticket_project')) {
            $this->addSql(
                'ALTER TABLE ticket_project CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('skill')) {
            $this->addSql(
                'ALTER TABLE skill CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('sequence_variable')) {
            $this->addSql(
                'ALTER TABLE sequence_variable CHANGE name title VARCHAR(255) DEFAULT NULL'
            );
        }

        if ($schema->hasTable('specific_field')) {
            $this->addSql(
                'ALTER TABLE specific_field CHANGE name title VARCHAR(200) NOT NULL'
            );
        }

        if ($schema->hasTable('ticket_priority')) {
            $this->addSql(
                'ALTER TABLE ticket_priority CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('sequence_type_entity')) {
            $this->addSql(
                'ALTER TABLE sequence_type_entity CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('promotion')) {
            $this->addSql(
                'ALTER TABLE promotion CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('sequence')) {
            $this->addSql(
                'ALTER TABLE sequence CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('ticket_category')) {
            $this->addSql(
                'ALTER TABLE ticket_category CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('session')) {
            $this->addSql(
                'DROP INDEX name ON session'
            );
            $this->addSql(
                'ALTER TABLE session CHANGE name title VARCHAR(150) NOT NULL'
            );
            $this->addSql(
                'CREATE UNIQUE INDEX title ON session (title)'
            );
        }

        if ($schema->hasTable('skill_profile')) {
            $this->addSql(
                'ALTER TABLE skill_profile CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('ticket_status')) {
            $this->addSql(
                'ALTER TABLE ticket_status CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('session_category')) {
            $this->addSql(
                'ALTER TABLE session_category CHANGE name title VARCHAR(100) NOT NULL'
            );
        }

        if ($schema->hasTable('skill_level')) {
            $this->addSql(
                'ALTER TABLE skill_level CHANGE name title VARCHAR(255) NOT NULL'
            );
        }

        if ($schema->hasTable('skill_level_profile')) {
            $this->addSql(
                'ALTER TABLE skill_level_profile CHANGE name title VARCHAR(255) NOT NULL'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('skill_level_profile');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE skill_level_profile CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('skill_level');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE skill_level CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('session_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE session_category CHANGE title name VARCHAR(100) NOT NULL');
        }

        $table = $schema->getTable('ticket_status');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE ticket_status CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('skill_profile');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE skill_profile CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('session');
        if ($table->hasColumn('title')) {
            $this->addSql('DROP INDEX title ON session');
            $this->addSql('ALTER TABLE session CHANGE title name VARCHAR(150) NOT NULL');
            $this->addSql('CREATE UNIQUE INDEX name ON session (name)');
        }

        $table = $schema->getTable('ticket_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE ticket_category CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('sequence');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE sequence CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('promotion');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE promotion CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('mail_template');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE mail_template CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('sequence_type_entity');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE sequence_type_entity CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('ticket_priority');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE ticket_priority CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('specific_field');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE specific_field CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('sequence_variable');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE sequence_variable CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('skill');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE skill CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('ticket_project');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE ticket_project CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('sequence_row_entity');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE sequence_row_entity CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('usergroup');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE usergroup CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('gradebook_evaluation');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE gradebook_evaluation CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('gradebook_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE gradebook_category CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('gradebook_linkeval_log');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('grade_model');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE grade_model CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('course_type');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE course_type CHANGE title name VARCHAR(50) NOT NULL');
        }

        $table = $schema->getTable('course_module');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE course_module CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('course_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE course_category CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('class_item');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE class_item CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('chat_video');
        if ($table->hasColumn('title')) {
            $this->addSql(
                'DROP INDEX idx_chat_video_title ON chat_video'
            );
            $this->addSql(
                'ALTER TABLE chat_video CHANGE title room_name VARCHAR(255) NOT NULL'
            );
            $this->addSql(
                'CREATE INDEX idx_chat_video_room_name ON chat_video (room_name)'
            );
        }

        $table = $schema->getTable('career');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE career CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_tool');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_tool CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('c_survey_group');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_survey_group CHANGE title name VARCHAR(20) NOT NULL');
        }

        $table = $schema->getTable('c_quiz_question_option');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_quiz_question_option CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_online_link');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_online_link CHANGE title name VARCHAR(50) NOT NULL');
        }

        $table = $schema->getTable('c_lp_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_lp_category CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('c_lp');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_lp CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_link_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_link_category CHANGE title category_title VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_group_info');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_group_info CHANGE title name VARCHAR(100) NOT NULL');
        }

        $table = $schema->getTable('c_glossary');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_glossary CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('c_forum_thread');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_forum_thread CHANGE title thread_title VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_forum_post');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_forum_post CHANGE title post_title VARCHAR(250) NOT NULL');
        }

        $table = $schema->getTable('c_forum_forum');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_forum_forum CHANGE title forum_title VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_forum_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_forum_category CHANGE title cat_title VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_exercise_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_exercise_category CHANGE title name VARCHAR(255) NOT NULL');
        }

        $table = $schema->getTable('c_dropbox_category');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_dropbox_category CHANGE title cat_name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('c_blog');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_blog CHANGE title blog_name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('c_attendance');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE c_attendance CHANGE title name LONGTEXT NOT NULL');
        }

        $table = $schema->getTable('branch_sync');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE branch_sync CHANGE title branch_name VARCHAR(250) NOT NULL');
        }

        $table = $schema->getTable('block');
        if ($table->hasColumn('title')) {
            $this->addSql('ALTER TABLE block CHANGE title name varchar(255) COLLATE "utf8_unicode_ci" NULL');
        }
    }
}
