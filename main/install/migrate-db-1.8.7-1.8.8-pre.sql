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

DELIMITER //
DROP PROCEDURE IF EXISTS drop_index //
CREATE PROCEDURE drop_index(in t_name varchar(128), in i_name varchar(128) )
BEGIN
IF ( (SELECT count(*) AS index_exists FROM  information_schema.statistics WHERE table_schema = DATABASE( )  AND table_name = t_name  AND  index_name =   i_name ) > 0) THEN
   SET @s = CONCAT('DROP INDEX ' , i_name , ' ON ' , t_name );
   PREPARE stmt FROM @s;
   EXECUTE stmt;
 END IF;
END //
DELIMITER ;

CREATE TABLE course_request (id int NOT NULL AUTO_INCREMENT, code varchar(40) NOT NULL, user_id int unsigned NOT NULL default '0', directory varchar(40) DEFAULT NULL, db_name varchar(40) DEFAULT NULL, course_language varchar(20) DEFAULT NULL, title varchar(250) DEFAULT NULL, description text, category_code varchar(40) DEFAULT NULL, tutor_name varchar(200) DEFAULT NULL, visual_code varchar(40) DEFAULT NULL, request_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00', objetives text, target_audience text, status int unsigned NOT NULL default '0', info int unsigned NOT NULL default '0', exemplary_content int unsigned NOT NULL default '0', PRIMARY KEY (id), UNIQUE KEY code (code));

CALL drop_index('settings_current', 'unique_setting');
CALL drop_index('settings_options', 'unique_setting_option');

ALTER TABLE settings_current ADD UNIQUE unique_setting (variable(110), subkey(110), category(110), access_url);
ALTER TABLE settings_options ADD UNIQUE unique_setting_option (variable(165), value(165));
ALTER TABLE settings_current CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE settings_options CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE user MODIFY COLUMN username VARCHAR(40) NOT NULL;

UPDATE settings_current SET variable='chamilo_database_version' WHERE variable='dokeos_database_version';

ALTER TABLE sys_announcement ADD COLUMN access_url_id INT  NOT NULL default 1;
ALTER TABLE sys_calendar ADD COLUMN access_url_id INT  NOT NULL default 1;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('users_copy_files', NULL, 'radio', 'Tools', 'true', 'AllowUsersCopyFilesTitle','AllowUsersCopyFilesComment', NULL,NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('users_copy_files', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('users_copy_files', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_validation', NULL, 'radio', 'Platform', 'false', 'EnableCourseValidation', 'EnableCourseValidationComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('course_validation', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('course_validation', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_validation_terms_and_conditions_url', NULL, 'textfield', 'Platform', '', 'CourseValidationTermsAndConditionsLink', 'CourseValidationTermsAndConditionsLinkComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication',NULL,'radio','Security','false','EnableSSOTitle','EnableSSOComment',NULL,NULL,1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_domain',NULL,'textfield','Security','','SSOServerDomainTitle','SSOServerDomainComment',NULL,NULL,1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_auth_uri',NULL,'textfield','Security','/?q=user','SSOServerAuthURITitle','SSOServerAuthURIComment',NULL,NULL,1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_unauth_uri',NULL,'textfield','Security','/?q=logout','SSOServerUnAuthURITitle','SSOServerUnAuthURIComment',NULL,NULL,1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_protocol',NULL,'radio','Security','http://','SSOServerProtocolTitle','SSOServerProtocolComment',NULL,NULL,1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication', 'false', 'No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication_protocol', 'http://', 'http://');
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication_protocol', 'https://', 'https://');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_asciisvg',NULL,'radio','Editor','false','AsciiSvgTitle','AsciiSvgComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_asciisvg', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_asciisvg', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('include_asciimathml_script',NULL,'radio','Editor','false','IncludeAsciiMathMlTitle','IncludeAsciiMathMlComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('include_asciimathml_script', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('include_asciimathml_script', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_wiris',NULL,'radio','Editor','false','EnabledWirisTitle','EnabledWirisComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_wiris', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_wiris', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_spellcheck',NULL,'radio','Editor','false','AllowSpellCheckTitle','AllowSpellCheckComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_spellcheck', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_spellcheck', 'false', 'No');
ALTER TABLE course ADD INDEX idx_course_category_code (category_code);
ALTER TABLE course ADD INDEX idx_course_directory (directory(10));
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('force_wiki_paste_as_plain_text',NULL,'radio','Editor','false','ForceWikiPasteAsPlainText','ForceWikiPasteAsPlainTextComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('force_wiki_paste_as_plain_text', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('force_wiki_paste_as_plain_text', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_googlemaps',NULL,'radio','Editor','false','EnabledGooglemapsTitle','EnabledGooglemapsComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_googlemaps', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_googlemaps', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_imgmap',NULL,'radio','Editor','true','EnabledImageMapsTitle','EnabledImageMapsComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_imgmap', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_imgmap', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_support_svg',NULL,'radio','Tools','true','EnabledSVGTitle','EnabledSVGComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_support_svg', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_support_svg', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('pdf_export_watermark_enable',	NULL,'radio',		'Platform',	'false','PDFExportWatermarkEnableTitle',	'PDFExportWatermarkEnableComment','platform',NULL,  1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_enable','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_enable','false','No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('pdf_export_watermark_by_course',	NULL,'radio',		'Platform',	'false','PDFExportWatermarkByCourseTitle',	'PDFExportWatermarkByCourseComment','platform',NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_by_course','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_by_course','false','No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('hide_courses_in_sessions',	NULL,'radio',		'Platform',	'false','HideCoursesInSessionsTitle',	'HideCoursesInSessionsComment','platform',NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('hide_courses_in_sessions','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('hide_courses_in_sessions','false','No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('pdf_export_watermark_text',		NULL,'textfield',	'Platform',	'',		'PDFExportWatermarkTextTitle','PDFExportWatermarkTextComment','platform',NULL, 	1);

ALTER TABLE personal_agenda ADD PRIMARY KEY (id);


INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_insertHtml',NULL,'radio','Editor','true','EnabledInsertHtmlTitle','EnabledInsertHtmlComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_insertHtml', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_insertHtml', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('students_export2pdf',NULL,'radio','Tools','true','EnabledStudentExport2PDFTitle','EnabledStudentExport2PDFComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('students_export2pdf', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('students_export2pdf', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('exercise_min_score', NULL,'textfield',	'Course',	'',		'ExerciseMinScoreTitle',		'ExerciseMinScoreComment','platform',NULL, 	1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('exercise_max_score', NULL,'textfield',	'Course',	'',		'ExerciseMaxScoreTitle',		'ExerciseMaxScoreComment','platform',NULL, 	1);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_users_folders',NULL,'radio','Tools','true','ShowUsersFoldersTitle','ShowUsersFoldersComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_users_folders', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_users_folders', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_default_folders',NULL,'radio','Tools','true','ShowDefaultFoldersTitle','ShowDefaultFoldersComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_default_folders', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_default_folders', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_chat_folder',NULL,'radio','Tools','true','ShowChatFolderTitle','ShowChatFolderComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_chat_folder', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_chat_folder', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_text2audio',NULL,'radio','Tools','false','Text2AudioTitle','Text2AudioComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_text2audio', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_text2audio', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','course_description','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseDescription', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','calendar_event','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Agenda', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','document','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Documents', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','learnpath','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'LearningPath', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','link','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Links', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','announcement','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Announcements', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','forum','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Forums', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','dropbox','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Dropbox', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','quiz','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Quiz', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','user','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Users', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','group','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Groups', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','chat','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Chat', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','student_publication','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'StudentPublications', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','wiki','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Wiki', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','gradebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Gradebook', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','survey','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Survey', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','glossary','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Glossary', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','notebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Notebook', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','attendance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Attendances', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','course_progress','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseProgress', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','blog_management','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Blog',1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','tracking','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Stats',1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','course_maintenance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Maintenance',1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_hide_tools','course_setting','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseSettings',1);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_support_pixlr',NULL,'radio','Tools','false','EnabledPixlrTitle','EnabledPixlrComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_support_pixlr', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_support_pixlr', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_groups_to_users',NULL,'radio','Platform','false','ShowGroupsToUsersTitle','ShowGroupsToUsersComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_groups_to_users', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_groups_to_users', 'false', 'No');

INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available) VALUES ('&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;', 'hindi', 'hi', 'hindi', 0);

ALTER TABLE session ADD COLUMN promotion_id INT NOT NULL;

CREATE TABLE career (id INT NOT NULL AUTO_INCREMENT,	name VARCHAR(255) NOT NULL, description TEXT NOT NULL, status INT NOT NULL default '0', created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY (id));
CREATE TABLE promotion (id INT NOT NULL AUTO_INCREMENT,	name VARCHAR(255) NOT NULL, description TEXT NOT NULL, status INT NOT NULL default '0', career_id INT NOT NULL, created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00', PRIMARY KEY(id));

CREATE TABLE usergroup ( id INT NOT NULL AUTO_INCREMENT,	name VARCHAR(255) NOT NULL, description TEXT NOT NULL,PRIMARY KEY (id));
CREATE TABLE usergroup_rel_user    ( usergroup_id INT NOT NULL, user_id 	INT NOT NULL );
CREATE TABLE usergroup_rel_course  ( usergroup_id INT NOT NULL, course_id 	INT NOT NULL );
CREATE TABLE usergroup_rel_session ( usergroup_id INT NOT NULL, session_id  INT NOT NULL );

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('accessibility_font_resize',NULL,'radio','Platform','false','EnableAccessibilityFontResizeTitle','EnableAccessibilityFontResizeComment',NULL,NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('accessibility_font_resize', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('accessibility_font_resize', 'false', 'No');

CREATE TABLE notification (id BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,dest_user_id INT NOT NULL, dest_mail CHAR(255),title CHAR(255), content CHAR(255), send_freq SMALLINT DEFAULT 1, created_at DATETIME NOT NULL, sent_at DATETIME NULL);

ALTER TABLE notification ADD index mail_notify_sent_index (sent_at);
ALTER TABLE notification ADD index mail_notify_freq_index (sent_at, send_freq, created_at);

ALTER TABLE session_category ADD COLUMN access_url_id INT NOT NULL default 1;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enable_quiz_scenario', NULL,'radio','Course','false','EnableQuizScenarioTitle','EnableQuizScenarioComment',NULL,NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_quiz_scenario', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_quiz_scenario', 'false', 'No');

UPDATE settings_current SET category='Search' WHERE variable='search_enable';

INSERT INTO settings_options (variable, value, display_text) VALUES ('homepage_view', 'activity_big', 'HomepageViewActivityBig');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enable_nanogong',NULL,'radio','Tools','false','EnableNanogongTitle','EnableNanogongComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_nanogong', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_nanogong', 'false', 'No');

ALTER TABLE gradebook_evaluation ADD COLUMN locked int NOT NULL DEFAULT 0;

UPDATE settings_current SET selected_value = '1.8.8.14911' WHERE variable = 'chamilo_database_version';

-- xxSTATSxx
ALTER TABLE track_e_exercices ADD COLUMN orig_lp_item_view_id INT NOT NULL DEFAULT 0;

-- xxUSERxx
ALTER TABLE personal_agenda ADD INDEX idx_personal_agenda_user (user);
ALTER TABLE personal_agenda ADD INDEX idx_personal_agenda_parent (parent_event_id);
ALTER TABLE user_course_category ADD INDEX idx_user_c_cat_uid (user_id);

-- xxCOURSExx
ALTER TABLE course_setting CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE forum_forum ADD start_time DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE forum_forum ADD end_time DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE wiki_mailcue ADD session_id smallint DEFAULT 0;

ALTER TABLE lp ADD COLUMN use_max_score INT DEFAULT 1;
ALTER TABLE lp_item MODIFY COLUMN max_score FLOAT UNSIGNED DEFAULT 100;
ALTER TABLE tool MODIFY COLUMN category varchar(20) not null default 'authoring';

ALTER TABLE lp ADD COLUMN autolunch INT DEFAULT 0;
ALTER TABLE lp ADD COLUMN created_on 	DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE lp ADD COLUMN modified_on 	DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE lp ADD COLUMN expired_on 	DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE lp ADD COLUMN publicated_on DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';

CREATE TABLE quiz_question_option (id int NOT NULL, name varchar(255), position int unsigned NOT NULL, PRIMARY KEY (id));
ALTER TABLE quiz_question ADD COLUMN extra varchar(255) DEFAULT NULL;
ALTER TABLE quiz_question CHANGE question question TEXT NOT NULL;

INSERT INTO course_setting(variable,value,category) VALUES ('enable_lp_auto_launch',0,'learning_path');
INSERT INTO course_setting(variable,value,category) VALUES ('pdf_export_watermark_text','','course');

ALTER TABLE quiz ADD COLUMN propagate_neg INT NOT NULL DEFAULT 0;
ALTER TABLE quiz_answer MODIFY COLUMN hotspot_type ENUM('square','circle','poly','delineation','oar');

ALTER TABLE attendance ADD COLUMN locked int NOT NULL default 0;
CREATE TABLE attendance_sheet_log (id INT  NOT NULL AUTO_INCREMENT, attendance_id INT  NOT NULL DEFAULT 0, lastedit_date DATETIME  NOT NULL DEFAULT '0000-00-00 00:00:00', lastedit_type VARCHAR(200)  NOT NULL, lastedit_user_id INT  NOT NULL DEFAULT 0, calendar_date_value DATETIME NULL, PRIMARY KEY (id));

