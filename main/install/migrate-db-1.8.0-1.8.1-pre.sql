-- This script updates the databases structure before migrating the data from
-- version 1.8.0 to version 1.8.1
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
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_list_type', NULL, 'radio', 'Security', 'blacklist', 'UploadExtensionsListType', 'UploadExtensionsListTypeComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_blacklist', NULL, 'textfield', 'Security', '', 'UploadExtensionsBlacklist', 'UploadExtensionsBlacklistComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_whitelist', NULL, 'textfield', 'Security', 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg', 'UploadExtensionsWhitelist', 'UploadExtensionsWhitelistComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_skip', NULL, 'radio', 'Security', 'true', 'UploadExtensionsSkip', 'UploadExtensionsSkipComment', NULL, NULL);
INSERT INTO settings_current(variable,subkey,type,category,selected_value,title,comment,scope,subkeytext) VALUES ('upload_extensions_replace_by', NULL, 'textfield', 'Security', 'txt', 'UploadExtensionsReplaceBy', 'UploadExtensionsReplaceByComment', NULL, NULL);

INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_list_type', 'blacklist', 'Blacklist');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_list_type', 'whitelist', 'Whitelist');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_skip', 'true', 'Yes');
INSERT INTO settings_options(variable,value,display_text) VALUES ('upload_extensions_skip', 'false', 'No');

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
