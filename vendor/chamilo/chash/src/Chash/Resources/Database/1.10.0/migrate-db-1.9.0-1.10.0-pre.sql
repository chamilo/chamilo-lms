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

DROP TABLE IF EXISTS php_session;
CREATE TABLE IF NOT EXISTS php_session(session_id varchar(255) NOT NULL, session_value LONGTEXT NOT NULL, session_time int NOT NULL, PRIMARY KEY (session_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS session_field_options (id int unsigned NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS course_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS c_quiz_order( iid bigint unsigned NOT NULL auto_increment, c_id int unsigned NOT NULL, session_id int unsigned NOT NULL, exercise_id int NOT NULL, exercise_order INT NOT NULL, PRIMARY KEY (iid));
CREATE TABLE IF NOT EXISTS c_student_publication_rel_document (id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,    work_id INT NOT NULL,    document_id INT NOT NULL,    c_id INT NOT NULL);
CREATE TABLE IF NOT EXISTS c_student_publication_rel_user (id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,    work_id INT NOT NULL,    user_id INT NOT NULL,    c_id INT NOT NULL);
CREATE TABLE IF NOT EXISTS c_student_publication_comment (id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,  work_id INT NOT NULL,  c_id INT NOT NULL,  comment text,  user_id int NOT NULL,  sent_at datetime NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS gradebook_evaluation_type(id INT unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT, name varchar(255), external_id INT unsigned NOT NULL DEFAULT 0);
CREATE TABLE IF NOT EXISTS question_field (id  int NOT NULL auto_increment, field_type int NOT NULL default 1, field_variable varchar(64) NOT NULL, field_display_text  varchar(64), field_default_value text, field_order int, field_visible tinyint default 0, field_changeable tinyint default 0, field_filter tinyint default 0, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY(id));
CREATE TABLE IF NOT EXISTS question_field_options(id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS question_field_values( id  int NOT NULL auto_increment, question_id int NOT NULL, field_id int NOT NULL, field_value text, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY(id));
CREATE TABLE IF NOT EXISTS question_score_name (id int NOT NULL AUTO_INCREMENT,  score varchar(255) DEFAULT NULL,  name varchar(255) DEFAULT NULL,  description TEXT DEFAULT NULL,  question_score_id INT NOT NULL,  PRIMARY KEY (id)) DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS question_score (  id int NOT NULL AUTO_INCREMENT,  name varchar(255) DEFAULT NULL,  PRIMARY KEY (id)) DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS c_lp_category (id int unsigned NOT NULL auto_increment, c_id INT unsigned NOT NULL, name VARCHAR(255), position INT, PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS c_quiz_rel_category (iid bigint unsigned NOT NULL auto_increment, c_id INT unsigned default 0, category_id int unsigned NOT NULL, exercise_id int unsigned NOT NULL, count_questions int NOT NULL default 0, PRIMARY KEY(iid));
CREATE TABLE IF NOT EXISTS extra_field_option_rel_field_option(id INT auto_increment, role_id INT, field_id INT, field_option_id INT, related_field_option_id INT, PRIMARY KEY(id));
CREATE TABLE IF NOT EXISTS ext_log_entries (id int(11) NOT NULL AUTO_INCREMENT, action varchar(255) DEFAULT NULL, logged_at datetime DEFAULT NULL, object_id varchar(64) DEFAULT NULL, object_class varchar(255) DEFAULT NULL, version int(11) DEFAULT NULL, data varchar(255) DEFAULT NULL, username varchar(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS roles (id INT auto_increment, name VARCHAR(255), role VARCHAR(255) unique, PRIMARY KEY(id));
CREATE TABLE IF NOT EXISTS users_roles (user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(user_id, role_id));
CREATE TABLE IF NOT EXISTS usergroup_rel_tag( id int NOT NULL AUTO_INCREMENT, tag_id int NOT NULL, usergroup_id int NOT NULL, PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS usergroup_rel_usergroup (id int NOT NULL AUTO_INCREMENT, group_id int NOT NULL, subgroup_id int NOT NULL, relation_type int NOT NULL, PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS branch_sync( id int unsigned not null AUTO_INCREMENT PRIMARY KEY, access_url_id int unsigned not null, branch_name varchar(250) default '', branch_ip varchar(40) default '', latitude decimal(15,7), longitude decimal(15,7), dwn_speed int unsigned default null, up_speed int unsigned default null, delay int unsigned default null, admin_mail varchar(250) default '', admin_name varchar(250) default '', admin_phone varchar(250) default '', last_sync_trans_id bigint unsigned default 0, last_sync_trans_date datetime, last_sync_type char(20) default 'full');
CREATE TABLE IF NOT EXISTS branch_sync_log( id bigint unsigned not null AUTO_INCREMENT PRIMARY KEY, branch_sync_id int unsigned not null, sync_trans_id bigint unsigned default 0, sync_trans_date datetime, sync_type char(20));
CREATE TABLE IF NOT EXISTS branch_transaction_status (id tinyint not null PRIMARY KEY AUTO_INCREMENT,  title char(20));
CREATE TABLE IF NOT EXISTS branch_transaction (id bigint unsigned not null AUTO_INCREMENT, transaction_id bigint unsigned, branch_id int unsigned not null default 0,  action char(20),  item_id char(36),  orig_id char(36),  dest_id char(36),  info char(20), status_id tinyint not null default 0,  time_insert datetime NOT NULL DEFAULT '0000-00-00 00:00:00',  time_update datetime NOT NULL DEFAULT '0000-00-00 00:00:00', message VARCHAR(255) default '' , PRIMARY KEY (id, transaction_id, branch_id));
CREATE TABLE IF NOT EXISTS access_url_rel_usergroup (access_url_id int unsigned NOT NULL, usergroup_id int unsigned NOT NULL, PRIMARY KEY (access_url_id, usergroup_id));
CREATE TABLE IF NOT EXISTS access_url_rel_course_category (access_url_id int unsigned NOT NULL, course_category_id int unsigned NOT NULL, PRIMARY KEY (access_url_id, course_category_id));

-- ALTER TABLE c_lp_item ADD INDEX idx_c_lp_item_cid_lp_id (c_id, lp_id);
-- ALTER TABLE c_lp_item_view ADD INDEX idx_c_lp_item_view_cid_lp_view_id_lp_item_id(c_id, lp_view_id, lp_item_id);

ALTER TABLE c_student_publication ADD COLUMN filename varchar(255) DEFAULT NULL;
ALTER TABLE course ADD COLUMN add_teachers_to_sessions_courses tinyint NOT NULL default 0;

ALTER TABLE c_tool_intro MODIFY COLUMN intro_text MEDIUMTEXT NOT NULL;
ALTER TABLE user MODIFY COLUMN hr_dept_id int unsigned default 0;
ALTER TABLE session MODIFY COLUMN nbr_courses int unsigned NOT NULL default 0;
ALTER TABLE session MODIFY COLUMN nbr_users int unsigned NOT NULL default 0;
ALTER TABLE session MODIFY COLUMN nbr_classes int unsigned NOT NULL default 0;
ALTER TABLE session_rel_course MODIFY COLUMN nbr_users int unsigned NOT NULL default 0;
ALTER TABLE track_e_exercices MODIFY COLUMN session_id int unsigned NOT NULL default 0;
ALTER TABLE track_e_exercices MODIFY COLUMN exe_exo_id int unsigned NOT NULL default 0;

ALTER TABLE track_e_default MODIFY COLUMN default_event_type VARCHAR(255);
ALTER TABLE track_e_default MODIFY COLUMN default_value_type VARCHAR(255);
ALTER TABLE usergroup ADD COLUMN allow_members_leave_group int NOT NULL DEFAULT 1;

ALTER TABLE c_quiz_answer MODIFY COLUMN answer longtext NOT NULL;
ALTER TABLE c_quiz_answer MODIFY COLUMN comment longtext;
ALTER TABLE c_announcement MODIFY COLUMN content longtext;
ALTER TABLE c_attendance MODIFY COLUMN description longtext;
ALTER TABLE c_calendar_event MODIFY COLUMN content longtext;
ALTER TABLE c_blog_comment MODIFY COLUMN comment longtext NOT NULL;
ALTER TABLE c_course_description MODIFY COLUMN content longtext;
ALTER TABLE c_forum_forum MODIFY COLUMN forum_comment longtext;
ALTER TABLE c_forum_post MODIFY COLUMN post_text longtext;
ALTER TABLE c_glossary MODIFY COLUMN description longtext NOT NULL;
ALTER TABLE c_group_category MODIFY COLUMN description longtext NOT NULL;
ALTER TABLE c_group_info MODIFY COLUMN description longtext;
ALTER TABLE c_lp MODIFY COLUMN description longtext;
ALTER TABLE c_notebook MODIFY COLUMN description longtext NOT NULL;
ALTER TABLE c_quiz MODIFY COLUMN description longtext;
ALTER TABLE c_quiz MODIFY COLUMN text_when_finished longtext;
ALTER TABLE c_quiz_question MODIFY COLUMN question longtext NOT NULL;
ALTER TABLE c_quiz_question MODIFY COLUMN description longtext;
ALTER TABLE c_quiz_category MODIFY COLUMN description longtext NOT NULL;
ALTER TABLE c_student_publication MODIFY COLUMN description longtext;
ALTER TABLE c_survey MODIFY COLUMN intro longtext;
ALTER TABLE c_survey_question MODIFY COLUMN survey_question_comment longtext NOT NULL;
ALTER TABLE c_survey_question MODIFY COLUMN survey_question longtext NOT NULL;
ALTER TABLE c_thematic MODIFY COLUMN content longtext;
ALTER TABLE c_thematic_advance MODIFY COLUMN content longtext;
ALTER TABLE c_thematic_plan MODIFY COLUMN description longtext;
ALTER TABLE c_tool_intro MODIFY COLUMN intro_text longtext NOT NULL;
ALTER TABLE c_wiki MODIFY COLUMN content longtext NOT NULL;
ALTER TABLE c_student_publication_comment MODIFY COLUMN comment longtext;

ALTER TABLE sys_announcement MODIFY COLUMN content longtext NOT NULL;
ALTER TABLE shared_survey MODIFY COLUMN intro longtext;
ALTER TABLE shared_survey_question MODIFY COLUMN survey_question longtext NOT NULL;
ALTER TABLE shared_survey_question_option MODIFY COLUMN option_text longtext NOT NULL;
ALTER TABLE sys_calendar MODIFY COLUMN content longtext;
ALTER TABLE system_template MODIFY COLUMN content longtext NOT NULL;
ALTER TABLE message MODIFY COLUMN content longtext NOT NULL;
ALTER TABLE track_e_attempt MODIFY COLUMN answer longtext NOT NULL;
ALTER TABLE track_e_attempt_recording MODIFY COLUMN teacher_comment longtext NOT NULL;
ALTER TABLE personal_agenda MODIFY COLUMN `text` longtext;

ALTER TABLE c_tool ADD COLUMN custom_icon varchar(255) DEFAULT NULL;
ALTER TABLE c_tool ADD COLUMN description text DEFAULT NULL;

TRUNCATE roles;
INSERT INTO roles (id, name, role) VALUES('1', 'Teacher', 'ROLE_TEACHER');
INSERT INTO roles (id, name, role) VALUES('4', 'RRHH', 'ROLE_RRHH');
INSERT INTO roles (id, name, role) VALUES('3', 'Session Manager', 'ROLE_SESSION_MANAGER');
INSERT INTO roles (id ,name, role) VALUES('5', 'Student', 'ROLE_STUDENT');
INSERT INTO roles (id, name, role) VALUES('6', 'Anonymous', 'ROLE_ANONYMOUS');
INSERT INTO roles (id, name, role) VALUES('11', 'Admin', 'ROLE_ADMIN');
INSERT INTO roles (id, name, role) VALUES('17', 'Question Manager', 'ROLE_QUESTION_MANAGER');
INSERT INTO roles (id, name, role) VALUES('18', 'Global admin', 'ROLE_GLOBAL_ADMIN');

-- Admin
TRUNCATE users_roles;
INSERT INTO users_roles VALUES (1, 11);

DELETE FROM settings_current WHERE variable = 'session_tutor_reports_visibility';
DELETE FROM settings_options WHERE variable = 'session_tutor_reports_visibility';

DELETE FROM settings_current WHERE variable = 'gradebook_show_percentage_in_reports';
DELETE FROM settings_options WHERE variable = 'gradebook_show_percentage_in_reports';

DELETE FROM settings_current WHERE variable = 'login_as_allowed';
DELETE FROM settings_options WHERE variable = 'login_as_allowed';

DELETE FROM settings_current WHERE variable = 'admins_can_set_users_pass';
DELETE FROM settings_options WHERE variable = 'admins_can_set_users_pass';

DELETE FROM settings_current WHERE variable = 'session_page_enabled';
DELETE FROM settings_options WHERE variable = 'session_page_enabled';

DELETE FROM settings_current WHERE variable = 'settings_latest_update';
DELETE FROM settings_options WHERE variable = 'settings_latest_update';

DELETE FROM settings_current WHERE variable = 'user_name_order';
DELETE FROM settings_current WHERE variable = 'user_name_sort_by';

DELETE FROM settings_current WHERE variable = 'allow_teachers_to_create_sessions';
DELETE FROM settings_options WHERE variable = 'allow_teachers_to_create_sessions';

DELETE FROM settings_current WHERE variable = 'template';

DELETE FROM settings_current WHERE variable = 'use_virtual_keyboard';
DELETE FROM settings_options WHERE variable = 'use_virtual_keyboard';

DELETE FROM settings_current WHERE variable = 'breadcrumb_navigation_display';
DELETE FROM settings_options WHERE variable = 'breadcrumb_navigation_display';

DELETE FROM settings_current WHERE variable = 'default_calendar_view';
DELETE FROM settings_options WHERE variable = 'default_calendar_view';

DELETE FROM settings_current WHERE variable = 'disable_copy_paste';
DELETE FROM settings_options WHERE variable = 'disable_copy_paste';
DELETE FROM settings_current WHERE variable = 'showonline';

DELETE FROM settings_options WHERE variable = 'last_transaction_id';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('session_tutor_reports_visibility', NULL, 'radio', 'Session', 'true', 'SessionTutorsCanSeeExpiredSessionsResultsTitle', 'SessionTutorsCanSeeExpiredSessionsResultsComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_show_percentage_in_reports', NULL,'radio','Gradebook','true','GradebookShowPercentageInReportsTitle','GradebookShowPercentageInReportsComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('login_as_allowed', NULL, 'radio', 'security', 'true','AdminLoginAsAllowedTitle', 'AdminLoginAsAllowedComment', 1, 0, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('admins_can_set_users_pass', NULL, 'radio', 'security', 'true', 'AdminsCanChangeUsersPassTitle', 'AdminsCanChangeUsersPassComment', 1, 0, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('session_page_enabled', NULL, 'radio', 'Session', 'true', 'SessionPageEnabledTitle', 'SessionPageEnabledComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('settings_latest_update', NULL, NULL, NULL, '', '','', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('user_name_order', NULL, 'textfield', 'Platform', '', 'UserNameOrderTitle', 'UserNameOrderComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('user_name_sort_by', NULL, 'textfield', 'Platform', '', 'UserNameSortByTitle', 'UserNameSortByComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_teachers_to_create_sessions', NULL,'radio','Session','false','AllowTeachersToCreateSessionsTitle','AllowTeachersToCreateSessionsComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('template', NULL, 'text', 'stylesheets', 'default', 'DefaultTemplateTitle', 'DefaultTemplateComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('breadcrumb_navigation_display', NULL, 'radio', 'Platform','true','BreadcrumbNavigationDisplayTitle', 'BreadcrumbNavigationDisplayComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('use_virtual_keyboard', NULL, 'radio', 'Platform', 'false','ShowVirtualKeyboardTitle','ShowVirtualKeyboardComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('default_calendar_view', NULL, 'radio', 'Platform','month','DefaultCalendarViewTitle', 'DefaultCalendarViewComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('disable_copy_paste', NULL, 'radio', 'Platform', 'false','DisableCopyPasteTitle','DisableCopyPasteComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('showonline','session','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineSession', 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('documents_default_visibility_defined_in_course', NULL,'radio','Tools','false','DocumentsDefaultVisibilityDefinedInCourseTitle','DocumentsDefaultVisibilityDefinedInCourseComment',NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_personal_user_files', NULL,'radio','Tools','false','AllowPersonalUserFilesTitle','AllowPersonalUserFilesComment',NULL, NULL, 1);

INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'false', 'No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_show_percentage_in_reports', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_show_percentage_in_reports', 'false', 'No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('login_as_allowed','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('login_as_allowed','false','No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('admins_can_set_users_pass','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('admins_can_set_users_pass','false','No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('last_transaction_id', '0', '');
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_page_enabled', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_page_enabled', 'false', 'No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'false', 'No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('breadcrumb_navigation_display', 'true', 'Show');
INSERT INTO settings_options (variable, value, display_text) VALUES ('breadcrumb_navigation_display', 'false', 'Hide');
INSERT INTO settings_options (variable, value, display_text) VALUES ('use_virtual_keyboard', 'true', 'Show');
INSERT INTO settings_options (variable, value, display_text) VALUES ('use_virtual_keyboard', 'false', 'Hide');
INSERT INTO settings_options (variable, value, display_text) VALUES ('disable_copy_paste', 'true', 'Show');
INSERT INTO settings_options (variable, value, display_text) VALUES ('disable_copy_paste', 'false', 'Hide');
INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'month', 'Month');
INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'basicWeek', 'BasicWeek');
INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'agendaWeek', 'Week');
INSERT INTO settings_options (variable, value, display_text) VALUES ('default_calendar_view', 'agendaDay', 'Day');

INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'false', 'No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_personal_user_files', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_personal_user_files', 'false', 'No');

TRUNCATE branch_transaction_status;
INSERT INTO branch_transaction_status VALUES (1, 'To be executed'), (2, 'Executed successfully'), (3, 'Execution deprecated'), (4, 'Execution failed');

UPDATE course_field SET field_type = 3 WHERE field_variable = 'special_course';

-- Do not move this
UPDATE settings_current SET selected_value = '1.10.0.056' WHERE variable = 'chamilo_database_version';
