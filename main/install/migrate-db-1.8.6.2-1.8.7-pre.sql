-- This script updates the databases structure before migrating the data from
-- version 1.8.6.2 to version 1.8.7
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

ALTER TABLE user_friend RENAME TO user_rel_user;
ALTER TABLE session_rel_user ADD COLUMN relation_type int NOT NULL default 0;
ALTER TABLE course_rel_user  ADD COLUMN relation_type int NOT NULL default 0;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable) VALUES ('course_create_active_tools','notebook','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Notebook',1,0);
ALTER TABLE course DROP PRIMARY KEY , ADD UNIQUE KEY code (code); 
ALTER TABLE course ADD id int NOT NULL auto_increment PRIMARY KEY FIRST;
CREATE TABLE block (id INT NOT NULL auto_increment, name VARCHAR(255) NULL, description TEXT NULL, path VARCHAR(255) NOT NULL, controller VARCHAR(100) NOT NULL, active TINYINT NOT NULL default 1, PRIMARY KEY(id));
ALTER TABLE block ADD UNIQUE(path);
INSERT INTO user_field(field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES(1, 'dashboard', 'Dashboard', 0, 0);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('show_tabs', 'dashboard', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsDashboard', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('use_users_timezone', 'timezones', 'radio', 'Timezones', 'true', 'UseUsersTimezoneTitle','UseUsersTimezoneComment',NULL,'Timezones', 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('use_users_timezone', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('use_users_timezone', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('timezone_value', 'timezones', 'select', 'Timezones', '', 'TimezoneValueTitle','TimezoneValueComment',NULL,'Timezones', 1);

ALTER TABLE user_field CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE course_field CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE course_field_values CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE session_field CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE session_field_values CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE user_field_options CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE user_field_values CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE access_url CHANGE tms tms DATETIME NOT NULL default '0000-00-00 00:00:00';

ALTER TABLE gradebook_certificate CHANGE date_certificate created_at DATETIME NOT NULL default '0000-00-00 00:00:00';

ALTER TABLE gradebook_evaluation ADD COLUMN created_at DATETIME NOT NULL default '0000-00-00 00:00:00';
UPDATE gradebook_evaluation SET created_at = FROM_UNIXTIME(date);
ALTER TABLE gradebook_evaluation DROP date;

ALTER TABLE gradebook_link ADD COLUMN created_at DATETIME NOT NULL default '0000-00-00 00:00:00';
UPDATE gradebook_link SET created_at = FROM_UNIXTIME(date);
ALTER TABLE gradebook_link DROP date;

ALTER TABLE gradebook_linkeval_log ADD COLUMN created_at DATETIME NOT NULL default '0000-00-00 00:00:00';
UPDATE gradebook_linkeval_log SET created_at = FROM_UNIXTIME(date_log);
ALTER TABLE gradebook_linkeval_log DROP date_log;

ALTER TABLE gradebook_result ADD COLUMN created_at DATETIME NOT NULL default '0000-00-00 00:00:00';
UPDATE gradebook_result SET created_at = FROM_UNIXTIME(date);
ALTER TABLE gradebook_result DROP date;

ALTER TABLE gradebook_result_log CHANGE date_log created_at DATETIME NOT NULL default '0000-00-00 00:00:00';
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_number_decimals', NULL, 'select', 'Gradebook', '0', 'GradebookNumberDecimals', 'GradebookNumberDecimalsComment', NULL, NULL, 0);

INSERT INTO user_field(field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES(11, 'timezone', 'Timezone', 0, 0);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_create_active_tools','attendances','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Attendances', 0);

ALTER TABLE user_field_values CHANGE id id BIGINT NOT NULL AUTO_INCREMENT;
ALTER TABLE user_field_values ADD INDEX (user_id, field_id);

UPDATE settings_current SET selected_value = '1.8.7.11571' WHERE variable = 'dokeos_database_version';

ALTER TABLE course_rel_user DROP PRIMARY KEY, ADD PRIMARY KEY (course_code, user_id, relation_type);
ALTER TABLE session_rel_user DROP PRIMARY KEY, ADD PRIMARY KEY (id_session, id_user, relation_type);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_create_active_tools','course_progress','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseProgress', 0);
INSERT INTO settings_options(variable,value,display_text) VALUES ('homepage_view','vertical_activity','HomepageViewVerticalActivity');

UPDATE settings_current SET selected_value = 'UTF-8' WHERE variable = 'platform_charset';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_user_course_subscription_by_course_admin', NULL, 'radio', 'Security', 'true', 'AllowUserCourseSubscriptionByCourseAdminTitle', 'AllowUserCourseSubscriptionByCourseAdminComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_user_course_subscription_by_course_admin', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_user_course_subscription_by_course_admin', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_link_bug_notification', NULL, 'radio', 'Platform', 'true', 'ShowLinkBugNotificationTitle', 'ShowLinkBugNotificationComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_link_bug_notification', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_link_bug_notification', 'false', 'No');
-- CAS
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_activate', NULL, 'radio', 'CAS', 'false', 'CasMainActivateTitle', 'CasMainActivateComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) values ('cas_activate', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) values ('cas_activate', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_server', NULL, 'textfield', 'CAS', '', 'CasMainServerTitle', 'CasMainServerComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_server_uri', NULL, 'textfield', 'CAS', '', 'CasMainServerURITitle', 'CasMainServerURIComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_port', NULL, 'textfield', 'CAS', '', 'CasMainPortTitle', 'CasMainPortComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_protocol', NULL, 'radio', 'CAS', '', 'CasMainProtocolTitle', 'CasMainProtocolComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) values ('cas_protocol', 'CAS1', 'CAS1Text');
INSERT INTO settings_options (variable, value, display_text) values ('cas_protocol', 'CAS2', 'CAS2Text');
INSERT INTO settings_options (variable, value, display_text) values ('cas_protocol', 'SAML', 'SAMLText');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_add_user_activate', NULL, 'radio', 'CAS', '', 'CasUserAddActivateTitle', 'CasUserAddActivateComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) values ('cas_add_user_activate', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) values ('cas_add_user_activate', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_add_user_login_attr', NULL, 'textfield', 'CAS', '', 'CasUserAddLoginAttributeTitle', 'CasUserAddLoginAttributeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_add_user_email_attr', NULL, 'textfield', 'CAS', '', 'CasUserAddEmailAttributeTitle', 'CasUserAddEmailAttributeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_add_user_firstname_attr', NULL, 'textfield', 'CAS', '', 'CasUserAddFirstnameAttributeTitle', 'CasUserAddFirstnameAttributeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_add_user_lastname_attr', NULL, 'textfield', 'CAS', '', 'CasUserAddLastnameAttributeTitle', 'CasUserAddLastnameAttributeComment', NULL, NULL, 0);
-- Custom Pages
INSERT INTO settings_options (variable, value, display_text) VALUES ('use_custom_pages', 'true', 'Yes'), ('use_custom_pages', 'false', 'No');
INSERT INTO settings_current (variable, type, category, selected_value, title, comment, scope) VALUES ('use_custom_pages','radio','Platform','false','UseCustomPages','UseCustomPagesComment', 'platform');

ALTER TABLE gradebook_score_display ADD category_id int NOT NULL DEFAULT 0;
ALTER TABLE gradebook_score_display ADD INDEX (category_id);
ALTER TABLE gradebook_score_display ADD score_color_percent float unsigned NOT NULL DEFAULT 0;
-- CBlue custom
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('student_page_after_login', NULL, 'textfield', 'Platform', '', 'StudentPageAfterLoginTitle', 'StudentPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('teacher_page_after_login', NULL, 'textfield', 'Platform', '', 'TeacherPageAfterLoginTitle', 'TeacherPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('DRH_page_after_login', NULL, 'textfield', 'Platform', '', 'DRHPageAfterLoginTitle', 'DRHPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('sessionadmin_page_after_login', NULL, 'textfield', 'Platform', '', 'SessionAdminPageAfterLoginTitle', 'SessionAdminPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('student_autosubscribe', NULL, 'textfield', 'Platform', '', 'StudentAutosubscribeTitle', 'StudentAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('teacher_autosubscribe', NULL, 'textfield', 'Platform', '', 'TeacherAutosubscribeTitle', 'TeacherAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('DRH_autosubscribe', NULL, 'textfield', 'Platform', '', 'DRHAutosubscribeTitle', 'DRHAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('sessionadmin_autosubscribe', NULL, 'textfield', 'Platform', '', 'SessionadminAutosubscribeTitle', 'SessionadminAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('show_tabs', 'custom_tab_1', 'checkbox', 'Platform', 'false', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom1', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('show_tabs', 'custom_tab_2', 'checkbox', 'Platform', 'false', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom2', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('show_tabs', 'custom_tab_3', 'checkbox', 'Platform', 'false', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom3', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('custom_tab_1_name', NULL, 'textfield', 'Platform', '', 'CustomTab1NameTitle', 'CustomTab1NameComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('custom_tab_1_url', NULL, 'textfield', 'Platform', '', 'CustomTab1URLTitle', 'CustomTab1URLComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('custom_tab_2_name', NULL, 'textfield', 'Platform', '', 'CustomTab2NameTitle', 'CustomTab2NameComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('custom_tab_2_url', NULL, 'textfield', 'Platform', '', 'CustomTab2URLTitle', 'CustomTab2URLComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('custom_tab_3_name', NULL, 'textfield', 'Platform', '', 'CustomTab3NameTitle', 'CustomTab3NameComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('custom_tab_3_url', NULL, 'textfield', 'Platform', '', 'CustomTab3URLTitle', 'CustomTab3URLComment', NULL, NULL, 0);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('activate_send_event_by_mail', NULL, 'radio', 'Platform', 'false', 'ActivateSendEventByMailTitle', 'ActivateSendEventByMailComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('activate_send_event_by_mail', 'true', 'Yes'),('activate_send_event_by_mail', 'false', 'No');
CREATE TABLE `event_type` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(50) NOT NULL, `name_lang_var` varchar(40) NOT NULL, `desc_lang_var` varchar(50) NOT NULL, PRIMARY KEY (`id`));
CREATE TABLE `event_type_message` (`id` int(11) NOT NULL AUTO_INCREMENT, `event_type_id` int(11) NOT NULL,`language_id` int(11) NOT NULL, `message` varchar(200) NOT NULL, `subject` varchar(60) NOT NULL, PRIMARY KEY (`id`));
CREATE TABLE `user_rel_event_type` (`id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `event_type_id` int(11) NOT NULL, PRIMARY KEY (`id`));
INSERT INTO `event_type` VALUES (1, 'course_deleted','courseDeletedTitle','courseDeletedComment'),(2,'course_created','courseCreatedTitle','courseCreatedComment'),(3,'user_deleted','userDeletedTitle','userDeletedComment'),(4,'user_created','userCreatedTitle','userCreatedComment'), (5, 'session_created','sessionCreatedTitle','sessionCreatedComment'), (6,'session_deleted','sessionDeletedTitle','sessionDeletedComment'), (7,'session_category_created','sessionCategoryCreatedTitle','sessionCategoryCreatedComment'),(8,'session_category_deleted','sessionCategoryDeletedTitle','sessionCategoryDeletedComment'),(9,'settings_changed','settingsChangedTitle','settingsChangedComment'),(10,'user_subscribed','userSubscribedTitle','userSubscribedComment'), (11,'user_unsubscribed','userUnsubscribedTitle','userUnsubscribedComment');
INSERT INTO `event_type_message` (`id`,`event_type_id`, `language_id`, `message`,`subject`) VALUES (1,4,10,'Bonjour, \r\n\r\nL\'utilisateur %username% (%firstname% %lastname%) a été créé.\r\nEmail : %mail%\r\n\r\nBien à vous.',''),(2,1,10,'Delete formation',''),(3,2,10,'Create formation',''),(4,3,10,'Bonjour, \r\n\r\nL\'utilisateur %username% (%firstname% %lastname%) a été supprimé.\r\n\r\nBien à vous.',''),(6,5,10,'Create session test',''),(7,6,10,'Delete session',''),(8,7,10,'Create category session',''),(9,8,10,'Delete category session',''),(10,9,10,'Change setting',''),(11,10,10,'Subscribe',''),(12,11,10,'Unsubscribe','');
CREATE TABLE announcement_rel_group (group_id int NOT NULL, announcement_id int NOT NULL, PRIMARY KEY (group_id, announcement_id));

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES  ('languagePriority1', NULL, 'radio', 'Languages', '', 'LanguagePriority1Title', 'LanguagePriority1Comment', NULL, NULL, 0); 
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('languagePriority2', NULL, 'radio', 'Languages', '', 'LanguagePriority2Title', 'LanguagePriority2Comment', NULL, NULL, 0); 
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('languagePriority3', NULL, 'radio', 'Languages', '', 'LanguagePriority3Title', 'LanguagePriority3Comment', NULL, NULL, 0); 
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('languagePriority4', NULL, 'radio', 'Languages', '', 'LanguagePriority4Title', 'LanguagePriority4Comment', NULL, NULL, 0); 
INSERT INTO settings_options (variable, value, display_text) VALUES ('languagePriority1','platform_lang','PlatformLanguage'), ('languagePriority1','user_profil_lang','UserLanguage'), ('languagePriority1','user_selected_lang','UserSelectedLanguage'), ('languagePriority1','course_lang','CourseLanguage'), ('languagePriority2','platform_lang','PlatformLanguage'), ('languagePriority2','user_profil_lang','UserLanguage'), ('languagePriority2','user_selected_lang','UserSelectedLanguage'), ('languagePriority2','course_lang','CourseLanguage'), ('languagePriority3','platform_lang','PlatformLanguage'), ('languagePriority3','user_profil_lang','UserLanguage'), ('languagePriority3','user_selected_lang','UserSelectedLanguage'), ('languagePriority3','course_lang','CourseLanguage'), ('languagePriority4','platform_lang','PlatformLanguage'), ('languagePriority4','user_profil_lang','UserLanguage'), ('languagePriority4','user_selected_lang','UserSelectedLanguage'), ('languagePriority4','course_lang','CourseLanguage');


-- 
-- Table structure for LP custom storage API
--
CREATE TABLE stored_values (user_id INT NOT NULL, sco_id INT NOT NULL, course_id CHAR(40) NOT NULL, sv_key CHAR(64) NOT NULL, sv_value TEXT NOT NULL );
ALTER TABLE stored_values ADD KEY (user_id, sco_id, course_id, sv_key);
ALTER TABLE stored_values ADD UNIQUE (user_id, sco_id, course_id, sv_key);
CREATE TABLE stored_values_stack (user_id INT NOT NULL, sco_id INT NOT NULL, stack_order INT NOT NULL, course_id CHAR(40) NOT NULL, sv_key CHAR(64) NOT NULL, sv_value TEXT NOT NULL );
ALTER TABLE stored_values_stack ADD KEY (user_id, sco_id, course_id, sv_key, stack_order);
ALTER TABLE stored_values_stack ADD UNIQUE (user_id, sco_id, course_id, sv_key, stack_order);

-- xxSTATSxx
CREATE TABLE track_e_item_property(id int NOT NULL auto_increment PRIMARY KEY, course_id int NOT NULL, item_property_id int NOT NULL, title varchar(255), content text, progress int NOT NULL default 0, lastedit_date datetime NOT NULL default '0000-00-00 00:00:00', lastedit_user_id int  NOT NULL, session_id int NOT NULL default 0);
ALTER TABLE track_e_item_property ADD INDEX (course_id, item_property_id, session_id);
ALTER TABLE track_e_access ADD access_session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_access ADD INDEX (access_session_id);  
ALTER TABLE track_e_course_access ADD session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_course_access ADD INDEX (session_id);
ALTER TABLE track_e_downloads ADD down_session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_downloads ADD INDEX (down_session_id);
ALTER TABLE track_e_links ADD links_session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_links ADD INDEX (links_session_id);
ALTER TABLE track_e_uploads ADD upload_session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_uploads ADD INDEX (upload_session_id);
ALTER TABLE track_e_online ADD session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_online ADD INDEX (session_id);
ALTER TABLE track_e_attempt ADD session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_attempt ADD INDEX (session_id);
ALTER TABLE track_e_attempt_recording ADD session_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_attempt_recording ADD INDEX (question_id);
ALTER TABLE track_e_attempt_recording ADD INDEX (session_id);
ALTER TABLE track_e_online ADD COLUMN access_url_id INT NOT NULL DEFAULT 1;
-- Groups can have subgroups
CREATE TABLE group_rel_group ( id int NOT NULL AUTO_INCREMENT, group_id int NOT NULL, subgroup_id int NOT NULL, relation_type int NOT NULL, PRIMARY KEY (id));
ALTER TABLE group_rel_group ADD INDEX ( group_id );
ALTER TABLE group_rel_group ADD INDEX ( subgroup_id );
ALTER TABLE group_rel_group ADD INDEX ( relation_type );

-- xxUSERxx

-- xxCOURSExx
INSERT INTO tool(name,link,image,visibility,admin,address,added_tool,target,category) VALUES ('attendance','attendance/index.php','attendance.gif',0,'0','squaregrey.gif',0,'_self','authoring');
ALTER TABLE course_description ADD COLUMN progress INT  NOT NULL DEFAULT 0 AFTER description_type;
ALTER TABLE item_property ADD id int NOT NULL auto_increment PRIMARY KEY FIRST;
CREATE TABLE attendance_calendar (id int NOT NULL auto_increment, attendance_id int NOT NULL, date_time datetime NOT NULL default '0000-00-00 00:00:00', done_attendance tinyint NOT NULL default 0, PRIMARY KEY(id));
ALTER TABLE attendance_calendar ADD INDEX(attendance_id);
ALTER TABLE attendance_calendar ADD INDEX(done_attendance);
CREATE TABLE attendance_sheet (user_id int NOT NULL, attendance_calendar_id int NOT NULL, presence tinyint NOT NULL DEFAULT 0, PRIMARY KEY(user_id, attendance_calendar_id));
ALTER TABLE attendance_sheet ADD INDEX(presence);
CREATE TABLE attendance_result (id int NOT NULL auto_increment PRIMARY KEY, user_id int NOT NULL, attendance_id int NOT NULL, score int NOT NULL DEFAULT 0);
ALTER TABLE attendance_result ADD INDEX(attendance_id);
ALTER TABLE attendance_result ADD INDEX(user_id);
CREATE TABLE attendance (id int NOT NULL auto_increment PRIMARY KEY, name text NOT NULL, description TEXT NULL, active tinyint NOT NULL default 1, attendance_qualify_title varchar(255) NULL, attendance_qualify_max int NOT NULL default 0, attendance_weight float(6,2) NOT NULL default '0.0', session_id int NOT NULL default 0);
ALTER TABLE attendance ADD INDEX(session_id);
ALTER TABLE attendance ADD INDEX(active);
ALTER TABLE lp_view ADD session_id INT NOT NULL DEFAULT 0;
ALTER TABLE lp_view ADD INDEX(session_id);
INSERT INTO course_setting (variable,value,category) VALUES ('allow_user_view_user_list',1,'user');
ALTER TABLE tool_intro ADD COLUMN session_id INT  NOT NULL DEFAULT 0 AFTER intro_text, DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE(id, session_id);
CREATE TABLE thematic (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR( 255 ) NOT NULL, content TEXT NULL, display_order int unsigned not null default 0, active TINYINT NOT NULL default 0, session_id INT NOT NULL DEFAULT 0);
ALTER TABLE thematic ADD INDEX (active, session_id);  
CREATE TABLE thematic_plan (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, thematic_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NULL, description_type INT NOT NULL); 
ALTER TABLE thematic_plan ADD INDEX (thematic_id, description_type); 
CREATE TABLE thematic_advance (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, thematic_id INT NOT NULL, attendance_id INT NOT NULL DEFAULT 0, content TEXT NOT NULL, start_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', duration INT NOT NULL DEFAULT 0, done_advance tinyint NOT NULL DEFAULT 0);
ALTER TABLE thematic_advance ADD INDEX (thematic_id);
INSERT INTO course_setting (variable,value,category) VALUES ('display_info_advance_inside_homecourse',1,'thematic_advance');
INSERT INTO tool(name, link, image, visibility, admin, address, added_tool, target, category) VALUES ('course_progress','course_progress/index.php','course_progress.gif',0,'0','squaregrey.gif',0,'_self','authoring');
ALTER TABLE lp ADD prerequisite int unsigned NOT NULL DEFAULT 0;
ALTER TABLE student_publication MODIFY COLUMN description TEXT DEFAULT NULL;
ALTER TABLE student_publication ADD COLUMN user_id INTEGER  NOT NULL AFTER session_id;
INSERT INTO course_setting(variable,value,category) VALUES ('email_alert_students_on_new_homework',0,'work');
ALTER TABLE lp ADD COLUMN hide_toc_frame TINYINT NOT NULL DEFAULT 0;

alter table lp_item_view modify column suspend_data longtext;
