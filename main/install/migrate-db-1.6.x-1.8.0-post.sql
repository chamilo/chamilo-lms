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

ALTER TABLE sys_announcement DROP COLUMN visible_teacher_temp;
ALTER TABLE sys_announcement DROP COLUMN visible_student_temp;
ALTER TABLE sys_announcement DROP COLUMN visible_guest_temp;
-- DELETE FROM settings_options WHERE variable='showonline';


-- xxSTATSxx

-- xxUSERxx

-- xxSCORMxx
DROP TABLE scorm_main;
DROP TABLE scorm_sco_data;

-- xxCOURSExx
ALTER TABLE quiz DROP COLUMN active_temp;
ALTER TABLE tool DROP COLUMN added_tool_temp;

ALTER TABLE group_info DROP COLUMN tutor_id;
ALTER TABLE group_info DROP COLUMN forum_state;
ALTER TABLE group_info DROP COLUMN forum_id;
ALTER TABLE group_info DROP COLUMN self_registration_allowed_temp;
ALTER TABLE group_info DROP COLUMN self_unregistration_allowed_temp;
ALTER TABLE group_info DROP COLUMN doc_state_temp;

ALTER TABLE group_category DROP COLUMN forum_state;
ALTER TABLE group_category DROP COLUMN self_reg_allowed_temp;
ALTER TABLE group_category DROP COLUMN self_unreg_allowed_temp;

DROP TABLE bb_access;
DROP TABLE bb_banlist;
DROP TABLE bb_categories;
DROP TABLE bb_config;
DROP TABLE bb_disallow;
DROP TABLE bb_forum_access;
DROP TABLE bb_forum_mods;
DROP TABLE bb_forums;
DROP TABLE bb_headermetafooter;
DROP TABLE bb_posts;
DROP TABLE bb_posts_text;
DROP TABLE bb_priv_msgs;
DROP TABLE bb_ranks;
DROP TABLE bb_sessions;
DROP TABLE bb_themes;
DROP TABLE bb_topics;
DROP TABLE bb_users;
DROP TABLE bb_whosonline;
DROP TABLE bb_words;

-- ? DROP TABLE stud_pub_rel_user;