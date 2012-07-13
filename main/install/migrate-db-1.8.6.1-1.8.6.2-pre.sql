-- This script updates the databases structure before migrating the data from
-- version 1.8.6.1 to version 1.8.6.2
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
ALTER TABLE session ADD COLUMN visibility int NOT NULL default 1;
ALTER TABLE session ADD COLUMN session_category_id INT NOT NULL;

ALTER TABLE session_rel_course_rel_user ADD COLUMN visibility int NOT NULL default 1;
ALTER TABLE session_rel_course_rel_user ADD COLUMN status int NOT NULL default 0;
CREATE TABLE session_category (id int NOT NULL auto_increment, name varchar(100) default NULL, date_start date default NULL, date_end date default NULL, PRIMARY KEY  (id));

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_coach_to_edit_course_session', NULL, 'radio', 'Course', 'false', 'AllowCoachsToEditInsideTrainingSessions', 'AllowCoachsToEditInsideTrainingSessionsComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable) VALUES ('show_courses_descriptions_in_catalog', NULL, 'radio', 'Course', 'true', 'ShowCoursesDescriptionsInCatalogTitle', 'ShowCoursesDescriptionsInCatalogComment', NULL, NULL, 1, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable) VALUES ('show_glossary_in_extra_tools', NULL, 'radio', 'Course', 'false', 'ShowGlossaryInExtraToolsTitle', 'ShowGlossaryInExtraToolsComment', NULL, NULL,1,0);

INSERT INTO settings_options (variable, value, display_text) VALUES ('show_courses_descriptions_in_catalog', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_courses_descriptions_in_catalog', 'false', 'No');

INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_coach_to_edit_course_session', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_coach_to_edit_course_session', 'false', 'No');

INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'false', 'No');

CREATE TABLE tag (id int NOT NULL auto_increment, tag varchar(255) NOT NULL, field_id int NOT NULL, count int NOT NULL, PRIMARY KEY  (id));
CREATE TABLE user_rel_tag (id int NOT NULL auto_increment,user_id int NOT NULL,tag_id int NOT NULL, PRIMARY KEY  (id));

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('send_email_to_admin_when_create_course',NULL,'radio','Platform','false','SendEmailToAdminTitle','SendEmailToAdminComment',NULL,NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('send_email_to_admin_when_create_course','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('send_email_to_admin_when_create_course','false','No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('go_to_course_after_login', NULL, 'radio', 'Course', 'false', 'GoToCourseAfterLoginTitle', 'GoToCourseAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('go_to_course_after_login', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('go_to_course_after_login', 'false', 'No');

CREATE TABLE message_attachment (id int NOT NULL AUTO_INCREMENT, path varchar(255) NOT NULL, comment text, size int NOT NULL default 0, message_id int NOT NULL, filename varchar(255) NOT NULL, PRIMARY KEY(id));

CREATE TABLE groups (id int NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL, description varchar(255) NOT NULL, picture_uri varchar(255) NOT NULL, url varchar(255) NOT NULL, visibility int NOT NULL, updated_on varchar(255) NOT NULL, created_on varchar(255) NOT NULL, PRIMARY KEY (id));
CREATE TABLE group_rel_tag (id int NOT NULL AUTO_INCREMENT, tag_id int NOT NULL, group_id int NOT NULL, PRIMARY KEY (id));
CREATE TABLE group_rel_user (id int NOT NULL AUTO_INCREMENT, group_id int NOT NULL, user_id int NOT NULL, relation_type int NOT NULL, PRIMARY KEY (id));

ALTER TABLE message ADD COLUMN group_id INT NOT NULL DEFAULT 0;
ALTER TABLE message ADD COLUMN parent_id INT NOT NULL DEFAULT 0;
ALTER TABLE message ADD INDEX idx_message_group(group_id);
ALTER TABLE message ADD INDEX idx_message_parent(parent_id);

INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) values (1, 'rssfeeds','RSS',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) values (10,'tags','tags',0,0);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_tabs', 'social', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsSocial', 0);

UPDATE settings_current SET selected_value = '1.8.6.2.9928' WHERE variable = 'dokeos_database_version';

INSERT INTO course_field (field_type, field_variable, field_display_text, field_default_value, field_visible, field_changeable) values (10, 'special_course','SpecialCourse', 'Yes', 1 , 1);

ALTER TABLE group_rel_user ADD INDEX ( group_id );
ALTER TABLE group_rel_user ADD INDEX ( user_id );
ALTER TABLE group_rel_user ADD INDEX ( relation_type );
ALTER TABLE group_rel_tag  ADD INDEX ( group_id );
ALTER TABLE group_rel_tag  ADD INDEX ( tag_id );

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_students_to_create_groups_in_social', NULL, 'radio', 'Tools', 'false', 'AllowStudentsToCreateGroupsInSocialTitle', 'AllowStudentsToCreateGroupsInSocialComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_students_to_create_groups_in_social', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_students_to_create_groups_in_social', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_send_message_to_all_platform_users', NULL, 'radio', 'Tools', 'false', 'AllowSendMessageToAllPlatformUsersTitle', 'AllowSendMessageToAllPlatformUsersComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_send_message_to_all_platform_users', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_send_message_to_all_platform_users', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('message_max_upload_filesize', NULL, 'textfield', 'Tools', '20971520', 'MessageMaxUploadFilesizeTitle','MessageMaxUploadFilesizeComment',NULL,NULL, 0);

ALTER TABLE message ADD COLUMN update_date DATETIME  NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE settings_current SET category = 'Languages' WHERE variable = 'hide_dltt_markup';
UPDATE settings_current SET category = 'Languages' WHERE variable = 'platform_charset';
UPDATE settings_current SET category = 'Languages' WHERE variable = 'allow_use_sub_language';

UPDATE settings_current SET category = 'Editor' WHERE variable = 'wcag_anysurfer_public_pages';
UPDATE settings_current SET category = 'Editor' WHERE variable = 'advanced_filemanager';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('math_mimetex',NULL,'radio','Editor','false','MathMimetexTitle','MathMimetexComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('math_mimetex', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('math_mimetex', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('math_asciimathML',NULL,'radio','Editor','false','MathASCIImathMLTitle','MathASCIImathMLComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('math_asciimathML', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('math_asciimathML', 'false', 'No');


INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('youtube_for_students',NULL,'radio','Editor','true','YoutubeForStudentsTitle','YoutubeForStudentsComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('youtube_for_students', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('youtube_for_students', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('block_copy_paste_for_students',NULL,'radio','Editor','false','BlockCopyPasteForStudentsTitle','BlockCopyPasteForStudentsComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('block_copy_paste_for_students', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('block_copy_paste_for_students', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('more_buttons_maximized_mode',NULL,'radio','Editor','false','MoreButtonsForMaximizedModeTitle','MoreButtonsForMaximizedModeComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('more_buttons_maximized_mode', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('more_buttons_maximized_mode', 'false', 'No');


INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('user_order_by', NULL, 'radio', 'User', 'lastname', 'OrderUsersByTitle', 'OrderUsersByComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('user_order_by','firstname','FirstName');
INSERT INTO settings_options (variable, value, display_text) VALUES ('user_order_by','lastname','LastName');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('read_more_limit', NULL , 'textfield', 'Tools', '100', 'ReadMoreLimitTitle', 'ReadMoreLimitComment', NULL , NULL , '1');


-- xxSTATSxx
ALTER TABLE track_e_exercices ADD COLUMN expired_time_control datetime NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE track_e_online ADD INDEX (course);

-- xxUSERxx

-- xxCOURSExx

CREATE TABLE announcement_attachment ( id int NOT NULL auto_increment, path varchar(255) NOT NULL, comment text, size int NOT NULL default 0, announcement_id int NOT NULL, filename varchar(255) NOT NULL, PRIMARY KEY (id) );
ALTER TABLE quiz ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE blog ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE course_description ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE glossary ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE link ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE wiki ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE tool ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE link_category ADD COLUMN session_id smallint DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE item_property ADD id_session INT NOT NULL DEFAULT 0;
ALTER TABLE item_property DROP INDEX idx_item_property_toolref, ADD INDEX idx_item_property_toolref (tool, ref, id_session);
ALTER TABLE quiz ADD COLUMN expired_time int NOT NULL DEFAULT '0' AFTER feedback_type;
ALTER TABLE group_info ADD COLUMN chat_state TINYINT DEFAULT 1, ADD INDEX (chat_state);
ALTER TABLE group_category ADD COLUMN chat_state TINYINT DEFAULT 1, ADD INDEX (chat_state);
ALTER TABLE student_publication ADD COLUMN weight float(6,2) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE course_description ADD COLUMN description_type TINYINT NOT NULL DEFAULT 0;
ALTER TABLE dropbox_category ADD COLUMN session_id smallint NOT NULL DEFAULT 0, ADD INDEX (session_id);
ALTER TABLE quiz ADD COLUMN random_answers TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER random;
ALTER TABLE quiz_answer ADD COLUMN id_auto INT NOT NULL AUTO_INCREMENT, ADD UNIQUE INDEX (id_auto);

ALTER TABLE chat_connected ADD COLUMN session_id INT NOT NULL default 0;
ALTER TABLE chat_connected ADD COLUMN to_group_id INT NOT NULL default 0;
