-- This script updates the databases structure after migrating the data from
-- version 1.9.0 (or version 1.9.*) to version 1.10.0

-- Main table changes

ALTER TABLE track_e_access DROP COLUMN access_cours_code;
ALTER TABLE track_e_default DROP COLUMN default_cours_code;
ALTER TABLE track_e_lastaccess DROP COLUMN access_cours_code;
ALTER TABLE track_e_exercises DROP COLUMN exe_cours_id;
ALTER TABLE track_e_downloads DROP COLUMN down_cours_id;
ALTER TABLE track_e_hotpotatoes DROP COLUMN exe_cours_id;
ALTER TABLE track_e_links DROP COLUMN links_cours_id;
ALTER TABLE track_e_course_access DROP COLUMN course_code;
ALTER TABLE track_e_online DROP COLUMN course;
ALTER TABLE track_e_attempt DROP COLUMN course_code;

-- not yet ready, uncomment when all user_id have been replaced by id
-- ALTER TABLE user DROP COLUMN user_id;

-- Course table changes (c_*)



hjus
