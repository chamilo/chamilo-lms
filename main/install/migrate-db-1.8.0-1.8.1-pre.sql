-- This script updates the databases structure before migrating the data from
-- version 1.8.0 to version 1.8.1
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
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_list_type', NULL, 'radio', 'Security', 'blacklist', 'UploadExtensionsListType', 'UploadExtensionsListTypeComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_blacklist', NULL, 'textfield', 'Security', '', 'UploadExtensionsBlacklist', 'UploadExtensionsBlacklistComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_whitelist', NULL, 'textfield', 'Security', 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg', 'UploadExtensionsWhitelist', 'UploadExtensionsWhitelistComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_skip', NULL, 'radio', 'Security', 'true', 'UploadExtensionsSkip', 'UploadExtensionsSkipComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_replace_by', NULL, 'textfield', 'Security', 'txt', 'UploadExtensionsReplaceBy', 'UploadExtensionsReplaceByComment', NULL, NULL);

INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_list_type', 'blacklist', 'Blacklist');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_list_type', 'whitelist', 'Whitelist');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_skip', 'true', 'Remove');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_skip', 'false', 'Rename');

-- xxSTATSxx
ALTER TABLE track_e_attempt ADD INDEX (exe_id);
ALTER TABLE track_e_attempt ADD INDEX (user_id); 
ALTER TABLE track_e_attempt ADD INDEX (question_id);
ALTER TABLE track_e_exercices ADD INDEX (exe_user_id);
ALTER TABLE track_e_course_access ADD INDEX (user_id);
ALTER TABLE track_e_course_access ADD INDEX (login_course_date);
ALTER TABLE track_e_course_access ADD INDEX (course_code);

-- xxUSERxx

-- xxCOURSExx
CREATE TABLE IF NOT EXISTS survey_answer (answer_id int unsigned NOT NULL auto_increment, survey_id int unsigned NOT NULL, question_id int NOT NULL, option_id TEXT NOT NULL, value int unsigned not null, user varchar(250) NOT NULL, PRIMARY KEY  (answer_id) );
ALTER TABLE lp_view ADD INDEX (lp_id);
ALTER TABLE lp_view ADD INDEX (user_id);
ALTER TABLE lp_item ADD INDEX (lp_id);
ALTER TABLE lp_item_view ADD INDEX (lp_item_id);
ALTER TABLE lp_item_view ADD INDEX (lp_view_id);
ALTER TABLE lp_iv_interaction ADD INDEX (lp_iv_id);
ALTER TABLE quiz_question ADD INDEX (position); 
ALTER TABLE forum_thread ADD INDEX (forum_id);
ALTER TABLE forum_thread DROP INDEX thread_id;
ALTER TABLE lp_item_view ADD core_exit varchar(32) NOT NULL DEFAULT 'none';
