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
CREATE TABLE blog(blog_id smallint NOT NULL AUTO_INCREMENT , blog_name varchar(250) NOT NULL default '', blog_subtitle varchar( 250 ) default NULL , date_creation datetime NOT NULL default '0000-00-00 00:00:00', visibility enum( '0', '1' ) NOT NULL default '0', PRIMARY KEY (blog_id));
CREATE TABLE blog_comment(comment_id int NOT NULL AUTO_INCREMENT , title varchar(250) NOT NULL default '', comment longtext NOT NULL , author_id int NOT NULL default '0', date_creation datetime NOT NULL default '0000-00-00 00:00:00', blog_id mediumint NOT NULL default '0', post_id int NOT NULL default '0', task_id int default NULL , parent_comment_id int NOT NULL default '0', PRIMARY KEY (comment_id));
CREATE TABLE blog_post(post_id int NOT NULL AUTO_INCREMENT, title varchar(250) NOT NULL default '', full_text longtext NOT NULL, date_creation datetime NOT NULL default '0000-00-00 00:00:00', blog_id mediumint NOT NULL default '0', author_id int NOT NULL default '0', PRIMARY KEY (post_id));
CREATE TABLE blog_rating(rating_id int NOT NULL AUTO_INCREMENT, blog_id int NOT NULL default '0', rating_type enum( 'post', 'comment' ) NOT NULL default 'post', item_id int NOT NULL default '0', user_id int NOT NULL default '0', rating mediumint NOT NULL default '0', PRIMARY KEY (rating_id));
CREATE TABLE blog_rel_user(blog_id int NOT NULL default '0', user_id int NOT NULL default '0', PRIMARY KEY (blog_id,user_id));
CREATE TABLE blog_task(task_id mediumint NOT NULL AUTO_INCREMENT,blog_id mediumint NOT NULL default '0',title varchar( 250 ) NOT NULL default '',description text NOT NULL ,color varchar( 10 ) NOT NULL default '', system_task enum( '0', '1' ) NOT NULL default '0',PRIMARY KEY (task_id));
CREATE TABLE blog_task_rel_user(blog_id mediumint NOT NULL default '0',user_id int NOT NULL default '0',task_id mediumint NOT NULL default '0',target_date date NOT NULL default '0000-00-00',PRIMARY KEY (blog_id,user_id,task_id));
CREATE TABLE course_setting(id int unsigned NOT NULL auto_increment, variable varchar(255) NOT NULL default '', value varchar(255) NOT NULL default '', tool_scope varchar(255) default '', PRIMARY KEY (id));
CREATE TABLE dropbox_category(cat_id int NOT NULL auto_increment, cat_name text NOT NULL, received enum('0','1') NOT NULL default '0', sent enum('0','1') NOT NULL default '0', user_id int NOT NULL default '0', PRIMARY KEY  (cat_id));
CREATE TABLE dropbox_feedback(feedback_id int NOT NULL auto_increment, file_id int NOT NULL default '0', author_user_id int NOT NULL default '0', feedback text NOT NULL, feedback_date datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY  (feedback_id), KEY file_id (file_id), KEY author_user_id (author_user_id));
CREATE TABLE forum_category(cat_id int NOT NULL auto_increment, cat_title varchar(255) NOT NULL default '', cat_comment text, cat_order int(11) NOT NULL default '0', locked int(5) NOT NULL default '0', PRIMARY KEY (cat_id));
CREATE TABLE forum_forum(forum_id int NOT NULL auto_increment, forum_title varchar(255) NOT NULL default '', forum_comment text, forum_threads int default '0', forum_posts int default '0', forum_last_post int default '0', forum_category int default NULL, allow_anonymous int default NULL, allow_edit int default NULL, approval_direct_post varchar(20) default NULL, allow_attachments int default NULL, allow_new_threads int default NULL, default_view varchar(20) default NULL, forum_of_group varchar(20) default NULL, forum_group_public_private varchar(20) default 'public', forum_order int default NULL,  locked int NOT NULL default '0', PRIMARY KEY (forum_id)); 
CREATE TABLE forum_mailcue(thread_id int default NULL, user_id int default NULL, post_id int default NULL);
CREATE TABLE forum_post(post_id int NOT NULL auto_increment, post_title varchar(250) default NULL, post_text text, thread_id int default '0', forum_id int default '0', poster_id int default '0', poster_name varchar(100) default '', post_date datetime default '0000-00-00 00:00:00', post_notification int default '0', post_parent_id int default '0', visible int default '1', PRIMARY KEY (post_id), KEY poster_id (poster_id), KEY forum_id (forum_id));
CREATE TABLE forum_thread(thread_id int NOT NULL auto_increment,thread_title varchar(255) default NULL, forum_id int default NULL, thread_replies int default '0', thread_poster_id int default NULL, thread_poster_name int default '0', thread_views int default '0', thread_last_post int default NULL, thread_date datetime default '0000-00-00 00:00:00', thread_sticky int default '0', locked int NOT NULL default '0', PRIMARY KEY (thread_id), KEY thread_id (thread_id));

ALTER TABLE group_category ADD COLUMN calendar_state tinyint unsigned NOT NULL default 1;
ALTER TABLE group_category ADD COLUMN work_state tinyint unsigned NOT NULL default 1;
ALTER TABLE group_category ADD COLUMN announcements_state tinyint unsigned NOT NULL default 1;

ALTER TABLE group_info MODIFY secret_directory varchar(255) default NULL;
ALTER TABLE group_info ADD COLUMN calendar_state tinyint unsigned NOT NULL default 0;
ALTER TABLE group_info ADD COLUMN work_state tinyint unsigned NOT NULL default 0;
ALTER TABLE group_info ADD COLUMN announcements_state tinyint unsigned NOT NULL default 0;

CREATE TABLE group_rel_tutor(id int NOT NULL auto_increment, user_id int NOT NULL, group_id int NOT NULL default 0, PRIMARY KEY (id));
CREATE TABLE lp(id int	unsigned primary key auto_increment, lp_type	smallint unsigned not null, name tinytext not null, ref tinytext null, description text null, path text	not null, force_commit  tinyint	unsigned not null default 0, default_view_mod char(32) not null default 'embedded', default_encoding char(32)	not null default 'ISO-8859-1', display_order int		unsigned	not null default 0, content_maker tinytext  not null default '', content_local 	varchar(32)  not null default 'local', content_license	text not null default '', prevent_reinit tinyint unsigned not null default 1, js_lib tinytext    not null default '', debug tinyint unsigned not null default 0);
CREATE TABLE lp_view(id	int	unsigned primary key auto_increment, lp_id int	unsigned not null, user_id int unsigned not null, view_count smallint unsigned not null default 0, last_item int	unsigned not null default 0, progress int	unsigned default 0);
CREATE TABLE lp_item(id	int	unsigned primary key auto_increment, lp_id int unsigned	not null, item_type	char(32) not null default 'dokeos_document', ref tinytext not null default '', title tinytext not null, description	tinytext not null default '', path text	 not null, min_score float unsigned	not null default 0, max_score float unsigned not null default 100, mastery_score float unsigned null, parent_item_id		int unsigned	not null default 0, previous_item_id	int unsigned	not null default 0, next_item_id		int unsigned	not null default 0, display_order		int unsigned	not null default 0, prerequisite  char(64)  null, parameters  text  null, launch_data text not null default '');
CREATE TABLE lp_item_view(id bigint	unsigned primary key auto_increment, lp_item_id		int unsigned	not null, lp_view_id		int unsigned 	not null, view_count		int unsigned	not null default 0, start_time		int unsigned	not null, total_time		int unsigned not null default 0, score			float unsigned not null default 0, status			char(32) not null default 'Not attempted', suspend_data	text null default '', lesson_location text null default '');
CREATE TABLE lp_iv_interaction(id bigint unsigned primary key auto_increment, order_id smallint unsigned not null default 0, lp_iv_id		bigint	unsigned not null, interaction_id	varchar(255) not null default '', interaction_type	varchar(255) not null default '', weighting			double not null default 0, completion_time	varchar(16) not null default '', correct_responses	text not null default '', student_response	text not null default '', result			varchar(255) not null default '', latency		varchar(16)	not null default '');

CREATE TABLE permission_group(id int NOT NULL AUTO_INCREMENT, group_id int NOT NULL default 0, tool varchar( 250 ) NOT NULL default '', action varchar( 250 ) NOT NULL default '', PRIMARY KEY ( id ));
CREATE TABLE permission_user(id int NOT NULL AUTO_INCREMENT, user_id int NOT NULL default 0, tool varchar( 250 ) NOT NULL default '', action varchar( 250 ) NOT NULL default '', PRIMARY KEY ( id ));
CREATE TABLE permission_task(id int NOT NULL AUTO_INCREMENT, task_id int NOT NULL default 0, tool varchar( 250 ) NOT NULL default '', action varchar( 250 ) NOT NULL default '', PRIMARY KEY ( id ));

CREATE TABLE questions();
ALTER TABLE quiz_answer ADD COLUMN hotspot_coordinates...
ALTER TABLE quiz_answer ADD COLUMN hotspot_type...


CREATE TABLE role(role_id int NOT NULL AUTO_INCREMENT , role_name varchar( 250 ) NOT NULL default '', role_comment text, default_role tinyint default 0,	PRIMARY KEY ( role_id ));
CREATE TABLE role_group(role_id int NOT NULL default 0, scope varchar( 20 ) NOT NULL default 'course', group_id int NOT NULL default 0);
CREATE TABLE role_permissions(role_id int NOT NULL default 0, tool varchar( 250 ) NOT NULL default '', action varchar( 50 ) NOT NULL default '', default_perm tinyint NOT NULL default 0);
CREATE TABLE role_user(role_id int NOT NULL default 0, scope varchar( 20 ) NOT NULL default 'course', user_id int NOT NULL default 0);

ALTER TABLE student_publication ADD COLUMN post_group_id int DEFAULT 0 NOT NULL;

CREATE TABLE survey(survey_id int unsigned NOT NULL auto_increment, code varchar(20) default NULL, title varchar(80) default NULL, subtitle varchar(80) default NULL, author varchar(20) default NULL, lang varchar(20) default NULL, avail_from date default NULL, avail_till date default NULL, is_shared char(1) default 1, template varchar(20) default NULL, intro text, surveythanks text, creation_date datetime NOT NULL default '0000-00-00 00:00:00', PRIMARY KEY (survey_id), UNIQUE KEY id (survey_id));
CREATE TABLE survey_group(group_id int NOT NULL auto_increment, survey_id int NOT NULL default 0, groupname varchar(100) NOT NULL default '', introduction text NOT NULL, imported_group int NOT NULL default 0, db_name varchar(100) NULL default '', sortby int NOT NULL default 1, PRIMARY KEY (group_id));
CREATE TABLE survey_report(id int NOT NULL auto_increment, qid int NOT NULL default 0, answer text NOT NULL, survey_id int NOT NULL default 0, user_id int NOT NULL default 0, PRIMARY KEY (id));

ALTER TABLE tool ADD COLUMN category enum('authoring','interaction','admin') NOT NULL default 'authoring';