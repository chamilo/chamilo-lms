-- This script updates the databases structure before migrating the data from
-- version 1.8.8 (or version 1.8.8.2, 1.8.8.4) to version 1.9.0
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

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('filter_terms', NULL, 'textarea', 'Security', '', 'FilterTermsTitle', 'FilterTermsComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('header_extra_content', NULL, 'textarea', 'Tracking', '', 'HeaderExtraContentTitle', 'HeaderExtraContentComment', NULL, NULL, 1),('footer_extra_content', NULL, 'textarea', 'Tracking', '', 'FooterExtraContentTitle', 'FooterExtraContentComment', NULL, NULL,1);

ALTER TABLE personal_agenda ADD COLUMN all_day INTEGER NOT NULL DEFAULT 0;
ALTER TABLE sys_calendar ADD COLUMN all_day INTEGER NOT NULL DEFAULT 0;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_documents_preview', NULL, 'radio', 'Tools', 'false', 'ShowDocumentPreviewTitle', 'ShowDocumentPreviewComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_documents_preview', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_documents_preview', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('htmlpurifier_wiki',NULL,'radio','Editor','false','HtmlPurifierWikiTitle','HtmlPurifierWikiComment',NULL,NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('htmlpurifier_wiki', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('htmlpurifier_wiki', 'false', 'No');

-- CAS feature
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

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('cas_add_user_activate', NULL, 'radio', 'CAS', 'false', 'CasUserAddActivateTitle', 'CasUserAddActivateComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) values ('cas_add_user_activate', 'platform', 'casAddUserActivatePlatform');
INSERT INTO settings_options (variable, value, display_text) values ('cas_add_user_activate', 'extldap', 'casAddUserActivateLDAP');
INSERT INTO settings_options (variable, value, display_text) values ('cas_add_user_activate', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('update_user_info_cas_with_ldap', NULL, 'radio', 'CAS', 'true', 'UpdateUserInfoCasWithLdapTitle', 'UpdateUserInfoCasWithLdapComment', NULL, NULL, 1, 0, 1);

-- Custom Pages
--INSERT INTO settings_current (variable, type, category, selected_value, title, comment, scope) VALUES ('use_custom_pages','radio','Platform','false','UseCustomPages','UseCustomPagesComment', 'platform');
--INSERT INTO settings_options (variable, value, display_text) VALUES ('use_custom_pages', 'true', 'Yes'), ('use_custom_pages', 'false', 'No');
-- Pages after login by role
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('student_page_after_login', NULL, 'textfield', 'Platform', '', 'StudentPageAfterLoginTitle', 'StudentPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('teacher_page_after_login', NULL, 'textfield', 'Platform', '', 'TeacherPageAfterLoginTitle', 'TeacherPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('drh_page_after_login', NULL, 'textfield', 'Platform', '', 'DRHPageAfterLoginTitle', 'DRHPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('sessionadmin_page_after_login', NULL, 'textfield', 'Session', '', 'SessionAdminPageAfterLoginTitle', 'SessionAdminPageAfterLoginComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('student_autosubscribe', NULL, 'textfield', 'Platform', '', 'StudentAutosubscribeTitle', 'StudentAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('teacher_autosubscribe', NULL, 'textfield', 'Platform', '', 'TeacherAutosubscribeTitle', 'TeacherAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('drh_autosubscribe', NULL, 'textfield', 'Platform', '', 'DRHAutosubscribeTitle', 'DRHAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('sessionadmin_autosubscribe', NULL, 'textfield', 'Session', '', 'SessionadminAutosubscribeTitle', 'SessionadminAutosubscribeComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('show_tabs', 'custom_tab_1', 'checkbox', 'Platform', 'true', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom1', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('show_tabs', 'custom_tab_2', 'checkbox', 'Platform', 'false', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom2', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('show_tabs', 'custom_tab_3', 'checkbox', 'Platform', 'false', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom3', 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('languagePriority1', NULL, 'radio', 'Languages', 'course_lang', 'LanguagePriority1Title', 'LanguagePriority1Comment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('languagePriority2', NULL, 'radio', 'Languages', 'user_profil_lang', 'LanguagePriority2Title', 'LanguagePriority2Comment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('languagePriority3', NULL, 'radio', 'Languages', 'user_selected_lang', 'LanguagePriority3Title', 'LanguagePriority3Comment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('languagePriority4', NULL, 'radio', 'Languages', 'platform_lang', 'LanguagePriority4Title', 'LanguagePriority4Comment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('languagePriority1','platform_lang','PlatformLanguage'), ('languagePriority1','user_profil_lang','UserLanguage'), ('languagePriority1','user_selected_lang','UserSelectedLanguage'), ('languagePriority1','course_lang','CourseLanguage'), ('languagePriority2','platform_lang','PlatformLanguage'), ('languagePriority2','user_profil_lang','UserLanguage'), ('languagePriority2','user_selected_lang','UserSelectedLanguage'), ('languagePriority2','course_lang','CourseLanguage'), ('languagePriority3','platform_lang','PlatformLanguage'), ('languagePriority3','user_profil_lang','UserLanguage'), ('languagePriority3','user_selected_lang','UserSelectedLanguage'), ('languagePriority3','course_lang','CourseLanguage'), ('languagePriority4','platform_lang','PlatformLanguage'), ('languagePriority4','user_profil_lang','UserLanguage'), ('languagePriority4','user_selected_lang','UserSelectedLanguage'), ('languagePriority4','course_lang','CourseLanguage');
-- Email is login
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('login_is_email', NULL, 'radio', 'Platform', 'false', 'LoginIsEmailTitle', 'LoginIsEmailComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('login_is_email','true','Yes'),('login_is_email','false','No') ;
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('scorm_cumulative_session_time', NULL, 'radio', 'Course', 'true', 'ScormCumulativeSessionTimeTitle', 'ScormCumulativeSessionTimeComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('scorm_cumulative_session_time','true','Yes'), ('scorm_cumulative_session_time','false','No');
CREATE TABLE event_type ( id int unsigned NOT NULL AUTO_INCREMENT, name varchar(50) NOT NULL, name_lang_var varchar(50) NOT NULL, desc_lang_var varchar(50) NOT NULL,`extendable_variables` varchar(255) NOT NULL, PRIMARY KEY (id));
ALTER TABLE event_type ADD INDEX ( name );
CREATE TABLE event_type_email_template ( id int unsigned NOT NULL AUTO_INCREMENT, event_type_id int NOT NULL, language_id int NOT NULL, message text NOT NULL, subject varchar(60) NOT NULL, PRIMARY KEY (id));
ALTER TABLE event_type_email_template ADD INDEX ( language_id );
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) values (1, 'already_logged_in','Already logged in',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) values (1, 'update_type','Update script type',0,0);
CREATE TABLE announcement_rel_group (group_id int NOT NULL, announcement_id int NOT NULL, PRIMARY KEY (group_id, announcement_id));
CREATE TABLE group_rel_group ( id int NOT NULL AUTO_INCREMENT, group_id int NOT NULL, subgroup_id int NOT NULL, relation_type int NOT NULL, PRIMARY KEY (id));
ALTER TABLE group_rel_group ADD INDEX ( group_id );
ALTER TABLE group_rel_group ADD INDEX ( subgroup_id );
ALTER TABLE group_rel_group ADD INDEX ( relation_type );


INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_enable_grade_model', NULL, 'radio', 'Gradebook', 'false', 'GradebookEnableGradeModelTitle', 'GradebookEnableGradeModelComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_enable_grade_model', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_enable_grade_model', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('teachers_can_change_score_settings', NULL, 'radio', 'Gradebook', 'true', 'TeachersCanChangeScoreSettingsTitle', 'TeachersCanChangeScoreSettingsComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('teachers_can_change_score_settings', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('teachers_can_change_score_settings', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('teachers_can_change_grade_model_settings', NULL, 'radio', 'Gradebook', 'true', 'TeachersCanChangeGradeModelSettingsTitle', 'TeachersCanChangeGradeModelSettingsComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('teachers_can_change_grade_model_settings', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('teachers_can_change_grade_model_settings', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_locking_enabled', NULL, 'radio', 'Gradebook', 'false', 'GradebookEnableLockingTitle', 'GradebookEnableLockingComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_locking_enabled', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_locking_enabled', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_users_to_change_email_with_no_password', NULL, 'radio', 'User', 'false', 'AllowUsersToChangeEmailWithNoPasswordTitle', 'AllowUsersToChangeEmailWithNoPasswordComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_users_to_change_email_with_no_password', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_users_to_change_email_with_no_password', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_session_admins_to_see_all_sessions', NULL, 'radio', 'Session', 'false', 'AllowSessionAdminsToSeeAllSessionsTitle', 'AllowSessionAdminsToSeeAllSessionsComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_session_admins_to_see_all_sessions', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_session_admins_to_see_all_sessions', 'false', 'No');

-- Shibboleth and Facebook auth and ldap
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('shibboleth_description', NULL, 'radio', 'Shibboleth', 'false', 'ShibbolethMainActivateTitle', 'ShibbolethMainActivateComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, accessurl_changeable) VALUES ('facebook_description', NULL, 'radio', 'Facebook', 'false', 'FacebookMainActivateTitle', 'FacebookMainActivateComment', NULL, NULL, 0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, accessurl_changeable) VALUES ('ldap_description', NULL, 'radio', 'LDAP', NULL, 'LdapDescriptionTitle', 'LdapDescriptionComment', NULL, NULL, 1, 0, 1);

ALTER TABLE course_rel_user ADD COLUMN legal_agreement INTEGER DEFAULT 0;
ALTER TABLE session_rel_course_rel_user ADD COLUMN legal_agreement INTEGER DEFAULT 0;
ALTER TABLE course ADD COLUMN legal TEXT NOT NULL;
ALTER TABLE course ADD COLUMN activate_legal INT NOT NULL DEFAULT 0;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enable_help_link', NULL, 'radio', 'Platform', 'true', 'EnableHelpLinkTitle', 'EnableHelpLinkComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_help_link', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_help_link', 'false', 'No');

ALTER TABLE gradebook_category MODIFY COLUMN weight FLOAT NOT NULL;
ALTER TABLE gradebook_link MODIFY COLUMN weight FLOAT  NOT NULL;
ALTER TABLE gradebook_link ADD COLUMN locked INT DEFAULT 0;
ALTER TABLE gradebook_category ADD COLUMN locked INT DEFAULT 0;

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_hr_skills_management', NULL, 'radio', 'Gradebook', 'true', 'AllowHRSkillsManagementTitle', 'AllowHRSkillsManagementComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_hr_skills_management', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_hr_skills_management', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_admin_toolbar', NULL, 'radio', 'Platform', 'show_to_admin', 'ShowAdminToolbarTitle', 'ShowAdminToolbarComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_admin_toolbar', 'do_not_show', 'DoNotShow');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_admin_toolbar', 'show_to_admin', 'ShowToAdminsOnly');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_admin_toolbar', 'show_to_admin_and_teachers', 'ShowToAdminsAndTeachers');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_admin_toolbar', 'show_to_all', 'ShowToAllUsers');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_global_chat', NULL, 'radio', 'Platform', 'true', 'AllowGlobalChatTitle', 'AllowGlobalChatComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_global_chat', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_global_chat', 'false', 'No');

CREATE TABLE chat (id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT, from_user INTEGER, to_user INTEGER, message TEXT NOT NULL, sent DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', recd INTEGER UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (id));
ALTER TABLE chat ADD INDEX idx_chat_to_user (to_user);
ALTER TABLE chat ADD INDEX idx_chat_from_user (from_user);

INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'google_calendar_url','Google Calendar URL',0,0);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('courses_default_creation_visibility', NULL, 'radio', 'Course', '2', 'CoursesDefaultCreationVisibilityTitle', 'CoursesDefaultCreationVisibilityComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('courses_default_creation_visibility', '3', 'OpenToTheWorld');
INSERT INTO settings_options (variable, value, display_text) VALUES ('courses_default_creation_visibility', '2', 'OpenToThePlatform');
INSERT INTO settings_options (variable, value, display_text) VALUES ('courses_default_creation_visibility', '1', 'Private');
INSERT INTO settings_options (variable, value, display_text) VALUES ('courses_default_creation_visibility', '0', 'CourseVisibilityClosed');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_browser_sniffer', NULL, 'radio', 'Tuning', 'false', 'AllowBrowserSnifferTitle', 'AllowBrowserSnifferComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_browser_sniffer', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_browser_sniffer', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enable_wami_record', NULL, 'radio', 'Tools', 'false', 'EnableWamiRecordTitle', 'EnableWamiRecordComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_wami_record', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_wami_record', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_public_certificates', NULL, 'radio', 'Course', 'false', 'AllowPublicCertificatesTitle', 'AllowPublicCertificatesComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_public_certificates', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_public_certificates', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('enable_iframe_inclusion', NULL, 'radio', 'Editor', 'false', 'EnableIframeInclusionTitle', 'EnableIframeInclusionComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_iframe_inclusion', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_iframe_inclusion', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_hot_courses', NULL, 'radio', 'Platform', 'true', 'ShowHotCoursesTitle', 'ShowHotCoursesComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_hot_courses', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_hot_courses', 'false', 'No');


INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_default_weight', NULL, 'textfield', 'Gradebook', '100', 'GradebookDefaultWeightTitle', 'GradebookDefaultWeightComment', NULL, NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('gradebook_default_grade_model_id', NULL, 'select', 'Gradebook', '', 'GradebookDefaultGradeModelTitle', 'GradebookDefaultGradeModelComment', NULL, NULL, 1);

UPDATE settings_current SET category = 'Session' WHERE variable IN ('show_tutor_data', 'use_session_mode', 'add_users_by_coach', 'show_session_coach', 'show_session_data', 'allow_coach_to_edit_course_session','hide_courses_in_sessions', 'show_groups_to_users');

INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available) VALUES ('&#2476;&#2494;&#2434;&#2482;&#2494;','bengali','bn','bengali',0), ('&#1575;&#1604;&#1589;&#1608;&#1605;&#1575;&#1604;&#1610;&#1577;','somali','so','somali',0);

-- Course ranking
CREATE TABLE track_course_ranking (id   int unsigned not null PRIMARY KEY AUTO_INCREMENT, c_id  int unsigned not null, session_id  int unsigned not null default 0, url_id  int unsigned not null default 0, accesses int unsigned not null default 0, total_score int unsigned not null default 0, users int unsigned not null default 0, creation_date datetime not null);

ALTER TABLE track_course_ranking ADD INDEX idx_tcc_cid (c_id);
ALTER TABLE track_course_ranking ADD INDEX idx_tcc_sid (session_id);
ALTER TABLE track_course_ranking ADD INDEX idx_tcc_urlid (url_id);
ALTER TABLE track_course_ranking ADD INDEX idx_tcc_creation_date (creation_date);

CREATE TABLE user_rel_course_vote ( id int unsigned not null AUTO_INCREMENT PRIMARY KEY,  c_id int unsigned not null,  user_id int unsigned not null, session_id int unsigned not null default 0,  url_id int unsigned not null default 0, vote int unsigned not null default 0);

ALTER TABLE user_rel_course_vote ADD INDEX idx_ucv_cid (c_id);
ALTER TABLE user_rel_course_vote ADD INDEX idx_ucv_uid (user_id);
ALTER TABLE user_rel_course_vote ADD INDEX idx_ucv_cuid (user_id, c_id);

ALTER TABLE track_e_default  MODIFY COLUMN default_value TEXT;

--User chat status
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'user_chat_status','User chat status', 0, 0);
UPDATE settings_current SET selected_value = 'true' WHERE variable = 'more_buttons_maximized_mode';

--Grade model
CREATE TABLE grade_model (id INTEGER  NOT NULL AUTO_INCREMENT, name VARCHAR(255)  NOT NULL, description TEXT , PRIMARY KEY (id));
CREATE TABLE grade_components (id INTEGER  NOT NULL AUTO_INCREMENT, percentage VARCHAR(255)  NOT NULL, title VARCHAR(255)  NOT NULL, acronym VARCHAR(255)  NOT NULL, grade_model_id INTEGER NOT NULL, PRIMARY KEY (id));
ALTER TABLE gradebook_category ADD COLUMN grade_model_id INT DEFAULT 0;
UPDATE settings_current SET title = 'DatabaseVersion' WHERE variable = 'chamilo_database_version';
ALTER TABLE gradebook_evaluation MODIFY COLUMN weight FLOAT NOT NULL;

INSERT INTO settings_options(variable,value,display_text) VALUES ('page_after_login', 'main/auth/courses.php', 'CourseCatalog');

ALTER TABLE settings_current ADD COLUMN access_url_locked INTEGER NOT NULL DEFAULT 0;

-- Event mail

CREATE TABLE event_email_template (  id int(11) NOT NULL AUTO_INCREMENT,  message text,  subject varchar(255) DEFAULT NULL,  event_type_name varchar(255) DEFAULT NULL,  activated tinyint(4) NOT NULL DEFAULT '0',  language_id int(11) DEFAULT NULL,  PRIMARY KEY (id));
ALTER TABLE event_email_template ADD INDEX event_name_index (event_type_name);
CREATE TABLE event_sent (  id int(11) NOT NULL AUTO_INCREMENT,  user_from int(11) NOT NULL,  user_to int(11) DEFAULT NULL,  event_type_name varchar(100) DEFAULT NULL,  PRIMARY KEY (`id`));ALTER TABLE event_sent ADD INDEX event_name_index (event_type_name);
CREATE TABLE user_rel_event_type (  id int(11) NOT NULL AUTO_INCREMENT,  user_id int(11) NOT NULL,  event_type_name varchar(255) NOT NULL,  PRIMARY KEY (`id`));ALTER TABLE user_rel_event_type ADD INDEX event_name_index (event_type_name);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('activate_email_template', NULL, 'radio', 'Platform', 'false', 'ActivateEmailTemplateTitle', 'ActivateEmailTemplateComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('activate_email_template', 'true', 'Yes'),('activate_email_template', 'false', 'No');

-- Skills

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_skills_tool', NULL, 'radio', 'Platform', 'false', 'AllowSkillsToolTitle', 'AllowSkillsToolComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_skills_tool', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_skills_tool', 'false', 'No');

CREATE TABLE IF NOT EXISTS skill ( id int NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL, short_code varchar(100) NOT NULL, description TEXT NOT NULL, access_url_id int NOT NULL, icon varchar(255) NOT NULL, PRIMARY KEY (id));
INSERT INTO skill (name) VALUES ('Root');

CREATE TABLE IF NOT EXISTS skill_rel_gradebook ( id int NOT NULL AUTO_INCREMENT, gradebook_id int NOT NULL, skill_id int NOT NULL, type varchar(10) NOT NULL, PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS skill_rel_skill (id int NOT NULL AUTO_INCREMENT, skill_id int NOT NULL, parent_id int NOT NULL, relation_type int NOT NULL, level int NOT NULL, PRIMARY KEY (id));
INSERT INTO skill_rel_skill VALUES(1, 1, 0, 0, 0);

CREATE TABLE IF NOT EXISTS skill_rel_user ( id int NOT NULL AUTO_INCREMENT, user_id int NOT NULL, skill_id int NOT NULL, acquired_skill_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',assigned_by int NOT NULL,PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS skill_profile ( id INTEGER  NOT NULL AUTO_INCREMENT, name VARCHAR(255)  NOT NULL, description TEXT  NOT NULL, PRIMARY KEY (id));
CREATE TABLE IF NOT EXISTS skill_rel_profile ( id INTEGER  NOT NULL AUTO_INCREMENT, skill_id INTEGER  NOT NULL, profile_id INTEGER  NOT NULL, PRIMARY KEY (id));

-- Removing use_document_title
DELETE FROM settings_current WHERE variable = 'use_document_title';
DELETE FROM settings_options WHERE variable = 'use_document_title';

ALTER TABLE course MODIFY COLUMN disk_quota bigint unsigned DEFAULT NULL;
ALTER TABLE user MODIFY COLUMN username VARCHAR(100) NOT NULL;

UPDATE language SET english_name = 'basque' , dokeos_folder = 'basque' where english_name = 'euskera';
UPDATE language SET english_name = 'turkish', dokeos_folder = 'turkish' where english_name = 'turkce';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES('platform_unsubscribe_allowed', NULL, 'radio', 'Platform', 'false', 'PlatformUnsubscribeTitle', 'PlatformUnsubscribeComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) values ('platform_unsubscribe_allowed', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) values ('platform_unsubscribe_allowed', 'false', 'No');

DROP TABLE IF EXISTS access_url_rel_session;

ALTER TABLE usergroup_rel_session   ADD COLUMN id INTEGER NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id);
ALTER TABLE usergroup_rel_course    ADD COLUMN id INTEGER NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id);
ALTER TABLE usergroup_rel_user      ADD COLUMN id INTEGER NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id);
ALTER TABLE admin                   ADD COLUMN id INTEGER NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id);

-- Remove settings entry that doesnt exist anymore

DELETE FROM settings_current WHERE variable = "read_more_limit";
DELETE FROM settings_current WHERE variable = "user_order_by";
DELETE FROM settings_options WHERE variable = "user_order_by";

-- Do not move this query
UPDATE settings_current SET selected_value = '1.9.0.18189' WHERE variable = 'chamilo_database_version';

-- xxSTATSxx
ALTER TABLE track_e_exercices ADD COLUMN questions_to_check TEXT NOT NULL DEFAULT '';
--CREATE TABLE track_filtered_terms (id int, user_id int, course_id int, session_id int, tool_id char(12), filtered_term varchar(255), created_at datetime);
CREATE TABLE stored_values (user_id INT NOT NULL, sco_id INT NOT NULL, course_id CHAR(40) NOT NULL, sv_key CHAR(64) NOT NULL, sv_value TEXT NOT NULL );
ALTER TABLE stored_values ADD KEY (user_id, sco_id, course_id, sv_key);
ALTER TABLE stored_values ADD UNIQUE (user_id, sco_id, course_id, sv_key);
CREATE TABLE stored_values_stack (user_id INT NOT NULL, sco_id INT NOT NULL, stack_order INT NOT NULL, course_id CHAR(40) NOT NULL, sv_key CHAR(64) NOT NULL, sv_value TEXT NOT NULL );
ALTER TABLE stored_values_stack ADD KEY (user_id, sco_id, course_id, sv_key, stack_order);
ALTER TABLE stored_values_stack ADD UNIQUE (user_id, sco_id, course_id, sv_key, stack_order);

ALTER TABLE track_e_attempt ADD COLUMN filename VARCHAR(255) DEFAULT NULL;
ALTER TABLE track_e_default ADD COLUMN c_id INTEGER DEFAULT NULL;

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE lp ADD COLUMN hide_toc_frame INT NOT NULL DEFAULT 0;
ALTER TABLE lp ADD COLUMN seriousgame_mode INT NOT NULL DEFAULT 0;
ALTER TABLE lp_item_view modify column suspend_data longtext;
ALTER TABLE quiz ADD COLUMN review_answers INT NOT NULL DEFAULT 0;
ALTER TABLE student_publication ADD COLUMN contains_file INTEGER NOT NULL DEFAULT 1;
ALTER TABLE student_publication ADD COLUMN allow_text_assignment INTEGER NOT NULL DEFAULT 0;
ALTER TABLE quiz ADD COLUMN random_by_category INT NOT NULL DEFAULT 0;
ALTER TABLE quiz ADD COLUMN text_when_finished TEXT DEFAULT NULL;
ALTER TABLE quiz ADD COLUMN display_category_name INT NOT NULL DEFAULT 1;
ALTER TABLE quiz ADD COLUMN pass_percentage INT DEFAULT NULL;

INSERT INTO course_setting(variable,value,category) VALUES ('allow_public_certificates', 0, 'certificates');

ALTER TABLE user_api_key ADD COLUMN api_end_point text DEFAULT NULL;
ALTER TABLE user_api_key ADD COLUMN created_date datetime DEFAULT NULL;
ALTER TABLE user_api_key ADD COLUMN validity_start_date datetime DEFAULT NULL;
ALTER TABLE user_api_key ADD COLUMN validity_end_date datetime DEFAULT NULL;
ALTER TABLE user_api_key ADD COLUMN description text DEFAULT NULL;