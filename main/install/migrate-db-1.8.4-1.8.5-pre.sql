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
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('openid_authentication', NULL, 'radio', 'Security', 'false', 'OpenIdAuthentication', 'OpenIdAuthenticationComment', NULL, NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('openid_authentication', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('openid_authentication', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('profile','openid','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'OpenIDURL');

CREATE TABLE templates (id int NOT NULL auto_increment, title varchar(100) NOT NULL, description varchar(250) NOT NULL, course_code varchar(40) NOT NULL, user_id int NOT NULL, ref_doc int NOT NULL, PRIMARY KEY (id));
ALTER TABLE user ADD openid varchar(255) DEFAULT NULL;
ALTER TABLE user ADD INDEX (openid(50));
CREATE TABLE IF NOT EXISTS openid_association (id int NOT NULL auto_increment,idp_endpoint_uri text NOT NULL,session_type varchar(30) NOT NULL,assoc_handle text NOT NULL,assoc_type text NOT NULL,expires_in bigint NOT NULL,mac_key text NOT NULL,created bigint NOT NULL,PRIMARY KEY (id));
CREATE TABLE gradebook_category (  id int NOT NULL auto_increment,  name text NOT NULL,  description text,  user_id int NOT NULL,  course_code varchar(40) default NULL,  parent_id int default NULL,  weight smallint NOT NULL,  visible tinyint NOT NULL, certif_min_score int DEFAULT NULL,  PRIMARY KEY  (id));
CREATE TABLE gradebook_evaluation (  id int unsigned NOT NULL auto_increment,  name text NOT NULL,  description text,  user_id int NOT NULL,  course_code varchar(40) default NULL,  category_id int default NULL,  date int default 0,  weight smallint NOT NULL,  max float unsigned NOT NULL,  visible tinyint NOT NULL,  PRIMARY KEY  (id));
CREATE TABLE gradebook_link (  id int NOT NULL auto_increment,  type int NOT NULL,  ref_id int NOT NULL,  user_id int NOT NULL,  course_code varchar(40) NOT NULL,  category_id int NOT NULL,  date int default NULL,  weight smallint NOT NULL,  visible tinyint NOT NULL,  PRIMARY KEY  (id));
CREATE TABLE gradebook_result (  id int NOT NULL auto_increment,  user_id int NOT NULL,  evaluation_id int NOT NULL,  date int NOT NULL,  score float unsigned default NULL,  PRIMARY KEY  (id));
CREATE TABLE gradebook_score_display (  id int NOT NULL auto_increment,  score float unsigned NOT NULL,  display varchar(40) NOT NULL,  PRIMARY KEY (id));
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('gradebook_enable', NULL, 'radio', 'Gradebook', 'false', 'GradebookActivation', 'GradebookActivationComment', NULL, NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_enable', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('gradebook_enable', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('show_tabs','my_gradebook','checkbox','Platform','true','ShowTabsTitle','ShowTabsComment',NULL,'TabsMyGradebook');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('gradebook_score_display_coloring', 'my_display_coloring', 'checkbox', 'Gradebook', 'false', 'GradebookScoreDisplayColoring', 'GradebookScoreDisplayColoringComment', NULL, 'TabsGradebookEnableColoring');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('gradebook_score_display_custom', 'my_display_custom', 'checkbox', 'Gradebook', 'false', 'GradebookScoreDisplayCustom', 'GradebookScoreDisplayCustomComment', NULL, 'TabsGradebookEnableCustom');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('gradebook_score_display_colorsplit', NULL, 'textfield', 'Gradebook', '50', 'GradebookScoreDisplayColorSplit', 'GradebookScoreDisplayColorSplitComment', NULL, NULL);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('gradebook_score_display_upperlimit', 'my_display_upperlimit', 'checkbox', 'Gradebook', 'false', 'GradebookScoreDisplayUpperLimit', 'GradebookScoreDisplayUpperLimitComment', NULL, 'TabsGradebookEnableUpperLimit');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('service_ppt2lp', 'port', 'checkbox', NULL, '2002', 'Port', NULL, NULL, NULL);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('user_selected_theme',NULL,'radio','Platform','false','UserThemeSelection','UserThemeSelectionComment',NULL,NULL);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('profile','theme','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'UserTheme');
INSERT INTO settings_options (variable, value, display_text) VALUES ('user_selected_theme', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('user_selected_theme', 'false', 'No');
ALTER TABLE user ADD theme varchar(255) DEFAULT NULL;
UPDATE settings_current SET variable='service_visio', subkey='active'    , title='VisioEnable' WHERE variable='service_visio' AND subkey='active';
UPDATE settings_current SET variable='service_visio', subkey='visio_host', title='VisioHost' WHERE variable='service_visio' AND subkey='visio_rtmp_host_local';
UPDATE settings_current SET variable='service_visio', subkey='visio_port', title='VisioPort' WHERE variable='service_visio' AND subkey='visio_rtmp_port';
UPDATE settings_current SET variable='service_visio', subkey='visio_pass', title='VisioPassword', type='textfield' WHERE variable='service_visio' AND subkey='visio_rtmp_tunnel_port';
DELETE FROM settings_options WHERE variable = 'visio_rtmp_host_local';
DELETE FROM settings_current WHERE variable='service_visio' AND subkey='visioconference_url';
DELETE FROM settings_current WHERE variable='service_visio' AND subkey='visioclassroom_url';
DELETE FROM settings_current WHERE variable='service_visio' AND subkey='visio_is_web_rtmp';
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('allow_course_theme',NULL,'radio','Course','true','AllowCourseThemeTitle','AllowCourseThemeComment',NULL,NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_course_theme', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_course_theme', 'false', 'No');

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
ALTER TABLE track_e_attempt ADD tms datetime not null default '0000-00-00 00:00:00';

-- xxUSERxx

-- xxCOURSExx
CREATE TABLE lp_iv_objective(id bigint unsigned primary key auto_increment, lp_iv_id bigint unsigned not null, order_id smallint unsigned not null default 0, objective_id	varchar(255) not null default '', score_raw		float unsigned not null default 0, score_max		float unsigned not null default 0, score_min		float unsigned not null default 0, status char(32) not null default 'not attempted');
ALTER TABLE lp_iv_objective ADD INDEX (lp_iv_id);
ALTER TABLE lp_item CHANGE prerequisite prerequisite TEXT DEFAULT NULL;
INSERT INTO course_setting(variable,value,category) VALUES ('email_alert_manager_on_new_quiz',0,'quiz');
ALTER TABLE dropbox_post ADD session_id SMALLINT UNSIGNED NOT NULL ;
ALTER TABLE dropbox_post ADD INDEX ( session_id ) ;
ALTER TABLE dropbox_file ADD session_id SMALLINT UNSIGNED NOT NULL ;
ALTER TABLE dropbox_file ADD INDEX ( session_id ) ;
ALTER TABLE item_property ADD INDEX (tool,ref);
ALTER TABLE forum_forum ADD session_id SMALLINT UNSIGNED  DEFAULT 0 ;
INSERT INTO course_setting(variable,value,category) VALUES ('allow_user_image_forum',1,'forum');
INSERT INTO course_setting(variable,value,category) VALUES ('course_theme','','theme');
