-- MySQL dump 10.13  Distrib 5.5.29, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: chamilostorm19
-- ------------------------------------------------------
-- Server version	5.5.29-0ubuntu0.12.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table c_announcement
--

DROP TABLE IF EXISTS c_announcement;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_announcement (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  title text,
  content mediumtext,
  end_date date DEFAULT NULL,
  display_order mediumint(9) NOT NULL DEFAULT '0',
  email_sent tinyint(4) DEFAULT '0',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_announcement
--

LOCK TABLES c_announcement WRITE;
/*!40000 ALTER TABLE c_announcement DISABLE KEYS */;
INSERT INTO c_announcement VALUES (1,1,'This is an announcement example','This is an announcement example. Only trainers are allowed to publish announcements.','2013-02-26',1,0,0);
/*!40000 ALTER TABLE c_announcement ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_announcement_attachment
--

DROP TABLE IF EXISTS c_announcement_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_announcement_attachment (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  path varchar(255) NOT NULL,
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  announcement_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_announcement_attachment
--

LOCK TABLES c_announcement_attachment WRITE;
/*!40000 ALTER TABLE c_announcement_attachment DISABLE KEYS */;
/*!40000 ALTER TABLE c_announcement_attachment ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_attendance
--

DROP TABLE IF EXISTS c_attendance;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  name text NOT NULL,
  description text,
  active tinyint(4) NOT NULL DEFAULT '1',
  attendance_qualify_title varchar(255) DEFAULT NULL,
  attendance_qualify_max int(11) NOT NULL DEFAULT '0',
  attendance_weight float(6,2) NOT NULL DEFAULT '0.00',
  session_id int(11) NOT NULL DEFAULT '0',
  locked int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id),
  KEY active (active)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_attendance
--

LOCK TABLES c_attendance WRITE;
/*!40000 ALTER TABLE c_attendance DISABLE KEYS */;
/*!40000 ALTER TABLE c_attendance ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_attendance_calendar
--

DROP TABLE IF EXISTS c_attendance_calendar;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_calendar (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  attendance_id int(11) NOT NULL,
  date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  done_attendance tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY attendance_id (attendance_id),
  KEY done_attendance (done_attendance)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_attendance_calendar
--

LOCK TABLES c_attendance_calendar WRITE;
/*!40000 ALTER TABLE c_attendance_calendar DISABLE KEYS */;
/*!40000 ALTER TABLE c_attendance_calendar ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_attendance_result
--

DROP TABLE IF EXISTS c_attendance_result;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_result (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  attendance_id int(11) NOT NULL,
  score int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY attendance_id (attendance_id),
  KEY user_id (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_attendance_result
--

LOCK TABLES c_attendance_result WRITE;
/*!40000 ALTER TABLE c_attendance_result DISABLE KEYS */;
/*!40000 ALTER TABLE c_attendance_result ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_attendance_sheet
--

DROP TABLE IF EXISTS c_attendance_sheet;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_sheet (
  c_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  attendance_calendar_id int(11) NOT NULL,
  presence tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,user_id,attendance_calendar_id),
  KEY presence (presence)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_attendance_sheet
--

LOCK TABLES c_attendance_sheet WRITE;
/*!40000 ALTER TABLE c_attendance_sheet DISABLE KEYS */;
/*!40000 ALTER TABLE c_attendance_sheet ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_attendance_sheet_log
--

DROP TABLE IF EXISTS c_attendance_sheet_log;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_sheet_log (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  attendance_id int(11) NOT NULL DEFAULT '0',
  lastedit_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  lastedit_type varchar(200) NOT NULL,
  lastedit_user_id int(11) NOT NULL DEFAULT '0',
  calendar_date_value datetime DEFAULT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_attendance_sheet_log
--

LOCK TABLES c_attendance_sheet_log WRITE;
/*!40000 ALTER TABLE c_attendance_sheet_log DISABLE KEYS */;
/*!40000 ALTER TABLE c_attendance_sheet_log ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog
--

DROP TABLE IF EXISTS c_blog;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog (
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL AUTO_INCREMENT,
  blog_name varchar(250) NOT NULL DEFAULT '',
  blog_subtitle varchar(250) DEFAULT NULL,
  date_creation datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  visibility tinyint(3) unsigned NOT NULL DEFAULT '0',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,blog_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table with blogs in this course';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog
--

LOCK TABLES c_blog WRITE;
/*!40000 ALTER TABLE c_blog DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog_attachment
--

DROP TABLE IF EXISTS c_blog_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_attachment (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  path varchar(255) NOT NULL COMMENT 'the real filename',
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL,
  filename varchar(255) NOT NULL COMMENT 'the user s file name',
  blog_id int(11) NOT NULL,
  comment_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog_attachment
--

LOCK TABLES c_blog_attachment WRITE;
/*!40000 ALTER TABLE c_blog_attachment DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog_attachment ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog_comment
--

DROP TABLE IF EXISTS c_blog_comment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_comment (
  c_id int(11) NOT NULL,
  comment_id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(250) NOT NULL DEFAULT '',
  comment longtext NOT NULL,
  author_id int(11) NOT NULL DEFAULT '0',
  date_creation datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  blog_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  task_id int(11) DEFAULT NULL,
  parent_comment_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,comment_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table with comments on posts in a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog_comment
--

LOCK TABLES c_blog_comment WRITE;
/*!40000 ALTER TABLE c_blog_comment DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog_comment ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog_post
--

DROP TABLE IF EXISTS c_blog_post;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_post (
  c_id int(11) NOT NULL,
  post_id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(250) NOT NULL DEFAULT '',
  full_text longtext NOT NULL,
  date_creation datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  blog_id int(11) NOT NULL DEFAULT '0',
  author_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,post_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table with posts / blog.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog_post
--

LOCK TABLES c_blog_post WRITE;
/*!40000 ALTER TABLE c_blog_post DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog_post ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog_rating
--

DROP TABLE IF EXISTS c_blog_rating;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_rating (
  c_id int(11) NOT NULL,
  rating_id int(11) NOT NULL AUTO_INCREMENT,
  blog_id int(11) NOT NULL DEFAULT '0',
  rating_type enum('post','comment') NOT NULL DEFAULT 'post',
  item_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  rating int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,rating_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table with ratings for post/comments in a certain blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog_rating
--

LOCK TABLES c_blog_rating WRITE;
/*!40000 ALTER TABLE c_blog_rating DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog_rating ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog_rel_user
--

DROP TABLE IF EXISTS c_blog_rel_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_rel_user (
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,blog_id,user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table representing users subscribed to a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog_rel_user
--

LOCK TABLES c_blog_rel_user WRITE;
/*!40000 ALTER TABLE c_blog_rel_user DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog_rel_user ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog_task
--

DROP TABLE IF EXISTS c_blog_task;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_task (
  c_id int(11) NOT NULL,
  task_id int(11) NOT NULL AUTO_INCREMENT,
  blog_id int(11) NOT NULL DEFAULT '0',
  title varchar(250) NOT NULL DEFAULT '',
  description text NOT NULL,
  color varchar(10) NOT NULL DEFAULT '',
  system_task tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,task_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table with tasks for a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog_task
--

LOCK TABLES c_blog_task WRITE;
/*!40000 ALTER TABLE c_blog_task DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog_task ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_blog_task_rel_user
--

DROP TABLE IF EXISTS c_blog_task_rel_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_task_rel_user (
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  task_id int(11) NOT NULL DEFAULT '0',
  target_date date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (c_id,blog_id,user_id,task_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Table with tasks assigned to a user in a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_blog_task_rel_user
--

LOCK TABLES c_blog_task_rel_user WRITE;
/*!40000 ALTER TABLE c_blog_task_rel_user DISABLE KEYS */;
/*!40000 ALTER TABLE c_blog_task_rel_user ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_calendar_event
--

DROP TABLE IF EXISTS c_calendar_event;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  content text,
  start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  end_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  parent_event_id int(11) DEFAULT NULL,
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  all_day int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_calendar_event
--

LOCK TABLES c_calendar_event WRITE;
/*!40000 ALTER TABLE c_calendar_event DISABLE KEYS */;
INSERT INTO c_calendar_event VALUES (1,1,'Training creation','This training has been created on this moment.','2013-02-26 14:44:56','2013-02-26 14:44:56',NULL,0,0),(1,2,'aaa 2 22','<p>&nbsp;2222</p>','2013-03-06 05:30:00','2013-03-06 11:30:00',NULL,0,0),(1,3,'especial','<p>especial</p>','2013-03-09 07:00:00','2013-03-09 13:00:00',NULL,0,0),(1,4,'aaaaaaa 333 123','<p>&nbsp;dsq dqsd qsd qsdqsd qsd qs d 123</p>','2013-03-06 08:00:00','2013-03-06 16:00:00',NULL,0,0);
/*!40000 ALTER TABLE c_calendar_event ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_calendar_event_attachment
--

DROP TABLE IF EXISTS c_calendar_event_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event_attachment (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  path varchar(255) NOT NULL,
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  agenda_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_calendar_event_attachment
--

LOCK TABLES c_calendar_event_attachment WRITE;
/*!40000 ALTER TABLE c_calendar_event_attachment DISABLE KEYS */;
INSERT INTO c_calendar_event_attachment VALUES (1,1,'513726730a55b','123',784519,2,'2sJwD.jpg');
/*!40000 ALTER TABLE c_calendar_event_attachment ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_calendar_event_repeat
--

DROP TABLE IF EXISTS c_calendar_event_repeat;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event_repeat (
  c_id int(11) NOT NULL,
  cal_id int(11) NOT NULL DEFAULT '0',
  cal_type varchar(20) DEFAULT NULL,
  cal_end int(11) DEFAULT NULL,
  cal_frequency int(11) DEFAULT '1',
  cal_days char(7) DEFAULT NULL,
  PRIMARY KEY (c_id,cal_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_calendar_event_repeat
--

LOCK TABLES c_calendar_event_repeat WRITE;
/*!40000 ALTER TABLE c_calendar_event_repeat DISABLE KEYS */;
/*!40000 ALTER TABLE c_calendar_event_repeat ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_calendar_event_repeat_not
--

DROP TABLE IF EXISTS c_calendar_event_repeat_not;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event_repeat_not (
  c_id int(11) NOT NULL,
  cal_id int(11) NOT NULL,
  cal_date int(11) NOT NULL,
  PRIMARY KEY (c_id,cal_id,cal_date)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_calendar_event_repeat_not
--

LOCK TABLES c_calendar_event_repeat_not WRITE;
/*!40000 ALTER TABLE c_calendar_event_repeat_not DISABLE KEYS */;
/*!40000 ALTER TABLE c_calendar_event_repeat_not ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_chat_connected
--

DROP TABLE IF EXISTS c_chat_connected;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_chat_connected (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL DEFAULT '0',
  last_connection datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  session_id int(11) NOT NULL DEFAULT '0',
  to_group_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id,user_id,last_connection),
  KEY char_connected_index (user_id,session_id,to_group_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_chat_connected
--

LOCK TABLES c_chat_connected WRITE;
/*!40000 ALTER TABLE c_chat_connected DISABLE KEYS */;
INSERT INTO c_chat_connected VALUES (1,1,1,'2013-03-04 19:32:20',0,0);
/*!40000 ALTER TABLE c_chat_connected ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_course_description
--

DROP TABLE IF EXISTS c_course_description;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_course_description (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) DEFAULT NULL,
  content text,
  session_id int(11) DEFAULT '0',
  description_type tinyint(3) unsigned NOT NULL DEFAULT '0',
  progress int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_course_description
--

LOCK TABLES c_course_description WRITE;
/*!40000 ALTER TABLE c_course_description DISABLE KEYS */;
/*!40000 ALTER TABLE c_course_description ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_course_setting
--

DROP TABLE IF EXISTS c_course_setting;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_course_setting (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  variable varchar(255) NOT NULL DEFAULT '',
  subkey varchar(255) DEFAULT NULL,
  type varchar(255) DEFAULT NULL,
  category varchar(255) DEFAULT NULL,
  value varchar(255) NOT NULL DEFAULT '',
  title varchar(255) NOT NULL DEFAULT '',
  comment varchar(255) DEFAULT NULL,
  subkeytext varchar(255) DEFAULT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_course_setting
--

LOCK TABLES c_course_setting WRITE;
/*!40000 ALTER TABLE c_course_setting DISABLE KEYS */;
INSERT INTO c_course_setting VALUES (1,1,'email_alert_manager_on_new_doc',NULL,NULL,'work','0','',NULL,NULL),(1,2,'email_alert_on_new_doc_dropbox',NULL,NULL,'dropbox','0','',NULL,NULL),(1,3,'allow_user_edit_agenda',NULL,NULL,'agenda','0','',NULL,NULL),(1,4,'allow_user_edit_announcement',NULL,NULL,'announcement','0','',NULL,NULL),(1,5,'email_alert_manager_on_new_quiz',NULL,NULL,'quiz','1','',NULL,NULL),(1,6,'allow_user_image_forum',NULL,NULL,'forum','1','',NULL,NULL),(1,7,'course_theme',NULL,NULL,'theme','','',NULL,NULL),(1,8,'allow_learning_path_theme',NULL,NULL,'theme','1','',NULL,NULL),(1,9,'allow_open_chat_window',NULL,NULL,'chat','1','',NULL,NULL),(1,10,'email_alert_to_teacher_on_new_user_in_course',NULL,NULL,'registration','0','',NULL,NULL),(1,11,'allow_user_view_user_list',NULL,NULL,'user','1','',NULL,NULL),(1,12,'display_info_advance_inside_homecourse',NULL,NULL,'thematic_advance','1','',NULL,NULL),(1,13,'email_alert_students_on_new_homework',NULL,NULL,'work','0','',NULL,NULL),(1,14,'enable_lp_auto_launch',NULL,NULL,'learning_path','0','',NULL,NULL),(1,15,'pdf_export_watermark_text',NULL,NULL,'learning_path','','',NULL,NULL),(1,16,'allow_public_certificates',NULL,NULL,'certificates','','',NULL,NULL);
/*!40000 ALTER TABLE c_course_setting ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_document
--

DROP TABLE IF EXISTS c_document;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_document (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  path varchar(255) NOT NULL DEFAULT '',
  comment text,
  title varchar(255) DEFAULT NULL,
  filetype set('file','folder') NOT NULL DEFAULT 'file',
  size int(11) NOT NULL DEFAULT '0',
  readonly tinyint(3) unsigned NOT NULL,
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_document
--

LOCK TABLES c_document WRITE;
/*!40000 ALTER TABLE c_document DISABLE KEYS */;
INSERT INTO c_document VALUES (1,1,'/shared_folder',NULL,'Folders of users','folder',0,0,0),(1,2,'/chat_files',NULL,'Chat conversations history','folder',0,0,0),(1,3,'/images',NULL,'Images','folder',0,0,0),(1,4,'/images/gallery',NULL,'Gallery','folder',0,0,0),(1,5,'/audio',NULL,'Audio','folder',0,0,0),(1,6,'/flash',NULL,'Flash','folder',0,0,0),(1,7,'/video',NULL,'Video','folder',0,0,0),(1,8,'/certificates',NULL,'Certificates','folder',0,0,0),(1,9,'/images/gallery/trainer',NULL,'trainer','folder',0,0,0),(1,10,'/images/gallery/small',NULL,'small','folder',0,0,0),(1,11,'/images/gallery/diagrams',NULL,'diagrams','folder',0,0,0),(1,12,'/images/gallery/diagrams/animated',NULL,'animated','folder',0,0,0),(1,13,'/images/gallery/mr_dokeos',NULL,'mr_dokeos','folder',0,0,0),(1,14,'/images/gallery/mr_dokeos/animated',NULL,'animated','folder',0,0,0),(1,15,'/images/gallery/computer.jpg',NULL,'computer.jpg','file',22319,0,0),(1,16,'/images/gallery/book.jpg',NULL,'book.jpg','file',21524,0,0),(1,17,'/images/gallery/mouse.jpg',NULL,'mouse.jpg','file',20171,0,0),(1,18,'/images/gallery/tutorial.jpg',NULL,'tutorial.jpg','file',18883,0,0),(1,19,'/images/gallery/note.jpg',NULL,'note.jpg','file',10948,0,0),(1,20,'/images/gallery/book_highlight.jpg',NULL,'book_highlight.jpg','file',23635,0,0),(1,21,'/images/gallery/idea.jpg',NULL,'idea.jpg','file',20308,0,0),(1,22,'/images/gallery/trainer/trainer_smile.png',NULL,'trainer_smile.png','file',16683,0,0),(1,23,'/images/gallery/trainer/trainer_face.png',NULL,'trainer_face.png','file',38924,0,0),(1,24,'/images/gallery/trainer/trainer_staring.png',NULL,'trainer_staring.png','file',22486,0,0),(1,25,'/images/gallery/trainer/trainer_join_hands.png',NULL,'trainer_join_hands.png','file',37865,0,0),(1,26,'/images/gallery/trainer/trainer_points_right.png',NULL,'trainer_points_right.png','file',26535,0,0),(1,27,'/images/gallery/trainer/trainer_case.png',NULL,'trainer_case.png','file',33094,0,0),(1,28,'/images/gallery/trainer/trainer_points_left.png',NULL,'trainer_points_left.png','file',27068,0,0),(1,29,'/images/gallery/trainer/trainer_reads.png',NULL,'trainer_reads.png','file',30209,0,0),(1,30,'/images/gallery/trainer/trainer_standing.png',NULL,'trainer_standing.png','file',31892,0,0),(1,31,'/images/gallery/trainer/trainer_glasses.png',NULL,'trainer_glasses.png','file',45939,0,0),(1,32,'/images/gallery/trainer/trainer_chair.png',NULL,'trainer_chair.png','file',53450,0,0),(1,33,'/images/gallery/trainer/trainer_join_left.png',NULL,'trainer_join_left.png','file',37600,0,0),(1,34,'/images/gallery/homework.jpg',NULL,'homework.jpg','file',24694,0,0),(1,35,'/images/gallery/maths.jpg',NULL,'maths.jpg','file',21865,0,0),(1,36,'/images/gallery/board.jpg',NULL,'board.jpg','file',24054,0,0),(1,37,'/images/gallery/presentation.jpg',NULL,'presentation.jpg','file',20445,0,0),(1,38,'/images/gallery/servicesgather.png',NULL,'servicesgather.png','file',19575,0,0),(1,39,'/images/gallery/time.jpg',NULL,'time.jpg','file',26028,0,0),(1,40,'/images/gallery/emot_happy.jpg',NULL,'emot_happy.jpg','file',21933,0,0),(1,41,'/images/gallery/logo_dokeos.png',NULL,'logo_dokeos.png','file',5136,0,0),(1,42,'/images/gallery/write.jpg',NULL,'write.jpg','file',19862,0,0),(1,43,'/images/gallery/speech.jpg',NULL,'speech.jpg','file',22652,0,0),(1,44,'/images/gallery/small/computer.jpg',NULL,'computer.jpg','file',13673,0,0),(1,45,'/images/gallery/small/left.jpg',NULL,'left.jpg','file',13974,0,0),(1,46,'/images/gallery/small/videoconference.jpg',NULL,'videoconference.jpg','file',13700,0,0),(1,47,'/images/gallery/small/mouse.jpg',NULL,'mouse.jpg','file',13662,0,0),(1,48,'/images/gallery/small/mime_zip.jpg',NULL,'mime_zip.jpg','file',13766,0,0),(1,49,'/images/gallery/small/tutorial.jpg',NULL,'tutorial.jpg','file',13560,0,0),(1,50,'/images/gallery/small/up.jpg',NULL,'up.jpg','file',13961,0,0),(1,51,'/images/gallery/small/collaboration.jpg',NULL,'collaboration.jpg','file',13812,0,0),(1,52,'/images/gallery/small/mime_flash.jpg',NULL,'mime_flash.jpg','file',13867,0,0),(1,53,'/images/gallery/small/09.png',NULL,'09.png','file',6343,0,0),(1,54,'/images/gallery/small/email.jpg',NULL,'email.jpg','file',13682,0,0),(1,55,'/images/gallery/small/02.png',NULL,'02.png','file',6260,0,0),(1,56,'/images/gallery/small/important.jpg',NULL,'important.jpg','file',13929,0,0),(1,57,'/images/gallery/small/01.png',NULL,'01.png','file',6112,0,0),(1,58,'/images/gallery/small/teacher.jpg',NULL,'teacher.jpg','file',13879,0,0),(1,59,'/images/gallery/small/teacher_male.jpg',NULL,'teacher_male.jpg','file',13660,0,0),(1,60,'/images/gallery/small/mime_excel.jpg',NULL,'mime_excel.jpg','file',13595,0,0),(1,61,'/images/gallery/small/agenda.jpg',NULL,'agenda.jpg','file',14108,0,0),(1,62,'/images/gallery/small/mime_music.jpg',NULL,'mime_music.jpg','file',13763,0,0),(1,63,'/images/gallery/small/maths.jpg',NULL,'maths.jpg','file',13756,0,0),(1,64,'/images/gallery/small/arrow.png',NULL,'arrow.png','file',1441,0,0),(1,65,'/images/gallery/small/04.png',NULL,'04.png','file',6147,0,0),(1,66,'/images/gallery/small/05.png',NULL,'05.png','file',6201,0,0),(1,67,'/images/gallery/small/talking.jpg',NULL,'talking.jpg','file',13812,0,0),(1,68,'/images/gallery/small/board.jpg',NULL,'board.jpg','file',13913,0,0),(1,69,'/images/gallery/small/work.jpg',NULL,'work.jpg','file',13827,0,0),(1,70,'/images/gallery/small/07.png',NULL,'07.png','file',6130,0,0),(1,71,'/images/gallery/small/help.jpg',NULL,'help.jpg','file',14025,0,0),(1,72,'/images/gallery/small/annoncement.jpg',NULL,'annoncement.jpg','file',13770,0,0),(1,73,'/images/gallery/small/right.jpg',NULL,'right.jpg','file',13903,0,0),(1,74,'/images/gallery/small/08.png',NULL,'08.png','file',6351,0,0),(1,75,'/images/gallery/small/button_ok.jpg',NULL,'button_ok.jpg','file',13817,0,0),(1,76,'/images/gallery/small/mime_publisher.jpg',NULL,'mime_publisher.jpg','file',13665,0,0),(1,77,'/images/gallery/small/listen.jpg',NULL,'listen.jpg','file',13727,0,0),(1,78,'/images/gallery/small/accessibilty.jpg',NULL,'accessibilty.jpg','file',13859,0,0),(1,79,'/images/gallery/small/emot_happy.jpg',NULL,'emot_happy.jpg','file',14110,0,0),(1,80,'/images/gallery/small/speak.jpg',NULL,'speak.jpg','file',13520,0,0),(1,81,'/images/gallery/small/mime_ppt.jpg',NULL,'mime_ppt.jpg','file',13590,0,0),(1,82,'/images/gallery/small/save.jpg',NULL,'save.jpg','file',13955,0,0),(1,83,'/images/gallery/small/06.png',NULL,'06.png','file',6296,0,0),(1,84,'/images/gallery/small/00.png',NULL,'00.png','file',6285,0,0),(1,85,'/images/gallery/small/mime_audio.jpg',NULL,'mime_audio.jpg','file',13709,0,0),(1,86,'/images/gallery/small/search.jpg',NULL,'search.jpg','file',13473,0,0),(1,87,'/images/gallery/small/mime_visio.jpg',NULL,'mime_visio.jpg','file',13749,0,0),(1,88,'/images/gallery/small/mime_pdf.jpg',NULL,'mime_pdf.jpg','file',14044,0,0),(1,89,'/images/gallery/small/emot_wink.jpg',NULL,'emot_wink.jpg','file',14074,0,0),(1,90,'/images/gallery/small/emot_neutral.jpg',NULL,'emot_neutral.jpg','file',13672,0,0),(1,91,'/images/gallery/small/down.jpg',NULL,'down.jpg','file',13862,0,0),(1,92,'/images/gallery/small/button_cancel.jpg',NULL,'button_cancel.jpg','file',14156,0,0),(1,93,'/images/gallery/small/mime_access.jpg',NULL,'mime_access.jpg','file',13681,0,0),(1,94,'/images/gallery/small/attach.jpg',NULL,'attach.jpg','file',14049,0,0),(1,95,'/images/gallery/small/emot_sad.jpg',NULL,'emot_sad.jpg','file',14085,0,0),(1,96,'/images/gallery/small/mime_word.jpg',NULL,'mime_word.jpg','file',13640,0,0),(1,97,'/images/gallery/small/group.jpg',NULL,'group.jpg','file',13894,0,0),(1,98,'/images/gallery/small/chart.jpg',NULL,'chart.jpg','file',14129,0,0),(1,99,'/images/gallery/small/science.jpg',NULL,'science.jpg','file',13851,0,0),(1,100,'/images/gallery/small/quicktime.jpg',NULL,'quicktime.jpg','file',13806,0,0),(1,101,'/images/gallery/small/03.png',NULL,'03.png','file',6324,0,0),(1,102,'/images/gallery/small/fish.jpg',NULL,'fish.jpg','file',13768,0,0),(1,103,'/images/gallery/small/bookcase.jpg',NULL,'bookcase.jpg','file',14038,0,0),(1,104,'/images/gallery/small/redlight.jpg',NULL,'redlight.jpg','file',13505,0,0),(1,105,'/images/gallery/small/mime_movie.jpg',NULL,'mime_movie.jpg','file',13628,0,0),(1,106,'/images/gallery/silhouette.png',NULL,'silhouette.png','file',4358,0,0),(1,107,'/images/gallery/emot_wink.jpg',NULL,'emot_wink.jpg','file',21552,0,0),(1,108,'/images/gallery/emot_neutral.jpg',NULL,'emot_neutral.jpg','file',20766,0,0),(1,109,'/images/gallery/diagrams/precession.jpg',NULL,'precession.jpg','file',8319,0,0),(1,110,'/images/gallery/diagrams/europemap.jpg',NULL,'europemap.jpg','file',61208,0,0),(1,111,'/images/gallery/diagrams/oilwell.jpg',NULL,'oilwell.jpg','file',23782,0,0),(1,112,'/images/gallery/diagrams/matching_electric_1.png',NULL,'matching_electric_1.png','file',11174,0,0),(1,113,'/images/gallery/diagrams/barbitursyra.jpg',NULL,'barbitursyra.jpg','file',6811,0,0),(1,114,'/images/gallery/diagrams/yalta_1.png',NULL,'yalta_1.png','file',31706,0,0),(1,115,'/images/gallery/diagrams/coccidioides.jpg',NULL,'coccidioides.jpg','file',27833,0,0),(1,116,'/images/gallery/diagrams/animated/anim_pendul.gif',NULL,'anim_pendul.gif','file',49559,0,0),(1,117,'/images/gallery/diagrams/animated/anim_corriolis.gif',NULL,'anim_corriolis.gif','file',151742,0,0),(1,118,'/images/gallery/diagrams/animated/anim_yugoslavia.gif',NULL,'anim_yugoslavia.gif','file',44252,0,0),(1,119,'/images/gallery/diagrams/animated/anim_wave_frequency.gif',NULL,'anim_wave_frequency.gif','file',97141,0,0),(1,120,'/images/gallery/diagrams/animated/anim_electrolysis.gif',NULL,'anim_electrolysis.gif','file',49668,0,0),(1,121,'/images/gallery/diagrams/animated/anim_twostroke.gif',NULL,'anim_twostroke.gif','file',209400,0,0),(1,122,'/images/gallery/diagrams/animated/anim_japanese.gif',NULL,'anim_japanese.gif','file',11892,0,0),(1,123,'/images/gallery/diagrams/animated/anim_rome.gif',NULL,'anim_rome.gif','file',401677,0,0),(1,124,'/images/gallery/diagrams/animated/anim_loco.gif',NULL,'anim_loco.gif','file',87422,0,0),(1,125,'/images/gallery/diagrams/alaska_chart.png',NULL,'alaska_chart.png','file',35312,0,0),(1,126,'/images/gallery/diagrams/constitution_1.png',NULL,'constitution_1.png','file',50616,0,0),(1,127,'/images/gallery/diagrams/piano.jpg',NULL,'piano.jpg','file',26538,0,0),(1,128,'/images/gallery/diagrams/synapse.jpg',NULL,'synapse.jpg','file',17330,0,0),(1,129,'/images/gallery/diagrams/halleffect.jpg',NULL,'halleffect.jpg','file',49877,0,0),(1,130,'/images/gallery/diagrams/dokeos_stones.png',NULL,'dokeos_stones.png','file',95264,0,0),(1,131,'/images/gallery/diagrams/pythagore.jpg',NULL,'pythagore.jpg','file',6308,0,0),(1,132,'/images/gallery/diagrams/brain.png',NULL,'brain.png','file',103017,0,0),(1,133,'/images/gallery/diagrams/chainette_formule.jpg',NULL,'chainette_formule.jpg','file',14880,0,0),(1,134,'/images/gallery/diagrams/top_arrow.png',NULL,'top_arrow.png','file',2806,0,0),(1,135,'/images/gallery/diagrams/bottom_arrow.png',NULL,'bottom_arrow.png','file',2841,0,0),(1,136,'/images/gallery/diagrams/spectre.jpg',NULL,'spectre.jpg','file',27391,0,0),(1,137,'/images/gallery/diagrams/tetralogy.png',NULL,'tetralogy.png','file',75038,0,0),(1,138,'/images/gallery/diagrams/receiver.jpg',NULL,'receiver.jpg','file',4745,0,0),(1,139,'/images/gallery/diagrams/elearning_project.png',NULL,'elearning_project.png','file',37981,0,0),(1,140,'/images/gallery/diagrams/dokeos_stones_external.png',NULL,'dokeos_stones_external.png','file',218411,0,0),(1,141,'/images/gallery/diagrams/bandgap_dv.jpg',NULL,'bandgap_dv.jpg','file',11121,0,0),(1,142,'/images/gallery/diagrams/gearbox.jpg',NULL,'gearbox.jpg','file',31176,0,0),(1,143,'/images/gallery/diagrams/waterloo.png',NULL,'waterloo.png','file',41939,0,0),(1,144,'/images/gallery/diagrams/argandgaussplane.jpg',NULL,'argandgaussplane.jpg','file',7754,0,0),(1,145,'/images/gallery/diagrams/pc.jpg',NULL,'pc.jpg','file',13711,0,0),(1,146,'/images/gallery/diagrams/asus-eee-pc.jpg',NULL,'asus-eee-pc.jpg','file',21133,0,0),(1,147,'/images/gallery/diagrams/lightbulb.jpg',NULL,'lightbulb.jpg','file',24030,0,0),(1,148,'/images/gallery/diagrams/accident_1.png',NULL,'accident_1.png','file',151329,0,0),(1,149,'/images/gallery/diagrams/head_olfactory_nerve.png',NULL,'head_olfactory_nerve.png','file',182785,0,0),(1,150,'/images/gallery/diagrams/distance.png',NULL,'distance.png','file',2929,0,0),(1,151,'/images/gallery/diagrams/velocity.jpg',NULL,'velocity.jpg','file',6536,0,0),(1,152,'/images/gallery/diagrams/radiograph.png',NULL,'radiograph.png','file',46503,0,0),(1,153,'/images/gallery/diagrams/europecouncilorange200_1.png',NULL,'europecouncilorange200_1.png','file',88995,0,0),(1,154,'/images/gallery/emot_sad.jpg',NULL,'emot_sad.jpg','file',21522,0,0),(1,155,'/images/gallery/interaction.jpg',NULL,'interaction.jpg','file',22036,0,0),(1,156,'/images/gallery/female.jpg',NULL,'female.jpg','file',20454,0,0),(1,157,'/images/gallery/mr_dokeos/animated/creativeAnim.gif',NULL,'creativeAnim.gif','file',15656,0,0),(1,158,'/images/gallery/mr_dokeos/animated/readerAnim.gif',NULL,'readerAnim.gif','file',15393,0,0),(1,159,'/images/gallery/mr_dokeos/animated/practicerAnim.gif',NULL,'practicerAnim.gif','file',22164,0,0),(1,160,'/images/gallery/mr_dokeos/animated/pointerAnim.gif',NULL,'pointerAnim.gif','file',24349,0,0),(1,161,'/images/gallery/mr_dokeos/animated/teacherAnim.gif',NULL,'teacherAnim.gif','file',20693,0,0),(1,162,'/images/gallery/mr_dokeos/animated/thinkerAnim.gif',NULL,'thinkerAnim.gif','file',32847,0,0),(1,163,'/images/gallery/mr_dokeos/collaborative.png',NULL,'collaborative.png','file',35703,0,0),(1,164,'/images/gallery/mr_dokeos/mr_dokeosleft.png',NULL,'mr_dokeosleft.png','file',6858,0,0),(1,165,'/images/gallery/mr_dokeos/collaborating.jpg',NULL,'collaborating.jpg','file',20052,0,0),(1,166,'/images/gallery/mr_dokeos/pointing_right.jpg',NULL,'pointing_right.jpg','file',18574,0,0),(1,167,'/images/gallery/mr_dokeos/teaching.jpg',NULL,'teaching.jpg','file',19512,0,0),(1,168,'/images/gallery/mr_dokeos/collaborative_big.png',NULL,'collaborative_big.png','file',32541,0,0),(1,169,'/images/gallery/mr_dokeos/anim_thinking.jpg',NULL,'anim_thinking.jpg','file',28710,0,0),(1,170,'/images/gallery/mr_dokeos/reading.jpg',NULL,'reading.jpg','file',19006,0,0),(1,171,'/images/gallery/mr_dokeos/thinking.jpg',NULL,'thinking.jpg','file',18384,0,0),(1,172,'/images/gallery/mr_dokeos/anim_pointing_right.jpg',NULL,'anim_pointing_right.jpg','file',29795,0,0),(1,173,'/images/gallery/mr_dokeos/writing.jpg',NULL,'writing.jpg','file',18580,0,0),(1,174,'/images/gallery/mr_dokeos/anim_teaching.jpg',NULL,'anim_teaching.jpg','file',34214,0,0),(1,175,'/images/gallery/mr_dokeos/practicing.jpg',NULL,'practicing.jpg','file',20816,0,0),(1,176,'/images/gallery/mr_dokeos/anim_practicing.jpg',NULL,'anim_practicing.jpg','file',38355,0,0),(1,177,'/images/gallery/mr_dokeos/group.jpg',NULL,'group.jpg','file',20481,0,0),(1,178,'/images/gallery/mr_dokeos/anim_pointing_left.jpg',NULL,'anim_pointing_left.jpg','file',19489,0,0),(1,179,'/images/gallery/mr_dokeos/pointing_left.jpg',NULL,'pointing_left.jpg','file',18391,0,0),(1,180,'/images/gallery/mr_dokeos/anim_writing.jpg',NULL,'anim_writing.jpg','file',32445,0,0),(1,181,'/images/gallery/mr_dokeos/anim_reading.jpg',NULL,'anim_reading.jpg','file',33330,0,0),(1,182,'/images/gallery/science.jpg',NULL,'science.jpg','file',24035,0,0),(1,183,'/images/gallery/male.jpg',NULL,'male.jpg','file',19132,0,0),(1,184,'/images/gallery/twopeople.png',NULL,'twopeople.png','file',10453,0,0),(1,185,'/images/gallery/newspaper.jpg',NULL,'newspaper.jpg','file',21935,0,0),(1,186,'/images/gallery/world.jpg',NULL,'world.jpg','file',22705,0,0),(1,187,'/images/gallery/pencil.png',NULL,'pencil.png','file',4794,0,0),(1,188,'/images/gallery/geometry.jpg',NULL,'geometry.jpg','file',27041,0,0),(1,189,'/images/gallery/mechanism.jpg',NULL,'mechanism.jpg','file',24639,0,0),(1,190,'/images/gallery/bookcase.jpg',NULL,'bookcase.jpg','file',26783,0,0),(1,191,'/images/gallery/redlight.jpg',NULL,'redlight.jpg','file',20374,0,0),(1,192,'/audio/ListeningComprehension.mp3',NULL,'ListeningComprehension.mp3','file',147854,0,0),(1,193,'/flash/ArtefactsInRMI.swf',NULL,'ArtefactsInRMI.swf','file',100988,0,0),(1,194,'/flash/SpinEchoSequence.swf',NULL,'SpinEchoSequence.swf','file',17904,0,0),(1,195,'/flash/PonderationOfMrSignal.swf',NULL,'PonderationOfMrSignal.swf','file',18825,0,0),(1,196,'/video/flv',NULL,'flv','folder',0,0,0),(1,197,'/video/flv/example.flv',NULL,'example.flv','file',1093260,0,0),(1,198,'/video/painting.mpg',NULL,'painting.mpg','file',2353702,0,0),(1,199,'/certificates/default.html',NULL,'default.html','file',2157,0,0),(1,200,'/chat_files/messages-2013-03-04.log.html','','messages-2013-03-04.log.html','file',1128,0,0),(1,201,'/chat_files/messages-2013-03-04-1.log.html','','messages-2013-03-04-1.log.html','file',2592,0,0),(1,202,'/Group_0001_groupdocs','','Group_0001_groupdocs','folder',0,0,0),(1,203,'/Group_0002_groupdocs','','Group_0002_groupdocs','folder',0,0,0),(1,204,'/Group_0003_groupdocs','','Group_0003_groupdocs','folder',0,0,0),(1,205,'/Group_0004_groupdocs','','Group_0004_groupdocs','folder',0,0,0),(1,206,'/Group_0005_groupdocs','','Group_0005_groupdocs','folder',0,0,0),(1,207,'/learning_path','','Learning paths','folder',0,0,0),(1,208,'/learning_path/lols','','lols','folder',0,0,0),(1,209,'/learning_path/lols/great.html','','great','file',270,0,0),(1,210,'/learning_path/123','','123','folder',0,0,0),(1,211,'/learning_path/123/1111.html','','1111','file',264,0,0);
/*!40000 ALTER TABLE c_document ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_dropbox_category
--

DROP TABLE IF EXISTS c_dropbox_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_category (
  c_id int(11) NOT NULL,
  cat_id int(11) NOT NULL AUTO_INCREMENT,
  cat_name text NOT NULL,
  received tinyint(3) unsigned NOT NULL DEFAULT '0',
  sent tinyint(3) unsigned NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,cat_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_dropbox_category
--

LOCK TABLES c_dropbox_category WRITE;
/*!40000 ALTER TABLE c_dropbox_category DISABLE KEYS */;
/*!40000 ALTER TABLE c_dropbox_category ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_dropbox_feedback
--

DROP TABLE IF EXISTS c_dropbox_feedback;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_feedback (
  c_id int(11) NOT NULL,
  feedback_id int(11) NOT NULL AUTO_INCREMENT,
  file_id int(11) NOT NULL DEFAULT '0',
  author_user_id int(11) NOT NULL DEFAULT '0',
  feedback text NOT NULL,
  feedback_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (c_id,feedback_id),
  KEY file_id (file_id),
  KEY author_user_id (author_user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_dropbox_feedback
--

LOCK TABLES c_dropbox_feedback WRITE;
/*!40000 ALTER TABLE c_dropbox_feedback DISABLE KEYS */;
/*!40000 ALTER TABLE c_dropbox_feedback ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_dropbox_file
--

DROP TABLE IF EXISTS c_dropbox_file;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_file (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  uploader_id int(10) unsigned NOT NULL DEFAULT '0',
  filename varchar(250) NOT NULL DEFAULT '',
  filesize int(10) unsigned NOT NULL,
  title varchar(250) DEFAULT '',
  description varchar(250) DEFAULT '',
  author varchar(250) DEFAULT '',
  upload_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  last_upload_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  cat_id int(11) NOT NULL DEFAULT '0',
  session_id int(10) unsigned NOT NULL,
  PRIMARY KEY (c_id,id),
  UNIQUE KEY UN_filename (filename),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_dropbox_file
--

LOCK TABLES c_dropbox_file WRITE;
/*!40000 ALTER TABLE c_dropbox_file DISABLE KEYS */;
/*!40000 ALTER TABLE c_dropbox_file ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_dropbox_person
--

DROP TABLE IF EXISTS c_dropbox_person;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_person (
  c_id int(11) NOT NULL,
  file_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,file_id,user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_dropbox_person
--

LOCK TABLES c_dropbox_person WRITE;
/*!40000 ALTER TABLE c_dropbox_person DISABLE KEYS */;
/*!40000 ALTER TABLE c_dropbox_person ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_dropbox_post
--

DROP TABLE IF EXISTS c_dropbox_post;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_post (
  c_id int(11) NOT NULL,
  file_id int(10) unsigned NOT NULL,
  dest_user_id int(10) unsigned NOT NULL DEFAULT '0',
  feedback_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  feedback text,
  cat_id int(11) NOT NULL DEFAULT '0',
  session_id int(10) unsigned NOT NULL,
  PRIMARY KEY (c_id,file_id,dest_user_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_dropbox_post
--

LOCK TABLES c_dropbox_post WRITE;
/*!40000 ALTER TABLE c_dropbox_post DISABLE KEYS */;
/*!40000 ALTER TABLE c_dropbox_post ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_attachment
--

DROP TABLE IF EXISTS c_forum_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_attachment (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  path varchar(255) NOT NULL,
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_attachment
--

LOCK TABLES c_forum_attachment WRITE;
/*!40000 ALTER TABLE c_forum_attachment DISABLE KEYS */;
/*!40000 ALTER TABLE c_forum_attachment ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_category
--

DROP TABLE IF EXISTS c_forum_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_category (
  c_id int(11) NOT NULL,
  cat_id int(11) NOT NULL AUTO_INCREMENT,
  cat_title varchar(255) NOT NULL DEFAULT '',
  cat_comment text,
  cat_order int(11) NOT NULL DEFAULT '0',
  locked int(11) NOT NULL DEFAULT '0',
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,cat_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_category
--

LOCK TABLES c_forum_category WRITE;
/*!40000 ALTER TABLE c_forum_category DISABLE KEYS */;
INSERT INTO c_forum_category VALUES (1,1,'Example Forum Category','',1,0,0);
/*!40000 ALTER TABLE c_forum_category ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_forum
--

DROP TABLE IF EXISTS c_forum_forum;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_forum (
  c_id int(11) NOT NULL,
  forum_id int(11) NOT NULL AUTO_INCREMENT,
  forum_title varchar(255) NOT NULL DEFAULT '',
  forum_comment text,
  forum_threads int(11) DEFAULT '0',
  forum_posts int(11) DEFAULT '0',
  forum_last_post int(11) DEFAULT '0',
  forum_category int(11) DEFAULT NULL,
  allow_anonymous int(11) DEFAULT NULL,
  allow_edit int(11) DEFAULT NULL,
  approval_direct_post varchar(20) DEFAULT NULL,
  allow_attachments int(11) DEFAULT NULL,
  allow_new_threads int(11) DEFAULT NULL,
  default_view varchar(20) DEFAULT NULL,
  forum_of_group varchar(20) DEFAULT NULL,
  forum_group_public_private varchar(20) DEFAULT 'public',
  forum_order int(11) DEFAULT NULL,
  locked int(11) NOT NULL DEFAULT '0',
  session_id int(11) NOT NULL DEFAULT '0',
  forum_image varchar(255) NOT NULL DEFAULT '',
  start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (c_id,forum_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_forum
--

LOCK TABLES c_forum_forum WRITE;
/*!40000 ALTER TABLE c_forum_forum DISABLE KEYS */;
INSERT INTO c_forum_forum VALUES (1,1,'Example Forum','',0,0,0,1,0,1,NULL,0,1,'flat','0','public',1,0,0,'','0000-00-00 00:00:00','0000-00-00 00:00:00'),(1,2,'Group 0001','',0,0,0,1,0,0,'0',1,1,'flat','1','public',2,0,0,'','0000-00-00 00:00:00','0000-00-00 00:00:00'),(1,3,'Group 0002','',0,0,0,1,0,0,'0',1,1,'flat','2','public',3,0,0,'','0000-00-00 00:00:00','0000-00-00 00:00:00'),(1,4,'Group 0003','',0,0,0,1,0,0,'0',1,1,'flat','3','public',4,0,0,'','0000-00-00 00:00:00','0000-00-00 00:00:00'),(1,5,'Group 0004','',0,0,0,1,0,0,'0',1,1,'flat','4','public',5,0,0,'','0000-00-00 00:00:00','0000-00-00 00:00:00'),(1,6,'Group 0005','',0,0,0,1,0,0,'0',1,1,'flat','5','public',6,0,0,'','0000-00-00 00:00:00','0000-00-00 00:00:00');
/*!40000 ALTER TABLE c_forum_forum ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_mailcue
--

DROP TABLE IF EXISTS c_forum_mailcue;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_mailcue (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  thread_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id,c_id,thread_id,user_id,post_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_mailcue
--

LOCK TABLES c_forum_mailcue WRITE;
/*!40000 ALTER TABLE c_forum_mailcue DISABLE KEYS */;
/*!40000 ALTER TABLE c_forum_mailcue ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_notification
--

DROP TABLE IF EXISTS c_forum_notification;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_notification (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  forum_id int(11) NOT NULL DEFAULT '0',
  thread_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id,c_id,user_id,forum_id,thread_id,post_id),
  KEY user_id (user_id),
  KEY forum_id (forum_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_notification
--

LOCK TABLES c_forum_notification WRITE;
/*!40000 ALTER TABLE c_forum_notification DISABLE KEYS */;
/*!40000 ALTER TABLE c_forum_notification ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_post
--

DROP TABLE IF EXISTS c_forum_post;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_post (
  c_id int(11) NOT NULL,
  post_id int(11) NOT NULL AUTO_INCREMENT,
  post_title varchar(250) DEFAULT NULL,
  post_text text,
  thread_id int(11) DEFAULT '0',
  forum_id int(11) DEFAULT '0',
  poster_id int(11) DEFAULT '0',
  poster_name varchar(100) DEFAULT '',
  post_date datetime DEFAULT '0000-00-00 00:00:00',
  post_notification tinyint(4) DEFAULT '0',
  post_parent_id int(11) DEFAULT '0',
  visible tinyint(4) DEFAULT '1',
  PRIMARY KEY (c_id,post_id),
  KEY poster_id (poster_id),
  KEY forum_id (forum_id),
  KEY idx_forum_post_thread_id (thread_id),
  KEY idx_forum_post_visible (visible)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_post
--

LOCK TABLES c_forum_post WRITE;
/*!40000 ALTER TABLE c_forum_post DISABLE KEYS */;
INSERT INTO c_forum_post VALUES (1,1,'Example Thread','Example content',1,1,1,'','2013-02-26 14:44:56',0,0,1);
/*!40000 ALTER TABLE c_forum_post ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_thread
--

DROP TABLE IF EXISTS c_forum_thread;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_thread (
  c_id int(11) NOT NULL,
  thread_id int(11) NOT NULL AUTO_INCREMENT,
  thread_title varchar(255) DEFAULT NULL,
  forum_id int(11) DEFAULT NULL,
  thread_replies int(11) DEFAULT '0',
  thread_poster_id int(11) DEFAULT NULL,
  thread_poster_name varchar(100) DEFAULT '',
  thread_views int(11) DEFAULT '0',
  thread_last_post int(11) DEFAULT NULL,
  thread_date datetime DEFAULT '0000-00-00 00:00:00',
  thread_sticky tinyint(3) unsigned DEFAULT '0',
  locked int(11) NOT NULL DEFAULT '0',
  session_id int(10) unsigned DEFAULT NULL,
  thread_title_qualify varchar(255) DEFAULT '',
  thread_qualify_max float(6,2) unsigned NOT NULL DEFAULT '0.00',
  thread_close_date datetime DEFAULT '0000-00-00 00:00:00',
  thread_weight float(6,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (c_id,thread_id),
  KEY idx_forum_thread_forum_id (forum_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_thread
--

LOCK TABLES c_forum_thread WRITE;
/*!40000 ALTER TABLE c_forum_thread DISABLE KEYS */;
INSERT INTO c_forum_thread VALUES (1,1,'Example Thread',1,0,1,'',0,1,'2013-02-26 14:44:56',0,0,0,'',10.00,'0000-00-00 00:00:00',0.00);
/*!40000 ALTER TABLE c_forum_thread ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_thread_qualify
--

DROP TABLE IF EXISTS c_forum_thread_qualify;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_thread_qualify (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  thread_id int(11) NOT NULL,
  qualify float(6,2) NOT NULL DEFAULT '0.00',
  qualify_user_id int(11) DEFAULT NULL,
  qualify_time datetime DEFAULT '0000-00-00 00:00:00',
  session_id int(11) DEFAULT NULL,
  PRIMARY KEY (c_id,id),
  KEY user_id (user_id,thread_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_thread_qualify
--

LOCK TABLES c_forum_thread_qualify WRITE;
/*!40000 ALTER TABLE c_forum_thread_qualify DISABLE KEYS */;
/*!40000 ALTER TABLE c_forum_thread_qualify ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_forum_thread_qualify_log
--

DROP TABLE IF EXISTS c_forum_thread_qualify_log;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_thread_qualify_log (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  thread_id int(11) NOT NULL,
  qualify float(6,2) NOT NULL DEFAULT '0.00',
  qualify_user_id int(11) DEFAULT NULL,
  qualify_time datetime DEFAULT '0000-00-00 00:00:00',
  session_id int(11) DEFAULT NULL,
  PRIMARY KEY (c_id,id),
  KEY user_id (user_id,thread_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_forum_thread_qualify_log
--

LOCK TABLES c_forum_thread_qualify_log WRITE;
/*!40000 ALTER TABLE c_forum_thread_qualify_log DISABLE KEYS */;
/*!40000 ALTER TABLE c_forum_thread_qualify_log ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_glossary
--

DROP TABLE IF EXISTS c_glossary;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_glossary (
  c_id int(11) NOT NULL,
  glossary_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  description text NOT NULL,
  display_order int(11) DEFAULT NULL,
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,glossary_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_glossary
--

LOCK TABLES c_glossary WRITE;
/*!40000 ALTER TABLE c_glossary DISABLE KEYS */;
/*!40000 ALTER TABLE c_glossary ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_group_category
--

DROP TABLE IF EXISTS c_group_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_category (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL DEFAULT '',
  description text NOT NULL,
  doc_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  calendar_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  work_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  announcements_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  forum_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  wiki_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  chat_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  max_student int(10) unsigned NOT NULL DEFAULT '8',
  self_reg_allowed tinyint(3) unsigned NOT NULL DEFAULT '0',
  self_unreg_allowed tinyint(3) unsigned NOT NULL DEFAULT '0',
  groups_per_user int(10) unsigned NOT NULL DEFAULT '0',
  display_order int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_group_category
--

LOCK TABLES c_group_category WRITE;
/*!40000 ALTER TABLE c_group_category DISABLE KEYS */;
INSERT INTO c_group_category VALUES (1,2,'Default groups','',1,1,1,1,1,1,1,8,0,0,0,0);
/*!40000 ALTER TABLE c_group_category ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_group_info
--

DROP TABLE IF EXISTS c_group_info;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_info (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(100) DEFAULT NULL,
  category_id int(10) unsigned NOT NULL DEFAULT '0',
  description text,
  max_student int(10) unsigned NOT NULL DEFAULT '8',
  doc_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  calendar_state tinyint(3) unsigned NOT NULL DEFAULT '0',
  work_state tinyint(3) unsigned NOT NULL DEFAULT '0',
  announcements_state tinyint(3) unsigned NOT NULL DEFAULT '0',
  forum_state tinyint(3) unsigned NOT NULL DEFAULT '0',
  wiki_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  chat_state tinyint(3) unsigned NOT NULL DEFAULT '1',
  secret_directory varchar(255) DEFAULT NULL,
  self_registration_allowed tinyint(3) unsigned NOT NULL DEFAULT '0',
  self_unregistration_allowed tinyint(3) unsigned NOT NULL DEFAULT '0',
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_group_info
--

LOCK TABLES c_group_info WRITE;
/*!40000 ALTER TABLE c_group_info DISABLE KEYS */;
INSERT INTO c_group_info VALUES (1,1,'Group 0001',2,NULL,8,1,1,1,1,1,1,1,'/Group_0001_groupdocs',0,0,0),(1,2,'Group 0002',2,NULL,8,1,1,1,1,1,1,1,'/Group_0002_groupdocs',0,0,0),(1,3,'Group 0003',2,NULL,8,1,1,1,1,1,1,1,'/Group_0003_groupdocs',0,0,0),(1,4,'Group 0004',2,NULL,8,1,1,1,1,1,1,1,'/Group_0004_groupdocs',0,0,0),(1,5,'Group 0005',2,NULL,8,1,1,1,1,1,1,1,'/Group_0005_groupdocs',0,0,0);
/*!40000 ALTER TABLE c_group_info ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_group_rel_tutor
--

DROP TABLE IF EXISTS c_group_rel_tutor;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_rel_tutor (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  group_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_group_rel_tutor
--

LOCK TABLES c_group_rel_tutor WRITE;
/*!40000 ALTER TABLE c_group_rel_tutor DISABLE KEYS */;
/*!40000 ALTER TABLE c_group_rel_tutor ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_group_rel_user
--

DROP TABLE IF EXISTS c_group_rel_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_rel_user (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  group_id int(10) unsigned NOT NULL DEFAULT '0',
  status int(11) NOT NULL DEFAULT '0',
  role char(50) NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_group_rel_user
--

LOCK TABLES c_group_rel_user WRITE;
/*!40000 ALTER TABLE c_group_rel_user DISABLE KEYS */;
/*!40000 ALTER TABLE c_group_rel_user ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_item_property
--

DROP TABLE IF EXISTS c_item_property;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_item_property (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  tool varchar(100) NOT NULL DEFAULT '',
  insert_user_id int(10) unsigned NOT NULL DEFAULT '0',
  insert_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  lastedit_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  ref int(11) NOT NULL DEFAULT '0',
  lastedit_type varchar(100) NOT NULL DEFAULT '',
  lastedit_user_id int(10) unsigned NOT NULL DEFAULT '0',
  to_group_id int(10) unsigned DEFAULT NULL,
  to_user_id int(10) unsigned DEFAULT NULL,
  visibility tinyint(4) NOT NULL DEFAULT '1',
  start_visible datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  end_visible datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  id_session int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY idx_item_property_toolref (tool,ref)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_item_property
--

LOCK TABLES c_item_property WRITE;
/*!40000 ALTER TABLE c_item_property DISABLE KEYS */;
INSERT INTO c_item_property VALUES (1,1,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',1,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,2,'document',1,'2013-02-26 14:44:56','2013-03-04 19:29:50',2,'DocumentInFolderUpdated',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,3,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',3,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,4,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',4,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,5,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',5,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,6,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',6,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,7,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',7,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,8,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',8,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,9,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',9,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,10,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',10,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,11,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',11,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,12,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',12,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,13,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',13,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,14,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',14,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,15,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',15,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,16,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',16,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,17,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',17,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,18,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',18,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,19,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',19,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,20,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',20,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,21,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',21,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,22,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',22,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,23,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',23,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,24,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',24,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,25,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',25,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,26,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',26,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,27,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',27,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,28,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',28,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,29,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',29,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,30,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',30,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,31,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',31,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,32,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',32,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,33,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',33,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,34,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',34,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,35,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',35,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,36,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',36,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,37,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',37,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,38,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',38,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,39,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',39,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,40,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',40,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,41,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',41,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,42,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',42,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,43,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',43,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,44,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',44,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,45,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',45,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,46,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',46,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,47,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',47,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,48,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',48,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,49,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',49,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,50,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',50,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,51,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',51,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,52,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',52,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,53,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',53,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,54,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',54,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,55,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',55,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,56,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',56,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,57,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',57,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,58,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',58,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,59,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',59,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,60,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',60,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,61,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',61,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,62,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',62,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,63,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',63,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,64,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',64,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,65,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',65,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,66,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',66,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,67,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',67,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,68,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',68,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,69,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',69,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,70,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',70,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,71,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',71,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,72,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',72,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,73,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',73,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,74,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',74,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,75,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',75,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,76,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',76,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,77,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',77,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,78,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',78,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,79,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',79,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,80,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',80,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,81,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',81,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,82,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',82,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,83,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',83,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,84,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',84,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,85,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',85,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,86,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',86,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,87,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',87,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,88,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',88,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,89,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',89,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,90,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',90,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,91,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',91,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,92,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',92,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,93,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',93,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,94,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',94,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,95,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',95,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,96,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',96,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,97,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',97,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,98,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',98,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,99,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',99,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,100,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',100,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,101,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',101,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,102,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',102,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,103,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',103,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,104,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',104,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,105,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',105,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,106,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',106,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,107,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',107,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,108,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',108,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,109,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',109,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,110,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',110,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,111,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',111,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,112,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',112,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,113,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',113,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,114,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',114,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,115,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',115,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,116,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',116,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,117,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',117,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,118,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',118,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,119,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',119,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,120,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',120,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,121,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',121,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,122,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',122,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,123,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',123,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,124,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',124,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,125,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',125,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,126,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',126,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,127,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',127,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,128,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',128,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,129,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',129,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,130,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',130,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,131,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',131,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,132,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',132,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,133,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',133,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,134,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',134,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,135,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',135,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,136,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',136,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,137,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',137,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,138,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',138,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,139,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',139,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,140,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',140,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,141,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',141,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,142,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',142,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,143,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',143,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,144,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',144,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,145,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',145,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,146,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',146,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,147,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',147,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,148,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',148,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,149,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',149,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,150,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',150,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,151,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',151,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,152,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',152,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,153,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',153,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,154,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',154,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,155,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',155,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,156,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',156,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,157,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',157,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,158,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',158,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,159,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',159,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,160,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',160,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,161,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',161,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,162,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',162,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,163,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',163,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,164,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',164,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,165,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',165,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,166,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',166,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,167,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',167,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,168,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',168,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,169,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',169,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,170,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',170,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,171,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',171,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,172,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',172,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,173,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',173,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,174,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',174,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,175,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',175,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,176,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',176,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,177,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',177,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,178,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',178,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,179,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',179,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,180,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',180,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,181,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',181,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,182,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',182,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,183,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',183,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,184,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',184,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,185,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',185,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,186,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',186,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,187,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',187,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,188,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',188,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,189,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',189,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,190,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',190,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,191,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',191,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,192,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',192,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,193,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',193,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,194,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',194,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,195,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',195,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,196,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',196,'DocumentAdded',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,197,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',197,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,198,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',198,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,199,'document',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',199,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,200,'calendar_event',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',1,'AgendaAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,201,'link',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',1,'LinkAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(0,1,'link',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',2,'LinkAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,202,'announcement',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',1,'AnnouncementAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,203,'forum_category',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',1,'ForumCategoryAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,204,'forum',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',1,'ForumAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,205,'forum_thread',1,'2013-02-26 14:44:56','2013-02-26 14:44:56',1,'ForumThreadAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,206,'document',1,'2013-03-04 18:21:00','2013-03-04 18:21:00',200,'DocumentInvisible',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,207,'document',1,'2013-03-04 18:29:45','2013-03-04 18:29:45',201,'DocumentInvisible',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,222,'calendar_event',1,'2013-03-06 12:41:08','2013-03-06 12:41:08',2,'AgendaModified',1,0,NULL,1,'2013-03-06 05:30:00','2013-03-06 11:30:00',0),(1,209,'calendar_event_attachment',1,'2013-03-06 11:20:19','2013-03-06 11:20:19',1,'AgendaAttachmentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,211,'document',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',202,'FolderCreated',1,1,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,212,'forum',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',2,'ForumVisible',1,1,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,213,'document',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',203,'FolderCreated',1,2,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,214,'forum',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',3,'ForumVisible',1,2,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,215,'document',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',204,'FolderCreated',1,3,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,216,'forum',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',4,'ForumVisible',1,3,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,217,'document',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',205,'FolderCreated',1,4,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,218,'forum',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',5,'ForumVisible',1,4,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,219,'document',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',206,'FolderCreated',1,5,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,220,'forum',1,'2013-03-06 11:24:49','2013-03-06 11:24:49',6,'ForumVisible',1,5,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,221,'calendar_event',1,'2013-03-06 11:36:17','2013-03-06 11:36:17',3,'AgendaModified',1,0,NULL,1,'2013-03-06 23:00:00','2013-03-07 02:00:00',0),(1,223,'calendar_event',1,'2013-03-06 12:52:41','2013-03-06 12:52:41',4,'AgendaAdded',1,NULL,7,1,'2013-03-06 08:00:00','2013-03-06 16:00:00',0),(1,224,'calendar_event',1,'2013-03-06 12:52:41','2013-03-06 12:52:41',4,'AgendaAdded',1,NULL,34,1,'2013-03-06 08:00:00','2013-03-06 16:00:00',0),(1,225,'quiz',1,'2013-03-25 12:34:52','2013-03-25 12:34:52',2,'QuizQuestionAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,226,'quiz',1,'2013-03-25 12:35:51','2013-03-25 12:36:02',3,'QuizQuestionUpdated',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,227,'quiz',1,'2013-03-25 12:58:38','2013-03-25 13:04:17',1,'QuizUpdated',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,228,'document',1,'2013-03-25 14:20:22','2013-03-25 14:20:22',207,'DocumentInvisible',1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,229,'learnpath',1,'2013-03-25 14:20:26','2013-03-25 14:20:26',1,'LearnpathAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,230,'learnpath',1,'2013-03-25 14:30:56','2013-03-25 14:30:56',2,'LearnpathAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,231,'document',1,'2013-03-25 14:31:01','2013-03-25 14:31:01',208,'FolderCreated',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,232,'document',1,'2013-03-25 14:31:09','2013-03-25 14:31:09',209,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,233,'document',1,'2013-03-25 14:34:31','2013-03-25 14:34:31',210,'FolderCreated',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0),(1,234,'document',1,'2013-03-25 14:34:37','2013-03-25 14:34:37',211,'DocumentAdded',1,0,NULL,1,'0000-00-00 00:00:00','0000-00-00 00:00:00',0);
/*!40000 ALTER TABLE c_item_property ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_link
--

DROP TABLE IF EXISTS c_link;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_link (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  url text NOT NULL,
  title varchar(150) DEFAULT NULL,
  description text,
  category_id int(10) unsigned DEFAULT NULL,
  display_order int(10) unsigned NOT NULL DEFAULT '0',
  on_homepage enum('0','1') NOT NULL DEFAULT '0',
  target char(10) DEFAULT '_self',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_link
--

LOCK TABLES c_link WRITE;
/*!40000 ALTER TABLE c_link DISABLE KEYS */;
INSERT INTO c_link VALUES (1,1,'http://www.google.com','Google','Quick and powerful search engine',0,0,'0','_self',0),(1,2,'http://www.wikipedia.org','Wikipedia','Free online encyclopedia',0,1,'0','_self',0);
/*!40000 ALTER TABLE c_link ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_link_category
--

DROP TABLE IF EXISTS c_link_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_link_category (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  category_title varchar(255) NOT NULL,
  description text,
  display_order mediumint(8) unsigned NOT NULL DEFAULT '0',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_link_category
--

LOCK TABLES c_link_category WRITE;
/*!40000 ALTER TABLE c_link_category DISABLE KEYS */;
/*!40000 ALTER TABLE c_link_category ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_lp
--

DROP TABLE IF EXISTS c_lp;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  lp_type int(10) unsigned NOT NULL,
  name varchar(255) NOT NULL,
  ref tinytext,
  description text,
  path text NOT NULL,
  force_commit tinyint(3) unsigned NOT NULL DEFAULT '0',
  default_view_mod char(32) NOT NULL DEFAULT 'embedded',
  default_encoding char(32) NOT NULL DEFAULT 'UTF-8',
  display_order int(10) unsigned NOT NULL DEFAULT '0',
  content_maker tinytext NOT NULL,
  content_local varchar(32) NOT NULL DEFAULT 'local',
  content_license text NOT NULL,
  prevent_reinit tinyint(3) unsigned NOT NULL DEFAULT '1',
  js_lib tinytext NOT NULL,
  debug tinyint(3) unsigned NOT NULL DEFAULT '0',
  theme varchar(255) NOT NULL DEFAULT '',
  preview_image varchar(255) NOT NULL DEFAULT '',
  author varchar(255) NOT NULL DEFAULT '',
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  prerequisite int(10) unsigned NOT NULL DEFAULT '0',
  hide_toc_frame tinyint(4) NOT NULL DEFAULT '0',
  seriousgame_mode tinyint(4) NOT NULL DEFAULT '0',
  use_max_score int(10) unsigned NOT NULL DEFAULT '1',
  autolunch int(10) unsigned NOT NULL DEFAULT '0',
  created_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  modified_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  publicated_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  expired_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_lp
--

LOCK TABLES c_lp WRITE;
/*!40000 ALTER TABLE c_lp DISABLE KEYS */;
INSERT INTO c_lp VALUES (1,1,1,'123',NULL,'','',0,'embedded','UTF-8',1,'Chamilo','local','',1,'',0,'','','',0,0,0,0,1,0,'2013-03-25 14:20:26','2013-03-25 14:34:37','2013-03-25 07:00:00','0000-00-00 00:00:00'),(1,2,1,'lols',NULL,'','',0,'embedded','UTF-8',2,'Chamilo','local','',1,'',0,'','','',0,0,0,0,1,0,'2013-03-25 14:30:56','2013-03-25 14:31:09','2013-03-25 07:00:00','0000-00-00 00:00:00');
/*!40000 ALTER TABLE c_lp ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_lp_item
--

DROP TABLE IF EXISTS c_lp_item;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_item (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  lp_id int(10) unsigned NOT NULL,
  item_type char(32) NOT NULL DEFAULT 'dokeos_document',
  ref tinytext NOT NULL,
  title varchar(511) NOT NULL,
  description varchar(511) NOT NULL DEFAULT '',
  path text NOT NULL,
  min_score float unsigned NOT NULL DEFAULT '0',
  max_score float unsigned DEFAULT '100',
  mastery_score float unsigned DEFAULT NULL,
  parent_item_id int(10) unsigned NOT NULL DEFAULT '0',
  previous_item_id int(10) unsigned NOT NULL DEFAULT '0',
  next_item_id int(10) unsigned NOT NULL DEFAULT '0',
  display_order int(10) unsigned NOT NULL DEFAULT '0',
  prerequisite text,
  parameters text,
  launch_data text NOT NULL,
  max_time_allowed char(13) DEFAULT '',
  terms text,
  search_did int(11) DEFAULT NULL,
  audio varchar(250) DEFAULT NULL,
  PRIMARY KEY (c_id,id),
  KEY lp_id (lp_id),
  KEY idx_c_lp_item_cid_lp_id (c_id,lp_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_lp_item
--

LOCK TABLES c_lp_item WRITE;
/*!40000 ALTER TABLE c_lp_item DISABLE KEYS */;
INSERT INTO c_lp_item VALUES (1,1,2,'document','1','great','','209',0,100,NULL,0,0,0,1,NULL,NULL,'','0',NULL,NULL,NULL),(1,2,1,'document','2','1111','','211',0,100,NULL,0,0,0,1,NULL,NULL,'','0',NULL,NULL,NULL);
/*!40000 ALTER TABLE c_lp_item ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_lp_item_view
--

DROP TABLE IF EXISTS c_lp_item_view;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_item_view (
  c_id int(11) NOT NULL,
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lp_item_id int(10) unsigned NOT NULL,
  lp_view_id int(10) unsigned NOT NULL,
  view_count int(10) unsigned NOT NULL DEFAULT '0',
  start_time int(10) unsigned NOT NULL,
  total_time int(10) unsigned NOT NULL DEFAULT '0',
  score float unsigned NOT NULL DEFAULT '0',
  status char(32) NOT NULL DEFAULT 'not attempted',
  suspend_data longtext,
  lesson_location text,
  core_exit varchar(32) NOT NULL DEFAULT 'none',
  max_score varchar(8) DEFAULT '',
  PRIMARY KEY (c_id,id),
  KEY lp_item_id (lp_item_id),
  KEY lp_view_id (lp_view_id),
  KEY idx_c_lp_item_view_cid_lp_view_id_lp_item_id (c_id,lp_view_id,lp_item_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_lp_item_view
--

LOCK TABLES c_lp_item_view WRITE;
/*!40000 ALTER TABLE c_lp_item_view DISABLE KEYS */;
INSERT INTO c_lp_item_view VALUES (1,1,1,2,1,1364222028,0,0,'completed','','','none','100'),(1,2,2,1,1,1364222088,0,0,'completed','','','none','100');
/*!40000 ALTER TABLE c_lp_item_view ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_lp_iv_interaction
--

DROP TABLE IF EXISTS c_lp_iv_interaction;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_iv_interaction (
  c_id int(11) NOT NULL,
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  order_id int(10) unsigned NOT NULL DEFAULT '0',
  lp_iv_id bigint(20) unsigned NOT NULL,
  interaction_id varchar(255) NOT NULL DEFAULT '',
  interaction_type varchar(255) NOT NULL DEFAULT '',
  weighting double NOT NULL DEFAULT '0',
  completion_time varchar(16) NOT NULL DEFAULT '',
  correct_responses text NOT NULL,
  student_response text NOT NULL,
  result varchar(255) NOT NULL DEFAULT '',
  latency varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (c_id,id),
  KEY lp_iv_id (lp_iv_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_lp_iv_interaction
--

LOCK TABLES c_lp_iv_interaction WRITE;
/*!40000 ALTER TABLE c_lp_iv_interaction DISABLE KEYS */;
/*!40000 ALTER TABLE c_lp_iv_interaction ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_lp_iv_objective
--

DROP TABLE IF EXISTS c_lp_iv_objective;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_iv_objective (
  c_id int(11) NOT NULL,
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  lp_iv_id bigint(20) unsigned NOT NULL,
  order_id int(10) unsigned NOT NULL DEFAULT '0',
  objective_id varchar(255) NOT NULL DEFAULT '',
  score_raw float unsigned NOT NULL DEFAULT '0',
  score_max float unsigned NOT NULL DEFAULT '0',
  score_min float unsigned NOT NULL DEFAULT '0',
  status char(32) NOT NULL DEFAULT 'not attempted',
  PRIMARY KEY (c_id,id),
  KEY lp_iv_id (lp_iv_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_lp_iv_objective
--

LOCK TABLES c_lp_iv_objective WRITE;
/*!40000 ALTER TABLE c_lp_iv_objective DISABLE KEYS */;
/*!40000 ALTER TABLE c_lp_iv_objective ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_lp_view
--

DROP TABLE IF EXISTS c_lp_view;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_view (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  lp_id int(10) unsigned NOT NULL,
  user_id int(10) unsigned NOT NULL,
  view_count int(10) unsigned NOT NULL DEFAULT '0',
  last_item int(10) unsigned NOT NULL DEFAULT '0',
  progress int(10) unsigned DEFAULT '0',
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY lp_id (lp_id),
  KEY user_id (user_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_lp_view
--

LOCK TABLES c_lp_view WRITE;
/*!40000 ALTER TABLE c_lp_view DISABLE KEYS */;
INSERT INTO c_lp_view VALUES (1,1,1,1,1,2,100,0),(1,2,2,1,1,1,100,0);
/*!40000 ALTER TABLE c_lp_view ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_metadata
--

DROP TABLE IF EXISTS c_metadata;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_metadata (
  c_id int(11) NOT NULL,
  eid varchar(250) NOT NULL,
  mdxmltext text,
  md5 char(32) DEFAULT '',
  htmlcache1 text,
  htmlcache2 text,
  indexabletext text,
  PRIMARY KEY (c_id,eid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_metadata
--

LOCK TABLES c_metadata WRITE;
/*!40000 ALTER TABLE c_metadata DISABLE KEYS */;
/*!40000 ALTER TABLE c_metadata ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_notebook
--

DROP TABLE IF EXISTS c_notebook;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_notebook (
  c_id int(11) NOT NULL,
  notebook_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  course varchar(40) NOT NULL,
  session_id int(11) NOT NULL DEFAULT '0',
  title varchar(255) NOT NULL,
  description text NOT NULL,
  creation_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  update_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  status int(11) DEFAULT NULL,
  PRIMARY KEY (c_id,notebook_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_notebook
--

LOCK TABLES c_notebook WRITE;
/*!40000 ALTER TABLE c_notebook DISABLE KEYS */;
/*!40000 ALTER TABLE c_notebook ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_online_connected
--

DROP TABLE IF EXISTS c_online_connected;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_online_connected (
  c_id int(11) NOT NULL,
  user_id int(10) unsigned NOT NULL,
  last_connection datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (c_id,user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_online_connected
--

LOCK TABLES c_online_connected WRITE;
/*!40000 ALTER TABLE c_online_connected DISABLE KEYS */;
/*!40000 ALTER TABLE c_online_connected ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_online_link
--

DROP TABLE IF EXISTS c_online_link;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_online_link (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name char(50) NOT NULL DEFAULT '',
  url char(100) NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_online_link
--

LOCK TABLES c_online_link WRITE;
/*!40000 ALTER TABLE c_online_link DISABLE KEYS */;
/*!40000 ALTER TABLE c_online_link ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_permission_group
--

DROP TABLE IF EXISTS c_permission_group;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_permission_group (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  group_id int(11) NOT NULL DEFAULT '0',
  tool varchar(250) NOT NULL DEFAULT '',
  action varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_permission_group
--

LOCK TABLES c_permission_group WRITE;
/*!40000 ALTER TABLE c_permission_group DISABLE KEYS */;
/*!40000 ALTER TABLE c_permission_group ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_permission_task
--

DROP TABLE IF EXISTS c_permission_task;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_permission_task (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  task_id int(11) NOT NULL DEFAULT '0',
  tool varchar(250) NOT NULL DEFAULT '',
  action varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_permission_task
--

LOCK TABLES c_permission_task WRITE;
/*!40000 ALTER TABLE c_permission_task DISABLE KEYS */;
/*!40000 ALTER TABLE c_permission_task ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_permission_user
--

DROP TABLE IF EXISTS c_permission_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_permission_user (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  tool varchar(250) NOT NULL DEFAULT '',
  action varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_permission_user
--

LOCK TABLES c_permission_user WRITE;
/*!40000 ALTER TABLE c_permission_user DISABLE KEYS */;
/*!40000 ALTER TABLE c_permission_user ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_quiz
--

DROP TABLE IF EXISTS c_quiz;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  description text,
  sound varchar(255) DEFAULT NULL,
  type tinyint(3) unsigned NOT NULL DEFAULT '1',
  random int(11) NOT NULL DEFAULT '0',
  random_answers tinyint(3) unsigned NOT NULL DEFAULT '0',
  active tinyint(4) NOT NULL DEFAULT '0',
  results_disabled int(10) unsigned NOT NULL DEFAULT '0',
  access_condition text,
  max_attempt int(11) NOT NULL DEFAULT '0',
  start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  feedback_type int(11) NOT NULL DEFAULT '0',
  expired_time int(11) NOT NULL DEFAULT '0',
  session_id int(11) DEFAULT '0',
  propagate_neg int(11) NOT NULL DEFAULT '0',
  review_answers int(11) NOT NULL DEFAULT '0',
  random_by_category int(11) NOT NULL DEFAULT '0',
  text_when_finished text,
  display_category_name int(11) NOT NULL DEFAULT '1',
  pass_percentage int(11) DEFAULT NULL,
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_quiz
--

LOCK TABLES c_quiz WRITE;
/*!40000 ALTER TABLE c_quiz DISABLE KEYS */;
INSERT INTO c_quiz VALUES (1,1,'Sample test','<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tbody><tr><td width=\"110\" valign=\"top\" align=\"left\"><img src=\"http://localhost/chamilostorm_net/chamilo.classic/main/default_course_document/images/mr_dokeos/thinking.jpg\" alt=\"\" /></td><td valign=\"top\" align=\"left\">Irony</td></tr></tbody></table>','',2,1,0,1,0,NULL,0,'0000-00-00 00:00:00','0000-00-00 00:00:00',0,0,0,0,0,0,'',1,0);
/*!40000 ALTER TABLE c_quiz ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_quiz_answer
--

DROP TABLE IF EXISTS c_quiz_answer;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_answer (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL,
  id_auto int(11) NOT NULL AUTO_INCREMENT,
  question_id int(10) unsigned NOT NULL,
  answer text NOT NULL,
  correct mediumint(8) unsigned DEFAULT NULL,
  comment text,
  ponderation float(6,2) NOT NULL DEFAULT '0.00',
  position mediumint(8) unsigned NOT NULL DEFAULT '1',
  hotspot_coordinates text,
  hotspot_type enum('square','circle','poly','delineation','oar') DEFAULT NULL,
  destination text NOT NULL,
  answer_code char(10) DEFAULT '',
  PRIMARY KEY (c_id,id_auto)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_quiz_answer
--

LOCK TABLES c_quiz_answer WRITE;
/*!40000 ALTER TABLE c_quiz_answer DISABLE KEYS */;
INSERT INTO c_quiz_answer VALUES (1,1,1,1,'Ridiculise one\'s interlocutor in order to have him concede he is wrong.',0,'No. Socratic irony is not a matter of psychology, it concerns argumentation.',-5.00,1,NULL,NULL,'',''),(1,2,2,1,'Admit one\'s own errors to invite one\'s interlocutor to do the same.',0,'No. Socratic irony is not a seduction strategy or a method based on the example.',-5.00,2,NULL,NULL,'',''),(1,3,3,1,'Compell one\'s interlocutor, by a series of questions and sub-questions, to admit he doesn\'t know what he claims to know.',1,'Indeed. Socratic irony is an interrogative method. The Greek \"eirotao\" means \"ask questions\"',5.00,3,NULL,NULL,'',''),(1,4,4,1,'Use the Principle of Non Contradiction to force one\'s interlocutor into a dead end.',1,'This answer is not false. It is true that the revelation of the interlocutor\'s ignorance means showing the contradictory conclusions where lead his premisses.',5.00,4,NULL,NULL,'',''),(1,1,5,2,'<p>&nbsp;11</p>',1,'',10.00,1,'','','0@@0@@0@@0',''),(1,2,6,2,'<p>&nbsp;22</p>',0,'',0.00,2,'','','0@@0@@0@@0',''),(1,3,7,2,'<p>&nbsp;3</p>',0,'',0.00,3,'','','0@@0@@0@@0',''),(1,4,8,2,'<p>&nbsp;4</p>',0,'',0.00,4,'','','0@@0@@0@@0',''),(1,3,11,3,'<p>&nbsp;33</p>',0,'',0.00,3,'','','0@@0@@0@@0',''),(1,2,10,3,'<p>&nbsp;22</p>',0,'',0.00,2,'','','0@@0@@0@@0',''),(1,1,9,3,'<p>&nbsp;11</p>',1,'',10.00,1,'','','0@@0@@0@@0',''),(1,4,12,3,'<p>&nbsp;444</p>',0,'',0.00,4,'','','0@@0@@0@@0','');
/*!40000 ALTER TABLE c_quiz_answer ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_quiz_question
--

DROP TABLE IF EXISTS c_quiz_question;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_question (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  question text NOT NULL,
  description text,
  ponderation float(6,2) NOT NULL DEFAULT '0.00',
  position mediumint(8) unsigned NOT NULL DEFAULT '1',
  type tinyint(3) unsigned NOT NULL DEFAULT '2',
  picture varchar(50) DEFAULT NULL,
  level int(10) unsigned NOT NULL DEFAULT '0',
  extra varchar(255) DEFAULT NULL,
  question_code char(10) DEFAULT '',
  PRIMARY KEY (c_id,id),
  KEY position (position)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_quiz_question
--

LOCK TABLES c_quiz_question WRITE;
/*!40000 ALTER TABLE c_quiz_question DISABLE KEYS */;
INSERT INTO c_quiz_question VALUES (1,1,'Socratic irony is...','(more than one answer can be true)',10.00,1,2,'',1,NULL,''),(1,2,'Multiple choice','',10.00,2,1,'',1,'',''),(1,3,'Multiple choice 33','',10.00,3,1,'',1,'','');
/*!40000 ALTER TABLE c_quiz_question ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_quiz_question_category
--

DROP TABLE IF EXISTS c_quiz_question_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_question_category (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  description text NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_quiz_question_category
--

LOCK TABLES c_quiz_question_category WRITE;
/*!40000 ALTER TABLE c_quiz_question_category DISABLE KEYS */;
/*!40000 ALTER TABLE c_quiz_question_category ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_quiz_question_option
--

DROP TABLE IF EXISTS c_quiz_question_option;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_question_option (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  question_id int(11) NOT NULL,
  name varchar(255) DEFAULT NULL,
  position int(10) unsigned NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_quiz_question_option
--

LOCK TABLES c_quiz_question_option WRITE;
/*!40000 ALTER TABLE c_quiz_question_option DISABLE KEYS */;
/*!40000 ALTER TABLE c_quiz_question_option ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_quiz_question_rel_category
--

DROP TABLE IF EXISTS c_quiz_question_rel_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_question_rel_category (
  c_id int(11) NOT NULL,
  question_id int(11) NOT NULL,
  category_id int(11) NOT NULL,
  PRIMARY KEY (c_id,question_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_quiz_question_rel_category
--

LOCK TABLES c_quiz_question_rel_category WRITE;
/*!40000 ALTER TABLE c_quiz_question_rel_category DISABLE KEYS */;
/*!40000 ALTER TABLE c_quiz_question_rel_category ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_quiz_rel_question
--

DROP TABLE IF EXISTS c_quiz_rel_question;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_rel_question (
  c_id int(11) NOT NULL,
  question_id int(10) unsigned NOT NULL,
  exercice_id int(10) unsigned NOT NULL,
  question_order int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (c_id,question_id,exercice_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_quiz_rel_question
--

LOCK TABLES c_quiz_rel_question WRITE;
/*!40000 ALTER TABLE c_quiz_rel_question DISABLE KEYS */;
INSERT INTO c_quiz_rel_question VALUES (1,1,1,1),(1,2,1,2),(1,3,1,3);
/*!40000 ALTER TABLE c_quiz_rel_question ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_resource
--

DROP TABLE IF EXISTS c_resource;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_resource (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  source_type varchar(50) DEFAULT NULL,
  source_id int(10) unsigned DEFAULT NULL,
  resource_type varchar(50) DEFAULT NULL,
  resource_id int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_resource
--

LOCK TABLES c_resource WRITE;
/*!40000 ALTER TABLE c_resource DISABLE KEYS */;
/*!40000 ALTER TABLE c_resource ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_role
--

DROP TABLE IF EXISTS c_role;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_role (
  c_id int(11) NOT NULL,
  role_id int(11) NOT NULL AUTO_INCREMENT,
  role_name varchar(250) NOT NULL DEFAULT '',
  role_comment text,
  default_role tinyint(4) DEFAULT '0',
  PRIMARY KEY (c_id,role_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_role
--

LOCK TABLES c_role WRITE;
/*!40000 ALTER TABLE c_role DISABLE KEYS */;
/*!40000 ALTER TABLE c_role ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_role_group
--

DROP TABLE IF EXISTS c_role_group;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_role_group (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  role_id int(11) NOT NULL DEFAULT '0',
  scope varchar(20) NOT NULL DEFAULT 'course',
  group_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (id,c_id,group_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_role_group
--

LOCK TABLES c_role_group WRITE;
/*!40000 ALTER TABLE c_role_group DISABLE KEYS */;
/*!40000 ALTER TABLE c_role_group ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_role_permissions
--

DROP TABLE IF EXISTS c_role_permissions;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_role_permissions (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  role_id int(11) NOT NULL DEFAULT '0',
  tool varchar(250) NOT NULL DEFAULT '',
  action varchar(50) NOT NULL DEFAULT '',
  default_perm tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (id,c_id,role_id,tool,action)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_role_permissions
--

LOCK TABLES c_role_permissions WRITE;
/*!40000 ALTER TABLE c_role_permissions DISABLE KEYS */;
/*!40000 ALTER TABLE c_role_permissions ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_role_user
--

DROP TABLE IF EXISTS c_role_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_role_user (
  c_id int(11) NOT NULL,
  role_id int(11) NOT NULL DEFAULT '0',
  scope varchar(20) NOT NULL DEFAULT 'course',
  user_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,role_id,user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_role_user
--

LOCK TABLES c_role_user WRITE;
/*!40000 ALTER TABLE c_role_user DISABLE KEYS */;
/*!40000 ALTER TABLE c_role_user ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_student_publication
--

DROP TABLE IF EXISTS c_student_publication;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_student_publication (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  url varchar(255) DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  description text,
  author varchar(255) DEFAULT NULL,
  active tinyint(4) DEFAULT NULL,
  accepted tinyint(4) DEFAULT '0',
  post_group_id int(11) NOT NULL DEFAULT '0',
  sent_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  filetype set('file','folder') NOT NULL DEFAULT 'file',
  has_properties int(10) unsigned NOT NULL DEFAULT '0',
  view_properties tinyint(4) DEFAULT NULL,
  qualification float(6,2) unsigned NOT NULL DEFAULT '0.00',
  date_of_qualification datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  parent_id int(10) unsigned NOT NULL DEFAULT '0',
  qualificator_id int(10) unsigned NOT NULL DEFAULT '0',
  weight float(6,2) unsigned NOT NULL DEFAULT '0.00',
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL,
  allow_text_assignment int(11) NOT NULL DEFAULT '0',
  contains_file int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_student_publication
--

LOCK TABLES c_student_publication WRITE;
/*!40000 ALTER TABLE c_student_publication DISABLE KEYS */;
/*!40000 ALTER TABLE c_student_publication ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_student_publication_assignment
--

DROP TABLE IF EXISTS c_student_publication_assignment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_student_publication_assignment (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  expires_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  ends_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  add_to_calendar tinyint(4) NOT NULL,
  enable_qualification tinyint(4) NOT NULL,
  publication_id int(11) NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_student_publication_assignment
--

LOCK TABLES c_student_publication_assignment WRITE;
/*!40000 ALTER TABLE c_student_publication_assignment DISABLE KEYS */;
/*!40000 ALTER TABLE c_student_publication_assignment ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_survey
--

DROP TABLE IF EXISTS c_survey;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey (
  c_id int(11) NOT NULL,
  survey_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  code varchar(20) DEFAULT NULL,
  title text,
  subtitle text,
  author varchar(20) DEFAULT NULL,
  lang varchar(20) DEFAULT NULL,
  avail_from date DEFAULT NULL,
  avail_till date DEFAULT NULL,
  is_shared char(1) DEFAULT '1',
  template varchar(20) DEFAULT NULL,
  intro text,
  surveythanks text,
  creation_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  invited int(11) NOT NULL,
  answered int(11) NOT NULL,
  invite_mail text NOT NULL,
  reminder_mail text NOT NULL,
  mail_subject varchar(255) NOT NULL,
  anonymous enum('0','1') NOT NULL DEFAULT '0',
  access_condition text,
  shuffle tinyint(1) NOT NULL DEFAULT '0',
  one_question_per_page tinyint(1) NOT NULL DEFAULT '0',
  survey_version varchar(255) NOT NULL DEFAULT '',
  parent_id int(10) unsigned NOT NULL,
  survey_type int(11) NOT NULL DEFAULT '0',
  show_form_profile int(11) NOT NULL DEFAULT '0',
  form_fields text NOT NULL,
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,survey_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_survey
--

LOCK TABLES c_survey WRITE;
/*!40000 ALTER TABLE c_survey DISABLE KEYS */;
/*!40000 ALTER TABLE c_survey ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_survey_answer
--

DROP TABLE IF EXISTS c_survey_answer;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_answer (
  c_id int(11) NOT NULL,
  answer_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  survey_id int(10) unsigned NOT NULL,
  question_id int(10) unsigned NOT NULL,
  option_id text NOT NULL,
  value int(10) unsigned NOT NULL,
  user varchar(250) NOT NULL,
  PRIMARY KEY (c_id,answer_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_survey_answer
--

LOCK TABLES c_survey_answer WRITE;
/*!40000 ALTER TABLE c_survey_answer DISABLE KEYS */;
/*!40000 ALTER TABLE c_survey_answer ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_survey_group
--

DROP TABLE IF EXISTS c_survey_group;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_group (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(20) NOT NULL,
  description varchar(255) NOT NULL,
  survey_id int(10) unsigned NOT NULL,
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_survey_group
--

LOCK TABLES c_survey_group WRITE;
/*!40000 ALTER TABLE c_survey_group DISABLE KEYS */;
/*!40000 ALTER TABLE c_survey_group ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_survey_invitation
--

DROP TABLE IF EXISTS c_survey_invitation;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_invitation (
  c_id int(11) NOT NULL,
  survey_invitation_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  survey_code varchar(20) NOT NULL,
  user varchar(250) NOT NULL,
  invitation_code varchar(250) NOT NULL,
  invitation_date datetime NOT NULL,
  reminder_date datetime NOT NULL,
  answered int(11) NOT NULL DEFAULT '0',
  session_id int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,survey_invitation_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_survey_invitation
--

LOCK TABLES c_survey_invitation WRITE;
/*!40000 ALTER TABLE c_survey_invitation DISABLE KEYS */;
/*!40000 ALTER TABLE c_survey_invitation ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_survey_question
--

DROP TABLE IF EXISTS c_survey_question;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_question (
  c_id int(11) NOT NULL,
  question_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  survey_id int(10) unsigned NOT NULL,
  survey_question text NOT NULL,
  survey_question_comment text NOT NULL,
  type varchar(250) NOT NULL,
  display varchar(10) NOT NULL,
  sort int(11) NOT NULL,
  shared_question_id int(11) DEFAULT NULL,
  max_value int(11) DEFAULT NULL,
  survey_group_pri int(10) unsigned NOT NULL DEFAULT '0',
  survey_group_sec1 int(10) unsigned NOT NULL DEFAULT '0',
  survey_group_sec2 int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,question_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_survey_question
--

LOCK TABLES c_survey_question WRITE;
/*!40000 ALTER TABLE c_survey_question DISABLE KEYS */;
/*!40000 ALTER TABLE c_survey_question ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_survey_question_option
--

DROP TABLE IF EXISTS c_survey_question_option;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_question_option (
  c_id int(11) NOT NULL,
  question_option_id int(10) unsigned NOT NULL AUTO_INCREMENT,
  question_id int(10) unsigned NOT NULL,
  survey_id int(10) unsigned NOT NULL,
  option_text text NOT NULL,
  sort int(11) NOT NULL,
  value int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,question_option_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_survey_question_option
--

LOCK TABLES c_survey_question_option WRITE;
/*!40000 ALTER TABLE c_survey_question_option DISABLE KEYS */;
/*!40000 ALTER TABLE c_survey_question_option ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_thematic
--

DROP TABLE IF EXISTS c_thematic;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_thematic (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(255) NOT NULL,
  content text,
  display_order int(10) unsigned NOT NULL DEFAULT '0',
  active tinyint(4) NOT NULL DEFAULT '0',
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY active (active,session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_thematic
--

LOCK TABLES c_thematic WRITE;
/*!40000 ALTER TABLE c_thematic DISABLE KEYS */;
/*!40000 ALTER TABLE c_thematic ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_thematic_advance
--

DROP TABLE IF EXISTS c_thematic_advance;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_thematic_advance (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  thematic_id int(11) NOT NULL,
  attendance_id int(11) NOT NULL DEFAULT '0',
  content text,
  start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  duration int(11) NOT NULL DEFAULT '0',
  done_advance tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY thematic_id (thematic_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_thematic_advance
--

LOCK TABLES c_thematic_advance WRITE;
/*!40000 ALTER TABLE c_thematic_advance DISABLE KEYS */;
/*!40000 ALTER TABLE c_thematic_advance ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_thematic_plan
--

DROP TABLE IF EXISTS c_thematic_plan;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_thematic_plan (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  thematic_id int(11) NOT NULL,
  title varchar(255) NOT NULL,
  description text,
  description_type int(11) NOT NULL,
  PRIMARY KEY (c_id,id),
  KEY thematic_id (thematic_id,description_type)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_thematic_plan
--

LOCK TABLES c_thematic_plan WRITE;
/*!40000 ALTER TABLE c_thematic_plan DISABLE KEYS */;
/*!40000 ALTER TABLE c_thematic_plan ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_tool
--

DROP TABLE IF EXISTS c_tool;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_tool (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  link varchar(255) NOT NULL,
  image varchar(255) DEFAULT NULL,
  visibility tinyint(3) unsigned DEFAULT '0',
  admin varchar(255) DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  added_tool tinyint(3) unsigned DEFAULT '1',
  target enum('_self','_blank') NOT NULL DEFAULT '_self',
  category varchar(20) NOT NULL DEFAULT 'authoring',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_tool
--

LOCK TABLES c_tool WRITE;
/*!40000 ALTER TABLE c_tool DISABLE KEYS */;
INSERT INTO c_tool VALUES (1,1,'course_description','course_description/','info.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,2,'calendar_event','calendar/agenda.php','agenda.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,3,'document','document/document.php','folder_document.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,4,'learnpath','newscorm/lp_controller.php','scorms.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,5,'link','link/link.php','links.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,6,'quiz','exercice/exercice.php','quiz.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,7,'announcement','announcements/announcements.php','valves.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,8,'forum','forum/index.php','forum.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,9,'dropbox','dropbox/index.php','dropbox.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,10,'user','user/user.php','members.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,11,'group','group/group.php','group.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,12,'chat','chat/chat.php','chat.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,13,'student_publication','work/work.php','works.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,14,'survey','survey/survey_list.php','survey.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,15,'wiki','wiki/index.php','wiki.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,16,'gradebook','gradebook/index.php','gradebook.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,17,'glossary','glossary/index.php','glossary.gif',1,'0','squaregrey.gif',0,'_self','authoring',0),(1,18,'notebook','notebook/index.php','notebook.gif',1,'0','squaregrey.gif',0,'_self','interaction',0),(1,19,'attendance','attendance/index.php','attendance.gif',0,'0','squaregrey.gif',0,'_self','authoring',0),(1,20,'course_progress','course_progress/index.php','course_progress.gif',0,'0','squaregrey.gif',0,'_self','authoring',0),(1,21,'blog_management','blog/blog_admin.php','blog_admin.gif',0,'1','squaregrey.gif',0,'_self','admin',0),(1,22,'tracking','tracking/courseLog.php','statistics.gif',0,'1','',0,'_self','admin',0),(1,23,'course_setting','course_info/infocours.php','reference.gif',0,'1','',0,'_self','admin',0),(1,24,'course_maintenance','course_info/maintenance.php','backup.gif',0,'1','',0,'_self','admin',0);
/*!40000 ALTER TABLE c_tool ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_tool_intro
--

DROP TABLE IF EXISTS c_tool_intro;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_tool_intro (
  c_id int(11) NOT NULL,
  id varchar(50) NOT NULL,
  intro_text mediumtext NOT NULL,
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id,session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_tool_intro
--

LOCK TABLES c_tool_intro WRITE;
/*!40000 ALTER TABLE c_tool_intro DISABLE KEYS */;
INSERT INTO c_tool_intro VALUES (1,'course_homepage','<p style=\"text-align: center;\">\n                        <img src=\"/chamilostorm_net/chamilo.classic/main/img/mascot.png\" alt=\"Mr. Chamilo\" title=\"Mr. Chamilo\" />\n                        <h2>Welcome to this training!</h2>\n                     </p>',0),(1,'student_publication','This page allows users and groups to publish documents.',0),(1,'wiki','<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td width=\"110\" valign=\"top\" align=\"left\"></td><td valign=\"top\" align=\"left\">The word Wiki is short for WikiWikiWeb. Wikiwiki is a Hawaiian word, meaning \"fast\" or \"speed\". In a wiki, people write pages together. If one person writes something wrong, the next person can correct it. The next person can also add something new to the page. Because of this, the pages improve continuously.</td></tr></table>',0);
/*!40000 ALTER TABLE c_tool_intro ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_userinfo_content
--

DROP TABLE IF EXISTS c_userinfo_content;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_userinfo_content (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  user_id int(10) unsigned NOT NULL,
  definition_id int(10) unsigned NOT NULL,
  editor_ip varchar(39) DEFAULT NULL,
  edition_time datetime DEFAULT NULL,
  content text NOT NULL,
  PRIMARY KEY (c_id,id),
  KEY user_id (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_userinfo_content
--

LOCK TABLES c_userinfo_content WRITE;
/*!40000 ALTER TABLE c_userinfo_content DISABLE KEYS */;
/*!40000 ALTER TABLE c_userinfo_content ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_userinfo_def
--

DROP TABLE IF EXISTS c_userinfo_def;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_userinfo_def (
  c_id int(11) NOT NULL,
  id int(10) unsigned NOT NULL AUTO_INCREMENT,
  title varchar(80) NOT NULL DEFAULT '',
  comment text,
  line_count tinyint(3) unsigned NOT NULL DEFAULT '5',
  rank tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_userinfo_def
--

LOCK TABLES c_userinfo_def WRITE;
/*!40000 ALTER TABLE c_userinfo_def DISABLE KEYS */;
/*!40000 ALTER TABLE c_userinfo_def ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_wiki
--

DROP TABLE IF EXISTS c_wiki;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  page_id int(11) NOT NULL DEFAULT '0',
  reflink varchar(255) NOT NULL DEFAULT 'index',
  title varchar(255) NOT NULL,
  content mediumtext NOT NULL,
  user_id int(11) NOT NULL DEFAULT '0',
  group_id int(11) DEFAULT NULL,
  dtime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  addlock int(11) NOT NULL DEFAULT '1',
  editlock int(11) NOT NULL DEFAULT '0',
  visibility int(11) NOT NULL DEFAULT '1',
  addlock_disc int(11) NOT NULL DEFAULT '1',
  visibility_disc int(11) NOT NULL DEFAULT '1',
  ratinglock_disc int(11) NOT NULL DEFAULT '1',
  assignment int(11) NOT NULL DEFAULT '0',
  comment text NOT NULL,
  progress text NOT NULL,
  score int(11) DEFAULT '0',
  version int(11) DEFAULT NULL,
  is_editing int(11) NOT NULL DEFAULT '0',
  time_edit datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  hits int(11) DEFAULT '0',
  linksto text NOT NULL,
  tag text NOT NULL,
  user_ip varchar(39) NOT NULL,
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,id),
  KEY reflink (reflink),
  KEY group_id (group_id),
  KEY page_id (page_id),
  KEY session_id (session_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_wiki
--

LOCK TABLES c_wiki WRITE;
/*!40000 ALTER TABLE c_wiki DISABLE KEYS */;
/*!40000 ALTER TABLE c_wiki ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_wiki_conf
--

DROP TABLE IF EXISTS c_wiki_conf;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki_conf (
  c_id int(11) NOT NULL,
  page_id int(11) NOT NULL DEFAULT '0',
  task text NOT NULL,
  feedback1 text NOT NULL,
  feedback2 text NOT NULL,
  feedback3 text NOT NULL,
  fprogress1 varchar(3) NOT NULL,
  fprogress2 varchar(3) NOT NULL,
  fprogress3 varchar(3) NOT NULL,
  max_size int(11) DEFAULT NULL,
  max_text int(11) DEFAULT NULL,
  max_version int(11) DEFAULT NULL,
  startdate_assig datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  enddate_assig datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  delayedsubmit int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (c_id,page_id),
  KEY page_id (page_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_wiki_conf
--

LOCK TABLES c_wiki_conf WRITE;
/*!40000 ALTER TABLE c_wiki_conf DISABLE KEYS */;
/*!40000 ALTER TABLE c_wiki_conf ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_wiki_discuss
--

DROP TABLE IF EXISTS c_wiki_discuss;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki_discuss (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL AUTO_INCREMENT,
  publication_id int(11) NOT NULL DEFAULT '0',
  userc_id int(11) NOT NULL DEFAULT '0',
  comment text NOT NULL,
  p_score varchar(255) DEFAULT NULL,
  dtime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_wiki_discuss
--

LOCK TABLES c_wiki_discuss WRITE;
/*!40000 ALTER TABLE c_wiki_discuss DISABLE KEYS */;
/*!40000 ALTER TABLE c_wiki_discuss ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table c_wiki_mailcue
--

DROP TABLE IF EXISTS c_wiki_mailcue;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki_mailcue (
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  type text NOT NULL,
  group_id int(11) DEFAULT NULL,
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (c_id,id,user_id),
  KEY c_id (c_id,id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table c_wiki_mailcue
--

LOCK TABLES c_wiki_mailcue WRITE;
/*!40000 ALTER TABLE c_wiki_mailcue DISABLE KEYS */;
/*!40000 ALTER TABLE c_wiki_mailcue ENABLE KEYS */;
UNLOCK TABLES;


/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-03-25 17:00:37
