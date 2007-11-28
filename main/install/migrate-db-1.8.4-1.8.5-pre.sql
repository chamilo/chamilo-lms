-- This script updates the databases structure before migrating the data from
-- version 1.8.4 to version 1.8.5
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
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'mycomptetences', 'checkbox', 'User', 'false', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyCompetences');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'mydiplomas', 'checkbox', 'User', 'false', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyDiplomas');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'myteach', 'checkbox', 'User', 'false', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyTeach');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'mypersonalopenarea', 'checkbox', 'false', 'true', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyPersonalOpenArea');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'mycomptetences', 'checkbox', 'false', 'true', 'ExtendedProfileRegistrationRequiredTitle', 'ExtendedProfileRegistrationRequiredComment', NULL, 'MyCompetences');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'mydiplomas', 'checkbox', 'false', 'true', 'ExtendedProfileRegistrationRequiredTitle', ExtendedProfileRegistrationRequiredComment', NULL, 'MyDiplomas');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'myteach', 'checkbox', 'false', 'true', 'ExtendedProfileRegistrationRequiredTitle', ExtendedProfileRegistrationRequiredComment', NULL, 'MyTeach');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'mypersonalopenarea', 'checkbox', 'User', 'false', 'ExtendedProfileRegistrationRequiredTitle', ExtendedProfileRegistrationRequiredComment', NULL, 'MyPersonalOpenArea');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('noreply_email_address', '', 'textfield', 'Platform', '', 'NoReplyEmailAddress', 'NoReplyEmailAddressComment', NULL, NULL);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('survey_email_sender_noreply', '', 'radio', 'Course', 'coach', 'SurveyEmailSenderNoReply', 'SurveyEmailSenderNoReplyComment', NULL, NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('survey_email_sender_noreply', 'coach', 'CourseCoachEmailSender');
INSERT INTO settings_options (variable, value, display_text) VALUES ('survey_email_sender_noreply', 'noreply', 'NoReplyEmailSender');
DELETE FROM settings_current WHERE variable='show_student_view';
DELETE FROM settings_options WHERE variable='show_student_view';
CREATE TABLE templates (id int NOT NULL auto_increment, title varchar(100) NOT NULL, description varchar(250) NOT NULL, course_code varchar(40) NOT NULL, user_id int NOT NULL, ref_doc int NOT NULL, PRIMARY KEY (id));

-- xxSTATSxx
ALTER TABLE track_e_downloads ADD INDEX (down_user_id);
ALTER TABLE track_e_downloads ADD INDEX (down_cours_id);
ALTER TABLE track_e_exercices ADD INDEX (exe_cours_id);
ALTER TABLE track_e_hotpotatoes ADD INDEX (exe_user_id);
ALTER TABLE track_e_hotpotatoes ADD INDEX (exe_cours_id);
ALTER TABLE track_e_lastaccess ADD INDEX (access_session_id);
ALTER TABLE track_e_links ADD INDEX (links_cours_id);
ALTER TABLE track_e_links ADD INDEX (links_user_id);
ALTER TABLE track_e_uploads ADD INDEX (upload_user_id);
ALTER TABLE track_e_uploads ADD INDEX (upload_cours_id);

-- xxUSERxx

-- xxCOURSExx
CREATE TABLE lp_iv_objective(id bigint unsigned primary key auto_increment, lp_iv_id bigint unsigned not null, order_id smallint unsigned not null default 0, objective_id	varchar(255) not null default '', score_raw		float unsigned not null default 0, score_max		float unsigned not null default 0, score_min		float unsigned not null default 0, status char(32) not null default 'not attempted');
ALTER TABLE lp_iv_objective ADD INDEX (lp_iv_id);
ALTER TABLE lp_item CHANGE prerequisite prerequisite TEXT DEFAULT NULL;
INSERT INTO course_setting(variable,value,category) VALUES ('email_alert_manager_on_new_quiz',0,'quiz');