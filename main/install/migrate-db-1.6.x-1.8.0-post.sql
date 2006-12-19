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

-- This first part is for the main database
-- xxMAINxx
ALTER TABLE admin SET COLUMN user_id Default
ALTER TABLE class_user SET COLUMN class_id Default
ALTER TABLE class_user SET COLUMN user_id Default
ALTER TABLE course ADD COLUMN registration_code...
ALTER TABLE course_rel_class SET COLUMN class_id Default
CREATE TABLE course_rel_survey();
ALTER TABLE course_rel_user SET COLUMN user_id Default
CREATE TABLE messages();
CREATE TABLE sess();
ALTER TABLE session DROP COLUMN sess_id
ALTER TABLE session DROP COLUMN sess_name
ALTER TABLE session DROP COLUMN sess_time
ALTER TABLE session DROP COLUMN sess_start
ALTER TABLE session DROP COLUMN sess_value
ALTER TABLE session ADD COLUMN id...
ALTER TABLE session ADD COLUMN id_coach...
ALTER TABLE session ADD COLUMN name...
ALTER TABLE session ADD COLUMN nbr_courses...
ALTER TABLE session ADD COLUMN nbr_users...
ALTER TABLE session ADD COLUMN nbr_classes...
ALTER TABLE session ADD COLUMN date_start...
ALTER TABLE session ADD COLUMN date_end...
CREATE TABLE session_rel_course();
CREATE TABLE session_rel_course_rel_user();
CREATE TABLE session_rel_user();
CREATE TABLE survey_reminder();
CREATE TABLE survey_user_info();
ALTER TABLE sys_announcement SET COLUMN visible_teacher Type
ALTER TABLE sys_announcement SET COLUMN visible_student Type
ALTER TABLE sys_announcement SET COLUMN visible_guest Type
ALTER TABLE sys_announcement ADD COLUMN lang...
ALTER TABLE user SET COLUMN auth_source Default
ALTER TABLE user ADD COLUMN language...
ALTER TABLE user ADD COLUMN registration_date...
ALTER TABLE user ADD COLUMN expiration_date...
ALTER TABLE user ADD COLUMN active...

-- xxSTATSxx
CREATE TABLE track_e_attempt();
CREATE TABLE track_e_course_access();
ALTER TABLE track_e_lastaccess SET COLUMN access_id Type
ALTER TABLE track_e_lastaccess ADD COLUMN access_session_id...
ALTER TABLE track_e_login ADD COLUMN logout_date...
ALTER TABLE track_e_online ADD COLUMN course...

-- xxUSERxx
ALTER TABLE user_course_category ADD COLUMN sort...

-- xxSCORMxx
DROP TABLE scorm_main();
DROP TABLE scorm_sco_data();

-- xxCOURSExx
ALTER TABLE announcement SET COLUMN content Type
ALTER TABLE announcement ADD COLUMN email_sent...
CREATE TABLE audiorecorder();
CREATE TABLE blogs();
CREATE TABLE blogs_comments();
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