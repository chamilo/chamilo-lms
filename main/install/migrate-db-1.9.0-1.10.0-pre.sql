-- This script updates the databases structure before migrating the data from
-- version 1.9.0 (or version 1.9.2, 1.9.4) to version 1.10.0
-- it is intended as a standalone script, however, this has not been finished
-- yet, and it should still be parsed by a PHP script.
-- There is one line per query, allowing the PHP function file() to read
-- all lines separately into an array. The xxMAINxx-type markers used to be 
-- there to tell the PHP script which database we're talking about, but since
-- 1.9.0, everything should be residing in the same, unique xxMAINxx section.
-- By always using the keyword "TABLE" in the queries, we should be able
-- to retrieve and modify the table name from the PHP script if needed, which
-- will allow us to deal with the unique-database-type installations

-- xxMAINxx

-- Optimize tracking query very often queried on busy campuses
ALTER TABLE track_e_online ADD INDEX idx_trackonline_uat (login_user_id, access_url_id, login_date);

-- Do not move this query
UPDATE settings_current SET selected_value = '1.10.0.19436' WHERE variable = 'chamilo_database_version';

