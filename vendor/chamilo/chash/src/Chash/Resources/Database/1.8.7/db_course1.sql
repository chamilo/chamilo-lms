-- MySQL dump 10.13  Distrib 5.5.29, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: chamilo187_1111
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
-- Table structure for table `announcement`
--

DROP TABLE IF EXISTS `announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` text,
  `content` mediumtext,
  `end_date` date DEFAULT NULL,
  `display_order` mediumint(9) NOT NULL DEFAULT '0',
  `email_sent` tinyint(4) DEFAULT '0',
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `announcement_attachment`
--

DROP TABLE IF EXISTS `announcement_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcement_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `comment` text,
  `size` int(11) NOT NULL DEFAULT '0',
  `announcement_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `description` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `attendance_qualify_title` varchar(255) DEFAULT NULL,
  `attendance_qualify_max` int(11) NOT NULL DEFAULT '0',
  `attendance_weight` float(6,2) NOT NULL DEFAULT '0.00',
  `session_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendance_calendar`
--

DROP TABLE IF EXISTS `attendance_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attendance_id` int(11) NOT NULL,
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `done_attendance` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `attendance_id` (`attendance_id`),
  KEY `done_attendance` (`done_attendance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendance_result`
--

DROP TABLE IF EXISTS `attendance_result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `attendance_id` (`attendance_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attendance_sheet`
--

DROP TABLE IF EXISTS `attendance_sheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_sheet` (
  `user_id` int(11) NOT NULL,
  `attendance_calendar_id` int(11) NOT NULL,
  `presence` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`attendance_calendar_id`),
  KEY `presence` (`presence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog`
--

DROP TABLE IF EXISTS `blog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog` (
  `blog_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `blog_name` varchar(250) NOT NULL DEFAULT '',
  `blog_subtitle` varchar(250) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `visibility` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`blog_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with blogs in this course';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_attachment`
--

DROP TABLE IF EXISTS `blog_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_attachment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL COMMENT 'the real filename',
  `comment` text,
  `size` int(11) NOT NULL DEFAULT '0',
  `post_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL COMMENT 'the user s file name',
  `blog_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_comment`
--

DROP TABLE IF EXISTS `blog_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_comment` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `comment` longtext NOT NULL,
  `author_id` int(11) NOT NULL DEFAULT '0',
  `date_creation` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `blog_id` mediumint(9) NOT NULL DEFAULT '0',
  `post_id` int(11) NOT NULL DEFAULT '0',
  `task_id` int(11) DEFAULT NULL,
  `parent_comment_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with comments on posts in a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_post`
--

DROP TABLE IF EXISTS `blog_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_post` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `full_text` longtext NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `blog_id` mediumint(9) NOT NULL DEFAULT '0',
  `author_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with posts / blog.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_rating`
--

DROP TABLE IF EXISTS `blog_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_rating` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL DEFAULT '0',
  `rating_type` enum('post','comment') NOT NULL DEFAULT 'post',
  `item_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `rating` mediumint(9) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rating_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with ratings for post/comments in a certain blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_rel_user`
--

DROP TABLE IF EXISTS `blog_rel_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_rel_user` (
  `blog_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`blog_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table representing users subscribed to a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_task`
--

DROP TABLE IF EXISTS `blog_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_task` (
  `task_id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `blog_id` mediumint(9) NOT NULL DEFAULT '0',
  `title` varchar(250) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `color` varchar(10) NOT NULL DEFAULT '',
  `system_task` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with tasks for a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `blog_task_rel_user`
--

DROP TABLE IF EXISTS `blog_task_rel_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_task_rel_user` (
  `blog_id` mediumint(9) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `task_id` mediumint(9) NOT NULL DEFAULT '0',
  `target_date` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`blog_id`,`user_id`,`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table with tasks assigned to a user in a blog';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar_event`
--

DROP TABLE IF EXISTS `calendar_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `parent_event_id` int(11) DEFAULT NULL,
  `session_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar_event_attachment`
--

DROP TABLE IF EXISTS `calendar_event_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_event_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `comment` text,
  `size` int(11) NOT NULL DEFAULT '0',
  `agenda_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar_event_repeat`
--

DROP TABLE IF EXISTS `calendar_event_repeat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_event_repeat` (
  `cal_id` int(11) NOT NULL DEFAULT '0',
  `cal_type` varchar(20) DEFAULT NULL,
  `cal_end` int(11) DEFAULT NULL,
  `cal_frequency` int(11) DEFAULT '1',
  `cal_days` char(7) DEFAULT NULL,
  PRIMARY KEY (`cal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar_event_repeat_not`
--

DROP TABLE IF EXISTS `calendar_event_repeat_not`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_event_repeat_not` (
  `cal_id` int(11) NOT NULL,
  `cal_date` int(11) NOT NULL,
  PRIMARY KEY (`cal_id`,`cal_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chat_connected`
--

DROP TABLE IF EXISTS `chat_connected`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_connected` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `last_connection` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `session_id` int(11) NOT NULL DEFAULT '0',
  `to_group_id` int(11) NOT NULL DEFAULT '0',
  KEY `char_connected_index` (`user_id`,`session_id`,`to_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_description`
--

DROP TABLE IF EXISTS `course_description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_description` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `content` text,
  `session_id` smallint(6) DEFAULT '0',
  `description_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `progress` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_setting`
--

DROP TABLE IF EXISTS `course_setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `variable` varchar(255) NOT NULL DEFAULT '',
  `subkey` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `value` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `comment` varchar(255) DEFAULT NULL,
  `subkeytext` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `document`
--

DROP TABLE IF EXISTS `document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `document` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL DEFAULT '',
  `comment` text,
  `title` varchar(255) DEFAULT NULL,
  `filetype` set('file','folder') NOT NULL DEFAULT 'file',
  `size` int(11) NOT NULL DEFAULT '0',
  `readonly` tinyint(3) unsigned NOT NULL,
  `session_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dropbox_category`
--

DROP TABLE IF EXISTS `dropbox_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_category` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_name` text NOT NULL,
  `received` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sent` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `session_id` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dropbox_feedback`
--

DROP TABLE IF EXISTS `dropbox_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_feedback` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL DEFAULT '0',
  `author_user_id` int(11) NOT NULL DEFAULT '0',
  `feedback` text NOT NULL,
  `feedback_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`feedback_id`),
  KEY `file_id` (`file_id`),
  KEY `author_user_id` (`author_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dropbox_file`
--

DROP TABLE IF EXISTS `dropbox_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_file` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uploader_id` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(250) NOT NULL DEFAULT '',
  `filesize` int(10) unsigned NOT NULL,
  `title` varchar(250) DEFAULT '',
  `description` varchar(250) DEFAULT '',
  `author` varchar(250) DEFAULT '',
  `upload_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_upload_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `session_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UN_filename` (`filename`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dropbox_person`
--

DROP TABLE IF EXISTS `dropbox_person`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_person` (
  `file_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`file_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dropbox_post`
--

DROP TABLE IF EXISTS `dropbox_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dropbox_post` (
  `file_id` int(10) unsigned NOT NULL,
  `dest_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `feedback_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `feedback` text,
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `session_id` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`file_id`,`dest_user_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_attachment`
--

DROP TABLE IF EXISTS `forum_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `comment` text,
  `size` int(11) NOT NULL DEFAULT '0',
  `post_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_category`
--

DROP TABLE IF EXISTS `forum_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_category` (
  `cat_id` int(11) NOT NULL AUTO_INCREMENT,
  `cat_title` varchar(255) NOT NULL DEFAULT '',
  `cat_comment` text,
  `cat_order` int(11) NOT NULL DEFAULT '0',
  `locked` int(11) NOT NULL DEFAULT '0',
  `session_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_forum`
--

DROP TABLE IF EXISTS `forum_forum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_forum` (
  `forum_id` int(11) NOT NULL AUTO_INCREMENT,
  `forum_title` varchar(255) NOT NULL DEFAULT '',
  `forum_comment` text,
  `forum_threads` int(11) DEFAULT '0',
  `forum_posts` int(11) DEFAULT '0',
  `forum_last_post` int(11) DEFAULT '0',
  `forum_category` int(11) DEFAULT NULL,
  `allow_anonymous` int(11) DEFAULT NULL,
  `allow_edit` int(11) DEFAULT NULL,
  `approval_direct_post` varchar(20) DEFAULT NULL,
  `allow_attachments` int(11) DEFAULT NULL,
  `allow_new_threads` int(11) DEFAULT NULL,
  `default_view` varchar(20) DEFAULT NULL,
  `forum_of_group` varchar(20) DEFAULT NULL,
  `forum_group_public_private` varchar(20) DEFAULT 'public',
  `forum_order` int(11) DEFAULT NULL,
  `locked` int(11) NOT NULL DEFAULT '0',
  `session_id` int(11) NOT NULL DEFAULT '0',
  `forum_image` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`forum_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_mailcue`
--

DROP TABLE IF EXISTS `forum_mailcue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_mailcue` (
  `thread_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_notification`
--

DROP TABLE IF EXISTS `forum_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_notification` (
  `user_id` int(11) DEFAULT NULL,
  `forum_id` int(11) DEFAULT NULL,
  `thread_id` int(11) DEFAULT NULL,
  `post_id` int(11) DEFAULT NULL,
  KEY `user_id` (`user_id`),
  KEY `forum_id` (`forum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_post`
--

DROP TABLE IF EXISTS `forum_post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_post` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_title` varchar(250) DEFAULT NULL,
  `post_text` text,
  `thread_id` int(11) DEFAULT '0',
  `forum_id` int(11) DEFAULT '0',
  `poster_id` int(11) DEFAULT '0',
  `poster_name` varchar(100) DEFAULT '',
  `post_date` datetime DEFAULT '0000-00-00 00:00:00',
  `post_notification` tinyint(4) DEFAULT '0',
  `post_parent_id` int(11) DEFAULT '0',
  `visible` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`post_id`),
  KEY `poster_id` (`poster_id`),
  KEY `forum_id` (`forum_id`),
  KEY `idx_forum_post_thread_id` (`thread_id`),
  KEY `idx_forum_post_visible` (`visible`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_thread`
--

DROP TABLE IF EXISTS `forum_thread`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_thread` (
  `thread_id` int(11) NOT NULL AUTO_INCREMENT,
  `thread_title` varchar(255) DEFAULT NULL,
  `forum_id` int(11) DEFAULT NULL,
  `thread_replies` int(11) DEFAULT '0',
  `thread_poster_id` int(11) DEFAULT NULL,
  `thread_poster_name` varchar(100) DEFAULT '',
  `thread_views` int(11) DEFAULT '0',
  `thread_last_post` int(11) DEFAULT NULL,
  `thread_date` datetime DEFAULT '0000-00-00 00:00:00',
  `thread_sticky` tinyint(3) unsigned DEFAULT '0',
  `locked` int(11) NOT NULL DEFAULT '0',
  `session_id` int(10) unsigned DEFAULT NULL,
  `thread_title_qualify` varchar(255) DEFAULT '',
  `thread_qualify_max` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `thread_close_date` datetime DEFAULT '0000-00-00 00:00:00',
  `thread_weight` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`thread_id`),
  KEY `idx_forum_thread_forum_id` (`forum_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_thread_qualify`
--

DROP TABLE IF EXISTS `forum_thread_qualify`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_thread_qualify` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `thread_id` int(11) NOT NULL,
  `qualify` float(6,2) NOT NULL DEFAULT '0.00',
  `qualify_user_id` int(11) DEFAULT NULL,
  `qualify_time` datetime DEFAULT '0000-00-00 00:00:00',
  `session_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `forum_thread_qualify_log`
--

DROP TABLE IF EXISTS `forum_thread_qualify_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_thread_qualify_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `thread_id` int(11) NOT NULL,
  `qualify` float(6,2) NOT NULL DEFAULT '0.00',
  `qualify_user_id` int(11) DEFAULT NULL,
  `qualify_time` datetime DEFAULT '0000-00-00 00:00:00',
  `session_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossary`
--

DROP TABLE IF EXISTS `glossary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossary` (
  `glossary_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `display_order` int(11) DEFAULT NULL,
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`glossary_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_category`
--

DROP TABLE IF EXISTS `group_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `doc_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `calendar_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `work_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `announcements_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `forum_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `wiki_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `chat_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `max_student` smallint(5) unsigned NOT NULL DEFAULT '8',
  `self_reg_allowed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `self_unreg_allowed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `groups_per_user` smallint(5) unsigned NOT NULL DEFAULT '0',
  `display_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_info`
--

DROP TABLE IF EXISTS `group_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `description` text,
  `max_student` smallint(5) unsigned NOT NULL DEFAULT '8',
  `doc_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `calendar_state` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `work_state` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `announcements_state` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `forum_state` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `wiki_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `chat_state` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `secret_directory` varchar(255) DEFAULT NULL,
  `self_registration_allowed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `self_unregistration_allowed` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `session_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_rel_tutor`
--

DROP TABLE IF EXISTS `group_rel_tutor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_rel_tutor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `group_rel_user`
--

DROP TABLE IF EXISTS `group_rel_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_rel_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `role` char(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `item_property`
--

DROP TABLE IF EXISTS `item_property`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tool` varchar(100) NOT NULL DEFAULT '',
  `insert_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `insert_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastedit_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ref` int(11) NOT NULL DEFAULT '0',
  `lastedit_type` varchar(100) NOT NULL DEFAULT '',
  `lastedit_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_group_id` int(10) unsigned DEFAULT NULL,
  `to_user_id` int(10) unsigned DEFAULT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '1',
  `start_visible` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_visible` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `id_session` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_item_property_toolref` (`tool`,`ref`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `link`
--

DROP TABLE IF EXISTS `link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text,
  `category_id` smallint(5) unsigned DEFAULT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `on_homepage` enum('0','1') NOT NULL DEFAULT '0',
  `target` char(10) DEFAULT '_self',
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `link_category`
--

DROP TABLE IF EXISTS `link_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `link_category` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `category_title` varchar(255) NOT NULL,
  `description` text,
  `display_order` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lp`
--

DROP TABLE IF EXISTS `lp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lp_type` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `ref` tinytext,
  `description` text,
  `path` text NOT NULL,
  `force_commit` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `default_view_mod` char(32) NOT NULL DEFAULT 'embedded',
  `default_encoding` char(32) NOT NULL DEFAULT 'UTF-8',
  `display_order` int(10) unsigned NOT NULL DEFAULT '0',
  `content_maker` tinytext NOT NULL,
  `content_local` varchar(32) NOT NULL DEFAULT 'local',
  `content_license` text NOT NULL,
  `prevent_reinit` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `js_lib` tinytext NOT NULL,
  `debug` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `theme` varchar(255) NOT NULL DEFAULT '',
  `preview_image` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(255) NOT NULL DEFAULT '',
  `session_id` int(10) unsigned NOT NULL DEFAULT '0',
  `prerequisite` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lp_item`
--

DROP TABLE IF EXISTS `lp_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lp_id` int(10) unsigned NOT NULL,
  `item_type` char(32) NOT NULL DEFAULT 'dokeos_document',
  `ref` tinytext NOT NULL,
  `title` varchar(511) NOT NULL,
  `description` varchar(511) NOT NULL DEFAULT '',
  `path` text NOT NULL,
  `min_score` float unsigned NOT NULL DEFAULT '0',
  `max_score` float unsigned NOT NULL DEFAULT '100',
  `mastery_score` float unsigned DEFAULT NULL,
  `parent_item_id` int(10) unsigned NOT NULL DEFAULT '0',
  `previous_item_id` int(10) unsigned NOT NULL DEFAULT '0',
  `next_item_id` int(10) unsigned NOT NULL DEFAULT '0',
  `display_order` int(10) unsigned NOT NULL DEFAULT '0',
  `prerequisite` text,
  `parameters` text,
  `launch_data` text NOT NULL,
  `max_time_allowed` char(13) DEFAULT '',
  `terms` text,
  `search_did` int(11) DEFAULT NULL,
  `audio` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lp_id` (`lp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lp_item_view`
--

DROP TABLE IF EXISTS `lp_item_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_item_view` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lp_item_id` int(10) unsigned NOT NULL,
  `lp_view_id` int(10) unsigned NOT NULL,
  `view_count` int(10) unsigned NOT NULL DEFAULT '0',
  `start_time` int(10) unsigned NOT NULL,
  `total_time` int(10) unsigned NOT NULL DEFAULT '0',
  `score` float unsigned NOT NULL DEFAULT '0',
  `status` char(32) NOT NULL DEFAULT 'not attempted',
  `suspend_data` text,
  `lesson_location` text,
  `core_exit` varchar(32) NOT NULL DEFAULT 'none',
  `max_score` varchar(8) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `lp_item_id` (`lp_item_id`),
  KEY `lp_view_id` (`lp_view_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lp_iv_interaction`
--

DROP TABLE IF EXISTS `lp_iv_interaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_iv_interaction` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `lp_iv_id` bigint(20) unsigned NOT NULL,
  `interaction_id` varchar(255) NOT NULL DEFAULT '',
  `interaction_type` varchar(255) NOT NULL DEFAULT '',
  `weighting` double NOT NULL DEFAULT '0',
  `completion_time` varchar(16) NOT NULL DEFAULT '',
  `correct_responses` text NOT NULL,
  `student_response` text NOT NULL,
  `result` varchar(255) NOT NULL DEFAULT '',
  `latency` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `lp_iv_id` (`lp_iv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lp_iv_objective`
--

DROP TABLE IF EXISTS `lp_iv_objective`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_iv_objective` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lp_iv_id` bigint(20) unsigned NOT NULL,
  `order_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `objective_id` varchar(255) NOT NULL DEFAULT '',
  `score_raw` float unsigned NOT NULL DEFAULT '0',
  `score_max` float unsigned NOT NULL DEFAULT '0',
  `score_min` float unsigned NOT NULL DEFAULT '0',
  `status` char(32) NOT NULL DEFAULT 'not attempted',
  PRIMARY KEY (`id`),
  KEY `lp_iv_id` (`lp_iv_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lp_view`
--

DROP TABLE IF EXISTS `lp_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lp_view` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lp_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `view_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_item` int(10) unsigned NOT NULL DEFAULT '0',
  `progress` int(10) unsigned DEFAULT '0',
  `session_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `lp_id` (`lp_id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notebook`
--

DROP TABLE IF EXISTS `notebook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notebook` (
  `notebook_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `course` varchar(40) NOT NULL,
  `session_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `update_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`notebook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `online_connected`
--

DROP TABLE IF EXISTS `online_connected`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `online_connected` (
  `user_id` int(10) unsigned NOT NULL,
  `last_connection` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `online_link`
--

DROP TABLE IF EXISTS `online_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `online_link` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(50) NOT NULL DEFAULT '',
  `url` char(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permission_group`
--

DROP TABLE IF EXISTS `permission_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT '0',
  `tool` varchar(250) NOT NULL DEFAULT '',
  `action` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permission_task`
--

DROP TABLE IF EXISTS `permission_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL DEFAULT '0',
  `tool` varchar(250) NOT NULL DEFAULT '',
  `action` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permission_user`
--

DROP TABLE IF EXISTS `permission_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `tool` varchar(250) NOT NULL DEFAULT '',
  `action` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz`
--

DROP TABLE IF EXISTS `quiz`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `sound` varchar(255) DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `random` smallint(6) NOT NULL DEFAULT '0',
  `random_answers` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `results_disabled` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `access_condition` text,
  `max_attempt` int(11) NOT NULL DEFAULT '0',
  `start_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `feedback_type` int(11) NOT NULL DEFAULT '0',
  `expired_time` int(11) NOT NULL DEFAULT '0',
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz_answer`
--

DROP TABLE IF EXISTS `quiz_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_answer` (
  `id` mediumint(8) unsigned NOT NULL,
  `question_id` mediumint(8) unsigned NOT NULL,
  `answer` text NOT NULL,
  `correct` mediumint(8) unsigned DEFAULT NULL,
  `comment` text,
  `ponderation` float(6,2) NOT NULL DEFAULT '0.00',
  `position` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `hotspot_coordinates` text,
  `hotspot_type` enum('square','circle','poly','delineation') DEFAULT NULL,
  `destination` text NOT NULL,
  `id_auto` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`,`question_id`),
  UNIQUE KEY `id_auto` (`id_auto`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz_question`
--

DROP TABLE IF EXISTS `quiz_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_question` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(511) NOT NULL,
  `description` text,
  `ponderation` float(6,2) NOT NULL DEFAULT '0.00',
  `position` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '2',
  `picture` varchar(50) DEFAULT NULL,
  `level` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `position` (`position`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quiz_rel_question`
--

DROP TABLE IF EXISTS `quiz_rel_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_rel_question` (
  `question_id` mediumint(8) unsigned NOT NULL,
  `exercice_id` mediumint(8) unsigned NOT NULL,
  `question_order` mediumint(8) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`question_id`,`exercice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resource`
--

DROP TABLE IF EXISTS `resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resource` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(10) unsigned DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(250) NOT NULL DEFAULT '',
  `role_comment` text,
  `default_role` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_group`
--

DROP TABLE IF EXISTS `role_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_group` (
  `role_id` int(11) NOT NULL DEFAULT '0',
  `scope` varchar(20) NOT NULL DEFAULT 'course',
  `group_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL DEFAULT '0',
  `tool` varchar(250) NOT NULL DEFAULT '',
  `action` varchar(50) NOT NULL DEFAULT '',
  `default_perm` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_user` (
  `role_id` int(11) NOT NULL DEFAULT '0',
  `scope` varchar(20) NOT NULL DEFAULT 'course',
  `user_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_publication`
--

DROP TABLE IF EXISTS `student_publication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_publication` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `author` varchar(255) DEFAULT NULL,
  `active` tinyint(4) DEFAULT NULL,
  `accepted` tinyint(4) DEFAULT '0',
  `post_group_id` int(11) NOT NULL DEFAULT '0',
  `sent_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `filetype` set('file','folder') NOT NULL DEFAULT 'file',
  `has_properties` int(10) unsigned NOT NULL DEFAULT '0',
  `view_properties` tinyint(4) DEFAULT NULL,
  `qualification` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `date_of_qualification` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `qualificator_id` int(10) unsigned NOT NULL DEFAULT '0',
  `weight` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `session_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student_publication_assignment`
--

DROP TABLE IF EXISTS `student_publication_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_publication_assignment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expires_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ends_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `add_to_calendar` tinyint(4) NOT NULL,
  `enable_qualification` tinyint(4) NOT NULL,
  `publication_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey`
--

DROP TABLE IF EXISTS `survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey` (
  `survey_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) DEFAULT NULL,
  `title` text,
  `subtitle` text,
  `author` varchar(20) DEFAULT NULL,
  `lang` varchar(20) DEFAULT NULL,
  `avail_from` date DEFAULT NULL,
  `avail_till` date DEFAULT NULL,
  `is_shared` char(1) DEFAULT '1',
  `template` varchar(20) DEFAULT NULL,
  `intro` text,
  `surveythanks` text,
  `creation_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `invited` int(11) NOT NULL,
  `answered` int(11) NOT NULL,
  `invite_mail` text NOT NULL,
  `reminder_mail` text NOT NULL,
  `mail_subject` varchar(255) NOT NULL,
  `anonymous` enum('0','1') NOT NULL DEFAULT '0',
  `access_condition` text,
  `shuffle` tinyint(1) NOT NULL DEFAULT '0',
  `one_question_per_page` tinyint(1) NOT NULL DEFAULT '0',
  `survey_version` varchar(255) NOT NULL DEFAULT '',
  `parent_id` int(10) unsigned NOT NULL,
  `survey_type` int(11) NOT NULL DEFAULT '0',
  `show_form_profile` int(11) NOT NULL DEFAULT '0',
  `form_fields` text NOT NULL,
  `session_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`survey_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_answer`
--

DROP TABLE IF EXISTS `survey_answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_answer` (
  `answer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` int(10) unsigned NOT NULL,
  `question_id` int(10) unsigned NOT NULL,
  `option_id` text NOT NULL,
  `value` int(10) unsigned NOT NULL,
  `user` varchar(250) NOT NULL,
  PRIMARY KEY (`answer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_group`
--

DROP TABLE IF EXISTS `survey_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `survey_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_invitation`
--

DROP TABLE IF EXISTS `survey_invitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_invitation` (
  `survey_invitation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `survey_code` varchar(20) NOT NULL,
  `user` varchar(250) NOT NULL,
  `invitation_code` varchar(250) NOT NULL,
  `invitation_date` datetime NOT NULL,
  `reminder_date` datetime NOT NULL,
  `answered` int(11) NOT NULL DEFAULT '0',
  `session_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`survey_invitation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_question`
--

DROP TABLE IF EXISTS `survey_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_question` (
  `question_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` int(10) unsigned NOT NULL,
  `survey_question` text NOT NULL,
  `survey_question_comment` text NOT NULL,
  `type` varchar(250) NOT NULL,
  `display` varchar(10) NOT NULL,
  `sort` int(11) NOT NULL,
  `shared_question_id` int(11) DEFAULT NULL,
  `max_value` int(11) DEFAULT NULL,
  `survey_group_pri` int(10) unsigned NOT NULL DEFAULT '0',
  `survey_group_sec1` int(10) unsigned NOT NULL DEFAULT '0',
  `survey_group_sec2` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_question_option`
--

DROP TABLE IF EXISTS `survey_question_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_question_option` (
  `question_option_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `survey_id` int(10) unsigned NOT NULL,
  `option_text` text NOT NULL,
  `sort` int(11) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`question_option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `thematic`
--

DROP TABLE IF EXISTS `thematic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thematic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `display_order` int(10) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `session_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `active` (`active`,`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `thematic_advance`
--

DROP TABLE IF EXISTS `thematic_advance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thematic_advance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thematic_id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL DEFAULT '0',
  `content` text,
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `duration` int(11) NOT NULL DEFAULT '0',
  `done_advance` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `thematic_id` (`thematic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `thematic_plan`
--

DROP TABLE IF EXISTS `thematic_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thematic_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thematic_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `description_type` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `thematic_id` (`thematic_id`,`description_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tool`
--

DROP TABLE IF EXISTS `tool`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tool` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `visibility` tinyint(3) unsigned DEFAULT '0',
  `admin` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `added_tool` tinyint(3) unsigned DEFAULT '1',
  `target` enum('_self','_blank') NOT NULL DEFAULT '_self',
  `category` enum('authoring','interaction','admin') NOT NULL DEFAULT 'authoring',
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tool_intro`
--

DROP TABLE IF EXISTS `tool_intro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tool_intro` (
  `id` varchar(50) NOT NULL,
  `intro_text` text NOT NULL,
  `session_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userinfo_content`
--

DROP TABLE IF EXISTS `userinfo_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userinfo_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `definition_id` int(10) unsigned NOT NULL,
  `editor_ip` varchar(39) DEFAULT NULL,
  `edition_time` datetime DEFAULT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userinfo_def`
--

DROP TABLE IF EXISTS `userinfo_def`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userinfo_def` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(80) NOT NULL DEFAULT '',
  `comment` text,
  `line_count` tinyint(3) unsigned NOT NULL DEFAULT '5',
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wiki`
--

DROP TABLE IF EXISTS `wiki`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL DEFAULT '0',
  `reflink` varchar(255) NOT NULL DEFAULT 'index',
  `title` varchar(255) NOT NULL,
  `content` mediumtext NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `group_id` int(11) DEFAULT NULL,
  `dtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `addlock` int(11) NOT NULL DEFAULT '1',
  `editlock` int(11) NOT NULL DEFAULT '0',
  `visibility` int(11) NOT NULL DEFAULT '1',
  `addlock_disc` int(11) NOT NULL DEFAULT '1',
  `visibility_disc` int(11) NOT NULL DEFAULT '1',
  `ratinglock_disc` int(11) NOT NULL DEFAULT '1',
  `assignment` int(11) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `progress` text NOT NULL,
  `score` int(11) DEFAULT '0',
  `version` int(11) DEFAULT NULL,
  `is_editing` int(11) NOT NULL DEFAULT '0',
  `time_edit` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `hits` int(11) DEFAULT '0',
  `linksto` text NOT NULL,
  `tag` text NOT NULL,
  `user_ip` varchar(39) NOT NULL,
  `session_id` smallint(6) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reflink` (`reflink`),
  KEY `group_id` (`group_id`),
  KEY `page_id` (`page_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wiki_conf`
--

DROP TABLE IF EXISTS `wiki_conf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_conf` (
  `page_id` int(11) NOT NULL DEFAULT '0',
  `task` text NOT NULL,
  `feedback1` text NOT NULL,
  `feedback2` text NOT NULL,
  `feedback3` text NOT NULL,
  `fprogress1` varchar(3) NOT NULL,
  `fprogress2` varchar(3) NOT NULL,
  `fprogress3` varchar(3) NOT NULL,
  `max_size` int(11) DEFAULT NULL,
  `max_text` int(11) DEFAULT NULL,
  `max_version` int(11) DEFAULT NULL,
  `startdate_assig` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddate_assig` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `delayedsubmit` int(11) NOT NULL DEFAULT '0',
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wiki_discuss`
--

DROP TABLE IF EXISTS `wiki_discuss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_discuss` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL DEFAULT '0',
  `userc_id` int(11) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `p_score` varchar(255) DEFAULT NULL,
  `dtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wiki_mailcue`
--

DROP TABLE IF EXISTS `wiki_mailcue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_mailcue` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` text NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-03-27 11:30:07