<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Types\Type;

/**
 * Class Version110
 *
 * Migrate file to updated to Chamilo 1.10
 *
 * @package Application\Migrations\Schema\V110
 */
class Version110 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        $this->addSql("ALTER TABLE session_rel_course ENGINE=InnoDB");
        $this->addSql("ALTER TABLE session_rel_course_rel_user ENGINE=InnoDB");
        $this->addSql("ALTER TABLE session_rel_user ENGINE=InnoDB");
        $this->addSql("UPDATE session SET session.id_coach = (SELECT u.user_id FROM admin a INNER JOIN user u ON (u.user_id = a.user_id AND u.active = 1) LIMIT 1) WHERE id_coach NOT IN (SELECT user_id FROM user)");
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        // Needed to update 0000-00-00 00:00:00 values
        $this->addSql('SET sql_mode = ""');
        // In case this one didn't work, also try this
        $this->addSql('SET SESSION sql_mode = ""');

        $connection = $this->connection;

        $this->addSql("CREATE TABLE IF NOT EXISTS course_field_options (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, field_id INT NOT NULL, option_value TEXT, option_display_text VARCHAR(64), option_order INT, tms DATETIME)");
        $this->addSql("CREATE TABLE IF NOT EXISTS session_field_options (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, field_id INT NOT NULL, option_value TEXT, option_display_text VARCHAR(64), option_order INT, tms DATETIME)");
        $this->addSql("CREATE TABLE IF NOT EXISTS hook_observer( id int UNSIGNED NOT NULL AUTO_INCREMENT, class_name varchar(255) UNIQUE, path varchar(255) NOT NULL, plugin_name varchar(255) NULL, PRIMARY KEY PK_hook_management_hook_observer(id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS hook_event( id int UNSIGNED NOT NULL AUTO_INCREMENT, class_name varchar(255) UNIQUE, description varchar(255), PRIMARY KEY PK_hook_management_hook_event(id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS hook_call( id int UNSIGNED NOT NULL AUTO_INCREMENT, hook_event_id int UNSIGNED NOT NULL, hook_observer_id int UNSIGNED NOT NULL, type tinyint NOT NULL, hook_order int UNSIGNED NOT NULL, enabled tinyint NOT NULL, PRIMARY KEY PK_hook_management_hook_call(id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_student_publication_rel_document (iid INT NOT NULL PRIMARY KEY, id INT NULL, work_id INT NOT NULL, document_id INT NOT NULL, c_id INT NOT NULL)");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_student_publication_rel_user (iid INT NOT NULL AUTO_INCREMENT PRIMARY KEY, id INT NULL, work_id INT NOT NULL, user_id INT NOT NULL, c_id INT NOT NULL)");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_student_publication_comment (iid INT NOT NULL PRIMARY KEY AUTO_INCREMENT, id INT NULL, work_id INT NOT NULL, c_id INT NOT NULL, comment text, file VARCHAR(255), user_id int NOT NULL, sent_at datetime NOT NULL)");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_attendance_calendar_rel_group (iid int NOT NULL auto_increment PRIMARY KEY, id INT, c_id INT NOT NULL, group_id INT NOT NULL, calendar_id INT NOT NULL)");

        $this->addSql("ALTER TABLE skill_rel_user MODIFY COLUMN acquired_skill_at datetime default NULL");
        $this->addSql("ALTER TABLE track_e_access MODIFY COLUMN access_date datetime DEFAULT NULL");
        $this->addSql("ALTER TABLE track_e_lastaccess MODIFY COLUMN access_date datetime DEFAULT NULL");

        $table = $schema->getTable('skill_rel_user');

        if (!$table->hasColumn('course_id')) {
            $this->addSql("ALTER TABLE skill_rel_user ADD COLUMN course_id INT NOT NULL DEFAULT 0 AFTER id");
        }

        if (!$table->hasColumn('session_id')) {
            $this->addSql("ALTER TABLE skill_rel_user ADD COLUMN session_id INT NOT NULL DEFAULT 0 AFTER course_id");
        }

        if (!$table->hasIndex('idx_select_cs')) {
            $this->addSql("ALTER TABLE skill_rel_user ADD INDEX idx_select_cs (course_id, session_id)");
        }

        // Delete info of session_rel_user if session does not exists;
        $this->addSql("DELETE FROM session_rel_user WHERE id_session NOT IN (SELECT id FROM session)");

        // Delete info of usergroup_rel_user if usergroup does not exists;
        $this->addSql("DELETE FROM usergroup_rel_user WHERE usergroup_id NOT IN (SELECT id FROM usergroup)");

        $session = $schema->getTable('session');
        $session->getColumn('id')->setType(Type::getType(Type::INTEGER))->setUnsigned(false);
        if (!$session->hasColumn('description')) {
            $session->addColumn(
                'description',
                'text'
            );
        }

        if (!$session->hasColumn('show_description')) {
            $session->addColumn(
                'show_description',
                'smallint',
                array('default' => 0, 'unsigned' => true)
            );
        }
        $sessionTable = $schema->getTable('session');
        if (!$sessionTable->hasColumn('duration')) {
            $this->addSql("ALTER TABLE session ADD COLUMN duration int");
        }

        $sessionRelUser = $schema->getTable('session_rel_user');
        if (!$sessionRelUser->hasColumn('duration')) {
            $this->addSql("ALTER TABLE session_rel_user ADD COLUMN duration int");
        }

        $table = $schema->getTable('skill');
        if (!$table->hasColumn('criteria')) {
            $this->addSql("ALTER TABLE skill ADD COLUMN criteria text");
        }
        $table = $schema->getTable('gradebook_category');
        if (!$table->hasColumn('generate_certificates')) {
            $this->addSql("ALTER TABLE gradebook_category ADD COLUMN generate_certificates TINYINT NOT NULL DEFAULT 0");
        }
        $this->addSql("ALTER TABLE track_e_access ADD COLUMN c_id int NOT NULL");

        $this->addSql("ALTER TABLE track_e_lastaccess ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE track_e_exercices ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE track_e_downloads ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE track_e_hotpotatoes ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE track_e_links ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE track_e_course_access ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE track_e_online ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE track_e_attempt ADD COLUMN c_id int NOT NULL");
        $table = $schema->getTable('track_e_default');
        if (!$table->hasColumn('session_id')) {
            $this->addSql("ALTER TABLE track_e_default ADD COLUMN session_id int NOT NULL");
        }

        if (!$table->hasColumn('c_id')) {
            $this->addSql("ALTER TABLE track_e_default ADD COLUMN c_id int NOT NULL");
        }

        $this->addSql("ALTER TABLE track_e_access ADD COLUMN user_ip varchar(39) NOT NULL default ''");
        $this->addSql("ALTER TABLE track_e_exercices ADD COLUMN user_ip varchar(39) NOT NULL default ''");
        $this->addSql("ALTER TABLE track_e_course_access ADD COLUMN user_ip varchar(39) NOT NULL default ''");
        $this->addSql("ALTER TABLE track_e_online CHANGE COLUMN login_ip user_ip varchar(39) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE track_e_login CHANGE COLUMN login_ip user_ip varchar(39) NOT NULL DEFAULT ''");

        $this->addSql('LOCK TABLE user WRITE');
        $this->addSql("ALTER TABLE user MODIFY COLUMN user_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE user MODIFY COLUMN user_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE user ADD COLUMN id INT DEFAULT NULL");
        $this->addSql("UPDATE user SET id = user_id");
        $this->addSql("ALTER TABLE user MODIFY COLUMN id INT NOT NULL PRIMARY KEY AUTO_INCREMENT AFTER user_id");

        $this->addSql("ALTER TABLE user MODIFY COLUMN chatcall_date datetime default NULL");
        $this->addSql("ALTER TABLE user MODIFY COLUMN chatcall_text varchar(50) default NULL");
        $this->addSql("ALTER TABLE user MODIFY COLUMN chatcall_user_id int unsigned default 0");
        $this->addSql("ALTER TABLE user MODIFY COLUMN expiration_date datetime default NULL");
        $this->addSql("ALTER TABLE user MODIFY COLUMN registration_date datetime NOT NULL");
        $this->addSql('UNLOCK TABLES');

        $table = $schema->getTable('course');
        if (!$table->hasColumn('add_teachers_to_sessions_courses')) {
            $this->addSql("ALTER TABLE course ADD COLUMN add_teachers_to_sessions_courses tinyint NOT NULL default 0");
        }
        $this->addSql("ALTER TABLE course DROP COLUMN target_course_code");
        $this->addSql("ALTER TABLE session MODIFY COLUMN name char(100) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE course_rel_user ADD COLUMN c_id int default NULL");
        $this->addSql("ALTER TABLE course_field_values ADD COLUMN c_id int default NULL");

        $this->addSql('LOCK TABLE session_rel_course_rel_user WRITE');
        $this->addSql("ALTER TABLE session_rel_course_rel_user ADD COLUMN c_id int NOT NULL");
        $this->addSql("ALTER TABLE session_rel_course_rel_user CHANGE id_session session_id int");
        $this->addSql("ALTER TABLE session_rel_course_rel_user CHANGE id_user user_id int");
        $this->addSql('UNLOCK TABLES');
        $this->addSql("ALTER TABLE access_url_rel_course ADD COLUMN c_id int");

        $table = $schema->getTable('session_rel_course');
        if (!$table->hasColumn('position')) {
            $this->addSql("ALTER TABLE session_rel_course ADD COLUMN position int NOT NULL default 0");
        }
        $this->addSql("ALTER TABLE session_rel_course ADD COLUMN category varchar(255) default ''");
        $this->addSql("ALTER TABLE session_rel_course ADD COLUMN c_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE session_rel_course CHANGE id_session session_id int");
        $this->addSql('DELETE FROM session_rel_course WHERE session_id NOT IN (SELECT id FROM session)');

        $this->addSql("DELETE FROM course_rel_user WHERE course_code NOT IN (SELECT code FROM course)");
        $this->addSql("UPDATE course_rel_user SET c_id = (SELECT id FROM course WHERE code = course_code)");

        // Add iid
        $tables = [
            'c_announcement',
            'c_announcement_attachment',
            'c_attendance',
            'c_attendance_calendar',
            //'c_attendance_calendar_rel_group',
            'c_attendance_result',
            //'c_attendance_sheet',
            'c_attendance_sheet_log',
            //'c_blog',
            'c_blog_attachment',
            //'c_blog_comment',
            //'c_blog_post',
            //'c_blog_rating',
            //'c_blog_rel_user',
            //'c_blog_task',
            //'c_blog_task_rel_user',
            'c_calendar_event',
            'c_calendar_event_attachment',
            //'c_calendar_event_repeat',
            //'c_calendar_event_repeat_not',
            'c_chat_connected',
            'c_course_description',
            'c_course_setting',
            'c_document',
            //'c_dropbox_category',
            //'c_dropbox_feedback',
            'c_dropbox_file',
            //'c_dropbox_person',
            //'c_dropbox_post',
            'c_forum_attachment',
            //'c_forum_category',
            //'c_forum_forum',
            'c_forum_mailcue',
            'c_forum_notification',
            //'c_forum_post',
            //'c_forum_thread',
            'c_forum_thread_qualify',
            'c_forum_thread_qualify_log',
            //'c_glossary',
            'c_group_category',
            'c_group_info',
            'c_group_rel_tutor',
            'c_group_rel_user',
            'c_item_property',
            'c_link',
            'c_link_category',
            'c_lp',
            'c_lp_item',
            'c_lp_item_view',
            'c_lp_iv_interaction',
            'c_lp_iv_objective',
            'c_lp_view',
            //'c_notebook',
            //'c_online_connected',
            'c_online_link',
            'c_permission_group',
            'c_permission_task',
            'c_permission_user',
            'c_quiz',
            //'c_quiz_answer',
            'c_quiz_question',
            'c_quiz_question_category',
            'c_quiz_question_option',
            //'c_quiz_question_rel_category',
            //'c_quiz_rel_question',
            'c_resource',
            //'c_role',
            'c_role_group',
            'c_role_permissions',
            //'c_role_user',
            'c_student_publication',
            'c_student_publication_assignment',
            //'c_student_publication_comment',
            //'c_student_publication_rel_document',
            //'c_student_publication_rel_user',
            //'c_survey',
            //'c_survey_answer',
            'c_survey_group',
            //'c_survey_invitation',
            //'c_survey_question',
            //'c_survey_question_option',
            'c_thematic',
            'c_thematic_advance',
            'c_thematic_plan',
            'c_tool',
            //'c_tool_intro',
            'c_userinfo_content',
            'c_userinfo_def',
            'c_wiki',
            //'c_wiki_conf',
            'c_wiki_discuss',
            'c_wiki_mailcue'
        ];

        foreach ($tables as $table) {
            if ($schema->hasTable($table)) {
                $this->addSql("ALTER TABLE $table MODIFY COLUMN id INT NOT NULL");
                $this->addSql("ALTER TABLE $table MODIFY COLUMN c_id INT NOT NULL");
                $this->addSql("ALTER TABLE $table DROP PRIMARY KEY");
                $this->addSql("ALTER TABLE $table MODIFY COLUMN id INT NULL");
                $this->addSql("ALTER TABLE $table ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");
            }
        }

        if ($schema->hasTable('c_attendance_calendar_rel_group')) {
            $table = $schema->getTable('c_attendance_calendar_rel_group');
            if ($table->hasColumn('iid') === false) {
                $this->addSql("ALTER TABLE c_attendance_calendar_rel_group MODIFY COLUMN id INT NOT NULL");
                $this->addSql("ALTER TABLE c_attendance_calendar_rel_group DROP PRIMARY KEY");
                $this->addSql("ALTER TABLE c_attendance_calendar_rel_group MODIFY COLUMN id INT NULL DEFAULT NULL");
                $this->addSql("ALTER TABLE c_attendance_calendar_rel_group ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");
            }
        }

        $this->addSql("ALTER TABLE c_attendance_sheet MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_attendance_sheet DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_attendance_sheet ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_blog MODIFY COLUMN blog_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_blog MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_blog DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_blog MODIFY COLUMN blog_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog ADD COLUMN iid int  NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_blog_comment MODIFY COLUMN comment_id int NOT NULL");
        $this->addSql("ALTER TABLE c_blog_comment MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_blog_comment DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_blog_comment MODIFY COLUMN comment_id int DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_comment ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_blog_post MODIFY COLUMN post_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_blog_post MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_blog_post DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_blog_post MODIFY COLUMN post_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_post ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_blog_rating MODIFY COLUMN rating_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_blog_rating MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_blog_rating DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_blog_rating MODIFY COLUMN rating_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_rating ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_blog_rel_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_blog_rel_user MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_blog_rel_user MODIFY COLUMN blog_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_rel_user MODIFY COLUMN user_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_rel_user ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_blog_task MODIFY COLUMN task_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_blog_task MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_blog_task DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_blog_task MODIFY COLUMN task_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_task ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_blog_task_rel_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_blog_task_rel_user MODIFY COLUMN blog_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_task_rel_user MODIFY COLUMN user_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_task_rel_user MODIFY COLUMN task_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_blog_task_rel_user ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_calendar_event_repeat DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_calendar_event_repeat MODIFY COLUMN cal_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_calendar_event_repeat MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_calendar_event_repeat ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_calendar_event_repeat_not DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_calendar_event_repeat_not MODIFY COLUMN cal_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_calendar_event_repeat_not MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_calendar_event_repeat_not ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_dropbox_category MODIFY COLUMN cat_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_dropbox_category DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_dropbox_category MODIFY COLUMN cat_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_dropbox_category MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_dropbox_category ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_dropbox_feedback MODIFY COLUMN feedback_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_dropbox_feedback DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_dropbox_feedback MODIFY COLUMN feedback_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_dropbox_feedback MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_dropbox_feedback ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_dropbox_person DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_dropbox_person MODIFY COLUMN file_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_dropbox_person MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_dropbox_person ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_dropbox_post DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_dropbox_post MODIFY COLUMN file_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_dropbox_post MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_dropbox_post ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_forum_category MODIFY COLUMN cat_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_forum_category DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_forum_category MODIFY COLUMN cat_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_forum_category MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_forum_category ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_forum_forum MODIFY COLUMN forum_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_forum_forum DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_forum_forum MODIFY COLUMN forum_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_forum_forum MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_forum_forum ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_forum_post MODIFY COLUMN post_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_forum_post DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_forum_post MODIFY COLUMN post_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_forum_post MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_forum_post ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_forum_thread MODIFY COLUMN thread_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_forum_thread DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_forum_thread MODIFY COLUMN forum_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_forum_thread MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_forum_thread MODIFY COLUMN thread_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_forum_thread ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_forum_thread ADD COLUMN thread_peer_qualify tinyint default 0");

        $this->addSql("ALTER TABLE c_glossary MODIFY COLUMN glossary_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_glossary MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_glossary DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_glossary MODIFY COLUMN glossary_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_glossary ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_notebook MODIFY COLUMN notebook_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_notebook MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_notebook DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_notebook MODIFY COLUMN notebook_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_notebook ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_online_connected MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_online_connected DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_online_connected ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        // For some reason c_tool_intro.id is a varchar in 1.9.x
        $this->addSql("ALTER TABLE c_tool_intro MODIFY COLUMN id VARCHAR(50) NOT NULL");
        $this->addSql("ALTER TABLE c_tool_intro MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_tool_intro MODIFY COLUMN session_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_tool_intro DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_tool_intro MODIFY COLUMN session_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_tool_intro ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_quiz_answer MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_quiz_answer MODIFY COLUMN id INT DEFAULT NULL");
        $this->addSql("ALTER TABLE c_quiz_answer MODIFY COLUMN id_auto int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_quiz_answer DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_quiz_answer MODIFY COLUMN id_auto int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_quiz_answer ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_quiz_question_rel_category MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_quiz_question_rel_category MODIFY COLUMN question_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_quiz_question_rel_category DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_quiz_question_rel_category MODIFY COLUMN question_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_quiz_question_rel_category ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE session_rel_user MODIFY COLUMN id_session int");
        $this->addSql("ALTER TABLE session_rel_user MODIFY COLUMN id_user int");
        $this->addSql("ALTER TABLE session_rel_user MODIFY COLUMN relation_type int unsigned DEFAULT 0");
        $this->addSql("ALTER TABLE session_rel_user DROP PRIMARY KEY");

        $this->addSql("ALTER TABLE session_rel_user CHANGE id_session session_id int");
        $this->addSql("ALTER TABLE session_rel_user CHANGE id_user user_id int");
        $this->addSql("DELETE FROM session_rel_user WHERE user_id NOT IN (SELECT id FROM user)");
        $this->addSql("ALTER TABLE session_rel_user ADD COLUMN id int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_item_property CHANGE id_session session_id int");
        $this->addSql("ALTER TABLE course_rel_user CHANGE tutor_id is_tutor int");

        $this->addSql("ALTER TABLE c_quiz_rel_question MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_quiz_rel_question DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_quiz_rel_question MODIFY COLUMN question_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_quiz_rel_question MODIFY COLUMN exercice_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_quiz_rel_question ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_role MODIFY COLUMN role_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_role MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_role DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_role MODIFY COLUMN role_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_role ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_role_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_role_user MODIFY COLUMN role_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_role_user MODIFY COLUMN user_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_role_user MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_role_user ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_survey MODIFY COLUMN survey_id int NOT NULL");
        $this->addSql("ALTER TABLE c_survey MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_survey DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_survey MODIFY COLUMN survey_id int NULL");
        $this->addSql("ALTER TABLE c_survey ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_survey_answer MODIFY COLUMN answer_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_survey_answer MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_survey_answer DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_survey_answer MODIFY COLUMN answer_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_survey_answer ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_survey_invitation MODIFY COLUMN survey_invitation_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_survey_invitation MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_survey_invitation DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_survey_invitation MODIFY COLUMN survey_invitation_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_survey_invitation ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_survey_question MODIFY COLUMN question_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_survey_question MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_survey_question DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_survey_question MODIFY COLUMN question_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_survey_question ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_survey_question_option MODIFY COLUMN question_option_id int unsigned NOT NULL");
        $this->addSql("ALTER TABLE c_survey_question_option MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_survey_question_option DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_survey_question_option MODIFY COLUMN question_option_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_survey_question_option ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        $this->addSql("ALTER TABLE c_wiki_conf DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE c_wiki_conf MODIFY COLUMN page_id int unsigned DEFAULT NULL");
        $this->addSql("ALTER TABLE c_wiki_conf MODIFY COLUMN c_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_wiki_conf ADD COLUMN iid int NOT NULL PRIMARY KEY AUTO_INCREMENT");

        // Course
        $this->addSql("ALTER TABLE c_survey ADD COLUMN visible_results INT UNSIGNED DEFAULT 0");
        $this->addSql("ALTER TABLE c_survey_invitation ADD COLUMN group_id INT NOT NULL");
        $this->addSql("ALTER TABLE c_lp_item ADD COLUMN prerequisite_min_score float");
        $this->addSql("ALTER TABLE c_lp_item ADD COLUMN prerequisite_max_score float");
        $table = $schema->getTable('c_group_info');
        if (!$table->hasColumn('status')) {
            $this->addSql("ALTER TABLE c_group_info ADD COLUMN status tinyint DEFAULT 1");
        }

        $table = $schema->getTable('c_student_publication');
        if (!$table->hasColumn('document_id')) {
            $this->addSql("ALTER TABLE c_student_publication ADD COLUMN document_id int DEFAULT 0");
        }

        $this->addSql("ALTER TABLE c_lp_item MODIFY COLUMN description VARCHAR(511) DEFAULT ''");
        $this->addSql("ALTER TABLE course_category MODIFY COLUMN auth_course_child VARCHAR(40) DEFAULT 'TRUE' ");
        $this->addSql("ALTER TABLE course_category MODIFY COLUMN auth_cat_child VARCHAR(40) DEFAULT 'TRUE'");
        $this->addSql("ALTER TABLE c_quiz_answer MODIFY COLUMN hotspot_type varchar(40) default NULL");
        $this->addSql("ALTER TABLE c_tool MODIFY COLUMN target varchar(20) NOT NULL default '_self' ");
        $this->addSql("ALTER TABLE c_link MODIFY COLUMN on_homepage char(10) NOT NULL default '0' ");
        $this->addSql("ALTER TABLE c_blog_rating MODIFY COLUMN rating_type char(40) NOT NULL default 'post' ");
        $this->addSql("ALTER TABLE c_survey MODIFY COLUMN anonymous char(10) NOT NULL default '0'");
        $this->addSql("ALTER TABLE c_course_setting MODIFY COLUMN value varchar(255) default ''");

        $this->addSql("UPDATE course_field SET field_type = 13 WHERE field_variable = 'special_course'");
        $this->addSql("UPDATE user SET registration_date = NULL WHERE registration_date = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE user SET expiration_date = NULL WHERE expiration_date = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE track_e_default SET default_date = NULL WHERE default_date = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE track_e_lastaccess SET access_date = NULL WHERE access_date = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE track_e_downloads SET down_date = NULL WHERE down_date = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE track_e_access SET access_date = NULL WHERE access_date = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE track_e_access SET c_id = (SELECT id FROM course WHERE code = access_cours_code)");
        $this->addSql("UPDATE track_e_default SET c_id = (SELECT id FROM course WHERE code = default_cours_code)");
        $this->addSql("UPDATE track_e_lastaccess SET c_id = (SELECT id FROM course WHERE code = access_cours_code)");
        $this->addSql("UPDATE track_e_exercices SET c_id = (SELECT id FROM course WHERE code = exe_cours_id)");
        $this->addSql("UPDATE track_e_downloads SET c_id = (SELECT id FROM course WHERE code = down_cours_id)");
        $this->addSql("UPDATE track_e_hotpotatoes SET c_id = (SELECT id FROM course WHERE code = exe_cours_id)");
        $this->addSql("UPDATE track_e_links SET c_id = (SELECT id FROM course WHERE code = links_cours_id)");
        $this->addSql("UPDATE track_e_course_access SET c_id = (SELECT id FROM course WHERE code = course_code)");
        $this->addSql("UPDATE track_e_online SET c_id = (SELECT id FROM course WHERE code = course)");
        $this->addSql("UPDATE track_e_attempt SET c_id = (SELECT id FROM course WHERE code = course_code)");
        $this->addSql("UPDATE course_field_values SET c_id = (SELECT id FROM course WHERE code = course_code)");
        $this->addSql("UPDATE session_rel_course_rel_user SET c_id = (SELECT id FROM course WHERE code = course_code)");
        $this->addSql('DELETE FROM session_rel_course WHERE course_code NOT IN (SELECT code FROM course)');
        $this->addSql("UPDATE session_rel_course SET c_id = (SELECT id FROM course WHERE code = course_code)");

        $this->addSql("DELETE FROM access_url_rel_course WHERE course_code NOT IN (SELECT code FROM course)");
        $this->addSql("UPDATE access_url_rel_course SET c_id = (SELECT id FROM course WHERE code = course_code)");

        $this->addSql("ALTER TABLE settings_current DROP INDEX unique_setting");
        $this->addSql("ALTER TABLE settings_options DROP INDEX unique_setting_option");

        $this->addSql("DELETE FROM settings_current WHERE variable = 'wcag_anysurfer_public_pages'");

        $this->addSql("DELETE FROM settings_current WHERE variable = 'wcag_anysurfer_public_pages'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'wcag_anysurfer_public_pages'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'advanced_filemanager'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'advanced_filemanager'");

        // Fixes missing options show_glossary_in_extra_tools
        $sql = "SELECT * FROM settings_current WHERE variable = 'institution_address'";
        $result = $connection->executeQuery($sql);
        $count = $result->rowCount();
        if (empty($count)) {
            $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('institution_address', NULL, 'textfield', 'Platform', '', 'InstitutionAddressTitle', 'InstitutionAddressComment', NULL, NULL, 1)");
        }
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('prevent_session_admins_to_manage_all_users', NULL, 'radio', 'Session', 'false', 'PreventSessionAdminsToManageAllUsersTitle', 'PreventSessionAdminsToManageAllUsersComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('prevent_session_admins_to_manage_all_users', 'true', 'Yes'), ('prevent_session_admins_to_manage_all_users', 'false', 'No')");

        // Fixes missing options show_glossary_in_extra_tools
        $sql = "SELECT * FROM settings_options WHERE variable = 'show_glossary_in_extra_tools'";
        $result = $connection->executeQuery($sql);
        $count = $result->rowCount();
        if (empty($count)) {
            $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'none', 'None')");
            $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise', 'Exercise')");
            $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'lp', 'Learning path')");
            $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise_and_lp', 'ExerciseAndLearningPath')");
        }

        $sql = "SELECT * FROM settings_current WHERE variable = 'documents_default_visibility_defined_in_course'";
        $result = $connection->executeQuery($sql);
        $count = $result->rowCount();
        if (empty($count)) {
            $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('documents_default_visibility_defined_in_course', NULL,'radio','Tools','false','DocumentsDefaultVisibilityDefinedInCourseTitle','DocumentsDefaultVisibilityDefinedInCourseComment',NULL, NULL, 1)");
            $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'true', 'Yes')");
            $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'false', 'No')");
        }

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_mathjax', NULL, 'radio', 'Editor', 'false', 'EnableMathJaxTitle', 'EnableMathJaxComment', NULL, NULL, 0)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_mathjax', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_mathjax', 'false', 'No')");

        $this->addSql("INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available) VALUES ('Føroyskt', 'faroese', 'fo', 'faroese', 0), ('Tagalog', 'tagalog', 'tl', 'tagalog',1), ('Tibetan', 'tibetan', 'bo', 'tibetan', 0), ('isiXhosa', 'xhosa', 'xh', 'xhosa', 0)");

        $this->addSql("ALTER TABLE c_student_publication MODIFY COLUMN date_of_qualification DATETIME NULL DEFAULT NULL");
        $this->addSql("ALTER TABLE c_student_publication MODIFY COLUMN sent_date DATETIME NULL DEFAULT NULL");
        $this->addSql("UPDATE c_student_publication SET date_of_qualification = NULL WHERE date_of_qualification = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE c_student_publication SET sent_date = NULL WHERE sent_date = '0000-00-00 00:00:00'");

        $this->addSql("ALTER TABLE c_student_publication_assignment MODIFY COLUMN expires_on DATETIME NULL DEFAULT NULL");
        $this->addSql("ALTER TABLE c_student_publication_assignment MODIFY COLUMN ends_on DATETIME NULL DEFAULT NULL");
        $this->addSql("UPDATE c_student_publication_assignment SET expires_on = NULL WHERE expires_on = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE c_student_publication_assignment SET ends_on = NULL WHERE ends_on = '0000-00-00 00:00:00'");

        $this->addSql("UPDATE settings_current SET type = 'checkbox' WHERE variable = 'registration' AND category = 'User'");
        $this->addSql("UPDATE settings_current SET selected_value = 'UTF-8' WHERE variable = 'platform_charset'");

        $this->addSql("ALTER TABLE course_rel_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE course_rel_user ADD COLUMN id INT NOT NULL PRIMARY KEY AUTO_INCREMENT");
        $this->addSql("ALTER TABLE course_rel_user MODIFY COLUMN user_id INT NULL");

        $this->addSql("ALTER TABLE user MODIFY COLUMN user_id INT NULL");

        $this->addSql("ALTER TABLE access_url_rel_course DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE access_url_rel_course ADD COLUMN id INT NOT NULL PRIMARY KEY AUTO_INCREMENT");
        $this->addSql("ALTER TABLE access_url_rel_course DROP COLUMN course_code");
        $this->addSql("ALTER TABLE access_url_rel_course ADD INDEX idx_select_c (c_id)");
        $this->addSql("ALTER TABLE access_url_rel_course ADD INDEX idx_select_u (access_url_id)");

        $this->addSql("ALTER TABLE access_url ADD COLUMN url_type TINYINT(1) NULL");

        $this->addSql("ALTER TABLE course_rel_user ADD INDEX idx_select_c (c_id)");

        $this->addSql("ALTER TABLE track_e_uploads ADD COLUMN c_id INT NOT NULL");
        $this->addSql("UPDATE track_e_uploads SET c_id = (SELECT id FROM course WHERE code = upload_cours_id)");

        //postUp
        $this->addSql("ALTER TABLE track_e_access DROP COLUMN access_cours_code");
        $this->addSql("ALTER TABLE track_e_default DROP COLUMN default_cours_code");
        $this->addSql("ALTER TABLE track_e_lastaccess DROP COLUMN access_cours_code");
        $this->addSql("ALTER TABLE track_e_exercices DROP COLUMN exe_cours_id");
        $this->addSql("ALTER TABLE track_e_downloads DROP COLUMN down_cours_id");
        $this->addSql("ALTER TABLE track_e_hotpotatoes DROP COLUMN exe_cours_id");
        $this->addSql("ALTER TABLE track_e_links DROP COLUMN links_cours_id");
        $this->addSql("ALTER TABLE track_e_course_access DROP COLUMN course_code");
        $this->addSql("ALTER TABLE track_e_online DROP COLUMN course");
        $this->addSql("ALTER TABLE track_e_attempt DROP COLUMN course_code");

        $this->addSql("ALTER TABLE course_rel_user DROP COLUMN group_id");
        $this->addSql("ALTER TABLE course_rel_user DROP COLUMN role");

        $this->addSql("DROP TABLE track_c_countries");
        $this->addSql("DROP TABLE track_c_browsers");
        $this->addSql("DROP TABLE track_c_os");
        $this->addSql("DROP TABLE track_c_providers");
        $this->addSql("DROP TABLE track_c_referers");
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
