-- MySQL dump
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS track_c_browsers;
CREATE TABLE track_c_browsers (
  id int NOT NULL auto_increment,
  browser varchar(255) NOT NULL default '',
  counter int NOT NULL default 0,
  PRIMARY KEY  (id)
);

DROP TABLE IF EXISTS track_c_countries;
CREATE TABLE track_c_countries (
  id int NOT NULL auto_increment,
  code varchar(40) NOT NULL default '',
  country varchar(50) NOT NULL default '',
  counter int NOT NULL default 0,
  PRIMARY KEY  (id)
);

DROP TABLE IF EXISTS track_c_os;
CREATE TABLE track_c_os (
  id int NOT NULL auto_increment,
  os varchar(255) NOT NULL default '',
  counter int NOT NULL default 0,
  PRIMARY KEY  (id)
);

DROP TABLE IF EXISTS track_c_providers;
CREATE TABLE track_c_providers (
  id int NOT NULL auto_increment,
  provider varchar(255) NOT NULL default '',
  counter int NOT NULL default 0,
  PRIMARY KEY  (id)
);

DROP TABLE IF EXISTS track_c_referers;
CREATE TABLE track_c_referers (
  id int NOT NULL auto_increment,
  referer varchar(255) NOT NULL default '',
  counter int NOT NULL default 0,
  PRIMARY KEY  (id)
);

DROP TABLE IF EXISTS track_e_access;
CREATE TABLE track_e_access (
  access_id int NOT NULL auto_increment,
  access_user_id int unsigned default NULL,
  access_date datetime NOT NULL default '0000-00-00 00:00:00',
  access_cours_code varchar(40) NOT NULL default '',
  access_tool varchar(30) default NULL,
  access_session_id int NOT NULL default 0,
  PRIMARY KEY  (access_id),
  KEY access_user_id (access_user_id),
  KEY access_cours_code (access_cours_code)
);

DROP TABLE IF EXISTS track_e_lastaccess;
CREATE TABLE track_e_lastaccess (
  access_id bigint NOT NULL auto_increment,
  access_user_id int unsigned default NULL,
  access_date datetime NOT NULL default '0000-00-00 00:00:00',
  access_cours_code varchar(40) NOT NULL,
  access_tool varchar(30) default NULL,
  access_session_id int unsigned default NULL,
  PRIMARY KEY  (access_id),
  KEY access_user_id (access_user_id),
  KEY access_cours_code (access_cours_code),
  KEY access_session_id (access_session_id)
);

DROP TABLE IF EXISTS track_e_default;
CREATE TABLE track_e_default (
  default_id int NOT NULL auto_increment,
  default_user_id int unsigned NOT NULL default 0,
  default_cours_code varchar(40) NOT NULL default '',
  default_date datetime NOT NULL default '0000-00-00 00:00:00',
  default_event_type varchar(20) NOT NULL default '',
  default_value_type varchar(20) NOT NULL default '',
  default_value text NOT NULL,
  PRIMARY KEY  (default_id)
);

DROP TABLE IF EXISTS track_e_downloads;
CREATE TABLE track_e_downloads (
  down_id int NOT NULL auto_increment,
  down_user_id int unsigned default NULL,
  down_date datetime NOT NULL default '0000-00-00 00:00:00',
  down_cours_id varchar(40) NOT NULL default '',
  down_doc_path varchar(255) NOT NULL default '',
  down_session_id INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (down_id),
  KEY down_user_id (down_user_id),
  KEY down_cours_id (down_cours_id)
);

DROP TABLE IF EXISTS track_e_exercices;
CREATE TABLE track_e_exercices (
  exe_id int NOT NULL auto_increment,
  exe_user_id int unsigned default NULL,
  exe_date datetime NOT NULL default '0000-00-00 00:00:00',
  exe_cours_id varchar(40) NOT NULL default '',
  exe_exo_id mediumint unsigned NOT NULL default 0,
  exe_result float(6,2) NOT NULL default 0,
  exe_weighting float(6,2) NOT NULL default 0,
  PRIMARY KEY  (exe_id),
  KEY exe_user_id (exe_user_id),
  KEY exe_cours_id (exe_cours_id)
);

ALTER TABLE track_e_exercices ADD status varchar(20) NOT NULL default '';
ALTER TABLE track_e_exercices ADD data_tracking text NOT NULL default '';
ALTER TABLE track_e_exercices ADD start_date datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE track_e_exercices ADD steps_counter SMALLINT UNSIGNED NOT NULL default 0;
ALTER TABLE track_e_exercices ADD session_id SMALLINT UNSIGNED NOT NULL default 0;
ALTER TABLE track_e_exercices ADD INDEX ( session_id ) ;
ALTER TABLE track_e_exercices ADD orig_lp_id int  NOT NULL default 0;
ALTER TABLE track_e_exercices ADD orig_lp_item_id int  NOT NULL default 0;
ALTER TABLE track_e_exercices ADD exe_duration int UNSIGNED NOT NULL default 0;
ALTER TABLE track_e_exercices ADD COLUMN expired_time_control datetime NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE track_e_exercices ADD COLUMN orig_lp_item_view_id INT NOT NULL DEFAULT 0;
ALTER TABLE track_e_exercices ADD COLUMN questions_to_check TEXT  NOT NULL DEFAULT '';

DROP TABLE IF EXISTS track_e_attempt;
CREATE TABLE track_e_attempt (
  exe_id int default NULL,
  user_id int NOT NULL default 0,
  question_id int NOT NULL default 0,
  answer text NOT NULL,
  teacher_comment text NOT NULL,
  marks float(6,2) NOT NULL default 0,
  course_code varchar(40) NOT NULL default '',
  position int default 0,
  tms datetime NOT NULL default '0000-00-00 00:00:00',
  session_id INT NOT NULL DEFAULT 0,
  filename VARCHAR(255) DEFAULT NULL
);
ALTER TABLE track_e_attempt ADD INDEX (exe_id);
ALTER TABLE track_e_attempt ADD INDEX (user_id); 
ALTER TABLE track_e_attempt ADD INDEX (question_id);
ALTER TABLE track_e_attempt ADD INDEX (session_id);

DROP TABLE IF EXISTS track_e_attempt_recording;
CREATE TABLE track_e_attempt_recording (
  exe_id int unsigned NOT NULL, 
  question_id int unsigned NOT NULL,  
  marks int NOT NULL,  
  insert_date datetime NOT NULL default '0000-00-00 00:00:00',  
  author int unsigned NOT NULL,  
  teacher_comment text NOT NULL,
  session_id INT NOT NULL DEFAULT 0
);
ALTER TABLE track_e_attempt_recording ADD INDEX (exe_id);
ALTER TABLE track_e_attempt_recording ADD INDEX (question_id);
ALTER TABLE track_e_attempt_recording ADD INDEX (session_id);

DROP TABLE IF EXISTS track_e_hotpotatoes;
CREATE TABLE track_e_hotpotatoes (
  exe_name VARCHAR( 255 ) NOT NULL ,
  exe_user_id int unsigned DEFAULT NULL ,
  exe_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL ,
  exe_cours_id varchar(40) NOT NULL ,
  exe_result smallint default 0 NOT NULL ,
  exe_weighting smallint default 0 NOT NULL,
  KEY exe_user_id (exe_user_id),
  KEY exe_cours_id (exe_cours_id)
);

DROP TABLE IF EXISTS track_e_links;
CREATE TABLE track_e_links (
  links_id int NOT NULL auto_increment,
  links_user_id int unsigned default NULL,
  links_date datetime NOT NULL default '0000-00-00 00:00:00',
  links_cours_id varchar(40) NOT NULL default '' ,
  links_link_id int NOT NULL default 0,
  links_session_id INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (links_id),
  KEY links_cours_id (links_cours_id),
  KEY links_user_id (links_user_id)
);

DROP TABLE IF EXISTS track_e_login;
CREATE TABLE track_e_login (
  login_id int NOT NULL auto_increment,
  login_user_id int unsigned NOT NULL default 0,
  login_date datetime NOT NULL default '0000-00-00 00:00:00',
  login_ip varchar(39) NOT NULL default '',
  logout_date datetime NULL default NULL,
  PRIMARY KEY  (login_id),
  KEY login_user_id (login_user_id)
);

DROP TABLE IF EXISTS track_e_online;
CREATE TABLE track_e_online (
  login_id int NOT NULL auto_increment,
  login_user_id int unsigned NOT NULL default 0,
  login_date datetime NOT NULL default '0000-00-00 00:00:00',
  login_ip varchar(39) NOT NULL default '',
  course varchar(40) default NULL,
  session_id INT NOT NULL DEFAULT 0,
  access_url_id INT NOT NULL DEFAULT 1,
  PRIMARY KEY  (login_id),
  KEY login_user_id (login_user_id)
);
DROP TABLE IF EXISTS track_e_open;
CREATE TABLE track_e_open (
  open_id int NOT NULL auto_increment,
  open_remote_host tinytext NOT NULL,
  open_agent tinytext NOT NULL,
  open_referer tinytext NOT NULL,
  open_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (open_id)
);

DROP TABLE IF EXISTS track_e_uploads;
CREATE TABLE track_e_uploads (
  upload_id int NOT NULL auto_increment,
  upload_user_id int unsigned default NULL,
  upload_date datetime NOT NULL default '0000-00-00 00:00:00',
  upload_cours_id varchar(40) NOT NULL default '',
  upload_work_id int NOT NULL default 0,
  upload_session_id INT NOT NULL DEFAULT 0,
  PRIMARY KEY  (upload_id),
  KEY upload_user_id (upload_user_id),
  KEY upload_cours_id (upload_cours_id)
);

DROP TABLE IF EXISTS track_e_course_access;
CREATE TABLE track_e_course_access (
  course_access_id int NOT NULL auto_increment,
  course_code varchar(40) NOT NULL,
  user_id int NOT NULL,
  login_course_date datetime NOT NULL default '0000-00-00 00:00:00',
  logout_course_date datetime default NULL,
  counter int NOT NULL,
  session_id int NOT NULL default 0,
  PRIMARY KEY  (course_access_id)
);

DROP TABLE IF EXISTS track_e_hotspot;
CREATE TABLE track_e_hotspot (
  hotspot_id int NOT NULL auto_increment,
  hotspot_user_id int NOT NULL,
  hotspot_course_code varchar(50) NOT NULL,
  hotspot_exe_id int NOT NULL,
  hotspot_question_id int NOT NULL,
  hotspot_answer_id int NOT NULL,
  hotspot_correct tinyint(3) unsigned NOT NULL,
  hotspot_coordinate text NOT NULL,
  PRIMARY KEY  (hotspot_id),
  KEY hotspot_course_code (hotspot_course_code),
  KEY hotspot_user_id (hotspot_user_id),
  KEY hotspot_exe_id (hotspot_exe_id),
  KEY hotspot_question_id (hotspot_question_id)
);

DROP TABLE IF EXISTS track_e_item_property;

CREATE TABLE track_e_item_property (
  id int NOT NULL auto_increment PRIMARY KEY,
  course_id int NOT NULL,
  item_property_id int NOT NULL,
  title varchar(255),
  content text,
  progress int NOT NULL default 0,
  lastedit_date datetime NOT NULL default '0000-00-00 00:00:00',
  lastedit_user_id int  NOT NULL,
  session_id int NOT NULL default 0
);

ALTER TABLE track_e_course_access ADD INDEX (user_id);
ALTER TABLE track_e_course_access ADD INDEX (login_course_date);
ALTER TABLE track_e_course_access ADD INDEX (course_code);
ALTER TABLE track_e_course_access ADD INDEX (session_id);
ALTER TABLE track_e_access ADD INDEX (access_session_id);

ALTER TABLE track_e_online ADD INDEX (course);
ALTER TABLE track_e_online ADD INDEX (session_id);

ALTER TABLE track_e_item_property ADD INDEX (course_id, item_property_id, session_id);
ALTER TABLE track_e_downloads ADD INDEX (down_session_id);  
ALTER TABLE track_e_links ADD INDEX (links_session_id); 
ALTER TABLE track_e_uploads ADD INDEX (upload_session_id);  

--
-- Table structure for LP custom storage API
--
DROP TABLE IF EXISTS stored_values;
CREATE TABLE stored_values (
        user_id INT NOT NULL,
        sco_id INT NOT NULL,
        course_id CHAR(40) NOT NULL,
        sv_key CHAR(64) NOT NULL,
        sv_value TEXT NOT NULL
);
ALTER TABLE stored_values ADD KEY (user_id, sco_id, course_id, sv_key);
ALTER TABLE stored_values ADD UNIQUE (user_id, sco_id, course_id, sv_key);

DROP TABLE IF EXISTS stored_values_stack;
CREATE TABLE stored_values_stack (
        user_id INT NOT NULL,
        sco_id INT NOT NULL,
        stack_order INT NOT NULL,
        course_id CHAR(40) NOT NULL,
        sv_key CHAR(64) NOT NULL,
        sv_value TEXT NOT NULL
);
ALTER TABLE stored_values_stack ADD KEY (user_id, sco_id, course_id, sv_key, stack_order);
ALTER TABLE stored_values_stack ADD UNIQUE (user_id, sco_id, course_id, sv_key, stack_order);