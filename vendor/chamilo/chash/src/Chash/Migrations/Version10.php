<?php

namespace Chash\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Manages the migration to Chamilo 1.10
 * @package ChamiloLMS\Controller\Migrations
 */
class Version10 extends AbstractMigration
{
    /**
     * Chamilo upgrade
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $trackDefaultTable = $schema->getTable('track_e_default');
        $trackDefaultTable->addColumn('session_id', 'integer', array('default' => 0, 'Notnull' => true));

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
        $this->addSql('RENAME TABLE c_quiz_question_category TO c_quiz_category');
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

        $this->addSql(
            "UPDATE settings_current SET selected_value = '1.10.0' WHERE variable = 'chamilo_database_version'"
        );
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
}
