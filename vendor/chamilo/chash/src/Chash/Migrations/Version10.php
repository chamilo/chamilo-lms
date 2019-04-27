<?php

namespace Chash\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Manages the migration to Chamilo 10
 * @package ChamiloLMS\Controller\Migrations
 */
class Version10 extends AbstractMigration
{
    //implements ContainerAwareInterface
    /*private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }*/

    /**
     * Chamilo upgrade
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $trackDefaultTable = $schema->getTable('track_e_default');
        $trackDefaultTable->addColumn('session_id', 'integer', array('default' => 0, 'Notnull' => true));
        $schema->dropTable('php_session');

        /*-- ALTER TABLE c_lp_item ADD INDEX idx_c_lp_item_cid_lp_id (c_id, lp_id);
        -- ALTER TABLE c_lp_item_view ADD INDEX idx_c_lp_item_view_cid_lp_view_id_lp_item_id(c_id, lp_view_id, lp_item_id);*/

        $this->addSql("CREATE TABLE IF NOT EXISTS php_session(session_id varchar(255) NOT NULL, session_value LONGTEXT NOT NULL, session_time int NOT NULL, PRIMARY KEY (session_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->addSql("CREATE TABLE IF NOT EXISTS session_field_options (id int unsigned NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS course_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_quiz_order( iid bigint unsigned NOT NULL auto_increment, c_id int unsigned NOT NULL, session_id int unsigned NOT NULL, exercise_id int NOT NULL, exercise_order INT NOT NULL, PRIMARY KEY (iid))");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_student_publication_rel_document (id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,    work_id INT NOT NULL,    document_id INT NOT NULL,    c_id INT NOT NULL)");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_student_publication_rel_user (id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,    work_id INT NOT NULL,    user_id INT NOT NULL,    c_id INT NOT NULL)");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_student_publication_comment (id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,  work_id INT NOT NULL,  c_id INT NOT NULL,  comment text,  user_id int NOT NULL,  sent_at datetime NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->addSql("CREATE TABLE IF NOT EXISTS gradebook_evaluation_type(id INT unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT, name varchar(255), external_id INT unsigned NOT NULL DEFAULT 0)");
        $this->addSql("CREATE TABLE IF NOT EXISTS question_field (id  int NOT NULL auto_increment, field_type int NOT NULL default 1, field_variable varchar(64) NOT NULL, field_display_text  varchar(64), field_default_value text, field_order int, field_visible tinyint default 0, field_changeable tinyint default 0, field_filter tinyint default 0, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS question_field_options(id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS question_field_values( id  int NOT NULL auto_increment, question_id int NOT NULL, field_id int NOT NULL, field_value text, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS question_score_name (id int NOT NULL AUTO_INCREMENT,  score varchar(255) DEFAULT NULL,  name varchar(255) DEFAULT NULL,  description TEXT DEFAULT NULL,  question_score_id INT NOT NULL,  PRIMARY KEY (id)) DEFAULT CHARSET=utf8");
        $this->addSql("CREATE TABLE IF NOT EXISTS question_score (  id int NOT NULL AUTO_INCREMENT,  name varchar(255) DEFAULT NULL,  PRIMARY KEY (id)) DEFAULT CHARSET=utf8");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_lp_category (id int unsigned NOT NULL auto_increment, c_id INT unsigned NOT NULL, name VARCHAR(255), position INT, PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS c_quiz_rel_category (iid bigint unsigned NOT NULL auto_increment, c_id INT unsigned default 0, category_id int unsigned NOT NULL, exercise_id int unsigned NOT NULL, count_questions int NOT NULL default 0, PRIMARY KEY(iid))");
        $this->addSql("CREATE TABLE IF NOT EXISTS extra_field_option_rel_field_option(id INT auto_increment, role_id INT, field_id INT, field_option_id INT, related_field_option_id INT, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS ext_log_entries (id int(11) NOT NULL AUTO_INCREMENT, action varchar(255) DEFAULT NULL, logged_at datetime DEFAULT NULL, object_id varchar(64) DEFAULT NULL, object_class varchar(255) DEFAULT NULL, version int(11) DEFAULT NULL, data varchar(255) DEFAULT NULL, username varchar(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARSET=utf8");
        $this->addSql("CREATE TABLE IF NOT EXISTS roles (id INT auto_increment, name VARCHAR(255), role VARCHAR(255) unique, PRIMARY KEY(id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS users_roles (user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(user_id, role_id));");
        $this->addSql("CREATE TABLE IF NOT EXISTS usergroup_rel_tag( id int NOT NULL AUTO_INCREMENT, tag_id int NOT NULL, usergroup_id int NOT NULL, PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS usergroup_rel_usergroup (id int NOT NULL AUTO_INCREMENT, group_id int NOT NULL, subgroup_id int NOT NULL, relation_type int NOT NULL, PRIMARY KEY (id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS branch_sync( id int unsigned not null AUTO_INCREMENT PRIMARY KEY, access_url_id int unsigned not null, branch_name varchar(250) default '', branch_ip varchar(40) default '', latitude decimal(15,7), longitude decimal(15,7), dwn_speed int unsigned default null, up_speed int unsigned default null, delay int unsigned default null, admin_mail varchar(250) default '', admin_name varchar(250) default '', admin_phone varchar(250) default '', last_sync_trans_id bigint unsigned default 0, last_sync_trans_date datetime, last_sync_type char(20) default 'full')");
        $this->addSql("CREATE TABLE IF NOT EXISTS branch_sync_log( id bigint unsigned not null AUTO_INCREMENT PRIMARY KEY, branch_sync_id int unsigned not null, sync_trans_id bigint unsigned default 0, sync_trans_date datetime, sync_type char(20))");
        $this->addSql("CREATE TABLE IF NOT EXISTS branch_transaction_status (id tinyint not null PRIMARY KEY AUTO_INCREMENT,  title char(20))");
        $this->addSql("CREATE TABLE IF NOT EXISTS branch_transaction (id bigint unsigned not null AUTO_INCREMENT, transaction_id bigint unsigned, branch_id int unsigned not null default 0,  action char(20),  item_id char(36),  orig_id char(36),  dest_id char(36),  info char(20), status_id tinyint not null default 0,  time_insert datetime NOT NULL DEFAULT '0000-00-00 00:00:00',  time_update datetime NOT NULL DEFAULT '0000-00-00 00:00:00', message VARCHAR(255) default '' , PRIMARY KEY (id, transaction_id, branch_id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS access_url_rel_usergroup (access_url_id int unsigned NOT NULL, usergroup_id int unsigned NOT NULL, PRIMARY KEY (access_url_id, usergroup_id))");
        $this->addSql("CREATE TABLE IF NOT EXISTS access_url_rel_course_category (access_url_id int unsigned NOT NULL, course_category_id int unsigned NOT NULL, PRIMARY KEY (access_url_id, course_category_id))");


        $this->addSql("ALTER TABLE c_student_publication ADD COLUMN filename varchar(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE course ADD COLUMN add_teachers_to_sessions_courses tinyint NOT NULL default 0");
        $this->addSql("ALTER TABLE c_quiz ADD COLUMN on_success_message longtext default ''");
        $this->addSql("ALTER TABLE c_quiz ADD COLUMN on_failed_message longtext default ''");
        $this->addSql("ALTER TABLE c_quiz ADD COLUMN email_notification_template_to_user longtext default ''");
        $this->addSql("ALTER TABLE c_quiz ADD COLUMN notify_user_by_email int default 0");
        $this->addSql("ALTER TABLE c_tool_intro MODIFY COLUMN intro_text MEDIUMTEXT NOT NULL");
        $this->addSql("ALTER TABLE user MODIFY COLUMN hr_dept_id int unsigned default 0");
        $this->addSql("ALTER TABLE session MODIFY COLUMN nbr_courses int unsigned NOT NULL default 0");
        $this->addSql("ALTER TABLE session MODIFY COLUMN nbr_users int unsigned NOT NULL default 0");
        $this->addSql("ALTER TABLE session MODIFY COLUMN nbr_classes int unsigned NOT NULL default 0");
        $this->addSql("ALTER TABLE session_rel_course MODIFY COLUMN nbr_users int unsigned NOT NULL default 0");
        $this->addSql("ALTER TABLE track_e_exercices MODIFY COLUMN session_id int unsigned NOT NULL default 0");
        $this->addSql("ALTER TABLE track_e_exercices MODIFY COLUMN exe_exo_id int unsigned NOT NULL default 0");
        $this->addSql("ALTER TABLE track_e_default MODIFY COLUMN default_event_type VARCHAR(255)");
        $this->addSql("ALTER TABLE track_e_default MODIFY COLUMN default_value_type VARCHAR(255)");
        $this->addSql("ALTER TABLE usergroup ADD COLUMN allow_members_leave_group int NOT NULL DEFAULT 1");
        $this->addSql("ALTER TABLE c_quiz_answer MODIFY COLUMN answer longtext NOT NULL");
        $this->addSql("ALTER TABLE c_quiz_answer MODIFY COLUMN comment longtext");
        $this->addSql("ALTER TABLE c_announcement MODIFY COLUMN content longtext");
        $this->addSql("ALTER TABLE c_attendance MODIFY COLUMN description longtext");
        $this->addSql("ALTER TABLE c_calendar_event MODIFY COLUMN content longtext");
        $this->addSql("ALTER TABLE c_blog_comment MODIFY COLUMN comment longtext NOT NULL");
        $this->addSql("ALTER TABLE c_course_description MODIFY COLUMN content longtext");
        $this->addSql("ALTER TABLE c_forum_forum MODIFY COLUMN forum_comment longtext");
        $this->addSql("ALTER TABLE c_forum_post MODIFY COLUMN post_text longtext");
        $this->addSql("ALTER TABLE c_glossary MODIFY COLUMN description longtext NOT NULL");
        $this->addSql("ALTER TABLE c_group_category MODIFY COLUMN description longtext NOT NULL");
        $this->addSql("ALTER TABLE c_group_info MODIFY COLUMN description longtext");
        $this->addSql("ALTER TABLE c_lp MODIFY COLUMN description longtext");
        $this->addSql("ALTER TABLE c_notebook MODIFY COLUMN description longtext NOT NULL");
        $this->addSql("ALTER TABLE c_quiz MODIFY COLUMN description longtext");
        $this->addSql("ALTER TABLE c_quiz MODIFY COLUMN text_when_finished longtext");
        $this->addSql("ALTER TABLE c_quiz_question MODIFY COLUMN question longtext NOT NULL");
        $this->addSql("ALTER TABLE c_quiz_question MODIFY COLUMN description longtext");
        $this->addSql("ALTER TABLE c_quiz_category MODIFY COLUMN description longtext NOT NULL");
        $this->addSql("ALTER TABLE c_student_publication MODIFY COLUMN description longtext");
        $this->addSql("ALTER TABLE c_survey MODIFY COLUMN intro longtext");
        $this->addSql("ALTER TABLE c_survey_question MODIFY COLUMN survey_question_comment longtext NOT NULL");
        $this->addSql("ALTER TABLE c_survey_question MODIFY COLUMN survey_question longtext NOT NULL");
        $this->addSql("ALTER TABLE c_thematic MODIFY COLUMN content longtext");
        $this->addSql("ALTER TABLE c_thematic_advance MODIFY COLUMN content longtext");
        $this->addSql("ALTER TABLE c_thematic_plan MODIFY COLUMN description longtext");
        $this->addSql("ALTER TABLE c_tool_intro MODIFY COLUMN intro_text longtext NOT NULL");
        $this->addSql("ALTER TABLE c_wiki MODIFY COLUMN content longtext NOT NULL");
        $this->addSql("ALTER TABLE c_student_publication_comment MODIFY COLUMN comment longtext");
        $this->addSql("ALTER TABLE sys_announcement MODIFY COLUMN content longtext NOT NULL");
        $this->addSql("ALTER TABLE shared_survey MODIFY COLUMN intro longtext");
        $this->addSql("ALTER TABLE shared_survey_question MODIFY COLUMN survey_question longtext NOT NULL");
        $this->addSql("ALTER TABLE shared_survey_question_option MODIFY COLUMN option_text longtext NOT NULL");
        $this->addSql("ALTER TABLE sys_calendar MODIFY COLUMN content longtext");
        $this->addSql("ALTER TABLE system_template MODIFY COLUMN content longtext NOT NULL");
        $this->addSql("ALTER TABLE message MODIFY COLUMN content longtext NOT NULL");
        $this->addSql("ALTER TABLE track_e_attempt MODIFY COLUMN answer longtext NOT NULL");
        $this->addSql("ALTER TABLE track_e_attempt_recording MODIFY COLUMN teacher_comment longtext NOT NULL");
        $this->addSql("ALTER TABLE personal_agenda MODIFY COLUMN `text` longtext");
        $this->addSql("ALTER TABLE c_tool ADD COLUMN custom_icon varchar(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE c_tool ADD COLUMN description text DEFAULT NULL");

        $this->addSql('ALTER TABLE notification ADD COLUMN sender_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE session_rel_user ADD COLUMN moved_to INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE session_rel_user ADD COLUMN moved_status INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE session_rel_user ADD COLUMN moved_at datetime NOT NULL default "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE session ADD COLUMN display_start_date datetime NOT NULL default "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE session ADD COLUMN display_end_date datetime NOT NULL default "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE session ADD COLUMN access_start_date datetime NOT NULL default "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE session ADD COLUMN access_end_date datetime NOT NULL default "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE session ADD COLUMN coach_access_start_date datetime NOT NULL default "0000-00-00 00:00:00"');
        $this->addSql('ALTER TABLE session ADD COLUMN coach_access_end_date datetime NOT NULL default "0000-00-00 00:00:00"');

        $this->addSql('ALTER TABLE grade_components ADD COLUMN prefix VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE grade_components  ADD COLUMN exclusions VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE grade_components ADD COLUMN count_elements VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE track_e_online ADD INDEX idx_trackonline_uat (login_user_id, access_url_id, login_date)');
        $this->addSql('ALTER TABLE session_field_options ADD INDEX idx_session_field_options_field_id(field_id)');
        $this->addSql('ALTER TABLE session_field_values ADD INDEX idx_session_field_values_session_id(session_id)');
        $this->addSql('ALTER TABLE session_field_values ADD INDEX idx_session_field_values_field_id(field_id)');

        $this->addSql('ALTER TABLE session MODIFY COLUMN session_category_id int default NULL');
        $this->addSql('ALTER TABLE session MODIFY COLUMN name CHAR(150) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE session MODIFY COLUMN id INT unsigned NOT NULL');
        $this->addSql('ALTER TABLE session MODIFY COLUMN id INT unsigned NOT NULL auto_increment;');

        $this->addSql('ALTER TABLE session_rel_course MODIFY COLUMN course_code char(40) NOT NULL');
        $this->addSql('ALTER TABLE session_rel_course MODIFY COLUMN id_session INT unsigned NOT NULL');
        $this->addSql('ALTER TABLE session_rel_course ADD COLUMN c_id INT NOT NULL DEFAULT "0"');
        $this->addSql('ALTER TABLE session_rel_course ADD COLUMN id INT NOT NULL');
        $this->addSql('ALTER TABLE session_rel_course DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE session_rel_course MODIFY COLUMN id int unsigned PRIMARY KEY AUTO_INCREMENT');
        $this->addSql('ALTER TABLE session_rel_course ADD INDEX idx_session_rel_course_course_id (c_id)');

        $this->addSql('ALTER TABLE session_rel_course_rel_user MODIFY COLUMN id_session INT unsigned NOT NULL');
        $this->addSql('ALTER TABLE session_rel_course_rel_user ADD COLUMN c_id INT NOT NULL DEFAULT "0"');
        $this->addSql('ALTER TABLE session_rel_course_rel_user ADD COLUMN id INT NOT NULL');
        $this->addSql('ALTER TABLE session_rel_course_rel_user DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE session_rel_course_rel_user ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE session_rel_course_rel_user ADD INDEX idx_session_rel_course_rel_user_id_user (id_user)');
        $this->addSql('ALTER TABLE session_rel_course_rel_user ADD INDEX idx_session_rel_course_rel_user_course_id (c_id)');
        $this->addSql('ALTER TABLE session_rel_user ADD INDEX idx_session_rel_user_id_user_moved (id_user, moved_to)');

        $this->addSql('ALTER TABLE c_item_property ADD INDEX idx_itemprop_id_tool (c_id, tool(8))');
        $this->addSql('ALTER TABLE c_quiz_answer ADD INDEX idx_quiz_answer_c_q (c_id, question_id)');

        $this->addSql('ALTER TABLE c_quiz_question_rel_category MODIFY COLUMN c_id INT unsigned NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category MODIFY COLUMN question_id INT unsigned NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD COLUMN iid INT unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT');

        $this->addSql('ALTER TABLE session ADD INDEX idx_id_coach (id_coach)');
        $this->addSql('ALTER TABLE session ADD INDEX idx_id_session_admin_id (session_admin_id)');

        $this->addSql('ALTER TABLE settings_current ADD INDEX idx_settings_current_au_cat (access_url, category(5))');

        $this->addSql('ALTER TABLE course_module change `row` row_module int unsigned NOT NULL default "0"');
        $this->addSql('ALTER TABLE course_module change `column` column_module int unsigned NOT NULL default "0"');

        $this->addSql('ALTER TABLE c_quiz_question ADD COLUMN parent_id INT unsigned NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN email_notification_template TEXT DEFAULT ""');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN model_type INT DEFAULT 1');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN score_type_model INT DEFAULT 0');
        $this->addSql('ALTER TABLE gradebook_evaluation ADD COLUMN evaluation_type_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE gradebook_link ADD COLUMN evaluation_type_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE access_url ADD COLUMN url_type tinyint unsigned default 1');
        $this->addSql('ALTER TABLE c_survey_invitation ADD COLUMN group_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE c_lp ADD COLUMN category_id INT unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE c_lp ADD COLUMN max_attempts INT NOT NULL default 0');
        $this->addSql('ALTER TABLE c_lp ADD COLUMN subscribe_users INT NOT NULL default 0');
        $this->addSql('ALTER TABLE usergroup ADD COLUMN group_type INT unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE usergroup ADD COLUMN picture varchar(255) NOT NULL');
        $this->addSql('ALTER TABLE usergroup ADD COLUMN url varchar(255) NOT NULL');
        $this->addSql('ALTER TABLE usergroup ADD COLUMN visibility varchar(255) NOT NULL');
        $this->addSql('ALTER TABLE usergroup ADD COLUMN updated_on varchar(255) NOT NULL');
        $this->addSql('ALTER TABLE usergroup ADD COLUMN created_on varchar(255) NOT NULL');

        $this->addSql('ALTER TABLE course_rel_user ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE course_rel_user DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE course_rel_user ADD COLUMN id int unsigned AUTO_INCREMENT, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE course_rel_user ADD INDEX course_rel_user_c_id_user_id (c_id, user_id)');

        $this->addSql('ALTER TABLE c_item_property MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE c_item_property DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_item_property DROP COLUMN id');
        $this->addSql('ALTER TABLE c_item_property ADD COLUMN id bigint unsigned NOT NULL auto_increment, ADD PRIMARY KEY (id)');

        $this->addSql('ALTER TABLE c_item_property MODIFY id_session INT default NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY COLUMN start_visible datetime default NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY COLUMN end_visible datetime default NULL');

        $this->addSql('ALTER TABLE c_group_info MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE c_group_info MODIFY c_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_group_info MODIFY session_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_group_info DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_group_info ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY');

        $this->addSql('ALTER TABLE usergroup_rel_tag ADD INDEX usergroup_rel_tag_usergroup_id ( usergroup_id )');
        $this->addSql('ALTER TABLE usergroup_rel_tag ADD INDEX usergroup_rel_tag_tag_id (tag_id)');
        $this->addSql('ALTER TABLE usergroup_rel_user ADD COLUMN relation_type int NOT NULL default 0');
        $this->addSql('ALTER TABLE usergroup_rel_user ADD INDEX usergroup_rel_user_usergroup_id (usergroup_id)');
        $this->addSql('ALTER TABLE usergroup_rel_user ADD INDEX usergroup_rel_user_user_id (user_id)');
        $this->addSql('ALTER TABLE usergroup_rel_user ADD INDEX usergroup_rel_user_relation_type (relation_type)');
        $this->addSql('ALTER TABLE usergroup_rel_usergroup ADD INDEX usergroup_rel_usergroup_group_id( group_id )');
        $this->addSql('ALTER TABLE usergroup_rel_usergroup ADD INDEX usergroup_rel_usergroup_subgroup_id( subgroup_id )');
        $this->addSql('ALTER TABLE usergroup_rel_usergroup ADD INDEX usergroup_rel_usergroup_relation_type( relation_type )');

        $this->addSql('ALTER TABLE announcement_rel_group DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE announcement_rel_group ADD COLUMN id INT unsigned NOT NULL auto_increment PRIMARY KEY');
        $this->addSql('ALTER TABLE track_e_hotpotatoes ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_exercices ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_attempt ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_hotspot ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_course_access ADD COLUMN c_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE access_url_rel_course ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_lastaccess ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_access ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_downloads ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_links ADD COLUMN c_id int unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE track_e_lastaccess ADD INDEX track_e_lastaccess_c_id_access_user_id (c_id, access_user_id)');

        $this->addSql('ALTER TABLE access_url_rel_course DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE access_url_rel_course ADD COLUMN id int unsigned NOT NULL auto_increment PRIMARY KEY');

        $this->addSql('ALTER TABLE c_quiz ADD COLUMN autolaunch int DEFAULT 0');
        //$this->addSql('RENAME TABLE c_quiz_question_category TO c_quiz_category');
        $schema->renameTable('c_quiz_question_category', 'c_quiz_category');
        $this->addSql('ALTER TABLE c_quiz_category ADD COLUMN parent_id int unsigned default NULL');

        $this->addSql('ALTER TABLE c_quiz DROP INDEX session_id');
        $this->addSql('ALTER TABLE c_quiz MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz MODIFY c_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY');

        $this->addSql('ALTER TABLE c_quiz_question MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question MODIFY c_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_question ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY');

        $this->addSql('ALTER TABLE c_quiz_answer MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer MODIFY c_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer MODIFY id_auto INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_answer DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_answer ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_answer ADD INDEX idx_cqa_qid (question_id)');

        $this->addSql('ALTER TABLE c_quiz_question_option MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question_option MODIFY c_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_question_option DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_question_option ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY');

        $this->addSql('ALTER TABLE c_quiz_rel_question MODIFY question_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_rel_question MODIFY exercice_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_rel_question DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_rel_question ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_rel_question ADD INDEX idx_cqrq_id (question_id)');
        $this->addSql('ALTER TABLE c_quiz_rel_question ADD INDEX idx_cqrq_cidexid (c_id, exercice_id)');

        $this->addSql('ALTER TABLE c_quiz_category MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_category MODIFY c_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_quiz_category DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_quiz_category ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY');

        $this->addSql('ALTER TABLE question_field_options ADD INDEX idx_question_field_options_field_id(field_id)');
        $this->addSql('ALTER TABLE question_field_values ADD INDEX idx_question_field_values_question_id(question_id)');
        $this->addSql('ALTER TABLE question_field_values ADD INDEX idx_question_field_values_field_id(field_id)');

        $this->addSql('ALTER TABLE question_field_values ADD COLUMN user_id INT unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE session_field_values ADD COLUMN user_id INT unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE course_field_values ADD COLUMN user_id INT unsigned NOT NULL default 0');
        $this->addSql('ALTER TABLE user_field_values ADD COLUMN author_id INT unsigned NOT NULL default 0');

        $this->addSql('ALTER TABLE c_quiz_category ADD COLUMN lvl int');
        $this->addSql('ALTER TABLE c_quiz_category ADD COLUMN lft int');
        $this->addSql('ALTER TABLE c_quiz_category ADD COLUMN rgt int');
        $this->addSql('ALTER TABLE c_quiz_category ADD COLUMN root int');
        $this->addSql('ALTER TABLE c_quiz_category MODIFY COLUMN parent_id int default null');

        $this->addSql('ALTER TABLE track_e_course_access MODIFY COLUMN course_access_id bigint unsigned auto_increment');
        $this->addSql('ALTER TABLE user_field ADD COLUMN field_loggeable int default 0');
        $this->addSql('ALTER TABLE session_field ADD COLUMN field_loggeable int default 0');
        $this->addSql('ALTER TABLE course_field ADD COLUMN field_loggeable int default 0');
        $this->addSql('ALTER TABLE question_field ADD COLUMN field_loggeable int default 0');

        $this->addSql('ALTER TABLE user_field_values ADD COLUMN comment VARCHAR(100) default ""');
        $this->addSql('ALTER TABLE session_field_values ADD COLUMN comment VARCHAR(100) default ""');
        $this->addSql('ALTER TABLE course_field_values ADD COLUMN comment VARCHAR(100) default ""');
        $this->addSql('ALTER TABLE question_field_values ADD COLUMN comment VARCHAR(100) default ""');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN end_button int NOT NULL default 0');
        $this->addSql('ALTER TABLE user ADD COLUMN salt VARCHAR(255) DEFAULT NULL');

        $this->addSql('ALTER TABLE question_field_options ADD COLUMN priority INT default NULL');
        $this->addSql('ALTER TABLE course_field_options ADD COLUMN priority INT default NULL');
        $this->addSql('ALTER TABLE user_field_options ADD COLUMN priority INT default NULL');
        $this->addSql('ALTER TABLE session_field_options ADD COLUMN priority INT default NULL');

        $this->addSql('ALTER TABLE question_field_options ADD COLUMN priority_message varchar(255) default NULL');
        $this->addSql('ALTER TABLE course_field_options ADD COLUMN priority_message varchar(255) default NULL');
        $this->addSql('ALTER TABLE user_field_options ADD COLUMN priority_message varchar(255) default NULL');
        $this->addSql('ALTER TABLE session_field_options ADD COLUMN priority_message varchar(255) default NULL');

        $this->addSql('ALTER TABLE c_announcement CHANGE id id int unsigned not null');
        $this->addSql('ALTER TABLE c_announcement DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_announcement add COLUMN iid int unsigned not null AUTO_INCREMENT PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE c_announcement add UNIQUE KEY(c_id,id)');
        $this->addSql('ALTER TABLE c_announcement ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_announcement_attachment CHANGE id id int unsigned not null');
        $this->addSql('ALTER TABLE c_announcement_attachment DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_announcement_attachment add COLUMN iid int unsigned not null AUTO_INCREMENT PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE c_announcement_attachment add UNIQUE KEY(c_id,id)');
        $this->addSql('ALTER TABLE c_announcement_attachment ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_attendance CHANGE id id int unsigned not null');
        $this->addSql('ALTER TABLE c_attendance DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_attendance add COLUMN iid int unsigned not null AUTO_INCREMENT PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE c_attendance add UNIQUE KEY(c_id,id)');
        $this->addSql('ALTER TABLE c_attendance ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_attendance_calendar CHANGE id id int unsigned not null');
        $this->addSql('ALTER TABLE c_attendance_calendar DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_attendance_calendar add COLUMN iid int unsigned not null AUTO_INCREMENT PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE c_attendance_calendar add UNIQUE KEY(c_id,id)');
        $this->addSql('ALTER TABLE c_attendance_calendar ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_attendance_result CHANGE id id int unsigned not null');
        $this->addSql('ALTER TABLE c_attendance_result DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_attendance_result add COLUMN iid int unsigned not null AUTO_INCREMENT PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE c_attendance_result add UNIQUE KEY(c_id,id)');
        $this->addSql('ALTER TABLE c_attendance_result ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_attendance_sheet DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_attendance_sheet add COLUMN iid int unsigned not null AUTO_INCREMENT PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE c_attendance_sheet add UNIQUE KEY(c_id,user_id,attendance_calendar_id)');
        $this->addSql('ALTER TABLE c_attendance_sheet ENGINE = InnoDB');
        $this->addSql('ALTER TABLE c_attendance_sheet_log CHANGE id id int unsigned not null');
        $this->addSql('ALTER TABLE c_attendance_sheet_log DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE c_attendance_sheet_log add COLUMN iid int unsigned not null AUTO_INCREMENT PRIMARY KEY FIRST');
        $this->addSql('ALTER TABLE c_attendance_sheet_log add UNIQUE KEY(c_id,id)');
        $this->addSql('ALTER TABLE c_attendance_sheet_log ENGINE = InnoDB');
        $this->addSql('ALTER TABLE session ADD COLUMN description TEXT');
        $this->addSql('ALTER TABLE session ADD COLUMN show_description int default NULL');

        $this->addSql('ALTER TABLE c_quiz_category ADD COLUMN visibility INT default 1');
        $this->addSql('ALTER TABLE c_quiz_question ADD INDEX idx_c_q_qst_cpt (c_id, parent_id, type)');
        $this->addSql('ALTER TABLE c_quiz_question_rel_category ADD INDEX idx_c_q_qst_r_cat_qc(question_id, c_id)');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN question_selection_type INT DEFAULT 1');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN hide_question_title INT DEFAULT 0');
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN global_category_id INT DEFAULT NULL');

        $this->addSql('ALTER TABLE user MODIFY COLUMN chatcall_user_id int unsigned default 0');
        $this->addSql('ALTER TABLE user MODIFY COLUMN chatcall_date datetime default NULL');
        $this->addSql('ALTER TABLE user MODIFY COLUMN chatcall_text varchar(50) default NULL');
        $this->addSql('ALTER TABLE user MODIFY COLUMN expiration_date datetime default NULL');

        $this->addSql('ALTER TABLE c_blog_rating MODIFY COLUMN rating_type varchar(100) NOT NULL DEFAULT "post"');
        $this->addSql('ALTER TABLE c_link MODIFY COLUMN on_homepage varchar(100) NOT NULL DEFAULT "0"');
        $this->addSql('ALTER TABLE c_quiz_answer MODIFY COLUMN hotspot_type varchar(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE c_survey MODIFY COLUMN anonymous varchar(255) NOT NULL DEFAULT "0"');
        $this->addSql('ALTER TABLE c_tool MODIFY COLUMN target varchar(100) NOT NULL DEFAULT "_self"');
        $this->addSql('ALTER TABLE course_category MODIFY COLUMN rating_type varchar(100) NOT NULL DEFAULT "post"');
        $this->addSql('ALTER TABLE course_category MODIFY COLUMN rating_type varchar(100) NOT NULL DEFAULT "post"');

        $this->addSql('ALTER TABLE user_rel_tag ADD INDEX idx_user_rel_tag_user (user_id)');
    }

    /**
     * Chamilo downgrade
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql(
            "UPDATE settings_current SET selected_value = '1.9.0' WHERE variable = 'chamilo_database_version'"
        );
    }

    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        $this->addSql("DROP TABLE IF EXISTS track_c_referers");
        $this->addSql("DROP TABLE IF EXISTS track_c_providers");
        $this->addSql("DROP TABLE IF EXISTS track_c_os");
        $this->addSql("DROP TABLE IF EXISTS track_c_countries");
        $this->addSql("DROP TABLE IF EXISTS track_c_browsers");
        $this->addSql("DROP TABLE IF EXISTS track_e_open");

        /*
        -- ALTER TABLE session_rel_course DROP COLUMN course_code;
        -- ALTER TABLE session_rel_course_rel_user DROP COLUMN course_code;
        -- ALTER TABLE track_e_hotpotatoes DROP COLUMN course_code;
        -- ALTER TABLE track_e_exercices DROP COLUMN course_code;
        -- ALTER TABLE track_e_attempt DROP COLUMN course_code;
        -- ALTER TABLE track_e_hotspot DROP COLUMN course_code;
        -- ALTER TABLE track_e_course_access DROP COLUMN course_code;
        -- ALTER TABLE access_url_rel_course DROP COLUMN course_code;
        -- ALTER TABLE course_rel_user DROP COLUMN course_code;
        -- ALTER TABLE track_e_lastaccess DROP COLUMN access_cours_code;

        -- ALTER TABLE track_e_lastaccess DROP COLUMN access_cours_code;
        -- ALTER TABLE track_e_access    DROP COLUMN access_cours_code ;
        -- ALTER TABLE track_e_course_access DROP COLUMN course_code;
        -- ALTER TABLE track_e_downloads DROP COLUMN  down_cours_id;
        -- ALTER TABLE track_e_links DROP COLUMN links_cours_id;

        -- ALTER TABLE session DROP COLUMN date_start;
        -- ALTER TABLE session DROP COLUMN date_end;*/


        $this->addSql("DELETE FROM settings_current WHERE variable = 'session_tutor_reports_visibility'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'session_tutor_reports_visibility'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'gradebook_show_percentage_in_reports'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'gradebook_show_percentage_in_reports'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'login_as_allowed'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'login_as_allowed'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'admins_can_set_users_pass'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'admins_can_set_users_pass'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'session_page_enabled'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'session_page_enabled'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'settings_latest_update'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'settings_latest_update'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'user_name_order'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'user_name_sort_by'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'allow_teachers_to_create_sessions'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'allow_teachers_to_create_sessions'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'template'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'use_virtual_keyboard'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'use_virtual_keyboard'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'breadcrumb_navigation_display'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'breadcrumb_navigation_display'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'default_calendar_view'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'default_calendar_view'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'disable_copy_paste'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'disable_copy_paste'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'showonline'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'last_transaction_id'");

        $this->addSql("UPDATE settings_current SET category = 'Admin' WHERE variable = 'emailAdministrator'");
        $this->addSql("UPDATE settings_current SET category = 'Admin' WHERE variable = 'administratorSurname'");
        $this->addSql("UPDATE settings_current SET category = 'Admin' WHERE variable = 'administratorName'");
        $this->addSql("UPDATE settings_current SET category = 'Admin' WHERE variable = 'show_administrator_data'");
        $this->addSql("UPDATE settings_current SET category = 'Admin' WHERE variable = 'administratorTelephone'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'allow_registration'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'allow_registration_as_teacher'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'allow_lostpassword'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'page_after_login'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'allow_terms_conditions'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'student_page_after_login'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'teacher_page_after_login'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'drh_page_after_login'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'student_autosubscribe'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'teacher_autosubscribe'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'drh_autosubscribe'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'platform_unsubscribe_allowed'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'teacher_page_after_login'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'registration'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'extendedprofile_registration'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'extendedprofile_registrationrequired'");
        $this->addSql("UPDATE settings_current SET category = 'Registration' WHERE variable = 'login_is_email'");
        $this->addSql("UPDATE settings_current SET category = 'Course' WHERE variable = 'display_coursecode_in_courselist'");
        $this->addSql("UPDATE settings_current SET category = 'Course' WHERE variable = 'display_teacher_in_courselist'");
        $this->addSql("UPDATE settings_current SET category = 'Course' WHERE variable = 'student_view_enabled'");
        $this->addSql("UPDATE settings_current SET category = 'Course' WHERE variable = 'course_validation'");
        $this->addSql("UPDATE settings_current SET category = 'User' WHERE variable = 'user_selected_theme'");

        $this->addSql("TRUNCATE roles");
        $this->addSql("INSERT INTO roles (id, name, role) VALUES('1', 'Teacher', 'ROLE_TEACHER')");
        $this->addSql("INSERT INTO roles (id, name, role) VALUES('4', 'RRHH', 'ROLE_RRHH')");
        $this->addSql("INSERT INTO roles (id, name, role) VALUES('3', 'Session Manager', 'ROLE_SESSION_MANAGER')");
        $this->addSql("INSERT INTO roles (id ,name, role) VALUES('5', 'Student', 'ROLE_STUDENT')");
        $this->addSql("INSERT INTO roles (id, name, role) VALUES('6', 'Anonymous', 'ROLE_ANONYMOUS')");
        $this->addSql("INSERT INTO roles (id, name, role) VALUES('11', 'Admin', 'ROLE_ADMIN')");
        $this->addSql("INSERT INTO roles (id, name, role) VALUES('17', 'Question Manager', 'ROLE_QUESTION_MANAGER')");
        $this->addSql("INSERT INTO roles (id, name, role) VALUES('18', 'Global admin', 'ROLE_GLOBAL_ADMIN')");
        $this->addSql("INSERT INTO users_roles VALUES (1, 11)");

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('session_tutor_reports_visibility', NULL, 'radio', 'Session', 'true', 'SessionTutorsCanSeeExpiredSessionsResultsTitle', 'SessionTutorsCanSeeExpiredSessionsResultsComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_show_percentage_in_reports', NULL,'radio','Gradebook','true','GradebookShowPercentageInReportsTitle','GradebookShowPercentageInReportsComment', NULL, NULL, 0)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('login_as_allowed', NULL, 'radio', 'security', 'true','AdminLoginAsAllowedTitle', 'AdminLoginAsAllowedComment', 1, 0, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('admins_can_set_users_pass', NULL, 'radio', 'security', 'true', 'AdminsCanChangeUsersPassTitle', 'AdminsCanChangeUsersPassComment', 1, 0, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('session_page_enabled', NULL, 'radio', 'Session', 'true', 'SessionPageEnabledTitle', 'SessionPageEnabledComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('settings_latest_update', NULL, NULL, NULL, '', '','', NULL, NULL, 0)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('user_name_order', NULL, 'textfield', 'Platform', '', 'UserNameOrderTitle', 'UserNameOrderComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('user_name_sort_by', NULL, 'textfield', 'Platform', '', 'UserNameSortByTitle', 'UserNameSortByComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_teachers_to_create_sessions', NULL,'radio','Session','false','AllowTeachersToCreateSessionsTitle','AllowTeachersToCreateSessionsComment', NULL, NULL, 0)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('template', NULL, 'text', 'stylesheets', 'default', 'DefaultTemplateTitle', 'DefaultTemplateComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('breadcrumb_navigation_display', NULL, 'radio', 'Platform','true','BreadcrumbNavigationDisplayTitle', 'BreadcrumbNavigationDisplayComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('use_virtual_keyboard', NULL, 'radio', 'Platform', 'false','ShowVirtualKeyboardTitle','ShowVirtualKeyboardComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('default_calendar_view', NULL, 'radio', 'Platform','month','DefaultCalendarViewTitle', 'DefaultCalendarViewComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('disable_copy_paste', NULL, 'radio', 'Platform', 'false','DisableCopyPasteTitle','DisableCopyPasteComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('showonline','session','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineSession', 0)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('documents_default_visibility_defined_in_course', NULL,'radio','Tools','false','DocumentsDefaultVisibilityDefinedInCourseTitle','DocumentsDefaultVisibilityDefinedInCourseComment',NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_personal_user_files', NULL,'radio','Tools','false','AllowPersonalUserFilesTitle','AllowPersonalUserFilesComment',NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('bug_report_link', NULL, 'textfield','Platform','','BugReportLinkTitle','BugReportLinkComment', NULL, NULL, 1)");
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('upload_extensions_whitelist', NULL, 'textfield', 'Security', 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf,webm,oga,ogg,ogv,h264', 'UploadExtensionsWhitelist', 'UploadExtensionsWhitelistComment', NULL, NULL, 0)");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'upload_extensions_whitelist'");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'false', 'No')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_show_percentage_in_reports', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_show_percentage_in_reports', 'false', 'No')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('login_as_allowed','true','Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('login_as_allowed','false','No')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('admins_can_set_users_pass','true','Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('admins_can_set_users_pass','false','No')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('last_transaction_id', '0', '')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('session_page_enabled', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('session_page_enabled', 'false', 'No')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'false', 'No')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('breadcrumb_navigation_display', 'true', 'Show')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('breadcrumb_navigation_display', 'false', 'Hide')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('use_virtual_keyboard', 'true', 'Show')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('use_virtual_keyboard', 'false', 'Hide')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('disable_copy_paste', 'true', 'Show')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('disable_copy_paste', 'false', 'Hide')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'month', 'Month')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'basicWeek', 'BasicWeek')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'agendaWeek', 'Week')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'agendaDay', 'Day')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'false', 'No')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_personal_user_files', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_personal_user_files', 'false', 'No')");
        $this->addSql("TRUNCATE branch_transaction_status");
        $this->addSql("INSERT INTO branch_transaction_status VALUES (1, 'To be executed'), (2, 'Executed successfully'), (3, 'Execution deprecated'), (4, 'Execution failed')");
        $this->addSql("UPDATE course_field SET field_type = 3 WHERE field_variable = 'special_course'");
        $this->addSql("ALTER TABLE c_item_property ADD INDEX idx_item_property_tooliuid(tool, insert_user_id)");

        // Chamilo version
        $this->addSql(
            "UPDATE settings_current SET selected_value = '10.064' WHERE variable = 'chamilo_database_version'"
        );
    }

    /**
     * @param Schema $schema
     */
    public function preDown(Schema $schema)
    {
    }

    /**
     * @param Schema $schema
     */
    public function postDown(Schema $schema)
    {
    }
}
