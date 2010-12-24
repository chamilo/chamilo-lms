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
CREATE TABLE course_request (id int NOT NULL AUTO_INCREMENT, code varchar(40) NOT NULL, user_id int unsigned NOT NULL default '0', directory varchar(40) DEFAULT NULL, db_name varchar(40) DEFAULT NULL, course_language varchar(20) DEFAULT NULL, title varchar(250) DEFAULT NULL, description text, category_code varchar(40) DEFAULT NULL, tutor_name varchar(200) DEFAULT NULL, visual_code varchar(40) DEFAULT NULL, request_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00', objetives text, target_audience text, status int unsigned NOT NULL default '0', info int unsigned NOT NULL default '0', exemplary_content int unsigned NOT NULL default '0', PRIMARY KEY (id), UNIQUE KEY code (code));

ALTER TABLE settings_current DROP INDEX unique_setting;
ALTER TABLE settings_options DROP INDEX unique_setting_option;
ALTER TABLE settings_current ADD UNIQUE unique_setting (variable(110), subkey(110), category(110), access_url);
ALTER TABLE settings_options ADD UNIQUE unique_setting_option (variable(165), value(165));
ALTER TABLE settings_current CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;
ALTER TABLE settings_options CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE user MODIFY COLUMN username VARCHAR(40) NOT NULL;

UPDATE settings_current SET variable='chamilo_database_version' WHERE variable='dokeos_database_version';
UPDATE settings_current SET selected_value = '1.8.8.13050' WHERE variable = 'chamilo_database_version';

ALTER TABLE sys_announcement ADD COLUMN access_url_id INT  NOT NULL default 1;
ALTER TABLE sys_calendar ADD COLUMN access_url_id INT  NOT NULL default 1;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('users_copy_files', NULL, 'radio', 'Tools', 'true', 'AllowUsersCopyFilesTitle','AllowUsersCopyFilesComment', NULL,NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('users_copy_files', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('users_copy_files', 'false', 'No');
UPDATE settings_current SET selected_value='1' WHERE variable='allow_social_tool';
UPDATE settings_current SET selected_value='1' WHERE variable='allow_message_tool';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_validation', NULL, 'radio', 'Platform', 'false', 'EnableCourseValidation', 'EnableCourseValidationComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('course_validation', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('course_validation', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_validation_terms_and_conditions_url', NULL, 'textfield', 'Platform', '', 'CourseValidationTermsAndConditionsLink', 'CourseValidationTermsAndConditionsLinkComment', NULL, NULL, 1);
UPDATE settings_current SET selected_value='1' WHERE variable='advanced_filemanager';
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication',NULL,'radio','Security','false','EnableSSOTitle','EnableSSOComment',NULL,NULL,1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_domain',NULL,'textfield','Security','','SSOServerDomainTitle','SSOServerDomainComment',NULL,NULL,1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_auth_uri',NULL,'textfield','Security','/?q=user','SSOServerAuthURITitle','SSOServerAuthURIComment',NULL,NULL,1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_unauth_uri',NULL,'textfield','Security','/?q=logout','SSOServerUnAuthURITitle','SSOServerUnAuthURIComment',NULL,NULL);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('sso_authentication_protocol',NULL,'radio','Security','http://','SSOServerProtocolTitle','SSOServerProtocolComment',NULL,NULL,1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication', 'false', 'No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication_protocol', 'http://', 'http://');
INSERT INTO settings_options (variable, value, display_text) VALUES ('sso_authentication_protocol', 'https://', 'https://');
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
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_support_svg',NULL,'radio','Editor','true','EnabledSVGTitle','EnabledSVGComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_support_svg', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_support_svg', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('pdf_export_watermark_enable',	NULL,'radio',		'Platform',	'false','PDFExportWatermarkEnableTitle',	'PDFExportWatermarkEnableComment','platform',NULL,  1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_enable','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_enable','false','No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('pdf_export_watermark_by_course',	NULL,'radio',		'Platform',	'false','PDFExportWatermarkByCourseTitle',	'PDFExportWatermarkByCourseComment','platform',NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_by_course','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('pdf_export_watermark_by_course','false','No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('pdf_export_watermark_text',		NULL,'textfield',	'Platform',	'',		'PDFExportWatermarkTextTitle',		'PDFExportWatermarkTextComment','platform',NULL, 	1);


ALTER TABLE personal_agenda ADD PRIMARY KEY (id);

UPDATE settings_current SET selected_value='1' WHERE variable='more_buttons_maximized_mode';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enabled_insertHtml',NULL,'radio','Editor','false','EnabledInsertHtmlTitle','EnabledInsertHtmlComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_insertHtml', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enabled_insertHtml', 'false', 'No');




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

INSERT INTO course_setting(variable,value,category) VALUES ('enable_lp_auto_launch',0,'learning_path');
INSERT INTO course_setting(variable,value,category) VALUES ('pdf_export_watermark_text','','course');

