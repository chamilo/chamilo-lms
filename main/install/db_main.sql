-- MySQL dump 10.9
--
-- Host: localhost    Database: chamilo_main
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
-- Table structure for table user
--

DROP TABLE IF EXISTS user;
CREATE TABLE IF NOT EXISTS user (
  user_id int unsigned NOT NULL auto_increment,
  lastname varchar(60) default NULL,
  firstname varchar(60) default NULL,
  username varchar(40) NOT NULL default '',
  password varchar(50) NOT NULL default '',
  auth_source varchar(50) default 'platform',
  email varchar(100) default NULL,
  status tinyint NOT NULL default '5',
  official_code varchar(40) default NULL,
  phone varchar(30) default NULL,
  picture_uri varchar(250) default NULL,
  creator_id int unsigned default NULL,
  competences text,
  diplomas text,
  openarea text,
  teach text,
  productions varchar(250) default NULL,
  chatcall_user_id int unsigned NOT NULL default '0',
  chatcall_date datetime NOT NULL default '0000-00-00 00:00:00',
  chatcall_text varchar(50) NOT NULL default '',
  language varchar(40) default NULL,
  registration_date datetime NOT NULL default '0000-00-00 00:00:00',
  expiration_date datetime NOT NULL default '0000-00-00 00:00:00',
  active tinyint unsigned NOT NULL default 1,
  openid varchar(255) DEFAULT NULL,
  theme varchar(255) DEFAULT NULL,
  hr_dept_id smallint unsigned NOT NULL default 0,
  PRIMARY KEY  (user_id),
  UNIQUE KEY username (username)
);
ALTER TABLE user ADD INDEX (status);

--
-- Dumping data for table user
--

/*!40000 ALTER TABLE user DISABLE KEYS */;
LOCK TABLES user WRITE;
INSERT INTO user (lastname, firstname, username, password, auth_source, email, status, official_code,phone, creator_id, registration_date, expiration_date,active,openid,language) VALUES ('{ADMINLASTNAME}','{ADMINFIRSTNAME}','{ADMINLOGIN}','{ADMINPASSWORD}','{PLATFORM_AUTH_SOURCE}','{ADMINEMAIL}',1,'ADMIN','{ADMINPHONE}',1,NOW(),'0000-00-00 00:00:00','1',NULL,'{ADMINLANGUAGE}');
-- Insert anonymous user
INSERT INTO user (lastname, firstname, username, password, auth_source, email, status, official_code, creator_id, registration_date, expiration_date,active,openid,language) VALUES ('Anonymous', 'Joe', '', '', 'platform', 'anonymous@localhost', 6, 'anonymous', 1, NOW(), '0000-00-00 00:00:00', 1,NULL,'{ADMINLANGUAGE}');
UNLOCK TABLES;
/*!40000 ALTER TABLE user ENABLE KEYS */;

--
-- Table structure for table admin
--

DROP TABLE IF EXISTS admin;
CREATE TABLE IF NOT EXISTS admin (
  user_id int unsigned NOT NULL default '0',
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
CREATE TABLE IF NOT EXISTS class (
  id mediumint unsigned NOT NULL auto_increment,
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
CREATE TABLE IF NOT EXISTS class_user (
  class_id mediumint unsigned NOT NULL default '0',
  user_id int unsigned NOT NULL default '0',
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
CREATE TABLE IF NOT EXISTS course (
  id int auto_increment,
  code varchar(40) NOT NULL,
  directory varchar(40) default NULL,
  db_name varchar(40) default NULL,
  course_language varchar(20) default NULL,
  title varchar(250) default NULL,
  description text,
  category_code varchar(40) default NULL,
  visibility tinyint default '0',
  show_score int NOT NULL default '1',
  tutor_name varchar(200) default NULL,
  visual_code varchar(40) default NULL,
  department_name varchar(30) default NULL,
  department_url varchar(180) default NULL,
  disk_quota int unsigned default NULL,
  last_visit datetime default NULL,
  last_edit datetime default NULL,
  creation_date datetime default NULL,
  expiration_date datetime default NULL,
  target_course_code varchar(40) default NULL,
  subscribe tinyint NOT NULL default '1',
  unsubscribe tinyint NOT NULL default '1',
  registration_code varchar(255) NOT NULL default '',
  legal TEXT  NOT NULL,
  activate_legal INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY code (code)
);
ALTER TABLE course ADD INDEX idx_course_category_code (category_code);
ALTER TABLE course ADD INDEX idx_course_directory (directory(10));
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
CREATE TABLE IF NOT EXISTS course_category (
  id int unsigned NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  code varchar(40) NOT NULL default '',
  parent_id varchar(40) default NULL,
  tree_pos int unsigned default NULL,
  children_count smallint default NULL,
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
-- Table structure for table course_field
--

DROP TABLE IF EXISTS course_field;
CREATE TABLE IF NOT EXISTS course_field (
    id  int NOT NULL auto_increment,
    field_type int NOT NULL default 1,
    field_variable  varchar(64) NOT NULL,
    field_display_text  varchar(64),
    field_default_value text,
    field_order int,
    field_visible tinyint default 0,
    field_changeable tinyint default 0,
    field_filter tinyint default 0,
    tms DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);

--
-- Table structure for table course_field_values
--

DROP TABLE IF EXISTS course_field_values;
CREATE TABLE IF NOT EXISTS course_field_values(
    id  int NOT NULL auto_increment,
    course_code varchar(40) NOT NULL,
    field_id int NOT NULL,
    field_value text,
    tms DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);


--
-- Table structure for table course_module
--

DROP TABLE IF EXISTS course_module;
CREATE TABLE IF NOT EXISTS course_module (
  id int unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL,
  link varchar(255) NOT NULL,
  image varchar(100) default NULL,
  `row` int unsigned NOT NULL default '0',
  `column` int unsigned NOT NULL default '0',
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
(20,'learnpath','newscorm/lp_controller.php','scorms.gif',5,1,'basic'),
(21,'blog','blog/blog.php','blog.gif',1,2,'basic'),
(22,'blog_management','blog/blog_admin.php','blog_admin.gif',1,2,'courseadmin'),
(23,'course_maintenance','course_info/maintenance.php','backup.gif',2,3,'courseadmin'),
(24,'survey','survey/survey_list.php','survey.gif',2,1,'basic'),
(25,'wiki','wiki/index.php','wiki.gif',2,3,'basic'),
(26,'gradebook','gradebook/index.php','gradebook.gif',2,2,'basic'),
(27,'glossary','glossary/index.php','glossary.gif',2,1,'basic'),
(28,'notebook','notebook/index.php','notebook.gif',2,1,'basic'),
(29,'attendance','attendance/index.php','attendance.gif',2,1,'basic'),
(30,'course_progress','course_progress/index.php','course_progress.gif',2,1,'basic');
UNLOCK TABLES;
/*!40000 ALTER TABLE course_module ENABLE KEYS */;

--
-- Table structure for table course_rel_class
--

DROP TABLE IF EXISTS course_rel_class;
CREATE TABLE IF NOT EXISTS course_rel_class (
  course_code char(40) NOT NULL,
  class_id mediumint unsigned NOT NULL,
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
CREATE TABLE IF NOT EXISTS course_rel_user (
  course_code varchar(40) NOT NULL,
  user_id int unsigned NOT NULL default '0',
  status tinyint NOT NULL default '5',
  role varchar(60) default NULL,
  group_id int NOT NULL default '0',
  tutor_id int unsigned NOT NULL default '0',
  sort int default NULL,
  user_course_cat int default '0',
  relation_type int default 0,
  legal_agreement INTEGER DEFAULT 0,
  PRIMARY KEY  (course_code,user_id,relation_type)
);
ALTER TABLE course_rel_user ADD INDEX (user_id);

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
CREATE TABLE IF NOT EXISTS language (
  id tinyint unsigned NOT NULL auto_increment,
  original_name varchar(255) default NULL,
  english_name varchar(255) default NULL,
  isocode varchar(10) default NULL,
  dokeos_folder varchar(250) default NULL,
  available tinyint NOT NULL default 1,
  parent_id tinyint unsigned,
  PRIMARY KEY  (id)
);
ALTER TABLE language ADD INDEX idx_language_dokeos_folder(dokeos_folder);

--
-- Dumping data for table language
--


/*!40000 ALTER TABLE language DISABLE KEYS */;
LOCK TABLES language WRITE;
INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available) VALUES
('&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;','arabic','ar','arabic',0),
('Asturianu','asturian','ast','asturian',0),
('&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;','bulgarian','bg','bulgarian',1),
('Bosanski','bosnian','bs','bosnian',1),
('Catal&agrave;','catalan','ca','catalan',0),
('&#20013;&#25991;&#65288;&#31616;&#20307;&#65289;','simpl_chinese','zh','simpl_chinese',0),
('&#32321;&#39636;&#20013;&#25991;','trad_chinese','zh-TW','trad_chinese',0),
('&#268;esky','czech','cs','czech',0),
('Dansk','danish','da','danish',0),
('&#1583;&#1585;&#1740;','dari','prs','dari',0),
('Deutsch','german','de','german',1),
('&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;','greek','el','greek',0),
('English','english','en','english',1),
('Espa&ntilde;ol','spanish','es','spanish',1),
('Esperanto','esperanto','eo','esperanto',0),
('Euskara','euskera','eu','euskera',0),
('&#1601;&#1575;&#1585;&#1587;&#1740;','persian','fa','persian',0),
('Fran&ccedil;ais','french','fr','french',1),
('Furlan','friulian','fur','friulian',0),
('Galego','galician','gl','galician',0),
('&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;','georgian','ka','georgian',0),
('Hrvatski','croatian','hr','croatian',0),
('&#1506;&#1489;&#1512;&#1497;&#1514;','hebrew','he','hebrew',0),
('&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;','hindi','hi','hindi',0),
('Bahasa Indonesia','indonesian','id','indonesian',1),
('Italiano','italian','it','italian',1),
('&#54620;&#44397;&#50612;','korean','ko','korean',0),
('Latvie&scaron;u','latvian','lv','latvian',0),
('Lietuvi&#371;','lithuanian','lt','lithuanian',0),
('&#1052;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080;','macedonian','mk','macedonian',0),
('Magyar','hungarian','hu','hungarian',1),
('Bahasa Melayu','malay','ms','malay',0),
('Nederlands','dutch','nl','dutch',1),
('&#26085;&#26412;&#35486;','japanese','ja','japanese',0),
('Norsk','norwegian','no','norwegian',0),
('Occitan','occitan','oc','occitan',0),
('&#1662;&#1690;&#1578;&#1608;','pashto','ps','pashto',0),
('Polski','polish','pl','polish',0),
('Portugu&ecirc;s europeu','portuguese','pt','portuguese',1),
('Portugu&ecirc;s do Brasil','brazilian','pt-BR','brazilian',1),
('Rom&acirc;n&#259;','romanian','ro','romanian',0),
('Runasimi','quechua_cusco','qu','quechua_cusco',0),
('&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;','russian','ru','russian',0),
('Sloven&#269;ina','slovak','sk','slovak',0),
('Sloven&scaron;&#269;ina','slovenian','sl','slovenian',1),
('Srpski','serbian','sr','serbian',0),
('Suomi','finnish','fi','finnish',0),
('Svenska','swedish','sv','swedish',0),
('&#3652;&#3607;&#3618;','thai','th','thai',0),
('T&uuml;rk&ccedil;e','turkce','tr','turkce',0),
('&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;','ukrainian','uk','ukrainian',0),
('Ti&#7871;ng Vi&#7879;t','vietnamese','vi','vietnamese',0),
('Kiswahili','swahili','sw','swahili',0),
('Yor&ugrave;b&aacute;','yoruba','yo','yoruba',0);

-- The chosen during the installation platform language should be enabled.
UPDATE language SET available=1 WHERE dokeos_folder = '{PLATFORMLANGUAGE}';

UNLOCK TABLES;
/*!40000 ALTER TABLE language ENABLE KEYS */;

--
-- Table structure for table php_session
--

DROP TABLE IF EXISTS php_session;
CREATE TABLE IF NOT EXISTS php_session (
  session_id varchar(32) NOT NULL default '',
  session_name varchar(10) NOT NULL default '',
  session_time int NOT NULL default '0',
  session_start int NOT NULL default '0',
  session_value mediumtext NOT NULL,
  PRIMARY KEY  (session_id)
);

--
-- Table structure for table session
--
DROP TABLE IF EXISTS session;
CREATE TABLE IF NOT EXISTS session (
  id smallint unsigned NOT NULL auto_increment,
  id_coach int unsigned NOT NULL default '0',
  name char(50) NOT NULL default '',
  nbr_courses smallint unsigned NOT NULL default '0',
  nbr_users mediumint unsigned NOT NULL default '0',
  nbr_classes mediumint unsigned NOT NULL default '0',
  date_start date NOT NULL default '0000-00-00',
  date_end date NOT NULL default '0000-00-00',
  nb_days_access_before_beginning TINYINT UNSIGNED NULL default '0',
  nb_days_access_after_end TINYINT UNSIGNED NULL default '0',
  session_admin_id INT UNSIGNED NOT NULL,
  visibility int NOT NULL default 1,
  session_category_id int NOT NULL,
  promotion_id INT NOT NULL,
  PRIMARY KEY  (id),
  INDEX (session_admin_id),
  UNIQUE KEY name (name)
);

-- --------------------------------------------------------

--
-- Table structure for table session_rel_course
--
DROP TABLE IF EXISTS session_rel_course;
CREATE TABLE IF NOT EXISTS session_rel_course (
  id_session smallint unsigned NOT NULL default '0',
  course_code char(40) NOT NULL default '',
  nbr_users smallint unsigned NOT NULL default '0',
  PRIMARY KEY  (id_session,course_code),
  KEY course_code (course_code)
);

-- --------------------------------------------------------

--
-- Table structure for table session_rel_course_rel_user
--
DROP TABLE IF EXISTS session_rel_course_rel_user;
CREATE TABLE IF NOT EXISTS session_rel_course_rel_user (
  id_session smallint unsigned NOT NULL default '0',
  course_code char(40) NOT NULL default '',
  id_user int unsigned NOT NULL default '0',
  visibility int NOT NULL default 1,
  status int NOT NULL default 0,
  legal_agreement INTEGER DEFAULT 0, 
  PRIMARY KEY  (id_session,course_code,id_user),
  KEY id_user (id_user),
  KEY course_code (course_code)
);

-- --------------------------------------------------------

--
-- Table structure for table session_rel_user
--
DROP TABLE IF EXISTS session_rel_user;
CREATE TABLE IF NOT EXISTS session_rel_user (
  id_session mediumint unsigned NOT NULL default '0',
  id_user mediumint unsigned NOT NULL default '0',
  relation_type int default 0,
  PRIMARY KEY (id_session, id_user, relation_type)
);


DROP TABLE IF EXISTS session_field;
CREATE TABLE IF NOT EXISTS session_field (
    id  int NOT NULL auto_increment,
    field_type int NOT NULL default 1,
    field_variable  varchar(64) NOT NULL,
    field_display_text  varchar(64),
    field_default_value text,
    field_order int,
    field_visible tinyint default 0,
    field_changeable tinyint default 0,
    field_filter tinyint default 0,
    tms DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);

DROP TABLE IF EXISTS session_field_values;
CREATE TABLE IF NOT EXISTS session_field_values(
    id  int NOT NULL auto_increment,
    session_id int NOT NULL,
    field_id int NOT NULL,
    field_value text,
    tms DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);

--
-- Table structure for table settings_current
--

DROP TABLE IF EXISTS settings_current;
CREATE TABLE IF NOT EXISTS settings_current (
  id int unsigned NOT NULL auto_increment,
  variable varchar(255) default NULL,
  subkey varchar(255) default NULL,
  type varchar(255) default NULL,
  category varchar(255) default NULL,
  selected_value varchar(255) default NULL,
  title varchar(255) NOT NULL default '',
  comment varchar(255) default NULL,
  scope varchar(50) default NULL,
  subkeytext varchar(255) default NULL,
  access_url int unsigned not null default 1,
  access_url_changeable int unsigned not null default 0,
  PRIMARY KEY id (id),
  INDEX (access_url)
);

ALTER TABLE settings_current ADD UNIQUE unique_setting (variable(110), subkey(110), category(110), access_url);

--
-- Dumping data for table settings_current
--

/*!40000 ALTER TABLE settings_current DISABLE KEYS */;
LOCK TABLES settings_current WRITE;
INSERT INTO settings_current
(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('Institution',NULL,'textfield','Platform','{ORGANISATIONNAME}','InstitutionTitle','InstitutionComment','platform',NULL, 1),
('InstitutionUrl',NULL,'textfield','Platform','{ORGANISATIONURL}','InstitutionUrlTitle','InstitutionUrlComment',NULL,NULL, 1),
('siteName',NULL,'textfield','Platform','{CAMPUSNAME}','SiteNameTitle','SiteNameComment',NULL,NULL, 1),
('emailAdministrator',NULL,'textfield','Platform','{ADMINEMAIL}','emailAdministratorTitle','emailAdministratorComment',NULL,NULL, 1),
('administratorSurname',NULL,'textfield','Platform','{ADMINLASTNAME}','administratorSurnameTitle','administratorSurnameComment',NULL,NULL, 1),
('administratorName',NULL,'textfield','Platform','{ADMINFIRSTNAME}','administratorNameTitle','administratorNameComment',NULL,NULL, 1),
('show_administrator_data',NULL,'radio','Platform','true','ShowAdministratorDataTitle','ShowAdministratorDataComment',NULL,NULL, 1),
('show_tutor_data',NULL,'radio','Platform','true','ShowTutorDataTitle','ShowTutorDataComment',NULL,NULL, 1),
('show_teacher_data',NULL,'radio','Platform','true','ShowTeacherDataTitle','ShowTeacherDataComment',NULL,NULL, 1),
('homepage_view',NULL,'radio','Course','activity_big','HomepageViewTitle','HomepageViewComment',NULL,NULL, 1),
('show_toolshortcuts',NULL,'radio','Course','false','ShowToolShortcutsTitle','ShowToolShortcutsComment',NULL,NULL, 0),
('allow_group_categories',NULL,'radio','Course','false','AllowGroupCategories','AllowGroupCategoriesComment',NULL,NULL, 0),
('server_type',NULL,'radio','Platform','production','ServerStatusTitle','ServerStatusComment',NULL,NULL, 0),
('platformLanguage',NULL,'link','Languages','{PLATFORMLANGUAGE}','PlatformLanguageTitle','PlatformLanguageComment',NULL,NULL, 0),
('showonline','world','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineWorld', 0),
('showonline','users','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineUsers', 0),
('showonline','course','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineCourse', 0),
('profile','name','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'name', 0),
('profile','officialcode','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'officialcode', 0),
('profile','email','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Email', 0),
('profile','picture','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPicture', 0),
('profile','login','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Login', 0),
('profile','password','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPassword', 0),
('profile','language','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'Language', 0),
('default_document_quotum',NULL,'textfield','Course','100000000','DefaultDocumentQuotumTitle','DefaultDocumentQuotumComment',NULL,NULL, 0),
('registration','officialcode','checkbox','User','false','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'OfficialCode', 0),
('registration','email','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Email', 0),
('registration','language','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Language', 0),
('default_group_quotum',NULL,'textfield','Course','5000000','DefaultGroupQuotumTitle','DefaultGroupQuotumComment',NULL,NULL, 0),
('allow_registration',NULL,'radio','Platform','{ALLOWSELFREGISTRATION}','AllowRegistrationTitle','AllowRegistrationComment',NULL,NULL, 0),
('allow_registration_as_teacher',NULL,'radio','Platform','{ALLOWTEACHERSELFREGISTRATION}','AllowRegistrationAsTeacherTitle','AllowRegistrationAsTeacherComment',NULL,NULL, 0),
('allow_lostpassword',NULL,'radio','Platform','true','AllowLostPasswordTitle','AllowLostPasswordComment',NULL,NULL, 0),
('allow_user_headings',NULL,'radio','Course','false','AllowUserHeadings','AllowUserHeadingsComment',NULL,NULL, 0),
('course_create_active_tools','course_description','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseDescription', 0),
('course_create_active_tools','agenda','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Agenda', 0),
('course_create_active_tools','documents','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Documents', 0),
('course_create_active_tools','learning_path','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'LearningPath', 0),
('course_create_active_tools','links','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Links', 0),
('course_create_active_tools','announcements','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Announcements', 0),
('course_create_active_tools','forums','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Forums', 0),
('course_create_active_tools','dropbox','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Dropbox', 0),
('course_create_active_tools','quiz','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Quiz', 0),
('course_create_active_tools','users','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Users', 0),
('course_create_active_tools','groups','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Groups', 0),
('course_create_active_tools','chat','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Chat', 0),
('course_create_active_tools','online_conference','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'OnlineConference', 0),
('course_create_active_tools','student_publications','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'StudentPublications', 0),
('allow_personal_agenda',NULL,'radio','User','true','AllowPersonalAgendaTitle','AllowPersonalAgendaComment',NULL,NULL, 0),
('display_coursecode_in_courselist',NULL,'radio','Platform','false','DisplayCourseCodeInCourselistTitle','DisplayCourseCodeInCourselistComment',NULL,NULL, 0),
('display_teacher_in_courselist',NULL,'radio','Platform','true','DisplayTeacherInCourselistTitle','DisplayTeacherInCourselistComment',NULL,NULL, 0),
('use_document_title',NULL,'radio','Tools','true','UseDocumentTitleTitle','UseDocumentTitleComment',NULL,NULL, 0),
('permanently_remove_deleted_files',NULL,'radio','Tools','false','PermanentlyRemoveFilesTitle','PermanentlyRemoveFilesComment',NULL,NULL, 0),
('dropbox_allow_overwrite',NULL,'radio','Tools','true','DropboxAllowOverwriteTitle','DropboxAllowOverwriteComment',NULL,NULL, 0),
('dropbox_max_filesize',NULL,'textfield','Tools','100000000','DropboxMaxFilesizeTitle','DropboxMaxFilesizeComment',NULL,NULL, 0),
('dropbox_allow_just_upload',NULL,'radio','Tools','true','DropboxAllowJustUploadTitle','DropboxAllowJustUploadComment',NULL,NULL, 0),
('dropbox_allow_student_to_student',NULL,'radio','Tools','true','DropboxAllowStudentToStudentTitle','DropboxAllowStudentToStudentComment',NULL,NULL, 0),
('dropbox_allow_group',NULL,'radio','Tools','true','DropboxAllowGroupTitle','DropboxAllowGroupComment',NULL,NULL, 0),
('dropbox_allow_mailing',NULL,'radio','Tools','false','DropboxAllowMailingTitle','DropboxAllowMailingComment',NULL,NULL, 0),
('administratorTelephone',NULL,'textfield','Platform','(000) 001 02 03','administratorTelephoneTitle','administratorTelephoneComment',NULL,NULL, 1),
('extended_profile',NULL,'radio','User','false','ExtendedProfileTitle','ExtendedProfileComment',NULL,NULL, 0),
('student_view_enabled',NULL,'radio','Platform','true','StudentViewEnabledTitle','StudentViewEnabledComment',NULL,NULL, 0),
('show_navigation_menu',NULL,'radio','Course','false','ShowNavigationMenuTitle','ShowNavigationMenuComment',NULL,NULL, 0),
('enable_tool_introduction',NULL,'radio','course','false','EnableToolIntroductionTitle','EnableToolIntroductionComment',NULL,NULL, 0),
('page_after_login', NULL, 'radio','Platform','user_portal.php', 'PageAfterLoginTitle','PageAfterLoginComment', NULL, NULL, 0),
('time_limit_whosonline', NULL, 'textfield','Platform','30', 'TimeLimitWhosonlineTitle','TimeLimitWhosonlineComment', NULL, NULL, 0),
('breadcrumbs_course_homepage', NULL, 'radio','Course','course_title', 'BreadCrumbsCourseHomepageTitle','BreadCrumbsCourseHomepageComment', NULL, NULL, 0),
('example_material_course_creation', NULL, 'radio','Platform','true', 'ExampleMaterialCourseCreationTitle','ExampleMaterialCourseCreationComment', NULL, NULL, 0),
('account_valid_duration',NULL, 'textfield','Platform','3660', 'AccountValidDurationTitle','AccountValidDurationComment', NULL, NULL, 0),
('use_session_mode', NULL, 'radio','Platform','true', 'UseSessionModeTitle','UseSessionModeComment', NULL, NULL, 0),
('allow_email_editor', NULL, 'radio', 'Tools', 'false', 'AllowEmailEditorTitle', 'AllowEmailEditorComment', NULL, NULL, 0),
('registered', NULL, 'textfield', NULL, 'false', NULL, NULL, NULL, NULL, 0),
('donotlistcampus', NULL, 'textfield', NULL, 'false', NULL, NULL, NULL, NULL,0 ),
('show_email_addresses', NULL,'radio','Platform','false','ShowEmailAddresses','ShowEmailAddressesComment',NULL,NULL, 1),
('profile','phone','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'phone', 0),
('service_visio', 'active', 'radio',NULL,'false', 'VisioEnable','', NULL, NULL, 0),
('service_visio', 'visio_host', 'textfield',NULL,'', 'VisioHost','', NULL, NULL, 0),
('service_visio', 'visio_port', 'textfield',NULL,'1935', 'VisioPort','', NULL, NULL, 0),
('service_visio', 'visio_pass', 'textfield',NULL,'', 'VisioPassword','', NULL, NULL, 0),
('service_ppt2lp', 'active', 'radio',NULL,'false', 'ppt2lp_actived','', NULL, NULL, 0),
('service_ppt2lp', 'host', 'textfield', NULL, NULL, 'Host', NULL, NULL, NULL, 0),
('service_ppt2lp', 'port', 'textfield', NULL, 2002, 'Port', NULL, NULL, NULL, 0),
('service_ppt2lp', 'user', 'textfield', NULL, NULL, 'UserOnHost', NULL, NULL, NULL, 0),
('service_ppt2lp', 'ftp_password', 'textfield', NULL, NULL, 'FtpPassword', NULL, NULL, NULL, 0),
('service_ppt2lp', 'path_to_lzx', 'textfield', NULL, NULL, '', NULL, NULL, NULL, 0),
('service_ppt2lp', 'size', 'radio', NULL, '720x540', '', NULL, NULL, NULL, 0),
('wcag_anysurfer_public_pages', NULL, 'radio','Editor','false','PublicPagesComplyToWAITitle','PublicPagesComplyToWAIComment', NULL, NULL, 0),
('stylesheets', NULL, 'textfield','stylesheets','chamilo','',NULL, NULL, NULL, 1),
('upload_extensions_list_type', NULL, 'radio', 'Security', 'blacklist', 'UploadExtensionsListType', 'UploadExtensionsListTypeComment', NULL, NULL, 0),
('upload_extensions_blacklist', NULL, 'textfield', 'Security', '', 'UploadExtensionsBlacklist', 'UploadExtensionsBlacklistComment', NULL, NULL, 0),
('upload_extensions_whitelist', NULL, 'textfield', 'Security', 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf', 'UploadExtensionsWhitelist', 'UploadExtensionsWhitelistComment', NULL, NULL, 0),
('upload_extensions_skip', NULL, 'radio', 'Security', 'true', 'UploadExtensionsSkip', 'UploadExtensionsSkipComment', NULL, NULL, 0),
('upload_extensions_replace_by', NULL, 'textfield', 'Security', 'dangerous', 'UploadExtensionsReplaceBy', 'UploadExtensionsReplaceByComment', NULL, NULL, 0),
('show_number_of_courses', NULL, 'radio','Platform','false', 'ShowNumberOfCourses','ShowNumberOfCoursesComment', NULL, NULL, 0),
('show_empty_course_categories', NULL, 'radio','Platform','true', 'ShowEmptyCourseCategories','ShowEmptyCourseCategoriesComment', NULL, NULL, 0),
('show_back_link_on_top_of_tree', NULL, 'radio','Platform','false', 'ShowBackLinkOnTopOfCourseTree','ShowBackLinkOnTopOfCourseTreeComment', NULL, NULL, 0),
('show_different_course_language', NULL, 'radio','Platform','true', 'ShowDifferentCourseLanguage','ShowDifferentCourseLanguageComment', NULL, NULL, 1),
('split_users_upload_directory', NULL, 'radio','Tuning','true', 'SplitUsersUploadDirectory','SplitUsersUploadDirectoryComment', NULL, NULL, 0),
('hide_dltt_markup', NULL, 'radio','Languages','true', 'HideDLTTMarkup','HideDLTTMarkupComment', NULL, NULL, 0),
('display_categories_on_homepage',NULL,'radio','Platform','false','DisplayCategoriesOnHomepageTitle','DisplayCategoriesOnHomepageComment',NULL,NULL, 1),
('permissions_for_new_directories', NULL, 'textfield', 'Security', '0777', 'PermissionsForNewDirs', 'PermissionsForNewDirsComment', NULL, NULL, 0),
('permissions_for_new_files', NULL, 'textfield', 'Security', '0666', 'PermissionsForNewFiles', 'PermissionsForNewFilesComment', NULL, NULL, 0),
('show_tabs', 'campus_homepage', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsCampusHomepage', 1),
('show_tabs', 'my_courses', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyCourses', 1),
('show_tabs', 'reporting', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsReporting', 1),
('show_tabs', 'platform_administration', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsPlatformAdministration', 1),
('show_tabs', 'my_agenda', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyAgenda', 1),
('show_tabs', 'my_profile', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyProfile', 1),
('default_forum_view', NULL, 'radio', 'Course', 'flat', 'DefaultForumViewTitle','DefaultForumViewComment',NULL,NULL, 0),
('platform_charset',NULL,'textfield','Languages','UTF-8','PlatformCharsetTitle','PlatformCharsetComment','platform',NULL, 0),
('noreply_email_address', '', 'textfield', 'Platform', '', 'NoReplyEmailAddress', 'NoReplyEmailAddressComment', NULL, NULL, 0),
('survey_email_sender_noreply', '', 'radio', 'Course', 'coach', 'SurveyEmailSenderNoReply', 'SurveyEmailSenderNoReplyComment', NULL, NULL, 0),
('openid_authentication',NULL,'radio','Security','false','OpenIdAuthentication','OpenIdAuthenticationComment',NULL,NULL, 0),
('profile','openid','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'OpenIDURL', 0),
('gradebook_enable',NULL,'radio','Gradebook','false','GradebookActivation','GradebookActivationComment',NULL,NULL, 0),
('show_tabs','my_gradebook','checkbox','Platform','true','ShowTabsTitle','ShowTabsComment',NULL,'TabsMyGradebook', 1),
('gradebook_score_display_coloring','my_display_coloring','checkbox','Gradebook','false','GradebookScoreDisplayColoring','GradebookScoreDisplayColoringComment',NULL,'TabsGradebookEnableColoring', 0),
('gradebook_score_display_custom','my_display_custom','checkbox','Gradebook','false','GradebookScoreDisplayCustom','GradebookScoreDisplayCustomComment',NULL,'TabsGradebookEnableCustom', 0),
('gradebook_score_display_colorsplit',NULL,'textfield','Gradebook','50','GradebookScoreDisplayColorSplit','GradebookScoreDisplayColorSplitComment',NULL,NULL, 0),
('gradebook_score_display_upperlimit','my_display_upperlimit','checkbox','Gradebook','false','GradebookScoreDisplayUpperLimit','GradebookScoreDisplayUpperLimitComment',NULL,'TabsGradebookEnableUpperLimit', 0),
('gradebook_number_decimals', NULL, 'select', 'Gradebook', '0', 'GradebookNumberDecimals', 'GradebookNumberDecimalsComment', NULL, NULL, 0),
('user_selected_theme',NULL,'radio','Platform','false','UserThemeSelection','UserThemeSelectionComment',NULL,NULL, 0),
('profile','theme','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'UserTheme', 0),
('allow_course_theme',NULL,'radio','Course','true','AllowCourseThemeTitle','AllowCourseThemeComment',NULL,NULL, 0),
('display_mini_month_calendar',NULL,'radio','Tools', 'true', 'DisplayMiniMonthCalendarTitle', 'DisplayMiniMonthCalendarComment', NULL, NULL, 0),
('display_upcoming_events',NULL,'radio','Tools','true','DisplayUpcomingEventsTitle','DisplayUpcomingEventsComment',NULL,NULL, 0),
('number_of_upcoming_events',NULL,'textfield','Tools','1','NumberOfUpcomingEventsTitle','NumberOfUpcomingEventsComment',NULL,NULL, 0),
('show_closed_courses',NULL,'radio','Platform','false','ShowClosedCoursesTitle','ShowClosedCoursesComment',NULL,NULL, 0),
('service_visio', 'visio_use_rtmpt', 'radio',null,'false', 'VisioUseRtmptTitle','VisioUseRtmptComment', NULL, NULL, 0),
('extendedprofile_registration', 'mycomptetences', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyCompetences', 0),
('extendedprofile_registration', 'mydiplomas', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyDiplomas', 0),
('extendedprofile_registration', 'myteach', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyTeach', 0),
('extendedprofile_registration', 'mypersonalopenarea', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyPersonalOpenArea', 0),
('extendedprofile_registrationrequired', 'mycomptetences', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyCompetences', 0),
('extendedprofile_registrationrequired', 'mydiplomas', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyDiplomas', 0),
('extendedprofile_registrationrequired', 'myteach', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyTeach', 0),
('extendedprofile_registrationrequired', 'mypersonalopenarea', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyPersonalOpenArea', 0),
('ldap_description', NULL, 'radio', 'LDAP', NULL, 'LdapDescriptionTitle', 'LdapDescriptionComment', NULL, NULL, 0),
('registration','phone','textfield','User','false','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Phone', 0),
('add_users_by_coach',NULL,'radio','Security','false','AddUsersByCoachTitle','AddUsersByCoachComment',NULL,NULL, 0),
('extend_rights_for_coach',NULL,'radio','Security','false','ExtendRightsForCoachTitle','ExtendRightsForCoachComment',NULL,NULL, 0),
('extend_rights_for_coach_on_survey',NULL,'radio','Security','true','ExtendRightsForCoachOnSurveyTitle','ExtendRightsForCoachOnSurveyComment',NULL,NULL, 0),
('course_create_active_tools','wiki','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Wiki', 0),
('show_session_coach', NULL, 'radio','Platform','false', 'ShowSessionCoachTitle','ShowSessionCoachComment', NULL, NULL, 0),
('course_create_active_tools','gradebook','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Gradebook', 0),
('allow_users_to_create_courses',NULL,'radio','Platform','true','AllowUsersToCreateCoursesTitle','AllowUsersToCreateCoursesComment',NULL,NULL, 0),
('course_create_active_tools','survey','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Survey', 0),
('course_create_active_tools','glossary','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Glossary', 0),
('course_create_active_tools','notebook','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Notebook', 0),
('course_create_active_tools','attendances','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Attendances', 0),
('course_create_active_tools','course_progress','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseProgress', 0),
('advanced_filemanager',NULL,'radio','Editor','true','AdvancedFileManagerTitle','AdvancedFileManagerComment',NULL,NULL, 1),
('allow_reservation', NULL, 'radio', 'Tools', 'false', 'AllowReservationTitle', 'AllowReservationComment', NULL, NULL, 0),
('profile','apikeys','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'ApiKeys', 0),
('allow_message_tool', NULL, 'radio', 'Tools', 'true', 'AllowMessageToolTitle', 'AllowMessageToolComment', NULL, NULL,1),
('allow_social_tool', NULL, 'radio', 'Tools', 'true', 'AllowSocialToolTitle', 'AllowSocialToolComment', NULL, NULL,1),
('allow_students_to_browse_courses',NULL,'radio','Platform','true','AllowStudentsToBrowseCoursesTitle','AllowStudentsToBrowseCoursesComment',NULL,NULL, 1),
('show_session_data', NULL, 'radio', 'Course', 'false', 'ShowSessionDataTitle', 'ShowSessionDataComment', NULL, NULL, 1),
('allow_use_sub_language', NULL, 'radio', 'Languages', 'false', 'AllowUseSubLanguageTitle', 'AllowUseSubLanguageComment', NULL, NULL,0),
('show_glossary_in_documents', NULL, 'radio', 'Course', 'none', 'ShowGlossaryInDocumentsTitle', 'ShowGlossaryInDocumentsComment', NULL, NULL,1),
('allow_terms_conditions', NULL, 'radio', 'Platform', 'false', 'AllowTermsAndConditionsTitle', 'AllowTermsAndConditionsComment', NULL, NULL,0),
('course_create_active_tools','enable_search','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Search',0),
('search_enabled',NULL,'radio','Search','false','EnableSearchTitle','EnableSearchComment',NULL,NULL,1),
('search_prefilter_prefix',NULL, NULL,'Search','','SearchPrefilterPrefix','SearchPrefilterPrefixComment',NULL,NULL,0),
('search_show_unlinked_results',NULL,'radio','Search','true','SearchShowUnlinkedResultsTitle','SearchShowUnlinkedResultsComment',NULL,NULL,1),
('show_courses_descriptions_in_catalog', NULL, 'radio', 'Course', 'true', 'ShowCoursesDescriptionsInCatalogTitle', 'ShowCoursesDescriptionsInCatalogComment', NULL, NULL, 1),
('allow_coach_to_edit_course_session',NULL,'radio','Course','true','AllowCoachsToEditInsideTrainingSessions','AllowCoachsToEditInsideTrainingSessionsComment',NULL,NULL, 0),
('show_glossary_in_extra_tools', NULL, 'radio', 'Course', 'false', 'ShowGlossaryInExtraToolsTitle', 'ShowGlossaryInExtraToolsComment', NULL, NULL,1),
('send_email_to_admin_when_create_course',NULL,'radio','Platform','false','SendEmailToAdminTitle','SendEmailToAdminComment',NULL,NULL, 1),
('go_to_course_after_login',NULL,'radio','Course','false','GoToCourseAfterLoginTitle','GoToCourseAfterLoginComment',NULL,NULL, 0),
('math_mimetex',NULL,'radio','Editor','false','MathMimetexTitle','MathMimetexComment',NULL,NULL, 0),
('math_asciimathML',NULL,'radio','Editor','false','MathASCIImathMLTitle','MathASCIImathMLComment',NULL,NULL, 0),
('enabled_asciisvg',NULL,'radio','Editor','false','AsciiSvgTitle','AsciiSvgComment',NULL,NULL, 0),
('include_asciimathml_script',NULL,'radio','Editor','false','IncludeAsciiMathMlTitle','IncludeAsciiMathMlComment',NULL,NULL, 0),
('youtube_for_students',NULL,'radio','Editor','true','YoutubeForStudentsTitle','YoutubeForStudentsComment',NULL,NULL, 0),
('block_copy_paste_for_students',NULL,'radio','Editor','false','BlockCopyPasteForStudentsTitle','BlockCopyPasteForStudentsComment',NULL,NULL, 0),
('more_buttons_maximized_mode',NULL,'radio','Editor','true','MoreButtonsForMaximizedModeTitle','MoreButtonsForMaximizedModeComment',NULL,NULL, 0),
('students_download_folders',NULL,'radio','Tools','true','AllowStudentsDownloadFoldersTitle','AllowStudentsDownloadFoldersComment',NULL,NULL, 0),
('users_copy_files',NULL,'radio','Tools','true','AllowUsersCopyFilesTitle','AllowUsersCopyFilesComment',NULL,NULL, 1),
('show_tabs', 'social', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsSocial', 0),
('allow_students_to_create_groups_in_social',NULL,'radio','Tools','false','AllowStudentsToCreateGroupsInSocialTitle','AllowStudentsToCreateGroupsInSocialComment',NULL,NULL, 0),
('allow_send_message_to_all_platform_users',NULL,'radio','Tools','true','AllowSendMessageToAllPlatformUsersTitle','AllowSendMessageToAllPlatformUsersComment',NULL,NULL, 0),
('message_max_upload_filesize',NULL,'textfield','Tools','20971520','MessageMaxUploadFilesizeTitle','MessageMaxUploadFilesizeComment',NULL,NULL, 0),
('show_tabs', 'dashboard', 'checkbox', 'Platform', 'true', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsDashboard', 1),
('use_users_timezone', 'timezones', 'radio', 'Timezones', 'true', 'UseUsersTimezoneTitle','UseUsersTimezoneComment',NULL,'Timezones', 1),
('timezone_value', 'timezones', 'select', 'Timezones', '', 'TimezoneValueTitle','TimezoneValueComment',NULL,'Timezones', 1),
('allow_user_course_subscription_by_course_admin', NULL, 'radio', 'Security', 'true', 'AllowUserCourseSubscriptionByCourseAdminTitle', 'AllowUserCourseSubscriptionByCourseAdminComment', NULL, NULL, 1),
('show_link_bug_notification', NULL, 'radio', 'Platform', 'true', 'ShowLinkBugNotificationTitle', 'ShowLinkBugNotificationComment', NULL, NULL, 0),
('course_validation', NULL, 'radio', 'Platform', 'false', 'EnableCourseValidation', 'EnableCourseValidationComment', NULL, NULL, 1),
('course_validation_terms_and_conditions_url', NULL, 'textfield', 'Platform', '', 'CourseValidationTermsAndConditionsLink', 'CourseValidationTermsAndConditionsLinkComment', NULL, NULL, 1),
('sso_authentication',NULL,'radio','Security','false','EnableSSOTitle','EnableSSOComment',NULL,NULL,1),
('sso_authentication_domain',NULL,'textfield','Security','','SSOServerDomainTitle','SSOServerDomainComment',NULL,NULL,1),
('sso_authentication_auth_uri',NULL,'textfield','Security','/?q=user','SSOServerAuthURITitle','SSOServerAuthURIComment',NULL,NULL,1),
('sso_authentication_unauth_uri',NULL,'textfield','Security','/?q=logout','SSOServerUnAuthURITitle','SSOServerUnAuthURIComment',NULL,NULL,1),
('sso_authentication_protocol',NULL,'radio','Security','http://','SSOServerProtocolTitle','SSOServerProtocolComment',NULL,NULL,1),
('enabled_wiris',NULL,'radio','Editor','false','EnabledWirisTitle','EnabledWirisComment',NULL,NULL, 0),
('allow_spellcheck',NULL,'radio','Editor','false','AllowSpellCheckTitle','AllowSpellCheckComment',NULL,NULL, 0),
('force_wiki_paste_as_plain_text',NULL,'radio','Editor','false','ForceWikiPasteAsPlainTextTitle','ForceWikiPasteAsPlainTextComment',NULL,NULL, 0),
('enabled_googlemaps',NULL,'radio','Editor','false','EnabledGooglemapsTitle','EnabledGooglemapsComment',NULL,NULL, 0),
('enabled_imgmap',NULL,'radio','Editor','true','EnabledImageMapsTitle','EnabledImageMapsComment',NULL,NULL, 0),
('enabled_support_svg',				NULL,'radio',		'Tools',	'true',	'EnabledSVGTitle','EnabledSVGComment',NULL,NULL, 0),
('pdf_export_watermark_enable',		NULL,'radio',		'Platform',	'false','PDFExportWatermarkEnableTitle',	'PDFExportWatermarkEnableComment',	'platform',NULL, 1),
('pdf_export_watermark_by_course',	NULL,'radio',		'Platform',	'false','PDFExportWatermarkByCourseTitle',	'PDFExportWatermarkByCourseComment','platform',NULL, 1),
('pdf_export_watermark_text',		NULL,'textfield',	'Platform',	'',		'PDFExportWatermarkTextTitle',		'PDFExportWatermarkTextComment',	'platform',NULL, 1),
('enabled_insertHtml',				NULL,'radio',		'Editor',	'true','EnabledInsertHtmlTitle',			'EnabledInsertHtmlComment',NULL,NULL, 0),
('students_export2pdf',				NULL,'radio',		'Tools',	'true',	'EnabledStudentExport2PDFTitle',	'EnabledStudentExport2PDFComment',NULL,NULL, 0),
('exercise_min_score', 				NULL,'textfield',	'Course',	'',		'ExerciseMinScoreTitle',			'ExerciseMinScoreComment','platform',NULL, 	1),
('exercise_max_score', 				NULL,'textfield',	'Course',	'',		'ExerciseMaxScoreTitle',			'ExerciseMaxScoreComment','platform',NULL, 	1),
('show_users_folders',				NULL,'radio',		'Tools',	'true',	'ShowUsersFoldersTitle','ShowUsersFoldersComment',NULL,NULL, 0),
('show_default_folders',			NULL,'radio',		'Tools',	'true',	'ShowDefaultFoldersTitle','ShowDefaultFoldersComment',NULL,NULL, 0),
('show_chat_folder',				NULL,'radio',		'Tools',	'true',	'ShowChatFolderTitle','ShowChatFolderComment',NULL,NULL, 0),
('enabled_text2audio',				NULL,'radio',		'Tools',	'false',	'Text2AudioTitle','Text2AudioComment',NULL,NULL, 0),
('course_hide_tools','course_description','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseDescription', 1),
('course_hide_tools','calendar_event','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Agenda', 1),
('course_hide_tools','document','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Documents', 1),
('course_hide_tools','learnpath','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'LearningPath', 1),
('course_hide_tools','link','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Links', 1),
('course_hide_tools','announcement','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Announcements', 1),
('course_hide_tools','forum','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Forums', 1),
('course_hide_tools','dropbox','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Dropbox', 1),
('course_hide_tools','quiz','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Quiz', 1),
('course_hide_tools','user','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Users', 1),
('course_hide_tools','group','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Groups', 1),
('course_hide_tools','chat','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Chat', 1),
('course_hide_tools','student_publication','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'StudentPublications', 1),
('course_hide_tools','wiki','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Wiki', 1),
('course_hide_tools','gradebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Gradebook', 1),
('course_hide_tools','survey','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Survey', 1),
('course_hide_tools','glossary','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Glossary', 1),
('course_hide_tools','notebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Notebook', 1),
('course_hide_tools','attendance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Attendances', 1),
('course_hide_tools','course_progress','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseProgress', 1),
('course_hide_tools','blog_management','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Blog',1),
('course_hide_tools','tracking','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Stats',1),
('course_hide_tools','course_maintenance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Maintenance',1),
('course_hide_tools','course_setting','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseSettings',1),
('enabled_support_pixlr',NULL,'radio','Tools','false','EnabledPixlrTitle','EnabledPixlrComment',NULL,NULL, 0),
('show_groups_to_users',NULL,'radio','Platform','false','ShowGroupsToUsersTitle','ShowGroupsToUsersComment',NULL,NULL, 0),
('accessibility_font_resize',NULL,'radio','Platform','false','EnableAccessibilityFontResizeTitle','EnableAccessibilityFontResizeComment',NULL,NULL, 1),
('hide_courses_in_sessions',NULL,'radio', 'Platform','false','HideCoursesInSessionsTitle',	'HideCoursesInSessionsComment','platform',NULL, 1),
('enable_quiz_scenario',  NULL,'radio','Course','false','EnableQuizScenarioTitle','EnableQuizScenarioComment',NULL,NULL, 1),
('enable_nanogong',NULL,'radio','Tools','false','EnableNanogongTitle','EnableNanogongComment',NULL,NULL, 0),
('filter_terms',NULL,'textarea','Security','','FilterTermsTitle','FilterTermsComment',NULL,NULL, 0),
('header_extra_content', NULL, 'textarea', 'Tracking', '', 'HeaderExtraContentTitle', 'HeaderExtraContentComment', NULL, NULL, 1),
('footer_extra_content', NULL, 'textarea', 'Tracking', '', 'FooterExtraContentTitle', 'FooterExtraContentComment', NULL, NULL, 1),
('show_documents_preview', NULL, 'radio', 'Tools', 'false', 'ShowDocumentPreviewTitle', 'ShowDocumentPreviewComment', NULL, NULL, 1),
('htmlpurifier_wiki', NULL, 'radio', 'Editor', 'false', 'HtmlPurifierWikiTitle', 'HtmlPurifierWikiComment', NULL, NULL, 0),
('cas_activate', NULL, 'radio', 'CAS', 'false', 'CasMainActivateTitle', 'CasMainActivateComment', NULL, NULL, 0),
('cas_server', NULL, 'textfield', 'CAS', '', 'CasMainServerTitle', 'CasMainServerComment', NULL, NULL, 0),
('cas_server_uri', NULL, 'textfield', 'CAS', '', 'CasMainServerURITitle', 'CasMainServerURIComment', NULL, NULL, 0),
('cas_port', NULL, 'textfield', 'CAS', '', 'CasMainPortTitle', 'CasMainPortComment', NULL, NULL, 0),
('cas_protocol', NULL, 'radio', 'CAS', '', 'CasMainProtocolTitle', 'CasMainProtocolComment', NULL, NULL, 0),
('cas_add_user_activate', NULL, 'radio', 'CAS', '', 'CasUserAddActivateTitle', 'CasUserAddActivateComment', NULL, NULL, 0),
('update_user_info_cas_with_ldap', NULL, 'radio', 'CAS', 'true', 'UpdateUserInfoCasWithLdapTitle', 'UpdateUserInfoCasWithLdapComment', NULL, NULL, 0),
('student_page_after_login', NULL, 'textfield', 'Platform', '', 'StudentPageAfterLoginTitle', 'StudentPageAfterLoginComment', NULL, NULL, 0),
('teacher_page_after_login', NULL, 'textfield', 'Platform', '', 'TeacherPageAfterLoginTitle', 'TeacherPageAfterLoginComment', NULL, NULL, 0),
('drh_page_after_login', NULL, 'textfield', 'Platform', '', 'DRHPageAfterLoginTitle', 'DRHPageAfterLoginComment', NULL, NULL, 0),
('sessionadmin_page_after_login', NULL, 'textfield', 'Platform', '', 'SessionAdminPageAfterLoginTitle', 'SessionAdminPageAfterLoginComment', NULL, NULL, 0),
('student_autosubscribe', NULL, 'textfield', 'Platform', '', 'StudentAutosubscribeTitle', 'StudentAutosubscribeComment', NULL, NULL, 0),
('teacher_autosubscribe', NULL, 'textfield', 'Platform', '', 'TeacherAutosubscribeTitle', 'TeacherAutosubscribeComment', NULL, NULL, 0),
('drh_autosubscribe', NULL, 'textfield', 'Platform', '', 'DRHAutosubscribeTitle', 'DRHAutosubscribeComment', NULL, NULL, 0),
('sessionadmin_autosubscribe', NULL, 'textfield', 'Platform', '', 'SessionadminAutosubscribeTitle', 'SessionadminAutosubscribeComment', NULL, NULL, 0),
('scorm_cumulative_session_time', NULL, 'radio', 'Course', 'true', 'ScormCumulativeSessionTimeTitle', 'ScormCumulativeSessionTimeComment', NULL, NULL, 0),
('allow_hr_skills_management', NULL, 'radio', 'Gradebook', 'true', 'AllowHRSkillsManagementTitle', 'AllowHRSkillsManagementComment', NULL, NULL, 1),
('enable_help_link', NULL, 'radio', 'Platform', 'true', 'EnableHelpLinkTitle', 'EnableHelpLinkComment', NULL, NULL, 0),
('teachers_can_change_score_settings', NULL, 'radio', 'Gradebook', 'true', 'TeachersCanChangeScoreSettingsTitle', 'TeachersCanChangeScoreSettingsComment', NULL, NULL, 1),
('allow_users_to_change_email_with_no_password', NULL, 'radio', 'User', 'false', 'AllowUsersToChangeEmailWithNoPasswordTitle', 'AllowUsersToChangeEmailWithNoPasswordComment', NULL, NULL, 0),
('show_admin_toolbar', NULL, 'radio', 'Platform', 'show_to_admin', 'ShowAdminToolbarTitle', 'ShowAdminToolbarComment', NULL, NULL, 1),
('allow_global_chat', NULL, 'radio', 'Platform', 'true', 'AllowGlobalChatTitle', 'AllowGlobalChatComment', NULL, NULL, 1),
('languagePriority1', NULL, 'radio', 'Languages', 'course_lang', 'LanguagePriority1Title', 'LanguagePriority1Comment', NULL, NULL, 0),
('languagePriority2', NULL, 'radio', 'Languages','user_profil_lang', 'LanguagePriority2Title', 'LanguagePriority2Comment', NULL, NULL, 0),
('languagePriority3', NULL, 'radio', 'Languages','user_selected_lang', 'LanguagePriority3Title', 'LanguagePriority3Comment', NULL, NULL, 0),
('languagePriority4', NULL, 'radio', 'Languages', 'platform_lang','LanguagePriority4Title', 'LanguagePriority4Comment', NULL, NULL, 0),
('login_is_email', NULL, 'radio', 'Platform', 'false', 'LoginIsEmailTitle', 'LoginIsEmailComment', NULL, NULL, 0),
('courses_default_creation_visibility', NULL, 'radio', 'Course', '2', 'CoursesDefaultCreationVisibilityTitle', 'CoursesDefaultCreationVisibilityComment', NULL, NULL, 1),
('allow_browser_sniffer', NULL, 'radio', 'Tuning', 'false', 'AllowBrowserSnifferTitle', 'AllowBrowserSnifferComment', NULL, NULL, 0),
('enable_wami_record',NULL,'radio','Tools','false','EnableWamiRecordTitle','EnableWamiRecordComment',NULL,NULL, 0),
('gradebook_default_weight', NULL, 'textfield', 'Gradebook', '100', 'GradebookDefaultWeightTitle', 'GradebookDefaultWeightComment', NULL, NULL, 0),
('gradebook_ranking_1', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_2', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_3', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_4', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_5', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_6', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_7', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_8', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_9', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('gradebook_ranking_10', 'ranking', 'gradebook_ranking', 'Gradebook', '', '', '', NULL, NULL, 1),
('chamilo_database_version',NULL,'textfield',NULL, '1.9.0.17631','DatabaseVersion','', NULL, NULL, 0);

/*
('show_tabs', 'custom_tab_1', 'checkbox', 'Platform', 'true', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom1', 1),
('show_tabs', 'custom_tab_2', 'checkbox', 'Platform', 'false', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom2', 1),
('show_tabs', 'custom_tab_3', 'checkbox', 'Platform', 'false', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsCustom3', 1),
('custom_tab_1_name', NULL, 'textfield', 'Platform', 'Reports', 'CustomTab1NameTitle', 'CustomTab1NameComment', NULL, NULL, 0),
('custom_tab_1_url', NULL, 'textfield', 'Platform', '/main/reports/', 'CustomTab1URLTitle', 'CustomTab1URLComment', NULL, NULL, 0),
('custom_tab_2_name', NULL, 'textfield', 'Platform', '', 'CustomTab2NameTitle', 'CustomTab2NameComment', NULL, NULL, 0),
('custom_tab_2_url', NULL, 'textfield', 'Platform', '', 'CustomTab2URLTitle', 'CustomTab2URLComment', NULL, NULL, 0),
('custom_tab_3_name', NULL, 'textfield', 'Platform', '', 'CustomTab3NameTitle', 'CustomTab3NameComment', NULL, NULL, 0),
('custom_tab_3_url', NULL, 'textfield', 'Platform', '', 'CustomTab3URLTitle', 'CustomTab3URLComment', NULL, NULL, 0),
('activate_send_event_by_mail', NULL, 'radio', 'Platform', 'false', 'ActivateSendEventByMailTitle', 'ActivateSendEventByMailComment', NULL, NULL, 0),
('use_custom_pages',NULL,'radio','Platform','false','UseCustomPages','useCustomPagesComment','platform',NULL,0),
('activate_send_event_by_mail', NULL, 'radio', 'Platform', 'false', 'ActivateSendEventByMailTitle', 'ActivateSendEventByMailComment', NULL, NULL, 0),
*/
UNLOCK TABLES;
/*!40000 ALTER TABLE settings_current ENABLE KEYS */;

--
-- Table structure for table settings_options
--

DROP TABLE IF EXISTS settings_options;
CREATE TABLE IF NOT EXISTS settings_options (
  id int unsigned NOT NULL auto_increment,
  variable varchar(255) default NULL,
  value varchar(255) default NULL,
  display_text varchar(255) NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY id (id)
);

ALTER TABLE settings_options ADD UNIQUE unique_setting_option (variable(165), value(165));

--
-- Dumping data for table settings_options
--


/*!40000 ALTER TABLE settings_options DISABLE KEYS */;
LOCK TABLES settings_options WRITE;
INSERT INTO settings_options (variable, value, display_text)
VALUES
('show_administrator_data','true','Yes'),
('show_administrator_data','false','No'),
('show_tutor_data','true','Yes'),
('show_tutor_data','false','No'),
('show_teacher_data','true','Yes'),
('show_teacher_data','false','No'),
('homepage_view','activity','HomepageViewActivity'),
('homepage_view','2column','HomepageView2column'),
('homepage_view','3column','HomepageView3column'),
('homepage_view','vertical_activity','HomepageViewVerticalActivity'),
('homepage_view','activity_big','HomepageViewActivityBig'),
('show_toolshortcuts','true','Yes'),
('show_toolshortcuts','false','No'),
('allow_group_categories','true','Yes'),
('allow_group_categories','false','No'),
('server_type','production','ProductionServer'),
('server_type','test','TestServer'),
('allow_name_change','true','Yes'),
('allow_name_change','false','No'),
('allow_officialcode_change','true','Yes'),
('allow_officialcode_change','false','No'),
('allow_registration','true','Yes'),
('allow_registration','false','No'),
('allow_registration','approval','AfterApproval'),
('allow_registration_as_teacher','true','Yes'),
('allow_registration_as_teacher','false','No'),
('allow_lostpassword','true','Yes'),
('allow_lostpassword','false','No'),
('allow_user_headings','true','Yes'),
('allow_user_headings','false','No'),
('allow_personal_agenda','true','Yes'),
('allow_personal_agenda','false','No'),
('display_coursecode_in_courselist','true','Yes'),
('display_coursecode_in_courselist','false','No'),
('display_teacher_in_courselist','true','Yes'),
('display_teacher_in_courselist','false','No'),
('use_document_title','true','Yes'),
('use_document_title','false','No'),
('permanently_remove_deleted_files','true','YesWillDeletePermanently'),
('permanently_remove_deleted_files','false','NoWillDeletePermanently'),
('dropbox_allow_overwrite','true','Yes'),
('dropbox_allow_overwrite','false','No'),
('dropbox_allow_just_upload','true','Yes'),
('dropbox_allow_just_upload','false','No'),
('dropbox_allow_student_to_student','true','Yes'),
('dropbox_allow_student_to_student','false','No'),
('dropbox_allow_group','true','Yes'),
('dropbox_allow_group','false','No'),
('dropbox_allow_mailing','true','Yes'),
('dropbox_allow_mailing','false','No'),
('extended_profile','true','Yes'),
('extended_profile','false','No'),
('student_view_enabled','true','Yes'),
('student_view_enabled','false','No'),
('show_navigation_menu','false','No'),
('show_navigation_menu','icons','IconsOnly'),
('show_navigation_menu','text','TextOnly'),
('show_navigation_menu','iconstext','IconsText'),
('enable_tool_introduction','true','Yes'),
('enable_tool_introduction','false','No'),
('page_after_login', 'index.php', 'CampusHomepage'),
('page_after_login', 'user_portal.php', 'MyCourses'),
('page_after_login', 'main/auth/courses.php', 'CourseCatalog'),
('breadcrumbs_course_homepage', 'get_lang', 'CourseHomepage'),
('breadcrumbs_course_homepage', 'course_code', 'CourseCode'),
('breadcrumbs_course_homepage', 'course_title', 'CourseTitle'),
('example_material_course_creation', 'true', 'Yes'),
('example_material_course_creation', 'false', 'No'),
('use_session_mode', 'true', 'Yes'),
('use_session_mode', 'false', 'No'),
('allow_email_editor', 'true' ,'Yes'),
('allow_email_editor', 'false', 'No'),
('show_email_addresses','true','Yes'),
('show_email_addresses','false','No'),
('wcag_anysurfer_public_pages', 'true', 'Yes'),
('wcag_anysurfer_public_pages', 'false', 'No'),
('upload_extensions_list_type', 'blacklist', 'Blacklist'),
('upload_extensions_list_type', 'whitelist', 'Whitelist'),
('upload_extensions_skip', 'true', 'Remove'),
('upload_extensions_skip', 'false', 'Rename'),
('show_number_of_courses', 'true', 'Yes'),
('show_number_of_courses', 'false', 'No'),
('show_empty_course_categories', 'true', 'Yes'),
('show_empty_course_categories', 'false', 'No'),
('show_back_link_on_top_of_tree', 'true', 'Yes'),
('show_back_link_on_top_of_tree', 'false', 'No'),
('show_different_course_language', 'true', 'Yes'),
('show_different_course_language', 'false', 'No'),
('split_users_upload_directory', 'true', 'Yes'),
('split_users_upload_directory', 'false', 'No'),
('hide_dltt_markup', 'false', 'No'),
('hide_dltt_markup', 'true', 'Yes'),
('display_categories_on_homepage','true','Yes'),
('display_categories_on_homepage','false','No'),
('default_forum_view', 'flat', 'Flat'),
('default_forum_view', 'threaded', 'Threaded'),
('default_forum_view', 'nested', 'Nested'),
('survey_email_sender_noreply', 'coach', 'CourseCoachEmailSender'),
('survey_email_sender_noreply', 'noreply', 'NoReplyEmailSender'),
('openid_authentication','true','Yes'),
('openid_authentication','false','No'),
('gradebook_enable','true','Yes'),
('gradebook_enable','false','No'),
('user_selected_theme','true','Yes'),
('user_selected_theme','false','No'),
('allow_course_theme','true','Yes'),
('allow_course_theme','false','No'),
('display_mini_month_calendar', 'true', 'Yes'),
('display_mini_month_calendar', 'false', 'No'),
('display_upcoming_events', 'true', 'Yes'),
('display_upcoming_events', 'false', 'No'),
('show_closed_courses', 'true', 'Yes'),
('show_closed_courses', 'false', 'No'),
('ldap_version', '2', 'LDAPVersion2'),
('ldap_version', '3', 'LDAPVersion3'),
('visio_use_rtmpt','true','Yes'),
('visio_use_rtmpt','false','No'),
('add_users_by_coach', 'true', 'Yes'),
('add_users_by_coach', 'false', 'No'),
('extend_rights_for_coach', 'true', 'Yes'),
('extend_rights_for_coach', 'false', 'No'),
('extend_rights_for_coach_on_survey', 'true', 'Yes'),
('extend_rights_for_coach_on_survey', 'false', 'No'),
('show_session_coach', 'true', 'Yes'),
('show_session_coach', 'false', 'No'),
('allow_users_to_create_courses','true','Yes'),
('allow_users_to_create_courses','false','No'),
('breadcrumbs_course_homepage', 'session_name_and_course_title', 'SessionNameAndCourseTitle'),
('advanced_filemanager','true','Yes'),
('advanced_filemanager','false','No'),
('allow_reservation', 'true', 'Yes'),
('allow_reservation', 'false', 'No'),
('allow_message_tool', 'true', 'Yes'),
('allow_message_tool', 'false', 'No'),
('allow_social_tool', 'true', 'Yes'),
('allow_social_tool', 'false', 'No'),
('allow_students_to_browse_courses','true','Yes'),
('allow_students_to_browse_courses','false','No'),
('show_email_of_teacher_or_tutor ', 'true', 'Yes'),
('show_email_of_teacher_or_tutor ', 'false', 'No'),
('show_session_data ', 'true', 'Yes'),
('show_session_data ', 'false', 'No'),
('allow_use_sub_language', 'true', 'Yes'),
('allow_use_sub_language', 'false', 'No'),
('show_glossary_in_documents', 'none', 'ShowGlossaryInDocumentsIsNone'),
('show_glossary_in_documents', 'ismanual', 'ShowGlossaryInDocumentsIsManual'),
('show_glossary_in_documents', 'isautomatic', 'ShowGlossaryInDocumentsIsAutomatic'),
('allow_terms_conditions', 'true', 'Yes'),
('allow_terms_conditions', 'false', 'No'),
('search_enabled', 'true', 'Yes'),
('search_enabled', 'false', 'No'),
('search_show_unlinked_results', 'true', 'SearchShowUnlinkedResults'),
('search_show_unlinked_results', 'false', 'SearchHideUnlinkedResults'),
('show_courses_descriptions_in_catalog', 'true', 'Yes'),
('show_courses_descriptions_in_catalog', 'false', 'No'),
('allow_coach_to_edit_course_session','true','Yes'),
('allow_coach_to_edit_course_session','false','No'),
('show_glossary_in_extra_tools', 'true', 'Yes'),
('show_glossary_in_extra_tools', 'false', 'No'),
('send_email_to_admin_when_create_course','true','Yes'),
('send_email_to_admin_when_create_course','false','No'),
('go_to_course_after_login','true','Yes'),
('go_to_course_after_login','false','No'),
('math_mimetex','true','Yes'),
('math_mimetex','false','No'),
('math_asciimathML','true','Yes'),
('math_asciimathML','false','No'),
('enabled_asciisvg','true','Yes'),
('enabled_asciisvg','false','No'),
('include_asciimathml_script','true','Yes'),
('include_asciimathml_script','false','No'),
('youtube_for_students','true','Yes'),
('youtube_for_students','false','No'),
('block_copy_paste_for_students','true','Yes'),
('block_copy_paste_for_students','false','No'),
('more_buttons_maximized_mode','true','Yes'),
('more_buttons_maximized_mode','false','No'),
('students_download_folders','true','Yes'),
('students_download_folders','false','No'),
('users_copy_files','true','Yes'),
('users_copy_files','false','No'),
('allow_students_to_create_groups_in_social','true','Yes'),
('allow_students_to_create_groups_in_social','false','No'),
('allow_send_message_to_all_platform_users','true','Yes'),
('allow_send_message_to_all_platform_users','false','No'),
('use_users_timezone', 'true', 'Yes'),
('use_users_timezone', 'false', 'No'),
('allow_user_course_subscription_by_course_admin', 'true', 'Yes'),
('allow_user_course_subscription_by_course_admin', 'false', 'No'),
('show_link_bug_notification', 'true', 'Yes'),
('show_link_bug_notification', 'false', 'No'),
('course_validation', 'true', 'Yes'),
('course_validation', 'false', 'No'),
('sso_authentication', 'true', 'Yes'),
('sso_authentication', 'false', 'No'),
('sso_authentication_protocol', 'http://', 'http://'),
('sso_authentication_protocol', 'https://', 'https://'),
('enabled_wiris','true','Yes'),
('enabled_wiris','false','No'),
('allow_spellcheck','true','Yes'),
('allow_spellcheck','false','No'),
('force_wiki_paste_as_plain_text','true','Yes'),
('force_wiki_paste_as_plain_text','false','No'),
('enabled_googlemaps','true','Yes'),
('enabled_googlemaps','false','No'),
('enabled_imgmap','true','Yes'),
('enabled_imgmap','false','No'),
('enabled_support_svg','true','Yes'),
('enabled_support_svg','false','No'),
('pdf_export_watermark_enable','true','Yes'),
('pdf_export_watermark_enable','false','No'),
('pdf_export_watermark_by_course','true','Yes'),
('pdf_export_watermark_by_course','false','No'),
('enabled_insertHtml','true','Yes'),
('enabled_insertHtml','false','No'),
('students_export2pdf','true','Yes'),
('students_export2pdf','false','No'),
('show_users_folders','true','Yes'),
('show_users_folders','false','No'),
('show_default_folders','true','Yes'),
('show_default_folders','false','No'),
('show_chat_folder','true','Yes'),
('show_chat_folder','false','No'),
('enabled_text2audio','true','Yes'),
('enabled_text2audio','false','No'),
('enabled_support_pixlr','true','Yes'),
('enabled_support_pixlr','false','No'),
('show_groups_to_users','true','Yes'),
('show_groups_to_users','false','No'),
('accessibility_font_resize', 'true', 'Yes'),
('accessibility_font_resize', 'false', 'No'),
('hide_courses_in_sessions','true','Yes'),
('hide_courses_in_sessions','false','No'),
('enable_quiz_scenario', 'true', 'Yes'),
('enable_quiz_scenario', 'false', 'No'),
('enable_nanogong','true','Yes'),
('enable_nanogong','false','No'),
('show_documents_preview', 'true', 'Yes'),
('show_documents_preview', 'false', 'No'),
('htmlpurifier_wiki', 'true', 'Yes'),
('htmlpurifier_wiki', 'false', 'No'),
('cas_activate', 'true', 'Yes'),
('cas_activate', 'false', 'No'),
('cas_protocol', 'CAS1', 'CAS1Text'),
('cas_protocol', 'CAS2', 'CAS2Text'),
('cas_protocol', 'SAML', 'SAMLText'),
('cas_add_user_activate', 'false', 'No'),
('cas_add_user_activate', 'platform', 'casAddUserActivatePlatform'),
('cas_add_user_activate', 'extldap', 'casAddUserActivateLDAP'),
('update_user_info_cas_with_ldap', 'true', 'Yes'),
('update_user_info_cas_with_ldap', 'false', 'No'),
('scorm_cumulative_session_time','true','Yes'),
('scorm_cumulative_session_time','false','No'),
('allow_hr_skills_management', 'true', 'Yes'),
('allow_hr_skills_management', 'false', 'No'),
('enable_help_link', 'true', 'Yes'),
('enable_help_link', 'false', 'No'),
('allow_users_to_change_email_with_no_password', 'true', 'Yes'),
('allow_users_to_change_email_with_no_password', 'false', 'No'),
('show_admin_toolbar', 'do_not_show', 'DoNotShow'),
('show_admin_toolbar', 'show_to_admin', 'ShowToAdminsOnly'), 
('show_admin_toolbar', 'show_to_admin_and_teachers', 'ShowToAdminsAndTeachers'),
('show_admin_toolbar', 'show_to_all', 'ShowToAllUsers'),
('use_custom_pages','true','Yes'),
('use_custom_pages','false','No'),
('languagePriority1','platform_lang','PlatformLanguage'),
('languagePriority1','user_profil_lang','UserLanguage'),
('languagePriority1','user_selected_lang','UserSelectedLanguage'),
('languagePriority1','course_lang','CourseLanguage'),
('languagePriority2','platform_lang','PlatformLanguage'),
('languagePriority2','user_profil_lang','UserLanguage'),
('languagePriority2','user_selected_lang','UserSelectedLanguage'),
('languagePriority2','course_lang','CourseLanguage'),
('languagePriority3','platform_lang','PlatformLanguage'),
('languagePriority3','user_profil_lang','UserLanguage'),
('languagePriority3','user_selected_lang','UserSelectedLanguage'),
('languagePriority3','course_lang','CourseLanguage'),
('languagePriority4','platform_lang','PlatformLanguage'),
('languagePriority4','user_profil_lang','UserLanguage'),
('languagePriority4','user_selected_lang','UserSelectedLanguage'),
('languagePriority4','course_lang','CourseLanguage'),
('allow_global_chat', 'true', 'Yes'),
('allow_global_chat', 'false', 'No'),
('login_is_email','true','Yes'),
('login_is_email','false','No'),
('courses_default_creation_visibility', '3', 'OpenToTheWorld'),
('courses_default_creation_visibility', '2', 'OpenToThePlatform'),
('courses_default_creation_visibility', '1', 'Private'),
('courses_default_creation_visibility', '0', 'CourseVisibilityClosed'),
('allow_browser_sniffer', 'true', 'Yes'),
('allow_browser_sniffer', 'false', 'No'),
('enable_wami_record', 'true', 'Yes'),
('enable_wami_record', 'false', 'No'),
('cas_add_user_activate', 'extldap', 'casAddUserActivateLDAP'),
('update_user_info_cas_with_ldap', 'true', 'Yes'),
('update_user_info_cas_with_ldap', 'false', 'No'),
('teachers_can_change_score_settings', 'false', 'Yes'),
('teachers_can_change_score_settings', 'false', 'No');


UNLOCK TABLES;
/*
('activate_send_event_by_mail', 'true', 'Yes'),
('activate_send_event_by_mail', 'false', 'No'),
*/

/*!40000 ALTER TABLE settings_options ENABLE KEYS */;


--
-- Table structure for table sys_announcement
--

DROP TABLE IF EXISTS sys_announcement;
CREATE TABLE IF NOT EXISTS sys_announcement (
  id int unsigned NOT NULL auto_increment,
  date_start datetime NOT NULL default '0000-00-00 00:00:00',
  date_end datetime NOT NULL default '0000-00-00 00:00:00',
  visible_teacher tinyint NOT NULL default 0,
  visible_student tinyint NOT NULL default 0,
  visible_guest tinyint NOT NULL default 0,
  title varchar(250) NOT NULL default '',
  content text NOT NULL,
  lang varchar(70) NULL default NULL,
  access_url_id INT NOT NULL default 1,
  PRIMARY KEY  (id)
);

--
-- Table structure for shared_survey
--

DROP TABLE IF EXISTS shared_survey;
CREATE TABLE IF NOT EXISTS shared_survey (
  survey_id int unsigned NOT NULL auto_increment,
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

DROP TABLE IF EXISTS shared_survey_question;
CREATE TABLE IF NOT EXISTS shared_survey_question (
  question_id int NOT NULL auto_increment,
  survey_id int NOT NULL default '0',
  survey_question text NOT NULL,
  survey_question_comment text NOT NULL,
  type varchar(250) NOT NULL default '',
  display varchar(10) NOT NULL default '',
  sort int NOT NULL default '0',
  code varchar(40) NOT NULL default '',
  max_value int NOT NULL,
  PRIMARY KEY  (question_id)
);

-- --------------------------------------------------------

--
-- Table structure for shared_survey_question_option
--

DROP TABLE IF EXISTS shared_survey_question_option;
CREATE TABLE IF NOT EXISTS shared_survey_question_option (
  question_option_id int NOT NULL auto_increment,
  question_id int NOT NULL default '0',
  survey_id int NOT NULL default '0',
  option_text text NOT NULL,
  sort int NOT NULL default '0',
  PRIMARY KEY  (question_option_id)
);


-- --------------------------------------------------------

--
-- Table structure for templates (User's FCKEditor templates)
--

DROP TABLE IF EXISTS templates;
CREATE TABLE IF NOT EXISTS templates (
  id int NOT NULL auto_increment,
  title varchar(100) NOT NULL,
  description varchar(250) NOT NULL,
  course_code varchar(40) NOT NULL,
  user_id int NOT NULL,
  ref_doc int NOT NULL,
  image varchar(250) NOT NULL,
  PRIMARY KEY  (id)
);



--

-- --------------------------------------------------------

--
-- Table structure of openid_association (keep info on openid servers)
--

DROP TABLE IF EXISTS openid_association;
CREATE TABLE IF NOT EXISTS openid_association (
  id int NOT NULL auto_increment,
  idp_endpoint_uri text NOT NULL,
  session_type varchar(30) NOT NULL,
  assoc_handle text NOT NULL,
  assoc_type text NOT NULL,
  expires_in bigint NOT NULL,
  mac_key text NOT NULL,
  created bigint NOT NULL,
  PRIMARY KEY  (id)
);
--
-- --------------------------------------------------------
--
-- Tables for gradebook
--
DROP TABLE IF EXISTS gradebook_category;
CREATE TABLE IF NOT EXISTS gradebook_category (
  id int NOT NULL auto_increment,
  name text NOT NULL,
  description text,
  user_id int NOT NULL,
  course_code varchar(40) default NULL,
  parent_id int default NULL,
  weight float NOT NULL,
  visible tinyint NOT NULL,
  certif_min_score int DEFAULT NULL,
  session_id int DEFAULT NULL,
  document_id int unsigned DEFAULT NULL,
  PRIMARY KEY  (id)
);
DROP TABLE IF EXISTS gradebook_evaluation;
CREATE TABLE IF NOT EXISTS gradebook_evaluation (
  id int unsigned NOT NULL auto_increment,
  name text NOT NULL,
  description text,
  user_id int NOT NULL,
  course_code varchar(40) default NULL,
  category_id int default NULL,
  created_at DATETIME NOT NULL default '0000-00-00 00:00:00',
  weight FLOAT NOT NULL,
  max float unsigned NOT NULL,
  visible tinyint NOT NULL,
  type varchar(40) NOT NULL default 'evaluation',
  locked int NOT NULL DEFAULT 0,
  PRIMARY KEY  (id)
);
DROP TABLE IF EXISTS gradebook_link;
CREATE TABLE IF NOT EXISTS gradebook_link (
  id int NOT NULL auto_increment,
  type int NOT NULL,
  ref_id int NOT NULL,
  user_id int NOT NULL,
  course_code varchar(40) NOT NULL,
  category_id int NOT NULL,
  created_at DATETIME NOT NULL default '0000-00-00 00:00:00',
  weight float NOT NULL,
  visible tinyint NOT NULL,
  PRIMARY KEY  (id)
);
DROP TABLE IF EXISTS gradebook_result;
CREATE TABLE IF NOT EXISTS gradebook_result (
  id int NOT NULL auto_increment,
  user_id int NOT NULL,
  evaluation_id int NOT NULL,
  created_at DATETIME NOT NULL default '0000-00-00 00:00:00',
  score float unsigned default NULL,
  PRIMARY KEY  (id)
);
DROP TABLE IF EXISTS gradebook_score_display;
CREATE TABLE IF NOT EXISTS gradebook_score_display (
  id int NOT NULL auto_increment,
  score float unsigned NOT NULL,
  display varchar(40) NOT NULL,
  category_id int NOT NULL default 0,
  score_color_percent float unsigned NOT NULL default 0,
  PRIMARY KEY (id)
);
ALTER TABLE gradebook_score_display ADD INDEX(category_id);

DROP TABLE IF EXISTS user_field;
CREATE TABLE IF NOT EXISTS user_field (
    id	INT NOT NULL auto_increment,
    field_type int NOT NULL DEFAULT 1,
    field_variable	varchar(64) NOT NULL,
    field_display_text	varchar(64),
    field_default_value text,
    field_order int,
    field_visible tinyint default 0,
    field_changeable tinyint default 0,
    field_filter tinyint default 0,
    tms	DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);
DROP TABLE IF EXISTS user_field_options;
CREATE TABLE IF NOT EXISTS user_field_options (
    id	int NOT NULL auto_increment,
    field_id int	NOT NULL,
    option_value	text,
    option_display_text varchar(64),
    option_order int,
    tms	DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY (id)
);
DROP TABLE IF EXISTS user_field_values;
CREATE TABLE IF NOT EXISTS user_field_values(
    id	bigint	NOT NULL auto_increment,
    user_id	int	unsigned NOT NULL,
    field_id int NOT NULL,
    field_value	text,
    tms DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);

ALTER TABLE user_field_values ADD INDEX (user_id, field_id);


INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'legal_accept','Legal',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'already_logged_in','Already logged in',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'update_type','Update script type',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (10, 'tags','tags',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'rssfeeds','RSS',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'dashboard', 'Dashboard', 0, 0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (11, 'timezone', 'Timezone', 0, 0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_invitation',   'MailNotifyInvitation',1,1,'1');
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_message',      'MailNotifyMessage',1,1,'1');
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable, field_default_value) values (4, 'mail_notify_group_message','MailNotifyGroupMessage',1,1,'1');
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'user_chat_status','User chat status',0,0);
INSERT INTO user_field (field_type, field_variable, field_display_text, field_visible, field_changeable) VALUES (1, 'google_calendar_url','Google Calendar URL',0,0);

INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (8, '1', 'AtOnce',1);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (8, '8', 'Daily',2);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (8, '0', 'No',3);

INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (9, '1', 'AtOnce',1);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (9, '8', 'Daily',2);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (9, '0', 'No',3);

INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (10, '1', 'AtOnce',1);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (10, '8', 'Daily',2);
INSERT INTO user_field_options (field_id, option_value, option_display_text, option_order) values (10, '0', 'No',3);




DROP TABLE IF EXISTS gradebook_result_log;
CREATE TABLE IF NOT EXISTS gradebook_result_log (
    id int NOT NULL auto_increment,
    id_result int NOT NULL,
    user_id int NOT NULL,
    evaluation_id int NOT NULL,
    created_at DATETIME NOT NULL default '0000-00-00 00:00:00',
    score float unsigned default NULL,
    PRIMARY KEY(id)
);

DROP TABLE IF EXISTS gradebook_linkeval_log;
CREATE TABLE IF NOT EXISTS gradebook_linkeval_log (
    id int NOT NULL auto_increment,
    id_linkeval_log int NOT NULL,
    name text,
    description text,
    created_at DATETIME NOT NULL default '0000-00-00 00:00:00',
    weight smallint default NULL,
    visible tinyint default NULL,
    type varchar(20) NOT NULL,
    user_id_log int NOT NULL,
    PRIMARY KEY  (id)
);

--
-- --------------------------------------------------------
--
-- Tables for the access URL feature
--

DROP TABLE IF EXISTS access_url;
CREATE TABLE IF NOT EXISTS access_url(
    id	int	unsigned NOT NULL auto_increment,
    url	varchar(255) NOT NULL,
    description text,
    active	int unsigned not null default 0,
    created_by	int	not null,
    tms DATETIME NOT NULL default '0000-00-00 00:00:00',
    PRIMARY KEY (id)
);

INSERT INTO access_url(url, description, active, created_by) VALUES ('http://localhost/',' ',1,1);

DROP TABLE IF EXISTS access_url_rel_user;
CREATE TABLE IF NOT EXISTS access_url_rel_user (
  access_url_id int unsigned NOT NULL,
  user_id int unsigned NOT NULL,
  PRIMARY KEY (access_url_id, user_id)
);

ALTER TABLE access_url_rel_user ADD INDEX idx_access_url_rel_user_user (user_id);
ALTER TABLE access_url_rel_user ADD INDEX idx_access_url_rel_user_access_url(access_url_id);
ALTER TABLE access_url_rel_user ADD INDEX idx_access_url_rel_user_access_url_user (user_id,access_url_id);

DROP TABLE IF EXISTS access_url_rel_course;
CREATE TABLE IF NOT EXISTS access_url_rel_course (
  access_url_id int unsigned NOT NULL,
  course_code char(40) NOT NULL,
  PRIMARY KEY (access_url_id, course_code)
);


DROP TABLE IF EXISTS access_url_rel_session;
CREATE TABLE IF NOT EXISTS access_url_rel_session (
  access_url_id int unsigned NOT NULL,
  session_id int unsigned NOT NULL,
  PRIMARY KEY (access_url_id, session_id)
);

--
-- Table structure for table sys_calendar
--
DROP TABLE IF EXISTS sys_calendar;
CREATE TABLE IF NOT EXISTS sys_calendar (
  id int unsigned NOT NULL auto_increment,
  title varchar(255) NOT NULL,
  content text,
  start_date datetime NOT NULL default '0000-00-00 00:00:00',
  end_date datetime NOT NULL default '0000-00-00 00:00:00',
  access_url_id INT NOT NULL default 1,
  all_day INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS system_template;
CREATE TABLE IF NOT EXISTS system_template (
  id int UNSIGNED NOT NULL auto_increment,
  title varchar(250) NOT NULL,
  comment text NOT NULL,
  image varchar(250) NOT NULL,
  content text NOT NULL,
  PRIMARY KEY  (id)
);

-- Adding the platform templates

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCourseTitle', 'TemplateTitleCourseTitleDescription', 'coursetitle.gif', '
<head>
                {CSS}
                <style type="text/css">
                .gris_title         	{
                    color: silver;
                }
                h1
                {
                    text-align: right;
                }
                </style>

            </head>
            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
            <tbody>
            <tr>
            <td style="vertical-align: middle; width: 50%;" colspan="1" rowspan="1">
                <h1>TITULUS 1<br>
                <span class="gris_title">TITULUS 2</span><br>
                </h1>
            </td>
            <td style="width: 50%;">
                <img style="width: 100px; height: 100px;" alt="dokeos logo" src="{COURSE_DIR}images/logo_dokeos.png"></td>
            </tr>
            </tbody>
            </table>
            <p><br>
            <br>
            </p>
            </body>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCheckList', 'TemplateTitleCheckListDescription', 'checklist.gif', '
      <head>
                   {CSS}
                </head>
                <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="vertical-align: top; width: 66%;">
                <h3>Lorem ipsum dolor sit amet</h3>
                <ul>
                    <li>consectetur adipisicing elit</li>
                    <li>sed do eiusmod tempor incididunt</li>
                    <li>ut labore et dolore magna aliqua</li>
                </ul>

                <h3>Ut enim ad minim veniam</h3>
                <ul>
                    <li>quis nostrud exercitation ullamco</li>
                    <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                    <li>Excepteur sint occaecat cupidatat non proident</li>
                </ul>

                <h3>Sed ut perspiciatis unde omnis</h3>
                <ul>
                    <li>iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam</li>
                    <li>eaque ipsa quae ab illo inventore veritatis</li>
                    <li>et quasi architecto beatae vitae dicta sunt explicabo.&nbsp;</li>
                </ul>

                </td>
                <td style="background: transparent url({IMG_DIR}postit.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 33%; text-align: center; vertical-align: bottom;">
                <h3>Ut enim ad minima</h3>
                Veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.<br>
                <h3>
                <img style="width: 180px; height: 144px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_smile.png "><br></h3>
                </td>
                </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
                </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTeacher', 'TemplateTitleTeacherDescription', 'yourinstructor.gif', '
<head>
                   {CSS}
                   <style type="text/css">
                    .text
                    {
                        font-weight: normal;
                    }
                    </style>
                </head>
                <body>
                    <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                    <tbody>
                    <tr>
                    <td></td>
                    <td style="height: 33%;"></td>
                    <td></td>
                    </tr>
                    <tr>
                    <td style="width: 25%;"></td>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right; font-weight: bold;" colspan="1" rowspan="1">
                    <span class="text">
                    <br>
                    Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</span>
                    </td>
                    <td style="width: 25%; font-weight: bold;">
                    <img style="width: 180px; height: 241px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_case.png "></td>
                    </tr>
                    </tbody>
                    </table>
                    <p><br>
                    <br>
                    </p>
                </body>
');


INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLeftList', 'TemplateTitleListLeftListDescription', 'leftlist.gif', '
<head>
               {CSS}
           </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="width: 66%;"></td>
                <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 248px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_reads.png "><br>
                </td>
                </tr>
                <tr align="right">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">Lorem
                ipsum dolor sit amet.
                </td>
                </tr>
                <tr align="right">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Vivamus
                a quam.&nbsp;<br>
                </td>
                </tr>
                <tr align="right">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Proin
                a est stibulum ante ipsum.</td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLeftRightList', 'TemplateTitleLeftRightListDescription', 'leftrightlist.gif', '

<head>
               {CSS}
            </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; height: 400px; width: 720px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td></td>
                <td style="vertical-align: top;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 294px;" alt="Trainer" src="{COURSE_DIR}images/trainer/trainer_join_hands.png "><br>
                </td>
                <td></td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">Lorem
                ipsum dolor sit amet.
                </td>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                Convallis
                ut.&nbsp;Cras dui magna.</td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
                Vivamus
                a quam.&nbsp;<br>
                </td>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                Etiam
                lacinia stibulum ante.<br>
                </td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
                Proin
                a est stibulum ante ipsum.</td>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
                Consectetuer
                adipiscing elit. <br>
                </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>

');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleRightList', 'TemplateTitleRightListDescription', 'rightlist.gif', '
    <head>
               {CSS}
            </head>
            <body style="direction: ltr;">
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="vertical-align: bottom; width: 50%;" colspan="1" rowspan="4"><img style="width: 300px; height: 199px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_points_right.png"><br>
                </td>
                <td style="width: 50%;"></td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                Convallis
                ut.&nbsp;Cras dui magna.</td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                Etiam
                lacinia.<br>
                </td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
                Consectetuer
                adipiscing elit. <br>
                </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleComparison', 'TemplateTitleComparisonDescription', 'compare.gif', '
<head>
            {CSS}
            </head>

            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tr>
                    <td style="height: 10%; width: 33%;"></td>
                    <td style="vertical-align: top; width: 33%;" colspan="1" rowspan="2">&nbsp;<img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_standing.png "><br>
                    </td>
                    <td style="height: 10%; width: 33%;"></td>
                </tr>
            <tr>
            <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Lorem ipsum dolor sit amet.
            </td>
            <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 33%;">
            Convallis
            ut.&nbsp;Cras dui magna.</td>
            </tr>
            </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleDiagram', 'TemplateTitleDiagramDescription', 'diagram.gif', '
    <head>
                       {CSS}
                    </head>

                    <body>
                    <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                    <tbody>
                    <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; height: 33%; width: 33%;">
                    <br>
                    Etiam
                    lacinia stibulum ante.
                    Convallis
                    ut.&nbsp;Cras dui magna.</td>
                    <td colspan="1" rowspan="3">
                        <img style="width: 350px; height: 267px;" alt="Alaska chart" src="{COURSE_DIR}images/diagrams/alaska_chart.png "></td>
                    </tr>
                    <tr>
                    <td colspan="1" rowspan="1">
                    <img style="width: 300px; height: 199px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_points_right.png "></td>
                    </tr>
                    <tr>
                    </tr>
                    </tbody>
                    </table>
                    <p><br>
                    <br>
                    </p>
                    </body>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleDesc', 'TemplateTitleCheckListDescription', 'description.gif', '
<head>
                       {CSS}
                    </head>
                    <body>
                        <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                        <tbody>
                        <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <img style="width: 48px; height: 49px; float: left;" alt="01" src="{COURSE_DIR}images/small/01.png " hspace="5"><br>Lorem ipsum dolor sit amet<br><br><br>
                            <img style="width: 48px; height: 49px; float: left;" alt="02" src="{COURSE_DIR}images/small/02.png " hspace="5">
                            <br>Ut enim ad minim veniam<br><br><br>
                            <img style="width: 48px; height: 49px; float: left;" alt="03" src="{COURSE_DIR}images/small/03.png " hspace="5">Duis aute irure dolor in reprehenderit<br><br><br>
                            <img style="width: 48px; height: 49px; float: left;" alt="04" src="{COURSE_DIR}images/small/04.png " hspace="5">Neque porro quisquam est</td>

                        <td style="vertical-align: top; width: 50%; text-align: right;" colspan="1" rowspan="1">
                            <img style="width: 300px; height: 291px;" alt="Gearbox" src="{COURSE_DIR}images/diagrams/gearbox.jpg "><br></td>
                        </tr><tr></tr>
                        </tbody>
                        </table>
                        <p><br>
                        <br>
                        </p>
                    </body>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleObjectives', 'TemplateTitleObjectivesDescription', 'courseobjectives.gif', '
<head>
                   {CSS}
                </head>

                <body>
                    <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                    <tbody>
                    <tr>
                    <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
                    <img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_chair.png "><br>
                    </td>
                    <td style="height: 10%; width: 66%;"></td>
                    </tr>
                    <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 66%;">
                    <h3>Lorem ipsum dolor sit amet</h3>
                    <ul>
                    <li>consectetur adipisicing elit</li>
                    <li>sed do eiusmod tempor incididunt</li>
                    <li>ut labore et dolore magna aliqua</li>
                    </ul>
                    <h3>Ut enim ad minim veniam</h3>
                    <ul>
                    <li>quis nostrud exercitation ullamco</li>
                    <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                    <li>Excepteur sint occaecat cupidatat non proident</li>
                    </ul>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                <p><br>
                <br>
                </p>
                </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCycle', 'TemplateTitleCycleDescription', 'cyclechart.gif', '
<head>
                   {CSS}
                   <style>
                   .title
                   {
                       color: white; font-weight: bold;
                   }
                   </style>
                </head>


                <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="6">
                <tbody>
                <tr>
                    <td style="text-align: center; vertical-align: bottom; height: 10%;" colspan="3" rowspan="1">
                        <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/top_arrow.png ">
                    </td>
                </tr>
                <tr>
                    <td style="height: 5%; width: 45%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
                        <span class="title">Lorem ipsum</span>
                    </td>

                    <td style="height: 5%; width: 10%;"></td>
                    <td style="height: 5%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
                        <span class="title">Sed ut perspiciatis</span>
                    </td>
                </tr>
                    <tr>
                        <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
                            <ul>
                                <li>dolor sit amet</li>
                                <li>consectetur adipisicing elit</li>
                                <li>sed do eiusmod tempor&nbsp;</li>
                                <li>adipisci velit, sed quia non numquam</li>
                                <li>eius modi tempora incidunt ut labore et dolore magnam</li>
                            </ul>
                </td>
                <td style="width: 10%;"></td>
                <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
                    <ul>
                    <li>ut enim ad minim veniam</li>
                    <li>quis nostrud exercitation</li><li>ullamco laboris nisi ut</li>
                    <li> Quis autem vel eum iure reprehenderit qui in ea</li>
                    <li>voluptate velit esse quam nihil molestiae consequatur,</li>
                    </ul>
                    </td>
                    </tr>
                    <tr align="center">
                    <td style="height: 10%; vertical-align: top;" colspan="3" rowspan="1">
                    <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/bottom_arrow.png ">&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;
                </td>
                </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
                </body>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLearnerWonder', 'TemplateTitleLearnerWonderDescription', 'learnerwonder.gif', '
<head>
               {CSS}
            </head>

            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="width: 33%;" colspan="1" rowspan="4">
                    <img style="width: 120px; height: 348px;" alt="learner wonders" src="{COURSE_DIR}images/silhouette.png "><br>
                </td>
                <td style="width: 66%;"></td>
                </tr>
                <tr align="center">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Convallis
                ut.&nbsp;Cras dui magna.</td>
                </tr>
                <tr align="center">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Etiam
                lacinia stibulum ante.<br>
                </td>
                </tr>
                <tr align="center">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Consectetuer
                adipiscing elit. <br>
                </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTimeline', 'TemplateTitleTimelineDescription', 'phasetimeline.gif', '
<head>
               {CSS}
                <style>
                .title
                {
                    font-weight: bold; text-align: center;
                }
                </style>
            </head>

            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="8" cellspacing="5">
                <tbody>
                <tr class="title">
                    <td style="vertical-align: top; height: 3%; background-color: rgb(224, 224, 224);">Lorem ipsum</td>
                    <td style="height: 3%;"></td>
                    <td style="vertical-align: top; height: 3%; background-color: rgb(237, 237, 237);">Perspiciatis</td>
                    <td style="height: 3%;"></td>
                    <td style="vertical-align: top; height: 3%; background-color: rgb(245, 245, 245);">Nemo enim</td>
                </tr>

                <tr>
                    <td style="vertical-align: top; width: 30%; background-color: rgb(224, 224, 224);">
                        <ul>
                        <li>dolor sit amet</li>
                        <li>consectetur</li>
                        <li>adipisicing elit</li>
                    </ul>
                    <br>
                    </td>
                    <td>
                        <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
                    </td>

                    <td style="vertical-align: top; width: 30%; background-color: rgb(237, 237, 237);">
                        <ul>
                            <li>ut labore</li>
                            <li>et dolore</li>
                            <li>magni dolores</li>
                        </ul>
                    </td>
                    <td>
                        <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
                    </td>

                    <td style="vertical-align: top; background-color: rgb(245, 245, 245); width: 30%;">
                        <ul>
                            <li>neque porro</li>
                            <li>quisquam est</li>
                            <li>qui dolorem&nbsp;&nbsp;</li>
                        </ul>
                        <br><br>
                    </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleStopAndThink', 'TemplateTitleStopAndThinkDescription', 'stopthink.gif', '
<head>
               {CSS}
            </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
                    <img style="width: 180px; height: 169px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_staring.png ">
                <br>
                </td>
                <td style="height: 10%; width: 66%;"></td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}postit.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 66%; vertical-align: middle; text-align: center;">
                    <h3>Attentio sectetur adipisicing elit</h3>
                    <ul>
                        <li>sed do eiusmod tempor incididunt</li>
                        <li>ut labore et dolore magna aliqua</li>
                        <li>quis nostrud exercitation ullamco</li>
                    </ul><br></td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTable', 'TemplateTitleCheckListDescription', 'table.gif', '
<head>
                   {CSS}
                   <style type="text/css">
                .title
                {
                    font-weight: bold; text-align: center;
                }

                .items
                {
                    text-align: right;
                }


                    </style>

                </head>
                <body>
                <br />
               <h2>A table</h2>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px;" border="1" cellpadding="5" cellspacing="0">
                <tbody>
                <tr class="title">
                    <td>City</td>
                    <td>2005</td>
                    <td>2006</td>
                    <td>2007</td>
                    <td>2008</td>
                </tr>
                <tr class="items">
                    <td>Lima</td>
                    <td>10,40</td>
                    <td>8,95</td>
                    <td>9,19</td>
                    <td>9,76</td>
                </tr>
                <tr class="items">
                <td>New York</td>
                    <td>18,39</td>
                    <td>17,52</td>
                    <td>16,57</td>
                    <td>16,60</td>
                </tr>
                <tr class="items">
                <td>Barcelona</td>
                    <td>0,10</td>
                    <td>0,10</td>
                    <td>0,05</td>
                    <td>0,05</td>
                </tr>
                <tr class="items">
                <td>Paris</td>
                    <td>3,38</td>
                    <td >3,63</td>
                    <td>3,63</td>
                    <td>3,54</td>
                </tr>
                </tbody>
                </table>
                <br>
                </body>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleAudio', 'TemplateTitleAudioDescription', 'audiocomment.gif', '
<head>
               {CSS}
            </head>
                   <body>
                    <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                    <tbody>
                    <tr>
                    <td>
                    <div align="center">
                    <span style="text-align: center;">
                        <embed  type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" width="300" height="20" bgcolor="#FFFFFF" src="{REL_PATH}main/inc/lib/mediaplayer/player.swf" allowfullscreen="false" allowscriptaccess="always" flashvars="file={COURSE_DIR}audio/ListeningComprehension.mp3&amp;autostart=true"></embed>
                    </span></div>

                    <br>
                    </td>
                    <td colspan="1" rowspan="3"><br>
                        <img style="width: 300px; height: 341px; float: right;" alt="image" src="{COURSE_DIR}images/diagrams/head_olfactory_nerve.png "><br></td>
                    </tr>
                    <tr>
                    <td colspan="1" rowspan="1">
                        <img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_glasses.png"><br></td>
                    </tr>
                    <tr>
                    </tr>
                    </tbody>
                    </table>
                    <p><br>
                    <br>
                    </p>
                    </body>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleVideo', 'TemplateTitleVideoDescription', 'video.gif', '
<head>
                {CSS}
            </head>

            <body>
            <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
            <tbody>
            <tr>
            <td style="width: 50%; vertical-align: top;">

                 <div style="text-align: center;" id="player810625-parent">
                    <div style="border-style: none; overflow: hidden; width: 320px; height: 240px; background-color: rgb(220, 220, 220);">

                        <div id="player810625">
                            <div id="player810625-config" style="overflow: hidden; display: none; visibility: hidden; width: 0px; height: 0px;">url={REL_PATH}main/default_course_document/video/flv/example.flv width=320 height=240 loop=false play=false downloadable=false fullscreen=true displayNavigation=true displayDigits=true align=left dispPlaylist=none playlistThumbs=false</div>
                        </div>

                        <embed
                            type="application/x-shockwave-flash"
                            src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
                            width="320"
                            height="240"
                            id="single"
                            name="single"
                            quality="high"
                            allowfullscreen="true"
                            flashvars="width=320&height=240&autostart=false&file={REL_PATH}main/default_course_document/video/flv/example.flv&repeat=false&image=&showdownload=false&link={REL_PATH}main/default_course_document/video/flv/example.flv&showdigits=true&shownavigation=true&logo="
                        />

                    </div>
                </div>

            </td>
            <td style="background: transparent url({IMG_DIR}faded_grey.png) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 50%;">
            <h3><br>
            </h3>
            <h3>Lorem ipsum dolor sit amet</h3>
                <ul>
                <li>consectetur adipisicing elit</li>
                <li>sed do eiusmod tempor incididunt</li>
                <li>ut labore et dolore magna aliqua</li>
                </ul>
            <h3>Ut enim ad minim veniam</h3>
                <ul>
                <li>quis nostrud exercitation ullamco</li>
                <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                <li>Excepteur sint occaecat cupidatat non proident</li>
                </ul>
            </td>
            </tr>
            </tbody>
            </table>
            <p><br>
            <br>
            </p>
             <style type="text/css">body{}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
            </body>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleFlash', 'TemplateTitleFlashDescription', 'flash.gif', '
<head>
               {CSS}
            </head>
            <body>
            <center>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 100%; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                    <tr>
                    <td align="center">
                    <embed width="700" height="300" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="{COURSE_DIR}flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed></span><br />
                    </td>
                    </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
            </center>
            </body>
');


--
-- --------------------------------------------------------
--
-- Tables for reservation
--


--
-- Table structure for table reservation category
--

DROP TABLE IF EXISTS reservation_category;
CREATE TABLE IF NOT EXISTS reservation_category (
   id  int unsigned NOT NULL auto_increment,
   parent_id  int NOT NULL default 0,
   name  varchar(128) NOT NULL default '',
  PRIMARY KEY  ( id )
);

-- --------------------------------------------------------

--
-- Table structure for table reservation category_rights
--

DROP TABLE IF EXISTS reservation_category_rights;
CREATE TABLE IF NOT EXISTS  reservation_category_rights  (
   category_id  int NOT NULL default 0,
   class_id  int NOT NULL default 0,
   m_items  tinyint NOT NULL default 0
);

-- --------------------------------------------------------

--
-- Table structure for table  item reservation
--

DROP TABLE IF EXISTS reservation_item;
CREATE TABLE IF NOT EXISTS  reservation_item  (
   id  int unsigned NOT NULL auto_increment,
   category_id  int unsigned NOT NULL default 0,
   course_code  varchar(40) NOT NULL default '',
   name  varchar(128) NOT NULL default '',
   description  text NOT NULL,
   blackout  tinyint NOT NULL default 0,
   creator  int unsigned NOT NULL default 0,
   always_available TINYINT NOT NULL default 0,
  PRIMARY KEY  ( id )
);

-- --------------------------------------------------------

--
-- Table structure for table reservation item_rights
--

DROP TABLE IF EXISTS reservation_item_rights;
CREATE TABLE IF NOT EXISTS  reservation_item_rights  (
   item_id  int unsigned NOT NULL default 0,
   class_id  int unsigned NOT NULL default 0,
   edit_right  tinyint unsigned NOT NULL default 0,
   delete_right  tinyint unsigned NOT NULL default 0,
   m_reservation  tinyint unsigned NOT NULL default 0,
   view_right  tinyint NOT NULL default 0,
  PRIMARY KEY  ( item_id , class_id )
);

-- --------------------------------------------------------

--
-- Table structure for main reservation table
--

DROP TABLE IF EXISTS reservation_main;
CREATE TABLE IF NOT EXISTS  reservation_main  (
   id  int unsigned NOT NULL auto_increment,
   subid  int unsigned NOT NULL default 0,
   item_id  int unsigned NOT NULL default 0,
   auto_accept  tinyint unsigned NOT NULL default 0,
   max_users  int unsigned NOT NULL default 1,
   start_at  datetime NOT NULL default '0000-00-00 00:00:00',
   end_at  datetime NOT NULL default '0000-00-00 00:00:00',
   subscribe_from  datetime NOT NULL default '0000-00-00 00:00:00',
   subscribe_until  datetime NOT NULL default '0000-00-00 00:00:00',
   subscribers  int unsigned NOT NULL default 0,
   notes  text NOT NULL,
   timepicker  tinyint NOT NULL default 0,
   timepicker_min  int NOT NULL default 0,
   timepicker_max  int NOT NULL default 0,
  PRIMARY KEY  ( id )
);

-- --------------------------------------------------------

--
-- Table structure for reservation subscription table
--

DROP TABLE IF EXISTS reservation_subscription;
CREATE TABLE IF NOT EXISTS  reservation_subscription  (
   dummy  int unsigned NOT NULL auto_increment,
   user_id  int unsigned NOT NULL default 0,
   reservation_id  int unsigned NOT NULL default 0,
   accepted  tinyint unsigned NOT NULL default 0,
   start_at  datetime NOT NULL default '0000-00-00 00:00:00',
   end_at  datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  ( dummy )
);

-- ---------------------------------------------------------

--
-- Table structure for table user_rel_user
--
DROP TABLE IF EXISTS user_rel_user;
CREATE TABLE IF NOT EXISTS user_rel_user (
  id bigint unsigned not null auto_increment,
  user_id int unsigned not null,
  friend_user_id int unsigned not null,
  relation_type int not null default 0,
  last_edit DATETIME,
  PRIMARY KEY(id)
);

ALTER TABLE user_rel_user ADD INDEX idx_user_rel_user__user (user_id);
ALTER TABLE user_rel_user ADD INDEX idx_user_rel_user__friend_user(friend_user_id);
ALTER TABLE user_rel_user ADD INDEX idx_user_rel_user__user_friend_user(user_id,friend_user_id);

--
-- Table structure for table user_friend_relation_type
--
DROP TABLE IF EXISTS user_friend_relation_type;
CREATE TABLE IF NOT EXISTS user_friend_relation_type(
  id int unsigned not null auto_increment,
  title char(20),
  PRIMARY KEY(id)
);


--
-- Table structure for MD5 API keys for users
--

DROP TABLE IF EXISTS user_api_key;
CREATE TABLE IF NOT EXISTS user_api_key (
    id int unsigned NOT NULL auto_increment,
    user_id int unsigned NOT NULL,
    api_key char(32) NOT NULL,
    api_service char(10) NOT NULL default 'dokeos',
    PRIMARY KEY (id)
);
ALTER TABLE user_api_key ADD INDEX idx_user_api_keys_user (user_id);

--
-- Table structure for table message
--
DROP TABLE IF EXISTS message;
CREATE TABLE IF NOT EXISTS message(
    id bigint unsigned not null auto_increment,
    user_sender_id int unsigned not null,
    user_receiver_id int unsigned not null,
    msg_status tinyint unsigned not null default 0, -- 0 read, 1 unread, 3 deleted, 5 pending invitation, 6 accepted invitation, 7 invitation denied
    send_date datetime not null default '0000-00-00 00:00:00',
    title varchar(255) not null,
    content text not null,
    group_id int unsigned not null default 0,
    parent_id int unsigned not null default 0,
    update_date datetime not null default '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);
ALTER TABLE message ADD INDEX idx_message_user_sender(user_sender_id);
ALTER TABLE message ADD INDEX idx_message_user_receiver(user_receiver_id);
ALTER TABLE message ADD INDEX idx_message_user_sender_user_receiver(user_sender_id,user_receiver_id);
ALTER TABLE message ADD INDEX idx_message_group(group_id);
ALTER TABLE message ADD INDEX idx_message_parent(parent_id);

INSERT INTO user_friend_relation_type (id,title)
VALUES
(1,'SocialUnknow'),
(2,'SocialParent'),
(3,'SocialFriend'),
(4,'SocialGoodFriend'),
(5,'SocialEnemy'),
(6,'SocialDeleted');

--
-- Table structure for table legal (Terms & Conditions)
--

DROP TABLE IF EXISTS legal;
CREATE TABLE IF NOT EXISTS legal (
  legal_id int NOT NULL auto_increment,
  language_id int NOT NULL,
  date int NOT NULL default 0,
  content text,
  type int NOT NULL,
  changes text NOT NULL,
  version int,
  PRIMARY KEY (legal_id,language_id)
);

--
-- Table structure for certificate with gradebook
--

DROP TABLE IF EXISTS gradebook_certificate;
CREATE TABLE IF NOT EXISTS gradebook_certificate (
    id bigint unsigned not null auto_increment,
    cat_id int unsigned not null,
    user_id int unsigned not null,
    score_certificate float unsigned not null default 0,
    created_at DATETIME NOT NULL default '0000-00-00 00:00:00',
    path_certificate text null,
    PRIMARY KEY(id)
);
ALTER TABLE gradebook_certificate ADD INDEX idx_gradebook_certificate_category_id(cat_id);
ALTER TABLE gradebook_certificate ADD INDEX idx_gradebook_certificate_user_id(user_id);
ALTER TABLE gradebook_certificate ADD INDEX idx_gradebook_certificate_category_id_user_id(cat_id,user_id);



--
-- Tables structure for search tool
--

-- specific fields tables
DROP TABLE IF EXISTS specific_field;
CREATE TABLE IF NOT EXISTS specific_field (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    code char(1) NOT NULL,
    name VARCHAR(200) NOT NULL
);

DROP TABLE IF EXISTS specific_field_values;
CREATE TABLE IF NOT EXISTS specific_field_values (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    course_code VARCHAR(40) NOT NULL ,
    tool_id VARCHAR(100) NOT NULL ,
    ref_id INT NOT NULL ,
    field_id INT NOT NULL ,
    value VARCHAR(200) NOT NULL
);
ALTER TABLE specific_field ADD CONSTRAINT unique_specific_field__code UNIQUE (code);

-- search engine references to map dokeos resources

DROP TABLE IF EXISTS search_engine_ref;
CREATE TABLE IF NOT EXISTS search_engine_ref (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR( 40 ) NOT NULL,
    tool_id VARCHAR( 100 ) NOT NULL,
    ref_id_high_level INT NOT NULL,
    ref_id_second_level INT NULL,
    search_did INT NOT NULL
);

--
-- Table structure for table sessions categories
--

DROP TABLE IF EXISTS session_category;
CREATE TABLE IF NOT EXISTS session_category (
    id int NOT NULL auto_increment,
    name varchar(100) default NULL,
    date_start date default NULL,
    date_end date default NULL,
	access_url_id INT NOT NULL default 1,
  PRIMARY KEY  (id)
);


--
-- Table structure for table user tag
--

DROP TABLE IF EXISTS tag;
CREATE TABLE IF NOT EXISTS tag (
    id int NOT NULL auto_increment,
    tag char(255) NOT NULL,
    field_id int NOT NULL,
    count int NOT NULL,
    PRIMARY KEY  (id)
);

DROP TABLE IF EXISTS user_rel_tag;
CREATE TABLE IF NOT EXISTS user_rel_tag (
    id int NOT NULL auto_increment,
    user_id int NOT NULL,
    tag_id int NOT NULL,
    PRIMARY KEY  (id)
);

--
-- Table structure for user platform groups
--

DROP TABLE IF EXISTS groups;
CREATE TABLE IF NOT EXISTS groups (
    id int NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    description varchar(255) NOT NULL,
    picture_uri varchar(255) NOT NULL,
    url varchar(255) NOT NULL,
    visibility int NOT NULL,
    updated_on varchar(255) NOT NULL,
    created_on varchar(255) NOT NULL,
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS group_rel_tag;
CREATE TABLE IF NOT EXISTS group_rel_tag (
    id int NOT NULL AUTO_INCREMENT,
    tag_id int NOT NULL,
    group_id int NOT NULL,
    PRIMARY KEY (id)
);

ALTER TABLE group_rel_tag ADD INDEX ( group_id );
ALTER TABLE group_rel_tag ADD INDEX ( tag_id );

DROP TABLE IF EXISTS group_rel_user;
CREATE TABLE IF NOT EXISTS group_rel_user (
    id int NOT NULL AUTO_INCREMENT,
    group_id int NOT NULL,
    user_id int NOT NULL,
    relation_type int NOT NULL,
    PRIMARY KEY (id)
);
ALTER TABLE group_rel_user ADD INDEX ( group_id );
ALTER TABLE group_rel_user ADD INDEX ( user_id );
ALTER TABLE group_rel_user ADD INDEX ( relation_type );

DROP TABLE IF EXISTS group_rel_group;
CREATE TABLE IF NOT EXISTS group_rel_group (
	id int NOT NULL AUTO_INCREMENT,
	group_id int NOT NULL,
	subgroup_id int NOT NULL,
	relation_type int NOT NULL,
	PRIMARY KEY (id)
);
ALTER TABLE group_rel_group ADD INDEX ( group_id );
ALTER TABLE group_rel_group ADD INDEX ( subgroup_id );
ALTER TABLE group_rel_group ADD INDEX ( relation_type );

DROP TABLE IF EXISTS announcement_rel_group;
CREATE TABLE IF NOT EXISTS announcement_rel_group (
	group_id int NOT NULL,
	announcement_id int NOT NULL,
	PRIMARY KEY (group_id, announcement_id)
);
--
-- Table structure for table message attachment
--

DROP TABLE IF EXISTS message_attachment;
CREATE TABLE IF NOT EXISTS message_attachment (
    id int NOT NULL AUTO_INCREMENT,
    path varchar(255) NOT NULL,
    comment text,
    size int NOT NULL default 0,
    message_id int NOT NULL,
    filename varchar(255) NOT NULL,
    PRIMARY KEY  (id)
);



INSERT INTO course_field (field_type, field_variable, field_display_text, field_default_value, field_visible, field_changeable) values (10, 'special_course','Special course', 'Yes', 1 , 1);

--
-- Table structure for table block
--

DROP TABLE IF EXISTS block;
CREATE TABLE IF NOT EXISTS block (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NULL,
    description TEXT NULL,
    path VARCHAR(255) NOT NULL,
    controller VARCHAR(100) NOT NULL,
    active TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY(id)
);
ALTER TABLE block ADD UNIQUE(path);

--
-- Structure for table 'course_request' ("Course validation" feature)
--

DROP TABLE IF EXISTS course_request;
CREATE TABLE IF NOT EXISTS course_request (
  id int NOT NULL AUTO_INCREMENT,
  code varchar(40) NOT NULL,
  user_id int unsigned NOT NULL default '0',
  directory varchar(40) DEFAULT NULL,
  db_name varchar(40) DEFAULT NULL,
  course_language varchar(20) DEFAULT NULL,
  title varchar(250) DEFAULT NULL,
  description text,
  category_code varchar(40) DEFAULT NULL,
  tutor_name varchar(200) DEFAULT NULL,
  visual_code varchar(40) DEFAULT NULL,
  request_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  objetives text,
  target_audience text,
  status int unsigned NOT NULL default '0',
  info int unsigned NOT NULL default '0',
  exemplary_content int unsigned NOT NULL default '0',
  PRIMARY KEY (id),
  UNIQUE KEY code (code)
);

--
-- Structure for Careers, Promotions and Usergroups
--

DROP TABLE IF EXISTS career;
CREATE TABLE IF NOT EXISTS career (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL ,
    description TEXT NOT NULL,
    status INT NOT NULL default '0',
    created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS promotion;
CREATE TABLE IF NOT EXISTS promotion (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL ,
    description TEXT NOT NULL,
    career_id INT NOT NULL,
    status INT NOT NULL default '0',
    created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY(id)
);

DROP TABLE IF EXISTS usergroup;
CREATE TABLE IF NOT EXISTS usergroup (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    PRIMARY KEY (id)
);

DROP TABLE IF EXISTS usergroup_rel_user;
CREATE TABLE IF NOT EXISTS usergroup_rel_user    (
    usergroup_id INT NOT NULL,
    user_id 	INT NOT NULL
);

DROP TABLE IF EXISTS usergroup_rel_course;
CREATE TABLE IF NOT EXISTS usergroup_rel_course  (
    usergroup_id INT NOT NULL,
    course_id 	INT NOT NULL
);

DROP TABLE IF EXISTS usergroup_rel_session;
CREATE TABLE IF NOT EXISTS usergroup_rel_session (
    usergroup_id INT NOT NULL,
    session_id  INT NOT NULL
);


--
-- Structure for Mail notifications
--

DROP TABLE IF EXISTS notification;
CREATE TABLE IF NOT EXISTS notification (
	id 			BIGINT PRIMARY KEY NOT NULL AUTO_INCREMENT,
	dest_user_id INT NOT NULL,
 	dest_mail 	CHAR(255),
 	title 		CHAR(255),
 	content 	CHAR(255),
 	send_freq 	SMALLINT DEFAULT 1,
 	created_at 	DATETIME NOT NULL,
 	sent_at 	DATETIME NULL
);

ALTER TABLE notification ADD index mail_notify_sent_index (sent_at);
ALTER TABLE notification ADD index mail_notify_freq_index (sent_at, send_freq, created_at);

-- Skills management

DROP TABLE IF EXISTS skill;
CREATE TABLE IF NOT EXISTS skill (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  short_code varchar(100) NOT NULL,
  description TEXT NOT NULL,
  access_url_id int NOT NULL,
  icon varchar(255) NOT NULL,
  PRIMARY KEY (id)
);

INSERT INTO skill (name) VALUES ('Root');

DROP TABLE IF EXISTS skill_rel_gradebook;
CREATE TABLE IF NOT EXISTS skill_rel_gradebook (
  id int NOT NULL AUTO_INCREMENT,
  gradebook_id int NOT NULL,
  skill_id int NOT NULL,
  type varchar(10) NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS skill_rel_skill;
CREATE TABLE IF NOT EXISTS skill_rel_skill (
  skill_id int NOT NULL,
  parent_id int NOT NULL,
  relation_type int NOT NULL,
  level int NOT NULL
);

INSERT INTO skill_rel_skill VALUES(1, 0, 0, 0);

DROP TABLE IF EXISTS skill_rel_user;
CREATE TABLE IF NOT EXISTS skill_rel_user (
  id int NOT NULL AUTO_INCREMENT,
  user_id int NOT NULL,
  skill_id int NOT NULL,
  acquired_skill_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  assigned_by int NOT NULL, 
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS skill_profile;
CREATE TABLE IF NOT EXISTS skill_profile (
  id INTEGER  NOT NULL AUTO_INCREMENT,
  name VARCHAR(255)  NOT NULL,
  description TEXT  NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS skill_rel_profile;
CREATE TABLE IF NOT EXISTS skill_rel_profile (
  id INTEGER  NOT NULL AUTO_INCREMENT,
  skill_id INTEGER  NOT NULL,
  profile_id INTEGER  NOT NULL,
  PRIMARY KEY (id)
);

--
-- Table structure for event email sending
--
DROP TABLE IF EXISTS event_type;
CREATE TABLE IF NOT EXISTS event_type (
  id smallint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  name_lang_var varchar(50) NOT NULL,
  desc_lang_var varchar(50) NOT NULL,
  extendable_variables varchar(255) NOT NULL,
  PRIMARY KEY (id)
);
ALTER TABLE event_type ADD INDEX ( name );

DROP TABLE IF EXISTS event_type_email_template;
CREATE TABLE IF NOT EXISTS event_type_email_template (
  id int unsigned NOT NULL AUTO_INCREMENT,
  event_type_id int unsigned NOT NULL,
  language_id smallint unsigned NOT NULL,
  message text NOT NULL,
  subject varchar(255) NOT NULL,
  PRIMARY KEY (id)
);
ALTER TABLE event_type_email_template ADD INDEX ( language_id );

--
-- Table structure for LP custom storage API
--
DROP TABLE IF EXISTS stored_value;
CREATE TABLE IF NOT EXISTS stored_values (
	user_id INT NOT NULL,
	sco_id INT NOT NULL,
	course_id CHAR(40) NOT NULL,
	sv_key CHAR(64) NOT NULL,
	sv_value TEXT NOT NULL
);
ALTER TABLE stored_values ADD KEY (user_id, sco_id, course_id, sv_key);
ALTER TABLE stored_values ADD UNIQUE (user_id, sco_id, course_id, sv_key);

DROP TABLE IF EXISTS stored_value_stack;
CREATE TABLE IF NOT EXISTS stored_values_stack (
	user_id INT NOT NULL,
	sco_id INT NOT NULL,
	stack_order INT NOT NULL,
	course_id CHAR(40) NOT NULL,
	sv_key CHAR(64) NOT NULL,
	sv_value TEXT NOT NULL
);
ALTER TABLE stored_values_stack ADD KEY (user_id, sco_id, course_id, sv_key, stack_order);
ALTER TABLE stored_values_stack ADD UNIQUE (user_id, sco_id, course_id, sv_key, stack_order);


-- Course ranking

DROP TABLE IF EXISTS track_course_ranking;
CREATE TABLE IF NOT EXISTS track_course_ranking (
 id   int unsigned not null PRIMARY KEY AUTO_INCREMENT,
 c_id  int unsigned not null,
 session_id  int unsigned not null default 0,
 url_id  int unsigned not null default 0, 
 accesses int unsigned not null default 0,
 total_score int unsigned not null default 0,
 users int unsigned not null default 0,
 creation_date datetime not null
);

ALTER TABLE track_course_ranking ADD INDEX idx_tcc_cid (c_id);
ALTER TABLE track_course_ranking ADD INDEX idx_tcc_sid (session_id);
ALTER TABLE track_course_ranking ADD INDEX idx_tcc_urlid (url_id);
ALTER TABLE track_course_ranking ADD INDEX idx_tcc_creation_date (creation_date);

DROP TABLE IF EXISTS user_rel_course_vote;
CREATE TABLE IF NOT EXISTS user_rel_course_vote (
  id int unsigned not null AUTO_INCREMENT PRIMARY KEY,
  c_id int unsigned not null,
  user_id int unsigned not null,  
  session_id int unsigned not null default 0,
  url_id int unsigned not null default 0,
  vote int unsigned not null default 0
);

ALTER TABLE user_course_vote ADD INDEX idx_ucv_cid (c_id);
ALTER TABLE user_course_vote ADD INDEX idx_ucv_uid (user_id);
ALTER TABLE user_course_vote ADD INDEX idx_ucv_cuid (user_id, c_id);

-- Global chat
DROP TABLE IF EXISTS chat;
CREATE TABLE IF NOT EXISTS chat (
	id			INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	from_user	INTEGER,
	to_user		INTEGER,
	message		TEXT NOT NULL,
	sent		DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	recd		INTEGER UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id)	
);

ALTER TABLE chat ADD INDEX idx_chat_to_user (to_user);
ALTER TABLE chat ADD INDEX idx_chat_from_user (from_user);

-- Grade Model

CREATE TABLE grade_model (
  id INTEGER  NOT NULL AUTO_INCREMENT,
  name VARCHAR(255)  NOT NULL,
  description TEXT ,
  PRIMARY KEY (id)
);

CREATE TABLE grade_components (
  id INTEGER  NOT NULL AUTO_INCREMENT,
  percentage VARCHAR(255)  NOT NULL,
  title VARCHAR(255)  NOT NULL,
  acronym VARCHAR(255)  NOT NULL,
  grade_model_id INTEGER NOT NULL,
  PRIMARY KEY (id)
);

ALTER TABLE gradebook_category ADD COLUMN grade_model_id INT DEFAULT 0;