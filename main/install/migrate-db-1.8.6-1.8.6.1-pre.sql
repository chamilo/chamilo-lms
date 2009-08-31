-- This script updates the databases structure before migrating the data from
-- version 1.8.6 to version 1.8.6.1
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
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_use_sub_language', NULL, 'radio', 'Platform', 'false', 'AllowUseSubLanguageTitle', 'AllowUseSubLanguageComment', NULL, NULL, 0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_use_sub_language', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_use_sub_language', 'false', 'No');
ALTER TABLE language ADD COLUMN parent_id tinyint unsigned;
ALTER TABLE language ADD INDEX idx_dokeos_folder(dokeos_folder);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_glossary_in_documents', NULL, 'radio', 'Course', 'none', 'ShowGlossaryInDocumentsTitle', 'ShowGlossaryInDocumentsComment', NULL, NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_documents', 'none', 'ShowGlossaryInDocumentsIsNone');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_documents', 'ismanual', 'ShowGlossaryInDocumentsIsManual');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_documents', 'isautomatic', 'ShowGlossaryInDocumentsIsAutomatic');
CREATE TABLE legal (legal_id int NOT NULL auto_increment, language_id int NOT NULL, date int NOT NULL default 0, content text, type int NOT NULL, changes text NOT NULL, version int, PRIMARY KEY (legal_id));
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) values (1, 'legal_accept','Legal',0,0);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_terms_conditions', NULL, 'radio', 'Platform', 'false', 'AllowTermsAndConditionsTitle', 'AllowTermsAndConditionsComment', NULL, NULL,0);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_terms_conditions', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_terms_conditions', 'false', 'No');
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_tutor_data',NULL,'radio','Platform','true','ShowTutorDataTitle','ShowTutorDataComment',NULL,NULL, 1);
INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('show_teacher_data',NULL,'radio','Platform','true','ShowTeacherDataTitle','ShowTeacherDataComment',NULL,NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_tutor_data','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_tutor_data','false','No');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_teacher_data','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_teacher_data','false','No');
ALTER TABLE user_friend ADD COLUMN last_edit DATETIME;
ALTER TABLE reservation_item ADD always_available TINYINT NOT NULL default 0;
CREATE TABLE gradebook_certificate( id bigint unsigned not null auto_increment, cat_id int unsigned not null, user_id int unsigned not null, score_certificate float unsigned not null default 0, date_certificate datetime not null default '0000-00-00 00:00:00', path_certificate text null, PRIMARY KEY(id));
ALTER TABLE gradebook_certificate ADD INDEX idx_gradebook_certificate_category_id(cat_id);
ALTER TABLE gradebook_certificate ADD INDEX idx_gradebook_certificate_user_id(user_id);
ALTER TABLE gradebook_certificate ADD INDEX idx_gradebook_certificate_category_id_user_id(cat_id,user_id);
ALTER TABLE gradebook_category ADD COLUMN document_id int unsigned default NULL;
ALTER TABLE gradebook_evaluation ADD COLUMN type varchar(40) NOT NULL default 'evaluation';
INSERT IGNORE INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable) VALUES ('dokeos_database_version',NULL,'textfield',NULL,'1.8.6.1.8225','DokeosDatabaseVersion','',NULL,NULL,1,0);

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE wiki CHANGE title title varchar(255), CHANGE reflink reflink varchar(255), ADD time_edit datetime NOT NULL default '0000-00-00 00:00:00' AFTER is_editing, ADD INDEX (title), ADD INDEX (reflink), ADD INDEX (group_id), ADD INDEX (page_id);
ALTER TABLE wiki_conf DROP id, ADD task text NOT NULL AFTER page_id, ADD fprogress3 varchar(3) NOT NULL AFTER feedback3, ADD fprogress2 varchar(3) NOT NULL AFTER feedback3, ADD fprogress1 varchar(3) NOT NULL AFTER feedback3, ADD INDEX(page_id);
ALTER TABLE link ADD COLUMN target char(10) DEFAULT '_self';
