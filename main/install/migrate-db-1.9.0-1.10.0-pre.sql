-- This script updates the databases structure before migrating the data from
-- version 1.9.0 (or version 1.9.2, 1.9.4) to version 1.10.0
-- it is intended as a standalone script, however, because of the multiple
-- databases related difficulties, it should be parsed by a PHP script in
-- order to connect to and update the right databases.
-- There is one line per query, allowing the PHP function file() to read
-- all lines separately into an array. The xxMAINxx-type markers are there
-- to tell the PHP script which database we're talking about.
-- By always using the keyword "TABLE" in the queries, we should be able
-- to retrieve and modify the table name from the PHP script if needed, which
-- will allow us to deal with the unique-database-type installations

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

ALTER TABLE session ADD COLUMN display_start_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN display_end_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN access_start_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN access_end_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN coach_access_start_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE session ADD COLUMN coach_access_end_date datetime NOT NULL default '0000-00-00 00:00:00';

ALTER TABLE grade_components ADD COLUMN prefix VARCHAR(255) DEFAULT NULL;
ALTER TABLE grade_components ADD COLUMN exclusions INT DEFAULT 0;
ALTER TABLE grade_components ADD COLUMN count_elements INT DEFAULT 0;

CREATE TABLE IF NOT EXISTS session_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS course_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));

ALTER TABLE session_field_options ADD INDEX idx_session_field_options_field_id(field_id);
ALTER TABLE session_field_values ADD INDEX idx_session_field_values_session_id(session_id);
ALTER TABLE session_field_values ADD INDEX idx_session_field_values_field_id(field_id);

ALTER TABLE session MODIFY COLUMN name CHAR(150) NOT NULL DEFAULT '';
ALTER TABLE session MODIFY COLUMN id MEDIUMINT unsigned NOT NULL;

ALTER TABLE session_rel_course MODIFY COLUMN id_session MEDIUMINT unsigned NOT NULL;
ALTER TABLE session_rel_course ADD COLUMN course_id INT NOT NULL DEFAULT '0';
ALTER TABLE session_rel_course ADD INDEX idx_session_rel_course_course_id (course_id);
ALTER TABLE session_rel_course DROP PRIMARY KEY;
ALTER TABLE session_rel_course ADD PRIMARY KEY (id_session, course_id);

ALTER TABLE session_rel_course_rel_user MODIFY COLUMN id_session MEDIUMINT unsigned NOT NULL;
ALTER TABLE session_rel_course_rel_user ADD COLUMN course_id INT NOT NULL DEFAULT '0';
ALTER TABLE session_rel_course_rel_user DROP PRIMARY KEY;
ALTER TABLE session_rel_course_rel_user ADD PRIMARY KEY (id_session, course_id, id_user);

ALTER TABLE session_rel_course_rel_user ADD INDEX idx_session_rel_course_rel_user_id_user (id_user);
ALTER TABLE session_rel_course_rel_user ADD INDEX idx_session_rel_course_rel_user_course_id (course_id);

-- Courses changes c_XXX

ALTER TABLE c_lp_item ADD INDEX idx_c_lp_item_cid_lp_id (c_id, lp_id);
ALTER TABLE c_lp_item_view ADD INDEX idx_c_lp_item_view_cid_lp_view_id_lp_item_id(c_id, lp_view_id, lp_item_id);
ALTER TABLE c_item_property ADD INDEX idx_itemprop_id_tool (c_id, tool(8));
ALTER TABLE c_tool_intro MODIFY COLUMN intro_text MEDIUMTEXT NOT NULL;

ALTER TABLE c_quiz_question_rel_category ADD COLUMN id int unsigned NOT NULL;
ALTER TABLE c_quiz_question_rel_category DROP PRIMARY KEY;
ALTER TABLE c_quiz_question_rel_category ADD PRIMARY KEY (id, c_id, question_id);
ALTER TABLE c_quiz_question_rel_category MODIFY COLUMN id int unsigned AUTO_INCREMENT;

ALTER TABLE session ADD INDEX idx_id_coach (id_coach);
ALTER TABLE session ADD INDEX idx_id_session_admin_id (session_admin_id);

ALTER TABLE c_quiz_question ADD COLUMN parent_id INT unsigned NOT NULL DEFAULT 0;

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

ALTER TABLE c_quiz_answer ADD INDEX idx_quiz_answer_c_q (c_id, question_id);
ALTER TABLE settings_current ADD INDEX idx_settings_current_au_cat (access_url, category(5));

CREATE TABLE c_quiz_order( id int unsigned NOT NULL auto_increment, c_id int unsigned NOT NULL, session_id int unsigned NOT NULL, exercise_id int NOT NULL, exercise_order INT NOT NULL, PRIMARY KEY (id));

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

ALTER TABLE c_item_property MODIFY COLUMN start_visible datetime default NULL;
ALTER TABLE c_item_property MODIFY COLUMN end_visible datetime default NULL;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('user_name_order', NULL, 'textfield', 'Platform', '', 'UserNameOrderTitle', 'UserNameOrderComment', NULL, NULL, 1);

-- Do not move this
UPDATE settings_current SET selected_value = '1.10.0.21566' WHERE variable = 'chamilo_database_version';