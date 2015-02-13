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

-- Do not move this query
UPDATE settings_current SET selected_value = '1.10.0.7' WHERE variable = 'chamilo_database_version';

-- xxCOURSExx

ALTER TABLE c_survey ADD visible_results INT UNSIGNED DEFAULT 0;

