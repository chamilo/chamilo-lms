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
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_tabs', 'campus_homepage', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsCampusHomepage');
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_tabs', 'my_courses', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyCourses');
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_tabs', 'reporting', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsReporting');
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_tabs', 'platform_administration', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsPlatformAdministration');
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_tabs', 'my_agenda', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyAgenda');
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('show_tabs', 'my_profile', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyProfile');
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('default_forum_view', NULL, 'radio', 'Course', 'flat', 'DefaultForumViewTitle','DefaultForumViewComment',NULL,NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('platform_charset',NULL,'textfield','Platform','iso-8859-15','PlatformCharsetTitle','PlatformCharsetComment','platform',NULL);

INSERT INTO settings_options(variable,value,display_text) VALUES ('default_forum_view', 'flat', 'Flat');
INSERT INTO settings_options(variable,value,display_text) VALUES ('default_forum_view', 'threaded', 'Threaded');
INSERT INTO settings_options(variable,value,display_text) VALUES ('default_forum_view', 'nested', 'Nested');

-- xxSTATSxx
CREATE TABLE track_e_hotspot (hotspot_id int NOT NULL auto_increment, hotspot_user_id int NOT NULL, hotspot_course_code varchar(50) NOT NULL, hotspot_exe_id int NOT NULL, hotspot_question_id int(11) NOT NULL, hotspot_answer_id int NOT NULL, hotspot_correct tinyint unsigned NOT NULL, hotspot_coordinate varchar(50) NOT NULL, PRIMARY KEY  (hotspot_id),  KEY hotspot_course_code (hotspot_course_code), KEY hotspot_user_id (hotspot_user_id), KEY hotspot_exe_id (hotspot_exe_id), KEY hotspot_question_id (hotspot_question_id));

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE survey ADD anonymous ENUM( '0', '1' ) NOT NULL DEFAULT '0';
ALTER TABLE lp_item ADD max_time_allowed char(13) NULL DEFAULT '';
ALTER TABLE item_property ADD INDEX (tool(20),ref);
ALTER TABLE lp_item_view ADD max_score varchar(8) default '';