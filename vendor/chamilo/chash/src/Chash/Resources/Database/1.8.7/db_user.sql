-- MySQL dump
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE personal_agenda (
  id int NOT NULL auto_increment,
  user int unsigned,
  title text,
  `text` text,
  `date` datetime default NULL,
  enddate datetime default NULL,
  course varchar(255),
  parent_event_id int null,
  UNIQUE KEY id (id)
);
CREATE TABLE personal_agenda_repeat (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_type VARCHAR(20),
  cal_end INT,
  cal_frequency INT DEFAULT 1,
  cal_days CHAR(7),
  PRIMARY KEY (cal_id)
);
CREATE TABLE personal_agenda_repeat_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  PRIMARY KEY ( cal_id, cal_date )
);
CREATE TABLE user_course_category (
  id int unsigned NOT NULL auto_increment,
  user_id int unsigned NOT NULL default 0,
  title text NOT NULL,
  sort int, 
  PRIMARY KEY  (id)
);