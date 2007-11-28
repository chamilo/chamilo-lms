-- MySQL dump 10.9
--
-- Host: localhost    Database: dokeos_main
-- ------------------------------------------------------
-- Server version	4.1.14

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table admin
--

DROP TABLE IF EXISTS admin;
CREATE TABLE admin (
  user_id int(10) unsigned NOT NULL default '0',
  UNIQUE KEY user_id (user_id)
);

--
-- Dumping data for table admin
--


/*!40000 ALTER TABLE admin DISABLE KEYS */;
LOCK TABLES admin WRITE;
INSERT INTO admin VALUES (1);
UNLOCK TABLES;
/*!40000 ALTER TABLE admin ENABLE KEYS */;

--
-- Table structure for table class
--

DROP TABLE IF EXISTS class;
CREATE TABLE class (
  id mediumint(8) unsigned NOT NULL auto_increment,
  code varchar(40) default '',
  name text NOT NULL,
  PRIMARY KEY  (id)
);

--
-- Dumping data for table class
--


/*!40000 ALTER TABLE class DISABLE KEYS */;
LOCK TABLES class WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE class ENABLE KEYS */;

--
-- Table structure for table class_user
--

DROP TABLE IF EXISTS class_user;
CREATE TABLE class_user (
  class_id mediumint(8) unsigned NOT NULL default '0',
  user_id int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (class_id,user_id)
);

--
-- Dumping data for table class_user
--


/*!40000 ALTER TABLE class_user DISABLE KEYS */;
LOCK TABLES class_user WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE class_user ENABLE KEYS */;

--
-- Table structure for table course
--

DROP TABLE IF EXISTS course;
CREATE TABLE course (
  code varchar(40) NOT NULL,
  directory varchar(40) default NULL,
  db_name varchar(40) default NULL,
  course_language varchar(20) default NULL,
  title varchar(250) default NULL,
  description text,
  category_code varchar(40) default NULL,
  visibility tinyint(4) default '0',
  show_score int(11) NOT NULL default '1',
  tutor_name varchar(200) default NULL,
  visual_code varchar(40) default NULL,
  department_name varchar(30) default NULL,
  department_url varchar(180) default NULL,
  disk_quota int(10) unsigned default NULL,
  last_visit datetime default NULL,
  last_edit datetime default NULL,
  creation_date datetime default NULL,
  expiration_date datetime default NULL,
  target_course_code varchar(40) default NULL,
  subscribe tinyint(4) NOT NULL default '1',
  unsubscribe tinyint(4) NOT NULL default '1',
  registration_code varchar(255) NOT NULL default '',
  PRIMARY KEY  (code)
);

--
-- Dumping data for table course
--


/*!40000 ALTER TABLE course DISABLE KEYS */;
LOCK TABLES course WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE course ENABLE KEYS */;

--
-- Table structure for table course_category
--

DROP TABLE IF EXISTS course_category;
CREATE TABLE course_category (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  code varchar(40) NOT NULL default '',
  parent_id varchar(40) default NULL,
  tree_pos int(10) unsigned default NULL,
  children_count smallint(6) default NULL,
  auth_course_child enum('TRUE','FALSE') default 'TRUE',
  auth_cat_child enum('TRUE','FALSE') default 'TRUE',
  PRIMARY KEY  (id),
  UNIQUE KEY code (code),
  KEY parent_id (parent_id),
  KEY tree_pos (tree_pos)
);

--
-- Dumping data for table course_category
--


/*!40000 ALTER TABLE course_category DISABLE KEYS */;
LOCK TABLES course_category WRITE;
INSERT INTO course_category VALUES (1,'Language skills','LANG',NULL,1,0,'TRUE','TRUE'),(2,'PC Skills','PC',NULL,2,0,'TRUE','TRUE'),(3,'Projects','PROJ',NULL,3,0,'TRUE','TRUE');
UNLOCK TABLES;
/*!40000 ALTER TABLE course_category ENABLE KEYS */;

--
-- Table structure for table course_module
--

DROP TABLE IF EXISTS course_module;
CREATE TABLE course_module (
  id int(10) unsigned NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  link varchar(255) NOT NULL,
  image varchar(100) default NULL,
  row int(10) unsigned NOT NULL default '0',
  column int(10) unsigned NOT NULL default '0',
  position varchar(20) NOT NULL default 'basic',
  PRIMARY KEY  (id)
);

--
-- Dumping data for table course_module
--


/*!40000 ALTER TABLE course_module DISABLE KEYS */;
LOCK TABLES course_module WRITE;
INSERT INTO course_module VALUES
(1,'calendar_event','calendar/agenda.php','agenda.gif',1,1,'basic'),
(2,'link','link/link.php','links.gif',4,1,'basic'),
(3,'document','document/document.php','documents.gif',3,1,'basic'),
(4,'student_publication','work/work.php','works.gif',3,2,'basic'),
(5,'announcement','announcements/announcements.php','valves.gif',2,1,'basic'),
(6,'user','user/user.php','members.gif',2,3,'basic'),
(7,'forum','forum/index.php','forum.gif',1,2,'basic'),
(8,'quiz','exercice/exercice.php','quiz.gif',2,2,'basic'),
(9,'group','group/group.php','group.gif',3,3,'basic'),
(10,'course_description','course_description/','info.gif',1,3,'basic'),
(11,'chat','chat/chat.php','chat.gif',0,0,'external'),
(12,'dropbox','dropbox/index.php','dropbox.gif',4,2,'basic'),
(13,'tracking','tracking/courseLog.php','statistics.gif',1,3,'courseadmin'),
(14,'homepage_link','link/link.php?action=addlink','npage.gif',1,1,'courseadmin'),
(15,'course_setting','course_info/infocours.php','reference.gif',1,1,'courseadmin'),
(16,'External','','external.gif',0,0,'external'),
(17,'AddedLearnpath','','scormbuilder.gif',0,0,'external'),
(18,'conference','conference/index.php?type=conference','conf.gif',0,0,'external'),
(19,'conference','conference/index.php?type=classroom','conf.gif',0,0,'external'),
(20,'learnpath','newscorm/lp_controller.php','scorm.gif',5,1,'basic'),
(21,'blog','blog/blog.php','blog.gif',1,2,'basic'),
(22,'blog_management','blog/blog_admin.php','blog_admin.gif',1,2,'courseadmin'),
(23,'course_maintenance','course_info/maintenance.php','backup.gif',2,3,'courseadmin'),
(24,'survey','survey/survey_list.php','survey.gif',2,1,'courseadmin');
UNLOCK TABLES;
/*!40000 ALTER TABLE course_module ENABLE KEYS */;

--
-- Table structure for table course_rel_class
--

DROP TABLE IF EXISTS course_rel_class;
CREATE TABLE course_rel_class (
  course_code char(40) NOT NULL,
  class_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (course_code,class_id)
);

--
-- Dumping data for table course_rel_class
--


/*!40000 ALTER TABLE course_rel_class DISABLE KEYS */;
LOCK TABLES course_rel_class WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE course_rel_class ENABLE KEYS */;

--
-- Table structure for table course_rel_user
--

DROP TABLE IF EXISTS course_rel_user;
CREATE TABLE course_rel_user (
  course_code varchar(40) NOT NULL,
  user_id int(10) unsigned NOT NULL default '0',
  status tinyint(4) NOT NULL default '5',
  role varchar(60) default NULL,
  group_id int(11) NOT NULL default '0',
  tutor_id int(10) unsigned NOT NULL default '0',
  sort int(11) default NULL,
  user_course_cat int(11) default '0',
  PRIMARY KEY  (course_code,user_id)
);

--
-- Dumping data for table course_rel_user
--


/*!40000 ALTER TABLE course_rel_user DISABLE KEYS */;
LOCK TABLES course_rel_user WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE course_rel_user ENABLE KEYS */;

--
-- Table structure for table language
--

DROP TABLE IF EXISTS language;
CREATE TABLE language (
  id tinyint(3) unsigned NOT NULL auto_increment,
  original_name varchar(255) default NULL,
  english_name varchar(255) default NULL,
  isocode varchar(10) default NULL,
  dokeos_folder varchar(250) default NULL,
  available tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (id)
);

--
-- Dumping data for table language
--


/*!40000 ALTER TABLE language DISABLE KEYS */;
LOCK TABLES language WRITE;
INSERT INTO language VALUES
(1,'Arabija (el)','arabic','ar','arabic',0),
(2,'Portugu&ecirc;s (Brazil)','brazilian','pt-BR','brazilian',1),
(3,'Balgarski','bulgarian','bg','bulgarian',0),
(4,'Catal&agrave;','catalan','ca','catalan',0),
(5,'Hrvatski','croatian','hr','croatian',0),
(6,'Dansk','danish','da','danish',0),
(7,'Nederlands','dutch','nl','dutch',1),
(8,'English','english','en','english',1),
(9,'Suomi','finnish','fi','finnish',0),
(10,'Fran&ccedil;ais','french','fr','french',1),
(11,'Galego','galician','gl','galician',0),
(12,'Deutsch','german','de','german',1),
(13,'Ellinika','greek','el','greek',0),
(14,'Magyar','hungarian','hu','hungarian',1),
(15,'Indonesia (Bahasa I.)','indonesian','id','indonesian',1),
(16,'Italiano','italian','it','italian',1),
(17,'Nihongo','japanese','ja','japanese',0),
(18,'Melayu (Bahasa M.)','malay','ms','malay',0),
(19,'Polski','polish','pl','polish',0),
(20,'Portugu&ecirc;s (Portugal)','portuguese','pt','portuguese',1),
(21,'Russkij','russian','ru','russian',0),
(22,'Chinese (simplified)','simpl_chinese','zh','simpl_chinese',0),
(23,'Slovenscina','slovenian','sl','slovenian',1),
(24,'Espa&ntilde;ol','spanish','es','spanish',1),
(25,'Svenska','swedish','sv','swedish',0),
(26,'Thai','thai','th','thai',0),
(27,'T&uuml;rk&ccedil;e','turkce','tr','turkce',0),
(28,'Vi&ecirc;t (Ti&ecirc;ng V.)','vietnamese','vi','vietnamese',0),
(29,'Norsk','norwegian','no','norwegian',0),
(30,'Farsi','persian','fa','persian',0),
(31,'Srpski','serbian','sr','serbian',0),
(32,'Bosanski','bosnian',NULL,'bosnian',1),
(33,'Swahili (kiSw.)','swahili','sw','swahili',0),
(34,'Esperanto','esperanto','eo','esperanto',0);
UNLOCK TABLES;
/*!40000 ALTER TABLE language ENABLE KEYS */;

--
-- Table structure for table php_session
--

DROP TABLE IF EXISTS php_session;
CREATE TABLE php_session (
  session_id varchar(32) NOT NULL default '',
  session_name varchar(10) NOT NULL default '',
  session_time int(11) NOT NULL default '0',
  session_start int(11) NOT NULL default '0',
  session_value text NOT NULL,
  PRIMARY KEY  (session_id)
);

--
-- Table structure for table session
--
DROP TABLE IF EXISTS session;
CREATE TABLE session (
  id smallint(5) unsigned NOT NULL auto_increment,
  id_coach int(10) unsigned NOT NULL default '0',
  name char(50) NOT NULL default '',
  nbr_courses smallint(5) unsigned NOT NULL default '0',
  nbr_users mediumint(8) unsigned NOT NULL default '0',
  nbr_classes mediumint(8) unsigned NOT NULL default '0',
  date_start date NOT NULL default '0000-00-00',
  date_end date NOT NULL default '0000-00-00',
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
);

-- --------------------------------------------------------

--
-- Table structure for table session_rel_course
--
DROP TABLE IF EXISTS session_rel_course;
CREATE TABLE session_rel_course (
  id_session smallint(5) unsigned NOT NULL default '0',
  course_code char(40) NOT NULL default '',
  id_coach int(10) unsigned NOT NULL default '0',
  nbr_users smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_session,course_code),
  KEY course_code (course_code)
);

-- --------------------------------------------------------

--
-- Table structure for table session_rel_course_rel_user
--
DROP TABLE IF EXISTS session_rel_course_rel_user;
CREATE TABLE session_rel_course_rel_user (
  id_session smallint(5) unsigned NOT NULL default '0',
  course_code char(40) NOT NULL default '',
  id_user int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_session,course_code,id_user),
  KEY id_user (id_user),
  KEY course_code (course_code)
);

-- --------------------------------------------------------

--
-- Table structure for table session_rel_user
--
DROP TABLE IF EXISTS session_rel_user;
CREATE TABLE session_rel_user (
  id_session mediumint(8) unsigned NOT NULL default '0',
  id_user mediumint(8) unsigned NOT NULL default '0',
  PRIMARY KEY  (id_session,id_user)
);


--
-- Table structure for table settings_current
--

DROP TABLE IF EXISTS settings_current;
CREATE TABLE settings_current (
  id int(10) unsigned NOT NULL auto_increment,
  variable varchar(255) default NULL,
  subkey varchar(255) default NULL,
  type varchar(255) default NULL,
  category varchar(255) default NULL,
  selected_value varchar(255) default NULL,
  title varchar(255) NOT NULL default '',
  comment varchar(255) default NULL,
  scope varchar(50) default NULL,
  subkeytext varchar(255) default NULL,
  UNIQUE KEY id (id)
);

--
-- Dumping data for table settings_current
--


/*!40000 ALTER TABLE settings_current DISABLE KEYS */;
LOCK TABLES settings_current WRITE;
INSERT INTO settings_current VALUES
(1,'Institution',NULL,'textfield','Platform','{ORGANISATIONNAME}','InstitutionTitle','InstitutionComment','platform',NULL),
(2,'InstitutionUrl',NULL,'textfield','Platform','{ORGANISATIONURL}','InstitutionUrlTitle','InstitutionUrlComment',NULL,NULL),
(3,'siteName',NULL,'textfield','Platform','{CAMPUSNAME}','SiteNameTitle','SiteNameComment',NULL,NULL),
(4,'emailAdministrator',NULL,'textfield','Platform','{ADMINEMAIL}','emailAdministratorTitle','emailAdministratorComment',NULL,NULL),
(5,'administratorSurname',NULL,'textfield','Platform','{ADMINLASTNAME}','administratorSurnameTitle','administratorSurnameComment',NULL,NULL),
(6,'administratorName',NULL,'textfield','Platform','{ADMINFIRSTNAME}','administratorNameTitle','administratorNameComment',NULL,NULL),
(7,'show_administrator_data',NULL,'radio','Platform','true','ShowAdministratorDataTitle','ShowAdministratorDataComment',NULL,NULL),
(8,'homepage_view',NULL,'radio','Course','activity','HomepageViewTitle','HomepageViewComment',NULL,NULL),
(9,'show_toolshortcuts',NULL,'radio','Course','false','ShowToolShortcutsTitle','ShowToolShortcutsComment',NULL,NULL),
(11,'allow_group_categories',NULL,'radio','Course','false','AllowGroupCategories','AllowGroupCategoriesComment',NULL,NULL),
(12,'server_type',NULL,'radio','Platform','production','ServerStatusTitle','ServerStatusComment',NULL,NULL),
(13,'platformLanguage',NULL,'link','Languages','{PLATFORMLANGUAGE}','PlatformLanguageTitle','PlatformLanguageComment',NULL,NULL),
(14,'showonline','world','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineWorld'),
(15,'showonline','users','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineUsers'),
(16,'showonline','course','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineCourse'),
(17,'profile','name','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'name'),
(18,'profile','officialcode','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'officialcode'),
(19,'profile','email','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Email'),
(20,'profile','picture','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPicture'),
(21,'profile','login','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Login'),
(22,'profile','password','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPassword'),
(23,'profile','language','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'Language'),
(24,'default_document_quotum',NULL,'textfield','Course','50000000','DefaultDocumentQuotumTitle','DefaultDocumentQuotumComment',NULL,NULL),
(25,'registration','officialcode','checkbox','User','false','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'OfficialCode'),
(26,'registration','email','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Email'),
(27,'registration','language','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Language'),
(28,'default_group_quotum',NULL,'textfield','Course','5000000','DefaultGroupQuotumTitle','DefaultGroupQuotumComment',NULL,NULL),
(29,'allow_registration',NULL,'radio','Platform','{ALLOWSELFREGISTRATION}','AllowRegistrationTitle','AllowRegistrationComment',NULL,NULL),
(30,'allow_registration_as_teacher',NULL,'radio','Platform','{ALLOWTEACHERSELFREGISTRATION}','AllowRegistrationAsTeacherTitle','AllowRegistrationAsTeacherComment',NULL,NULL),
(31,'allow_lostpassword',NULL,'radio','Platform','true','AllowLostPasswordTitle','AllowLostPasswordComment',NULL,NULL),
(32,'allow_user_headings',NULL,'radio','Course','false','AllowUserHeadings','AllowUserHeadingsComment',NULL,NULL),
(33,'course_create_active_tools','course_description','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseDescription'),
(34,'course_create_active_tools','agenda','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Agenda'),
(35,'course_create_active_tools','documents','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Documents'),
(36,'course_create_active_tools','learning_path','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'LearningPath'),
(37,'course_create_active_tools','links','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Links'),
(38,'course_create_active_tools','announcements','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Announcements'),
(39,'course_create_active_tools','forums','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Forums'),
(40,'course_create_active_tools','dropbox','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Dropbox'),
(41,'course_create_active_tools','quiz','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Quiz'),
(42,'course_create_active_tools','users','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Users'),
(43,'course_create_active_tools','groups','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Groups'),
(44,'course_create_active_tools','chat','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Chat'),
(45,'course_create_active_tools','online_conference','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'OnlineConference'),
(46,'course_create_active_tools','student_publications','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'StudentPublications'),
(47,'allow_personal_agenda',NULL,'radio','User','false','AllowPersonalAgendaTitle','AllowPersonalAgendaComment',NULL,NULL),
(48,'display_coursecode_in_courselist',NULL,'radio','Platform','true','DisplayCourseCodeInCourselistTitle','DisplayCourseCodeInCourselistComment',NULL,NULL),
(49,'display_teacher_in_courselist',NULL,'radio','Platform','true','DisplayTeacherInCourselistTitle','DisplayTeacherInCourselistComment',NULL,NULL),
(50,'use_document_title',NULL,'radio','Tools','false','UseDocumentTitleTitle','UseDocumentTitleComment',NULL,NULL),
(51,'permanently_remove_deleted_files',NULL,'radio','Tools','false','PermanentlyRemoveFilesTitle','PermanentlyRemoveFilesComment',NULL,NULL),
(52,'dropbox_allow_overwrite',NULL,'radio','Tools','true','DropboxAllowOverwriteTitle','DropboxAllowOverwriteComment',NULL,NULL),
(53,'dropbox_max_filesize',NULL,'textfield','Tools','100000000','DropboxMaxFilesizeTitle','DropboxMaxFilesizeComment',NULL,NULL),
(54,'dropbox_allow_just_upload',NULL,'radio','Tools','true','DropboxAllowJustUploadTitle','DropboxAllowJustUploadComment',NULL,NULL),
(55,'dropbox_allow_student_to_student',NULL,'radio','Tools','true','DropboxAllowStudentToStudentTitle','DropboxAllowStudentToStudentComment',NULL,NULL),
(56,'dropbox_allow_group',NULL,'radio','Tools','true','DropboxAllowGroupTitle','DropboxAllowGroupComment',NULL,NULL),
(57,'dropbox_allow_mailing',NULL,'radio','Tools','false','DropboxAllowMailingTitle','DropboxAllowMailingComment',NULL,NULL),
(58,'administratorTelephone',NULL,'textfield','Platform','(000) 001 02 03','administratorTelephoneTitle','administratorTelephoneComment',NULL,NULL),
(59,'extended_profile',NULL,'radio','User','true','ExtendedProfileTitle','ExtendedProfileComment',NULL,NULL),
(60,'student_view_enabled',NULL,'radio','Platform','true','StudentViewEnabledTitle','StudentViewEnabledComment',NULL,NULL),
(61,'show_navigation_menu',NULL,'radio','Course','false','ShowNavigationMenuTitle','ShowNavigationMenuComment',NULL,NULL),
(62,'enable_tool_introduction',NULL,'radio','course','false','EnableToolIntroductionTitle','EnableToolIntroductionComment',NULL,NULL),
(63, 'page_after_login', NULL, 'radio','Platform','user_portal.php', 'PageAfterLoginTitle','PageAfterLoginComment', NULL, NULL),
(64, 'time_limit_whosonline', NULL, 'textfield','Platform','30', 'TimeLimitWhosonlineTitle','TimeLimitWhosonlineComment', NULL, NULL),
(65, 'breadcrumbs_course_homepage', NULL, 'radio','Course','course_title', 'BreadCrumbsCourseHomepageTitle','BreadCrumbsCourseHomepageComment', NULL, NULL),
(66, 'example_material_course_creation', NULL, 'radio','Platform','true', 'ExampleMaterialCourseCreationTitle','ExampleMaterialCourseCreationComment', NULL, NULL),
(67,'account_valid_duration',NULL, 'textfield','Platform','3660', 'AccountValidDurationTitle','AccountValidDurationComment', NULL, NULL),
(68, 'use_session_mode', NULL, 'radio','Platform','false', 'UseSessionModeTitle','UseSessionModeComment', NULL, NULL),
(69, 'allow_email_editor', NULL, 'radio', 'Tools', 'false', 'AllowEmailEditorTitle', 'AllowEmailEditorComment', NULL, NULL),
(70, 'registered', NULL, 'textfield', NULL, 'false', NULL, NULL, NULL, NULL),
(71, 'donotlistcampus', NULL, 'textfield', NULL, 'false', NULL, NULL, NULL, NULL),
(72, 'show_email_addresses', NULL,'radio','Platform','false','ShowEmailAddresses','ShowEmailAddressesComment',NULL,NULL),
(73,'profile','phone','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'phone'),
(75, 'service_visio', 'active', 'radio',NULL,'false', 'visio_actived','', NULL, NULL),
(76, 'service_visio', 'visioconference_url', 'textfield',NULL,'', 'visio_url','', NULL, NULL),
(77, 'service_visio', 'visioclassroom_url', 'textfield',NULL,'', 'visio_url','', NULL, NULL),
(78, 'service_ppt2lp', 'active', 'radio',NULL,'false', 'ppt2lp_actived','', NULL, NULL),
(79, 'service_ppt2lp', 'host', 'textfield', NULL, NULL, 'Host', NULL, NULL, NULL),
(80, 'service_ppt2lp', 'user', 'textfield', NULL, NULL, 'UserOnHost', NULL, NULL, NULL),
(81, 'service_ppt2lp', 'ftp_password', 'textfield', NULL, NULL, 'FtpPassword', NULL, NULL, NULL),
(82, 'service_ppt2lp', 'path_to_lzx', 'textfield', NULL, NULL, '', NULL, NULL, NULL),
(84, 'service_ppt2lp', 'size', 'radio', NULL, '720x540', '', NULL, NULL, NULL),
(85, 'wcag_anysurfer_public_pages', NULL, 'radio','Platform','false','PublicPagesComplyToWAITitle','PublicPagesComplyToWAIComment', NULL, NULL),
(86, 'stylesheets', NULL, 'textfield','stylesheets','default_with_tabs','',NULL, NULL, NULL),
(87, 'upload_extensions_list_type', NULL, 'radio', 'Security', 'blacklist', 'UploadExtensionsListType', 'UploadExtensionsListTypeComment', NULL, NULL),
(88, 'upload_extensions_blacklist', NULL, 'textfield', 'Security', '', 'UploadExtensionsBlacklist', 'UploadExtensionsBlacklistComment', NULL, NULL),
(89, 'upload_extensions_whitelist', NULL, 'textfield', 'Security', 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg', 'UploadExtensionsWhitelist', 'UploadExtensionsWhitelistComment', NULL, NULL),
(90, 'upload_extensions_skip', NULL, 'radio', 'Security', 'true', 'UploadExtensionsSkip', 'UploadExtensionsSkipComment', NULL, NULL),
(91, 'upload_extensions_replace_by', NULL, 'textfield', 'Security', 'dangerous', 'UploadExtensionsReplaceBy', 'UploadExtensionsReplaceByComment', NULL, NULL),
(92, 'service_visio', 'visio_rtmp_host_local', 'textfield',NULL,'', 'VisioHostLocal','', NULL, NULL),
(93, 'service_visio', 'visio_is_web_rtmp', 'radio',NULL,'false', 'VisioRTMPIsWeb','', NULL, NULL),
(94, 'service_visio', 'visio_rtmp_port', 'textfield',NULL,'1935', 'VisioRTMPPort','', NULL, NULL),
(95, 'service_visio', 'visio_rtmp_tunnel_port', 'textfield',NULL,'80', 'VisioRTMPTunnelPort','', NULL, NULL),
(96, 'show_number_of_courses', NULL, 'radio','Platform','false', 'ShowNumberOfCourses','ShowNumberOfCoursesComment', NULL, NULL),
(97, 'show_empty_course_categories', NULL, 'radio','Platform','true', 'ShowEmptyCourseCategories','ShowEmptyCourseCategoriesComment', NULL, NULL),
(98, 'show_back_link_on_top_of_tree', NULL, 'radio','Platform','false', 'ShowBackLinkOnTopOfCourseTree','ShowBackLinkOnTopOfCourseTreeComment', NULL, NULL),
(99, 'show_different_course_language', NULL, 'radio','Platform','true', 'ShowDifferentCourseLanguage','ShowDifferentCourseLanguageComment', NULL, NULL),
(100, 'split_users_upload_directory', NULL, 'radio','Tuning','false', 'SplitUsersUploadDirectory','SplitUsersUploadDirectoryComment', NULL, NULL),
(101, 'hide_dltt_markup', NULL, 'radio','Platform','true', 'HideDLTTMarkup','HideDLTTMarkupComment', NULL, NULL),
(102, 'display_categories_on_homepage',NULL,'radio','Platform','false','DisplayCategoriesOnHomepageTitle','DisplayCategoriesOnHomepageComment',NULL,NULL),
(103, 'permissions_for_new_directories', NULL, 'textfield', 'Security', '0777', 'PermissionsForNewDirs', 'PermissionsForNewDirsComment', NULL, NULL),
(104, 'permissions_for_new_files', NULL, 'textfield', 'Security', '0666', 'PermissionsForNewFiles', 'PermissionsForNewFilesComment', NULL, NULL),
(105, 'show_tabs', 'campus_homepage', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsCampusHomepage'),
(106, 'show_tabs', 'my_courses', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyCourses'),
(107, 'show_tabs', 'reporting', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsReporting'),
(108, 'show_tabs', 'platform_administration', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsPlatformAdministration'),
(109, 'show_tabs', 'my_agenda', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyAgenda'), 
(110, 'show_tabs', 'my_profile', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyProfile'),
(111, 'default_forum_view', NULL, 'radio', 'Course', 'flat', 'DefaultForumViewTitle','DefaultForumViewComment',NULL,NULL),
(112, 'platform_charset',NULL,'textfield','Platform','iso-8859-15','PlatformCharsetTitle','PlatformCharsetComment','platform',NULL),
(113,'noreply_email_address', '', 'textfield', 'Platform', '', 
'NoReplyEmailAddress', 'NoReplyEmailAddressComment', NULL, NULL),
(114,'survey_email_sender_noreply', '', 'radio', 'Course', 'coach', 
'SurveyEmailSenderNoReply', 'SurveyEmailSenderNoReplyComment', NULL, NULL);


UNLOCK TABLES;
/*!40000 ALTER TABLE settings_current ENABLE KEYS */;

--
-- Table structure for table settings_options
--

DROP TABLE IF EXISTS settings_options;
CREATE TABLE settings_options (
  id int(10) unsigned NOT NULL auto_increment,
  variable varchar(255) default NULL,
  value varchar(255) default NULL,
  display_text varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY id (id)
);

--
-- Dumping data for table settings_options
--


/*!40000 ALTER TABLE settings_options DISABLE KEYS */;
LOCK TABLES settings_options WRITE;
INSERT INTO settings_options VALUES
(11,'show_administrator_data','true','Yes'),
(12,'show_administrator_data','false','No'),
(13,'homepage_view','activity','HomepageViewActivity'),
(14,'homepage_view','2column','HomepageView2column'),
(15,'homepage_view','3column','HomepageView3column'),
(16,'show_toolshortcuts','true','Yes'),
(17,'show_toolshortcuts','false','No'),
(20,'allow_group_categories','true','Yes'),
(21,'allow_group_categories','false','No'),
(22,'server_type','production','ProductionServer'),
(23,'server_type','test','TestServer'),
(26,'allow_name_change','true','Yes'),
(27,'allow_name_change','false','No'),
(28,'allow_officialcode_change','true','Yes'),
(29,'allow_officialcode_change','false','No'),
(30,'allow_registration','true','Yes'),
(31,'allow_registration','false','No'),
(32,'allow_registration','approval','AfterApproval'),
(33,'allow_registration_as_teacher','true','Yes'),
(34,'allow_registration_as_teacher','false','No'),
(35,'allow_lostpassword','true','Yes'),
(36,'allow_lostpassword','false','No'),
(37,'allow_user_headings','true','Yes'),
(38,'allow_user_headings','false','No'),
(39,'allow_personal_agenda','true','Yes'),
(40,'allow_personal_agenda','false','No'),
(41,'display_coursecode_in_courselist','true','Yes'),
(42,'display_coursecode_in_courselist','false','No'),
(43,'display_teacher_in_courselist','true','Yes'),
(44,'display_teacher_in_courselist','false','No'),
(45,'use_document_title','true','Yes'),
(46,'use_document_title','false','No'),
(47,'permanently_remove_deleted_files','true','Yes'),
(48,'permanently_remove_deleted_files','false','No'),
(49,'dropbox_allow_overwrite','true','Yes'),
(50,'dropbox_allow_overwrite','false','No'),
(51,'dropbox_allow_just_upload','true','Yes'),
(52,'dropbox_allow_just_upload','false','No'),
(53,'dropbox_allow_student_to_student','true','Yes'),
(54,'dropbox_allow_student_to_student','false','No'),
(55,'dropbox_allow_group','true','Yes'),
(56,'dropbox_allow_group','false','No'),
(57,'dropbox_allow_mailing','true','Yes'),
(58,'dropbox_allow_mailing','false','No'),
(59,'extended_profile','true','Yes'),
(60,'extended_profile','false','No'),
(61,'student_view_enabled','true','Yes'),
(62,'student_view_enabled','false','No'),
(63,'show_navigation_menu','false','No'),
(64,'show_navigation_menu','icons','IconsOnly'),
(65,'show_navigation_menu','text','TextOnly'),
(66,'show_navigation_menu','iconstext','IconsText'),
(67,'enable_tool_introduction','true','Yes'),
(68,'enable_tool_introduction','false','No'),
(69, 'page_after_login', 'index.php', 'CampusHomepage'),
(70, 'page_after_login', 'user_portal.php', 'MyCourses'),
(71,'breadcrumbs_course_homepage', 'get_lang', 'CourseHomepage'),
(72,'breadcrumbs_course_homepage', 'course_code', 'CourseCode'),
(73,'breadcrumbs_course_homepage', 'course_title', 'CourseTitle'),
(74,'example_material_course_creation', 'true', 'Yes'),
(75,'example_material_course_creation', 'false', 'No'),
(76,'use_session_mode', 'true', 'Yes'),
(77,'use_session_mode', 'false', 'No'),
(78,'allow_email_editor', 'true' ,'Yes'),
(79,'allow_email_editor', 'false', 'No'),
(80,'show_email_addresses','true','Yes'),
(81,'show_email_addresses','false','No'),
(82,'wcag_anysurfer_public_pages', 'true', 'Yes'),
(83,'wcag_anysurfer_public_pages', 'false', 'No'),
(84, 'upload_extensions_list_type', 'blacklist', 'Blacklist'),
(85, 'upload_extensions_list_type', 'whitelist', 'Whitelist'),
(86, 'upload_extensions_skip', 'true', 'Remove'),
(87, 'upload_extensions_skip', 'false', 'Rename'),
(88, 'visio_rtmp_host_local', 'true', 'Web'),
(89, 'visio_rtmp_host_local', 'false', 'Not web'), 
(90, 'show_number_of_courses', 'true', 'Yes'),
(91, 'show_number_of_courses', 'false', 'No'),
(92, 'show_empty_course_categories', 'true', 'Yes'),
(93, 'show_empty_course_categories', 'false', 'No'),
(94, 'show_back_link_on_top_of_tree', 'true', 'Yes'),
(95, 'show_back_link_on_top_of_tree', 'false', 'No'),
(96, 'show_different_course_language', 'true', 'Yes'),
(97, 'show_different_course_language', 'false', 'No'), 
(98, 'split_users_upload_directory', 'true', 'Yes'),
(99, 'split_users_upload_directory', 'false', 'No'),
(100, 'hide_dltt_markup', 'false', 'No'),
(101, 'hide_dltt_markup', 'true', 'Yes'),
(102, 'display_categories_on_homepage','true','Yes'),
(103, 'display_categories_on_homepage','false','No'),
(104, 'default_forum_view', 'flat', 'Flat'),
(105, 'default_forum_view', 'threaded', 'Threaded'),
(106, 'default_forum_view', 'nested', 'Nested'),
(107, 'survey_email_sender_noreply', 'coach', 'CourseCoachEmailSender'),
(108, 'survey_email_sender_noreply', 'noreply', 'NoReplyEmailSender');


UNLOCK TABLES;




/*!40000 ALTER TABLE settings_options ENABLE KEYS */;


--
-- Table structure for table sys_announcement
--

DROP TABLE IF EXISTS sys_announcement;
CREATE TABLE sys_announcement (
  id int(10) unsigned NOT NULL auto_increment,
  date_start datetime NOT NULL default '0000-00-00 00:00:00',
  date_end datetime NOT NULL default '0000-00-00 00:00:00',
  visible_teacher tinyint NOT NULL default 0,
  visible_student tinyint NOT NULL default 0,
  visible_guest tinyint NOT NULL default 0,
  title varchar(250) NOT NULL default '',
  content text NOT NULL,
  lang varchar(70) NULL default NULL,
  PRIMARY KEY  (id)
);

--
-- Dumping data for table sys_announcement
--


/*!40000 ALTER TABLE sys_announcement DISABLE KEYS */;
LOCK TABLES sys_announcement WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE sys_announcement ENABLE KEYS */;

--
-- Table structure for table user
--

DROP TABLE IF EXISTS user;
CREATE TABLE user (
  user_id int(10) unsigned NOT NULL auto_increment,
  lastname varchar(60) default NULL,
  firstname varchar(60) default NULL,
  username varchar(20) NOT NULL default '',
  password varchar(50) NOT NULL default '',
  auth_source varchar(50) default 'platform',
  email varchar(100) default NULL,
  status tinyint(4) NOT NULL default '5',
  official_code varchar(40) default NULL,
  phone varchar(30) default NULL,
  picture_uri varchar(250) default NULL,
  creator_id int(10) unsigned default NULL,
  competences text,
  diplomas text,
  openarea text,
  teach text,
  productions varchar(250) default NULL,
  chatcall_user_id int(10) unsigned NOT NULL default '0',
  chatcall_date datetime NOT NULL default '0000-00-00 00:00:00',
  chatcall_text varchar(50) NOT NULL default '',
  language varchar(40) default NULL,
  registration_date datetime NOT NULL default '0000-00-00 00:00:00',
  expiration_date datetime NOT NULL default '0000-00-00 00:00:00',
  active tinyint unsigned NOT NULL default 1,
  PRIMARY KEY  (user_id),
  UNIQUE KEY username (username)
);
ALTER TABLE user ADD INDEX (status);

--
-- Dumping data for table user
--


/*!40000 ALTER TABLE user DISABLE KEYS */;
LOCK TABLES user WRITE;
INSERT INTO user VALUES (1,'{ADMINLASTNAME}','{ADMINFIRSTNAME}','{ADMINLOGIN}','{ADMINPASSWORD}','{PLATFORM_AUTH_SOURCE}','{ADMINEMAIL}',1,'ADMIN',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'0000-00-00 00:00:00','',NULL,NOW(),'0000-00-00 00:00:00','1');
-- Insert anonymous user
INSERT INTO user(lastname, firstname, username, password, auth_source, email, status, official_code, creator_id, registration_date, expiration_date,active) VALUES ('Anonymous', 'Joe', '', '', 'platform', 'anonymous@localhost', 6, 'anonymous', 1, NOW(), '0000-00-00 00:00:00', 1);
UNLOCK TABLES;
/*!40000 ALTER TABLE user ENABLE KEYS */;

-- 
-- Table structure for shared_survey
-- 

CREATE TABLE shared_survey (
  survey_id int(10) unsigned NOT NULL auto_increment,
  code varchar(20) default NULL,
  title text default NULL,
  subtitle text default NULL,
  author varchar(250) default NULL,
  lang varchar(20) default NULL,
  template varchar(20) default NULL,
  intro text,
  surveythanks text,
  creation_date datetime NOT NULL default '0000-00-00 00:00:00',
  course_code varchar(40) NOT NULL default '',
  PRIMARY KEY  (survey_id),
  UNIQUE KEY id (survey_id)
);

-- --------------------------------------------------------

-- 
-- Table structure for shared_survey_question
-- 

CREATE TABLE shared_survey_question (
  question_id int(11) NOT NULL auto_increment,
  survey_id int(11) NOT NULL default '0',
  survey_question text NOT NULL,
  survey_question_comment text NOT NULL,
  type varchar(250) NOT NULL default '',
  display varchar(10) NOT NULL default '',
  sort int(11) NOT NULL default '0',
  code varchar(40) NOT NULL default '',
  max_value int(11) NOT NULL,
  PRIMARY KEY  (question_id)
);

-- --------------------------------------------------------

-- 
-- Table structure for shared_survey_question_option
-- 

CREATE TABLE shared_survey_question_option (
  question_option_id int(11) NOT NULL auto_increment,
  question_id int(11) NOT NULL default '0',
  survey_id int(11) NOT NULL default '0',
  option_text text NOT NULL,
  sort int(11) NOT NULL default '0',
  PRIMARY KEY  (question_option_id)
);


-- --------------------------------------------------------

-- 
-- Table structure for templates (User's FCKEditor templates)
-- 

CREATE TABLE templates (
  id int(11) NOT NULL auto_increment,
  title varchar(100) NOT NULL,
  description varchar(250) NOT NULL,
  course_code varchar(40) NOT NULL,
  user_id int(11) NOT NULL,
  ref_doc int(11) NOT NULL,
  PRIMARY KEY  (id)
);
