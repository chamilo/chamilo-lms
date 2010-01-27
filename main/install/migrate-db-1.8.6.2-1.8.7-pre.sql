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
UPDATE settings_current SET selected_value = '1.8.7.10004' WHERE variable = 'dokeos_database_version';
ALTER TABLE course DROP PRIMARY KEY , ADD UNIQUE KEY code (code); 
ALTER TABLE course ADD id int NOT NULL auto_increment PRIMARY KEY FIRST;

-- xxSTATSxx
CREATE TABLE track_e_item_property(id int NOT NULL auto_increment PRIMARY KEY, course_id int NOT NULL, item_property_id int NOT NULL, title varchar(255), content text, progress int NOT NULL default 0, lastedit_date datetime NOT NULL default '0000-00-00 00:00:00', lastedit_user_id int  NOT NULL, session_id int NOT NULL default 0);
ALTER TABLE track_e_item_property ADD INDEX (course_id, item_property_id, session_id);

-- xxUSERxx

-- xxCOURSExx
INSERT INTO tool(name,link,image,visibility,admin,address,added_tool,target,category) VALUES ('notebook','notebook/index.php','notebook.gif',0,'0','squaregrey.gif',0,'_self','interaction');
ALTER TABLE course_description ADD COLUMN progress INT  NOT NULL DEFAULT 0 AFTER description_type;
ALTER TABLE item_property ADD id int NOT NULL auto_increment PRIMARY KEY FIRST;
UPDATE course_description SET description_type = (SELECT IF(description_type>7,description_type+1,description_type)); -- update description_type field for using thematic advance with description_type = 8

