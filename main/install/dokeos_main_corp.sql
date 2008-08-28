INSERT INTO settings_current 
(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext)
VALUES
('search_enabled',NULL,'radio','Tools','false','EnableSearchTitle','EnableSearchComment',NULL,NULL);

INSERT INTO settings_options 
(variable, value, display_text)
VALUES
('search_enabled', 'true', 'Yes'),
('search_enabled', 'false', 'No');