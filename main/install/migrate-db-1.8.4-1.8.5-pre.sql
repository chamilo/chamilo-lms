-- This script updates the databases structure before migrating the data from
-- version 1.8.3 to version 1.8.4
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
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'mycomptetences', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyCompetences');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'mydiplomas', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyDiplomas');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'myteach', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyTeach');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registration', 'mypersonalopenarea', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationTitle', 'ExtendedProfileRegistrationComment', NULL, 'MyPersonalOpenArea');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'mycomptetences', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationRequiredTitle', 'ExtendedProfileRegistrationRequiredComment', NULL, 'MyCompetences');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'mydiplomas', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationRequiredTitle', ExtendedProfileRegistrationRequiredComment', NULL, 'MyDiplomas');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'myteach', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationRequiredTitle', ExtendedProfileRegistrationRequiredComment', NULL, 'MyTeach');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('extendedprofile_registrationrequired', 'mypersonalopenarea', 'checkbox', 'User', 'true', 'ExtendedProfileRegistrationRequiredTitle', ExtendedProfileRegistrationRequiredComment', NULL, 'MyPersonalOpenArea');

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
