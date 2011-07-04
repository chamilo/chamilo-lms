-- This script updates the databases structure before migrating the data from
-- version 1.8.2 to version 1.8.3
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
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('display_categories_on_homepage',NULL,'radio','Platform','true','DisplayCategoriesOnHomepageTitle','DisplayCategoriesOnHomepageComment',NULL,NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('permissions_for_new_directories', NULL, 'textfield', 'Security', '0770', 'PermissionsForNewDirs', 'PermissionsForNewDirsComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('permissions_for_new_files', NULL, 'textfield', 'Security', '0660', 'PermissionsForNewFiles', 'PermissionsForNewFilesComment', NULL, NULL);

INSERT INTO settings_options(variable,value,display_text) VALUES ('display_categories_on_homepage', 'true', 'Yes');
INSERT INTO settings_options(variable,value,display_text) VALUES ('display_categories_on_homepage', 'false', 'No');
-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
