-- This script updates the databases structure before migrating the data from
-- version 1.9.0 (or version 1.9.2) to version 1.10.0
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

-- Optimize tracking query very often queried on busy campuses
ALTER TABLE track_e_online ADD INDEX idx_trackonline_uat (login_user_id, access_url_id, login_date);
ALTER TABLE track_e_default ADD COLUMN session_id INT NOT NULL DEFAULT 0;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('session_tutor_reports_visibility', NULL, 'radio', 'Session', 'true', 'SessionTutorsCanSeeExpiredSessionsResultsTitle', 'SessionTutorsCanSeeExpiredSessionsResultsComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('session_tutor_reports_visibility', 'false', 'No');

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

-- ALTER TABLE session DROP COLUMN date_start;
-- ALTER TABLE session DROP COLUMN date_end;

CREATE TABLE IF NOT EXISTS session_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS course_field_options (id int NOT NULL auto_increment, field_id int NOT NULL, option_value text, option_display_text varchar(255), option_order int, tms DATETIME NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (id));

-- Courses changes c_XXX

ALTER TABLE c_lp_item ADD INDEX idx_c_lp_item_cid_lp_id (c_id, lp_id);
ALTER TABLE c_lp_item_view ADD INDEX idx_c_lp_item_view_cid_lp_view_id_lp_item_id(c_id, lp_view_id, lp_item_id);
ALTER TABLE c_item_property ADD INDEX idx_itemprop_id_tool (c_id, tool(8));
ALTER TABLE c_tool_intro MODIFY COLUMN intro_text MEDIUMTEXT NOT NULL;

-- Do not move this 
UPDATE settings_current SET selected_value = '1.10.0.19730' WHERE variable = 'chamilo_database_version';

-- xxSTATSxx
-- All stats DB changes will be added in the "main zone"

-- xxUSERxx
-- All user DB changes will be added in the "main zone"

-- xxCOURSExx
-- All course DB changes will be added in the "main zone"