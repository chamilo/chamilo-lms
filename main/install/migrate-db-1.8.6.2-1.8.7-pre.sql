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

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx

INSERT INTO tool(name,link,image,visibility,admin,address,added_tool,target,category) VALUES ('notebook','notebook/index.php','notebook.gif',0,'0','squaregrey.gif',0,'_self','interaction');