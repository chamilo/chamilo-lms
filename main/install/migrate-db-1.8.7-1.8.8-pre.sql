-- This script updates the databases structure before migrating the data from
-- version 1.8.7 (or 1.8.7.1) to version 1.8.8
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
ALTER TABLE settings_current DROP INDEX unique_setting;
ALTER TABLE settings_options DROP INDEX unique_setting_option;
ALTER TABLE settings_current ADD UNIQUE unique_setting (variable(110), subkey(110), category(110), access_url);
ALTER TABLE settings_options ADD UNIQUE unique_setting_option (variable(165), value(165));
ALTER TABLE settings_current CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE settings_options CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE user MODIFY COLUMN username VARCHAR(40) NOT NULL;

UPDATE settings_current SET variable='chamilo_database_version' WHERE variable='dokeos_database_version';
UPDATE settings_current SET selected_value = '1.8.8.12378' WHERE variable = 'chamilo_database_version';

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE course_setting CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE forum_forum ADD start_time DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE forum_forum ADD end_time DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE wiki_mailcue ADD session_id smallint DEFAULT 0;