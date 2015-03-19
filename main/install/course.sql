
CREATE TABLE c_announcement(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  title text,
  content mediumtext,
  end_date date default NULL,
  display_order mediumint NOT NULL default 0,
  email_sent tinyint default 0,
  session_id int default 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_announcement ADD INDEX ( session_id );

CREATE TABLE c_announcement_attachment (
    id int NOT NULL auto_increment,
    c_id INT NOT NULL,
    path varchar(255) NOT NULL,
    comment text,
    size int NOT NULL default 0,
    announcement_id int NOT NULL,
    filename varchar(255) NOT NULL,
    PRIMARY KEY (id, c_id)
);

CREATE TABLE c_resource (
    id int unsigned NOT NULL auto_increment,
    c_id INT NOT NULL,
    source_type varchar(50) default NULL,
    source_id int unsigned default NULL,
    resource_type varchar(50) default NULL,
    resource_id int unsigned default NULL,
    PRIMARY KEY (id, c_id)
);

CREATE TABLE c_userinfo_content (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  user_id int unsigned NOT NULL,
  definition_id int unsigned NOT NULL,
  editor_ip varchar(39) default NULL,
  edition_time datetime default NULL,
  content text NOT NULL,
  PRIMARY KEY (id, c_id),
  KEY user_id (user_id)
);

CREATE TABLE c_userinfo_def (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  title varchar(80) NOT NULL default '',
  comment text,
  line_count tinyint unsigned NOT NULL default 5,
  rank tinyint unsigned NOT NULL default 0,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_forum_category(
 cat_id int NOT NULL auto_increment,
 c_id INT NOT NULL,
 cat_title varchar(255) NOT NULL default '',
 cat_comment text,
 cat_order int NOT NULL default 0,
 locked int NOT NULL default 0,
 session_id int unsigned NOT NULL default 0,
 PRIMARY KEY (cat_id, c_id)
);

ALTER TABLE c_forum_category ADD INDEX ( session_id );

CREATE TABLE c_forum_forum(
  forum_id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  forum_title varchar(255) NOT NULL default '',
  forum_comment text,
  forum_threads int default 0,
  forum_posts int default 0,
  forum_last_post int default 0,
  forum_category int default NULL,
  allow_anonymous int default NULL,
  allow_edit int default NULL,
  approval_direct_post varchar(20) default NULL,
  allow_attachments int default NULL,
  allow_new_threads int default NULL,
  default_view varchar(20) default NULL,
  forum_of_group varchar(20) default NULL,
  forum_group_public_private varchar(20) default 'public',
  forum_order int default NULL,
  locked int NOT NULL default 0,
  session_id int NOT NULL default 0,
  forum_image varchar(255) NOT NULL default '',
  start_time datetime NOT NULL default '0000-00-00 00:00:00',
  end_time datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (forum_id, c_id)
);

CREATE TABLE c_forum_thread  (
 thread_id int NOT NULL auto_increment,
 c_id INT NOT NULL,
 thread_title varchar(255) default NULL,
 forum_id int default NULL,
 thread_replies int default 0,
 thread_poster_id int default NULL,
 thread_poster_name varchar(100) default '',
 thread_views int default 0,
 thread_last_post int default NULL,
 thread_date datetime default '0000-00-00 00:00:00',
 thread_sticky tinyint unsigned default 0,
 locked int NOT NULL default 0,
 session_id int unsigned default NULL,
 thread_title_qualify varchar(255) default '',
 thread_qualify_max float(6,2) UNSIGNED NOT NULL default 0,
 thread_close_date datetime default '0000-00-00 00:00:00',
 thread_weight float(6,2) UNSIGNED NOT NULL default 0,
 PRIMARY KEY (thread_id, c_id)
);

ALTER TABLE c_forum_thread ADD INDEX idx_forum_thread_forum_id (forum_id);

CREATE TABLE c_forum_post (
    post_id int NOT NULL auto_increment,
    c_id INT NOT NULL,
    post_title varchar(250) default NULL,
    post_text text,
    thread_id int default 0,
    forum_id int default 0,
    poster_id int default 0,
    poster_name varchar(100) default '',
    post_date datetime default '0000-00-00 00:00:00',
    post_notification tinyint default 0,
    post_parent_id int default 0,
    visible tinyint default 1,
    PRIMARY KEY (post_id, c_id),
    KEY poster_id (poster_id),
    KEY forum_id (forum_id)
);

ALTER TABLE c_forum_post ADD INDEX idx_forum_post_thread_id (thread_id);
ALTER TABLE c_forum_post ADD INDEX idx_forum_post_visible (visible);

CREATE TABLE c_forum_mailcue  (
 id int NOT NULL auto_increment,
 c_id INT NOT NULL,
 user_id int default NULL,
 thread_id int default NULL,
 post_id int default NULL,
 PRIMARY KEY (id, c_id, thread_id, user_id, post_id )
);

CREATE TABLE  c_forum_attachment  (
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  path varchar(255) NOT NULL,
  comment text,
  size int NOT NULL default 0,
  post_id int NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_forum_notification(
    id int NOT NULL auto_increment,
    c_id INT NOT NULL,
    user_id int,
    forum_id int,
    thread_id int,
    post_id int,
    KEY user_id (user_id),
    KEY forum_id (forum_id),
    PRIMARY KEY (id, c_id, user_id, forum_id, thread_id, post_id )
);

CREATE TABLE c_forum_thread_qualify  (
  id int unsigned AUTO_INCREMENT,
  c_id INT NOT NULL,
  user_id int unsigned NOT NULL,
  thread_id int NOT NULL,
  qualify float(6,2) NOT NULL default 0,
  qualify_user_id int  default NULL,
  qualify_time datetime default '0000-00-00 00:00:00',
  session_id int  default NULL,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_forum_thread_qualify ADD INDEX (user_id, thread_id);

CREATE TABLE c_forum_thread_qualify_log  (
  id int unsigned AUTO_INCREMENT,
  c_id INT NOT NULL,
  user_id int unsigned NOT NULL,
  thread_id int NOT NULL,
  qualify float(6,2) NOT NULL default 0,
  qualify_user_id int default NULL,
  qualify_time datetime default '0000-00-00 00:00:00',
  session_id int default NULL,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_forum_thread_qualify_log  ADD INDEX (user_id, thread_id);

CREATE TABLE c_quiz (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  title varchar(255) NOT NULL,
  description text default NULL,
  sound varchar(255) default NULL,
  type tinyint unsigned NOT NULL default 1,
  random int NOT NULL default 0,
  random_answers tinyint unsigned NOT NULL default 0,
  active tinyint NOT NULL default 0,
  results_disabled INT UNSIGNED NOT NULL DEFAULT 0,
  access_condition TEXT DEFAULT NULL,
  max_attempt int NOT NULL default 0,
  start_time datetime NOT NULL default '0000-00-00 00:00:00',
  end_time datetime NOT NULL default '0000-00-00 00:00:00',
  feedback_type int NOT NULL default 0,
  expired_time int NOT NULL default '0',
  session_id int default 0,
  propagate_neg INT NOT NULL DEFAULT 0,
  review_answers INT NOT NULL DEFAULT 0,
  random_by_category INT NOT NULL DEFAULT 0,
  text_when_finished TEXT default NULL,
  display_category_name INT NOT NULL DEFAULT 1,
  pass_percentage INT DEFAULT NULL,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_quiz  ADD INDEX ( session_id );

CREATE TABLE c_quiz_question(
  id int unsigned NOT NULL auto_increment,
  question TEXT NOT NULL,
  description text default NULL,
  ponderation float(6,2) NOT NULL default 0,
  position mediumint unsigned NOT NULL default 1,
  type    tinyint unsigned NOT NULL default 2,
  picture varchar(50) default NULL,
  level   int unsigned NOT NULL default 0,
  extra   varchar(255) default NULL,
  question_code char(10) default '',
  c_id INT NOT NULL,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_quiz_question  ADD INDEX (position);

CREATE TABLE c_quiz_answer(
  id int unsigned NOT NULL,
  c_id INT NOT NULL,
  id_auto int NOT NULL AUTO_INCREMENT,
  question_id int unsigned NOT NULL,
  answer text NOT NULL,
  correct mediumint unsigned default NULL,
  comment text default NULL,
  ponderation float(6,2) NOT NULL default 0,
  position mediumint unsigned NOT NULL default 1,
  hotspot_coordinates text,
  hotspot_type enum('square','circle','poly','delineation','oar') default NULL,
  destination text NOT NULL,
  answer_code char(10) default '',
  PRIMARY KEY (id_auto, c_id)
);

CREATE TABLE c_quiz_question_option  (
  id          int NOT NULL auto_increment,
  c_id INT NOT NULL,
  question_id int NOT NULL,
  name        varchar(255),
  position    int unsigned NOT NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_quiz_rel_question  (
  c_id INT NOT NULL,
  question_id int unsigned NOT NULL,
  exercice_id int unsigned NOT NULL,
  question_order int unsigned NOT NULL default 1,
  PRIMARY KEY (c_id, question_id, exercice_id)
);

CREATE TABLE c_quiz_question_category  (
  id int NOT NULL AUTO_INCREMENT,
  c_id INT NOT NULL,
  title varchar(255) NOT NULL,
  description text NOT NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_quiz_question_rel_category  (
  c_id INT NOT NULL,
  question_id int NOT NULL,
  category_id int NOT NULL,
  PRIMARY KEY (c_id,question_id)
);

CREATE TABLE c_course_description(
  id int UNSIGNED NOT NULL auto_increment,
  c_id INT NOT NULL,
  title VARCHAR(255),
  content TEXT,
  session_id int default 0,
  description_type tinyint unsigned NOT NULL default 0,
  progress INT NOT NULL default 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_course_description  ADD INDEX ( session_id );

CREATE TABLE c_tool(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  name varchar(255) NOT NULL,
  link varchar(255) NOT NULL,
  image varchar(255) default NULL,
  visibility tinyint unsigned default 0,
  admin varchar(255) default NULL,
  address varchar(255) default NULL,
  added_tool tinyint unsigned default 1,
  target enum('_self','_blank') NOT NULL default '_self',
  category varchar(20) not null default 'authoring',
  session_id int default 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_tool ADD INDEX ( session_id );

CREATE TABLE c_calendar_event(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  title varchar(255) NOT NULL,
  content text,
  start_date datetime NOT NULL default '0000-00-00 00:00:00',
  end_date datetime NOT NULL default '0000-00-00 00:00:00',
  parent_event_id INT NULL,
  session_id int unsigned NOT NULL default 0,
  all_day INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_calendar_event ADD INDEX ( session_id );

CREATE TABLE c_calendar_event_repeat(
  c_id INT NOT NULL,
  cal_id INT DEFAULT 0 NOT NULL,
  cal_type VARCHAR(20),
  cal_end INT,
  cal_frequency INT DEFAULT 1,
  cal_days CHAR(7),
  PRIMARY KEY (c_id, cal_id)
);

CREATE TABLE c_calendar_event_repeat_not  (
  c_id INT NOT NULL,
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  PRIMARY KEY (c_id, cal_id, cal_date )
);

CREATE TABLE  c_calendar_event_attachment  (
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  path varchar(255) NOT NULL,
  comment text,
  size int NOT NULL default 0,
  agenda_id int NOT NULL,
  filename varchar(255) NOT NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_document (
    id int unsigned NOT NULL auto_increment,
    c_id INT NOT NULL,
    path varchar(255) NOT NULL default '',
    comment text,
    title varchar(255) default NULL,
    filetype set('file','folder') NOT NULL default 'file',
    size int NOT NULL default 0,
    readonly TINYINT UNSIGNED NOT NULL,
    session_id int UNSIGNED NOT NULL default 0,
    PRIMARY KEY (id, c_id)
);

CREATE TABLE c_student_publication  (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  url varchar(255) default NULL,
  title varchar(255) default NULL,
  description text default NULL,
  author varchar(255) default NULL,
  active tinyint default NULL,
  accepted tinyint default 0,
  post_group_id int DEFAULT 0 NOT NULL,
  sent_date datetime NOT NULL default '0000-00-00 00:00:00',
  filetype set('file','folder') NOT NULL default 'file',
  has_properties int UNSIGNED NOT NULL DEFAULT 0,
  view_properties tinyint NULL,
  qualification float(6,2) UNSIGNED NOT NULL DEFAULT 0,
  date_of_qualification datetime NOT NULL default '0000-00-00 00:00:00',
  parent_id INT UNSIGNED NOT NULL DEFAULT 0,
  qualificator_id INT UNSIGNED NOT NULL DEFAULT 0,
  weight float(6,2) UNSIGNED NOT NULL default 0,
  session_id INT UNSIGNED NOT NULL default 0,
  user_id INTEGER  NOT NULL,
  allow_text_assignment INTEGER NOT NULL DEFAULT 0,
  contains_file INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_student_publication_assignment(
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  expires_on datetime NOT NULL default '0000-00-00 00:00:00',
  ends_on datetime NOT NULL default '0000-00-00 00:00:00',
  add_to_calendar tinyint NOT NULL,
  enable_qualification tinyint NOT NULL,
  publication_id int NOT NULL,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_student_publication  ADD INDEX ( session_id );

CREATE TABLE c_link(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  url TEXT NOT NULL,
  title varchar(150) default NULL,
  description text,
  category_id int unsigned default NULL,
  display_order int unsigned NOT NULL default 0,
  on_homepage enum('0','1') NOT NULL default '0',
  target char(10) default '_self',
  session_id int default 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_link  ADD INDEX ( session_id );

CREATE TABLE c_link_category (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  category_title varchar(255) NOT NULL,
  description text,
  display_order mediumint unsigned NOT NULL default 0,
  session_id int default 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_link_category  ADD INDEX ( session_id );

CREATE TABLE c_wiki(
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  page_id int NOT NULL default 0,
  reflink varchar(255) NOT NULL default 'index',
  title varchar(255) NOT NULL,
  content mediumtext NOT NULL,
  user_id int NOT NULL default 0,
  group_id int DEFAULT NULL,
  dtime datetime NOT NULL default '0000-00-00 00:00:00',
  addlock int NOT NULL default 1,
  editlock int NOT NULL default 0,
  visibility int NOT NULL default 1,
  addlock_disc int NOT NULL default 1,
  visibility_disc int NOT NULL default 1,
  ratinglock_disc int NOT NULL default 1,
  assignment int NOT NULL default 0,
  comment text NOT NULL,
  progress text NOT NULL,
  score int NULL default 0,
  version int default NULL,
  is_editing int NOT NULL default 0,
  time_edit datetime NOT NULL default '0000-00-00 00:00:00',
  hits int default 0,
  linksto text NOT NULL,
  tag text NOT NULL,
  user_ip varchar(39) NOT NULL,
  session_id int default 0,
  PRIMARY KEY (id, c_id),
  KEY reflink (reflink),
  KEY group_id (group_id),
  KEY page_id (page_id),
  KEY session_id (session_id)
);

CREATE TABLE c_wiki_conf  (
  c_id INT NOT NULL,
  page_id int NOT NULL default 0,
  task text NOT NULL,
  feedback1 text NOT NULL,
  feedback2 text NOT NULL,
  feedback3 text NOT NULL,
  fprogress1 varchar(3) NOT NULL,
  fprogress2 varchar(3) NOT NULL,
  fprogress3 varchar(3) NOT NULL,
  max_size int default NULL,
  max_text int default NULL,
  max_version int default NULL,
  startdate_assig datetime NOT NULL default '0000-00-00 00:00:00',
  enddate_assig datetime  NOT NULL default '0000-00-00 00:00:00',
  delayedsubmit int NOT NULL default 0,
  KEY page_id (page_id),
  PRIMARY KEY  ( c_id, page_id )
);

CREATE TABLE c_wiki_discuss  (
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  publication_id int NOT NULL default 0,
  userc_id int NOT NULL default 0,
  comment text NOT NULL,
  p_score varchar(255) default NULL,
  dtime datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_wiki_mailcue  (
  c_id INT NOT NULL,
  id int NOT NULL,
  user_id int NOT NULL,
  type text NOT NULL,
  group_id int DEFAULT NULL,
  session_id int default 0,
  KEY (c_id, id),
  PRIMARY KEY  ( c_id, id, user_id )
);

CREATE TABLE c_online_connected  (
  c_id INT NOT NULL,
  user_id int unsigned NOT NULL,
  last_connection datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (c_id, user_id)
);

CREATE TABLE c_online_link (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  name char(50) NOT NULL default '',
  url char(100) NOT NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_chat_connected (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  user_id int unsigned NOT NULL default '0',
  last_connection datetime NOT NULL default '0000-00-00 00:00:00',
  session_id  INT NOT NULL default 0,
  to_group_id INT NOT NULL default 0,
  PRIMARY KEY  (id, c_id, user_id, last_connection)
);

ALTER TABLE c_chat_connected ADD INDEX char_connected_index(user_id, session_id, to_group_id);

CREATE TABLE c_group_info(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  name varchar(100) default NULL,
  status tinyint DEFAULT 1,
  category_id int unsigned NOT NULL default 0,
  description text,
  max_student int unsigned NOT NULL default 8,
  doc_state tinyint unsigned NOT NULL default 1,
  calendar_state tinyint unsigned NOT NULL default 0,
  work_state tinyint unsigned NOT NULL default 0,
  announcements_state tinyint unsigned NOT NULL default 0,
  forum_state tinyint unsigned NOT NULL default 0,
  wiki_state tinyint unsigned NOT NULL default 1,
  chat_state tinyint unsigned NOT NULL default 1,
  secret_directory varchar(255) default NULL,
  self_registration_allowed tinyint unsigned NOT NULL default '0',
  self_unregistration_allowed tinyint unsigned NOT NULL default '0',
  session_id int unsigned NOT NULL default 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_group_info ADD INDEX ( session_id );

CREATE TABLE c_group_category(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  title varchar(255) NOT NULL default '',
  description text NOT NULL,
  doc_state tinyint unsigned NOT NULL default 1,
  calendar_state tinyint unsigned NOT NULL default 1,
  work_state tinyint unsigned NOT NULL default 1,
  announcements_state tinyint unsigned NOT NULL default 1,
  forum_state tinyint unsigned NOT NULL default 1,
  wiki_state tinyint unsigned NOT NULL default 1,
  chat_state tinyint unsigned NOT NULL default 1,
  max_student int unsigned NOT NULL default 8,
  self_reg_allowed tinyint unsigned NOT NULL default 0,
  self_unreg_allowed tinyint unsigned NOT NULL default 0,
  groups_per_user int unsigned NOT NULL default 0,
  display_order int unsigned NOT NULL default 0,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_group_rel_user(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  user_id int unsigned NOT NULL,
  group_id int unsigned NOT NULL default 0,
  status int NOT NULL default 0,
  role char(50) NOT NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_group_rel_tutor(
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  user_id int NOT NULL,
  group_id int NOT NULL default 0,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_item_property(
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  tool varchar(100) NOT NULL default '',
  insert_user_id int unsigned NOT NULL default '0',
  insert_date datetime NOT NULL default '0000-00-00 00:00:00',
  lastedit_date datetime NOT NULL default '0000-00-00 00:00:00',
  ref int NOT NULL default '0',
  lastedit_type varchar(100) NOT NULL default '',
  lastedit_user_id int unsigned NOT NULL default '0',
  to_group_id int unsigned default NULL,
  to_user_id int unsigned default NULL,
  visibility tinyint NOT NULL default '1',
  start_visible datetime NOT NULL default '0000-00-00 00:00:00',
  end_visible datetime NOT NULL default '0000-00-00 00:00:00',
  id_session INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_item_property ADD INDEX idx_item_property_toolref (tool,ref);

CREATE TABLE c_tool_intro(
  id varchar(50) NOT NULL,
  c_id INT NOT NULL,
  intro_text MEDIUMTEXT NOT NULL,
  session_id INT  NOT NULL DEFAULT 0,
  PRIMARY KEY (id, c_id, session_id)
);

CREATE TABLE c_dropbox_file  (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  uploader_id int unsigned NOT NULL default 0,
  filename varchar(250) NOT NULL default '',
  filesize int unsigned NOT NULL,
  title varchar(250) default '',
  description varchar(250) default '',
  author varchar(250) default '',
  upload_date datetime NOT NULL default '0000-00-00 00:00:00',
  last_upload_date datetime NOT NULL default '0000-00-00 00:00:00',
  cat_id int NOT NULL default 0,
  session_id int UNSIGNED NOT NULL,
  PRIMARY KEY (id, c_id),
  UNIQUE KEY UN_filename (filename)
);

ALTER TABLE c_dropbox_file ADD INDEX ( session_id );

CREATE TABLE c_dropbox_post  (
  c_id INT NOT NULL,
  file_id int unsigned NOT NULL,
  dest_user_id int unsigned NOT NULL default 0,
  feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
  feedback text default '',
  cat_id int NOT NULL default 0,
  session_id int UNSIGNED NOT NULL,
  PRIMARY KEY (c_id, file_id, dest_user_id)
);

ALTER TABLE c_dropbox_post ADD INDEX ( session_id );

CREATE TABLE c_dropbox_person  (
  c_id INT NOT NULL,
  file_id int unsigned NOT NULL,
  user_id int unsigned NOT NULL default 0,
  PRIMARY KEY (c_id, file_id, user_id)
);

CREATE TABLE c_dropbox_category  (
  cat_id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  cat_name text NOT NULL,
  received tinyint unsigned NOT NULL default 0,
  sent tinyint unsigned NOT NULL default 0,
  user_id int NOT NULL default 0,
  session_id int NOT NULL default 0,
  PRIMARY KEY(cat_id, c_id)
);

ALTER TABLE c_dropbox_category ADD INDEX ( session_id );

CREATE TABLE c_dropbox_feedback (
  feedback_id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  file_id int NOT NULL default 0,
  author_user_id int NOT NULL default 0,
  feedback text NOT NULL,
  feedback_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (feedback_id, c_id),
  KEY file_id (file_id),
  KEY author_user_id (author_user_id)
);

CREATE TABLE IF NOT EXISTS c_lp (
  id             int unsigned        auto_increment,
  c_id INT NOT NULL,
  lp_type        int unsigned   not null,
  name           varchar(255)        not null,
  ref            tinytext            null,
  description    text                null,
  path           text                not null,
  force_commit   tinyint unsigned    not null default 0,
  default_view_mod   char(32)        not null default 'embedded',
  default_encoding   char(32)        not null default 'UTF-8',
  display_order  int unsigned        not null default 0,
  content_maker  tinytext            not null default '',
  content_local  varchar(32)         not null default 'local',
  content_license    text            not null default '',
  prevent_reinit tinyint unsigned    not null default 1,
  js_lib         tinytext            not null default '',
  debug          tinyint unsigned    not null default 0,
  theme          varchar(255)        not null default '',
  preview_image  varchar(255)        not null default '',
  author         varchar(255)        not null default '',
  session_id     int unsigned        not null default 0,
  prerequisite   int unsigned        not null default 0,
  hide_toc_frame tinyint             NOT NULL DEFAULT 0,
  seriousgame_mode tinyint           NOT NULL DEFAULT 0,
  use_max_score  int unsigned        not null default 1,
  autolunch      int unsigned        not null default 0,
  created_on     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  modified_on    DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  publicated_on  DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  expired_on     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id, c_id)
);

CREATE TABLE IF NOT EXISTS c_lp_view (
  id             int unsigned        auto_increment,
  c_id INT NOT NULL,
  lp_id          int unsigned        not null,
  user_id        int unsigned        not null,
  view_count     int unsigned   not null default 0,
  last_item      int unsigned        not null default 0,
  progress       int unsigned        default 0,
  session_id     int                 not null default 0,
  PRIMARY KEY  (id, c_id)
);

ALTER TABLE c_lp_view ADD INDEX (lp_id);
ALTER TABLE c_lp_view ADD INDEX (user_id);
ALTER TABLE c_lp_view ADD INDEX (session_id);

CREATE TABLE IF NOT EXISTS c_lp_item (
  id              int unsigned       auto_increment,
  c_id INT NOT NULL,
  lp_id          int unsigned        not null,
  item_type      char(32)            not null default 'dokeos_document',
  ref            tinytext            not null default '',
  title          varchar(511)        not null,
  description    varchar(511)        not null default '',
  path           text                not null,
  min_score      float unsigned      not null default 0,
  max_score      float unsigned      default 100,
  mastery_score  float unsigned      null,
  parent_item_id     int unsigned    not null default 0,
  previous_item_id   int unsigned    not null default 0,
  next_item_id       int unsigned    not null default 0,
  display_order      int unsigned    not null default 0,
  prerequisite   text                null default null,
  parameters     text                null,
  launch_data    text                not null default '',
  max_time_allowed   char(13)        NULL default '',
  terms          TEXT                NULL,
  search_did     INT                 NULL,
  audio          VARCHAR(250),
  prerequisite_min_score float,
  prerequisite_max_score float,
  PRIMARY KEY  (id, c_id)
);

ALTER TABLE c_lp_item ADD INDEX (lp_id);
ALTER TABLE c_lp_item ADD INDEX idx_c_lp_item_cid_lp_id (c_id, lp_id);

CREATE TABLE IF NOT EXISTS c_lp_item_view (
  id bigint unsigned auto_increment,
  c_id INT NOT NULL,
  lp_item_id     int unsigned    not null,
  lp_view_id     int unsigned    not null,
  view_count     int unsigned    not null default 0,
  start_time     int unsigned    not null,
  total_time     int unsigned    not null default 0,
  score          float unsigned  not null default 0,
  status         char(32)        not null default 'not attempted',
  suspend_data   longtext null default '',
  lesson_location    text        null default '',
  core_exit      varchar(32)     not null default 'none',
  max_score      varchar(8)      default '',
  PRIMARY KEY  (id, c_id)
);

ALTER TABLE c_lp_item_view ADD INDEX (lp_item_id);
ALTER TABLE c_lp_item_view ADD INDEX (lp_view_id);
ALTER TABLE c_lp_item_view ADD INDEX idx_c_lp_item_view_cid_lp_view_id_lp_item_id (c_id, lp_view_id, lp_item_id);

CREATE TABLE IF NOT EXISTS c_lp_iv_interaction(
  id             bigint unsigned     auto_increment,
  c_id INT NOT NULL,
  order_id       int unsigned   not null default 0,
  lp_iv_id       bigint unsigned     not null,
  interaction_id varchar(255)        not null default '',
  interaction_type   varchar(255)    not null default '',
  weighting          double          not null default 0,
  completion_time    varchar(16)     not null default '',
  correct_responses  text            not null default '',
  student_response   text            not null default '',
  result         varchar(255)        not null default '',
  latency        varchar(16)         not null default '',
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_lp_iv_interaction ADD INDEX (lp_iv_id);

CREATE TABLE IF NOT EXISTS c_lp_iv_objective(
  id             bigint unsigned     auto_increment,
  c_id INT NOT NULL,
  lp_iv_id       bigint unsigned     not null,
  order_id       int unsigned   not null default 0,
  objective_id   varchar(255)        not null default '',
  score_raw      float unsigned      not null default 0,
  score_max      float unsigned      not null default 0,
  score_min      float unsigned      not null default 0,
  status         char(32)            not null default 'not attempted',
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_lp_iv_objective ADD INDEX (lp_iv_id);

CREATE TABLE c_blog (
  blog_id int NOT NULL AUTO_INCREMENT ,
  c_id INT NOT NULL,
  blog_name varchar( 250 ) NOT NULL default '',
  blog_subtitle varchar( 250 ) default NULL ,
  date_creation datetime NOT NULL default '0000-00-00 00:00:00',
  visibility tinyint unsigned NOT NULL default 0,
  session_id int default 0,
  PRIMARY KEY (blog_id, c_id)
);

ALTER TABLE c_blog  ADD INDEX ( session_id );

CREATE TABLE c_blog_comment (
    comment_id int NOT NULL AUTO_INCREMENT ,
    c_id INT NOT NULL,
    title varchar( 250 ) NOT NULL default '',
    comment longtext NOT NULL ,
    author_id int NOT NULL default 0,
    date_creation datetime NOT NULL default '0000-00-00 00:00:00',
    blog_id int NOT NULL default 0,
    post_id int NOT NULL default 0,
    task_id int default NULL ,
    parent_comment_id int NOT NULL default 0,
    PRIMARY KEY (comment_id, c_id)
);

CREATE TABLE c_blog_post(
    post_id int NOT NULL AUTO_INCREMENT ,
    c_id INT NOT NULL,
    title varchar( 250 ) NOT NULL default '',
    full_text longtext NOT NULL ,
    date_creation datetime NOT NULL default '0000-00-00 00:00:00',
    blog_id int NOT NULL default 0,
    author_id int NOT NULL default 0,
    PRIMARY KEY (post_id, c_id)
);

CREATE TABLE c_blog_rating(
    rating_id int NOT NULL AUTO_INCREMENT ,
    c_id INT NOT NULL,
    blog_id int NOT NULL default 0,
    rating_type enum( 'post', 'comment' ) NOT NULL default 'post',
    item_id int NOT NULL default 0,
    user_id int NOT NULL default 0,
    rating int NOT NULL default 0,
    PRIMARY KEY (rating_id, c_id)
);

CREATE TABLE c_blog_rel_user(
    c_id INT NOT NULL,
    blog_id int NOT NULL default 0,
    user_id int NOT NULL default 0,
    PRIMARY KEY (c_id, blog_id , user_id )
);

CREATE TABLE c_blog_task(
    task_id int NOT NULL AUTO_INCREMENT ,
    c_id INT NOT NULL,
    blog_id int NOT NULL default 0,
    title varchar( 250 ) NOT NULL default '',
    description text NOT NULL ,
    color varchar( 10 ) NOT NULL default '',
    system_task tinyint unsigned NOT NULL default 0,
    PRIMARY KEY (task_id, c_id)
);

CREATE TABLE c_blog_task_rel_user  (
    c_id INT NOT NULL,
    blog_id int NOT NULL default 0,
    user_id int NOT NULL default 0,
    task_id int NOT NULL default 0,
    target_date date NOT NULL default '0000-00-00',
    PRIMARY KEY (c_id, blog_id , user_id , task_id )
);

CREATE TABLE  c_blog_attachment  (
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  path varchar(255) NOT NULL COMMENT 'the real filename',
  comment text,
  size int NOT NULL default '0',
  post_id int NOT NULL,
  filename varchar(255) NOT NULL COMMENT 'the user s file name',
  blog_id int NOT NULL,
  comment_id int NOT NULL default '0',
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_permission_group  (
    id int NOT NULL AUTO_INCREMENT,
    c_id INT NOT NULL,
    group_id int NOT NULL default 0,
    tool varchar( 250 ) NOT NULL default '',
    action varchar( 250 ) NOT NULL default '',
    PRIMARY KEY (id, c_id)
);

CREATE TABLE c_permission_user  (
    id int NOT NULL AUTO_INCREMENT ,
    c_id INT NOT NULL,
    user_id int NOT NULL default 0,
    tool varchar( 250 ) NOT NULL default '',
    action varchar( 250 ) NOT NULL default '',
    PRIMARY KEY (id, c_id)
);

CREATE TABLE c_permission_task(
    id int NOT NULL AUTO_INCREMENT,
    c_id INT NOT NULL,
    task_id int NOT NULL default 0,
    tool varchar( 250 ) NOT NULL default '',
    action varchar( 250 ) NOT NULL default '',
    PRIMARY KEY (id, c_id)
);

CREATE TABLE c_role(
    role_id int NOT NULL AUTO_INCREMENT,
    c_id INT NOT NULL,
    role_name varchar( 250 ) NOT NULL default '',
    role_comment text,
    default_role tinyint default 0,
    PRIMARY KEY (role_id, c_id)
);

CREATE TABLE c_role_group(
    id int NOT NULL AUTO_INCREMENT,
    c_id INT NOT NULL,
    role_id int NOT NULL default 0,
    scope varchar( 20 ) NOT NULL default 'course',
    group_id int NOT NULL default 0,
    PRIMARY KEY  (id, c_id, group_id )
);

CREATE TABLE c_role_permissions(
    id int NOT NULL AUTO_INCREMENT,
    c_id INT NOT NULL,
    role_id int NOT NULL default 0,
    tool varchar( 250 ) NOT NULL default '',
    action varchar( 50 ) NOT NULL default '',
    default_perm tinyint NOT NULL default 0,
    PRIMARY KEY  (id, c_id, role_id, tool, action )
);

CREATE TABLE c_role_user  (
    c_id INT NOT NULL,
    role_id int NOT NULL default 0,
    scope varchar( 20 ) NOT NULL default 'course',
    user_id int NOT NULL default 0,
    PRIMARY KEY  ( c_id, role_id, user_id )
);

CREATE TABLE c_course_setting  (
  id          int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  variable    varchar(255) NOT NULL default '',
  subkey      varchar(255) default NULL,
  type        varchar(255) default NULL,
  category    varchar(255) default NULL,
  value       varchar(255) NOT NULL default '',
  title       varchar(255) NOT NULL default '',
  comment     varchar(255) default NULL,
  subkeytext  varchar(255) default NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_survey  (
  survey_id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  code varchar(20) default NULL,
  title text default NULL,
  subtitle text default NULL,
  author varchar(20) default NULL,
  lang varchar(20) default NULL,
  avail_from date default NULL,
  avail_till date default NULL,
  is_shared char(1) default '1',
  template varchar(20) default NULL,
  intro text,
  surveythanks text,
  creation_date datetime NOT NULL default '0000-00-00 00:00:00',
  invited int NOT NULL,
  answered int NOT NULL,
  invite_mail text NOT NULL,
  reminder_mail text NOT NULL,
  mail_subject VARCHAR( 255 ) NOT NULL,
  anonymous enum('0','1') NOT NULL default '0',
  access_condition TEXT DEFAULT NULL,
  shuffle bool NOT NULL default '0',
  one_question_per_page bool NOT NULL default '0',
  survey_version varchar(255) NOT NULL default '',
  parent_id int unsigned NOT NULL,
  survey_type int NOT NULL default 0,
  show_form_profile int NOT NULL default 0,
  form_fields TEXT NOT NULL,
  session_id int unsigned NOT NULL default 0,
  visible_results int unsigned DEFAULT 0,
  PRIMARY KEY  (survey_id, c_id)
);

ALTER TABLE c_survey  ADD INDEX ( session_id );
CREATE TABLE c_survey_invitation(
  survey_invitation_id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  survey_code varchar(20) NOT NULL,
  user varchar(250) NOT NULL,
  invitation_code varchar(250) NOT NULL,
  invitation_date datetime NOT NULL,
  reminder_date datetime NOT NULL,
  answered int NOT NULL default 0,
  session_id int UNSIGNED NOT NULL default 0,
  group_id INT NOT NULL,
  PRIMARY KEY (survey_invitation_id, c_id)
);

CREATE TABLE c_survey_question  (
  question_id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  survey_id int unsigned NOT NULL,
  survey_question text NOT NULL,
  survey_question_comment text NOT NULL,
  type varchar(250) NOT NULL,
  display varchar(10) NOT NULL,
  sort int NOT NULL,
  shared_question_id int,
  max_value int,
  survey_group_pri int unsigned NOT NULL default '0',
  survey_group_sec1 int unsigned NOT NULL default '0',
  survey_group_sec2 int unsigned NOT NULL default '0',
  PRIMARY KEY (question_id, c_id)
);

CREATE TABLE c_survey_question_option  (
  question_option_id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  question_id int unsigned NOT NULL,
  survey_id int unsigned NOT NULL,
  option_text text NOT NULL,
  sort int NOT NULL,
  value int NOT NULL default '0',
  PRIMARY KEY  (question_option_id, c_id)
);

CREATE TABLE c_survey_answer  (
  answer_id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  survey_id int unsigned NOT NULL,
  question_id int unsigned NOT NULL,
  option_id TEXT NOT NULL,
  value int unsigned NOT NULL,
  user varchar(250) NOT NULL,
  PRIMARY KEY  (answer_id, c_id)
);

CREATE TABLE c_survey_group(
  id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  name varchar(20) NOT NULL,
  description varchar(255) NOT NULL,
  survey_id int unsigned NOT NULL,
  PRIMARY KEY  (id, c_id)
);

CREATE TABLE c_glossary(
  glossary_id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  name varchar(255) NOT NULL,
  description text not null,
  display_order int,
  session_id int default 0,
  PRIMARY KEY (glossary_id, c_id)
);

ALTER TABLE c_glossary ADD INDEX ( session_id );

CREATE TABLE c_notebook (
  notebook_id int unsigned NOT NULL auto_increment,
  c_id INT NOT NULL,
  user_id int unsigned NOT NULL,
  course varchar(40) not null,
  session_id int NOT NULL default 0,
  title varchar(255) NOT NULL,
  description text NOT NULL,
  creation_date datetime NOT NULL default '0000-00-00 00:00:00',
  update_date datetime NOT NULL default '0000-00-00 00:00:00',
  status int,
  PRIMARY KEY (notebook_id, c_id)
);

CREATE TABLE c_attendance(
    id int NOT NULL auto_increment,
    c_id INT NOT NULL,
    name text NOT NULL,
    description TEXT NULL,
    active tinyint NOT NULL default 1,
    attendance_qualify_title varchar(255) NULL,
    attendance_qualify_max int NOT NULL default 0,
    attendance_weight float(6,2) NOT NULL default '0.0',
    session_id int NOT NULL default 0,
    locked int NOT NULL default 0,
    PRIMARY KEY (id, c_id)
);

ALTER TABLE c_attendance ADD INDEX (session_id);
ALTER TABLE c_attendance ADD INDEX (active);

CREATE TABLE c_attendance_sheet  (
    c_id INT NOT NULL,
    user_id int NOT NULL,
    attendance_calendar_id int NOT NULL,
    presence tinyint NOT NULL DEFAULT 0,
    PRIMARY KEY(c_id, user_id, attendance_calendar_id)
);

ALTER TABLE c_attendance_sheet  ADD INDEX (presence);

CREATE TABLE c_attendance_calendar(
    id int NOT NULL auto_increment,
    c_id INT NOT NULL,
    attendance_id int NOT NULL ,
    date_time datetime NOT NULL default '0000-00-00 00:00:00',
    done_attendance tinyint NOT NULL default 0,
    PRIMARY KEY(id, c_id)
);

ALTER TABLE c_attendance_calendar ADD INDEX (attendance_id);
ALTER TABLE c_attendance_calendar ADD INDEX (done_attendance);

CREATE TABLE c_attendance_result(
    id int NOT NULL auto_increment,
    c_id INT NOT NULL,
    user_id int NOT NULL,
    attendance_id int NOT NULL,
    score int NOT NULL DEFAULT 0,
    PRIMARY KEY  (id, c_id)
);

ALTER TABLE c_attendance_result ADD INDEX (attendance_id);
ALTER TABLE c_attendance_result ADD INDEX (user_id);

CREATE TABLE c_attendance_sheet_log(
  id int  NOT NULL auto_increment,
  c_id INT NOT NULL,
  attendance_id int  NOT NULL DEFAULT 0,
  lastedit_date datetime  NOT NULL DEFAULT '0000-00-00 00:00:00',
  lastedit_type varchar(200)  NOT NULL,
  lastedit_user_id int  NOT NULL DEFAULT 0,
  calendar_date_value datetime NULL,
  PRIMARY KEY (id, c_id)
);

CREATE TABLE c_thematic(
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  title varchar(255) NOT NULL,
  content text NULL,
  display_order int unsigned NOT NULL DEFAULT 0,
  active tinyint NOT NULL DEFAULT 0,
  session_id int NOT NULL DEFAULT 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_thematic ADD INDEX (active, session_id);

CREATE TABLE c_thematic_plan(
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  thematic_id int NOT NULL,
  title varchar(255) NOT NULL,
  description text NULL,
  description_type int NOT NULL,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_thematic_plan ADD INDEX (thematic_id, description_type);

CREATE TABLE c_thematic_advance(
  id int NOT NULL auto_increment,
  c_id INT NOT NULL,
  thematic_id int NOT NULL,
  attendance_id int NOT NULL DEFAULT 0,
  content text NULL,
  start_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  duration int NOT NULL DEFAULT 0,
  done_advance tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (id, c_id)
);

ALTER TABLE c_thematic_advance ADD INDEX (thematic_id);

CREATE TABLE IF NOT EXISTS c_metadata (
  c_id INT NOT NULL,
  eid VARCHAR(250) NOT NULL,
  mdxmltext TEXT default '',
  md5 CHAR(32) default '',
  htmlcache1 TEXT default '',
  htmlcache2 TEXT default '',
  indexabletext TEXT default '',
  PRIMARY KEY (c_id, eid)
);

