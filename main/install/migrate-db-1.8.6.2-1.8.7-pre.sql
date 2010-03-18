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
INSERT INTO course_module (name, link, image, `row`, `column`, position) VALUES ('notebook','notebook/index.php','notebook.gif',2,1,'basic');
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

INSERT INTO user_field(field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES(11, 'timezone', 'Timezone', 0, 0);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_create_active_tools','attendances','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Attendances', 0);

ALTER TABLE user_field_values CHANGE id id BIGINT NOT NULL AUTO_INCREMENT;
ALTER TABLE user_field_values ADD INDEX (user_id, field_id);

UPDATE settings_current SET selected_value = '1.8.7.11140' WHERE variable = 'dokeos_database_version';

ALTER TABLE course_rel_user DROP PRIMARY KEY, ADD PRIMARY KEY (course_code, user_id, relation_type);
ALTER TABLE session_rel_user DROP PRIMARY KEY, ADD PRIMARY KEY (id_session, id_user, relation_type);

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


-- xxUSERxx

-- xxCOURSExx
INSERT INTO tool(name,link,image,visibility,admin,address,added_tool,target,category) VALUES ('attendance','attendance/index.php','attendance.gif',0,'0','squaregrey.gif',0,'_self','authoring');
ALTER TABLE course_description ADD COLUMN progress INT  NOT NULL DEFAULT 0 AFTER description_type;
ALTER TABLE item_property ADD id int NOT NULL auto_increment PRIMARY KEY FIRST;
UPDATE course_description SET description_type = (SELECT IF(description_type>7,description_type+1,description_type)); -- update description_type field for using thematic advance with description_type = 8
CREATE TABLE attendance_calendar (id int NOT NULL auto_increment, attendance_id int NOT NULL, date_time datetime NOT NULL default '0000-00-00 00:00:00', done_attendance tinyint(3) NOT NULL default 0, PRIMARY KEY(id));
ALTER TABLE attendance_calendar ADD INDEX(attendance_id);
ALTER TABLE attendance_calendar ADD INDEX(done_attendance);
CREATE TABLE attendance_sheet (user_id int NOT NULL, attendance_calendar_id int NOT NULL, presence tinyint(3) NOT NULL DEFAULT 0, PRIMARY KEY(user_id, attendance_calendar_id));
ALTER TABLE attendance_sheet ADD INDEX(presence);
CREATE TABLE attendance_result (id int NOT NULL auto_increment PRIMARY KEY, user_id int NOT NULL, attendance_id int NOT NULL, score int NOT NULL DEFAULT 0);
ALTER TABLE attendance_result ADD INDEX(attendance_id);
ALTER TABLE attendance_result ADD INDEX(user_id);
CREATE TABLE attendance (id int NOT NULL auto_increment PRIMARY KEY, name text NOT NULL, description TEXT NULL, active tinyint(3) NOT NULL default 1, attendance_qualify_title varchar(255) NULL, attendance_qualify_max int NOT NULL default 0, attendance_weight float(6,2) NOT NULL default '0.0', session_id int NOT NULL default 0);
ALTER TABLE attendance ADD INDEX(session_id);
ALTER TABLE attendance ADD INDEX(active);
ALTER TABLE lp_view ADD session_id INT NOT NULL DEFAULT 0;
ALTER TABLE lp_view ADD INDEX(session_id);
INSERT INTO course_setting (variable,value,category) VALUES ('allow_user_view_user_list',1,'user');
ALTER TABLE tool_intro ADD COLUMN session_id INT  NOT NULL DEFAULT 0 AFTER intro_text, DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE(id, session_id);
CREATE TABLE thematic (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR( 255 ) NOT NULL, content TEXT NULL, display_order int unsigned not null default 0, active TINYINT NOT NULL default 0, session_id INT NOT NULL DEFAULT 0);
ALTER TABLE thematic ADD INDEX (active, session_id);  
CREATE TABLE thematic_plan (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, thematic_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NULL, description_type` INT NOT NULL); 
ALTER TABLE thematic_plan ADD INDEX (thematic_id, description_type); 
CREATE TABLE thematic_advance (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, thematic_id INT NOT NULL, attendance_id INT NOT NULL DEFAULT 0, content TEXT NOT NULL, start_date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', duration INT NOT NULL DEFAULT 0, done_advance tinyint NOT NULL DEFAULT 0);
ALTER TABLE thematic_advance ADD INDEX (thematic_id);
INSERT INTO course_setting (variable,value,category) VALUES ('display_info_advance_inside_homecourse',1,'thematic_advance');