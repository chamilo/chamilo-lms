-- This script updates the databases structure before migrating the data from
-- version 1.8.5 to version 1.8.6
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
INSERT INTO settings_current (variable, subkey,type,category,selected_value,title,comment,scope,subkeytext)VALUES ('registration', 'phone', 'textfield', 'User', 'false', 'RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment', NULL, 'Phone');
ALTER TABLE php_session CHANGE session_value session_value MEDIUMTEXT NOT NULL;
INSERT INTO settings_current (variable, subkey,type,category,selected_value,title,comment,scope,subkeytext)VALUES ('add_users_by_coach',NULL,'radio','Platform','false','AddUsersByCoachTitle','AddUsersByCoachComment',NULL,NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('add_users_by_coach', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('add_users_by_coach', 'false', 'No');

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE lp ADD theme varchar(255) not null default '';
ALTER TABLE survey ADD mail_subject VARCHAR( 255 ) NOT NULL AFTER reminder_mail ;
ALTER TABLE quiz_rel_question ADD question_order mediumint unsigned NOT NULL default 1;
ALTER TABLE quiz ADD max_attempt int NOT NULL default 0;
