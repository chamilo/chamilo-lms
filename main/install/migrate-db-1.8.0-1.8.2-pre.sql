-- This script updates the databases structure before migrating the data from
-- version 1.8.0 to version 1.8.2
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
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('service_visio', 'visio_rtmp_host_local', 'textfield',NULL,'', 'VisioHostLocal','', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('service_visio', 'visio_is_web_rtmp', 'radio',NULL,'false', 'VisioRTMPIsWeb','', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('service_visio', 'visio_rtmp_port', 'textfield',NULL,'1935', 'VisioRTMPPort','', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('service_visio', 'visio_rtmp_tunnel_port', 'textfield',NULL,'80', 'VisioRTMPTunnelPort','', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_number_of_courses', NULL, 'radio','Platform','false', 'ShowNumberOfCourses','ShowNumberOfCoursesComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_empty_course_categories', NULL, 'radio','Platform','true', 'ShowEmptyCourseCategories','ShowEmptyCourseCategoriesComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_back_link_on_top_of_tree', NULL, 'radio','Platform','false', 'ShowBackLinkOnTopOfCourseTree','ShowBackLinkOnTopOfCourseTreeComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_different_course_language', NULL, 'radio','Platform','true', 'ShowDifferentCourseLanguage','ShowDifferentCourseLanguageComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('split_users_upload_directory', NULL, 'radio','Tuning','false', 'SplitUsersUploadDirectory','SplitUsersUploadDirectoryComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('hide_dltt_markup', NULL, 'radio','Platform','false', 'HideDLTTMarkup','HideDLTTMarkupComment', NULL, NULL);


INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_list_type', 'blacklist', 'Blacklist');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_list_type', 'whitelist', 'Whitelist');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_skip', 'true', 'Remove');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_skip', 'false', 'Rename'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('visio_rtmp_host_local', 'true', 'Web');
INSERT INTO settings_options(variable,value,display_text) VALUES ('visio_rtmp_host_local', 'false', 'Not web'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_number_of_courses', 'true', 'Yes');
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_number_of_courses', 'false', 'No'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_empty_course_categories', 'true', 'Yes');
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_empty_course_categories', 'false', 'No'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_back_link_on_top_of_tree', 'true', 'Yes');
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_back_link_on_top_of_tree', 'false', 'No'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_different_course_language', 'true', 'Yes');
INSERT INTO settings_options(variable,value,display_text) VALUES ('show_different_course_language', 'false', 'No'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('split_users_upload_directory', 'true', 'Yes');
INSERT INTO settings_options(variable,value,display_text) VALUES ('split_users_upload_directory', 'false', 'No'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('hide_dltt_markup', 'false', 'No'); 
INSERT INTO settings_options(variable,value,display_text) VALUES ('hide_dltt_markup', 'true', 'Yes'); 

-- Insert anonymous user
INSERT INTO user(lastname, firstname, username, password, auth_source, email, status, official_code, creator_id, registration_date, expiration_date,active) VALUES ('Anonymous', 'Joe', '', '', 'platform', 'anonymous@localhost', 6, 'anonymous', 1, NOW(), '0000-00-00 00:00:00', 1);
ALTER TABLE user ADD INDEX (status);

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
CREATE TABLE survey_answer (answer_id int unsigned NOT NULL auto_increment, survey_id int unsigned NOT NULL, question_id int NOT NULL, option_id TEXT NOT NULL, value int unsigned not null, user varchar(250) NOT NULL, PRIMARY KEY  (answer_id) );

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
