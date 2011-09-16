<?php
/* For licensing terms, see /license.txt */
/**
 *	This is the database constants definition for Chamilo
 *  This file is called by database.lib.php and database.mysqli.lib.php  
 *  
 *  @todo the table constants have all to start with TABLE_
 *        This is because of the analogy with the tool constants TOOL_
 *
 *	@package chamilo.library 
 */

/**
 * CONSTANTS 
 */

// Main database tables
define('TABLE_MAIN_COURSE',                 'course');
define('TABLE_MAIN_USER',                   'user');
define('TABLE_MAIN_CLASS',                  'class');
define('TABLE_MAIN_ADMIN',                  'admin');
define('TABLE_MAIN_COURSE_CLASS',           'course_rel_class');
define('TABLE_MAIN_COURSE_USER',            'course_rel_user');
define('TABLE_MAIN_CLASS_USER',             'class_user');
define('TABLE_MAIN_CATEGORY',               'course_category');
define('TABLE_MAIN_COURSE_MODULE',          'course_module');
define('TABLE_MAIN_SYSTEM_ANNOUNCEMENTS',   'sys_announcement');
define('TABLE_MAIN_LANGUAGE',               'language');
define('TABLE_MAIN_SETTINGS_OPTIONS',       'settings_options');
define('TABLE_MAIN_SETTINGS_CURRENT',       'settings_current');
define('TABLE_MAIN_SESSION',                'session');
define('TABLE_MAIN_SESSION_CATEGORY',       'session_category');
define('TABLE_MAIN_SESSION_COURSE',         'session_rel_course');
define('TABLE_MAIN_SESSION_USER',           'session_rel_user');
define('TABLE_MAIN_SESSION_CLASS',          'session_rel_class');
define('TABLE_MAIN_SESSION_COURSE_USER',    'session_rel_course_rel_user');
define('TABLE_MAIN_SHARED_SURVEY',          'shared_survey');
define('TABLE_MAIN_SHARED_SURVEY_QUESTION', 'shared_survey_question');
define('TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION', 'shared_survey_question_option');
define('TABLE_MAIN_TEMPLATES',              'templates');
define('TABLE_MAIN_SYSTEM_TEMPLATE',        'system_template');
define('TABLE_MAIN_OPENID_ASSOCIATION',     'openid_association');
define('TABLE_MAIN_COURSE_REQUEST',         'course_request');

// Gradebook
define('TABLE_MAIN_GRADEBOOK_CATEGORY',     'gradebook_category');
define('TABLE_MAIN_GRADEBOOK_EVALUATION',   'gradebook_evaluation');
define('TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG', 'gradebook_linkeval_log');
define('TABLE_MAIN_GRADEBOOK_RESULT',       'gradebook_result');
define('TABLE_MAIN_GRADEBOOK_RESULT_LOG',   'gradebook_result_log');
define('TABLE_MAIN_GRADEBOOK_LINK',         'gradebook_link');
define('TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY','gradebook_score_display');
define('TABLE_MAIN_GRADEBOOK_CERTIFICATE',  'gradebook_certificate');

//Profiling
define('TABLE_MAIN_USER_FIELD',         'user_field');
define('TABLE_MAIN_USER_FIELD_OPTIONS', 'user_field_options');
define('TABLE_MAIN_USER_FIELD_VALUES',  'user_field_values');

//User tags
define('TABLE_MAIN_TAG',                'tag');
define('TABLE_MAIN_USER_REL_TAG',       'user_rel_tag');

//User groups
define('TABLE_MAIN_GROUP',              'groups');
define('TABLE_MAIN_USER_REL_GROUP',     'group_rel_user');
define('TABLE_MAIN_GROUP_REL_TAG',      'group_rel_tag');

// Search engine
define('TABLE_MAIN_SPECIFIC_FIELD',         'specific_field');
define('TABLE_MAIN_SPECIFIC_FIELD_VALUES',  'specific_field_values');
define('TABLE_MAIN_SEARCH_ENGINE_REF',      'search_engine_ref');

// Access URLs
define('TABLE_MAIN_ACCESS_URL', 'access_url');
define('TABLE_MAIN_ACCESS_URL_REL_USER',    'access_url_rel_user');
define('TABLE_MAIN_ACCESS_URL_REL_COURSE',  'access_url_rel_course');
define('TABLE_MAIN_ACCESS_URL_REL_SESSION', 'access_url_rel_session');

// Global calendar
define('TABLE_MAIN_SYSTEM_CALENDAR', 'sys_calendar');

// Reservation System
define('TABLE_MAIN_RESERVATION_ITEM',           'reservation_item');
define('TABLE_MAIN_RESERVATION_RESERVATION',    'reservation_main');
define('TABLE_MAIN_RESERVATION_SUBSCRIBTION',   'reservation_subscription');
define('TABLE_MAIN_RESERVATION_CATEGORY',       'reservation_category');
define('TABLE_MAIN_RESERVATION_ITEM_RIGHTS',    'reservation_item_rights');

// Social networking
define('TABLE_MAIN_USER_REL_USER', 'user_rel_user');
define('TABLE_MAIN_USER_FRIEND_RELATION_TYPE', 'user_friend_relation_type');

// Web services
define('TABLE_MAIN_USER_API_KEY',           'user_api_key');
define('TABLE_MAIN_COURSE_FIELD',           'course_field');
define('TABLE_MAIN_COURSE_FIELD_VALUES',    'course_field_values');
define('TABLE_MAIN_SESSION_FIELD',          'session_field');
define('TABLE_MAIN_SESSION_FIELD_VALUES',   'session_field_values');

// Message
define('TABLE_MAIN_MESSAGE', 'message');

// Term and conditions
define('TABLE_MAIN_LEGAL', 'legal');

// Dashboard blocks plugin
define('TABLE_MAIN_BLOCK', 'block');

// Statistic database tables 
define('TABLE_STATISTIC_TRACK_E_LASTACCESS',        'track_e_lastaccess');
define('TABLE_STATISTIC_TRACK_E_ACCESS',            'track_e_access');
define('TABLE_STATISTIC_TRACK_E_LOGIN',             'track_e_login');
define('TABLE_STATISTIC_TRACK_E_DOWNLOADS',         'track_e_downloads');
define('TABLE_STATISTIC_TRACK_E_LINKS',             'track_e_links');
define('TABLE_STATISTIC_TRACK_E_ONLINE',            'track_e_online');
define('TABLE_STATISTIC_TRACK_E_HOTPOTATOES',       'track_e_hotpotatoes');
define('TABLE_STATISTIC_TRACK_E_COURSE_ACCESS',     'track_e_course_access');
define('TABLE_STATISTIC_TRACK_E_EXERCICES',         'track_e_exercices');
define('TABLE_STATISTIC_TRACK_E_ATTEMPT',           'track_e_attempt');
define('TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING', 'track_e_attempt_recording');
define('TABLE_STATISTIC_TRACK_E_DEFAULT',           'track_e_default');
define('TABLE_STATISTIC_TRACK_E_UPLOADS',           'track_e_uploads');
define('TABLE_STATISTIC_TRACK_E_HOTSPOT',           'track_e_hotspot');
define('TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY',     'track_e_item_property');
define('TABLE_STATISTIC_TRACK_E_OPEN',              'track_e_open');

define('TABLE_STATISTIC_TRACK_FILTERED_TERMS',      'track_filtered_terms');


define('TABLE_STATISTIC_TRACK_C_BROWSERS',          'track_c_browsers');
define('TABLE_STATISTIC_TRACK_C_COUNTRIES',         'track_c_countries');
define('TABLE_STATISTIC_TRACK_C_OS',                'track_c_os');
define('TABLE_STATISTIC_TRACK_C_PROVIDERS',         'track_c_providers');
define('TABLE_STATISTIC_TRACK_C_REFERERS',          'track_c_referers');



// SCORM database tables
define('TABLE_SCORM_MAIN', 'scorm_main');
define('TABLE_SCORM_SCO_DATA', 'scorm_sco_data');

// Course tables
define('TABLE_AGENDA',                          'calendar_event');
define('TABLE_AGENDA_REPEAT',                   'calendar_event_repeat');
define('TABLE_AGENDA_REPEAT_NOT',               'calendar_event_repeat_not');
define('TABLE_AGENDA_ATTACHMENT',               'calendar_event_attachment');
define('TABLE_ANNOUNCEMENT',                    'announcement');
define('TABLE_ANNOUNCEMENT_ATTACHMENT',         'announcement_attachment');
define('TABLE_CHAT_CONNECTED',                  'chat_connected'); // @todo: probably no longer in use !!!
define('TABLE_COURSE_DESCRIPTION',              'course_description');
define('TABLE_DOCUMENT',                        'document');
define('TABLE_ITEM_PROPERTY',                   'item_property');
define('TABLE_LINK',                            'link');
define('TABLE_LINK_CATEGORY',                   'link_category');
define('TABLE_TOOL_LIST',                       'tool');
define('TABLE_TOOL_INTRO',                      'tool_intro');
define('TABLE_SCORMDOC',                        'scormdocument');
define('TABLE_STUDENT_PUBLICATION',             'student_publication');
define('TABLE_STUDENT_PUBLICATION_ASSIGNMENT',  'student_publication_assignment');
define('CHAT_CONNECTED_TABLE',                  'chat_connected');

// Course forum tables
define('TABLE_FORUM_CATEGORY',              'forum_category');
define('TABLE_FORUM',                       'forum_forum');
define('TABLE_FORUM_THREAD',                'forum_thread');
define('TABLE_FORUM_POST',                  'forum_post');
define('TABLE_FORUM_ATTACHMENT',            'forum_attachment');
define('TABLE_FORUM_MAIL_QUEUE',            'forum_mailcue');
define('TABLE_FORUM_THREAD_QUALIFY',        'forum_thread_qualify');
define('TABLE_FORUM_THREAD_QUALIFY_LOG',    'forum_thread_qualify_log');
define('TABLE_FORUM_NOTIFICATION',          'forum_notification');

// Course group tables
define('TABLE_GROUP',           'group_info');
define('TABLE_GROUP_USER',      'group_rel_user');
define('TABLE_GROUP_TUTOR',     'group_rel_tutor');
define('TABLE_GROUP_CATEGORY',  'group_category');

// Course dropbox tables
define('TABLE_DROPBOX_CATEGORY','dropbox_category');
define('TABLE_DROPBOX_FEEDBACK','dropbox_feedback');
define('TABLE_DROPBOX_POST',    'dropbox_post');
define('TABLE_DROPBOX_FILE',    'dropbox_file');
define('TABLE_DROPBOX_PERSON',  'dropbox_person');

// Course quiz (or test, or exercice) tables
define('TABLE_QUIZ_QUESTION',       'quiz_question');
define('TABLE_QUIZ_TEST',           'quiz');
define('TABLE_QUIZ_ANSWER',         'quiz_answer');
define('TABLE_QUIZ_TEST_QUESTION',  'quiz_rel_question');
define('TABLE_QUIZ_QUESTION_OPTION','quiz_question_option');

// Linked resource table
define('TABLE_LINKED_RESOURCES', 'resource');

// New SCORM tables
define('TABLE_LP_MAIN', 'lp');
define('TABLE_LP_ITEM', 'lp_item');
define('TABLE_LP_VIEW', 'lp_view');
define('TABLE_LP_ITEM_VIEW', 'lp_item_view');
define('TABLE_LP_IV_INTERACTION', 'lp_iv_interaction'); // IV = Item View
define('TABLE_LP_IV_OBJECTIVE', 'lp_iv_objective'); // IV = Item View

// Smartblogs (Kevin Van Den Haute::kevin@develop-it.be)
// Permission tables
define('TABLE_PERMISSION_USER', 'permission_user');
define('TABLE_PERMISSION_TASK', 'permission_task');
define('TABLE_PERMISSION_GROUP', 'permission_group');
// Role tables
define('TABLE_ROLE', 'role');
define('TABLE_ROLE_PERMISSION', 'role_permissions');
define('TABLE_ROLE_USER', 'role_user');
define('TABLE_ROLE_GROUP', 'role_group');
// Blog tables
define('TABLE_BLOGS', 'blog');
define('TABLE_BLOGS_POSTS', 'blog_post');
define('TABLE_BLOGS_COMMENTS', 'blog_comment');
define('TABLE_BLOGS_REL_USER', 'blog_rel_user');
define('TABLE_BLOGS_TASKS', 'blog_task');
define('TABLE_BLOGS_TASKS_REL_USER', 'blog_task_rel_user');
define('TABLE_BLOGS_RATING', 'blog_rating');
define('TABLE_BLOGS_ATTACHMENT', 'blog_attachment');
define('TABLE_BLOGS_TASKS_PERMISSIONS', 'permission_task');
//end of Smartblogs

// User information tables
define('TABLE_USER_INFO',           'userinfo_def');
define('TABLE_USER_INFO_CONTENT',   'userinfo_content');

// Course settings table
define('TABLE_COURSE_SETTING', 'course_setting');

// Course online tables
define('TABLE_ONLINE_LINK',     'online_link');
define('TABLE_ONLINE_CONNECTED','online_connected');

// User database
define('TABLE_PERSONAL_AGENDA',             'personal_agenda');
define('TABLE_PERSONAL_AGENDA_REPEAT',      'personal_agenda_repeat');
define('TABLE_PERSONAL_AGENDA_REPEAT_NOT',  'personal_agenda_repeat_not');
define('TABLE_USER_COURSE_CATEGORY',        'user_course_category');

// Survey
// @TODO: Are these MAIN tables or course tables?
// @TODO: Probably these constants are obsolete.
define('TABLE_MAIN_SURVEY',         'survey');
define('TABLE_MAIN_SURVEYQUESTION', 'questions');

// Survey
define('TABLE_SURVEY',                  'survey');
define('TABLE_SURVEY_QUESTION',         'survey_question');
define('TABLE_SURVEY_QUESTION_OPTION',  'survey_question_option');
define('TABLE_SURVEY_INVITATION',       'survey_invitation');
define('TABLE_SURVEY_ANSWER',           'survey_answer');
define('TABLE_SURVEY_QUESTION_GROUP',   'survey_group');
define('TABLE_SURVEY_REPORT',           'survey_report');

// Wiki tables
define('TABLE_WIKI',            'wiki');
define('TABLE_WIKI_CONF',       'wiki_conf');
define('TABLE_WIKI_DISCUSS',    'wiki_discuss');
define('TABLE_WIKI_MAILCUE',    'wiki_mailcue');

// Glossary
define('TABLE_GLOSSARY', 'glossary');

// Notebook
define('TABLE_NOTEBOOK', 'notebook');

// Message
define('TABLE_MESSAGE', 'message');
define('TABLE_MESSAGE_ATTACHMENT', 'message_attachment');

// Metadata
define('TABLE_METADATA', 'metadata');

// Attendance Sheet
define('TABLE_ATTENDANCE',          'attendance');
define('TABLE_ATTENDANCE_CALENDAR', 'attendance_calendar');
define('TABLE_ATTENDANCE_SHEET_LOG','attendance_sheet_log');

define('TABLE_ATTENDANCE_SHEET',    'attendance_sheet');
define('TABLE_ATTENDANCE_RESULT',   'attendance_result');

// Thematic
define('TABLE_THEMATIC','thematic');
define('TABLE_THEMATIC_PLAN', 'thematic_plan');
define('TABLE_THEMATIC_ADVANCE','thematic_advance');

// Careers, promotions, Usergroups
define('TABLE_CAREER',      'career');
define('TABLE_PROMOTION',   'promotion');

define('TABLE_USERGROUP',               'usergroup');
define('TABLE_USERGROUP_REL_USER',      'usergroup_rel_user');
define('TABLE_USERGROUP_REL_COURSE',    'usergroup_rel_course');
define('TABLE_USERGROUP_REL_SESSION',   'usergroup_rel_session');

// Mail notifications
define('TABLE_NOTIFICATION',               'notification');

