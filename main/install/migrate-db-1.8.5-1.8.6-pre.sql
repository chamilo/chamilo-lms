-- This script updates the databases structure before migrating the data from
-- version 1.8.5 to version 1.8.6
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
ALTER TABLE settings_current ADD INDEX unique_setting (variable,subkey,category);
ALTER TABLE settings_options ADD INDEX unique_setting_option (variable,value);
INSERT INTO settings_current (variable, subkey,type,category,selected_value,title,comment,scope,subkeytext)VALUES ('registration', 'phone', 'textfield', 'User', 'false', 'RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment', NULL, 'Phone');
ALTER TABLE php_session CHANGE session_value session_value MEDIUMTEXT NOT NULL;
INSERT INTO settings_current (variable, subkey,type,category,selected_value,title,comment,scope,subkeytext)VALUES ('add_users_by_coach',NULL,'radio','Platform','false','AddUsersByCoachTitle','AddUsersByCoachComment',NULL,NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('add_users_by_coach', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('add_users_by_coach', 'false', 'No');
ALTER TABLE session ADD nb_days_access_before_beginning TINYINT NULL DEFAULT '0' AFTER date_end , ADD nb_days_access_after_end TINYINT NULL DEFAULT '0' AFTER nb_days_access_before_beginning ;
ALTER TABLE course_rel_user ADD INDEX (user_id);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable) VALUES ('course_create_active_tools', 'wiki', 'checkbox', 'Tools', 'true', 'CourseCreateActiveToolsTitle', 'CourseCreateActiveToolsComment', NULL, 'Wiki', 1, 0);

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE lp ADD theme varchar(255) not null default '';
ALTER TABLE survey ADD mail_subject VARCHAR( 255 ) NOT NULL AFTER reminder_mail ;
ALTER TABLE quiz_rel_question ADD question_order mediumint unsigned NOT NULL default 1;
ALTER TABLE quiz ADD max_attempt int NOT NULL default 0;
ALTER TABLE survey ADD one_question_per_page bool NOT NULL default 0;
ALTER TABLE survey ADD shuffle bool NOT NULL default 0;
ALTER TABLE survey ADD survey_version varchar(255) NOT NULL default '';
ALTER TABLE survey ADD parent_id int NOT NULL default 0;
ALTER TABLE survey ADD survey_type int NOT NULL default 0;
ALTER TABLE survey_question ADD survey_group_pri int unsigned NOT NULL default 0;
ALTER TABLE survey_question ADD survey_group_sec1 int unsigned NOT NULL default 0;
ALTER TABLE survey_question ADD survey_group_sec2 int unsigned NOT NULL default 0;
CREATE TABLE survey_group (  id int unsigned NOT NULL auto_increment, name varchar(20) NOT NULL, description varchar(255) NOT NULL,  survey_id int unsigned NOT NULL, PRIMARY KEY  (id) );
ALTER TABLE survey_question_option ADD value int NOT NULL default 0;
UPDATE tool SET category = 'interaction' WHERE name = 'forum';
ALTER TABLE survey ADD show_form_profile int NOT NULL default 0;
ALTER TABLE survey ADD form_fields TEXT NOT NULL;
ALTER TABLE quiz_answer CHANGE hotspot_type hotspot_type ENUM( 'square', 'circle', 'poly', 'delineation' ) NULL DEFAULT NULL;
ALTER TABLE forum_forum ADD forum_image varchar(255) NOT NULL default '';
ALTER TABLE lp ADD preview_image varchar(255) NOT NULL default '';
ALTER TABLE lp ADD author varchar(255) NOT NULL default '';
ALTER TABLE lp_item ADD terms TEXT NULL;
ALTER TABLE lp_item ADD search_did INT NULL;
CREATE TABLE wiki (id int NOT NULL auto_increment, reflink varchar(250) NOT NULL default 'index', title text NOT NULL, content text NOT NULL, user_id int NOT NULL default 0, group_id int default NULL, timestamp timestamp NOT NULL, addlock int NOT NULL default 1, editlock int NOT NULL default 0, visibility int NOT NULL default 1, addlock_disc int NOT NULL default 1, visibility_disc int NOT NULL default 1, ratinglock_disc int NOT NULL default 1, assignment int NOT NULL default 0, startdate_assig datetime NOT NULL default '0000-00-00 00:00:00', enddate_assig datetime NOT NULL default '0000-00-00 00:00:00', delayedsubmit int NOT NULL default 0, comment text NOT NULL, progress text NOT NULL, score int default 0, version int default NULL, hits int default 0, linksto text NOT NULL, tag text NOT NULL, user_ip varchar(39) NOT NULL, PRIMARY KEY  (id) );
INSERT INTO tool(name,link,image,visibility,admin,address,added_tool,target,category) VALUES ('wiki','wiki/index.php','wiki.gif',0,'1','squaregrey.gif',0,'_self','interaction');
ALTER TABLE group_category ADD COLUMN wiki_state tinyint unsigned NOT NULL default 1;
ALTER TABLE group_info ADD COLUMN wiki_state enum('0','1','2') NOT NULL default '0';
ALTER TABLE announcement ADD session_id SMALLINT UNSIGNED NOT NULL;
ALTER TABLE announcement ADD INDEX ( session_id ) ;
ALTER TABLE forum_category ADD session_id SMALLINT UNSIGNED NOT NULL ;
ALTER TABLE forum_category ADD INDEX ( session_id ) ;
ALTER TABLE student_publication ADD session_id SMALLINT UNSIGNED NOT NULL default 0 ;
ALTER TABLE student_publication ADD INDEX ( session_id ) ;
ALTER TABLE calendar_event ADD session_id SMALLINT UNSIGNED NOT NULL default 0 ;
ALTER TABLE calendar_event ADD INDEX ( session_id ) ;
ALTER TABLE group_info ADD session_id SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE group_info ADD INDEX ( session_id ) ;
ALTER TABLE survey ADD session_id SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE survey ADD INDEX ( session_id ) ;
CREATE TABLE wiki_discuss (id int NOT NULL auto_increment, publication_id int NOT NULL default 0, userc_id int NOT NULL default 0, comment text NOT NULL, p_score varchar(255) default NULL, timestamp timestamp(14) NOT NULL, PRIMARY KEY  (id) );
CREATE TABLE wiki_mailcue (id int NOT NULL, user_id int NOT NULL, type text NOT NULL, group_id int DEFAULT NULL, KEY  (id) );
ALTER TABLE lp_item ADD audio VARCHAR(250);
CREATE TABLE wiki_conf (id int NOT NULL auto_increment, feedback1 text NOT NULL, feedback2 text NOT NULL, feedback3 text NOT NULL, max_size int default NULL, max_text int default NULL, allow_attachments int default NULL, PRIMARY KEY  (id) );