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
ALTER TABLE settings_current ADD INDEX unique_setting (variable,subkey,category);
ALTER TABLE settings_options ADD INDEX unique_setting_option (variable,value);
INSERT INTO settings_current (variable, subkey,type,category,selected_value,title,comment,scope,subkeytext)VALUES ('registration', 'phone', 'textfield', 'User', 'false', 'RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment', NULL, 'Phone');
ALTER TABLE php_session CHANGE session_value session_value MEDIUMTEXT NOT NULL;
INSERT INTO settings_current (variable, subkey,type,category,selected_value,title,comment,scope,subkeytext)VALUES ('add_users_by_coach',NULL,'radio','Platform','false','AddUsersByCoachTitle','AddUsersByCoachComment',NULL,NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('add_users_by_coach', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('add_users_by_coach', 'false', 'No');
ALTER TABLE session ADD nb_days_access_before_beginning TINYINT NULL DEFAULT '0' AFTER date_end , ADD nb_days_access_after_end TINYINT NULL DEFAULT '0' AFTER nb_days_access_before_beginning ;

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE lp ADD theme varchar(255) not null default '';
ALTER TABLE survey ADD mail_subject VARCHAR( 255 ) NOT NULL AFTER reminder_mail ;
ALTER TABLE quiz_rel_question ADD question_order mediumint unsigned NOT NULL default 1;
ALTER TABLE quiz ADD max_attempt int NOT NULL default 0;
ALTER TABLE survey ADD one_question_per_page bool NOT NULL default 0;
ALTER TABLE survey ADD shuffle bool NOT NULL default 0;
ALTER TABLE survey ADD survey_version varchar(255) NOT NULL default '';
ALTER TABLE survey ADD parent_id int NOT NULL default 0;
ALTER TABLE survey ADD survey_type int NOT NULL default 0;
ALTER TABLE survey_question ADD survey_group_pri int unsigned NOT NULL default 0;
ALTER TABLE survey_question ADD survey_group_sec1 int unsigned NOT NULL default 0;
ALTER TABLE survey_question ADD survey_group_sec2 int unsigned NOT NULL default 0;
CREATE TABLE survey_group (  id int unsigned NOT NULL auto_increment, name varchar(20) NOT NULL, description varchar(255) NOT NULL,  survey_id int unsigned NOT NULL, PRIMARY KEY  (id) );
ALTER TABLE survey_question_option ADD value int NOT NULL default 0;
UPDATE tool SET category = 'interaction' WHERE name = 'forum';
