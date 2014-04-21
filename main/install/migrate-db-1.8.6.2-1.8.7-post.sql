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
ALTER TABLE course_module CHANGE name name varchar(255) NOT NULL;
ALTER TABLE sys_calendar CHANGE title title varchar(255) NOT NULL;
ALTER TABLE tag CHANGE tag tag varchar(255) NOT NULL;

-- xxSTATSxx

-- xxUSERxx

-- xxCOURSExx
ALTER TABLE quiz CHANGE title title VARCHAR(255) NOT NULL;
ALTER TABLE quiz CHANGE sound sound VARCHAR(255) DEFAULT NULL;
ALTER TABLE quiz_question CHANGE question question VARCHAR(511) NOT NULL;
ALTER TABLE tool CHANGE name name VARCHAR(255) NOT NULL;
ALTER TABLE tool CHANGE image image VARCHAR(255) default NULL;
ALTER TABLE tool CHANGE admin admin VARCHAR(255) default NULL;
ALTER TABLE tool CHANGE address address VARCHAR(255) default NULL;
ALTER TABLE calendar_event CHANGE title title VARCHAR(255) NOT NULL;
ALTER TABLE student_publication CHANGE url url VARCHAR(255) default NULL;
ALTER TABLE student_publication CHANGE title title VARCHAR(255) default NULL;
ALTER TABLE student_publication CHANGE author author VARCHAR(255) default NULL;
ALTER TABLE lp CHANGE name name varchar(255) NOT NULL;
ALTER TABLE lp_item CHANGE title title  varchar(511) NOT NULL;
ALTER TABLE lp_item CHANGE description description varchar(511) NOT NULL default '';