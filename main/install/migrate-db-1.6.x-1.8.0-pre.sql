-- This script updates the databases structure before migrating the data from
-- version 1.6.x to version 1.8.0
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
ALTER TABLE admin 		CHANGE user_id 	user_id 	int unsigned NOT NULL default '0';
ALTER TABLE class_user 	CHANGE class_id class_id 	mediumint unsigned NOT NULL default '0';
ALTER TABLE class_user 	CHANGE user_id 	user_id 	int unsigned NOT NULL default '0';
ALTER TABLE course 		ADD registration_code		varchar(255) NOT NULL default '';
ALTER TABLE course_rel_class CHANGE class_id class_id mediumint unsigned NOT NULL default '0';
ALTER TABLE course_rel_user CHANGE user_id user_id int unsigned NOT NULL default '0';
-- Rename table session into php_session
RENAME TABLE sess TO php_session; 
-- We might want to review the following table structure -- 
CREATE TABLE session (id smallint unsigned NOT NULL auto_increment, id_coach int unsigned NOT NULL default '0', name char(50) NOT NULL default '', nbr_courses smallint unsigned NOT NULL default '0', nbr_users mediumint unsigned NOT NULL default '0', nbr_classes mediumint unsigned NOT NULL default '0', date_start date NOT NULL default '0000-00-00', date_end date NOT NULL default '0000-00-00', PRIMARY KEY  (id),  UNIQUE KEY name (name));
-- We might want to review the following table structure -- 
CREATE TABLE session_rel_course(id_session smallint unsigned NOT NULL default '0', course_code char(40) NOT NULL default '', id_coach int unsigned NOT NULL default '0', nbr_users smallint(5) unsigned NOT NULL default '0', PRIMARY KEY  (id_session,course_code), KEY course_code (course_code));
-- We might want to review the following table structure -- 
CREATE TABLE session_rel_course_rel_user(id_session smallint unsigned NOT NULL default '0', course_code char(40) NOT NULL default '', id_user int unsigned NOT NULL default '0', PRIMARY KEY  (id_session,course_code,id_user), KEY id_user (id_user), KEY course_code (course_code));
-- We might want to review the following table structure -- 
CREATE TABLE session_rel_user(id_session mediumint unsigned NOT NULL default '0', id_user mediumint unsigned NOT NULL default '0', PRIMARY KEY  (id_session,id_user));
-- We might want to review the following table structure -- 
CREATE TABLE course_rel_survey (id int NOT NULL auto_increment, course_code varchar(200) default NULL, db_name varchar(200) default NULL,  survey_id varchar(200) default NULL,  PRIMARY KEY  (id));
-- We might want to review the following table structure -- 
CREATE TABLE survey_reminder(sid int NOT NULL default '0', db_name varchar(100) NOT NULL default '', email varchar(100) NOT NULL default '', access int NOT NULL default '0', subject text NOT NULL, content text NOT NULL, reminder_choice int NOT NULL default '0', reminder_time text NOT NULL, avail_till date NOT NULL default '0000-00-00');
-- We might want to review the following table structure -- 
CREATE TABLE survey_user_info(id int NOT NULL auto_increment, user_id int NOT NULL default '0', survey_id int NOT NULL default '0', db_name varchar(200) default NULL, firstname varchar(200) default NULL, lastname varchar(200) default NULL, email varchar(200) default NULL, organization  text, age int default NULL, registered char(1) default NULL, attempted varchar(10) NOT NULL default '', PRIMARY KEY (id));
-- ALTER TABLE sys_announcement CHANGE visible_student visible_student enum('0','1') NOT NULL default '0';
-- ALTER TABLE sys_announcement CHANGE visible_guest visible_guest enum('0','1') NOT NULL default '0';
ALTER TABLE sys_announcement ADD lang varchar(70) NOT NULL default '';
ALTER TABLE user CHANGE auth_source auth_source varchar(50) default 'platform';
ALTER TABLE user ADD language varchar(40) default NULL;
ALTER TABLE user ADD registration_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE user ADD expiration_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE user ADD active enum('0','1') NOT NULL default '1';
-- xxSTATSxx
CREATE TABLE track_e_attempt(exe_id int default NULL, user_id int NOT NULL default '0', question_id int NOT NULL default '0', answer text NOT NULL, teacher_comment text NOT NULL, marks int NOT NULL default '0', course_code varchar(40) NOT NULL default '', position int default '0');
CREATE TABLE track_e_course_access(course_access_id int NOT NULL auto_increment, course_code varchar(40) NOT NULL, user_id int NOT NULL, login_course_date datetime NOT NULL default '0000-00-00 00:00:00', logout_course_date datetime default NULL, counter int NOT NULL, PRIMARY KEY (course_access_id));
ALTER TABLE track_e_lastaccess CHANGE access_id access_id bigint NOT NULL auto_increment;
ALTER TABLE track_e_lastaccess ADD access_session_id int unsigned default NULL;
ALTER TABLE track_e_login ADD logout_date datetime NULL default NULL;
ALTER TABLE track_e_online ADD course varchar(40) default NULL;
-- xxUSERxx
ALTER TABLE user_course_category ADD sort int;
-- xxSCORMxx
-- xxCOURSExx
ALTER TABLE announcement CHANGE content content mediumtext;
ALTER TABLE announcement ADD email_sent tinyint;
CREATE TABLE blogs(blog_id smallint NOT NULL AUTO_INCREMENT , blog_name varchar(250) NOT NULL default '', blog_subtitle varchar( 250 ) default NULL , date_creation datetime NOT NULL default '0000-00-00 00:00:00', visibility enum( '0', '1' ) NOT NULL default '0', PRIMARY KEY (blog_id));
CREATE TABLE blogs_comments(comment_id int NOT NULL AUTO_INCREMENT , title varchar(250) NOT NULL default '', comment longtext NOT NULL , author_id int NOT NULL default '0', date_creation datetime NOT NULL default '0000-00-00 00:00:00', blog_id mediumint NOT NULL default '0', post_id int NOT NULL default '0', task_id int default NULL , parent_comment_id int NOT NULL default '0', PRIMARY KEY (comment_id));
CREATE TABLE blogs_posts();
CREATE TABLE blogs_rating();
CREATE TABLE blogs_rel_user();
CREATE TABLE blogs_tasks();
CREATE TABLE blogs_tasks_rel_user();
CREATE TABLE course_setting();
CREATE TABLE dropbox_category();
CREATE TABLE dropbox_feedback();
CREATE TABLE forum_category();
CREATE TABLE forum_forum();
CREATE TABLE forum_mailcue();
CREATE TABLE forum_post();
CREATE TABLE forum_thread();
ALTER TABLE group_category DROP COLUMN forum_state
ALTER TABLE group_category ADD COLUMN calendar_state...
ALTER TABLE group_category ADD COLUMN work_state...
ALTER TABLE group_category ADD COLUMN announcements_state...
ALTER TABLE group_info DROP COLUMN tutor_id
ALTER TABLE group_info DROP COLUMN forum_state
ALTER TABLE group_info DROP COLUMN forum_id
ALTER TABLE group_info SET COLUMN secret_directory Type
ALTER TABLE group_info ADD COLUMN calendar_state...
ALTER TABLE group_info ADD COLUMN work_state...
ALTER TABLE group_info ADD COLUMN announcements_state...
CREATE TABLE group_rel_tutor();
CREATE TABLE lp();
CREATE TABLE lp_item();
CREATE TABLE lp_item_view();
CREATE TABLE lp_iv_interaction();
CREATE TABLE lp_view();
CREATE TABLE permission_group();
CREATE TABLE permission_task();
CREATE TABLE permission_user();
CREATE TABLE questions();
ALTER TABLE quiz_answer ADD COLUMN hotspot_coordinates...
ALTER TABLE quiz_answer ADD COLUMN hotspot_type...
CREATE TABLE role();
CREATE TABLE role_group();
CREATE TABLE role_permissions();
CREATE TABLE role_user();
ALTER TABLE student_publication ADD COLUMN post_group_id...
CREATE TABLE survey();
CREATE TABLE survey_group();
CREATE TABLE survey_report();
ALTER TABLE tool ADD COLUMN category...
DROP TABLE bb_access();
DROP TABLE bb_banlist();
DROP TABLE bb_categories();
DROP TABLE bb_config();
DROP TABLE bb_disallow();
DROP TABLE bb_forum_access();
DROP TABLE bb_forum_mods();
DROP TABLE bb_forums();
DROP TABLE bb_headermetafooter();
DROP TABLE bb_posts();
DROP TABLE bb_posts_text();
DROP TABLE bb_priv_msgs();
DROP TABLE bb_ranks();
DROP TABLE bb_sessions();
DROP TABLE bb_themes();
DROP TABLE bb_topics();
DROP TABLE bb_users();
DROP TABLE bb_whosonline();
DROP TABLE bb_words();