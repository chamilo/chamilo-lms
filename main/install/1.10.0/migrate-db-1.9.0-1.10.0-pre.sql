-- This script updates the databases structure before migrating the data from
-- version 1.9.0 (or version 1.9.2, .4, .6 or .8) to version 1.10.0
-- It is intended as a standalone script. Because of the relatively recent
-- migration to a single-database structure, it is still recommended to let
-- it be parsed by Chamilo installation scripts. We will modify this message
-- once we have ensured it works properly through direct SQL execution.
-- There is one line per query now, allowing the PHP function file() to read
-- all lines separately into an array. The xxMAINxx-type markers are there
-- to tell the PHP script which database we're talking about.
-- For version tracking easiness, we recommend you define all new queries
-- at the end of the corresponding section. We will re-order them previous
-- to the stable release in order to optimize the migration.

-- xxMAINxx

-- Optimize tracking query very often queried on busy campuses
ALTER TABLE track_e_online ADD INDEX idx_trackonline_uat (login_user_id, access_url_id, login_date);
ALTER TABLE track_e_default ADD COLUMN session_id INT NOT NULL DEFAULT 0;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('session_tutor_reports_visibility', NULL, 'radio', 'Session', 'true', 'SessionTutorsCanSeeExpiredSessionsResultsTitle', 'SessionTutorsCanSeeExpiredSessionsResultsComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_show_percentage_in_reports', NULL,'radio','Gradebook','true','GradebookShowPercentageInReportsTitle','GradebookShowPercentageInReportsComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_show_percentage_in_reports', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_show_percentage_in_reports', 'false', 'No');

ALTER TABLE notification ADD COLUMN sender_id INT NOT NULL DEFAULT 0;

ALTER TABLE session_rel_user ADD COLUMN moved_to INT NOT NULL DEFAULT 0;
ALTER TABLE session_rel_user ADD COLUMN moved_status INT NOT NULL DEFAULT 0;
ALTER TABLE session_rel_user ADD COLUMN moved_at datetime NOT NULL default '0000-00-00 00:00:00';

ALTER TABLE session ADD COLUMN display_start_date datetime default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN display_end_date datetime default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN access_start_date datetime default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN access_end_date datetime default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN coach_access_start_date datetime default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN coach_access_end_date datetime default '0000-00-00 00:00:00';

ALTER TABLE grade_components ADD COLUMN prefix VARCHAR(255) DEFAULT NULL;
ALTER TABLE grade_components ADD COLUMN exclusions INT DEFAULT 0;
ALTER TABLE grade_components ADD COLUMN count_elements INT DEFAULT 0;

CREATE TABLE IF NOT EXISTS session_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS course_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));

ALTER TABLE session_field_options ADD INDEX idx_session_field_options_field_id(field_id);
ALTER TABLE session_field_values ADD INDEX idx_session_field_values_session_id(session_id);
ALTER TABLE session_field_values ADD INDEX idx_session_field_values_field_id(field_id);

ALTER TABLE session MODIFY COLUMN name CHAR(150) NOT NULL DEFAULT '';
ALTER TABLE session MODIFY COLUMN id INT unsigned NOT NULL;

ALTER TABLE session_rel_course MODIFY COLUMN id_session INT unsigned NOT NULL;
ALTER TABLE session_rel_course ADD COLUMN c_id INT NOT NULL DEFAULT '0';
ALTER TABLE session_rel_course DROP PRIMARY KEY;
-- remove course_code
ALTER TABLE session_rel_course ADD COLUMN id INT NOT NULL;
ALTER TABLE session_rel_course MODIFY COLUMN id int unsigned AUTO_INCREMENT;
ALTER TABLE session_rel_course ADD INDEX idx_session_rel_course_course_id (c_id);
ALTER TABLE session_rel_course ADD PRIMARY KEY (id);

ALTER TABLE session_rel_course_rel_user MODIFY COLUMN id_session INT unsigned NOT NULL;
ALTER TABLE session_rel_course_rel_user ADD COLUMN c_id INT NOT NULL DEFAULT '0';
ALTER TABLE session_rel_course_rel_user ADD COLUMN id INT NOT NULL;
ALTER TABLE session_rel_course_rel_user DROP PRIMARY KEY;
ALTER TABLE session_rel_course_rel_user ADD PRIMARY KEY (id);


ALTER TABLE session_rel_course_rel_user ADD INDEX idx_session_rel_course_rel_user_id_user (id_user);
ALTER TABLE session_rel_course_rel_user ADD INDEX idx_session_rel_course_rel_user_course_id (c_id);

ALTER TABLE session_rel_user ADD INDEX idx_session_rel_user_id_user_moved (id_user, moved_to);

INSERT INTO settings_current(variable, type, subkey, category, selected_value, title, comment, access_url, access_url_changeable, access_url_locked) VALUES ('login_as_allowed', NULL, 'radio', 'security', 'true','AdminLoginAsAllowedTitle', 'AdminLoginAsAllowedComment', 1, 0, 1);
INSERT INTO settings_options(variable, value, display_text) VALUES ('login_as_allowed','true','Yes'),('login_as_allowed','false','No');
INSERT into settings_current(variable, type, subkey, category, selected_value, title, comment, access_url, access_url_changeable, access_url_locked) VALUES ('admins_can_set_users_pass', NULL, 'radio', 'security', 'true', 'AdminsCanChangeUsersPassTitle', 'AdminsCanChangeUsersPassComment', 1, 0, 1);
INSERT into settings_options(variable, value, display_text) VALUES('admins_can_set_users_pass','true','Yes'),('admins_can_set_users_pass','false','No');

-- Courses changes c_XXX

-- ALTER TABLE c_lp_item ADD INDEX idx_c_lp_item_cid_lp_id (c_id, lp_id);
-- ALTER TABLE c_lp_item_view ADD INDEX idx_c_lp_item_view_cid_lp_view_id_lp_item_id(c_id, lp_view_id, lp_item_id);

ALTER TABLE c_item_property ADD INDEX idx_itemprop_id_tool (c_id, tool(8));
ALTER TABLE c_tool_intro MODIFY COLUMN intro_text MEDIUMTEXT NOT NULL;

ALTER TABLE c_quiz_answer ADD INDEX idx_quiz_answer_c_q (c_id, question_id);
CREATE TABLE c_quiz_order( iid bigint unsigned NOT NULL auto_increment, c_id int unsigned NOT NULL, session_id int unsigned NOT NULL, exercise_id int NOT NULL, exercise_order INT NOT NULL, PRIMARY KEY (iid));

CREATE TABLE c_quiz_question_rel_category ( iid int unsigned NOT NULL AUTO_INCREMENT, c_id int NOT NULL, question_id int NOT NULL, category_id int NOT NULL,  PRIMARY KEY (iid));

ALTER TABLE session ADD INDEX idx_id_coach (id_coach);
ALTER TABLE session ADD INDEX idx_id_session_admin_id (session_admin_id);

ALTER TABLE c_quiz_question ADD COLUMN parent_id INT unsigned NOT NULL DEFAULT 0;
ALTER TABLE c_quiz ADD COLUMN email_notification_template TEXT DEFAULT '';
ALTER TABLE c_quiz ADD COLUMN model_type INT DEFAULT 1;

CREATE TABLE IF NOT EXISTS gradebook_evaluation_type(id INT unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT, name varchar(255), external_id INT unsigned NOT NULL DEFAULT 0);

ALTER TABLE gradebook_evaluation ADD COLUMN evaluation_type_id INT NOT NULL DEFAULT 0;
ALTER TABLE gradebook_link ADD COLUMN evaluation_type_id INT NOT NULL DEFAULT 0;

INSERT INTO settings_options(variable, value) VALUES ('last_transaction_id','0');

ALTER TABLE access_url ADD COLUMN url_type tinyint unsigned default 1;

CREATE TABLE branch_sync( id int unsigned not null AUTO_INCREMENT PRIMARY KEY, access_url_id int unsigned not null, branch_name varchar(250) default '', branch_ip varchar(40) default '', latitude decimal(15,7), longitude decimal(15,7), dwn_speed int unsigned default null, up_speed int unsigned default null, delay int unsigned default null, admin_mail varchar(250) default '', admin_name varchar(250) default '', admin_phone varchar(250) default '', last_sync_trans_id bigint unsigned default 0, last_sync_trans_date datetime, last_sync_type char(20) default 'full');

CREATE TABLE branch_sync_log( id bigint unsigned not null AUTO_INCREMENT PRIMARY KEY, branch_sync_id int unsigned not null, sync_trans_id bigint unsigned default 0, sync_trans_date datetime, sync_type char(20));

CREATE TABLE branch_transaction_status (id tinyint not null PRIMARY KEY AUTO_INCREMENT,  title char(20));
INSERT INTO branch_transaction_status VALUES (1, 'To be executed'), (2, 'Executed successfully'), (3, 'Execution deprecated'), (4, 'Execution failed');

CREATE TABLE branch_transaction (id bigint unsigned not null AUTO_INCREMENT, transaction_id bigint unsigned, branch_id int unsigned not null default 0,  action char(20),  item_id char(36),  orig_id char(36),  dest_id char(36),  info char(20), status_id tinyint not null default 0,  time_insert datetime NOT NULL DEFAULT '0000-00-00 00:00:00',  time_update datetime NOT NULL DEFAULT '0000-00-00 00:00:00', message VARCHAR(255) default '' , PRIMARY KEY (id, transaction_id, branch_id));

ALTER TABLE settings_current ADD INDEX idx_settings_current_au_cat (access_url, category(5));

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('session_page_enabled', NULL, 'radio', 'Session', 'true', 'SessionPageEnabledTitle', 'SessionPageEnabledComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_page_enabled', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_page_enabled', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('settings_latest_update', NULL, NULL, NULL, '', '','', NULL, NULL, 0);

ALTER TABLE course_module change `row` row_module int unsigned NOT NULL default '0';
ALTER TABLE course_module change `column` column_module int unsigned NOT NULL default '0';

ALTER TABLE c_survey_invitation ADD COLUMN group_id INT NOT NULL DEFAULT 0;

ALTER TABLE c_lp ADD COLUMN category_id INT unsigned NOT NULL default 0;
ALTER TABLE c_lp ADD COLUMN max_attempts INT NOT NULL default 0;
ALTER TABLE c_lp ADD COLUMN subscribe_users INT NOT NULL default 0;

CREATE TABLE c_lp_category (id int unsigned NOT NULL auto_increment, c_id INT unsigned NOT NULL, name VARCHAR(255), position INT, PRIMARY KEY (id));

ALTER TABLE user MODIFY COLUMN hr_dept_id int unsigned NOT NULL default 0;

ALTER TABLE session MODIFY COLUMN id INT unsigned NOT NULL auto_increment;
ALTER TABLE session MODIFY COLUMN nbr_courses int unsigned NOT NULL default 0;
ALTER TABLE session MODIFY COLUMN nbr_users int unsigned NOT NULL default 0;
ALTER TABLE session MODIFY COLUMN nbr_classes int unsigned NOT NULL default 0;

ALTER TABLE session_rel_course MODIFY COLUMN nbr_users int unsigned NOT NULL default 0;
ALTER TABLE track_e_exercices MODIFY COLUMN session_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_exercices MODIFY COLUMN exe_exo_id int unsigned NOT NULL default 0;

ALTER TABLE course_rel_user ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE course_rel_user DROP PRIMARY KEY;
ALTER TABLE course_rel_user ADD COLUMN id int unsigned AUTO_INCREMENT, ADD PRIMARY KEY (id);
ALTER TABLE course_rel_user ADD INDEX (c_id, user_id);

ALTER TABLE c_item_property MODIFY id INT NOT NULL;
ALTER TABLE c_item_property DROP PRIMARY KEY;
ALTER TABLE c_item_property DROP COLUMN id;
ALTER TABLE c_item_property ADD COLUMN id bigint unsigned NOT NULL auto_increment, ADD PRIMARY KEY (id);

ALTER TABLE c_item_property MODIFY id_session INT default NULL;
ALTER TABLE c_item_property MODIFY COLUMN start_visible datetime default NULL;
ALTER TABLE c_item_property MODIFY COLUMN end_visible datetime default NULL;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('user_name_order', NULL, 'textfield', 'Platform', '', 'UserNameOrderTitle', 'UserNameOrderComment', NULL, NULL, 1);

ALTER TABLE c_group_info MODIFY id INT NOT NULL;
ALTER TABLE c_group_info MODIFY c_id INT NOT NULL;
ALTER TABLE c_group_info MODIFY session_id INT NOT NULL;
ALTER TABLE c_group_info DROP PRIMARY KEY;
ALTER TABLE c_group_info ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_teachers_to_create_sessions', NULL,'radio','Session','false','AllowTeachersToCreateSessionsTitle','AllowTeachersToCreateSessionsComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'false', 'No');

UPDATE course_field SET field_type = 3 WHERE field_variable = 'special_course';

ALTER TABLE usergroup ADD COLUMN group_type INT unsigned NOT NULL default 0;
ALTER TABLE usergroup ADD COLUMN picture varchar(255) NOT NULL;
ALTER TABLE usergroup ADD COLUMN url varchar(255) NOT NULL;
ALTER TABLE usergroup ADD COLUMN visibility varchar(255) NOT NULL;
ALTER TABLE usergroup ADD COLUMN updated_on varchar(255) NOT NULL;
ALTER TABLE usergroup ADD COLUMN created_on varchar(255) NOT NULL;

CREATE TABLE IF NOT EXISTS usergroup_rel_tag( id int NOT NULL AUTO_INCREMENT, tag_id int NOT NULL, usergroup_id int NOT NULL, PRIMARY KEY (id));
ALTER TABLE usergroup_rel_tag ADD INDEX ( usergroup_id );
ALTER TABLE usergroup_rel_tag ADD INDEX ( tag_id );

ALTER TABLE usergroup_rel_user ADD relation_type int NOT NULL default 0;

ALTER TABLE usergroup_rel_user ADD INDEX ( usergroup_id );
ALTER TABLE usergroup_rel_user ADD INDEX ( user_id );
ALTER TABLE usergroup_rel_user ADD INDEX ( relation_type );

CREATE TABLE IF NOT EXISTS usergroup_rel_usergroup (id int NOT NULL AUTO_INCREMENT, group_id int NOT NULL, subgroup_id int NOT NULL, relation_type int NOT NULL, PRIMARY KEY (id));

ALTER TABLE usergroup_rel_usergroup ADD INDEX ( group_id );
ALTER TABLE usergroup_rel_usergroup ADD INDEX ( subgroup_id );
ALTER TABLE usergroup_rel_usergroup ADD INDEX ( relation_type );

ALTER TABLE announcement_rel_group DROP PRIMARY KEY;
ALTER TABLE announcement_rel_group ADD COLUMN id INT unsigned NOT NULL auto_increment PRIMARY KEY;
ALTER TABLE track_e_hotpotatoes ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_exercices ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_attempt ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_hotspot ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_course_access ADD COLUMN c_id INT NOT NULL DEFAULT 0;
ALTER TABLE access_url_rel_course ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_lastaccess ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_access ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_downloads ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_links ADD COLUMN c_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_lastaccess ADD INDEX (c_id, access_user_id);

ALTER TABLE access_url_rel_course DROP PRIMARY KEY;
ALTER TABLE access_url_rel_course ADD COLUMN id int unsigned NOT NULL auto_increment PRIMARY KEY;

ALTER TABLE c_quiz ADD COLUMN autolaunch int DEFAULT 0;
RENAME TABLE c_quiz_question_category TO c_quiz_category;
ALTER TABLE c_quiz_category ADD COLUMN parent_id int unsigned default NULL;

CREATE TABLE c_quiz_rel_category (iid bigint unsigned NOT NULL auto_increment, c_id INT unsigned default 0, category_id int unsigned NOT NULL, exercise_id int unsigned NOT NULL, count_questions int NOT NULL default 0, PRIMARY KEY(iid));

ALTER TABLE c_quiz DROP INDEX session_id;
ALTER TABLE c_quiz MODIFY id INT NOT NULL;
ALTER TABLE c_quiz MODIFY c_id INT NOT NULL;
ALTER TABLE c_quiz DROP PRIMARY KEY;
ALTER TABLE c_quiz ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY;

ALTER TABLE c_quiz_question MODIFY id INT NOT NULL;
ALTER TABLE c_quiz_question MODIFY c_id INT NOT NULL;
ALTER TABLE c_quiz_question DROP PRIMARY KEY;
ALTER TABLE c_quiz_question ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY;

ALTER TABLE c_quiz_answer MODIFY id INT NOT NULL;
ALTER TABLE c_quiz_answer MODIFY c_id INT NOT NULL;
ALTER TABLE c_quiz_answer MODIFY id_auto INT NOT NULL;
ALTER TABLE c_quiz_answer DROP PRIMARY KEY;
ALTER TABLE c_quiz_answer ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY;
ALTER TABLE c_quiz_answer ADD INDEX idx_cqa_qid (question_id);

ALTER TABLE c_quiz_question_option MODIFY id INT NOT NULL;
ALTER TABLE c_quiz_question_option MODIFY c_id INT NOT NULL;
ALTER TABLE c_quiz_question_option DROP PRIMARY KEY;
ALTER TABLE c_quiz_question_option ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY;

ALTER TABLE c_quiz_rel_question MODIFY id INT NOT NULL;
ALTER TABLE c_quiz_rel_question MODIFY question_id INT NOT NULL;
ALTER TABLE c_quiz_rel_question MODIFY exercice_id INT NOT NULL;
ALTER TABLE c_quiz_rel_question DROP PRIMARY KEY;
ALTER TABLE c_quiz_rel_question ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY;
ALTER TABLE c_quiz_rel_question ADD INDEX idx_cqrq_id (question_id);
ALTER TABLE c_quiz_rel_question ADD INDEX idx_cqrq_cidexid (c_id, exercice_id);

ALTER TABLE c_quiz_category MODIFY id INT NOT NULL;
ALTER TABLE c_quiz_category MODIFY c_id INT NOT NULL;
ALTER TABLE c_quiz_category DROP PRIMARY KEY;
ALTER TABLE c_quiz_category ADD COLUMN iid INT unsigned NOT NULL auto_increment PRIMARY KEY;

CREATE TABLE IF NOT EXISTS question_field (id  int NOT NULL auto_increment, field_type int NOT NULL default 1, field_variable varchar(64) NOT NULL, field_display_text  varchar(64), field_default_value text, field_order int, field_visible tinyint default 0, field_changeable tinyint default 0, field_filter tinyint default 0, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY(id));
CREATE TABLE IF NOT EXISTS question_field_options(id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms	DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS question_field_values( id  int NOT NULL auto_increment, question_id int NOT NULL, field_id int NOT NULL, field_value text, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY(id));

ALTER TABLE question_field_options ADD INDEX idx_question_field_options_field_id(field_id);
ALTER TABLE question_field_values ADD INDEX idx_question_field_values_question_id(question_id);
ALTER TABLE question_field_values ADD INDEX idx_question_field_values_field_id(field_id);

ALTER TABLE question_field_values ADD COLUMN user_id INT unsigned NOT NULL default 0;
ALTER TABLE session_field_values ADD COLUMN user_id INT unsigned NOT NULL default 0;
ALTER TABLE course_field_values ADD COLUMN user_id INT unsigned NOT NULL default 0;
ALTER TABLE user_field_values ADD COLUMN author_id INT unsigned NOT NULL default 0;

ALTER TABLE c_quiz_category ADD COLUMN lvl int;
ALTER TABLE c_quiz_category ADD COLUMN lft int;
ALTER TABLE c_quiz_category ADD COLUMN rgt int;
ALTER TABLE c_quiz_category ADD COLUMN root int;
ALTER TABLE c_quiz_category MODIFY COLUMN parent_id int default null;

ALTER TABLE track_e_course_access MODIFY COLUMN course_access_id bigint unsigned auto_increment;

CREATE TABLE extra_field_option_rel_field_option(id INT auto_increment, role_id INT, field_id INT, field_option_id INT, related_field_option_id INT, PRIMARY KEY(id));

CREATE TABLE ext_log_entries (id int(11) NOT NULL AUTO_INCREMENT, action varchar(255) DEFAULT NULL, logged_at datetime DEFAULT NULL, object_id varchar(64) DEFAULT NULL, object_class varchar(255) DEFAULT NULL, version int(11) DEFAULT NULL, data varchar(255) DEFAULT NULL, username varchar(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARSET=utf8;

ALTER TABLE user_field ADD COLUMN field_loggeable int default 0;
ALTER TABLE session_field ADD COLUMN field_loggeable int default 0;
ALTER TABLE course_field ADD COLUMN field_loggeable int default 0;
ALTER TABLE question_field ADD COLUMN field_loggeable int default 0;

ALTER TABLE user_field_values ADD COLUMN comment VARCHAR(100) default '';
ALTER TABLE session_field_values ADD COLUMN comment VARCHAR(100) default '';
ALTER TABLE course_field_values ADD COLUMN comment VARCHAR(100) default '';
ALTER TABLE question_field_values ADD COLUMN comment VARCHAR(100) default '';
ALTER TABLE c_quiz ADD COLUMN end_button int NOT NULL default 0;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('template', NULL, 'text', 'stylesheets', 'default', 'DefaultTemplateTitle', 'DefaultTemplateComment', NULL, NULL, 1);

ALTER TABLE user ADD COLUMN salt VARCHAR(255) DEFAULT NULL;
CREATE TABLE roles (id INT auto_increment, name VARCHAR(255), role VARCHAR(255) unique, PRIMARY KEY(id));
CREATE TABLE users_roles (user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(user_id, role_id));

INSERT INTO roles (name, role) VALUES('Admin', 'ROLE_ADMIN');
INSERT INTO roles (name, role) VALUES('Teacher', 'ROLE_TEACHER');
INSERT INTO roles (name, role) VALUES('Student', 'ROLE_STUDENT');
INSERT INTO roles (name, role) VALUES('Anonymous', 'ROLE_ANONYMOUS');
INSERT INTO roles (name, role) VALUES('RRHH', 'ROLE_RRHH');
INSERT INTO roles (name, role) VALUES('Question Manager', 'ROLE_QUESTION_MANAGER');

-- Admin
INSERT INTO users_roles VALUES (1, 1);

CREATE TABLE question_score_name (id int NOT NULL AUTO_INCREMENT,  score varchar(255) DEFAULT NULL,  name varchar(255) DEFAULT NULL,  description TEXT DEFAULT NULL,  question_score_id INT NOT NULL,  PRIMARY KEY (id)) DEFAULT CHARSET=utf8;
CREATE TABLE question_score (  id int NOT NULL AUTO_INCREMENT,  name varchar(255) DEFAULT NULL,  PRIMARY KEY (id)) DEFAULT CHARSET=utf8;



-- Add new configuration setting for action related transaction settings.
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('transaction_action_map','exercise_attempt','text','TransactionMapping','a:0:{}','TransactionMapForExerciseAttempts','TransactionMapForExerciseAttemptsComment',NULL,'TransactionMapForExerciseAttemptsText', 1);

-- Rename the transaction import log table and change its structure.
RENAME TABLE branch_sync_log TO branch_transaction_log;
ALTER TABLE branch_transaction_log CHANGE sync_trans_id transaction_id bigint unsigned not null default 0;
ALTER TABLE branch_transaction_log DROP branch_sync_id, DROP sync_type;
ALTER TABLE branch_transaction_log CHANGE sync_trans_date import_time DATETIME NULL DEFAULT NULL;
ALTER TABLE branch_transaction_log ADD message MEDIUMTEXT NOT NULL;

-- Remove orig_id in favor of item_id and delete info field.
ALTER TABLE branch_transaction DROP orig_id, DROP info;

-- Add missing fields to brach_sync table.
ALTER TABLE branch_sync ADD ssl_pub_key varchar(250) default '';
ALTER TABLE branch_sync ADD lft int unsigned;
ALTER TABLE branch_sync ADD rgt int unsigned;
ALTER TABLE branch_sync ADD lvl int unsigned;
ALTER TABLE branch_sync ADD root int unsigned;
ALTER TABLE branch_sync ADD parent_id int unsigned;
ALTER TABLE branch_sync ADD branch_type varchar(250) default null;

-- Do not move this
UPDATE settings_current SET selected_value = '1.10.0.029' WHERE variable = 'chamilo_database_version';
