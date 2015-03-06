-- This script updates the databases structure before migrating the data from
-- version 1.9.0 (or version 1.9.*) to version 1.10.0
-- it is intended as a standalone script, however, because of the multiple
-- databases related difficulties, it should be parsed by a PHP script in
-- order to connect to and update the right databases.
-- There is one line per query, allowing the PHP function file() to read
-- all lines separately into an array. The xxMAINxx-type markers are there
-- to tell the PHP script which database we're talking about.
-- By always using the keyword "TABLE" in the queries, we should be able
-- to retrieve and modify the table name from the PHP script if needed, which
-- will allow us to deal with the unique-database-type installations
--
-- This first part is for the main database

-- xxMAINxx
ALTER TABLE skill_rel_user ADD COLUMN course_id INT NOT NULL DEFAULT 0 AFTER id;
ALTER TABLE skill_rel_user ADD COLUMN session_id INT NOT NULL DEFAULT 0 AFTER course_id;
ALTER TABLE skill_rel_user ADD INDEX idx_select_cs (course_id, session_id);

CREATE TABLE IF NOT EXISTS hook_observer( id int UNSIGNED NOT NULL AUTO_INCREMENT, class_name varchar(255) UNIQUE, path varchar(255) NOT NULL, plugin_name varchar(255) NULL, PRIMARY KEY PK_hook_management_hook_observer(id));
CREATE TABLE IF NOT EXISTS hook_event( id int UNSIGNED NOT NULL AUTO_INCREMENT, class_name varchar(255) UNIQUE, description varchar(255), PRIMARY KEY PK_hook_management_hook_event(id));
CREATE TABLE IF NOT EXISTS hook_call( id int UNSIGNED NOT NULL AUTO_INCREMENT, hook_event_id int UNSIGNED NOT NULL, hook_observer_id int UNSIGNED NOT NULL, type tinyint NOT NULL, hook_order int UNSIGNED NOT NULL, enabled tinyint NOT NULL, PRIMARY KEY PK_hook_management_hook_call(id));

ALTER TABLE session ADD COLUMN description TEXT DEFAULT NULL;
ALTER TABLE session ADD COLUMN show_description TINYINT UNSIGNED DEFAULT 0 AFTER description;

ALTER TABLE session_rel_course ADD COLUMN position int NOT NULL default 0;
ALTER TABLE session_rel_course ADD COLUMN category varchar(255) default '';
ALTER TABLE session ADD COLUMN duration int;
ALTER TABLE session_rel_user ADD COLUMN duration int;

CREATE TABLE course_field_options (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, field_id INT NOT NULL, option_value TEXT, option_display_text VARCHAR(64), option_order INT, tms DATETIME);
CREATE TABLE session_field_options (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, field_id INT NOT NULL, option_value TEXT, option_display_text VARCHAR(64), option_order INT, tms DATETIME);

ALTER TABLE skill ADD COLUMN criteria text DEFAULT '';

ALTER TABLE gradebook_category ADD COLUMN generate_certificates TINYINT NOT NULL DEFAULT 0;

RENAME TABLE track_e_exercices TO track_e_exercises;

ALTER TABLE track_e_access ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_default ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_lastaccess ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_exercises ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_downloads ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_hotpotatoes ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_links ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_course_access ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_online ADD COLUMN c_id int NOT NULL;
ALTER TABLE track_e_attempt ADD COLUMN c_id int NOT NULL;

DELETE FROM settings_current WHERE variable = 'wcag_anysurfer_public_pages';
DELETE FROM settings_options WHERE variable = 'wcag_anysurfer_public_pages';
DELETE FROM settings_current WHERE variable = 'advanced_filemanager';
DELETE FROM settings_options WHERE variable = 'advanced_filemanager';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('prevent_session_admins_to_manage_all_users', NULL, 'radio', 'Session', 'false', 'PreventSessionAdminsToManageAllUsersTitle', 'PreventSessionAdminsToManageAllUsersComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('prevent_session_admins_to_manage_all_users', 'true', 'Yes'), ('prevent_session_admins_to_manage_all_users', 'false', 'No');

ALTER TABLE track_e_access ADD COLUMN user_ip varchar(39) NOT NULL default '';
ALTER TABLE track_e_exercises ADD COLUMN user_ip varchar(39) NOT NULL default '';
ALTER TABLE track_e_course_access ADD COLUMN user_ip varchar(39) NOT NULL default '';
ALTER TABLE track_e_online CHANGE COLUMN login_ip user_ip varchar(39) NOT NULL DEFAULT '';
ALTER TABLE track_e_login CHANGE COLUMN login_ip user_ip varchar(39) NOT NULL DEFAULT '';

ALTER TABLE user MODIFY COLUMN user_id int unsigned DEFAULT null;
ALTER TABLE user DROP PRIMARY KEY;
ALTER TABLE user ADD COLUMN id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT AFTER user_id;
UPDATE user SET id = user_id;
UPDATE user SET registration_date = NULL WHERE registration_date = '0000-00-00 00:00:00';
UPDATE user SET expiration_date = NULL WHERE expiration_date = '0000-00-00 00:00:00';

ALTER TABLE user MODIFY COLUMN chatcall_date datetime default NULL;
ALTER TABLE user MODIFY COLUMN chatcall_text varchar(50) default NULL;
ALTER TABLE user MODIFY COLUMN chatcall_user_id int unsigned default '0';

ALTER TABLE user MODIFY COLUMN expiration_date datetime default NULL;
ALTER TABLE user MODIFY COLUMN registration_date datetime NOT NULL;

DELETE FROM settings_options WHERE variable = 'show_glossary_in_extra_tools';

INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'none', 'None');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise', 'Exercise');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'lp', 'Learning path');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise_and_lp', 'ExerciseAndLearningPath');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('documents_default_visibility_defined_in_course', NULL,'radio','Tools','false','DocumentsDefaultVisibilityDefinedInCourseTitle','DocumentsDefaultVisibilityDefinedInCourseComment',NULL, NULL, 1);

INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('documents_default_visibility_defined_in_course', 'false', 'No');

-- Do not move this query
UPDATE settings_current SET selected_value = '1.10.0.17' WHERE variable = 'chamilo_database_version';

-- xxCOURSExx

ALTER TABLE c_survey ADD visible_results INT UNSIGNED DEFAULT 0;
ALTER TABLE c_lp_item ADD COLUMN prerequisite_min_score float;
ALTER TABLE c_lp_item ADD COLUMN prerequisite_max_score float;
ALTER TABLE c_student_publication ADD COLUMN document_id int DEFAULT 0;

CREATE TABLE IF NOT EXISTS c_student_publication_rel_document (id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT, work_id INT NOT NULL, document_id INT NOT NULL, c_id INT NOT NULL);
CREATE TABLE IF NOT EXISTS c_student_publication_rel_user ( id  INT PRIMARY KEY NOT NULL AUTO_INCREMENT, work_id INT NOT NULL, user_id INT NOT NULL, c_id INT NOT NULL);
CREATE TABLE IF NOT EXISTS c_student_publication_comment ( id INT PRIMARY KEY NOT NULL AUTO_INCREMENT, work_id INT NOT NULL, c_id INT NOT NULL, comment text, file VARCHAR(255), user_id int NOT NULL, sent_at datetime NOT NULL);

