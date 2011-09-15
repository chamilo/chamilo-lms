-- This script updates the databases structure before migrating the data from
-- version 1.8.8 (or version 1.8.8.2, 1.8.8.4) to version 1.9.0
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
UPDATE settings_current SET selected_value = '1.9.0.15605' WHERE variable = 'chamilo_database_version';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('filter_terms', NULL, 'textarea', 'Security', '', 'FilterTermsTitle', 'FilterTermsComment', NULL, NULL, 0);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('header_extra_content', NULL, 'textarea', 'Tracking', '', 'HeaderExtraContentTitle', 'HeaderExtraContentComment', NULL, NULL, 1);

ALTER TABLE personal_agenda ADD COLUMN all_day INTEGER NOT NULL DEFAULT 0;
ALTER TABLE sys_calendar ADD COLUMN all_day INTEGER NOT NULL DEFAULT 0;

-- xxSTATSxx
ALTER TABLE track_e_exercices ADD COLUMN questions_to_check TEXT  NOT NULL DEFAULT '';
--CREATE TABLE track_filtered_terms (id int, user_id int, course_id int, session_id int, tool_id char(12), filtered_term varchar(255), created_at datetime);

-- xxUSERxx

-- xxCOURSExx

