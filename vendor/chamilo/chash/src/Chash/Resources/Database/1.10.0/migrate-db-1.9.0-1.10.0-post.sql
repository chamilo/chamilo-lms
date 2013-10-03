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

-- xxMAINxx

-- ALTER TABLE session_rel_course DROP COLUMN course_code;
-- ALTER TABLE session_rel_course_rel_user DROP COLUMN course_code;
-- ALTER TABLE track_e_hotpotatoes DROP COLUMN course_code;
-- ALTER TABLE track_e_exercices DROP COLUMN course_code;
-- ALTER TABLE track_e_attempt DROP COLUMN course_code;
-- ALTER TABLE track_e_hotspot DROP COLUMN course_code;
-- ALTER TABLE track_e_course_access DROP COLUMN course_code;
-- ALTER TABLE access_url_rel_course DROP COLUMN course_code;
-- ALTER TABLE course_rel_user DROP COLUMN course_code;
-- ALTER TABLE track_e_lastaccess DROP COLUMN access_cours_code;

DROP TABLE IF EXISTS track_c_referers;
DROP TABLE IF EXISTS track_c_providers;
DROP TABLE IF EXISTS track_c_os;
DROP TABLE IF EXISTS track_c_countries;
DROP TABLE IF EXISTS track_c_browsers;
DROP TABLE IF EXISTS track_e_open;

--ALTER TABLE track_e_lastaccess DROP COLUMN access_cours_code;
--ALTER TABLE track_e_access    DROP COLUMN access_cours_code ;
--ALTER TABLE track_e_course_access DROP COLUMN course_code;
--ALTER TABLE track_e_downloads DROP COLUMN  down_cours_id;
--ALTER TABLE track_e_links DROP COLUMN links_cours_id;

--ALTER TABLE session DROP COLUMN date_start;
--ALTER TABLE session DROP COLUMN date_end;

