INSERT INTO settings_current 
(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext)
VALUES
('search_enabled',NULL,'radio','Tools','false','EnableSearchTitle','EnableSearchComment',NULL,NULL),
('search_prefilter_prefix',NULL, NULL,'Search','','SearchPrefilterPrefix','SearchPrefilterPrefixComment',NULL,NULL),
('search_show_unlinked_results',NULL,'radio','Search','true','SearchShowUnlinkedResultsTitle','SearchShowUnlinkedResultsComment',NULL,NULL);

INSERT INTO settings_options 
(variable, value, display_text)
VALUES
('search_enabled', 'true', 'Yes'),
('search_enabled', 'false', 'No'),
('search_show_unlinked_results', 'true', 'SearchShowUnlinkedResults'),
('search_show_unlinked_results', 'false', 'SearchHideUnlinkedResults');


-- specific fields tables
 CREATE TABLE specific_field (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
code char(1) NOT NULL,
name VARCHAR( 200 ) NOT NULL
);

CREATE TABLE specific_field_values (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
course_code VARCHAR( 40 ) NOT NULL ,
tool_id VARCHAR( 100 ) NOT NULL ,
ref_id INT NOT NULL ,
field_id INT NOT NULL ,
value VARCHAR( 200 ) NOT NULL
);
ALTER TABLE specific_field ADD CONSTRAINT unique_specific_field__code UNIQUE (code);

-- search engine references to map dokeos resources
CREATE TABLE search_engine_ref (
id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
course_code VARCHAR( 40 ) NOT NULL,
tool_id VARCHAR( 100 ) NOT NULL,
ref_id_high_level INT NOT NULL,
ref_id_second_level INT NULL,
search_did INT NOT NULL
);

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext) VALUES ('allow_message_tool', NULL, 'radio', 'Tools', 'false', 'AllowMessageToolTitle', 'AllowMessageToolComment', NULL, NULL);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_message_tool', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_message_tool', 'false', 'No');
