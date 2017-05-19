-- MySQL dump 10.13  Distrib 5.1.66, for debian-linux-gnu (i486)
--
-- Host: localhost    Database: chamilo
-- ------------------------------------------------------
-- Server version	5.1.66-0+squeeze1

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
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  title text,
  content longtext,
  end_date date DEFAULT NULL,
  display_order mediumint(9) NOT NULL DEFAULT '0',
  email_sent tinyint(4) DEFAULT '0',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (iid),
  UNIQUE KEY (iid),
  KEY session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_announcement_attachment
--

DROP TABLE IF EXISTS c_announcement_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_announcement_attachment (
  iid int  NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11)  NOT NULL,
  path varchar(255) NOT NULL,
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  announcement_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (iid),
  UNIQUE KEY (c_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_attendance
--

DROP TABLE IF EXISTS c_attendance;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance (
  iid int not null AUTO_INCREMENT,
  c_id int NOT NULL,
  id int  NOT NULL,
  name text NOT NULL,
  description longtext,
  active tinyint NOT NULL DEFAULT '1',
  attendance_qualify_title varchar(255) DEFAULT NULL,
  attendance_qualify_max int NOT NULL DEFAULT '0',
  attendance_weight float(6,2) NOT NULL DEFAULT '0.00',
  session_id int NOT NULL DEFAULT '0',
  locked int NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  UNIQUE KEY (c_id,id),
  KEY session_id (session_id),
  KEY active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_attendance_calendar
--

DROP TABLE IF EXISTS c_attendance_calendar;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_calendar (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int NOT NULL,
  id int  NOT NULL,
  attendance_id int NOT NULL,
  date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  done_attendance tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  UNIQUE KEY (c_id,id),
  KEY attendance_id (attendance_id),
  KEY done_attendance (done_attendance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_attendance_result
--

DROP TABLE IF EXISTS c_attendance_result;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_result (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int NOT NULL,
  id int  NOT NULL,
  user_id int NOT NULL,
  attendance_id int NOT NULL,
  score int NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  UNIQUE KEY (c_id,id),
  KEY attendance_id (attendance_id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_attendance_sheet
--

DROP TABLE IF EXISTS c_attendance_sheet;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_sheet (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int NOT NULL,
  user_id int NOT NULL,
  attendance_calendar_id int NOT NULL,
  presence tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  UNIQUE KEY (c_id,user_id,attendance_calendar_id),
  KEY presence (presence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_attendance_sheet_log
--

DROP TABLE IF EXISTS c_attendance_sheet_log;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_attendance_sheet_log (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int NOT NULL,
  id int  NOT NULL,
  attendance_id int NOT NULL DEFAULT '0',
  lastedit_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  lastedit_type varchar(200) NOT NULL,
  lastedit_user_id int NOT NULL DEFAULT '0',
  calendar_date_value datetime DEFAULT NULL,
  PRIMARY KEY (iid),
  UNIQUE KEY (c_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog
--

DROP TABLE IF EXISTS c_blog;
CREATE TABLE c_blog (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL,
  blog_name varchar(250) NOT NULL DEFAULT '',
  blog_subtitle varchar(250) DEFAULT NULL,
  date_creation datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  visibility tinyint(3)  NOT NULL DEFAULT '0',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog_attachment
--

DROP TABLE IF EXISTS c_blog_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_attachment (
  iid int NOT NULL AUTO_INCREMENT,
  id int(10),
  c_id int(11) NOT NULL,
  path varchar(255) NOT NULL COMMENT 'the real filename',
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL,
  filename varchar(255) NOT NULL COMMENT 'the user s file name',
  blog_id int(11) NOT NULL,
  comment_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog_comment
--

DROP TABLE IF EXISTS c_blog_comment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_comment (
  iid int NOT NULL AUTO_INCREMENT,
  comment_id int(11) NOT NULL,
  c_id int(11) NOT NULL,
  title varchar(250) NOT NULL DEFAULT '',
  comment longtext NOT NULL,
  author_id int(11) NOT NULL DEFAULT '0',
  date_creation datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  blog_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  task_id int(11) DEFAULT NULL,
  parent_comment_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8 COMMENT='Table with comments on posts in a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog_post
--

DROP TABLE IF EXISTS c_blog_post;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_post (
  iid int NOT NULL AUTO_INCREMENT,
  post_id int(11) NOT NULL,
  c_id int(11) NOT NULL,
  title varchar(250) NOT NULL DEFAULT '',
  full_text longtext NOT NULL,
  date_creation datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  blog_id int(11) NOT NULL DEFAULT '0',
  author_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8 COMMENT='Table with posts / blog.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog_rating
--

DROP TABLE IF EXISTS c_blog_rating;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_rating (
  iid int NOT NULL AUTO_INCREMENT,
  rating_id int(11) NOT NULL,
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL DEFAULT '0',
  rating_type varchar(100) NOT NULL DEFAULT 'post',
  item_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  rating int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8 COMMENT='Table with ratings for post/comments in a certain blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog_rel_user
--

DROP TABLE IF EXISTS c_blog_rel_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_rel_user (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8 COMMENT='Table representing users subscribed to a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog_task
--

DROP TABLE IF EXISTS c_blog_task;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_task (
  iid int NOT NULL AUTO_INCREMENT,
  task_id int(11) NOT NULL,
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL DEFAULT '0',
  title varchar(250) NOT NULL DEFAULT '',
  description text NOT NULL,
  color varchar(10) NOT NULL DEFAULT '',
  system_task tinyint(3)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8 COMMENT='Table with tasks for a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_blog_task_rel_user
--

DROP TABLE IF EXISTS c_blog_task_rel_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_blog_task_rel_user (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  blog_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  task_id int(11) NOT NULL DEFAULT '0',
  target_date date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8 COMMENT='Table with tasks assigned to a user in a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_calendar_event
--

DROP TABLE IF EXISTS c_calendar_event;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event (
  iid int NOT NULL AUTO_INCREMENT,
  id int(10)  NOT NULL,
  c_id int(11) NOT NULL,
  title varchar(255) NOT NULL,
  content longtext,
  start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  end_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  parent_event_id int(11) DEFAULT NULL,
  session_id int(10)  NOT NULL DEFAULT '0',
  all_day int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_calendar_event_attachment
--

DROP TABLE IF EXISTS c_calendar_event_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event_attachment (
  iid int NOT NULL AUTO_INCREMENT,
  id int(11) NOT NULL,
  c_id int(11) NOT NULL,
  path varchar(255) NOT NULL,
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  agenda_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_calendar_event_repeat
--

DROP TABLE IF EXISTS c_calendar_event_repeat;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event_repeat (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  cal_id int(11) NOT NULL DEFAULT '0',
  cal_type varchar(20) DEFAULT NULL,
  cal_end int(11) DEFAULT NULL,
  cal_frequency int(11) DEFAULT '1',
  cal_days char(7) DEFAULT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_calendar_event_repeat_not
--

DROP TABLE IF EXISTS c_calendar_event_repeat_not;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_calendar_event_repeat_not (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  cal_id int(11) NOT NULL,
  cal_date int(11) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_chat_connected
--

DROP TABLE IF EXISTS c_chat_connected;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_chat_connected (
  iid int(10)  NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  user_id int(10)  NOT NULL DEFAULT '0',
  last_connection datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  session_id int(11) NOT NULL DEFAULT '0',
  to_group_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY char_connected_index (user_id,session_id,to_group_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_course_description
--

DROP TABLE IF EXISTS c_course_description;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_course_description (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  title varchar(255) DEFAULT NULL,
  content longtext,
  session_id int(11) DEFAULT '0',
  description_type tinyint(3)  NOT NULL DEFAULT '0',
  progress int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_course_setting
--

DROP TABLE IF EXISTS c_course_setting;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_course_setting (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  variable varchar(255) NOT NULL DEFAULT '',
  subkey varchar(255) DEFAULT NULL,
  type varchar(255) DEFAULT NULL,
  category varchar(255) DEFAULT NULL,
  value varchar(255) NOT NULL DEFAULT '',
  title varchar(255) NOT NULL DEFAULT '',
  comment varchar(255) DEFAULT NULL,
  subkeytext varchar(255) DEFAULT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_document
--

DROP TABLE IF EXISTS c_document;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_document (
  iid int(10)  NOT NULL AUTO_INCREMENT,
  id int(10) NOT NULL,
  c_id int(11) NOT NULL,
  path varchar(255) NOT NULL DEFAULT '',
  comment text,
  title varchar(255) DEFAULT NULL,
  filetype NOT NULL DEFAULT 'file',
  size int(11) NOT NULL DEFAULT '0',
  readonly tinyint(3)  NOT NULL,
  session_id int(10)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_dropbox_category
--

DROP TABLE IF EXISTS c_dropbox_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_category (
  iid int NOT NULL AUTO_INCREMENT,
  cat_id int(11) NOT NULL,
  c_id int(11) NOT NULL,
  cat_name text NOT NULL,
  received tinyint(3)  NOT NULL DEFAULT '0',
  sent tinyint(3)  NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_dropbox_feedback
--

DROP TABLE IF EXISTS c_dropbox_feedback;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_feedback (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  feedback_id int(11) NOT NULL,
  file_id int(11) NOT NULL DEFAULT '0',
  author_user_id int(11) NOT NULL DEFAULT '0',
  feedback text NOT NULL,
  feedback_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (iid),
  KEY file_id (file_id),
  KEY author_user_id (author_user_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_dropbox_file
--

DROP TABLE IF EXISTS c_dropbox_file;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_file (
  iid int(10)  NOT NULL AUTO_INCREMENT,
  id int(10)  NOT NULL,
  c_id int(11) NOT NULL,
  uploader_id int(10)  NOT NULL DEFAULT '0',
  filename varchar(250) NOT NULL DEFAULT '',
  filesize int(10)  NOT NULL,
  title varchar(250) DEFAULT '',
  description varchar(250) DEFAULT '',
  author varchar(250) DEFAULT '',
  upload_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  last_upload_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  cat_id int(11) NOT NULL DEFAULT '0',
  session_id int(10)  NOT NULL,
  PRIMARY KEY (iid),
  UNIQUE KEY UN_filename (filename),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_dropbox_person
--

DROP TABLE IF EXISTS c_dropbox_person;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_person (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  file_id int(10)  NOT NULL,
  user_id int(10)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_dropbox_post
--

DROP TABLE IF EXISTS c_dropbox_post;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_dropbox_post (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  file_id int(10) NOT NULL,
  dest_user_id int(10)  NOT NULL DEFAULT '0',
  feedback_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  feedback text,
  cat_id int(11) NOT NULL DEFAULT '0',
  session_id int(10)  NOT NULL,
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_attachment
--

DROP TABLE IF EXISTS c_forum_attachment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_attachment (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  path varchar(255) NOT NULL,
  comment text,
  size int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_category
--

DROP TABLE IF EXISTS c_forum_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_category (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  cat_id int(11) NOT NULL,
  cat_title varchar(255) NOT NULL DEFAULT '',
  cat_comment text,
  cat_order int(11) NOT NULL DEFAULT '0',
  locked int(11) NOT NULL DEFAULT '0',
  session_id int(10)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_forum
--

DROP TABLE IF EXISTS c_forum_forum;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_forum (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  forum_id int(11) NOT NULL,
  forum_title varchar(255) NOT NULL DEFAULT '',
  forum_comment longtext,
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
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_mailcue
--

DROP TABLE IF EXISTS c_forum_mailcue;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_mailcue (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  user_id int(11) NOT NULL DEFAULT '0',
  thread_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_notification
--

DROP TABLE IF EXISTS c_forum_notification;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_notification (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int NOT NULL,
  id int NOT NULL,
  user_id int NOT NULL DEFAULT 0,
  forum_id int NOT NULL DEFAULT 0,
  thread_id int NOT NULL DEFAULT 0,
  post_id int NOT NULL DEFAULT 0,
  PRIMARY KEY (iid),
  KEY user_id (user_id),
  KEY forum_id (forum_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_post
--

DROP TABLE IF EXISTS c_forum_post;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_post (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  post_id int(11) NOT NULL,
  post_title varchar(250) DEFAULT NULL,
  post_text longtext,
  thread_id int(11) DEFAULT '0',
  forum_id int(11) DEFAULT '0',
  poster_id int(11) DEFAULT '0',
  poster_name varchar(100) DEFAULT '',
  post_date datetime DEFAULT '0000-00-00 00:00:00',
  post_notification tinyint(4) DEFAULT '0',
  post_parent_id int(11) DEFAULT '0',
  visible tinyint(4) DEFAULT '1',
  PRIMARY KEY (iid),
  KEY poster_id (poster_id),
  KEY forum_id (forum_id),
  KEY idx_forum_post_thread_id (thread_id),
  KEY idx_forum_post_visible (visible)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_thread
--

DROP TABLE IF EXISTS c_forum_thread;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_thread (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  thread_id int(11) NOT NULL,
  thread_title varchar(255) DEFAULT NULL,
  forum_id int(11) DEFAULT NULL,
  thread_replies int(11) DEFAULT '0',
  thread_poster_id int(11) DEFAULT NULL,
  thread_poster_name varchar(100) DEFAULT '',
  thread_views int(11) DEFAULT '0',
  thread_last_post int(11) DEFAULT NULL,
  thread_date datetime DEFAULT '0000-00-00 00:00:00',
  thread_sticky tinyint(3)  DEFAULT '0',
  locked int(11) NOT NULL DEFAULT '0',
  session_id int(10)  DEFAULT NULL,
  thread_title_qualify varchar(255) DEFAULT '',
  thread_qualify_max float(6,2)  NOT NULL DEFAULT '0.00',
  thread_close_date datetime DEFAULT '0000-00-00 00:00:00',
  thread_weight float(6,2)  NOT NULL DEFAULT '0.00',
  PRIMARY KEY (iid),
  KEY idx_forum_thread_forum_id (forum_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_thread_qualify
--

DROP TABLE IF EXISTS c_forum_thread_qualify;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_thread_qualify (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  user_id int(10)  NOT NULL,
  thread_id int(11) NOT NULL,
  qualify float(6,2) NOT NULL DEFAULT '0.00',
  qualify_user_id int(11) DEFAULT NULL,
  qualify_time datetime DEFAULT '0000-00-00 00:00:00',
  session_id int(11) DEFAULT NULL,
  PRIMARY KEY (iid),
  KEY user_id (user_id,thread_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_forum_thread_qualify_log
--

DROP TABLE IF EXISTS c_forum_thread_qualify_log;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_forum_thread_qualify_log (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  user_id int(10)  NOT NULL,
  thread_id int(11) NOT NULL,
  qualify float(6,2) NOT NULL DEFAULT '0.00',
  qualify_user_id int(11) DEFAULT NULL,
  qualify_time datetime DEFAULT '0000-00-00 00:00:00',
  session_id int(11) DEFAULT NULL,
  PRIMARY KEY (iid),
  KEY user_id (user_id,thread_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_glossary
--

DROP TABLE IF EXISTS c_glossary;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_glossary (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  glossary_id int(10)  NOT NULL,
  name varchar(255) NOT NULL,
  description longtext NOT NULL,
  display_order int(11) DEFAULT NULL,
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_group_category
--

DROP TABLE IF EXISTS c_group_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_category (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  title varchar(255) NOT NULL DEFAULT '',
  description longtext NOT NULL,
  doc_state tinyint(3)  NOT NULL DEFAULT '1',
  calendar_state tinyint(3)  NOT NULL DEFAULT '1',
  work_state tinyint(3)  NOT NULL DEFAULT '1',
  announcements_state tinyint(3)  NOT NULL DEFAULT '1',
  forum_state tinyint(3)  NOT NULL DEFAULT '1',
  wiki_state tinyint(3)  NOT NULL DEFAULT '1',
  chat_state tinyint(3)  NOT NULL DEFAULT '1',
  max_student int(10)  NOT NULL DEFAULT '8',
  self_reg_allowed tinyint(3)  NOT NULL DEFAULT '0',
  self_unreg_allowed tinyint(3)  NOT NULL DEFAULT '0',
  groups_per_user int(10)  NOT NULL DEFAULT '0',
  display_order int(10)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_group_info
--

DROP TABLE IF EXISTS c_group_info;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_info (
  iid int NOT NULL AUTO_INCREMENT,
  id int NOT NULL,
  c_id int NOT NULL,
  name varchar(100) DEFAULT NULL,
  category_id int  NOT NULL DEFAULT '0',
  description longtext,
  max_student int  NOT NULL DEFAULT '8',
  doc_state tinyint(3)  NOT NULL DEFAULT '1',
  calendar_state tinyint(3)  NOT NULL DEFAULT '0',
  work_state tinyint(3)  NOT NULL DEFAULT '0',
  announcements_state tinyint(3)  NOT NULL DEFAULT '0',
  forum_state tinyint(3)  NOT NULL DEFAULT '0',
  wiki_state tinyint(3)  NOT NULL DEFAULT '1',
  chat_state tinyint(3)  NOT NULL DEFAULT '1',
  secret_directory varchar(255) DEFAULT NULL,
  self_registration_allowed tinyint(3)  NOT NULL DEFAULT '0',
  self_unregistration_allowed tinyint(3)  NOT NULL DEFAULT '0',
  session_id int NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_group_rel_tutor
--

DROP TABLE IF EXISTS c_group_rel_tutor;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_rel_tutor (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  group_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_group_rel_user
--

DROP TABLE IF EXISTS c_group_rel_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_group_rel_user (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  user_id int(10)  NOT NULL,
  group_id int(10)  NOT NULL DEFAULT '0',
  status int(11) NOT NULL DEFAULT '0',
  role char(50) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_item_property
--

DROP TABLE IF EXISTS c_item_property;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_item_property (
  iid int NOT NULL AUTO_INCREMENT,
  id bigint(20)  NOT NULL,
  c_id int(11) NOT NULL,
  tool varchar(100) NOT NULL DEFAULT '',
  insert_user_id int(10)  NOT NULL DEFAULT '0',
  insert_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  lastedit_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  ref int(11) NOT NULL DEFAULT '0',
  lastedit_type varchar(100) NOT NULL DEFAULT '',
  lastedit_user_id int(10)  NOT NULL DEFAULT '0',
  to_group_id int(10)  DEFAULT NULL,
  to_user_id int(10)  DEFAULT NULL,
  visibility tinyint(4) NOT NULL DEFAULT '1',
  start_visible datetime DEFAULT NULL,
  end_visible datetime DEFAULT NULL,
  id_session int(11) DEFAULT NULL,
  PRIMARY KEY (iid),
  KEY idx_item_property_toolref (tool, ref)
) DEFAULT CHARSET=utf8;

ALTER TABLE c_item_property ADD INDEX idx_item_property_tooliuid(tool, insert_user_id);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_link
--

DROP TABLE IF EXISTS c_link;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_link (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int  NOT NULL,
  url text NOT NULL,
  title varchar(150) DEFAULT NULL,
  description text,
  category_id int(10)  DEFAULT NULL,
  display_order int(10)  NOT NULL DEFAULT '0',
  on_homepage varchar(100) NOT NULL DEFAULT '0',
  target char(10) DEFAULT '_self',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_link_category
--

DROP TABLE IF EXISTS c_link_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_link_category (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  category_title varchar(255) NOT NULL,
  description text,
  display_order mediumint(8)  NOT NULL DEFAULT '0',
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_lp
--

DROP TABLE IF EXISTS c_lp;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp (
  iid int  NOT NULL AUTO_INCREMENT,
  id int(10),
  c_id int(11) NOT NULL,
  lp_type int(10)  NOT NULL,
  name varchar(255) NOT NULL,
  ref tinytext,
  description longtext,
  path text NOT NULL,
  force_commit tinyint(3)  NOT NULL DEFAULT '0',
  default_view_mod char(32) NOT NULL DEFAULT 'embedded',
  default_encoding char(32) NOT NULL DEFAULT 'UTF-8',
  display_order int(10)  NOT NULL DEFAULT '0',
  content_maker tinytext NOT NULL,
  content_local varchar(32) NOT NULL DEFAULT 'local',
  content_license text NOT NULL,
  prevent_reinit tinyint(3)  NOT NULL DEFAULT '1',
  js_lib tinytext NOT NULL,
  debug tinyint(3)  NOT NULL DEFAULT '0',
  theme varchar(255) NOT NULL DEFAULT '',
  preview_image varchar(255) NOT NULL DEFAULT '',
  author varchar(255) NOT NULL DEFAULT '',
  session_id int(10)  NOT NULL DEFAULT '0',
  prerequisite int(10)  NOT NULL DEFAULT '0',
  hide_toc_frame tinyint(4) NOT NULL DEFAULT '0',
  seriousgame_mode tinyint(4) NOT NULL DEFAULT '0',
  use_max_score int(10)  NOT NULL DEFAULT '1',
  autolunch int(10)  NOT NULL DEFAULT '0',
  created_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  modified_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  publicated_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  expired_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  max_attempts int(11) NOT NULL DEFAULT '0',
  category_id int(10)  NOT NULL DEFAULT '0',
  subscribe_users int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_lp_category
--

DROP TABLE IF EXISTS c_lp_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_category (
  iid int(10)  NOT NULL AUTO_INCREMENT,
  id int(10)  NOT NULL,
  c_id int(10)  NOT NULL,
  name varchar(255) DEFAULT NULL,
  position int(11) DEFAULT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_lp_item
--

DROP TABLE IF EXISTS c_lp_item;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_item (
  iid int NOT NULL AUTO_INCREMENT,
  id int(10)  NOT NULL,
  c_id int(11) NOT NULL,
  lp_id int(10)  NOT NULL,
  item_type char(32) NOT NULL DEFAULT 'dokeos_document',
  ref tinytext NOT NULL,
  title varchar(511) NOT NULL,
  description varchar(511) NOT NULL DEFAULT '',
  path text NOT NULL,
  min_score float  NOT NULL DEFAULT '0',
  max_score float  DEFAULT '100',
  mastery_score float  DEFAULT NULL,
  parent_item_id int(10)  NOT NULL DEFAULT '0',
  previous_item_id int(10)  NOT NULL DEFAULT '0',
  next_item_id int(10)  NOT NULL DEFAULT '0',
  display_order int(10)  NOT NULL DEFAULT '0',
  prerequisite text,
  parameters text,
  launch_data text NOT NULL,
  max_time_allowed char(13) DEFAULT '',
  terms text,
  search_did int(11) DEFAULT NULL,
  audio varchar(250) DEFAULT NULL,
  PRIMARY KEY (iid),
  KEY lp_id (lp_id),
  KEY idx_c_lp_item_cid_lp_id (c_id,lp_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_lp_item_view
--

DROP TABLE IF EXISTS c_lp_item_view;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_item_view (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id bigint(20)  NOT NULL,
  lp_item_id int(10)  NOT NULL,
  lp_view_id int(10)  NOT NULL,
  view_count int(10)  NOT NULL DEFAULT '0',
  start_time int(10)  NOT NULL,
  total_time int(10)  NOT NULL DEFAULT '0',
  score float  NOT NULL DEFAULT '0',
  status char(32) NOT NULL DEFAULT 'not attempted',
  suspend_data longtext,
  lesson_location text,
  core_exit varchar(32) NOT NULL DEFAULT 'none',
  max_score varchar(8) DEFAULT '',
  PRIMARY KEY (iid),
  KEY lp_item_id (lp_item_id),
  KEY lp_view_id (lp_view_id),
  KEY idx_c_lp_item_view_cid_lp_view_id_lp_item_id (c_id,lp_view_id,lp_item_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_lp_iv_interaction
--

DROP TABLE IF EXISTS c_lp_iv_interaction;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_iv_interaction (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id bigint(20)  NOT NULL,
  order_id int(10)  NOT NULL DEFAULT '0',
  lp_iv_id bigint(20)  NOT NULL,
  interaction_id varchar(255) NOT NULL DEFAULT '',
  interaction_type varchar(255) NOT NULL DEFAULT '',
  weighting double NOT NULL DEFAULT '0',
  completion_time varchar(16) NOT NULL DEFAULT '',
  correct_responses text NOT NULL,
  student_response text NOT NULL,
  result varchar(255) NOT NULL DEFAULT '',
  latency varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (iid),
  KEY lp_iv_id (lp_iv_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_lp_iv_objective
--

DROP TABLE IF EXISTS c_lp_iv_objective;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_iv_objective (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id bigint(20)  NOT NULL,
  lp_iv_id bigint(20)  NOT NULL,
  order_id int(10)  NOT NULL DEFAULT '0',
  objective_id varchar(255) NOT NULL DEFAULT '',
  score_raw float  NOT NULL DEFAULT '0',
  score_max float  NOT NULL DEFAULT '0',
  score_min float  NOT NULL DEFAULT '0',
  status char(32) NOT NULL DEFAULT 'not attempted',
  PRIMARY KEY (iid),
  KEY lp_iv_id (lp_iv_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_lp_view
--

DROP TABLE IF EXISTS c_lp_view;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_lp_view (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  lp_id int(10)  NOT NULL,
  user_id int(10)  NOT NULL,
  view_count int(10)  NOT NULL DEFAULT '0',
  last_item int(10)  NOT NULL DEFAULT '0',
  progress int(10)  DEFAULT '0',
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY lp_id (lp_id),
  KEY user_id (user_id),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_notebook
--

DROP TABLE IF EXISTS c_notebook;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_notebook (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  notebook_id int(10)  NOT NULL,
  user_id int(10)  NOT NULL,
  course varchar(40) NOT NULL,
  session_id int(11) NOT NULL DEFAULT '0',
  title varchar(255) NOT NULL,
  description longtext NOT NULL,
  creation_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  update_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  status int(11) DEFAULT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_online_connected
--

DROP TABLE IF EXISTS c_online_connected;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_online_connected (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  user_id int(10)  NOT NULL,
  last_connection datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_online_link
--

DROP TABLE IF EXISTS c_online_link;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_online_link (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  name char(50) NOT NULL DEFAULT '',
  url char(100) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_permission_group
--

DROP TABLE IF EXISTS c_permission_group;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_permission_group (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  group_id int(11) NOT NULL DEFAULT '0',
  tool varchar(250) NOT NULL DEFAULT '',
  action varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_permission_task
--

DROP TABLE IF EXISTS c_permission_task;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_permission_task (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  task_id int(11) NOT NULL DEFAULT '0',
  tool varchar(250) NOT NULL DEFAULT '',
  action varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_permission_user
--

DROP TABLE IF EXISTS c_permission_user;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_permission_user (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int NOT NULL,
  user_id int(11) NOT NULL DEFAULT '0',
  tool varchar(250) NOT NULL DEFAULT '',
  action varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_quiz
--

DROP TABLE IF EXISTS c_quiz;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz (
  iid int  NOT NULL auto_increment,
  c_id int(11) NOT NULL,
  title varchar(255) NOT NULL,
  description longtext,
  sound varchar(255) DEFAULT NULL,
  type tinyint(3)  NOT NULL DEFAULT '1',
  random int(11) NOT NULL DEFAULT '0',
  random_answers tinyint(3)  NOT NULL DEFAULT '0',
  active tinyint(4) NOT NULL DEFAULT '0',
  results_disabled int(10)  NOT NULL DEFAULT '0',
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
  text_when_finished longtext,
  display_category_name int(11) NOT NULL DEFAULT '1',
  pass_percentage int(11) DEFAULT NULL,
  autolaunch INT DEFAULT 0,
  end_button int NOT NULL DEFAULT 0,
  email_notification_template longtext default '',
  email_notification_template_to_user longtext default '',
  model_type INT default 1,
  question_selection_type INT default 1,
  hide_question_title INT default 0,
  score_type_model INT default 0,
  global_category_id INT default NULL,
  on_success_message longtext default '',
  on_failed_message longtext default '',
  notify_user_by_email int default 0,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_quiz_answer
--

DROP TABLE IF EXISTS c_quiz_answer;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_answer (
  iid bigint NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  question_id int(10)  NOT NULL,
  answer longtext NOT NULL,
  correct mediumint(8)  DEFAULT NULL,
  comment longtext,
  ponderation float(6,2) NOT NULL DEFAULT '0.00',
  position mediumint(8)  NOT NULL DEFAULT '1',
  hotspot_coordinates text,
  hotspot_type varchar(100) DEFAULT NULL,
  destination text NOT NULL,
  answer_code char(10) DEFAULT '',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
ALTER TABLE c_quiz_answer ADD INDEX idx_cqa_qid (question_id);
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_quiz_order
--

DROP TABLE IF EXISTS c_quiz_order;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_order (
  iid bigint NOT NULL auto_increment,
  c_id int(10)  NOT NULL,
  session_id int(10)  NOT NULL,
  exercise_id int(11) NOT NULL,
  exercise_order int(11) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_quiz_question
--

DROP TABLE IF EXISTS c_quiz_question;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_question (
  iid bigint NOT NULL auto_increment,
  c_id int(11) NOT NULL,
  question longtext NOT NULL,
  description longtext,
  ponderation float(6,2) NOT NULL DEFAULT '0.00',
  position mediumint(8)  NOT NULL DEFAULT '1',
  type tinyint(3)  NOT NULL DEFAULT '2',
  picture varchar(50) DEFAULT NULL,
  level int(10)  NOT NULL DEFAULT '0',
  extra varchar(255) DEFAULT NULL,
  question_code char(10) DEFAULT '',
  parent_id int(10)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

ALTER TABLE c_quiz_question ADD INDEX idx_c_q_qst_cpt (c_id, parent_id, type);

--
-- Table structure for table c_quiz_category
--

DROP TABLE IF EXISTS c_quiz_category;
CREATE TABLE c_quiz_category (
  iid bigint NOT NULL AUTO_INCREMENT,
  parent_id bigint DEFAULT NULL,
  c_id int(11) NOT NULL,
  title varchar(255) NOT NULL,
  description longtext NOT NULL,
  lvl int default NULL,
  lft int default NULL,
  rgt int default NULL,
  root int default NULL,
  visibility INT default 1,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;

--
-- Table structure for table c_quiz_question_option
--

DROP TABLE IF EXISTS c_quiz_question_option;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_question_option (
  iid bigint  NOT NULL auto_increment,
  c_id int(11) NOT NULL,
  question_id int(11) NOT NULL,
  name varchar(255) DEFAULT NULL,
  position int(10)  NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_quiz_question_rel_category
--

DROP TABLE IF EXISTS c_quiz_question_rel_category;
CREATE TABLE c_quiz_question_rel_category (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int NOT NULL,
  question_id bigint NOT NULL,
	category_id bigint NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;

ALTER TABLE c_quiz_question_rel_category ADD INDEX idx_c_q_qst_r_cat_qc(iid, question_id, c_id);

DROP TABLE IF EXISTS c_quiz_rel_category;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_rel_category (
  iid bigint  NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  category_id int(11) NOT NULL,
  exercise_id int  NOT NULL,
  count_questions int NOT NULL default 0,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_quiz_rel_question
--

DROP TABLE IF EXISTS c_quiz_rel_question;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_quiz_rel_question (
  iid bigint  NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  question_id int(10)  NOT NULL,
  exercice_id int(10)  NOT NULL,
  question_order int(10)  NOT NULL DEFAULT '1',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
ALTER TABLE c_quiz_rel_question ADD INDEX idx_cqrq_id (question_id);
ALTER TABLE c_quiz_rel_question ADD INDEX idx_cqrq_cidexid (c_id, exercice_id);
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS c_student_publication;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_student_publication (
  iid int(10)  NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  url varchar(255) DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  filename varchar(255) DEFAULT NULL,
  description longtext,
  author varchar(255) DEFAULT NULL,
  active tinyint(4) DEFAULT NULL,
  accepted tinyint(4) DEFAULT '0',
  post_group_id int(11) NOT NULL DEFAULT '0',
  sent_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  filetype NOT NULL DEFAULT 'file',
  has_properties int(10)  NOT NULL DEFAULT '0',
  view_properties tinyint(4) DEFAULT NULL,
  qualification float(6,2)  NOT NULL DEFAULT '0.00',
  date_of_qualification datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  parent_id int(10)  NOT NULL DEFAULT '0',
  qualificator_id int(10)  NOT NULL DEFAULT '0',
  weight float(6,2)  NOT NULL DEFAULT '0.00',
  session_id int(10)  NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL,
  allow_text_assignment int(11) NOT NULL DEFAULT '0',
  contains_file int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_student_publication_assignment
--

DROP TABLE IF EXISTS c_student_publication_assignment;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_student_publication_assignment (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  expires_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  ends_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  add_to_calendar tinyint(4) NOT NULL,
  enable_qualification tinyint(4) NOT NULL,
  publication_id int(11) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_survey
--

DROP TABLE IF EXISTS c_survey;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  survey_id int(10)  NOT NULL,
  code varchar(20) DEFAULT NULL,
  title text,
  subtitle text,
  author varchar(20) DEFAULT NULL,
  lang varchar(20) DEFAULT NULL,
  avail_from date DEFAULT NULL,
  avail_till date DEFAULT NULL,
  is_shared char(1) DEFAULT '1',
  template varchar(20) DEFAULT NULL,
  intro longtext,
  surveythanks text,
  creation_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  invited int(11) NOT NULL,
  answered int(11) NOT NULL,
  invite_mail text NOT NULL,
  reminder_mail text NOT NULL,
  mail_subject varchar(255) NOT NULL,
  anonymous varchar(255) NOT NULL DEFAULT '0',
  access_condition text,
  shuffle tinyint(1) NOT NULL DEFAULT '0',
  one_question_per_page tinyint(1) NOT NULL DEFAULT '0',
  survey_version varchar(255) NOT NULL DEFAULT '',
  parent_id int(10)  NOT NULL,
  survey_type int(11) NOT NULL DEFAULT '0',
  show_form_profile int(11) NOT NULL DEFAULT '0',
  form_fields text NOT NULL,
  session_id int(10)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_survey_answer
--

DROP TABLE IF EXISTS c_survey_answer;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_answer (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  answer_id int(10)  NOT NULL,
  survey_id int(10)  NOT NULL,
  question_id int(10)  NOT NULL,
  option_id text NOT NULL,
  value int(10)  NOT NULL,
  user varchar(250) NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_survey_group
--

DROP TABLE IF EXISTS c_survey_group;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_group (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10)  NOT NULL,
  name varchar(20) NOT NULL,
  description varchar(255) NOT NULL,
  survey_id int(10)  NOT NULL,
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_survey_invitation
--

DROP TABLE IF EXISTS c_survey_invitation;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_invitation (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  survey_invitation_id int(10)  NOT NULL,
  survey_code varchar(20) NOT NULL,
  user varchar(250) NOT NULL,
  invitation_code varchar(250) NOT NULL,
  invitation_date datetime NOT NULL,
  reminder_date datetime NOT NULL,
  answered int(11) NOT NULL DEFAULT '0',
  session_id int(10)  NOT NULL DEFAULT '0',
  group_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_survey_question
--

DROP TABLE IF EXISTS c_survey_question;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_question (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  question_id int(10)  NOT NULL,
  survey_id int(10)  NOT NULL,
  survey_question longtext NOT NULL,
  survey_question_comment longtext NOT NULL,
  type varchar(250) NOT NULL,
  display varchar(10) NOT NULL,
  sort int(11) NOT NULL,
  shared_question_id int(11) DEFAULT NULL,
  max_value int(11) DEFAULT NULL,
  survey_group_pri int(10)  NOT NULL DEFAULT '0',
  survey_group_sec1 int(10)  NOT NULL DEFAULT '0',
  survey_group_sec2 int(10)  NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_survey_question_option
--

DROP TABLE IF EXISTS c_survey_question_option;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_survey_question_option (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  question_option_id int(10)  NOT NULL,
  question_id int(10)  NOT NULL,
  survey_id int(10)  NOT NULL,
  option_text text NOT NULL,
  sort int(11) NOT NULL,
  value int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_thematic
--

DROP TABLE IF EXISTS c_thematic;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_thematic (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  title varchar(255) NOT NULL,
  content longtext,
  display_order int(10)  NOT NULL DEFAULT '0',
  active tinyint(4) NOT NULL DEFAULT '0',
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY active (active,session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_thematic_advance
--

DROP TABLE IF EXISTS c_thematic_advance;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_thematic_advance (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  thematic_id int(11) NOT NULL,
  attendance_id int(11) NOT NULL DEFAULT '0',
  content longtext,
  start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  duration int(11) NOT NULL DEFAULT '0',
  done_advance tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid),
  KEY thematic_id (thematic_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_thematic_plan
--

DROP TABLE IF EXISTS c_thematic_plan;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_thematic_plan (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  thematic_id int(11) NOT NULL,
  title varchar(255) NOT NULL,
  description longtext,
  description_type int(11) NOT NULL,
  PRIMARY KEY (iid),
  KEY thematic_id (thematic_id,description_type)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_tool
--

DROP TABLE IF EXISTS c_tool;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_tool (
  iid int  NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(10),
  name varchar(255) NOT NULL,
  link varchar(255) NOT NULL,
  image varchar(255) DEFAULT NULL,
  visibility tinyint(3)  DEFAULT '0',
  admin varchar(255) DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  added_tool tinyint(3)  DEFAULT '1',
  target varchar(100) NOT NULL DEFAULT '_self',
  category varchar(20) NOT NULL DEFAULT 'authoring',
  session_id int(11) DEFAULT '0',
  custom_icon varchar(255) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  PRIMARY KEY (iid),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_tool_intro
--

DROP TABLE IF EXISTS c_tool_intro;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_tool_intro (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id varchar(50) NOT NULL,
  intro_text longtext NOT NULL,
  session_id int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_wiki
--

DROP TABLE IF EXISTS c_wiki;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  page_id int(11) NOT NULL DEFAULT '0',
  reflink varchar(255) NOT NULL DEFAULT 'index',
  title varchar(255) NOT NULL,
  content longtext NOT NULL,
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
  PRIMARY KEY (iid),
  KEY reflink (reflink),
  KEY group_id (group_id),
  KEY page_id (page_id),
  KEY session_id (session_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_wiki_conf
--

DROP TABLE IF EXISTS c_wiki_conf;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki_conf (
  iid int NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (iid),
  KEY page_id (page_id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_wiki_discuss
--

DROP TABLE IF EXISTS c_wiki_discuss;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki_discuss (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  publication_id int(11) NOT NULL DEFAULT '0',
  userc_id int(11) NOT NULL DEFAULT '0',
  comment text NOT NULL,
  p_score varchar(255) DEFAULT NULL,
  dtime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (iid)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table c_wiki_mailcue
--

DROP TABLE IF EXISTS c_wiki_mailcue;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE c_wiki_mailcue (
  iid int NOT NULL AUTO_INCREMENT,
  c_id int(11) NOT NULL,
  id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  type text NOT NULL,
  group_id int(11) DEFAULT NULL,
  session_id int(11) DEFAULT '0',
  PRIMARY KEY (iid),
  KEY c_id (c_id,id)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS c_student_publication_rel_document;
CREATE TABLE c_student_publication_rel_document (
  iid  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  work_id INT NOT NULL,
  document_id INT NOT NULL,
  c_id INT NOT NULL
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS c_student_publication_rel_user;
CREATE TABLE c_student_publication_rel_user (
  iid  INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  work_id INT NOT NULL,
  user_id INT NOT NULL,
  c_id INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS c_student_publication_comment;
CREATE TABLE c_student_publication_comment (
  iid INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  work_id INT NOT NULL,
  c_id INT NOT NULL,
  comment longtext,
  user_id int NOT NULL,
  sent_at datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
