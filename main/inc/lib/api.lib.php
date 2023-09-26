<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\UserBundle\Entity\User;
use ChamiloSession as Session;
use League\OAuth2\Client\Provider\GenericProvider;
use PHPMailer\PHPMailer\OAuth;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Finder\Finder;

/**
 * This is a code library for Chamilo.
 * It is included by default in every Chamilo file (through including the global.inc.php)
 * This library is in process of being transferred to src/Chamilo/CoreBundle/Component/Utils/ChamiloApi.
 * Whenever a function is transferred to the ChamiloApi class, the places where it is used should include
 * the "use Chamilo\CoreBundle\Component\Utils\ChamiloApi;" statement.
 */

// PHP version requirement.
define('REQUIRED_PHP_VERSION', '7.4');
define('REQUIRED_MIN_MEMORY_LIMIT', '128');
define('REQUIRED_MIN_UPLOAD_MAX_FILESIZE', '10');
define('REQUIRED_MIN_POST_MAX_SIZE', '10');

// USER STATUS CONSTANTS
/** global status of a user: student */
define('STUDENT', 5);
/** global status of a user: course manager */
define('COURSEMANAGER', 1);
/** global status of a user: session admin */
define('SESSIONADMIN', 3);
/** global status of a user: human ressource manager */
define('DRH', 4);
/** global status of a user: human ressource manager */
define('ANONYMOUS', 6);
/** global status of a user: low security, necessary for inserting data from
 * the teacher through HTMLPurifier */
define('COURSEMANAGERLOWSECURITY', 10);
// Soft user status
define('PLATFORM_ADMIN', 11);
define('SESSION_COURSE_COACH', 12);
define('SESSION_GENERAL_COACH', 13);
define('COURSE_STUDENT', 14); //student subscribed in a course
define('SESSION_STUDENT', 15); //student subscribed in a session course
define('COURSE_TUTOR', 16); // student is tutor of a course (NOT in session)
define('STUDENT_BOSS', 17); // student is boss
define('INVITEE', 20);
define('HRM_REQUEST', 21); //HRM has request for vinculation with user
define('COURSE_EXLEARNER', 22);

// Table of status
$_status_list[COURSEMANAGER] = 'teacher'; // 1
$_status_list[SESSIONADMIN] = 'session_admin'; // 3
$_status_list[DRH] = 'drh'; // 4
$_status_list[STUDENT] = 'user'; // 5
$_status_list[ANONYMOUS] = 'anonymous'; // 6
$_status_list[INVITEE] = 'invited'; // 20

// COURSE VISIBILITY CONSTANTS
/** only visible for course admin */
define('COURSE_VISIBILITY_CLOSED', 0);
/** only visible for users registered in the course */
define('COURSE_VISIBILITY_REGISTERED', 1);
/** Open for all registered users on the platform */
define('COURSE_VISIBILITY_OPEN_PLATFORM', 2);
/** Open for the whole world */
define('COURSE_VISIBILITY_OPEN_WORLD', 3);
/** Invisible to all except admin */
define('COURSE_VISIBILITY_HIDDEN', 4);

define('COURSE_REQUEST_PENDING', 0);
define('COURSE_REQUEST_ACCEPTED', 1);
define('COURSE_REQUEST_REJECTED', 2);
define('DELETE_ACTION_ENABLED', false);

// EMAIL SENDING RECIPIENT CONSTANTS
define('SEND_EMAIL_EVERYONE', 1);
define('SEND_EMAIL_STUDENTS', 2);
define('SEND_EMAIL_TEACHERS', 3);

// SESSION VISIBILITY CONSTANTS
define('SESSION_VISIBLE_READ_ONLY', 1);
define('SESSION_VISIBLE', 2);
define('SESSION_INVISIBLE', 3); // not available
define('SESSION_AVAILABLE', 4);

define('SESSION_LINK_TARGET', '_self');

define('SUBSCRIBE_ALLOWED', 1);
define('SUBSCRIBE_NOT_ALLOWED', 0);
define('UNSUBSCRIBE_ALLOWED', 1);
define('UNSUBSCRIBE_NOT_ALLOWED', 0);

// SURVEY VISIBILITY CONSTANTS
define('SURVEY_VISIBLE_TUTOR', 0);
define('SURVEY_VISIBLE_TUTOR_STUDENT', 1);
define('SURVEY_VISIBLE_PUBLIC', 2);

// CONSTANTS defining all tools, using the english version
/* When you add a new tool you must add it into function api_get_tools_lists() too */
define('TOOL_DOCUMENT', 'document');
define('TOOL_LP_FINAL_ITEM', 'final_item');
define('TOOL_READOUT_TEXT', 'readout_text');
define('TOOL_THUMBNAIL', 'thumbnail');
define('TOOL_HOTPOTATOES', 'hotpotatoes');
define('TOOL_CALENDAR_EVENT', 'calendar_event');
define('TOOL_LINK', 'link');
define('TOOL_LINK_CATEGORY', 'link_category');
define('TOOL_COURSE_DESCRIPTION', 'course_description');
define('TOOL_SEARCH', 'search');
define('TOOL_LEARNPATH', 'learnpath');
define('TOOL_LEARNPATH_CATEGORY', 'learnpath_category');
define('TOOL_AGENDA', 'agenda');
define('TOOL_ANNOUNCEMENT', 'announcement');
define('TOOL_FORUM', 'forum');
define('TOOL_FORUM_CATEGORY', 'forum_category');
define('TOOL_FORUM_THREAD', 'forum_thread');
define('TOOL_FORUM_POST', 'forum_post');
define('TOOL_FORUM_ATTACH', 'forum_attachment');
define('TOOL_FORUM_THREAD_QUALIFY', 'forum_thread_qualify');
define('TOOL_THREAD', 'thread');
define('TOOL_POST', 'post');
define('TOOL_DROPBOX', 'dropbox');
define('TOOL_QUIZ', 'quiz');
define('TOOL_TEST_CATEGORY', 'test_category');
define('TOOL_USER', 'user');
define('TOOL_GROUP', 'group');
define('TOOL_BLOGS', 'blog_management');
define('TOOL_CHAT', 'chat');
define('TOOL_STUDENTPUBLICATION', 'student_publication');
define('TOOL_TRACKING', 'tracking');
define('TOOL_HOMEPAGE_LINK', 'homepage_link');
define('TOOL_COURSE_SETTING', 'course_setting');
define('TOOL_BACKUP', 'backup');
define('TOOL_COPY_COURSE_CONTENT', 'copy_course_content');
define('TOOL_RECYCLE_COURSE', 'recycle_course');
define('TOOL_COURSE_HOMEPAGE', 'course_homepage');
define('TOOL_COURSE_RIGHTS_OVERVIEW', 'course_rights');
define('TOOL_UPLOAD', 'file_upload');
define('TOOL_COURSE_MAINTENANCE', 'course_maintenance');
define('TOOL_SURVEY', 'survey');
define('TOOL_WIKI', 'wiki');
define('TOOL_GLOSSARY', 'glossary');
define('TOOL_GRADEBOOK', 'gradebook');
define('TOOL_NOTEBOOK', 'notebook');
define('TOOL_ATTENDANCE', 'attendance');
define('TOOL_COURSE_PROGRESS', 'course_progress');
define('TOOL_PORTFOLIO', 'portfolio');
define('TOOL_PORTFOLIO_COMMENT', 'portfolio_comment');
define('TOOL_PLAGIARISM', 'compilatio');
define('TOOL_XAPI', 'xapi');
define('TOOL_H5P', 'h5p');

// CONSTANTS defining Chamilo interface sections
define('SECTION_CAMPUS', 'mycampus');
define('SECTION_COURSES', 'mycourses');
define('SECTION_CATALOG', 'catalog');
define('SECTION_MYPROFILE', 'myprofile');
define('SECTION_MYAGENDA', 'myagenda');
define('SECTION_COURSE_ADMIN', 'course_admin');
define('SECTION_PLATFORM_ADMIN', 'platform_admin');
define('SECTION_MYGRADEBOOK', 'mygradebook');
define('SECTION_TRACKING', 'session_my_space');
define('SECTION_SOCIAL', 'social-network');
define('SECTION_DASHBOARD', 'dashboard');
define('SECTION_REPORTS', 'reports');
define('SECTION_GLOBAL', 'global');
define('SECTION_INCLUDE', 'include');
define('SECTION_CUSTOMPAGE', 'custompage');

// CONSTANT name for local authentication source
define('PLATFORM_AUTH_SOURCE', 'platform');
define('CAS_AUTH_SOURCE', 'cas');
define('LDAP_AUTH_SOURCE', 'extldap');
define('IMS_LTI_SOURCE', 'ims_lti');

// CONSTANT defining the default HotPotatoes files directory
define('DIR_HOTPOTATOES', '/HotPotatoes_files');

// event logs types
define('LOG_COURSE_DELETE', 'course_deleted');
define('LOG_COURSE_CREATE', 'course_created');
define('LOG_COURSE_SETTINGS_CHANGED', 'course_settings_changed');

// @todo replace 'soc_gr' with social_group
define('LOG_GROUP_PORTAL_CREATED', 'soc_gr_created');
define('LOG_GROUP_PORTAL_UPDATED', 'soc_gr_updated');
define('LOG_GROUP_PORTAL_DELETED', 'soc_gr_deleted');
define('LOG_GROUP_PORTAL_USER_DELETE_ALL', 'soc_gr_delete_users');

define('LOG_GROUP_PORTAL_ID', 'soc_gr_portal_id');
define('LOG_GROUP_PORTAL_REL_USER_ARRAY', 'soc_gr_user_array');

define('LOG_GROUP_PORTAL_USER_SUBSCRIBED', 'soc_gr_u_subs');
define('LOG_GROUP_PORTAL_USER_UNSUBSCRIBED', 'soc_gr_u_unsubs');
define('LOG_GROUP_PORTAL_USER_UPDATE_ROLE', 'soc_gr_update_role');
define('LOG_GROUP_PORTAL_COURSE_SUBSCRIBED', 'soc_gr_c_subs');
define('LOG_GROUP_PORTAL_COURSE_UNSUBSCRIBED', 'soc_gr_c_unsubs');
define('LOG_GROUP_PORTAL_SESSION_SUBSCRIBED', 'soc_gr_s_subs');
define('LOG_GROUP_PORTAL_SESSION_UNSUBSCRIBED', 'soc_gr_s_unsubs');

define('LOG_USER_DELETE', 'user_deleted');
define('LOG_USER_CREATE', 'user_created');
define('LOG_USER_UPDATE', 'user_updated');
define('LOG_USER_PASSWORD_UPDATE', 'user_password_updated');
define('LOG_USER_ENABLE', 'user_enable');
define('LOG_USER_DISABLE', 'user_disable');
define('LOG_USER_ANONYMIZE', 'user_anonymized');
define('LOG_USER_FIELD_CREATE', 'user_field_created');
define('LOG_USER_FIELD_DELETE', 'user_field_deleted');
define('LOG_SESSION_CREATE', 'session_created');
define('LOG_SESSION_DELETE', 'session_deleted');
define('LOG_SESSION_ADD_USER_COURSE', 'session_add_user_course');
define('LOG_SESSION_DELETE_USER_COURSE', 'session_delete_user_course');
define('LOG_SESSION_ADD_USER', 'session_add_user');
define('LOG_SESSION_DELETE_USER', 'session_delete_user');
define('LOG_SESSION_ADD_COURSE', 'session_add_course');
define('LOG_SESSION_DELETE_COURSE', 'session_delete_course');
define('LOG_SESSION_CATEGORY_CREATE', 'session_cat_created'); //changed in 1.9.8
define('LOG_SESSION_CATEGORY_DELETE', 'session_cat_deleted'); //changed in 1.9.8
define('LOG_CONFIGURATION_SETTINGS_CHANGE', 'settings_changed');
define('LOG_PLATFORM_LANGUAGE_CHANGE', 'platform_lng_changed'); //changed in 1.9.8
define('LOG_SUBSCRIBE_USER_TO_COURSE', 'user_subscribed');
define('LOG_UNSUBSCRIBE_USER_FROM_COURSE', 'user_unsubscribed');
define('LOG_ATTEMPTED_FORCED_LOGIN', 'attempted_forced_login');
define('LOG_PLUGIN_CHANGE', 'plugin_changed');
define('LOG_HOMEPAGE_CHANGED', 'homepage_changed');
define('LOG_PROMOTION_CREATE', 'promotion_created');
define('LOG_PROMOTION_DELETE', 'promotion_deleted');
define('LOG_CAREER_CREATE', 'career_created');
define('LOG_CAREER_DELETE', 'career_deleted');
define('LOG_USER_PERSONAL_DOC_DELETED', 'user_doc_deleted');
define('LOG_WIKI_ACCESS', 'wiki_page_view');
// All results from an exercise
define('LOG_EXERCISE_RESULT_DELETE', 'exe_result_deleted');
// Logs only the one attempt
define('LOG_EXERCISE_ATTEMPT_DELETE', 'exe_attempt_deleted');
define('LOG_LP_ATTEMPT_DELETE', 'lp_attempt_deleted');
define('LOG_QUESTION_RESULT_DELETE', 'qst_attempt_deleted');
define('LOG_QUESTION_SCORE_UPDATE', 'score_attempt_updated');

define('LOG_MY_FOLDER_CREATE', 'my_folder_created');
define('LOG_MY_FOLDER_CHANGE', 'my_folder_changed');
define('LOG_MY_FOLDER_DELETE', 'my_folder_deleted');
define('LOG_MY_FOLDER_COPY', 'my_folder_copied');
define('LOG_MY_FOLDER_CUT', 'my_folder_cut');
define('LOG_MY_FOLDER_PASTE', 'my_folder_pasted');
define('LOG_MY_FOLDER_UPLOAD', 'my_folder_uploaded');

// Event logs data types (max 20 chars)
define('LOG_COURSE_CODE', 'course_code');
define('LOG_COURSE_ID', 'course_id');
define('LOG_USER_ID', 'user_id');
define('LOG_USER_OBJECT', 'user_object');
define('LOG_USER_FIELD_VARIABLE', 'user_field_variable');
define('LOG_SESSION_ID', 'session_id');

define('LOG_QUESTION_ID', 'question_id');
define('LOG_SESSION_CATEGORY_ID', 'session_category_id');
define('LOG_CONFIGURATION_SETTINGS_CATEGORY', 'settings_category');
define('LOG_CONFIGURATION_SETTINGS_VARIABLE', 'settings_variable');
define('LOG_PLATFORM_LANGUAGE', 'default_platform_language');
define('LOG_PLUGIN_UPLOAD', 'plugin_upload');
define('LOG_PLUGIN_ENABLE', 'plugin_enable');
define('LOG_PLUGIN_SETTINGS_CHANGE', 'plugin_settings_change');
define('LOG_CAREER_ID', 'career_id');
define('LOG_PROMOTION_ID', 'promotion_id');
define('LOG_GRADEBOOK_LOCKED', 'gradebook_locked');
define('LOG_GRADEBOOK_UNLOCKED', 'gradebook_unlocked');
define('LOG_GRADEBOOK_ID', 'gradebook_id');
define('LOG_WIKI_PAGE_ID', 'wiki_page_id');
define('LOG_EXERCISE_ID', 'exercise_id');
define('LOG_EXERCISE_AND_USER_ID', 'exercise_and_user_id');
define('LOG_LP_ID', 'lp_id');
define('LOG_EXERCISE_ATTEMPT_QUESTION_ID', 'exercise_a_q_id');
define('LOG_EXERCISE_ATTEMPT', 'exe_id');

define('LOG_WORK_DIR_DELETE', 'work_dir_delete');
define('LOG_WORK_FILE_DELETE', 'work_file_delete');
define('LOG_WORK_DATA', 'work_data_array');

define('LOG_MY_FOLDER_PATH', 'path');
define('LOG_MY_FOLDER_NEW_PATH', 'new_path');

define('LOG_TERM_CONDITION_ACCEPTED', 'term_condition_accepted');
define('LOG_USER_CONFIRMED_EMAIL', 'user_confirmed_email');
define('LOG_USER_REMOVED_LEGAL_ACCEPT', 'user_removed_legal_accept');

define('LOG_USER_DELETE_ACCOUNT_REQUEST', 'user_delete_account_request');

define('LOG_QUESTION_CREATED', 'question_created');
define('LOG_QUESTION_UPDATED', 'question_updated');
define('LOG_QUESTION_DELETED', 'question_deleted');
define('LOG_QUESTION_REMOVED_FROM_QUIZ', 'question_removed_from_quiz');

define('LOG_SURVEY_ID', 'survey_id');
define('LOG_SURVEY_CREATED', 'survey_created');
define('LOG_SURVEY_DELETED', 'survey_deleted');
define('LOG_SURVEY_CLEAN_RESULTS', 'survey_clean_results');

define('LOG_WS', 'access_ws_');

define('USERNAME_PURIFIER', '/[^0-9A-Za-z_\.@\$-]/');

//used when login_is_email setting is true
define('USERNAME_PURIFIER_MAIL', '/[^0-9A-Za-z_\.@]/');
define('USERNAME_PURIFIER_SHALLOW', '/\s/');

// This constant is a result of Windows OS detection, it has a boolean value:
// true whether the server runs on Windows OS, false otherwise.
define('IS_WINDOWS_OS', api_is_windows_os());

// Checks for installed optional php-extensions.
// intl extension (from PECL), it is installed by default as of PHP 5.3.0.
define('INTL_INSTALLED', function_exists('intl_get_error_code'));
// iconv extension, for PHP5 on Windows it is installed by default.
define('ICONV_INSTALLED', function_exists('iconv'));
define('MBSTRING_INSTALLED', function_exists('mb_strlen')); // mbstring extension.

// Patterns for processing paths. Examples.
define('REPEATED_SLASHES_PURIFIER', '/\/{2,}/'); // $path = preg_replace(REPEATED_SLASHES_PURIFIER, '/', $path);
define('VALID_WEB_PATH', '/https?:\/\/[^\/]*(\/.*)?/i'); // $is_valid_path = preg_match(VALID_WEB_PATH, $path);
// $new_path = preg_replace(VALID_WEB_SERVER_BASE, $new_base, $path);
define('VALID_WEB_SERVER_BASE', '/https?:\/\/[^\/]*/i');
// Constants for api_get_path() and api_get_path_type(), etc. - registered path types.
// basic (leaf elements)
define('REL_CODE_PATH', 'REL_CODE_PATH');
define('REL_COURSE_PATH', 'REL_COURSE_PATH');
define('REL_HOME_PATH', 'REL_HOME_PATH');

// Constants for api_get_path() and api_get_path_type(), etc. - registered path types.
define('WEB_PATH', 'WEB_PATH');
define('WEB_APP_PATH', 'WEB_APP_PATH');
define('SYS_PATH', 'SYS_PATH');
define('SYS_APP_PATH', 'SYS_APP_PATH');
define('SYS_UPLOAD_PATH', 'SYS_UPLOAD_PATH');
define('WEB_UPLOAD_PATH', 'WEB_UPLOAD_PATH');

define('REL_PATH', 'REL_PATH');
define('WEB_COURSE_PATH', 'WEB_COURSE_PATH');
define('SYS_COURSE_PATH', 'SYS_COURSE_PATH');
define('WEB_CODE_PATH', 'WEB_CODE_PATH');
define('SYS_CODE_PATH', 'SYS_CODE_PATH');
define('SYS_LANG_PATH', 'SYS_LANG_PATH');
define('WEB_IMG_PATH', 'WEB_IMG_PATH');
define('WEB_CSS_PATH', 'WEB_CSS_PATH');
define('WEB_PUBLIC_PATH', 'WEB_PUBLIC_PATH');
define('SYS_CSS_PATH', 'SYS_CSS_PATH');
define('SYS_PLUGIN_PATH', 'SYS_PLUGIN_PATH');
define('WEB_PLUGIN_PATH', 'WEB_PLUGIN_PATH');
define('WEB_PLUGIN_ASSET_PATH', 'WEB_PLUGIN_ASSET_PATH');
define('SYS_ARCHIVE_PATH', 'SYS_ARCHIVE_PATH');
define('WEB_ARCHIVE_PATH', 'WEB_ARCHIVE_PATH');
define('SYS_INC_PATH', 'SYS_INC_PATH');
define('LIBRARY_PATH', 'LIBRARY_PATH');
define('CONFIGURATION_PATH', 'CONFIGURATION_PATH');
define('WEB_LIBRARY_PATH', 'WEB_LIBRARY_PATH');
define('WEB_LIBRARY_JS_PATH', 'WEB_LIBRARY_JS_PATH');
define('WEB_AJAX_PATH', 'WEB_AJAX_PATH');
define('SYS_TEST_PATH', 'SYS_TEST_PATH');
define('WEB_TEMPLATE_PATH', 'WEB_TEMPLATE_PATH');
define('SYS_TEMPLATE_PATH', 'SYS_TEMPLATE_PATH');
define('SYS_PUBLIC_PATH', 'SYS_PUBLIC_PATH');
define('SYS_HOME_PATH', 'SYS_HOME_PATH');
define('WEB_HOME_PATH', 'WEB_HOME_PATH');
define('WEB_FONTS_PATH', 'WEB_FONTS_PATH');
define('SYS_FONTS_PATH', 'SYS_FONTS_PATH');

define('SYS_DEFAULT_COURSE_DOCUMENT_PATH', 'SYS_DEFAULT_COURSE_DOCUMENT_PATH');
define('REL_DEFAULT_COURSE_DOCUMENT_PATH', 'REL_DEFAULT_COURSE_DOCUMENT_PATH');
define('WEB_DEFAULT_COURSE_DOCUMENT_PATH', 'WEB_DEFAULT_COURSE_DOCUMENT_PATH');

// Relations type with Course manager
define('COURSE_RELATION_TYPE_COURSE_MANAGER', 1);
define('SESSION_RELATION_TYPE_COURSE_MANAGER', 1);

// Relations type with Human resources manager
define('COURSE_RELATION_TYPE_RRHH', 1);
define('SESSION_RELATION_TYPE_RRHH', 1);

//User image sizes
define('USER_IMAGE_SIZE_ORIGINAL', 1);
define('USER_IMAGE_SIZE_BIG', 2);
define('USER_IMAGE_SIZE_MEDIUM', 3);
define('USER_IMAGE_SIZE_SMALL', 4);

// Relation type between users
define('USER_UNKNOWN', 0);
define('USER_RELATION_TYPE_UNKNOWN', 1);
define('USER_RELATION_TYPE_PARENT', 2); // should be deprecated is useless
define('USER_RELATION_TYPE_FRIEND', 3);
define('USER_RELATION_TYPE_GOODFRIEND', 4); // should be deprecated is useless
define('USER_RELATION_TYPE_ENEMY', 5); // should be deprecated is useless
define('USER_RELATION_TYPE_DELETED', 6);
define('USER_RELATION_TYPE_RRHH', 7);
define('USER_RELATION_TYPE_BOSS', 8);
define('USER_RELATION_TYPE_HRM_REQUEST', 9);

// Gradebook link constants
// Please do not change existing values, they are used in the database !
define('GRADEBOOK_ITEM_LIMIT', 1000);

define('LINK_EXERCISE', 1);
define('LINK_DROPBOX', 2);
define('LINK_STUDENTPUBLICATION', 3);
define('LINK_LEARNPATH', 4);
define('LINK_FORUM_THREAD', 5);
//define('LINK_WORK',6);
define('LINK_ATTENDANCE', 7);
define('LINK_SURVEY', 8);
define('LINK_HOTPOTATOES', 9);
define('LINK_PORTFOLIO', 10);

// Score display types constants
define('SCORE_DIV', 1); // X / Y
define('SCORE_PERCENT', 2); // XX %
define('SCORE_DIV_PERCENT', 3); // X / Y (XX %)
define('SCORE_AVERAGE', 4); // XX %
define('SCORE_DECIMAL', 5); // 0.50  (X/Y)
define('SCORE_BAR', 6); // Uses the Display::bar_progress function
define('SCORE_SIMPLE', 7); // X
define('SCORE_IGNORE_SPLIT', 8); //  ??
define('SCORE_DIV_PERCENT_WITH_CUSTOM', 9); // X / Y (XX %) - Good!
define('SCORE_CUSTOM', 10); // Good!
define('SCORE_DIV_SIMPLE_WITH_CUSTOM', 11); // X - Good!
define('SCORE_DIV_SIMPLE_WITH_CUSTOM_LETTERS', 12); // X - Good!
define('SCORE_ONLY_SCORE', 13); // X - Good!
define('SCORE_NUMERIC', 14);

define('SCORE_BOTH', 1);
define('SCORE_ONLY_DEFAULT', 2);
define('SCORE_ONLY_CUSTOM', 3);

// From display.lib.php

define('MAX_LENGTH_BREADCRUMB', 100);
define('ICON_SIZE_ATOM', 8);
define('ICON_SIZE_TINY', 16);
define('ICON_SIZE_SMALL', 22);
define('ICON_SIZE_MEDIUM', 32);
define('ICON_SIZE_LARGE', 48);
define('ICON_SIZE_BIG', 64);
define('ICON_SIZE_HUGE', 128);
define('SHOW_TEXT_NEAR_ICONS', false);

// Session catalog
define('CATALOG_COURSES', 0);
define('CATALOG_SESSIONS', 1);
define('CATALOG_COURSES_SESSIONS', 2);

// Hook type events, pre-process and post-process.
// All means to be executed for both hook event types
define('HOOK_EVENT_TYPE_PRE', 0);
define('HOOK_EVENT_TYPE_POST', 1);
define('HOOK_EVENT_TYPE_ALL', 10);

define('CAREER_STATUS_ACTIVE', 1);
define('CAREER_STATUS_INACTIVE', 0);

define('PROMOTION_STATUS_ACTIVE', 1);
define('PROMOTION_STATUS_INACTIVE', 0);

// Group permissions
define('GROUP_PERMISSION_OPEN', '1');
define('GROUP_PERMISSION_CLOSED', '2');

// Group user permissions
define('GROUP_USER_PERMISSION_ADMIN', '1'); // the admin of a group
define('GROUP_USER_PERMISSION_READER', '2'); // a normal user
define('GROUP_USER_PERMISSION_PENDING_INVITATION', '3'); // When an admin/moderator invites a user
define('GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER', '4'); // an user joins a group
define('GROUP_USER_PERMISSION_MODERATOR', '5'); // a moderator
define('GROUP_USER_PERMISSION_ANONYMOUS', '6'); // an anonymous user
define('GROUP_USER_PERMISSION_HRM', '7'); // a human resources manager

define('GROUP_IMAGE_SIZE_ORIGINAL', 1);
define('GROUP_IMAGE_SIZE_BIG', 2);
define('GROUP_IMAGE_SIZE_MEDIUM', 3);
define('GROUP_IMAGE_SIZE_SMALL', 4);
define('GROUP_TITLE_LENGTH', 50);

// Exercise
// @todo move into a class
define('ALL_ON_ONE_PAGE', 1);
define('ONE_PER_PAGE', 2);

define('EXERCISE_FEEDBACK_TYPE_END', 0); //Feedback 		 - show score and expected answers
define('EXERCISE_FEEDBACK_TYPE_DIRECT', 1); //DirectFeedback - Do not show score nor answers
define('EXERCISE_FEEDBACK_TYPE_EXAM', 2); // NoFeedback 	 - Show score only
define('EXERCISE_FEEDBACK_TYPE_POPUP', 3); // Popup BT#15827

define('RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS', 0); //show score and expected answers
define('RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS', 1); //Do not show score nor answers
define('RESULT_DISABLE_SHOW_SCORE_ONLY', 2); //Show score only
define('RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES', 3); //Show final score only with categories
define('RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT', 4);
define('RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK', 5);
define('RESULT_DISABLE_RANKING', 6);
define('RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER', 7);
define('RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING', 8);
define('RESULT_DISABLE_RADAR', 9);
define('RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK', 10);

define('EXERCISE_MAX_NAME_SIZE', 80);

// Question types (edit next array as well when adding values)
// @todo move into a class
define('UNIQUE_ANSWER', 1);
define('MULTIPLE_ANSWER', 2);
define('FILL_IN_BLANKS', 3);
define('MATCHING', 4);
define('FREE_ANSWER', 5);
define('HOT_SPOT', 6);
define('HOT_SPOT_ORDER', 7);
define('HOT_SPOT_DELINEATION', 8);
define('MULTIPLE_ANSWER_COMBINATION', 9);
define('UNIQUE_ANSWER_NO_OPTION', 10);
define('MULTIPLE_ANSWER_TRUE_FALSE', 11);
define('MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE', 12);
define('ORAL_EXPRESSION', 13);
define('GLOBAL_MULTIPLE_ANSWER', 14);
define('MEDIA_QUESTION', 15);
define('CALCULATED_ANSWER', 16);
define('UNIQUE_ANSWER_IMAGE', 17);
define('DRAGGABLE', 18);
define('MATCHING_DRAGGABLE', 19);
define('ANNOTATION', 20);
define('READING_COMPREHENSION', 21);
define('MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY', 22);
define('UPLOAD_ANSWER', 23);
define('MATCHING_COMBINATION', 24);
define('MATCHING_DRAGGABLE_COMBINATION', 25);
define('HOT_SPOT_COMBINATION', 26);
define('FILL_IN_BLANKS_COMBINATION', 27);
define('MULTIPLE_ANSWER_DROPDOWN_COMBINATION', 28);
define('MULTIPLE_ANSWER_DROPDOWN', 29);

define('EXERCISE_CATEGORY_RANDOM_SHUFFLED', 1);
define('EXERCISE_CATEGORY_RANDOM_ORDERED', 2);
define('EXERCISE_CATEGORY_RANDOM_DISABLED', 0);

// Question selection type
define('EX_Q_SELECTION_ORDERED', 1);
define('EX_Q_SELECTION_RANDOM', 2);
define('EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED', 3);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED', 4);
define('EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM', 5);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM', 6);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_ORDERED_NO_GROUPED', 7);
define('EX_Q_SELECTION_CATEGORIES_RANDOM_QUESTIONS_RANDOM_NO_GROUPED', 8);
define('EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_ORDERED', 9);
define('EX_Q_SELECTION_CATEGORIES_ORDERED_BY_PARENT_QUESTIONS_RANDOM', 10);

// Used to save the skill_rel_item table
define('ITEM_TYPE_EXERCISE', 1);
define('ITEM_TYPE_HOTPOTATOES', 2);
define('ITEM_TYPE_LINK', 3);
define('ITEM_TYPE_LEARNPATH', 4);
define('ITEM_TYPE_GRADEBOOK', 5);
define('ITEM_TYPE_STUDENT_PUBLICATION', 6);
//define('ITEM_TYPE_FORUM', 7);
define('ITEM_TYPE_ATTENDANCE', 8);
define('ITEM_TYPE_SURVEY', 9);
define('ITEM_TYPE_FORUM_THREAD', 10);
define('ITEM_TYPE_PORTFOLIO', 11);
define('ITEM_TYPE_GRADEBOOK_EVALUATION', 12);

// one big string with all question types, for the validator in pear/HTML/QuickForm/Rule/QuestionType
define(
    'QUESTION_TYPES',
    UNIQUE_ANSWER.':'.
    MULTIPLE_ANSWER.':'.
    FILL_IN_BLANKS.':'.
    MATCHING.':'.
    FREE_ANSWER.':'.
    HOT_SPOT.':'.
    HOT_SPOT_ORDER.':'.
    HOT_SPOT_DELINEATION.':'.
    MULTIPLE_ANSWER_COMBINATION.':'.
    UNIQUE_ANSWER_NO_OPTION.':'.
    MULTIPLE_ANSWER_TRUE_FALSE.':'.
    MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE.':'.
    ORAL_EXPRESSION.':'.
    GLOBAL_MULTIPLE_ANSWER.':'.
    MEDIA_QUESTION.':'.
    CALCULATED_ANSWER.':'.
    UNIQUE_ANSWER_IMAGE.':'.
    DRAGGABLE.':'.
    MATCHING_DRAGGABLE.':'.
    MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY.':'.
    ANNOTATION
);

//Some alias used in the QTI exports
define('MCUA', 1);
define('TF', 1);
define('MCMA', 2);
define('FIB', 3);

// Skills
define('SKILL_TYPE_REQUIREMENT', 'required');
define('SKILL_TYPE_ACQUIRED', 'acquired');
define('SKILL_TYPE_BOTH', 'both');

// Message
define('MESSAGE_STATUS_NEW', '0');
define('MESSAGE_STATUS_UNREAD', '1');
//2 ??
define('MESSAGE_STATUS_DELETED', '3');
define('MESSAGE_STATUS_OUTBOX', '4');
define('MESSAGE_STATUS_INVITATION_PENDING', '5');
define('MESSAGE_STATUS_INVITATION_ACCEPTED', '6');
define('MESSAGE_STATUS_INVITATION_DENIED', '7');
define('MESSAGE_STATUS_WALL', '8');
define('MESSAGE_STATUS_WALL_DELETE', '9');
define('MESSAGE_STATUS_WALL_POST', '10');
define('MESSAGE_STATUS_CONVERSATION', '11');
define('MESSAGE_STATUS_FORUM', '12');
define('MESSAGE_STATUS_PROMOTED', '13');

// Images
define('IMAGE_WALL_SMALL_SIZE', 200);
define('IMAGE_WALL_MEDIUM_SIZE', 500);
define('IMAGE_WALL_BIG_SIZE', 2000);
define('IMAGE_WALL_SMALL', 'small');
define('IMAGE_WALL_MEDIUM', 'medium');
define('IMAGE_WALL_BIG', 'big');

// Social PLUGIN PLACES
define('SOCIAL_LEFT_PLUGIN', 1);
define('SOCIAL_CENTER_PLUGIN', 2);
define('SOCIAL_RIGHT_PLUGIN', 3);
define('CUT_GROUP_NAME', 50);

/**
 * FormValidator Filter.
 */
define('NO_HTML', 1);
define('STUDENT_HTML', 2);
define('TEACHER_HTML', 3);
define('STUDENT_HTML_FULLPAGE', 4);
define('TEACHER_HTML_FULLPAGE', 5);

// Timeline
define('TIMELINE_STATUS_ACTIVE', '1');
define('TIMELINE_STATUS_INACTIVE', '2');

// Event email template class
define('EVENT_EMAIL_TEMPLATE_ACTIVE', 1);
define('EVENT_EMAIL_TEMPLATE_INACTIVE', 0);

// Course home
define('SHORTCUTS_HORIZONTAL', 0);
define('SHORTCUTS_VERTICAL', 1);

// Image class
define('IMAGE_PROCESSOR', 'gd'); // 'imagick' or 'gd' strings

// Course copy
define('FILE_SKIP', 1);
define('FILE_RENAME', 2);
define('FILE_OVERWRITE', 3);
define('UTF8_CONVERT', false); //false by default

define('DOCUMENT', 'file');
define('FOLDER', 'folder');

define('RESOURCE_ASSET', 'asset');
define('RESOURCE_DOCUMENT', 'document');
define('RESOURCE_GLOSSARY', 'glossary');
define('RESOURCE_EVENT', 'calendar_event');
define('RESOURCE_LINK', 'link');
define('RESOURCE_COURSEDESCRIPTION', 'course_description');
define('RESOURCE_LEARNPATH', 'learnpath');
define('RESOURCE_LEARNPATH_CATEGORY', 'learnpath_category');
define('RESOURCE_ANNOUNCEMENT', 'announcement');
define('RESOURCE_FORUM', 'forum');
define('RESOURCE_FORUMTOPIC', 'thread');
define('RESOURCE_FORUMPOST', 'post');
define('RESOURCE_QUIZ', 'quiz');
define('RESOURCE_TEST_CATEGORY', 'test_category');
define('RESOURCE_QUIZQUESTION', 'Exercise_Question');
define('RESOURCE_TOOL_INTRO', 'Tool introduction');
define('RESOURCE_LINKCATEGORY', 'Link_Category');
define('RESOURCE_FORUMCATEGORY', 'Forum_Category');
define('RESOURCE_SCORM', 'Scorm');
define('RESOURCE_SURVEY', 'survey');
define('RESOURCE_SURVEYQUESTION', 'survey_question');
define('RESOURCE_SURVEYINVITATION', 'survey_invitation');
define('RESOURCE_WIKI', 'wiki');
define('RESOURCE_THEMATIC', 'thematic');
define('RESOURCE_ATTENDANCE', 'attendance');
define('RESOURCE_WORK', 'work');
define('RESOURCE_SESSION_COURSE', 'session_course');
define('RESOURCE_GRADEBOOK', 'gradebook');
define('RESOURCE_XAPI_TOOL', 'xapi_tool');
define('RESOURCE_H5P_TOOL', 'h5p_tool');
define('ADD_THEMATIC_PLAN', 6);

// Max online users to show per page (whoisonline)
define('MAX_ONLINE_USERS', 12);

// Number of characters maximum to show in preview of course blog posts
define('BLOG_MAX_PREVIEW_CHARS', 800);
// HTML string to replace with a 'Read more...' link
define('BLOG_PAGE_BREAK', '<div style="page-break-after: always"><span style="display: none;">&nbsp;</span></div>');

// Make sure the CHAMILO_LOAD_WYSIWYG constant is defined
// To remove CKeditor libs from HTML, set this constant to true before loading
if (!defined('CHAMILO_LOAD_WYSIWYG')) {
    define('CHAMILO_LOAD_WYSIWYG', true);
}

/* Constants for course home */
define('TOOL_PUBLIC', 'Public');
define('TOOL_PUBLIC_BUT_HIDDEN', 'PublicButHide');
define('TOOL_COURSE_ADMIN', 'courseAdmin');
define('TOOL_PLATFORM_ADMIN', 'platformAdmin');
define('TOOL_AUTHORING', 'toolauthoring');
define('TOOL_INTERACTION', 'toolinteraction');
define('TOOL_COURSE_PLUGIN', 'toolcourseplugin'); //all plugins that can be enabled in courses
define('TOOL_ADMIN', 'tooladmin');
define('TOOL_ADMIN_PLATFORM', 'tooladminplatform');
define('TOOL_DRH', 'tool_drh');
define('TOOL_STUDENT_VIEW', 'toolstudentview');
define('TOOL_ADMIN_VISIBLE', 'tooladminvisible');

/**
 * Inclusion of internationalization libraries.
 */
require_once __DIR__.'/internationalization.lib.php';

/**
 * Returns a path to a certain resource within the Chamilo area, specifyed through a parameter.
 * Also, this function provides conversion between path types, in this case the input path points inside the Chamilo area too.
 *
 * See $_configuration['course_folder'] in the configuration.php to alter the WEB_COURSE_PATH and SYS_COURSE_PATH parameters.

 *
 * @param string $path (optional)   A path which type is to be converted. Also, it may be a defined constant for a path.
 *                     This parameter has meaning when $type parameter has one of the following values: TO_WEB, TO_SYS, TO_REL. Otherwise it is ignored.
 *
 * @return string the requested path or the converted path
 *
 * Notes about the current behaviour model:
 * 1. Windows back-slashes are converted to slashes in the result.
 * 2. A semi-absolute web-path is detected by its leading slash. On Linux systems, absolute system paths start with
 * a slash too, so an additional check about presence of leading system server base is implemented. For example, the function is
 * able to distinguish type difference between /var/www/chamilo/courses/ (SYS) and /chamilo/courses/ (REL).
 * 3. The function api_get_path() returns only these three types of paths, which in some sense are absolute. The function has
 * no mechanism for processing relative web/system paths, such as: lesson01.html, ./lesson01.html, ../css/my_styles.css.
 * It has not been identified as needed yet.
 * 4. Also, resolving the meta-symbols "." and ".." within paths has not been implemented, it is to be identified as needed.
 *
 * For examples go to: *
 * See main/admin/system_status.php?section=paths
 *
 * Vchamilo changes : allow using an alternate configuration
 * to get vchamilo  instance paths
 */
function api_get_path($path = '', $configuration = [])
{
    global $paths;

    // get proper configuration data if exists
    global $_configuration;

    $emptyConfigurationParam = false;
    if (empty($configuration)) {
        $configuration = (array) $_configuration;
        $emptyConfigurationParam = true;
    }

    $course_folder = 'courses/';
    static $root_web = '';
    $root_sys = null;
    if ($_configuration) {
        $root_sys = $_configuration['root_sys'];
    }

    // If no $root_web has been set so far *and* no custom config has been passed to the function
    // then re-use the previously-calculated (run-specific) $root_web and skip this complex calculation
    if (empty($root_web) || $emptyConfigurationParam === false || empty($configuration)) {
        // Resolve master hostname.
        if (!empty($configuration) && array_key_exists('root_web', $configuration)) {
            $root_web = $configuration['root_web'];
        } else {
            $root_web = '';
            // Try guess it from server.
            if (defined('SYSTEM_INSTALLATION') && SYSTEM_INSTALLATION) {
                if (($pos = strpos(($requested_page_rel = api_get_self()), 'main/install')) !== false) {
                    $root_rel = substr($requested_page_rel, 0, $pos);
                    // See http://www.mediawiki.org/wiki/Manual:$wgServer
                    $server_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
                    $server_name =
                        isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME']
                            : (isset($_SERVER['HOSTNAME']) ? $_SERVER['HOSTNAME']
                            : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
                                : (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR']
                                    : 'localhost')));
                    if (isset($_SERVER['SERVER_PORT']) &&
                        !strpos($server_name, ':') &&
                        (($server_protocol == 'http' && $_SERVER['SERVER_PORT'] != 80) ||
                        ($server_protocol == 'https' && $_SERVER['SERVER_PORT'] != 443))
                    ) {
                        $server_name .= ":".$_SERVER['SERVER_PORT'];
                    }
                    $root_web = $server_protocol.'://'.$server_name.$root_rel;
                    $root_sys = str_replace('\\', '/', realpath(__DIR__.'/../../../')).'/';
                }
                // Here we give up, so we don't touch anything.
            }
        }
    }

    if (isset($configuration['multiple_access_urls']) &&
        $configuration['multiple_access_urls']
    ) {
        // To avoid that the api_get_access_url() function fails since global.inc.php also calls the api.lib.php.
        if (isset($configuration['access_url']) && !empty($configuration['access_url'])) {
            // We look into the DB the function api_get_access_url
            $urlInfo = api_get_access_url($configuration['access_url']);
            // Avoid default value
            $defaultValues = ['http://localhost/', 'https://localhost/'];
            if (!empty($urlInfo['url']) && !in_array($urlInfo['url'], $defaultValues)) {
                $root_web = $urlInfo['active'] == 1 ? $urlInfo['url'] : $configuration['root_web'];
            }
        }
    }

    $paths = [];
    // Initialise cache with default values.
    if (!array_key_exists($root_web, $paths)) {
        $paths[$root_web] = [
            WEB_PATH => '',
            SYS_PATH => '',
            REL_PATH => '',
            WEB_COURSE_PATH => '',
            SYS_COURSE_PATH => '',
            REL_COURSE_PATH => '',
            WEB_CODE_PATH => 'main/',
            SYS_CODE_PATH => 'main/',
            REL_CODE_PATH => '/main/',
            SYS_LANG_PATH => 'lang/',
            WEB_IMG_PATH => 'img/',
            WEB_CSS_PATH => 'web/css/',
            SYS_CSS_PATH => 'app/Resources/public/css/',
            SYS_PLUGIN_PATH => 'plugin/',
            WEB_PLUGIN_PATH => 'plugin/',
            WEB_PLUGIN_ASSET_PATH => 'public/plugins/',
            SYS_ARCHIVE_PATH => 'app/cache/',
            WEB_ARCHIVE_PATH => 'app/cache/',
            SYS_HOME_PATH => 'app/home/',
            WEB_HOME_PATH => 'app/home/',
            REL_HOME_PATH => 'app/home/',
            SYS_APP_PATH => 'app/',
            WEB_APP_PATH => 'app/',
            SYS_UPLOAD_PATH => 'app/upload/',
            SYS_INC_PATH => 'inc/',
            CONFIGURATION_PATH => 'app/config/',
            LIBRARY_PATH => 'inc/lib/',
            WEB_LIBRARY_PATH => 'inc/lib/',
            WEB_LIBRARY_JS_PATH => 'inc/lib/javascript/',
            WEB_AJAX_PATH => 'inc/ajax/',
            SYS_TEST_PATH => 'tests/',
            WEB_TEMPLATE_PATH => 'template/',
            SYS_TEMPLATE_PATH => 'template/',
            WEB_UPLOAD_PATH => 'app/upload/',
            WEB_PUBLIC_PATH => 'web/',
            SYS_PUBLIC_PATH => 'web/',
            WEB_FONTS_PATH => 'fonts/',
            SYS_FONTS_PATH => 'fonts/',
        ];
    }

    $isInitialized = [];
    $course_folder = isset($configuration['course_folder']) ? $configuration['course_folder'] : $course_folder;
    $root_rel = isset($configuration['url_append']) ? $configuration['url_append'] : '';
    if (!empty($root_rel)) {
        // Adds "/" to the root_rel
        $hasSlash = substr($configuration['url_append'], 0, 1);
        if ($hasSlash !== '/') {
            $root_rel = '/'.$root_rel;
        }
    }

    // Web server base and system server base.
    if (!array_key_exists($root_web, $isInitialized)) {
        // process absolute global roots
        if (!empty($configuration)) {
            $code_folder = 'main';
        } else {
            $code_folder = $paths[$root_web][REL_CODE_PATH];
        }

        // Support for the installation process.
        // Developers might use the function api_get_path() directly or indirectly (this is difficult to be traced), at the moment when
        // configuration has not been created yet. This is why this function should be upgraded to return correct results in this case.

        // Dealing with trailing slashes.
        $slashed_root_web = api_add_trailing_slash($root_web);
        $root_sys = api_add_trailing_slash($root_sys);
        $root_rel = api_add_trailing_slash($root_rel);
        $code_folder = api_add_trailing_slash($code_folder);
        $course_folder = api_add_trailing_slash($course_folder);

        // Initialization of a table that contains common-purpose paths.
        $paths[$root_web][REL_PATH] = $root_rel;
        $paths[$root_web][REL_COURSE_PATH] = $root_rel.$course_folder;
        $paths[$root_web][REL_CODE_PATH] = $root_rel.$code_folder;
        $paths[$root_web][REL_DEFAULT_COURSE_DOCUMENT_PATH] = $paths[$root_web][REL_PATH].'main/default_course_document/';

        $paths[$root_web][WEB_PATH] = $slashed_root_web;
        $paths[$root_web][WEB_CODE_PATH] = $paths[$root_web][WEB_PATH].$code_folder;
        $paths[$root_web][WEB_COURSE_PATH] = $paths[$root_web][WEB_PATH].$course_folder;
        $paths[$root_web][WEB_DEFAULT_COURSE_DOCUMENT_PATH] = $paths[$root_web][WEB_CODE_PATH].'default_course_document/';
        $paths[$root_web][WEB_APP_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][WEB_APP_PATH];
        $paths[$root_web][WEB_PLUGIN_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][WEB_PLUGIN_PATH];
        $paths[$root_web][WEB_PLUGIN_ASSET_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][WEB_PLUGIN_ASSET_PATH];
        $paths[$root_web][WEB_ARCHIVE_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][WEB_ARCHIVE_PATH];

        $paths[$root_web][WEB_CSS_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][WEB_CSS_PATH];
        $paths[$root_web][WEB_IMG_PATH] = $paths[$root_web][WEB_CODE_PATH].$paths[$root_web][WEB_IMG_PATH];
        $paths[$root_web][WEB_LIBRARY_PATH] = $paths[$root_web][WEB_CODE_PATH].$paths[$root_web][WEB_LIBRARY_PATH];
        $paths[$root_web][WEB_LIBRARY_JS_PATH] = $paths[$root_web][WEB_CODE_PATH].$paths[$root_web][WEB_LIBRARY_JS_PATH];
        $paths[$root_web][WEB_AJAX_PATH] = $paths[$root_web][WEB_CODE_PATH].$paths[$root_web][WEB_AJAX_PATH];
        $paths[$root_web][WEB_FONTS_PATH] = $paths[$root_web][WEB_CODE_PATH].$paths[$root_web][WEB_FONTS_PATH];
        $paths[$root_web][WEB_TEMPLATE_PATH] = $paths[$root_web][WEB_CODE_PATH].$paths[$root_web][WEB_TEMPLATE_PATH];
        $paths[$root_web][WEB_UPLOAD_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][WEB_UPLOAD_PATH];
        $paths[$root_web][WEB_PUBLIC_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][WEB_PUBLIC_PATH];
        $paths[$root_web][WEB_HOME_PATH] = $paths[$root_web][WEB_PATH].$paths[$root_web][REL_HOME_PATH];

        $paths[$root_web][SYS_PATH] = $root_sys;
        $paths[$root_web][SYS_CODE_PATH] = $root_sys.$code_folder;
        $paths[$root_web][SYS_TEST_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_TEST_PATH];
        $paths[$root_web][SYS_TEMPLATE_PATH] = $paths[$root_web][SYS_CODE_PATH].$paths[$root_web][SYS_TEMPLATE_PATH];
        $paths[$root_web][SYS_PUBLIC_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_PUBLIC_PATH];
        $paths[$root_web][SYS_CSS_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_CSS_PATH];
        $paths[$root_web][SYS_FONTS_PATH] = $paths[$root_web][SYS_CODE_PATH].$paths[$root_web][SYS_FONTS_PATH];
        $paths[$root_web][SYS_ARCHIVE_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_ARCHIVE_PATH];
        $paths[$root_web][SYS_APP_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_APP_PATH];
        $paths[$root_web][SYS_COURSE_PATH] = $paths[$root_web][SYS_APP_PATH].$course_folder;
        $paths[$root_web][SYS_UPLOAD_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_UPLOAD_PATH];
        $paths[$root_web][SYS_LANG_PATH] = $paths[$root_web][SYS_CODE_PATH].$paths[$root_web][SYS_LANG_PATH];
        $paths[$root_web][SYS_HOME_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_HOME_PATH];
        $paths[$root_web][SYS_PLUGIN_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][SYS_PLUGIN_PATH];
        $paths[$root_web][SYS_INC_PATH] = $paths[$root_web][SYS_CODE_PATH].$paths[$root_web][SYS_INC_PATH];

        $paths[$root_web][LIBRARY_PATH] = $paths[$root_web][SYS_CODE_PATH].$paths[$root_web][LIBRARY_PATH];
        $paths[$root_web][CONFIGURATION_PATH] = $paths[$root_web][SYS_PATH].$paths[$root_web][CONFIGURATION_PATH];

        global $virtualChamilo;
        if (!empty($virtualChamilo)) {
            $paths[$root_web][SYS_ARCHIVE_PATH] = api_add_trailing_slash($virtualChamilo[SYS_ARCHIVE_PATH]);
            $paths[$root_web][SYS_HOME_PATH] = api_add_trailing_slash($virtualChamilo[SYS_HOME_PATH]);
            $paths[$root_web][SYS_COURSE_PATH] = api_add_trailing_slash($virtualChamilo[SYS_COURSE_PATH]);
            $paths[$root_web][SYS_UPLOAD_PATH] = api_add_trailing_slash($virtualChamilo[SYS_UPLOAD_PATH]);

            $paths[$root_web][WEB_HOME_PATH] = api_add_trailing_slash($virtualChamilo[WEB_HOME_PATH]);
            $paths[$root_web][WEB_UPLOAD_PATH] = api_add_trailing_slash($virtualChamilo[WEB_UPLOAD_PATH]);
            $paths[$root_web][WEB_ARCHIVE_PATH] = api_add_trailing_slash($virtualChamilo[WEB_ARCHIVE_PATH]);
            //$paths[$root_web][WEB_COURSE_PATH] = api_add_trailing_slash($virtualChamilo[WEB_COURSE_PATH]);

            // WEB_UPLOAD_PATH should be handle by apache htaccess in the vhost

            // RewriteEngine On
            // RewriteRule /app/upload/(.*)$ http://localhost/other/upload/my-chamilo111-net/$1 [QSA,L]

            //$paths[$root_web][WEB_UPLOAD_PATH] = api_add_trailing_slash($virtualChamilo[WEB_UPLOAD_PATH]);
            //$paths[$root_web][REL_PATH] = $virtualChamilo[REL_PATH];
            //$paths[$root_web][REL_COURSE_PATH] = $virtualChamilo[REL_COURSE_PATH];
        }

        $isInitialized[$root_web] = true;
    }

    $path = trim($path);

    // Retrieving a common-purpose path.
    if (isset($paths[$root_web][$path])) {
        return $paths[$root_web][$path];
    }

    // Second purification.

    // Replacing Windows back slashes.
    $path = str_replace('\\', '/', $path);
    // Query strings sometimes mighth wrongly appear in non-URLs.
    // Let us check remove them from all types of paths.
    if (($pos = strpos($path, '?')) !== false) {
        $path = substr($path, 0, $pos);
    }

    // Detection of the input path type. Conversion to semi-absolute type ( /chamilo/main/inc/.... ).

    if (preg_match(VALID_WEB_PATH, $path)) {
        // A special case: When a URL points to the document download script directly, without
        // mod-rewrite translation, we have to translate it into an "ordinary" web path.
        // For example:
        // http://localhost/chamilo/main/document/download.php?doc_url=/image.png&cDir=/
        // becomes
        // http://localhost/chamilo/courses/TEST/document/image.png
        // TEST is a course directory name, so called "system course code".
        if (strpos($path, 'download.php') !== false) { // Fast detection first.
            $path = urldecode($path);
            if (preg_match('/(.*)main\/document\/download.php\?doc_url=\/(.*)&cDir=\/(.*)?/', $path, $matches)) {
                $sys_course_code =
                    isset($_SESSION['_course']['sysCode'])  // User is inside a course?
                        ? $_SESSION['_course']['sysCode']   // Yes, then use course's directory name.
                        : '{SYS_COURSE_CODE}'; // No, then use a fake code, it may be processed later.
                $path = $matches[1].'courses/'.$sys_course_code.'/document/'.str_replace('//', '/', $matches[3].'/'.$matches[2]);
            }
        }
        // Replacement of the present web server base with a slash '/'.
        $path = preg_replace(VALID_WEB_SERVER_BASE, '/', $path);
    }

    // Path now is semi-absolute. It is convenient at this moment repeated slashes to be removed.
    $path = preg_replace(REPEATED_SLASHES_PURIFIER, '/', $path);

    return $path;
}

/**
 * Gets a modified version of the path for the CDN, if defined in
 * configuration.php.
 *
 * @param string $web_path The path of the resource without CDN
 *
 * @return string The path of the resource converted to CDN
 *
 * @author Yannick Warnier <ywarnier@beeznst.org>
 */
function api_get_cdn_path($web_path)
{
    global $_configuration;
    if (!empty($_configuration['cdn_enable'])) {
        $web_root = api_get_path(WEB_PATH);
        $ext = substr($web_path, strrpos($web_path, '.'));
        if (isset($ext[2])) { // faster version of strlen to check if len>2
            // Check for CDN definitions
            if (!empty($ext)) {
                foreach ($_configuration['cdn'] as $host => $exts) {
                    if (in_array($ext, $exts)) {
                        //Use host as defined in $_configuration['cdn'], without
                        // trailing slash
                        return str_replace($web_root, $host.'/', $web_path);
                    }
                }
            }
        }
    }

    return $web_path;
}

/**
 * @return bool Return true if CAS authentification is activated
 */
function api_is_cas_activated()
{
    return api_get_setting('cas_activate') == "true";
}

/**
 * @return bool Return true if LDAP authentification is activated
 */
function api_is_ldap_activated()
{
    global $extAuthSource;

    return is_array($extAuthSource[LDAP_AUTH_SOURCE]);
}

/**
 * @return bool Return true if Facebook authentification is activated
 */
function api_is_facebook_auth_activated()
{
    global $_configuration;

    return isset($_configuration['facebook_auth']) && $_configuration['facebook_auth'] == 1;
}

/**
 * Adds to a given path a trailing slash if it is necessary (adds "/" character at the end of the string).
 *
 * @param string $path the input path
 *
 * @return string returns the modified path
 */
function api_add_trailing_slash($path)
{
    return substr($path, -1) == '/' ? $path : $path.'/';
}

/**
 * Removes from a given path the trailing slash if it is necessary (removes "/" character from the end of the string).
 *
 * @param string $path the input path
 *
 * @return string returns the modified path
 */
function api_remove_trailing_slash($path)
{
    return substr($path, -1) == '/' ? substr($path, 0, -1) : $path;
}

/**
 * Checks the RFC 3986 syntax of a given URL.
 *
 * @param string $url      the URL to be checked
 * @param bool   $absolute whether the URL is absolute (beginning with a scheme such as "http:")
 *
 * @return string|false Returns the URL if it is valid, FALSE otherwise.
 *                      This function is an adaptation from the function valid_url(), Drupal CMS.
 *
 * @see http://drupal.org
 * Note: The built-in function filter_var($urs, FILTER_VALIDATE_URL) has a bug for some versions of PHP.
 * @see http://bugs.php.net/51192
 */
function api_valid_url($url, $absolute = false)
{
    if ($absolute) {
        if (preg_match("
            /^                                                      # Start at the beginning of the text
            (?:ftp|https?|feed):\/\/                                # Look for ftp, http, https or feed schemes
            (?:                                                     # Userinfo (optional) which is typically
                (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*    # a username or a username and password
                (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@        # combination
            )?
            (?:
                (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                      # A domain name or a IPv4 address
                |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])       # or a well formed IPv6 address
            )
            (?::[0-9]+)?                                            # Server port number (optional)
            (?:[\/|\?]
                (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2}) # The path and query (optional)
            *)?
            $/xi", $url)) {
            return $url;
        }

        return false;
    } else {
        return preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url) ? $url : false;
    }
}

/**
 * Checks whether a given string looks roughly like an email address.
 *
 * @param string $address the e-mail address to be checked
 *
 * @return mixed returns the e-mail if it is valid, FALSE otherwise
 */
function api_valid_email($address)
{
    return filter_var($address, FILTER_VALIDATE_EMAIL);
}

/* PROTECTION FUNCTIONS
   Use these functions to protect your scripts. */

/**
 * Function used to protect a course script.
 * The function blocks access when
 * - there is no $_SESSION["_course"] defined; or
 * - $is_allowed_in_course is set to false (this depends on the course
 * visibility and user status).
 *
 * This is only the first proposal, test and improve!
 *
 * @param bool Option to print headers when displaying error message. Default: false
 * @param bool whether session admins should be allowed or not
 * @param bool $checkTool check if tool is available for users (user, group)
 *
 * @return bool True if the user has access to the current course or is out of a course context, false otherwise
 *
 * @todo replace global variable
 *
 * @author Roan Embrechts
 */
function api_protect_course_script($print_headers = false, $allow_session_admins = false, $checkTool = '')
{
    $course_info = api_get_course_info();
    if (empty($course_info)) {
        api_not_allowed($print_headers);

        return false;
    }

    if (api_is_drh()) {
        return true;
    }

    // Session admin has access to course
    $sessionAccess = api_get_configuration_value('session_admins_access_all_content');
    if ($sessionAccess) {
        $allow_session_admins = true;
    }

    if (api_is_platform_admin($allow_session_admins)) {
        return true;
    }

    $isAllowedInCourse = api_is_allowed_in_course();

    $is_visible = false;
    if (isset($course_info) && isset($course_info['visibility'])) {
        switch ($course_info['visibility']) {
            default:
            case COURSE_VISIBILITY_CLOSED:
                // Completely closed: the course is only accessible to the teachers. - 0
                if ($isAllowedInCourse && api_get_user_id() && !api_is_anonymous()) {
                    $is_visible = true;
                }
                break;
            case COURSE_VISIBILITY_REGISTERED:
                // Private - access authorized to course members only - 1
                if ($isAllowedInCourse && api_get_user_id() && !api_is_anonymous()) {
                    $is_visible = true;
                }
                break;
            case COURSE_VISIBILITY_OPEN_PLATFORM:
                // Open - access allowed for users registered on the platform - 2
                if ($isAllowedInCourse && api_get_user_id() && !api_is_anonymous()) {
                    $is_visible = true;
                }
                break;
            case COURSE_VISIBILITY_OPEN_WORLD:
                //Open - access allowed for the whole world - 3
                $is_visible = true;
                break;
            case COURSE_VISIBILITY_HIDDEN:
                //Completely closed: the course is only accessible to the teachers. - 0
                if (api_is_platform_admin()) {
                    $is_visible = true;
                }
                break;
        }

        // If password is set and user is not registered to the course then the course is not visible.
        if (false == $isAllowedInCourse &&
            isset($course_info['registration_code']) && !empty($course_info['registration_code'])
        ) {
            $is_visible = false;
        }
    }

    if (!empty($checkTool)) {
        if (!api_is_allowed_to_edit(true, true, true)) {
            $toolInfo = api_get_tool_information_by_name($checkTool);
            if (!empty($toolInfo) && isset($toolInfo['visibility']) && $toolInfo['visibility'] == 0) {
                api_not_allowed(true);

                return false;
            }
        }
    }

    // Check session visibility
    $session_id = api_get_session_id();

    if (!empty($session_id)) {
        // $isAllowedInCourse was set in local.inc.php
        if (!$isAllowedInCourse) {
            $is_visible = false;
        }

        // Check if course is inside session.
        if (!SessionManager::relation_session_course_exist($session_id, $course_info['real_id'])) {
            $is_visible = false;
        }
    }

    if (!$is_visible) {
        api_not_allowed($print_headers);

        return false;
    }

    if ($is_visible && 'true' === api_get_plugin_setting('positioning', 'tool_enable')) {
        $plugin = Positioning::create();
        $block = $plugin->get('block_course_if_initial_exercise_not_attempted');
        if ('true' === $block) {
            $currentPath = $_SERVER['PHP_SELF'];
            // Allowed only this course paths.
            $paths = [
                '/plugin/positioning/start.php',
                '/plugin/positioning/start_student.php',
                '/main/course_home/course_home.php',
                '/main/exercise/overview.php',
            ];

            if (!in_array($currentPath, $paths, true)) {
                // Check if entering an exercise.
                global $current_course_tool;
                if ('quiz' !== $current_course_tool) {
                    $initialData = $plugin->getInitialExercise($course_info['real_id'], $session_id);
                    if ($initialData && isset($initialData['exercise_id'])) {
                        $results = Event::getExerciseResultsByUser(
                            api_get_user_id(),
                            $initialData['exercise_id'],
                            $course_info['real_id'],
                            $session_id
                        );
                        if (empty($results)) {
                            api_not_allowed($print_headers);

                            return false;
                        }
                    }
                }
            }
        }
    }

    api_block_inactive_user();

    return true;
}

/**
 * Function used to protect an admin script.
 *
 * The function blocks access when the user has no platform admin rights
 * with an error message printed on default output
 *
 * @param bool Whether to allow session admins as well
 * @param bool Whether to allow HR directors as well
 * @param string An optional message (already passed through get_lang)
 * @param bool Whether to allow session coach as well
 *
 * @return bool True if user is allowed, false otherwise.
 *              The function also outputs an error message in case not allowed
 *
 * @author Roan Embrechts (original author)
 */
function api_protect_admin_script($allow_sessions_admins = false, $allow_drh = false, $message = null, $allow_session_coach = false)
{
    if (!api_is_platform_admin($allow_sessions_admins, $allow_drh)) {
        if (!($allow_session_coach && api_is_coach())) {
            api_not_allowed(true, $message);

            return false;
        }
    }
    api_block_inactive_user();

    return true;
}

/**
 * Blocks inactive users with a currently active session from accessing more pages "live".
 *
 * @return bool Returns true if the feature is disabled or the user account is still enabled.
 *              Returns false (and shows a message) if the feature is enabled *and* the user is disabled.
 */
function api_block_inactive_user()
{
    $data = true;
    if (api_get_configuration_value('security_block_inactive_users_immediately') != 1) {
        return $data;
    }

    $userId = api_get_user_id();
    $homeUrl = api_get_path(WEB_PATH);
    if ($userId == 0) {
        return $data;
    }

    $sql = "SELECT active FROM ".Database::get_main_table(TABLE_MAIN_USER)."
            WHERE id = $userId";

    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $result_array = Database::fetch_array($result);
        $data = (bool) $result_array['active'];
    }
    if ($data == false) {
        $tpl = new Template(null, true, true, false, true, false, true, 0);
        $tpl->assign('hide_login_link', 1);

        //api_not_allowed(true, get_lang('AccountInactive'));
        // we were not in a course, return to home page
        $msg = Display::return_message(
            get_lang('AccountInactive'),
            'error',
            false
        );

        $msg .= '<p class="text-center">
                 <a class="btn btn-default" href="'.$homeUrl.'">'.get_lang('BackHome').'</a></p>';

        if (api_is_anonymous()) {
            $form = api_get_not_allowed_login_form();
            $msg .= '<div class="well">';
            $msg .= $form->returnForm();
            $msg .= '</div>';
        }

        $tpl->assign('content', $msg);
        $tpl->display_one_col_template();
        exit;
    }

    return $data;
}

/**
 * Function used to protect a teacher script.
 * The function blocks access when the user has no teacher rights.
 *
 * @return bool True if the current user can access the script, false otherwise
 *
 * @author Yoselyn Castillo
 */
function api_protect_teacher_script()
{
    if (!api_is_allowed_to_edit()) {
        api_not_allowed(true);

        return false;
    }

    return true;
}

/**
 * Function used to prevent anonymous users from accessing a script.
 *
 * @param bool|true $printHeaders
 *
 * @author Roan Embrechts
 *
 * @return bool
 */
function api_block_anonymous_users($printHeaders = true)
{
    $user = api_get_user_info();
    if (!(isset($user['user_id']) && $user['user_id']) || api_is_anonymous($user['user_id'], true)) {
        api_not_allowed($printHeaders);

        return false;
    }
    api_block_inactive_user();

    return true;
}

/**
 * Returns a rough evaluation of the browser's name and version based on very
 * simple regexp.
 *
 * @return array with the navigator name and version ['name' => '...', 'version' => '...']
 */
function api_get_navigator()
{
    $navigator = 'Unknown';
    $version = 0;

    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return ['name' => 'Unknown', 'version' => '0.0.0'];
    }

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== false) {
        $navigator = 'Opera';
        list(, $version) = explode('Opera', $_SERVER['HTTP_USER_AGENT']);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Edge') !== false) {
        $navigator = 'Edge';
        list(, $version) = explode('Edge', $_SERVER['HTTP_USER_AGENT']);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
        $navigator = 'Internet Explorer';
        list(, $version) = explode('MSIE ', $_SERVER['HTTP_USER_AGENT']);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) {
        $navigator = 'Chrome';
        list(, $version) = explode('Chrome', $_SERVER['HTTP_USER_AGENT']);
    } elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false) {
        $navigator = 'Safari';
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'Version/') !== false) {
            // If this Safari does have the "Version/" string in its user agent
            // then use that as a version indicator rather than what's after
            // "Safari/" which is rather a "build number" or something
            list(, $version) = explode('Version/', $_SERVER['HTTP_USER_AGENT']);
        } else {
            list(, $version) = explode('Safari/', $_SERVER['HTTP_USER_AGENT']);
        }
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false) {
        $navigator = 'Firefox';
        list(, $version) = explode('Firefox', $_SERVER['HTTP_USER_AGENT']);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Netscape') !== false) {
        $navigator = 'Netscape';
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'Netscape/') !== false) {
            list(, $version) = explode('Netscape', $_SERVER['HTTP_USER_AGENT']);
        } else {
            list(, $version) = explode('Navigator', $_SERVER['HTTP_USER_AGENT']);
        }
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Konqueror') !== false) {
        $navigator = 'Konqueror';
        list(, $version) = explode('Konqueror', $_SERVER['HTTP_USER_AGENT']);
    } elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'applewebkit') !== false) {
        $navigator = 'AppleWebKit';
        list(, $version) = explode('Version/', $_SERVER['HTTP_USER_AGENT']);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Gecko') !== false) {
        $navigator = 'Mozilla';
        list(, $version) = explode('; rv:', $_SERVER['HTTP_USER_AGENT']);
    }

    // Now cut extra stuff around (mostly *after*) the version number
    $version = preg_replace('/^([\/\s])?([\d\.]+)?.*/', '\2', $version);

    if (strpos($version, '.') === false) {
        $version = number_format(doubleval($version), 1);
    }

    return ['name' => $navigator, 'version' => $version];
}
/**
 * Check if it is a desktop or mobile browser.
 */
function api_is_browser_mobile(): bool
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        static $isMobile = false;
    } elseif (
        strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false
    ) {
        $isMobile = true;
    } else {
        $isMobile = false;
    }

    return $isMobile;
}

/**
 * @return true if user self registration is allowed, false otherwise
 */
function api_is_self_registration_allowed()
{
    return isset($GLOBALS['allowSelfReg']) ? $GLOBALS['allowSelfReg'] : false;
}

/**
 * This function returns the id of the user which is stored in the $_user array.
 *
 * example: The function can be used to check if a user is logged in
 *          if (api_get_user_id())
 *
 * @return int the id of the current user, 0 if is empty
 */
function api_get_user_id()
{
    $userInfo = Session::read('_user');
    if ($userInfo && isset($userInfo['user_id'])) {
        return (int) $userInfo['user_id'];
    }

    return 0;
}

/**
 * Gets the list of courses a specific user is subscribed to.
 *
 * @param int       User ID
 * @param bool $fetch_session Whether to get session courses or not - NOT YET IMPLEMENTED
 *
 * @return array Array of courses in the form [0]=>('code'=>xxx,'db'=>xxx,'dir'=>xxx,'status'=>d)
 *
 * @deprecated use CourseManager::get_courses_list_by_user_id()
 */
function api_get_user_courses($userId, $fetch_session = true)
{
    // Get out if not integer
    if ($userId != strval(intval($userId))) {
        return [];
    }

    $t_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $t_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

    $sql = "SELECT cc.id as real_id, cc.code code, cc.directory dir, cu.status status
            FROM $t_course cc, $t_course_user cu
            WHERE
                cc.id = cu.c_id AND
                cu.user_id = $userId AND
                cu.relation_type <> ".COURSE_RELATION_TYPE_RRHH;
    $result = Database::query($sql);
    if ($result === false) {
        return [];
    }

    $courses = [];
    while ($row = Database::fetch_array($result)) {
        // we only need the database name of the course
        $courses[] = $row;
    }

    return $courses;
}

/**
 * Formats user information into a standard array
 * This function should be only used inside api_get_user_info().
 *
 * @param array Non-standard user array
 * @param bool $add_password
 * @param bool $loadAvatars  turn off to improve performance
 *
 * @return array Standard user array
 */
function _api_format_user($user, $add_password = false, $loadAvatars = true)
{
    $result = [];

    $result['firstname'] = null;
    $result['lastname'] = null;

    if (isset($user['firstname']) && isset($user['lastname'])) { // with only lowercase
        $result['firstname'] = $user['firstname'];
        $result['lastname'] = $user['lastname'];
    } elseif (isset($user['firstName']) && isset($user['lastName'])) { // with uppercase letters
        $result['firstname'] = isset($user['firstName']) ? $user['firstName'] : null;
        $result['lastname'] = isset($user['lastName']) ? $user['lastName'] : null;
    }

    if (isset($user['email'])) {
        $result['mail'] = isset($user['email']) ? $user['email'] : null;
        $result['email'] = isset($user['email']) ? $user['email'] : null;
    } else {
        $result['mail'] = isset($user['mail']) ? $user['mail'] : null;
        $result['email'] = isset($user['mail']) ? $user['mail'] : null;
    }

    $result['complete_name'] = api_get_person_name($result['firstname'], $result['lastname']);
    $result['complete_name_with_username'] = $result['complete_name'];

    if (!empty($user['username']) && !api_get_configuration_value('hide_username_with_complete_name')) {
        $result['complete_name_with_username'] = $result['complete_name'].' ('.$user['username'].')';
    }

    $showEmail = api_get_setting('show_email_addresses') === 'true';
    if (!empty($user['email'])) {
        $result['complete_name_with_email_forced'] = $result['complete_name'].' ('.$user['email'].')';
        if ($showEmail) {
            $result['complete_name_with_email'] = $result['complete_name'].' ('.$user['email'].')';
        }
    } else {
        $result['complete_name_with_email'] = $result['complete_name'];
        $result['complete_name_with_email_forced'] = $result['complete_name'];
    }

    // Kept for historical reasons
    $result['firstName'] = $result['firstname'];
    $result['lastName'] = $result['lastname'];

    $attributes = [
        'phone',
        'address',
        'picture_uri',
        'official_code',
        'status',
        'active',
        'auth_source',
        'username',
        'theme',
        'language',
        'creator_id',
        'registration_date',
        'hr_dept_id',
        'expiration_date',
        'last_login',
        'user_is_online',
    ];

    if (api_get_setting('extended_profile') === 'true') {
        $attributes[] = 'competences';
        $attributes[] = 'diplomas';
        $attributes[] = 'teach';
        $attributes[] = 'openarea';
    }

    foreach ($attributes as $attribute) {
        $result[$attribute] = isset($user[$attribute]) ? $user[$attribute] : null;
    }

    $user_id = (int) $user['user_id'];
    // Maintain the user_id index for backwards compatibility
    $result['user_id'] = $result['id'] = $user_id;

    $hasCertificates = Certificate::getCertificateByUser($user_id);
    $result['has_certificates'] = 0;
    if (!empty($hasCertificates)) {
        $result['has_certificates'] = 1;
    }

    $result['icon_status'] = '';
    $result['icon_status_medium'] = '';

    $result['is_admin'] = UserManager::is_admin($user_id);

    // Getting user avatar.
    if ($loadAvatars) {
        $result['avatar'] = '';
        $result['avatar_no_query'] = '';
        $result['avatar_small'] = '';
        $result['avatar_medium'] = '';

        if (!isset($user['avatar'])) {
            $originalFile = UserManager::getUserPicture(
                $user_id,
                USER_IMAGE_SIZE_ORIGINAL,
                null,
                $result
            );
            $result['avatar'] = $originalFile;
            $avatarString = explode('?', $result['avatar']);
            $result['avatar_no_query'] = reset($avatarString);
        } else {
            $result['avatar'] = $user['avatar'];
            $avatarString = explode('?', $user['avatar']);
            $result['avatar_no_query'] = reset($avatarString);
        }

        if (!isset($user['avatar_small'])) {
            $smallFile = UserManager::getUserPicture(
                $user_id,
                USER_IMAGE_SIZE_SMALL,
                null,
                $result
            );
            $result['avatar_small'] = $smallFile;
        } else {
            $result['avatar_small'] = $user['avatar_small'];
        }

        if (!isset($user['avatar_medium'])) {
            $mediumFile = UserManager::getUserPicture(
                $user_id,
                USER_IMAGE_SIZE_MEDIUM,
                null,
                $result
            );
            $result['avatar_medium'] = $mediumFile;
        } else {
            $result['avatar_medium'] = $user['avatar_medium'];
        }

        $urlImg = api_get_path(WEB_IMG_PATH);
        $iconStatus = '';
        $iconStatusMedium = '';
        $label = '';
        switch ($result['status']) {
            case STUDENT:
                if ($result['has_certificates']) {
                    $iconStatus = $urlImg.'icons/svg/identifier_graduated.svg';
                    $label = get_lang('Graduated');
                } else {
                    $iconStatus = $urlImg.'icons/svg/identifier_student.svg';
                    $label = get_lang('Student');
                }
                break;
            case COURSEMANAGER:
                if ($result['is_admin']) {
                    $iconStatus = $urlImg.'icons/svg/identifier_admin.svg';
                    $label = get_lang('Admin');
                } else {
                    $iconStatus = $urlImg.'icons/svg/identifier_teacher.svg';
                    $label = get_lang('Teacher');
                }
                break;
            case STUDENT_BOSS:
                $iconStatus = $urlImg.'icons/svg/identifier_teacher.svg';
                $label = get_lang('StudentBoss');
                break;
        }

        if (!empty($iconStatus)) {
            $iconStatusMedium = '<img src="'.$iconStatus.'" width="32px" height="32px">';
            $iconStatus = '<img src="'.$iconStatus.'" width="22px" height="22px">';
        }

        $result['icon_status'] = $iconStatus;
        $result['icon_status_label'] = $label;
        $result['icon_status_medium'] = $iconStatusMedium;
    }

    if (isset($user['user_is_online'])) {
        $result['user_is_online'] = $user['user_is_online'] == true ? 1 : 0;
    }
    if (isset($user['user_is_online_in_chat'])) {
        $result['user_is_online_in_chat'] = (int) $user['user_is_online_in_chat'];
    }

    if ($add_password) {
        $result['password'] = $user['password'];
    }

    if (isset($result['profile_completed'])) {
        $result['profile_completed'] = $user['profile_completed'];
    }

    $result['profile_url'] = api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user_id;

    // Send message link
    $sendMessage = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&user_id='.$user_id;
    $result['complete_name_with_message_link'] = Display::url(
        $result['complete_name_with_username'],
        $sendMessage,
        ['class' => 'ajax']
    );

    if (isset($user['extra'])) {
        $result['extra'] = $user['extra'];
    }

    return $result;
}

/**
 * Finds all the information about a user.
 * If no parameter is passed you find all the information about the current user.
 *
 * @param int  $user_id
 * @param bool $checkIfUserOnline
 * @param bool $showPassword
 * @param bool $loadExtraData
 * @param bool $loadOnlyVisibleExtraData Get the user extra fields that are visible
 * @param bool $loadAvatars              turn off to improve performance and if avatars are not needed
 * @param bool $updateCache              update apc cache if exists
 *
 * @return mixed $user_info user_id, lastname, firstname, username, email, etc or false on error
 *
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @author Julio Montoya
 *
 * @version 21 September 2004
 */
function api_get_user_info(
    $user_id = 0,
    $checkIfUserOnline = false,
    $showPassword = false,
    $loadExtraData = false,
    $loadOnlyVisibleExtraData = false,
    $loadAvatars = true,
    $updateCache = false
) {
    $apcVar = null;
    $user = false;
    $cacheAvailable = api_get_configuration_value('apc');

    if (empty($user_id)) {
        $userFromSession = Session::read('_user');

        if (isset($userFromSession)) {
            if ($cacheAvailable === true &&
                (
                    empty($userFromSession['is_anonymous']) &&
                    (isset($userFromSession['status']) && $userFromSession['status'] != ANONYMOUS)
                )
            ) {
                $apcVar = api_get_configuration_value('apc_prefix').'userinfo_'.$userFromSession['user_id'];
                if (apcu_exists($apcVar)) {
                    if ($updateCache) {
                        apcu_store($apcVar, $userFromSession, 60);
                    }
                    $user = apcu_fetch($apcVar);
                } else {
                    $user = _api_format_user(
                        $userFromSession,
                        $showPassword,
                        $loadAvatars
                    );
                    apcu_store($apcVar, $user, 60);
                }
            } else {
                $user = _api_format_user(
                    $userFromSession,
                    $showPassword,
                    $loadAvatars
                );
            }

            return $user;
        }

        return false;
    }

    // Make sure user_id is safe
    $user_id = (int) $user_id;

    // Re-use user information if not stale and already stored in APCu
    if ($cacheAvailable === true) {
        $apcVar = api_get_configuration_value('apc_prefix').'userinfo_'.$user_id;
        if (apcu_exists($apcVar) && $updateCache == false && $checkIfUserOnline == false) {
            $user = apcu_fetch($apcVar);

            return $user;
        }
    }

    $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
            WHERE id = $user_id";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $result_array = Database::fetch_array($result);
        $result_array['user_is_online_in_chat'] = 0;
        if ($checkIfUserOnline) {
            $use_status_in_platform = user_is_online($user_id);
            $result_array['user_is_online'] = $use_status_in_platform;
            $user_online_in_chat = 0;
            if ($use_status_in_platform) {
                $user_status = UserManager::get_extra_user_data_by_field(
                    $user_id,
                    'user_chat_status',
                    false,
                    true
                );
                if ((int) $user_status['user_chat_status'] == 1) {
                    $user_online_in_chat = 1;
                }
            }
            $result_array['user_is_online_in_chat'] = $user_online_in_chat;
        }

        if ($loadExtraData) {
            $fieldValue = new ExtraFieldValue('user');
            $result_array['extra'] = $fieldValue->getAllValuesForAnItem(
                $user_id,
                $loadOnlyVisibleExtraData
            );
        }
        $user = _api_format_user($result_array, $showPassword, $loadAvatars);
    }

    if ($cacheAvailable === true) {
        apcu_store($apcVar, $user, 60);
    }

    return $user;
}

/**
 * @param int $userId
 *
 * @return User
 */
function api_get_user_entity($userId)
{
    $userId = (int) $userId;
    $repo = UserManager::getRepository();

    /** @var User $user */
    $user = $repo->find($userId);

    return $user;
}

/**
 * Finds all the information about a user from username instead of user id.
 *
 * @param string $username
 *
 * @return mixed $user_info array user_id, lastname, firstname, username, email or false on error
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
function api_get_user_info_from_username($username, $authSource = null)
{
    if (empty($username)) {
        return false;
    }
    $username = trim($username);

    $andAuthSource = "";
    if (isset($authSource)) {
        $authSource = Database::escape_string($authSource);
        $andAuthSource = " AND auth_source = '$authSource'";
    }
    $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
            WHERE username='".Database::escape_string($username)."' $andAuthSource";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $resultArray = Database::fetch_array($result);

        return _api_format_user($resultArray);
    }

    return false;
}

/**
 * Get first user with an email.
 *
 * @param string $email
 *
 * @return array|bool
 */
function api_get_user_info_from_email($email = '')
{
    if (empty($email)) {
        return false;
    }
    $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
            WHERE email ='".Database::escape_string($email)."' LIMIT 1";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $resultArray = Database::fetch_array($result);

        return _api_format_user($resultArray);
    }

    return false;
}

/**
 * @return string
 */
function api_get_course_id()
{
    return Session::read('_cid', null);
}

/**
 * Returns the current course id (integer).
 *
 * @param string $code Optional course code
 *
 * @return int
 */
function api_get_course_int_id($code = null)
{
    if (!empty($code)) {
        $code = Database::escape_string($code);
        $row = Database::select(
            'id',
            Database::get_main_table(TABLE_MAIN_COURSE),
            ['where' => ['code = ?' => [$code]]],
            'first'
        );

        if (is_array($row) && isset($row['id'])) {
            return $row['id'];
        } else {
            return false;
        }
    }

    return Session::read('_real_cid', 0);
}

/**
 * Returns the current course directory.
 *
 * This function relies on api_get_course_info()
 *
 * @param string    The course code - optional (takes it from session if not given)
 *
 * @return string The directory where the course is located inside the Chamilo "courses" directory
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
function api_get_course_path($course_code = null)
{
    $info = !empty($course_code) ? api_get_course_info($course_code) : api_get_course_info();

    return $info['path'];
}

/**
 * Gets a course setting from the current course_setting table. Try always using integer values.
 *
 * @param string $settingName The name of the setting we want from the table
 * @param array  $courseInfo
 * @param bool   $force       force checking the value in the database
 *
 * @return mixed The value of that setting in that table. Return -1 if not found.
 */
function api_get_course_setting($settingName, $courseInfo = [], $force = false)
{
    if (empty($courseInfo)) {
        $courseInfo = api_get_course_info();
    }

    if (empty($courseInfo) || empty($settingName)) {
        return -1;
    }

    $courseId = isset($courseInfo['real_id']) && !empty($courseInfo['real_id']) ? $courseInfo['real_id'] : 0;

    if (empty($courseId)) {
        return -1;
    }

    static $courseSettingInfo = [];

    if ($force) {
        $courseSettingInfo = [];
    }

    if (!isset($courseSettingInfo[$courseId])) {
        $table = Database::get_course_table(TABLE_COURSE_SETTING);
        $settingName = Database::escape_string($settingName);

        $sql = "SELECT variable, value FROM $table
                WHERE c_id = $courseId ";
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $result = Database::store_result($res, 'ASSOC');
            $courseSettingInfo[$courseId] = array_column($result, 'value', 'variable');

            if (isset($courseSettingInfo[$courseId]['email_alert_manager_on_new_quiz'])) {
                $value = $courseSettingInfo[$courseId]['email_alert_manager_on_new_quiz'];
                if (!is_null($value)) {
                    $result = explode(',', $value);
                    $courseSettingInfo[$courseId]['email_alert_manager_on_new_quiz'] = $result;
                }
            }
        }
    }

    if (isset($courseSettingInfo[$courseId]) && array_key_exists($settingName, $courseSettingInfo[$courseId])) {
        return $courseSettingInfo[$courseId][$settingName];
    }

    return -1;
}

function api_get_course_plugin_setting($plugin, $settingName, $courseInfo = [])
{
    $value = api_get_course_setting($settingName, $courseInfo, true);

    if (-1 === $value) {
        // Check global settings
        $value = api_get_plugin_setting($plugin, $settingName);
        if ($value === 'true') {
            return 1;
        }
        if ($value === 'false') {
            return 0;
        }
        if (null === $value) {
            return -1;
        }
    }

    return $value;
}

/**
 * Gets an anonymous user ID.
 *
 * For some tools that need tracking, like the learnpath tool, it is necessary
 * to have a usable user-id to enable some kind of tracking, even if not
 * perfect. An anonymous ID is taken from the users table by looking for a
 * status of "6" (anonymous).
 *
 * @return int User ID of the anonymous user, or O if no anonymous user found
 */
function api_get_anonymous_id()
{
    // Find if another anon is connected now
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
    $tableU = Database::get_main_table(TABLE_MAIN_USER);
    $ip = Database::escape_string(api_get_real_ip());
    $max = (int) api_get_configuration_value('max_anonymous_users');
    if ($max >= 2) {
        $sql = "SELECT * FROM $table as TEL
                JOIN $tableU as U
                ON U.user_id = TEL.login_user_id
                WHERE TEL.user_ip = '$ip'
                    AND U.status = ".ANONYMOUS."
                    AND U.user_id != 2 ";

        $result = Database::query($sql);
        if (empty(Database::num_rows($result))) {
            $login = uniqid('anon_');
            $email = ' anonymous@localhost.local';
            if (api_get_setting('login_is_email') == 'true') {
                $login = $login."@localhost.local";
                $email = $login;
            }
            $anonList = UserManager::get_user_list(['status' => ANONYMOUS], ['registration_date ASC']);
            if (count($anonList) >= $max) {
                foreach ($anonList as $userToDelete) {
                    UserManager::delete_user($userToDelete['user_id']);
                    break;
                }
            }
            // Return the user ID
            return UserManager::create_user(
                $login,
                'anon',
                ANONYMOUS,
                $email,
                $login,
                $login
            );
        } else {
            $row = Database::fetch_array($result, 'ASSOC');

            return $row['user_id'];
        }
    }

    $table = Database::get_main_table(TABLE_MAIN_USER);
    $sql = "SELECT user_id
            FROM $table
            WHERE status = ".ANONYMOUS." ";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_array($res, 'ASSOC');

        return $row['user_id'];
    }

    // No anonymous user was found.
    return 0;
}

/**
 * @param string $courseCode
 * @param int    $sessionId
 * @param int    $groupId
 *
 * @return string
 */
function api_get_cidreq_params($courseCode, $sessionId = 0, $groupId = 0)
{
    $courseCode = !empty($courseCode) ? htmlspecialchars($courseCode) : '';
    $sessionId = !empty($sessionId) ? (int) $sessionId : 0;
    $groupId = !empty($groupId) ? (int) $groupId : 0;

    $url = 'cidReq='.$courseCode;
    $url .= '&id_session='.$sessionId;
    $url .= '&gidReq='.$groupId;

    return $url;
}

/**
 * Returns the current course url part including session, group, and gradebook params.
 *
 * @param bool   $addSessionId
 * @param bool   $addGroupId
 * @param string $origin
 *
 * @return string Course & session references to add to a URL
 */
function api_get_cidreq($addSessionId = true, $addGroupId = true, $origin = '')
{
    $courseCode = api_get_course_id();
    $url = empty($courseCode) ? '' : 'cidReq='.urlencode(htmlspecialchars($courseCode));
    $origin = empty($origin) ? api_get_origin() : urlencode(Security::remove_XSS($origin));

    if ($addSessionId) {
        if (!empty($url)) {
            $url .= api_get_session_id() == 0 ? '&id_session=0' : '&id_session='.api_get_session_id();
        }
    }

    if ($addGroupId) {
        if (!empty($url)) {
            $url .= api_get_group_id() == 0 ? '&gidReq=0' : '&gidReq='.api_get_group_id();
        }
    }

    if (!empty($url)) {
        $url .= '&gradebook='.intval(api_is_in_gradebook());
        $url .= '&origin='.$origin;
    }

    return $url;
}

/**
 * Get if we visited a gradebook page.
 *
 * @return bool
 */
function api_is_in_gradebook()
{
    return Session::read('in_gradebook', false);
}

/**
 * Set that we are in a page inside a gradebook.
 */
function api_set_in_gradebook()
{
    Session::write('in_gradebook', true);
}

/**
 * Remove gradebook session.
 */
function api_remove_in_gradebook()
{
    Session::erase('in_gradebook');
}

/**
 * Returns the current course info array see api_format_course_array()
 * If the course_code is given, the returned array gives info about that
 * particular course, if none given it gets the course info from the session.
 *
 * @param string $course_code
 *
 * @return array
 */
function api_get_course_info($course_code = null)
{
    if (!empty($course_code)) {
        $course_code = Database::escape_string($course_code);
        $courseId = api_get_course_int_id($course_code);

        if (empty($courseId)) {
            return [];
        }

        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT
                    course.*,
                    course_category.code faCode,
                    course_category.name faName
                FROM $course_table
                LEFT JOIN $course_cat_table
                ON course.category_code = course_category.code
                WHERE course.id = $courseId";
        $result = Database::query($sql);
        $courseInfo = [];
        if (Database::num_rows($result) > 0) {
            $data = Database::fetch_array($result);
            $courseInfo = api_format_course_array($data);
        }

        return $courseInfo;
    }

    global $_course;
    if ($_course == '-1') {
        $_course = [];
    }

    return $_course;
}

/**
 * @param int $courseId
 *
 * @return \Chamilo\CoreBundle\Entity\Course
 */
function api_get_course_entity($courseId = 0)
{
    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    }

    return Database::getManager()->getRepository('ChamiloCoreBundle:Course')->find($courseId);
}

function api_get_group_entity($id = 0)
{
    if (empty($id)) {
        $id = api_get_group_id();
    }

    return Database::getManager()->getRepository('ChamiloCourseBundle:CGroupInfo')->find($id);
}

/**
 * @param int $id
 *
 * @return \Chamilo\CoreBundle\Entity\Session
 */
function api_get_session_entity($id = 0)
{
    if (empty($id)) {
        $id = api_get_session_id();
    }

    return Database::getManager()->getRepository('ChamiloCoreBundle:Session')->find($id);
}

/**
 * Returns the current course info array.

 * Now if the course_code is given, the returned array gives info about that
 * particular course, not specially the current one.
 *
 * @param int $id Numeric ID of the course
 *
 * @return array The course info as an array formatted by api_format_course_array, including category.name
 */
function api_get_course_info_by_id($id = null)
{
    if (!empty($id)) {
        $id = (int) $id;
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT
                    course.*,
                    course_category.code faCode,
                    course_category.name faName
                FROM $course_table
                LEFT JOIN $course_cat_table
                ON course.category_code = course_category.code
                WHERE course.id = $id";
        $result = Database::query($sql);
        $_course = [];
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result);
            $_course = api_format_course_array($row);
        }

        return $_course;
    }

    global $_course;
    if ($_course == '-1') {
        $_course = [];
    }

    return $_course;
}

/**
 * Reformat the course array (output by api_get_course_info()) in order, mostly,
 * to switch from 'code' to 'id' in the array. This is a legacy feature and is
 * now possibly causing massive confusion as a new "id" field has been added to
 * the course table in 1.9.0.
 *
 * @param $course_data
 *
 * @return array
 *
 * @todo eradicate the false "id"=code field of the $_course array and use the int id
 */
function api_format_course_array($course_data)
{
    if (empty($course_data)) {
        return [];
    }

    $_course = [];
    $_course['id'] = $course_data['code'];
    $_course['real_id'] = $course_data['id'];

    // Added
    $_course['code'] = $course_data['code'];
    $_course['name'] = $course_data['title'];
    $_course['title'] = $course_data['title'];
    $_course['official_code'] = $course_data['visual_code'];
    $_course['visual_code'] = $course_data['visual_code'];
    $_course['sysCode'] = $course_data['code'];
    $_course['path'] = $course_data['directory']; // Use as key in path.
    $_course['directory'] = $course_data['directory'];
    $_course['creation_date'] = $course_data['creation_date'];
    $_course['titular'] = $course_data['tutor_name'];
    $_course['tutor_name'] = $course_data['tutor_name'];
    $_course['language'] = $course_data['course_language'];
    $_course['extLink']['url'] = $course_data['department_url'];
    $_course['extLink']['name'] = $course_data['department_name'];
    $_course['categoryCode'] = $course_data['faCode'];
    $_course['category_code'] = $course_data['faCode'];
    $_course['categoryName'] = $course_data['faName'];
    $_course['visibility'] = $course_data['visibility'];
    $_course['subscribe_allowed'] = $course_data['subscribe'];
    $_course['subscribe'] = $course_data['subscribe'];
    $_course['unsubscribe'] = $course_data['unsubscribe'];
    $_course['course_language'] = $course_data['course_language'];
    $_course['activate_legal'] = isset($course_data['activate_legal']) ? $course_data['activate_legal'] : false;
    $_course['legal'] = $course_data['legal'];
    $_course['show_score'] = $course_data['show_score']; //used in the work tool
    $_course['department_name'] = $course_data['department_name'];
    $_course['department_url'] = $course_data['department_url'];

    $courseSys = api_get_path(SYS_COURSE_PATH).$course_data['directory'];
    $webCourseHome = api_get_path(WEB_COURSE_PATH).$course_data['directory'];

    // Course password
    $_course['registration_code'] = !empty($course_data['registration_code']) ? sha1($course_data['registration_code']) : null;
    $_course['disk_quota'] = $course_data['disk_quota'];
    $_course['course_public_url'] = $webCourseHome.'/index.php';
    $_course['course_sys_path'] = $courseSys.'/';

    if (array_key_exists('add_teachers_to_sessions_courses', $course_data)) {
        $_course['add_teachers_to_sessions_courses'] = $course_data['add_teachers_to_sessions_courses'];
    }

    // Course image
    $_course['course_image_source'] = '';
    if (file_exists($courseSys.'/course-pic85x85.png')) {
        $url_image = $webCourseHome.'/course-pic85x85.png';
        $_course['course_image_source'] = $courseSys.'/course-pic85x85.png';
    } else {
        $url_image = Display::return_icon(
            'course.png',
            null,
            null,
            ICON_SIZE_LARGE,
            null,
            true,
            true
        );
    }
    $_course['course_image'] = $url_image;

    // Course large image
    $_course['course_image_large_source'] = '';
    if (file_exists($courseSys.'/course-pic.png')) {
        $url_image = $webCourseHome.'/course-pic.png';
        $_course['course_image_large_source'] = $courseSys.'/course-pic.png';
    } else {
        $url_image = Display::return_icon(
            'session_default.png',
            null,
            null,
            null,
            null,
            true,
            true
        );
    }

    $_course['course_image_large'] = $url_image;

    // email pictures
    // Course image
    $url_image = null;
    $_course['course_email_image_source'] = '';
    $mailPicture = $courseSys.'/course-email-pic-cropped.png';
    if (file_exists($mailPicture)) {
        $url_image = $webCourseHome.'/course-email-pic-cropped.png';
        $_course['course_email_image_source'] = $mailPicture;
    }
    $_course['course_email_image'] = $url_image;

    // Course large image
    $url_image = null;
    $_course['course_email_image_large_source'] = '';
    $mailPicture = $courseSys.'/course-email-pic.png';
    if (file_exists($mailPicture)) {
        $url_image = $webCourseHome.'/course-email-pic.png';
        $_course['course_email_image_large_source'] = $mailPicture;
    }

    $_course['course_email_image_large'] = $url_image;

    return $_course;
}

/**
 * Returns a difficult to guess password.
 *
 * @param int $length the length of the password
 *
 * @return string the generated password
 */
function api_generate_password($length = 8)
{
    if ($length < 2) {
        $length = 2;
    }

    $charactersLowerCase = Security::CHAR_LOWER;
    $charactersUpperCase = Security::CHAR_UPPER;

    $minNumbers = 2;
    $length = $length - $minNumbers;
    $minLowerCase = round($length / 2);
    $minUpperCase = $length - $minLowerCase;

    $password = '';
    $passwordRequirements = api_get_configuration_value('password_requirements');

    $factory = new RandomLib\Factory();
    $generator = $factory->getMediumStrengthGenerator();

    if (!empty($passwordRequirements)) {
        $length = $passwordRequirements['min']['length'];
        $minNumbers = $passwordRequirements['min']['numeric'];
        $minLowerCase = $passwordRequirements['min']['lowercase'];
        $minUpperCase = $passwordRequirements['min']['uppercase'];
        $minSpecials = $passwordRequirements['min']['specials'];

        $rest = $length - $minNumbers - $minLowerCase - $minUpperCase - $minSpecials;
        // Add the rest to fill the length requirement
        if ($rest > 0) {
            $password .= $generator->generateString(
                $rest,
                $charactersLowerCase.$charactersUpperCase
            );
        }

        $password .= $generator->generateString($minSpecials, Security::CHAR_SYMBOLS);
    }

    // Min digits default 2
    for ($i = 0; $i < $minNumbers; $i++) {
        $password .= $generator->generateInt(2, 9);
    }

    // Min lowercase
    $password .= $generator->generateString($minLowerCase, $charactersLowerCase);

    // Min uppercase
    $password .= $generator->generateString($minUpperCase, $charactersUpperCase);
    $password = str_shuffle($password);

    return $password;
}

/**
 * Checks a password to see wether it is OK to use.
 *
 * @param string $password
 *
 * @return bool if the password is acceptable, false otherwise
 *              Notes about what a password "OK to use" is:
 *              1. The password should be at least 5 characters long.
 *              2. Only English letters (uppercase or lowercase, it doesn't matter) and digits are allowed.
 *              3. The password should contain at least 3 letters.
 *              4. It should contain at least 2 digits.
 *              Settings will change if the configuration value is set: password_requirements
 */
function api_check_password($password)
{
    $passwordRequirements = Security::getPasswordRequirements();

    $minLength = $passwordRequirements['min']['length'];
    $minNumbers = $passwordRequirements['min']['numeric'];
    // Optional
    $minLowerCase = $passwordRequirements['min']['lowercase'];
    $minUpperCase = $passwordRequirements['min']['uppercase'];
    $minSpecials = $passwordRequirements['min']['specials'];

    $minLetters = $minLowerCase + $minUpperCase;
    $passwordLength = api_strlen($password);

    $conditions = [
        'min_length' => $passwordLength >= $minLength,
    ];

    $digits = 0;
    $lowerCase = 0;
    $upperCase = 0;
    $specials = 0;

    for ($i = 0; $i < $passwordLength; $i++) {
        $currentCharacter = api_substr($password, $i, 1);
        $currentCharacterCode = api_ord($currentCharacter);
        if ($currentCharacterCode >= 65 && $currentCharacterCode <= 90) {
            $upperCase++;
        }

        if ($currentCharacterCode >= 97 && $currentCharacterCode <= 122) {
            $lowerCase++;
        }
        if ($currentCharacterCode >= 48 && $currentCharacterCode <= 57) {
            $digits++;
        }

        if (false !== strpos(Security::CHAR_SYMBOLS, $currentCharacter)) {
            $specials++;
        }
    }

    // Min number of digits
    $conditions['min_numeric'] = $digits >= $minNumbers;

    if (!empty($minUpperCase)) {
        // Uppercase
        $conditions['min_uppercase'] = $upperCase >= $minUpperCase;
    }

    if (!empty($minLowerCase)) {
        // Lowercase
        $conditions['min_lowercase'] = $lowerCase >= $minLowerCase;
    }

    if (!empty($minSpecials)) {
        $conditions['min_specials'] = $specials >= $minSpecials;
    }

    // Min letters
    $letters = $upperCase + $lowerCase;
    $conditions['min_letters'] = $letters >= $minLetters;

    $isPasswordOk = true;
    foreach ($conditions as $condition) {
        if ($condition === false) {
            $isPasswordOk = false;
            break;
        }
    }

    if ($isPasswordOk === false) {
        $output = get_lang('NewPasswordRequirementsNotMatched').'<br />';
        $output .= Security::getPasswordRequirementsToString($conditions);

        Display::addFlash(Display::return_message($output, 'warning', false));
    }

    return $isPasswordOk;
}

/**
 * Clears the user ID from the session if it was the anonymous user. Generally
 * used on out-of-tools pages to remove a user ID that could otherwise be used
 * in the wrong context.
 * This function is to be used in conjunction with the api_set_anonymous()
 * function to simulate the user existence in case of an anonymous visit.
 *
 * @param bool      database check switch - passed to api_is_anonymous()
 *
 * @return bool true if succesfully unregistered, false if not anonymous
 */
function api_clear_anonymous($db_check = false)
{
    global $_user;
    if (isset($_user['user_id']) && api_is_anonymous($_user['user_id'], $db_check)) {
        unset($_user['user_id']);
        Session::erase('_uid');

        return true;
    }

    return false;
}

/**
 * Returns the status string corresponding to the status code.
 *
 * @author Noel Dieschburg
 *
 * @param int $status_code The integer status code (usually in the form of a constant)
 *
 * @return string
 */
function get_status_from_code($status_code)
{
    switch ($status_code) {
        case STUDENT:
            return get_lang('Student', '');
        case COURSEMANAGER:
            return get_lang('Teacher', '');
        case SESSIONADMIN:
            return get_lang('SessionsAdmin', '');
        case DRH:
            return get_lang('Drh', '');
        case ANONYMOUS:
            return get_lang('Anonymous', '');
        case PLATFORM_ADMIN:
            return get_lang('Administrator', '');
        case SESSION_COURSE_COACH:
            return get_lang('SessionCourseCoach', '');
        case SESSION_GENERAL_COACH:
            return get_lang('SessionGeneralCoach', '');
        case COURSE_TUTOR:
            return get_lang('CourseAssistant', '');
        case STUDENT_BOSS:
            return get_lang('StudentBoss', '');
        case INVITEE:
            return get_lang('Invitee', '');
    }

    return '';
}

/**
 * Sets the current user as anonymous if it hasn't been identified yet. This
 * function should be used inside a tool only. The function api_clear_anonymous()
 * acts in the opposite direction by clearing the anonymous user's data every
 * time we get on a course homepage or on a neutral page (index, admin, my space).
 *
 * @return bool true if set user as anonymous, false if user was already logged in or anonymous id could not be found
 */
function api_set_anonymous()
{
    global $_user;

    if (!empty($_user['user_id'])) {
        return false;
    }

    $user_id = api_get_anonymous_id();
    if ($user_id == 0) {
        return false;
    }

    if (isset($_user['is_anonymous'])) {
        return false;
    }

    Session::erase('_user');
    $_user['user_id'] = $user_id;
    $_user['is_anonymous'] = true;
    $GLOBALS['_user'] = $_user;
    Session::write('_user', $_user);

    return true;
}

/**
 * Gets the current Chamilo (not PHP/cookie) session ID.
 *
 * @return int O if no active session, the session ID otherwise
 */
function api_get_session_id()
{
    return (int) Session::read('id_session', 0);
}

/**
 * Gets the current Chamilo (not social network) group ID.
 *
 * @return int O if no active group, the group id otherwise
 */
function api_get_group_id()
{
    return (int) Session::read('_gid', 0);
}

/**
 * Gets the current or given session name.
 *
 * @param   int     Session ID (optional)
 *
 * @return string The session name, or null if not found
 */
function api_get_session_name($session_id = 0)
{
    if (empty($session_id)) {
        $session_id = api_get_session_id();
        if (empty($session_id)) {
            return null;
        }
    }
    $t = Database::get_main_table(TABLE_MAIN_SESSION);
    $s = "SELECT name FROM $t WHERE id = ".(int) $session_id;
    $r = Database::query($s);
    $c = Database::num_rows($r);
    if ($c > 0) {
        //technically, there can be only one, but anyway we take the first
        $rec = Database::fetch_array($r);

        return $rec['name'];
    }

    return null;
}

/**
 * Gets the session info by id.
 *
 * @param int $id Session ID
 *
 * @return array information of the session
 */
function api_get_session_info($id)
{
    return SessionManager::fetch($id);
}

/**
 * Gets the session visibility by session id.
 *
 * @param int  $session_id
 * @param int  $courseId
 * @param bool $ignore_visibility_for_admins
 * @param int  $userId
 *
 * @return int
 *             0 = session still available,
 *             SESSION_VISIBLE_READ_ONLY = 1,
 *             SESSION_VISIBLE = 2,
 *             SESSION_INVISIBLE = 3
 */
function api_get_session_visibility(
    $session_id,
    $courseId = null,
    $ignore_visibility_for_admins = true,
    $userId = 0
) {
    if (api_is_platform_admin()) {
        if ($ignore_visibility_for_admins) {
            return SESSION_AVAILABLE;
        }
    }

    $userId = empty($userId) ? api_get_user_id() : (int) $userId;

    $now = time();
    if (empty($session_id)) {
        return 0; // Means that the session is still available.
    }

    $session_id = (int) $session_id;
    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

    $result = Database::query("SELECT * FROM $tbl_session WHERE id = $session_id");

    if (Database::num_rows($result) <= 0) {
        return SESSION_INVISIBLE;
    }

    $row = Database::fetch_array($result, 'ASSOC');
    $visibility = $row['visibility'];

    // I don't care the session visibility.
    if (empty($row['access_start_date']) && empty($row['access_end_date'])) {

        // Session duration per student.
        if (isset($row['duration']) && !empty($row['duration'])) {
            if (api_get_configuration_value('session_coach_access_after_duration_end') == true && api_is_teacher()) {
                return SESSION_AVAILABLE;
            }

            $duration = $row['duration'] * 24 * 60 * 60;
            $courseAccess = CourseManager::getFirstCourseAccessPerSessionAndUser($session_id, $userId);

            // If there is a session duration but there is no previous
            // access by the user, then the session is still available
            if (0 == count($courseAccess)) {
                return SESSION_AVAILABLE;
            }

            $currentTime = time();
            $firstAccess = isset($courseAccess['login_course_date'])
                ? api_strtotime($courseAccess['login_course_date'], 'UTC')
                : 0;
            $userDurationData = SessionManager::getUserSession($userId, $session_id);
            $userDuration = isset($userDurationData['duration'])
                ? (intval($userDurationData['duration']) * 24 * 60 * 60)
                : 0;

            $totalDuration = $firstAccess + $duration + $userDuration;

            return $totalDuration > $currentTime ? SESSION_AVAILABLE : SESSION_VISIBLE_READ_ONLY;
        }

        return SESSION_AVAILABLE;
    }

    // If start date was set.
    if (!empty($row['access_start_date'])) {
        $visibility = $now > api_strtotime($row['access_start_date'], 'UTC') ? SESSION_AVAILABLE : SESSION_INVISIBLE;
    }

    // If the end date was set.
    if (!empty($row['access_end_date'])) {
        // Only if date_start said that it was ok
        if ($visibility === SESSION_AVAILABLE) {
            $visibility = $now < api_strtotime($row['access_end_date'], 'UTC')
                ? SESSION_AVAILABLE // Date still available
                : $row['visibility']; // Session ends
        }
    }

    // If I'm a coach the visibility can change in my favor depending in the coach dates.
    $isCoach = api_is_coach($session_id, $courseId, $userId);

    if ($isCoach) {
        // Test start date.
        if (!empty($row['coach_access_start_date'])) {
            $start = api_strtotime($row['coach_access_start_date'], 'UTC');
            $visibility = $start < $now ? SESSION_AVAILABLE : SESSION_INVISIBLE;
        }

        // Test end date.
        if (!empty($row['coach_access_end_date'])) {
            if ($visibility === SESSION_AVAILABLE) {
                $endDateCoach = api_strtotime($row['coach_access_end_date'], 'UTC');
                $visibility = $endDateCoach >= $now ? SESSION_AVAILABLE : $row['visibility'];
            }
        }
    }

    return $visibility;
}

/**
 * This function returns a (star) session icon if the session is not null and
 * the user is not a student.
 *
 * @param int $sessionId
 * @param int $statusId  User status id - if 5 (student) or in student view, will return empty
 *
 * @return string Session icon
 */
function api_get_session_image($sessionId, $statusId)
{
    $sessionId = (int) $sessionId;
    $image = '';
    $studentView = !empty($_SESSION['studentview']) && $_SESSION['studentview'] == 'studentview';
    if ($statusId != STUDENT && !$studentView) {
        // Check whether is not a student
        if ($sessionId > 0) {
            $image = '&nbsp;&nbsp;'.Display::return_icon(
                'star.png',
                get_lang('SessionSpecificResource'),
                ['align' => 'absmiddle'],
                ICON_SIZE_SMALL
            );
        }
    }

    return $image;
}

/**
 * This function add an additional condition according to the session of the course.
 *
 * @param int    $session_id        session id
 * @param bool   $and               optional, true if more than one condition false if the only condition in the query
 * @param bool   $with_base_content optional, true to accept content with session=0 as well,
 *                                  false for strict session condition
 * @param string $session_field
 *
 * @return string condition of the session
 */
function api_get_session_condition(
    $session_id,
    $and = true,
    $with_base_content = false,
    $session_field = 'session_id'
) {
    $session_id = (int) $session_id;

    if (empty($session_field)) {
        $session_field = 'session_id';
    }
    // Condition to show resources by session
    $condition_add = $and ? ' AND ' : ' WHERE ';

    if ($with_base_content) {
        $condition_session = $condition_add." ( $session_field = $session_id OR $session_field = 0 OR $session_field IS NULL) ";
    } else {
        if (empty($session_id)) {
            $condition_session = $condition_add." ($session_field = $session_id OR $session_field IS NULL)";
        } else {
            $condition_session = $condition_add." $session_field = $session_id ";
        }
    }

    return $condition_session;
}

/**
 * Returns the value of a setting from the web-adjustable admin config settings.
 *
 * WARNING true/false are stored as string, so when comparing you need to check e.g.
 * if (api_get_setting('show_navigation_menu') == 'true') //CORRECT
 * instead of
 * if (api_get_setting('show_navigation_menu') == true) //INCORRECT
 *
 * @param string $variable The variable name
 * @param string $key      The subkey (sub-variable) if any. Defaults to NULL
 *
 * @return string
 *
 * @author René Haentjens
 * @author Bart Mollet
 */
function api_get_setting($variable, $key = null)
{
    global $_setting;
    if ($variable == 'header_extra_content') {
        $filename = api_get_home_path().'header_extra_content.txt';
        if (file_exists($filename)) {
            $value = file_get_contents($filename);

            return $value;
        } else {
            return '';
        }
    }
    if ($variable == 'footer_extra_content') {
        $filename = api_get_home_path().'footer_extra_content.txt';
        if (file_exists($filename)) {
            $value = file_get_contents($filename);

            return $value;
        } else {
            return '';
        }
    }
    $value = null;
    if (is_null($key)) {
        $value = ((isset($_setting[$variable]) && $_setting[$variable] != '') ? $_setting[$variable] : null);
    } else {
        if (isset($_setting[$variable][$key])) {
            $value = $_setting[$variable][$key];
        }
    }

    return $value;
}

/**
 * @param string $plugin
 * @param string $variable
 *
 * @return string
 */
function api_get_plugin_setting($plugin, $variable)
{
    $settings = api_get_configuration_value('plugin_settings');

    if (!empty($settings) && isset($settings[$plugin]) && isset($settings[$plugin][$variable])) {
        return $settings[$plugin][$variable];
    }

    $variableName = $plugin.'_'.$variable;
    $result = api_get_setting($variableName);

    if (isset($result[$plugin])) {
        $value = $result[$plugin];
        $unSerialized = UnserializeApi::unserialize('not_allowed_classes', $value, true);

        if (false !== $unSerialized) {
            $value = $unSerialized;
        }

        return $value;
    }

    return null;
}

/**
 * Returns the value of a setting from the web-adjustable admin config settings.
 */
function api_get_settings_params($params)
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    return Database::select('*', $table, ['where' => $params]);
}

/**
 * @param array $params example: [id = ? => '1']
 *
 * @return array
 */
function api_get_settings_params_simple($params)
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    return Database::select('*', $table, ['where' => $params], 'one');
}

/**
 * Returns the value of a setting from the web-adjustable admin config settings.
 */
function api_delete_settings_params($params)
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $result = Database::delete($table, $params);

    return $result;
}

/**
 * Returns an escaped version of $_SERVER['PHP_SELF'] to avoid XSS injection.
 *
 * @return string Escaped version of $_SERVER['PHP_SELF']
 */
function api_get_self()
{
    return htmlentities($_SERVER['PHP_SELF']);
}

/* USER PERMISSIONS */

/**
 * Checks whether current user is a platform administrator.
 *
 * @param bool $allowSessionAdmins Whether session admins should be considered admins or not
 * @param bool $allowDrh           Whether HR directors should be considered admins or not
 *
 * @return bool true if the user has platform admin rights,
 *              false otherwise
 *
 * @see usermanager::is_admin(user_id) for a user-id specific function
 */
function api_is_platform_admin($allowSessionAdmins = false, $allowDrh = false)
{
    $isAdmin = Session::read('is_platformAdmin');
    if ($isAdmin) {
        return true;
    }
    $user = api_get_user_info();

    return
        isset($user['status']) &&
        (
            ($allowSessionAdmins && $user['status'] == SESSIONADMIN) ||
            ($allowDrh && $user['status'] == DRH)
        );
}

/**
 * Checks whether the user given as user id is in the admin table.
 *
 * @param int $user_id If none provided, will use current user
 * @param int $url     URL ID. If provided, also check if the user is active on given URL
 *
 * @return bool True if the user is admin, false otherwise
 */
function api_is_platform_admin_by_id($user_id = null, $url = null)
{
    $user_id = (int) $user_id;
    if (empty($user_id)) {
        $user_id = api_get_user_id();
    }
    $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
    $sql = "SELECT * FROM $admin_table WHERE user_id = $user_id";
    $res = Database::query($sql);
    $is_admin = Database::num_rows($res) === 1;
    if (!$is_admin || !isset($url)) {
        return $is_admin;
    }
    // We get here only if $url is set
    $url = (int) $url;
    $url_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $sql = "SELECT * FROM $url_user_table
            WHERE access_url_id = $url AND user_id = $user_id";
    $res = Database::query($sql);
    $result = Database::num_rows($res) === 1;

    return $result;
}

/**
 * Returns the user's numeric status ID from the users table.
 *
 * @param int $user_id If none provided, will use current user
 *
 * @return int User's status (1 for teacher, 5 for student, etc)
 */
function api_get_user_status($user_id = null)
{
    $user_id = (int) $user_id;
    if (empty($user_id)) {
        $user_id = api_get_user_id();
    }
    $table = Database::get_main_table(TABLE_MAIN_USER);
    $sql = "SELECT status FROM $table WHERE user_id = $user_id ";
    $result = Database::query($sql);
    $status = null;
    if (Database::num_rows($result)) {
        $row = Database::fetch_array($result);
        $status = $row['status'];
    }

    return $status;
}

/**
 * Checks whether current user is allowed to create courses.
 *
 * @return bool true if the user has course creation rights,
 *              false otherwise
 */
function api_is_allowed_to_create_course()
{
    if (api_is_platform_admin()) {
        return true;
    }

    // Teachers can only create courses
    if (api_is_teacher()) {
        if (api_get_setting('allow_users_to_create_courses') === 'true') {
            return true;
        } else {
            return false;
        }
    }

    return Session::read('is_allowedCreateCourse');
}

/**
 * Checks whether the current user is a course administrator.
 *
 * @return bool True if current user is a course administrator
 */
function api_is_course_admin()
{
    if (api_is_platform_admin()) {
        return true;
    }

    return Session::read('is_courseAdmin');
}

/**
 * Checks whether the current user is a course coach
 * Based on the presence of user in session.id_coach (session general coach).
 *
 * @return bool True if current user is a course coach
 */
function api_is_session_general_coach()
{
    return Session::read('is_session_general_coach');
}

/**
 * Checks whether the current user is a course tutor
 * Based on the presence of user in session_rel_course_rel_user.user_id with status = 2.
 *
 * @return bool True if current user is a course tutor
 */
function api_is_course_tutor()
{
    return Session::read('is_courseTutor');
}

/**
 * @param int $user_id
 * @param int $courseId
 * @param int $session_id
 *
 * @return bool
 */
function api_is_course_session_coach($user_id, $courseId, $session_id)
{
    $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
    $session_rel_course_rel_user_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

    $user_id = (int) $user_id;
    $session_id = (int) $session_id;
    $courseId = (int) $courseId;

    $sql = "SELECT DISTINCT session.id
            FROM $session_table
            INNER JOIN $session_rel_course_rel_user_table session_rc_ru
            ON session.id = session_rc_ru.session_id
            WHERE
                session_rc_ru.user_id = '".$user_id."'  AND
                session_rc_ru.c_id = '$courseId' AND
                session_rc_ru.status = 2 AND
                session_rc_ru.session_id = '$session_id'";
    $result = Database::query($sql);

    return Database::num_rows($result) > 0;
}

/**
 * Checks whether the current user is a course or session coach.
 *
 * @param int $session_id
 * @param int $courseId
 * @param bool  Check whether we are in student view and, if we are, return false
 * @param int $userId
 *
 * @return bool True if current user is a course or session coach
 */
function api_is_coach($session_id = 0, $courseId = null, $check_student_view = true, $userId = 0)
{
    $userId = empty($userId) ? api_get_user_id() : (int) $userId;

    if (!empty($session_id)) {
        $session_id = (int) $session_id;
    } else {
        $session_id = api_get_session_id();
    }

    // The student preview was on
    if ($check_student_view && api_is_student_view_active()) {
        return false;
    }

    if (!empty($courseId)) {
        $courseId = (int) $courseId;
    } else {
        $courseId = api_get_course_int_id();
    }

    $session_table = Database::get_main_table(TABLE_MAIN_SESSION);
    $session_rel_course_rel_user_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $sessionIsCoach = [];

    if (!empty($courseId)) {
        $sql = "SELECT DISTINCT s.id, name, access_start_date, access_end_date
                FROM $session_table s
                INNER JOIN $session_rel_course_rel_user_table session_rc_ru
                ON session_rc_ru.session_id = s.id AND session_rc_ru.user_id = '".$userId."'
                WHERE
                    session_rc_ru.c_id = '$courseId' AND
                    session_rc_ru.status = 2 AND
                    session_rc_ru.session_id = '$session_id'";
        $result = Database::query($sql);
        $sessionIsCoach = Database::store_result($result);
    }

    if (!empty($session_id)) {
        $sql = "SELECT DISTINCT id, name, access_start_date, access_end_date
                FROM $session_table
                WHERE session.id_coach = $userId AND id = $session_id
                ORDER BY access_start_date, access_end_date, name";
        $result = Database::query($sql);
        if (!empty($sessionIsCoach)) {
            $sessionIsCoach = array_merge(
                $sessionIsCoach,
                Database::store_result($result)
            );
        } else {
            $sessionIsCoach = Database::store_result($result);
        }
    }

    return count($sessionIsCoach) > 0;
}

/**
 * Checks whether the current user is a session administrator.
 *
 * @return bool True if current user is a course administrator
 */
function api_is_session_admin()
{
    $user = api_get_user_info();

    return isset($user['status']) && $user['status'] == SESSIONADMIN;
}

/**
 * Checks whether the current user is a human resources manager.
 *
 * @return bool True if current user is a human resources manager
 */
function api_is_drh()
{
    $user = api_get_user_info();

    return isset($user['status']) && $user['status'] == DRH;
}

/**
 * Checks whether the current user is a student.
 *
 * @return bool True if current user is a human resources manager
 */
function api_is_student()
{
    $user = api_get_user_info();

    return isset($user['status']) && $user['status'] == STUDENT;
}

/**
 * Checks whether the current user has the status 'teacher'.
 *
 * @return bool True if current user is a human resources manager
 */
function api_is_teacher()
{
    $user = api_get_user_info();

    return isset($user['status']) && $user['status'] == COURSEMANAGER;
}

/**
 * Checks whether the current user is a invited user.
 *
 * @return bool
 */
function api_is_invitee()
{
    $user = api_get_user_info();

    return isset($user['status']) && $user['status'] == INVITEE;
}

/**
 * This function checks whether a session is assigned into a category.
 *
 * @param int       - session id
 * @param string    - category name
 *
 * @return bool - true if is found, otherwise false
 */
function api_is_session_in_category($session_id, $category_name)
{
    $session_id = (int) $session_id;
    $category_name = Database::escape_string($category_name);
    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

    $sql = "SELECT 1
            FROM $tbl_session
            WHERE $session_id IN (
                SELECT s.id FROM $tbl_session s, $tbl_session_category sc
                WHERE
                  s.session_category_id = sc.id AND
                  sc.name LIKE '%$category_name'
            )";
    $rs = Database::query($sql);

    if (Database::num_rows($rs) > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Displays the title of a tool.
 * Normal use: parameter is a string:
 * api_display_tool_title("My Tool").
 *
 * Optionally, there can be a subtitle below
 * the normal title, and / or a supra title above the normal title.
 *
 * e.g. supra title:
 * group
 * GROUP PROPERTIES
 *
 * e.g. subtitle:
 * AGENDA
 * calender & events tool
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 *
 * @param mixed $title_element - it could either be a string or an array
 *                             containing 'supraTitle', 'mainTitle',
 *                             'subTitle'
 */
function api_display_tool_title($title_element)
{
    if (is_string($title_element)) {
        $tit = $title_element;
        unset($title_element);
        $title_element = [];
        $title_element['mainTitle'] = $tit;
    }
    echo '<h3>';
    if (!empty($title_element['supraTitle'])) {
        echo '<small>'.$title_element['supraTitle'].'</small><br />';
    }
    if (!empty($title_element['mainTitle'])) {
        echo $title_element['mainTitle'];
    }
    if (!empty($title_element['subTitle'])) {
        echo '<br /><small>'.$title_element['subTitle'].'</small>';
    }
    echo '</h3>';
}

/**
 * Displays options for switching between student view and course manager view.
 *
 * Changes in version 1.2 (Patrick Cool)
 * Student view switch now behaves as a real switch. It maintains its current state until the state
 * is changed explicitly
 *
 * Changes in version 1.1 (Patrick Cool)
 * student view now works correctly in subfolders of the document tool
 * student view works correctly in the new links tool
 *
 * Example code for using this in your tools:
 * //if ($is_courseAdmin && api_get_setting('student_view_enabled') == 'true') {
 * //   display_tool_view_option($isStudentView);
 * //}
 * //and in later sections, use api_is_allowed_to_edit()
 *
 * @author Roan Embrechts
 * @author Patrick Cool
 * @author Julio Montoya, changes added in Chamilo
 *
 * @version 1.2
 *
 * @todo rewrite code so it is easier to understand
 */
function api_display_tool_view_option()
{
    if (api_get_setting('student_view_enabled') != 'true') {
        return '';
    }

    $sourceurl = '';
    $is_framed = false;
    // Exceptions apply for all multi-frames pages
    if (strpos($_SERVER['REQUEST_URI'], 'chat/chat_banner.php') !== false) {
        // The chat is a multiframe bit that doesn't work too well with the student_view, so do not show the link
        return '';
    }

    // Uncomment to remove student view link from document view page
    if (strpos($_SERVER['REQUEST_URI'], 'lp/lp_header.php') !== false) {
        if (empty($_GET['lp_id'])) {
            return '';
        }
        $sourceurl = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
        $sourceurl = str_replace(
            'lp/lp_header.php',
            'lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.intval($_GET['lp_id']).'&isStudentView='.($_SESSION['studentview'] == 'studentview' ? 'false' : 'true'),
            $sourceurl
        );
        //showinframes doesn't handle student view anyway...
        //return '';
        $is_framed = true;
    }

    // Check whether the $_SERVER['REQUEST_URI'] contains already url parameters (thus a questionmark)
    if (!$is_framed) {
        if (strpos($_SERVER['REQUEST_URI'], '?') === false) {
            $sourceurl = api_get_self().'?'.api_get_cidreq();
        } else {
            $sourceurl = $_SERVER['REQUEST_URI'];
        }
    }

    $output_string = '';
    if (!empty($_SESSION['studentview'])) {
        if ($_SESSION['studentview'] == 'studentview') {
            // We have to remove the isStudentView=true from the $sourceurl
            $sourceurl = str_replace('&isStudentView=true', '', $sourceurl);
            $sourceurl = str_replace('&isStudentView=false', '', $sourceurl);
            $output_string .= '<a class="btn btn-primary btn-sm" href="'.$sourceurl.'&isStudentView=false" target="_self">'.
                Display::returnFontAwesomeIcon('eye').' '.get_lang('SwitchToTeacherView').'</a>';
        } elseif ($_SESSION['studentview'] == 'teacherview') {
            // Switching to teacherview
            $sourceurl = str_replace('&isStudentView=true', '', $sourceurl);
            $sourceurl = str_replace('&isStudentView=false', '', $sourceurl);
            $output_string .= '<a class="btn btn-default btn-sm" href="'.$sourceurl.'&isStudentView=true" target="_self">'.
                Display::returnFontAwesomeIcon('eye').' '.get_lang('SwitchToStudentView').'</a>';
        }
    } else {
        $output_string .= '<a class="btn btn-default btn-sm" href="'.$sourceurl.'&isStudentView=true" target="_self">'.
            Display::returnFontAwesomeIcon('eye').' '.get_lang('SwitchToStudentView').'</a>';
    }
    $output_string = Security::remove_XSS($output_string);
    $html = Display::tag('div', $output_string, ['class' => 'view-options']);

    return $html;
}

// TODO: This is for the permission section.
/**
 * Function that removes the need to directly use is_courseAdmin global in
 * tool scripts. It returns true or false depending on the user's rights in
 * this particular course.
 * Optionally checking for tutor and coach roles here allows us to use the
 * student_view feature altogether with these roles as well.
 *
 * @param bool  Whether to check if the user has the tutor role
 * @param bool  Whether to check if the user has the coach role
 * @param bool  Whether to check if the user has the session coach role
 * @param bool  check the student view or not
 *
 * @author Roan Embrechts
 * @author Patrick Cool
 * @author Julio Montoya
 *
 * @version 1.1, February 2004
 *
 * @return bool true: the user has the rights to edit, false: he does not
 */
function api_is_allowed_to_edit(
    $tutor = false,
    $coach = false,
    $session_coach = false,
    $check_student_view = true
) {
    $allowSessionAdminEdit = api_get_configuration_value('session_admins_edit_courses_content') === true;

    // Admins can edit anything.
    if (api_is_platform_admin($allowSessionAdminEdit)) {
        //The student preview was on
        if ($check_student_view && api_is_student_view_active()) {
            return false;
        }

        return true;
    }

    $sessionId = api_get_session_id();

    if ($sessionId && api_get_configuration_value('session_courses_read_only_mode')) {
        $efv = new ExtraFieldValue('course');
        $lockExrafieldField = $efv->get_values_by_handler_and_field_variable(
            api_get_course_int_id(),
            'session_courses_read_only_mode'
        );

        if (!empty($lockExrafieldField['value'])) {
            return false;
        }
    }

    $is_allowed_coach_to_edit = api_is_coach(null, null, $check_student_view);
    $session_visibility = api_get_session_visibility($sessionId);
    $is_courseAdmin = api_is_course_admin();

    if (!$is_courseAdmin && $tutor) {
        // If we also want to check if the user is a tutor...
        $is_courseAdmin = $is_courseAdmin || api_is_course_tutor();
    }

    if (!$is_courseAdmin && $coach) {
        // If we also want to check if the user is a coach...';
        // Check if session visibility is read only for coaches.
        if ($session_visibility == SESSION_VISIBLE_READ_ONLY) {
            $is_allowed_coach_to_edit = false;
        }

        if (api_get_setting('allow_coach_to_edit_course_session') == 'true') {
            // Check if coach is allowed to edit a course.
            $is_courseAdmin = $is_courseAdmin || $is_allowed_coach_to_edit;
        }
    }

    if (!$is_courseAdmin && $session_coach) {
        $is_courseAdmin = $is_courseAdmin || $is_allowed_coach_to_edit;
    }

    // Check if the student_view is enabled, and if so, if it is activated.
    if (api_get_setting('student_view_enabled') == 'true') {
        if (!empty($sessionId)) {
            // Check if session visibility is read only for coaches.
            if ($session_visibility == SESSION_VISIBLE_READ_ONLY) {
                $is_allowed_coach_to_edit = false;
            }

            if (api_get_setting('allow_coach_to_edit_course_session') == 'true') {
                // Check if coach is allowed to edit a course.
                $is_allowed = $is_allowed_coach_to_edit;
            } else {
                $is_allowed = false;
            }
            if ($check_student_view) {
                $is_allowed = $is_allowed && $_SESSION['studentview'] != 'studentview';
            }
        } else {
            if ($check_student_view) {
                $is_allowed = $is_courseAdmin && $_SESSION['studentview'] != 'studentview';
            } else {
                $is_allowed = $is_courseAdmin;
            }
        }

        return $is_allowed;
    } else {
        return $is_courseAdmin;
    }
}

/**
 * Returns true if user is a course coach of at least one course in session.
 *
 * @param int $sessionId
 *
 * @return bool
 */
function api_is_coach_of_course_in_session($sessionId)
{
    if (api_is_platform_admin()) {
        return true;
    }

    $userId = api_get_user_id();
    $courseList = UserManager::get_courses_list_by_session(
        $userId,
        $sessionId
    );

    // Session visibility.
    $visibility = api_get_session_visibility(
        $sessionId,
        null,
        false
    );

    if ($visibility != SESSION_VISIBLE && !empty($courseList)) {
        // Course Coach session visibility.
        $blockedCourseCount = 0;
        $closedVisibilityList = [
            COURSE_VISIBILITY_CLOSED,
            COURSE_VISIBILITY_HIDDEN,
        ];

        foreach ($courseList as $course) {
            // Checking session visibility
            $sessionCourseVisibility = api_get_session_visibility(
                $sessionId,
                $course['real_id']
            );

            $courseIsVisible = !in_array(
                $course['visibility'],
                $closedVisibilityList
            );
            if ($courseIsVisible === false || $sessionCourseVisibility == SESSION_INVISIBLE) {
                $blockedCourseCount++;
            }
        }

        // If all courses are blocked then no show in the list.
        if ($blockedCourseCount === count($courseList)) {
            $visibility = SESSION_INVISIBLE;
        } else {
            $visibility = SESSION_VISIBLE;
        }
    }

    switch ($visibility) {
        case SESSION_VISIBLE_READ_ONLY:
        case SESSION_VISIBLE:
        case SESSION_AVAILABLE:
            return true;
            break;
        case SESSION_INVISIBLE:
            return false;
    }

    return false;
}

/**
 * Checks if a student can edit contents in a session depending
 * on the session visibility.
 *
 * @param bool $tutor Whether to check if the user has the tutor role
 * @param bool $coach Whether to check if the user has the coach role
 *
 * @return bool true: the user has the rights to edit, false: he does not
 */
function api_is_allowed_to_session_edit($tutor = false, $coach = false)
{
    if (api_is_allowed_to_edit($tutor, $coach)) {
        // If I'm a teacher, I will return true in order to not affect the normal behaviour of Chamilo tools.
        return true;
    } else {
        $sessionId = api_get_session_id();

        if (0 == $sessionId) {
            // I'm not in a session so i will return true to not affect the normal behaviour of Chamilo tools.
            return true;
        } else {
            // I'm in a session and I'm a student
            // Get the session visibility
            $session_visibility = api_get_session_visibility($sessionId);
            // if 5 the session is still available
            switch ($session_visibility) {
                case SESSION_VISIBLE_READ_ONLY: // 1
                    return false;
                case SESSION_VISIBLE:           // 2
                    return true;
                case SESSION_INVISIBLE:         // 3
                    return false;
                case SESSION_AVAILABLE:         //5
                    return true;
            }
        }
    }

    return false;
}

/**
 * Checks whether the user is allowed in a specific tool for a specific action.
 *
 * @param string $tool   the tool we are checking if the user has a certain permission
 * @param string $action the action we are checking (add, edit, delete, move, visibility)
 *
 * @return bool
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @author Julio Montoya
 *
 * @version 1.0
 */
function api_is_allowed($tool, $action, $task_id = 0)
{
    $_user = api_get_user_info();
    $_course = api_get_course_info();

    if (api_is_course_admin()) {
        return true;
    }

    if (is_array($_course) and count($_course) > 0) {
        require_once __DIR__.'/../../permissions/permissions_functions.inc.php';

        // Getting the permissions of this user.
        if ($task_id == 0) {
            $user_permissions = get_permissions('user', $_user['user_id']);
            $_SESSION['total_permissions'][$_course['code']] = $user_permissions;
        }

        // Getting the permissions of the task.
        if ($task_id != 0) {
            $task_permissions = get_permissions('task', $task_id);
            $_SESSION['total_permissions'][$_course['code']] = $task_permissions;
        }
        //print_r($_SESSION['total_permissions']);

        // Getting the permissions of the groups of the user
        //$groups_of_user = GroupManager::get_group_ids($_course['db_name'], $_user['user_id']);

        //foreach($groups_of_user as $group)
        //   $this_group_permissions = get_permissions('group', $group);

        // Getting the permissions of the courseroles of the user
        $user_courserole_permissions = get_roles_permissions('user', $_user['user_id']);

        // Getting the permissions of the platformroles of the user
        //$user_platformrole_permissions = get_roles_permissions('user', $_user['user_id'], ', platform');

        // Getting the permissions of the roles of the groups of the user
        //foreach($groups_of_user as $group)
        //    $this_group_courserole_permissions = get_roles_permissions('group', $group);

        // Getting the permissions of the platformroles of the groups of the user
        //foreach($groups_of_user as $group)
        //    $this_group_platformrole_permissions = get_roles_permissions('group', $group, 'platform');
    }

    // If the permissions are limited, we have to map the extended ones to the limited ones.
    if (api_get_setting('permissions') == 'limited') {
        if ($action == 'Visibility') {
            $action = 'Edit';
        }
        if ($action == 'Move') {
            $action = 'Edit';
        }
    }

    // The session that contains all the permissions already exists for this course
    // so there is no need to requery everything.
    //my_print_r($_SESSION['total_permissions'][$_course['code']][$tool]);
    if (is_array($_SESSION['total_permissions'][$_course['code']][$tool])) {
        if (in_array($action, $_SESSION['total_permissions'][$_course['code']][$tool])) {
            return true;
        } else {
            return false;
        }
    }

    return false;
}

/**
 * Tells whether this user is an anonymous user.
 *
 * @param int  $user_id  User ID (optional, will take session ID if not provided)
 * @param bool $db_check Whether to check in the database (true) or simply in
 *                       the session (false) to see if the current user is the anonymous user
 *
 * @return bool true if this user is anonymous, false otherwise
 */
function api_is_anonymous($user_id = null, $db_check = false)
{
    if (!isset($user_id)) {
        $user_id = api_get_user_id();
    }

    if ($db_check) {
        $info = api_get_user_info($user_id);
        if (false === $info || $info['status'] == ANONYMOUS) {
            return true;
        }
    }

    $_user = api_get_user_info();

    if (isset($_user['status']) && $_user['status'] == ANONYMOUS) {
        //if ($_user['user_id'] == 0) {
        // In some cases, api_set_anonymous doesn't seem to be triggered in local.inc.php. Make sure it is.
        // Occurs in agenda for admin links - YW
        // it occurs when pages are opened directly without entering first the course home page. To fix it add
        // $use_anonymous = true;
        // before including global.inc.php in the page
        global $use_anonymous;
        if (isset($use_anonymous) && $use_anonymous) {
            api_set_anonymous();
        }

        return true;
    }

    return (isset($_user['is_anonymous']) && $_user['is_anonymous'] === true) || $_user === false;
}

/**
 * Displays message "You are not allowed here..." and exits the entire script.
 *
 * @param bool   $print_headers Whether or not to print headers (default = false -> does not print them)
 * @param string $message
 * @param int    $responseCode
 */
function api_not_allowed(
    $print_headers = false,
    $message = null,
    $responseCode = 0
) {
    if (api_get_setting('sso_authentication') === 'true') {
        global $osso;
        if ($osso) {
            $osso->logout();
        }
    }
    $home_url = api_get_path(WEB_PATH);
    $user_id = api_get_user_id();
    $course = api_get_course_id();

    global $this_section;

    if (CustomPages::enabled() && !isset($user_id)) {
        if (empty($user_id)) {
            // Why the CustomPages::enabled() need to be to set the request_uri
            $_SESSION['request_uri'] = $_SERVER['REQUEST_URI'];
        }
        CustomPages::display(CustomPages::INDEX_UNLOGGED);
    }

    $origin = api_get_origin();

    $msg = null;
    if (isset($message)) {
        $msg = $message;
    } else {
        $msg = Display::return_message(
            get_lang('NotAllowedClickBack').'
            <script>function goBack(){window.history.back();}</script>',
            'error',
            false
        );
        $msg .= '<p class="text-center">
             <a onclick="goBack();" class="btn btn-default" href="'.$home_url.'">'.get_lang('GoBack').'</a>
             </p>';
    }

    $msg = Display::div($msg, ['align' => 'center']);

    $show_headers = 0;
    if ($print_headers && $origin != 'learnpath') {
        $show_headers = 1;
    }

    $hideBreadCrumb = false;
    if (api_get_configuration_value('hide_breadcrumb_if_not_allowed')) {
        $hideBreadCrumb = true;
    }

    $tpl = new Template(null, $show_headers, $show_headers, $hideBreadCrumb, true, false, true, $responseCode);
    $tpl->assign('hide_login_link', 1);
    $tpl->assign('content', $msg);

    if (($user_id != 0 && !api_is_anonymous()) &&
        (!isset($course) || $course == -1) &&
        empty($_GET['cidReq'])
    ) {
        // if the access is not authorized and there is some login information
        // but the cidReq is not found, assume we are missing course data and send the user
        // to the user_portal
        $tpl->display_one_col_template();
        exit;
    }

    $tplPlugin = new AppPlugin();
    $loginTopRegionContent = $tplPlugin->load_region('login_top', $tpl, true);
    $loginBottomRegionContent = $tplPlugin->load_region('login_bottom', $tpl, true);

    if (!empty($_SERVER['REQUEST_URI']) &&
        (
            !empty($_GET['cidReq']) ||
            $this_section == SECTION_MYPROFILE ||
            $this_section == SECTION_PLATFORM_ADMIN
        )
    ) {
        $courseCode = api_get_course_id();
        // Only display form and return to the previous URL if there was a course ID included
        if ($user_id != 0 && !api_is_anonymous()) {
            //if there is a user ID, then the user is not allowed but the session is still there. Say so and exit
            $tpl->assign('content', $msg);
            $tpl->display_one_col_template();
            exit;
        }

        if (!is_null($courseCode)) {
            api_set_firstpage_parameter($courseCode);
        }

        // If the user has no user ID, then his session has expired
        $form = api_get_not_allowed_login_form();

        // see same text in auth/gotocourse.php and main_api.lib.php function api_not_allowed (above)
        $content = Display::return_message(get_lang('NotAllowed'), 'error', false);

        if (!empty($courseCode)) {
            $content .= '<h4>'.get_lang('LoginToGoToThisCourse').'</h4>';
        }

        if (api_is_cas_activated()) {
            $content .= Display::return_message(sprintf(get_lang('YouHaveAnInstitutionalAccount'), api_get_setting("Institution")), '', false);
            $content .= Display::div(
                Template::displayCASLoginButton(),
                ['align' => 'center']
            );
            $content .= Display::return_message(get_lang('YouDontHaveAnInstitutionAccount'));
            $content .= "<p style='text-align:center'><a href='#' onclick='$(this).parent().next().toggle()'>".get_lang('LoginWithExternalAccount')."</a></p>";
            $content .= "<div style='display:none;'>";
        }
        $content .= PHP_EOL.$loginTopRegionContent;
        $content .= '<div class="well">';
        $content .= $form->returnForm();
        $content .= '</div>';
        $content .= PHP_EOL.$loginBottomRegionContent;
        if (api_is_cas_activated()) {
            $content .= "</div>";
        }

        if (!empty($courseCode)) {
            $content .= '<hr/><p style="text-align:center"><a href="'.$home_url.'">'.
                get_lang('ReturnToCourseHomepage').'</a></p>';
        } else {
            $content .= '<hr/><p style="text-align:center"><a href="'.$home_url.'">'.
                get_lang('BackHome').'</a></p>';
        }

        $tpl->setLoginBodyClass();
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();
        exit;
    }

    if ($user_id != 0 && !api_is_anonymous()) {
        $tpl->display_one_col_template();
        exit;
    }

    $msg = null;
    // The session is over and we were not in a course,
    // or we try to get directly to a private course without being logged
    $courseId = api_get_course_int_id();
    if (!empty($courseId)) {
        api_set_firstpage_parameter(api_get_course_id());
        $tpl->setLoginBodyClass();

        // see same text in auth/gotocourse.php and main_api.lib.php function api_not_allowed (bellow)
        $msg = Display::return_message(get_lang('NotAllowed'), 'error', false);
        $msg .= '<h4>'.get_lang('LoginToGoToThisCourse').'</h4>';
        $casEnabled = api_is_cas_activated();
        if ($casEnabled) {
            $msg .= Display::return_message(
                sprintf(get_lang('YouHaveAnInstitutionalAccount'), api_get_setting("Institution")),
                '',
                false
            );
            $msg .= Display::div(
                Template::displayCASLoginButton(),
                ['align' => 'center']
            );
            $msg .= Display::return_message(get_lang('YouDontHaveAnInstitutionAccount'));
            $msg .= "<p style='text-align:center'><a href='#' onclick='$(this).parent().next().toggle()'>".get_lang('LoginWithExternalAccount')."</a></p>";
            $msg .= "<div style='display:none;'>";
        }
        $form = api_get_not_allowed_login_form();
        $msg .= PHP_EOL.$loginTopRegionContent;
        $msg .= '<div class="well">';
        $msg .= $form->returnForm();
        $msg .= '</div>';
        $msg .= PHP_EOL.$loginBottomRegionContent;
        if ($casEnabled) {
            $msg .= "</div>";
        }
    } else {
        // we were not in a course, return to home page
        $msg = Display::return_message(
            get_lang('NotAllowed'),
            'error',
            false
        );

        $msg .= '<p class="text-center">
                 <a class="btn btn-default" href="'.$home_url.'">'.get_lang('BackHome').'</a>
                 </p>';

        if (!empty($message)) {
            $msg = $message;
        }

        if (api_is_anonymous()) {
            $form = api_get_not_allowed_login_form();
            $msg .= PHP_EOL.$loginTopRegionContent;
            $msg .= '<div class="well">';
            $msg .= $form->returnForm();
            $msg .= '</div>';
            $msg .= PHP_EOL.$loginBottomRegionContent;
        }
    }

    $tpl->assign('content', $msg);
    $tpl->display_one_col_template();
    exit;
}

/**
 * @return FormValidator
 */
function api_get_not_allowed_login_form()
{
    $action = api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']);
    $action = str_replace('&amp;', '&', $action);
    Session::write('redirect_after_not_allow_page', $action);
    $action .= '&redirect_after_not_allow_page=1';

    $form = new FormValidator(
        'formLogin',
        'post',
        $action,
        null,
        ['class' => 'form-stacked']
    );
    $params = [
        'placeholder' => get_lang('UserName'),
        'class' => 'col-md-3',
    ];
    if (api_browser_support('autocapitalize')) {
        $params['autocapitalize'] = 'none';
    }

    $form->addElement(
        'text',
        'login',
        null,
        $params
    );
    $form->addElement(
        'password',
        'password',
        null,
        ['placeholder' => get_lang('Password'), 'class' => 'col-md-3']
    ); //new
    $form->addButtonNext(get_lang('LoginEnter'), 'submitAuth');

    return $form;
}

/**
 * Gets a UNIX timestamp from a database (MySQL) datetime format string.
 *
 * @param string $last_post_datetime standard output date in a sql query
 *
 * @return int timestamp
 *
 * @author Toon Van Hoecke <Toon.VanHoecke@UGent.be>
 *
 * @version October 2003
 * @desc convert sql date to unix timestamp
 */
function convert_sql_date($last_post_datetime)
{
    list($last_post_date, $last_post_time) = explode(' ', $last_post_datetime);
    list($year, $month, $day) = explode('-', $last_post_date);
    list($hour, $min, $sec) = explode(':', $last_post_time);

    return mktime((int) $hour, (int) $min, (int) $sec, (int) $month, (int) $day, (int) $year);
}

/**
 * Gets item visibility from the item_property table.
 *
 * Getting the visibility is done by getting the last updated visibility entry,
 * using the largest session ID found if session 0 and another was found (meaning
 * the only one that is actually from the session, in case there are results from
 * session 0 *AND* session n).
 *
 * @param array  $_course  Course properties array (result of api_get_course_info())
 * @param string $tool     Tool (learnpath, document, etc)
 * @param int    $id       The item ID in the given tool
 * @param int    $session  The session ID (optional)
 * @param int    $user_id
 * @param string $type
 * @param string $group_id
 *
 * @return int -1 on error, 0 if invisible, 1 if visible
 */
function api_get_item_visibility(
    $_course,
    $tool,
    $id,
    $session = 0,
    $user_id = null,
    $type = null,
    $group_id = null
) {
    if (!is_array($_course) || count($_course) == 0 || empty($tool) || empty($id)) {
        return -1;
    }

    $tool = Database::escape_string($tool);
    $id = (int) $id;
    $session = (int) $session;
    $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $course_id = (int) $_course['real_id'];

    $userCondition = '';
    if (!empty($user_id)) {
        $user_id = (int) $user_id;
        $userCondition = " AND to_user_id = $user_id ";
    }

    $typeCondition = '';
    if (!empty($type)) {
        $type = Database::escape_string($type);
        $typeCondition = " AND lastedit_type = '$type' ";
    }

    $groupCondition = '';
    if (!empty($group_id)) {
        $group_id = (int) $group_id;
        $groupCondition = " AND to_group_id = '$group_id' ";
    }

    $sql = "SELECT visibility
            FROM $TABLE_ITEMPROPERTY
            WHERE
                c_id = $course_id AND
                tool = '$tool' AND
                ref = $id AND
                (session_id = $session OR session_id = 0 OR session_id IS NULL)
                $userCondition $typeCondition $groupCondition
            ORDER BY session_id DESC, lastedit_date DESC
            LIMIT 1";

    $res = Database::query($sql);
    if ($res === false || Database::num_rows($res) == 0) {
        return -1;
    }
    $row = Database::fetch_array($res);

    return (int) $row['visibility'];
}

/**
 * Delete a row in the c_item_property table.
 *
 * @param array  $courseInfo
 * @param string $tool
 * @param int    $itemId
 * @param int    $userId
 * @param int    $groupId    group.iid
 * @param int    $sessionId
 *
 * @return false|null
 */
function api_item_property_delete(
    $courseInfo,
    $tool,
    $itemId,
    $userId,
    $groupId = 0,
    $sessionId = 0
) {
    if (empty($courseInfo)) {
        return false;
    }

    $courseId = (int) $courseInfo['real_id'];

    if (empty($courseId) || empty($tool) || empty($itemId)) {
        return false;
    }

    $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $tool = Database::escape_string($tool);
    $itemId = intval($itemId);
    $userId = intval($userId);
    $groupId = intval($groupId);
    $sessionId = intval($sessionId);

    $groupCondition = " AND to_group_id = $groupId ";
    if (empty($groupId)) {
        $groupCondition = " AND (to_group_id is NULL OR to_group_id = 0) ";
    }

    $userCondition = " AND to_user_id = $userId ";
    if (empty($userId)) {
        $userCondition = " AND (to_user_id is NULL OR to_user_id = 0) ";
    }
    $sessionCondition = api_get_session_condition($sessionId, true, false, 'session_id');
    $sql = "DELETE FROM $table
            WHERE
                c_id = $courseId AND
                tool  = '$tool' AND
                ref = $itemId
                $sessionCondition
                $userCondition
                $groupCondition
            ";

    Database::query($sql);
}

/**
 * Updates or adds item properties to the Item_propetry table
 * Tool and lastedit_type are language independant strings (langvars->get_lang!).
 *
 * @param array  $_course        array with course properties
 * @param string $tool           tool id, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param int    $item_id        id of the item itself, linked to key of every tool ('id', ...)
 * @param string $last_edit_type add or update action
 *                               (1) message to be translated (in trad4all) : e.g. DocumentAdded, DocumentUpdated;
 *                               (2) "delete"
 *                               (3) "visible"
 *                               (4) "invisible"
 * @param int    $user_id        id of the editing/adding user
 * @param array  $groupInfo      must include group.iid/group.od
 * @param int    $to_user_id     id of the intended user (always has priority over $to_group_id !), only relevant for $type (1)
 * @param string $start_visible  0000-00-00 00:00:00 format
 * @param string $end_visible    0000-00-00 00:00:00 format
 * @param int    $session_id     The session ID, if any, otherwise will default to 0
 *
 * @return bool false if update fails
 *
 * @author Toon Van Hoecke <Toon.VanHoecke@UGent.be>, Ghent University
 *
 * @version January 2005
 * @desc update the item_properties table (if entry not exists, insert) of the course
 */
function api_item_property_update(
    $_course,
    $tool,
    $item_id,
    $last_edit_type,
    $user_id,
    $groupInfo = [],
    $to_user_id = null,
    $start_visible = '',
    $end_visible = '',
    $session_id = 0
) {
    if (empty($_course)) {
        return false;
    }

    $course_id = $_course['real_id'];

    if (empty($course_id)) {
        return false;
    }

    $to_group_id = 0;
    if (!empty($groupInfo) && isset($groupInfo['iid'])) {
        $to_group_id = (int) $groupInfo['iid'];
    }

    $em = Database::getManager();

    // Definition of variables.
    $tool = Database::escape_string($tool);
    $item_id = (int) $item_id;
    $lastEditTypeNoFilter = $last_edit_type;
    $last_edit_type = Database::escape_string($last_edit_type);
    $user_id = (int) $user_id;

    $startVisible = "NULL";
    if (!empty($start_visible)) {
        $start_visible = Database::escape_string($start_visible);
        $startVisible = "'$start_visible'";
    }

    $endVisible = "NULL";
    if (!empty($end_visible)) {
        $end_visible = Database::escape_string($end_visible);
        $endVisible = "'$end_visible'";
    }

    $to_filter = '';
    $time = api_get_utc_datetime();

    if (!empty($session_id)) {
        $session_id = (int) $session_id;
    } else {
        $session_id = api_get_session_id();
    }

    // Definition of tables.
    $tableItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);

    if ($to_user_id <= 0) {
        $to_user_id = null; // No to_user_id set
    }

    if (!is_null($to_user_id)) {
        // $to_user_id has more priority than $to_group_id
        $to_user_id = (int) $to_user_id;
        $to_field = 'to_user_id';
        $to_value = $to_user_id;
    } else {
        // $to_user_id is not set.
        $to_field = 'to_group_id';
        $to_value = $to_group_id;
    }

    $toValueCondition = empty($to_value) ? 'NULL' : "'$to_value'";
    // Set filters for $to_user_id and $to_group_id, with priority for $to_user_id
    $condition_session = " AND session_id = $session_id ";
    if (empty($session_id)) {
        $condition_session = ' AND (session_id = 0 OR session_id IS NULL) ';
    }

    $filter = " c_id = $course_id AND tool = '$tool' AND ref = $item_id $condition_session ";

    // Check whether $to_user_id and $to_group_id are passed in the function call.
    // If both are not passed (both are null) then it is a message for everybody and $to_group_id should be 0 !
    if (is_null($to_user_id) && is_null($to_group_id)) {
        $to_group_id = 0;
    }

    if (!is_null($to_user_id)) {
        // Set filter to intended user.
        $to_filter = " AND to_user_id = $to_user_id $condition_session";
    } else {
        // Set filter to intended group.
        if (($to_group_id != 0) && $to_group_id == strval(intval($to_group_id))) {
            $to_filter = " AND to_group_id = $to_group_id $condition_session";
        }
    }

    // Adding filter if set.
    $filter .= $to_filter;

    // Update if possible
    $set_type = '';

    switch ($lastEditTypeNoFilter) {
        case 'delete':
            // delete = make item only visible for the platform admin.
            $visibility = '2';
            if (!empty($session_id)) {
                // Check whether session id already exist into item_properties for updating visibility or add it.
                $sql = "SELECT session_id FROM $tableItemProperty
                        WHERE
                            c_id = $course_id AND
                            tool = '$tool' AND
                            ref = $item_id AND
                            session_id = $session_id";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $sql = "UPDATE $tableItemProperty
                            SET lastedit_type       = '".str_replace('_', '', ucwords($tool))."Deleted',
                                lastedit_date       = '$time',
                                lastedit_user_id    = $user_id,
                                visibility          = $visibility,
                                session_id          = $session_id $set_type
                            WHERE $filter";
                    $result = Database::query($sql);
                } else {
                    $sql = "INSERT INTO $tableItemProperty (c_id, tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id, $to_field, visibility, start_visible, end_visible, session_id)
                            VALUES ($course_id, '$tool',$item_id, '$time', $user_id, '$time', '$last_edit_type',$user_id, $toValueCondition, $visibility, $startVisible, $endVisible, $session_id)";
                    $result = Database::query($sql);
                    $id = Database::insert_id();
                    if ($id) {
                        $sql = "UPDATE $tableItemProperty SET id = iid WHERE iid = $id";
                        Database::query($sql);
                    }
                }
            } else {
                $sql = "UPDATE $tableItemProperty
                        SET
                            lastedit_type='".str_replace('_', '', ucwords($tool))."Deleted',
                            lastedit_date='$time',
                            lastedit_user_id = $user_id,
                            visibility = $visibility $set_type
                        WHERE $filter";
                $result = Database::query($sql);
            }
            break;
        case 'visible': // Change item to visible.
            $visibility = '1';
            if (!empty($session_id)) {
                // Check whether session id already exist into item_properties for updating visibility or add it.
                $sql = "SELECT session_id FROM $tableItemProperty
                        WHERE
                            c_id = $course_id AND
                            tool = '$tool' AND
                            ref = $item_id AND
                            session_id = $session_id";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $sql = "UPDATE $tableItemProperty
                            SET
                                lastedit_type='".str_replace('_', '', ucwords($tool))."Visible',
                                lastedit_date='$time',
                                lastedit_user_id = $user_id,
                                visibility = $visibility,
                                session_id = $session_id $set_type
                            WHERE $filter";
                    $result = Database::query($sql);
                } else {
                    $sql = "INSERT INTO $tableItemProperty (c_id, tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id, $to_field, visibility, start_visible, end_visible, session_id)
                            VALUES ($course_id, '$tool', $item_id, '$time', $user_id, '$time', '$last_edit_type', $user_id, $toValueCondition, $visibility, $startVisible, $endVisible, $session_id)";
                    $result = Database::query($sql);
                    $id = Database::insert_id();
                    if ($id) {
                        $sql = "UPDATE $tableItemProperty SET id = iid WHERE iid = $id";
                        Database::query($sql);
                    }
                }
            } else {
                $sql = "UPDATE $tableItemProperty
                        SET
                            lastedit_type='".str_replace('_', '', ucwords($tool))."Visible',
                            lastedit_date='$time',
                            lastedit_user_id = $user_id,
                            visibility = $visibility $set_type
                        WHERE $filter";
                $result = Database::query($sql);
            }
            break;
        case 'invisible': // Change item to invisible.
            $visibility = '0';
            if (!empty($session_id)) {
                // Check whether session id already exist into item_properties for updating visibility or add it
                $sql = "SELECT session_id FROM $tableItemProperty
                        WHERE
                            c_id = $course_id AND
                            tool = '$tool' AND
                            ref = $item_id AND
                            session_id = $session_id";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $sql = "UPDATE $tableItemProperty
                            SET
                                lastedit_type = '".str_replace('_', '', ucwords($tool))."Invisible',
                                lastedit_date = '$time',
                                lastedit_user_id = $user_id,
                                visibility = $visibility,
                                session_id = $session_id $set_type
                            WHERE $filter";
                    $result = Database::query($sql);
                } else {
                    $sql = "INSERT INTO $tableItemProperty (c_id, tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id,$to_field, visibility, start_visible, end_visible, session_id)
                            VALUES ($course_id, '$tool', $item_id, '$time', $user_id, '$time', '$last_edit_type', $user_id, $toValueCondition, $visibility, $startVisible, $endVisible, $session_id)";
                    $result = Database::query($sql);
                    $id = Database::insert_id();
                    if ($id) {
                        $sql = "UPDATE $tableItemProperty SET id = iid WHERE iid = $id";
                        Database::query($sql);
                    }
                }
            } else {
                $sql = "UPDATE $tableItemProperty
                        SET
                            lastedit_type = '".str_replace('_', '', ucwords($tool))."Invisible',
                            lastedit_date = '$time',
                            lastedit_user_id = $user_id,
                            visibility = $visibility $set_type
                        WHERE $filter";
                $result = Database::query($sql);
            }
            break;
        default: // The item will be added or updated.
            $set_type = ", lastedit_type = '$last_edit_type' ";
            $visibility = '1';
            //$filter .= $to_filter; already added
            $sql = "UPDATE $tableItemProperty
                    SET
                      lastedit_date = '$time',
                      lastedit_user_id = $user_id $set_type
                    WHERE $filter";
            $result = Database::query($sql);
    }

    // Insert if no entries are found (can only happen in case of $last_edit_type switch is 'default').
    if ($result == false || Database::affected_rows($result) == 0) {
        $objCourse = $em->find('ChamiloCoreBundle:Course', intval($course_id));
        $objTime = new DateTime('now', new DateTimeZone('UTC'));
        $objUser = api_get_user_entity($user_id);
        if (empty($objUser)) {
            // Use anonymous
            $user_id = api_get_anonymous_id();
            $objUser = api_get_user_entity($user_id);
        }

        $objGroup = null;
        if (!empty($to_group_id)) {
            $objGroup = $em->find('ChamiloCourseBundle:CGroupInfo', $to_group_id);
        }

        $objToUser = api_get_user_entity($to_user_id);
        $objSession = $em->find('ChamiloCoreBundle:Session', intval($session_id));

        $startVisibleDate = !empty($start_visible) ? new DateTime($start_visible, new DateTimeZone('UTC')) : null;
        $endVisibleDate = !empty($endVisibleDate) ? new DateTime($endVisibleDate, new DateTimeZone('UTC')) : null;

        $cItemProperty = new CItemProperty($objCourse);
        $cItemProperty
            ->setTool($tool)
            ->setRef($item_id)
            ->setInsertDate($objTime)
            ->setInsertUser($objUser)
            ->setLasteditDate($objTime)
            ->setLasteditType($last_edit_type)
            ->setGroup($objGroup)
            ->setToUser($objToUser)
            ->setVisibility($visibility)
            ->setStartVisible($startVisibleDate)
            ->setEndVisible($endVisibleDate)
            ->setSession($objSession);

        $em->persist($cItemProperty);
        $em->flush();

        $id = $cItemProperty->getIid();

        if ($id) {
            $cItemProperty->setId($id);
            $em->merge($cItemProperty);
            $em->flush();

            return false;
        }
    }

    return true;
}

/**
 * Gets item property by tool.
 *
 * @param string $tool        tool name, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param string $course_code
 * @param int    $session_id
 *
 * @return array All fields from c_item_property (all rows found) or empty array
 */
function api_get_item_property_by_tool($tool, $course_code, $session_id = null)
{
    $course_info = api_get_course_info($course_code);
    $tool = Database::escape_string($tool);

    // Definition of tables.
    $item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $session_id = (int) $session_id;
    $session_condition = ' AND session_id = '.$session_id;
    if (empty($session_id)) {
        $session_condition = " AND (session_id = 0 OR session_id IS NULL) ";
    }
    $course_id = $course_info['real_id'];

    $sql = "SELECT * FROM $item_property_table
            WHERE
                c_id = $course_id AND
                tool = '$tool'
                $session_condition ";
    $rs = Database::query($sql);
    $list = [];
    if (Database::num_rows($rs) > 0) {
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $list[] = $row;
        }
    }

    return $list;
}

/**
 * Gets item property by tool and user.
 *
 * @param int $userId
 * @param int $tool
 * @param int $courseId
 * @param int $session_id
 *
 * @return array
 */
function api_get_item_property_list_by_tool_by_user(
    $userId,
    $tool,
    $courseId,
    $session_id = 0
) {
    $userId = intval($userId);
    $tool = Database::escape_string($tool);
    $session_id = intval($session_id);
    $courseId = intval($courseId);

    // Definition of tables.
    $item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $session_condition = ' AND session_id = '.$session_id;
    if (empty($session_id)) {
        $session_condition = " AND (session_id = 0 OR session_id IS NULL) ";
    }
    $sql = "SELECT * FROM $item_property_table
            WHERE
                insert_user_id = $userId AND
                c_id = $courseId AND
                tool = '$tool'
                $session_condition ";

    $rs = Database::query($sql);
    $list = [];
    if (Database::num_rows($rs) > 0) {
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $list[] = $row;
        }
    }

    return $list;
}

/**
 * Gets item property id from tool of a course.
 *
 * @param string $course_code course code
 * @param string $tool        tool name, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param int    $ref         id of the item itself, linked to key of every tool ('id', ...), "*" = all items of the tool
 * @param int    $sessionId   Session ID (optional)
 *
 * @return int
 */
function api_get_item_property_id($course_code, $tool, $ref, $sessionId = 0)
{
    $course_info = api_get_course_info($course_code);
    $tool = Database::escape_string($tool);
    $ref = (int) $ref;

    // Definition of tables.
    $tableItemProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $course_id = $course_info['real_id'];
    $sessionId = (int) $sessionId;
    $sessionCondition = " AND session_id = $sessionId ";
    if (empty($sessionId)) {
        $sessionCondition = ' AND (session_id = 0 OR session_id IS NULL) ';
    }
    $sql = "SELECT id FROM $tableItemProperty
            WHERE
                c_id = $course_id AND
                tool = '$tool' AND
                ref = $ref
                $sessionCondition";
    $rs = Database::query($sql);
    $item_property_id = '';
    if (Database::num_rows($rs) > 0) {
        $row = Database::fetch_array($rs);
        $item_property_id = $row['id'];
    }

    return $item_property_id;
}

/**
 * Inserts a record in the track_e_item_property table (No update).
 *
 * @param string $tool
 * @param int    $ref
 * @param string $title
 * @param string $content
 * @param int    $progress
 *
 * @return bool|int
 */
function api_track_item_property_update($tool, $ref, $title, $content, $progress)
{
    $tbl_stats_item_property = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
    $course_id = api_get_course_int_id(); //numeric
    $course_code = api_get_course_id(); //alphanumeric
    $item_property_id = api_get_item_property_id($course_code, $tool, $ref);
    if (!empty($item_property_id)) {
        $sql = "INSERT IGNORE INTO $tbl_stats_item_property SET
                course_id           = '$course_id',
                item_property_id    = '$item_property_id',
                title               = '".Database::escape_string($title)."',
                content             = '".Database::escape_string($content)."',
                progress            = '".intval($progress)."',
                lastedit_date       = '".api_get_utc_datetime()."',
                lastedit_user_id    = '".api_get_user_id()."',
                session_id          = '".api_get_session_id()."'";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);

        return $affected_rows;
    }

    return false;
}

/**
 * @param string $tool
 * @param int    $ref
 *
 * @return array|resource
 */
function api_get_track_item_property_history($tool, $ref)
{
    $tbl_stats_item_property = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
    $course_id = api_get_course_int_id(); //numeric
    $course_code = api_get_course_id(); //alphanumeric
    $item_property_id = api_get_item_property_id($course_code, $tool, $ref);
    $sql = "SELECT * FROM $tbl_stats_item_property
            WHERE item_property_id = $item_property_id AND course_id = $course_id
            ORDER BY lastedit_date DESC";
    $result = Database::query($sql);
    if ($result === false or $result === null) {
        $result = [];
    } else {
        $result = Database::store_result($result, 'ASSOC');
    }

    return $result;
}

/**
 * Gets item property data from tool of a course id.
 *
 * @param int    $course_id
 * @param string $tool       tool name, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param int    $ref        id of the item itself, linked to key of every tool ('id', ...), "*" = all items of the tool
 * @param int    $session_id
 * @param int    $groupId
 *
 * @return array with all fields from c_item_property, empty array if not found or false if course could not be found
 */
function api_get_item_property_info($course_id, $tool, $ref, $session_id = 0, $groupId = 0)
{
    $courseInfo = api_get_course_info_by_id($course_id);

    if (empty($courseInfo)) {
        return false;
    }

    $tool = Database::escape_string($tool);
    $course_id = $courseInfo['real_id'];
    $ref = (int) $ref;
    $session_id = (int) $session_id;

    $sessionCondition = " session_id = $session_id";
    if (empty($session_id)) {
        $sessionCondition = ' (session_id = 0 OR session_id IS NULL) ';
    }

    // Definition of tables.
    $table = Database::get_course_table(TABLE_ITEM_PROPERTY);

    $sql = "SELECT * FROM $table
            WHERE
                c_id = $course_id AND
                tool = '$tool' AND
                ref = $ref AND
                $sessionCondition ";

    if (!empty($groupId)) {
        $groupId = (int) $groupId;
        $sql .= " AND to_group_id = $groupId ";
    }

    $rs = Database::query($sql);
    $row = [];
    if (Database::num_rows($rs) > 0) {
        $row = Database::fetch_array($rs, 'ASSOC');
    }

    return $row;
}

/**
 * Gets the last item property data from tool of a course id, in chronological order.
 *
 * @param string $tool      tool name, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param int    $ref       id of the item itself, linked to key of every tool ('id', ...), "*" = all items of the tool
 * @param int    $sessionId
 * @param int    $groupId
 *
 * @return array with all fields from c_item_property, empty array if not found or false if course could not be found
 */
function api_get_last_item_property_info(int $courseId, string $tool, int $ref, int $sessionId = null, int $groupId = null): array
{
    $tool = Database::escape_string($tool);
    // Definition of tables.
    $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $sessionCondition = " session_id = $sessionId";
    if (empty($sessionId)) {
        $sessionCondition = ' (session_id = 0 OR session_id IS NULL) ';
    }

    $sql = "SELECT * FROM $table
            WHERE
                c_id = $courseId AND
                tool = '$tool' AND
                ref = $ref AND
                $sessionCondition ";

    if (!empty($groupId)) {
        $sql .= " AND to_group_id = $groupId ";
    }
    // Add criteria to only get the last one
    $sql .= "ORDER BY lastedit_date DESC LIMIT 1";
    $rs = Database::query($sql);
    $row = [];
    if (Database::num_rows($rs) > 0) {
        $row = Database::fetch_array($rs, 'ASSOC');
    }

    return $row;
}

/**
 * Displays a combo box so the user can select his/her preferred language.
 *
 * @param string The desired name= value for the select
 * @param bool Whether we use the JQuery Chozen library or not
 * (in some cases, like the indexing language picker, it can alter the presentation)
 *
 * @return string
 */
function api_get_languages_combo($name = 'language')
{
    $ret = '';
    $platformLanguage = api_get_setting('platformLanguage');

    // Retrieve a complete list of all the languages.
    $language_list = api_get_languages();

    if (count($language_list['name']) < 2) {
        return $ret;
    }

    // The the current language of the user so that his/her language occurs as selected in the dropdown menu.
    if (isset($_SESSION['user_language_choice'])) {
        $default = $_SESSION['user_language_choice'];
    } else {
        $default = $platformLanguage;
    }

    $languages = $language_list['name'];
    $folder = $language_list['folder'];

    $ret .= '<select name="'.$name.'" id="language_chosen" class="selectpicker form-control">';
    foreach ($languages as $key => $value) {
        if ($folder[$key] == $default) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        $ret .= sprintf('<option value=%s" %s>%s</option>', $folder[$key], $selected, $value);
    }
    $ret .= '</select>';

    return $ret;
}

/**
 * Displays a form (drop down menu) so the user can select his/her preferred language.
 * The form works with or without javascript.
 *
 * @param  bool Hide form if only one language available (defaults to false = show the box anyway)
 * @param bool $showAsButton
 *
 * @return string|null Display the box directly
 */
function api_display_language_form($hide_if_no_choice = false, $showAsButton = false)
{
    // Retrieve a complete list of all the languages.
    $language_list = api_get_languages();
    if (count($language_list['name']) <= 1 && $hide_if_no_choice) {
        return null; //don't show any form
    }

    // The the current language of the user so that his/her language occurs as selected in the dropdown menu.
    if (isset($_SESSION['user_language_choice'])) {
        $user_selected_language = $_SESSION['user_language_choice'];
    }
    if (empty($user_selected_language)) {
        $user_selected_language = api_get_setting('platformLanguage');
    }

    $currentLanguageId = api_get_language_id($user_selected_language);
    $currentLanguageInfo = api_get_language_info($currentLanguageId);

    $countryCode = languageCodeToCountryIsoCodeForFlags($currentLanguageInfo['isocode']);
    $url = api_get_self();
    if ($showAsButton) {
        $html = '<div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="flag-icon flag-icon-'.$countryCode.'"></span>
                '.$currentLanguageInfo['original_name'].'
                <span class="caret">
                </span>
              </button>';
    } else {
        $html = '
            <a href="'.$url.'" class="dropdown-toggle" data-toggle="dropdown" role="button">
                <span class="flag-icon flag-icon-'.$countryCode.'"></span>
                '.$currentLanguageInfo['original_name'].'
                <span class="caret"></span>
            </a>
            ';
    }

    $html .= '<ul class="dropdown-menu" role="menu">';
    foreach ($language_list['all'] as $key => $data) {
        $urlLink = $url.'?language='.$data['english_name'];
        $html .= '<li><a href="'.$urlLink.'"><span class="flag-icon flag-icon-'.languageCodeToCountryIsoCodeForFlags($data['isocode']).'"></span> '.$data['original_name'].'</a></li>';
    }
    $html .= '</ul>';

    if ($showAsButton) {
        $html .= '</div>';
    }

    return $html;
}

/**
 * Return a country code based on a language in order to show a country flag.
 * Note: Showing a "language" flag is arguably a bad idea, as several countries
 * share languages and the right flag cannot be shown for all of them.
 *
 * @param string $languageIsoCode
 *
 * @return string
 */
function languageCodeToCountryIsoCodeForFlags($languageIsoCode)
{
    $allow = api_get_configuration_value('language_flags_by_country');

    // @todo save in DB
    switch ($languageIsoCode) {
        case 'ar':
            $country = 'ae';
            break;
        case 'bs':
            $country = 'ba';
            break;
        case 'ca':
            $country = 'es';
            if ($allow) {
                $country = 'catalan';
            }
            break;
        case 'cs':
            $country = 'cz';
            break;
        case 'da':
            $country = 'dk';
            break;
        case 'el':
            $country = 'gr';
            break;
        case 'en':
            $country = 'gb';
            break;
        case 'eu': // Euskera
            $country = 'es';
            if ($allow) {
                $country = 'basque';
            }
            break;
        case 'gl': // galego
            $country = 'es';
            if ($allow) {
                $country = 'galician';
            }
            break;
        case 'he':
            $country = 'il';
            break;
        case 'ja':
            $country = 'jp';
            break;
        case 'ka':
            $country = 'ge';
            break;
        case 'ko':
            $country = 'kr';
            break;
        case 'ms':
            $country = 'my';
            break;
        case 'pt-BR':
            $country = 'br';
            break;
        case 'qu':
            $country = 'pe';
            break;
        case 'sl':
            $country = 'si';
            break;
        case 'sv':
            $country = 'se';
            break;
        case 'uk': // Ukraine
            $country = 'ua';
            break;
        case 'vi': // Vietnam - GH#4231
            $country = 'vn';
            break;
        case 'zh-TW':
        case 'zh':
            $country = 'cn';
            break;
        default:
            $country = $languageIsoCode;
            break;
    }
    $country = strtolower($country);

    return $country;
}

/**
 * Returns a list of all the languages that are made available by the admin.
 *
 * @return array An array with all languages. Structure of the array is
 *               array['name'] = An array with the name of every language
 *               array['folder'] = An array with the corresponding names of the language-folders in the filesystem
 */
function api_get_languages()
{
    $tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
    $sql = "SELECT * FROM $tbl_language WHERE available='1'
            ORDER BY original_name ASC";
    $result = Database::query($sql);
    $language_list = [];
    while ($row = Database::fetch_array($result)) {
        $language_list['name'][] = $row['original_name'];
        $language_list['folder'][] = $row['dokeos_folder'];
        $language_list['all'][] = $row;
    }

    return $language_list;
}

/**
 * Returns a list of all the languages that are made available by the admin.
 *
 * @return array
 */
function api_get_languages_to_array()
{
    $tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
    $sql = "SELECT * FROM $tbl_language
            WHERE available='1' ORDER BY original_name ASC";
    $result = Database::query($sql);
    $languages = [];
    while ($row = Database::fetch_array($result)) {
        $languages[$row['dokeos_folder']] = $row['original_name'];
    }

    return $languages;
}

/**
 * Returns the id (the database id) of a language.
 *
 * @param   string  language name (the corresponding name of the language-folder in the filesystem)
 *
 * @return int id of the language
 */
function api_get_language_id($language)
{
    if (empty($language)) {
        return null;
    }

    static $staticResult = [];

    if (isset($staticResult[$language])) {
        return $staticResult[$language];
    } else {
        $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $language = Database::escape_string($language);
        $sql = "SELECT id FROM $table
                WHERE dokeos_folder = '$language' LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        $staticResult[$language] = $row['id'];

        return $row['id'];
    }
}

/**
 * Gets language of the requested type for the current user. Types are :
 * user_profil_lang : profile language of current user
 * user_select_lang : language selected by user at login
 * course_lang : language of the current course
 * platform_lang : default platform language.
 *
 * @param string $lang_type
 *
 * @return string
 */
function api_get_language_from_type($lang_type)
{
    $return = false;
    switch ($lang_type) {
        case 'platform_lang':
            $temp_lang = api_get_setting('platformLanguage');
            if (!empty($temp_lang)) {
                $return = $temp_lang;
            }
            break;
        case 'user_profil_lang':
            $_user = api_get_user_info();
            if (isset($_user['language']) && !empty($_user['language'])) {
                $return = $_user['language'];
            }
            break;
        case 'user_selected_lang':
            if (isset($_SESSION['user_language_choice']) && !empty($_SESSION['user_language_choice'])) {
                $return = $_SESSION['user_language_choice'];
            }
            break;
        case 'course_lang':
            global $_course;
            $cidReq = null;
            if (empty($_course)) {
                // Code modified because the local.inc.php file it's declarated after this work
                // causing the function api_get_course_info() returns a null value
                $cidReq = isset($_GET["cidReq"]) ? Database::escape_string($_GET["cidReq"]) : null;
                $cDir = (!empty($_GET['cDir']) ? $_GET['cDir'] : null);
                if (empty($cidReq) && !empty($cDir)) {
                    $c = CourseManager::getCourseCodeFromDirectory($cDir);
                    if ($c) {
                        $cidReq = $c;
                    }
                }
            }
            $_course = api_get_course_info($cidReq);
            if (isset($_course['language']) && !empty($_course['language'])) {
                $return = $_course['language'];
                $showCourseInUserLanguage = api_get_course_setting('show_course_in_user_language');
                if ($showCourseInUserLanguage == 1) {
                    $userInfo = api_get_user_info();
                    if (isset($userInfo['language'])) {
                        $return = $userInfo['language'];
                    }
                }
            }
            break;
        default:
            $return = false;
            break;
    }

    return $return;
}

/**
 * Get the language information by its id.
 *
 * @param int $languageId
 *
 * @throws Exception
 *
 * @return array
 */
function api_get_language_info($languageId)
{
    if (empty($languageId)) {
        return [];
    }

    $language = Database::getManager()
        ->find('ChamiloCoreBundle:Language', $languageId);

    if (!$language) {
        return [];
    }

    return [
        'id' => $language->getId(),
        'original_name' => $language->getOriginalName(),
        'english_name' => $language->getEnglishName(),
        'isocode' => $language->getIsocode(),
        'dokeos_folder' => $language->getDokeosFolder(),
        'available' => $language->getAvailable(),
        'parent_id' => $language->getParent() ? $language->getParent()->getId() : null,
    ];
}

/**
 * Returns the name of the visual (CSS) theme to be applied on the current page.
 * The returned name depends on the platform, course or user -wide settings.
 *
 * @return string The visual theme's name, it is the name of a folder inside web/css/themes
 */
function api_get_visual_theme()
{
    static $visual_theme;

    $course_id = api_get_course_id();
    $courseThemeAvailable = false;
    // If called from CLI or from inside a course, it should be reloaded.
    if ('cli' === PHP_SAPI) {
        $visual_theme = null;
    } elseif (!empty($course_id)) {
        $courseThemeAvailable = api_get_setting('allow_course_theme') == 'true';
        if ($courseThemeAvailable) {
            $visual_theme = null;
        }
    }

    if (!isset($visual_theme)) {
        $cacheAvailable = api_get_configuration_value('apc');
        $userThemeAvailable = api_get_setting('user_selected_theme') == 'true';
        // only use a shared cache if no user-based or course-based theme is allowed
        $useCache = ($cacheAvailable && !$userThemeAvailable && !$courseThemeAvailable);
        $apcVar = '';
        if ($useCache) {
            $apcVar = api_get_configuration_value('apc_prefix').'my_campus_visual_theme';
            if (apcu_exists($apcVar)) {
                return apcu_fetch($apcVar);
            }
        }

        $accessUrlId = api_get_current_access_url_id();
        if ('cli' === PHP_SAPI) {
            $accessUrlId = api_get_configuration_value('access_url');
        }

        // Get style directly from DB
        $styleFromDatabase = api_get_settings_params_simple(
            [
                'variable = ? AND access_url = ?' => [
                    'stylesheets',
                    $accessUrlId,
                ],
            ]
        );
        if ($styleFromDatabase) {
            $platform_theme = $styleFromDatabase['selected_value'];
        } else {
            $platform_theme = api_get_setting('stylesheets');
        }

        // Platform's theme.
        $visual_theme = $platform_theme;
        if ($userThemeAvailable) {
            $user_info = api_get_user_info();
            if (isset($user_info['theme'])) {
                $user_theme = $user_info['theme'];

                if (!empty($user_theme)) {
                    $visual_theme = $user_theme;
                    // User's theme.
                }
            }
        }

        if (!empty($course_id)) {
            if ($courseThemeAvailable) {
                $course_theme = api_get_course_setting('course_theme', api_get_course_info());

                if (!empty($course_theme) && $course_theme != -1) {
                    if (!empty($course_theme)) {
                        // Course's theme.
                        $visual_theme = $course_theme;
                    }
                }

                $allow_lp_theme = api_get_course_setting('allow_learning_path_theme');
                if ($allow_lp_theme == 1) {
                    global $lp_theme_css, $lp_theme_config;
                    // These variables come from the file lp_controller.php.
                    if (!$lp_theme_config) {
                        if (!empty($lp_theme_css)) {
                            // LP's theme.
                            $visual_theme = $lp_theme_css;
                        }
                    }
                }
            }
        }

        if (empty($visual_theme)) {
            $visual_theme = 'chamilo';
        }

        global $lp_theme_log;
        if ($lp_theme_log) {
            $visual_theme = $platform_theme;
        }
        if ($useCache) {
            apcu_store($apcVar, $visual_theme, 120);
        }
    }

    return $visual_theme;
}

/**
 * Returns a list of CSS themes currently available in the CSS folder
 * The folder must have a default.css file.
 *
 * @param bool $getOnlyThemeFromVirtualInstance Used by the vchamilo plugin
 *
 * @return array list of themes directories from the css folder
 *               Note: Directory names (names of themes) in the file system should contain ASCII-characters only
 */
function api_get_themes($getOnlyThemeFromVirtualInstance = false)
{
    // This configuration value is set by the vchamilo plugin
    $virtualTheme = api_get_configuration_value('virtual_css_theme_folder');

    $readCssFolder = function ($dir) use ($virtualTheme) {
        $finder = new Finder();
        $themes = $finder->directories()->in($dir)->depth(0)->sortByName();
        $list = [];
        /** @var Symfony\Component\Finder\SplFileInfo $theme */
        foreach ($themes as $theme) {
            $folder = $theme->getFilename();
            // A theme folder is consider if there's a default.css file
            if (!file_exists($theme->getPathname().'/default.css')) {
                continue;
            }
            $name = ucwords(str_replace('_', ' ', $folder));
            if ($folder == $virtualTheme) {
                continue;
            }
            $list[$folder] = $name;
        }

        return $list;
    };

    $dir = api_get_path(SYS_CSS_PATH).'themes/';
    $list = $readCssFolder($dir);

    if (!empty($virtualTheme)) {
        $newList = $readCssFolder($dir.'/'.$virtualTheme);
        if ($getOnlyThemeFromVirtualInstance) {
            return $newList;
        }
        $list = $list + $newList;
        asort($list);
    }

    return $list;
}

/**
 * Find the largest sort value in a given user_course_category
 * This function is used when we are moving a course to a different category
 * and also when a user subscribes to courses (the new course is added at the end of the main category.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @param int $user_course_category the id of the user_course_category
 * @param int $user_id
 *
 * @return int the value of the highest sort of the user_course_category
 */
function api_max_sort_value($user_course_category, $user_id)
{
    $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $sql = "SELECT max(sort) as max_sort FROM $tbl_course_user
            WHERE
                user_id='".intval($user_id)."' AND
                relation_type<>".COURSE_RELATION_TYPE_RRHH." AND
                user_course_cat='".intval($user_course_category)."'";
    $result_max = Database::query($sql);
    if (Database::num_rows($result_max) == 1) {
        $row_max = Database::fetch_array($result_max);

        return $row_max['max_sort'];
    }

    return 0;
}

/**
 * Transforms a number of seconds in hh:mm:ss format.
 *
 * @author Julian Prud'homme
 *
 * @param int    $seconds      number of seconds
 * @param string $space
 * @param bool   $showSeconds
 * @param bool   $roundMinutes
 *
 * @return string the formatted time
 */
function api_time_to_hms($seconds, $space = ':', $showSeconds = true, $roundMinutes = false)
{
    // $seconds = -1 means that we have wrong data in the db.
    if ($seconds == -1) {
        return
            get_lang('Unknown').
            Display::return_icon(
                'info2.gif',
                get_lang('WrongDatasForTimeSpentOnThePlatform'),
                ['align' => 'absmiddle', 'hspace' => '3px']
            );
    }

    // How many hours ?
    $hours = floor($seconds / 3600);

    // How many minutes ?
    $min = floor(($seconds - ($hours * 3600)) / 60);

    if ($roundMinutes) {
        if ($min >= 45) {
            $min = 45;
        }

        if ($min >= 30 && $min <= 44) {
            $min = 30;
        }

        if ($min >= 15 && $min <= 29) {
            $min = 15;
        }

        if ($min >= 0 && $min <= 14) {
            $min = 0;
        }
    }

    // How many seconds
    $sec = floor($seconds - ($hours * 3600) - ($min * 60));

    if ($hours < 10) {
        $hours = "0$hours";
    }

    if ($sec < 10) {
        $sec = "0$sec";
    }

    if ($min < 10) {
        $min = "0$min";
    }

    $seconds = '';
    if ($showSeconds) {
        $seconds = $space.$sec;
    }

    return $hours.$space.$min.$seconds;
}

/* FILE SYSTEM RELATED FUNCTIONS */

/**
 * Returns the permissions to be assigned to every newly created directory by the web-server.
 * The return value is based on the platform administrator's setting
 * "Administration > Configuration settings > Security > Permissions for new directories".
 *
 * @return int returns the permissions in the format "Owner-Group-Others, Read-Write-Execute", as an integer value
 */
function api_get_permissions_for_new_directories()
{
    static $permissions;
    if (!isset($permissions)) {
        $permissions = trim(api_get_setting('permissions_for_new_directories'));
        // The default value 0777 is according to that in the platform administration panel after fresh system installation.
        $permissions = octdec(!empty($permissions) ? $permissions : '0777');
    }

    return $permissions;
}

/**
 * Returns the permissions to be assigned to every newly created directory by the web-server.
 * The return value is based on the platform administrator's setting
 * "Administration > Configuration settings > Security > Permissions for new files".
 *
 * @return int returns the permissions in the format
 *             "Owner-Group-Others, Read-Write-Execute", as an integer value
 */
function api_get_permissions_for_new_files()
{
    static $permissions;
    if (!isset($permissions)) {
        $permissions = trim(api_get_setting('permissions_for_new_files'));
        // The default value 0666 is according to that in the platform
        // administration panel after fresh system installation.
        $permissions = octdec(!empty($permissions) ? $permissions : '0666');
    }

    return $permissions;
}

/**
 * Deletes a file, or a folder and its contents.
 *
 * @author      Aidan Lister <aidan@php.net>
 *
 * @version     1.0.3
 *
 * @param string $dirname Directory to delete
 * @param       bool     Deletes only the content or not
 * @param bool $strict if one folder/file fails stop the loop
 *
 * @return bool Returns TRUE on success, FALSE on failure
 *
 * @see http://aidanlister.com/2004/04/recursively-deleting-a-folder-in-php/
 *
 * @author      Yannick Warnier, adaptation for the Chamilo LMS, April, 2008
 * @author      Ivan Tcholakov, a sanity check about Directory class creation has been added, September, 2009
 */
function rmdirr($dirname, $delete_only_content_in_folder = false, $strict = false)
{
    $res = true;
    // A sanity check.
    if (!file_exists($dirname)) {
        return false;
    }
    $php_errormsg = '';
    // Simple delete for a file.
    if (is_file($dirname) || is_link($dirname)) {
        $res = unlink($dirname);
        if ($res === false) {
            error_log(__FILE__.' line '.__LINE__.': '.((bool) ini_get('track_errors') ? $php_errormsg : 'Error not recorded because track_errors is off in your php.ini'), 0);
        }

        return $res;
    }

    // Loop through the folder.
    $dir = dir($dirname);
    // A sanity check.
    $is_object_dir = is_object($dir);
    if ($is_object_dir) {
        while (false !== $entry = $dir->read()) {
            // Skip pointers.
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Recurse.
            if ($strict) {
                $result = rmdirr("$dirname/$entry");
                if ($result == false) {
                    $res = false;
                    break;
                }
            } else {
                rmdirr("$dirname/$entry");
            }
        }
    }

    // Clean up.
    if ($is_object_dir) {
        $dir->close();
    }

    if ($delete_only_content_in_folder == false) {
        $res = rmdir($dirname);
        if ($res === false) {
            error_log(__FILE__.' line '.__LINE__.': '.((bool) ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }
    }

    return $res;
}

// TODO: This function is to be simplified. File access modes to be implemented.
/**
 * function adapted from a php.net comment
 * copy recursively a folder.
 *
 * @param string $source       the source folder
 * @param string $dest         the dest folder
 * @param array  $exclude      an array of excluded file_name (without extension)
 * @param array  $copied_files the returned array of copied files
 */
function copyr($source, $dest, $exclude = [], $copied_files = [])
{
    if (empty($dest)) {
        return false;
    }
    // Simple copy for a file
    if (is_file($source)) {
        $path_info = pathinfo($source);
        if (!in_array($path_info['filename'], $exclude)) {
            copy($source, $dest);
        }

        return true;
    } elseif (!is_dir($source)) {
        //then source is not a dir nor a file, return
        return false;
    }

    // Make destination directory.
    if (!is_dir($dest)) {
        mkdir($dest, api_get_permissions_for_new_directories());
    }

    // Loop through the folder.
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories.
        if ($dest !== "$source/$entry") {
            $files = copyr("$source/$entry", "$dest/$entry", $exclude, $copied_files);
        }
    }
    // Clean up.
    $dir->close();

    return true;
}

/**
 * @param string $pathname
 * @param string $base_path_document
 * @param int    $session_id
 * @param array
 * @param string
 *
 * @return mixed True if directory already exists, false if a file already exists at
 *               the destination and null if everything goes according to plan
 *@todo: Using DIRECTORY_SEPARATOR is not recommended, this is an obsolete approach.
 * Documentation header to be added here.
 */
function copy_folder_course_session(
    $pathname,
    $base_path_document,
    $session_id,
    $course_info,
    $document,
    $source_course_id,
    $originalFolderNameList = [],
    $originalBaseName = ''
) {
    // Check whether directory already exists.
    if (empty($pathname) || is_dir($pathname)) {
        return true;
    }

    // Ensure that a file with the same name does not already exist.
    if (is_file($pathname)) {
        trigger_error('copy_folder_course_session(): File exists', E_USER_WARNING);

        return false;
    }

    //error_log('checking:');
    //error_log(str_replace($base_path_document.DIRECTORY_SEPARATOR, '', $pathname));
    $baseNoDocument = str_replace('document', '', $originalBaseName);
    $folderTitles = explode('/', $baseNoDocument);
    $folderTitles = array_filter($folderTitles);

    //error_log($baseNoDocument);error_log(print_r($folderTitles, 1));

    $table = Database::get_course_table(TABLE_DOCUMENT);
    $session_id = (int) $session_id;
    $source_course_id = (int) $source_course_id;
    $course_id = $course_info['real_id'];
    $folders = explode(DIRECTORY_SEPARATOR, str_replace($base_path_document.DIRECTORY_SEPARATOR, '', $pathname));
    $new_pathname = $base_path_document;

    $path = '';
    foreach ($folders as $index => $folder) {
        $new_pathname .= DIRECTORY_SEPARATOR.$folder;
        $path .= DIRECTORY_SEPARATOR.$folder;

        if (!file_exists($new_pathname)) {
            $path = Database::escape_string($path);
            //error_log("path: $path");
            $sql = "SELECT * FROM $table
                    WHERE
                        c_id = $source_course_id AND
                        path = '$path' AND
                        filetype = 'folder' AND
                        session_id = '$session_id'";
            $rs1 = Database::query($sql);
            $num_rows = Database::num_rows($rs1);

            if (0 == $num_rows) {
                mkdir($new_pathname, api_get_permissions_for_new_directories());
                $title = basename($new_pathname);

                if (isset($folderTitles[$index + 1])) {
                    $checkPath = $folderTitles[$index + 1];
                    //error_log("check $checkPath");
                    if (isset($originalFolderNameList[$checkPath])) {
                        $title = $originalFolderNameList[$checkPath];
                        //error_log('use this name: '.$title);
                    }
                }

                // Insert new folder with destination session_id.
                $params = [
                    'c_id' => $course_id,
                    'path' => $path,
                    'comment' => $document->comment,
                    'title' => $title,
                    'filetype' => 'folder',
                    'size' => '0',
                    'session_id' => $session_id,
                ];

                //error_log("old $folder"); error_log("Add doc $title in $path");
                $document_id = Database::insert($table, $params);
                if ($document_id) {
                    $sql = "UPDATE $table SET id = iid WHERE iid = $document_id";
                    Database::query($sql);

                    api_item_property_update(
                        $course_info,
                        TOOL_DOCUMENT,
                        $document_id,
                        'FolderCreated',
                        api_get_user_id(),
                        0,
                        0,
                        null,
                        null,
                        $session_id
                    );
                }
            }
        }
    }
}

// TODO: chmodr() is a better name. Some corrections are needed. Documentation header to be added here.
/**
 * @param string $path
 */
function api_chmod_R($path, $filemode)
{
    if (!is_dir($path)) {
        return chmod($path, $filemode);
    }

    $handler = opendir($path);
    while ($file = readdir($handler)) {
        if ($file != '.' && $file != '..') {
            $fullpath = "$path/$file";
            if (!is_dir($fullpath)) {
                if (!chmod($fullpath, $filemode)) {
                    return false;
                }
            } else {
                if (!api_chmod_R($fullpath, $filemode)) {
                    return false;
                }
            }
        }
    }

    closedir($handler);

    return chmod($path, $filemode);
}

// TODO: Where the following function has been copy/pased from? There is no information about author and license. Style, coding conventions...
/**
 * Parse info file format. (e.g: file.info).
 *
 * Files should use an ini-like format to specify values.
 * White-space generally doesn't matter, except inside values.
 * e.g.
 *
 * @verbatim
 *   key = value
 *   key = "value"
 *   key = 'value'
 *   key = "multi-line
 *
 *   value"
 *   key = 'multi-line
 *
 *   value'
 *   key
 *   =
 *   'value'
 * @endverbatim
 *
 * Arrays are created using a GET-like syntax:
 *
 * @verbatim
 *   key[] = "numeric array"
 *   key[index] = "associative array"
 *   key[index][] = "nested numeric array"
 *   key[index][index] = "nested associative array"
 * @endverbatim
 *
 * PHP constants are substituted in, but only when used as the entire value:
 *
 * Comments should start with a semi-colon at the beginning of a line.
 *
 * This function is NOT for placing arbitrary module-specific settings. Use
 * variable_get() and variable_set() for that.
 *
 * Information stored in the module.info file:
 * - name: The real name of the module for display purposes.
 * - description: A brief description of the module.
 * - dependencies: An array of shortnames of other modules this module depends on.
 * - package: The name of the package of modules this module belongs to.
 *
 * Example of .info file:
 * <code>
 * @verbatim
 *   name = Forum
 *   description = Enables threaded discussions about general topics.
 *   dependencies[] = taxonomy
 *   dependencies[] = comment
 *   package = Core - optional
 *   version = VERSION
 * @endverbatim
 * </code>
 *
 * @param string $filename
 *                         The file we are parsing. Accepts file with relative or absolute path.
 *
 * @return
 *   The info array
 */
function api_parse_info_file($filename)
{
    $info = [];

    if (!file_exists($filename)) {
        return $info;
    }

    $data = file_get_contents($filename);
    if (preg_match_all('
        @^\s*                           # Start at the beginning of a line, ignoring leading whitespace
        ((?:
          [^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
          \[[^\[\]]*\]                  # unless they are balanced and not nested
        )+?)
        \s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
        (?:
          ("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
          (\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
          ([^\r\n]*?)                   # Non-quoted string
        )\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
        @msx', $data, $matches, PREG_SET_ORDER)) {
        $key = $value1 = $value2 = $value3 = '';
        foreach ($matches as $match) {
            // Fetch the key and value string.
            $i = 0;
            foreach (['key', 'value1', 'value2', 'value3'] as $var) {
                $$var = isset($match[++$i]) ? $match[$i] : '';
            }
            $value = stripslashes(substr($value1, 1, -1)).stripslashes(substr($value2, 1, -1)).$value3;

            // Parse array syntax.
            $keys = preg_split('/\]?\[/', rtrim($key, ']'));
            $last = array_pop($keys);
            $parent = &$info;

            // Create nested arrays.
            foreach ($keys as $key) {
                if ($key == '') {
                    $key = count($parent);
                }
                if (!isset($parent[$key]) || !is_array($parent[$key])) {
                    $parent[$key] = [];
                }
                $parent = &$parent[$key];
            }

            // Handle PHP constants.
            if (defined($value)) {
                $value = constant($value);
            }

            // Insert actual value.
            if ($last == '') {
                $last = count($parent);
            }
            $parent[$last] = $value;
        }
    }

    return $info;
}

/**
 * Gets Chamilo version from the configuration files.
 *
 * @return string A string of type "1.8.4", or an empty string if the version could not be found
 */
function api_get_version()
{
    return (string) api_get_configuration_value('system_version');
}

/**
 * Gets the software name (the name/brand of the Chamilo-based customized system).
 *
 * @return string
 */
function api_get_software_name()
{
    $name = api_get_configuration_value('software_name');
    if (!empty($name)) {
        return $name;
    } else {
        return 'Chamilo';
    }
}

/**
 * Checks whether status given in parameter exists in the platform.
 *
 * @param mixed the status (can be either int either string)
 *
 * @return bool if the status exists, else returns false
 */
function api_status_exists($status_asked)
{
    global $_status_list;

    return in_array($status_asked, $_status_list) ? true : isset($_status_list[$status_asked]);
}

/**
 * Checks whether status given in parameter exists in the platform. The function
 * returns the status ID or false if it does not exist, but given the fact there
 * is no "0" status, the return value can be checked against
 * if(api_status_key()) to know if it exists.
 *
 * @param   mixed   The status (can be either int or string)
 *
 * @return mixed Status ID if exists, false otherwise
 */
function api_status_key($status)
{
    global $_status_list;

    return isset($_status_list[$status]) ? $status : array_search($status, $_status_list);
}

/**
 * Gets the status langvars list.
 *
 * @return string[] the list of status with their translations
 */
function api_get_status_langvars()
{
    return [
        COURSEMANAGER => get_lang('Teacher', ''),
        SESSIONADMIN => get_lang('SessionsAdmin', ''),
        DRH => get_lang('Drh', ''),
        STUDENT => get_lang('Student', ''),
        ANONYMOUS => get_lang('Anonymous', ''),
        STUDENT_BOSS => get_lang('RoleStudentBoss', ''),
        INVITEE => get_lang('Invited'),
    ];
}

/**
 * The function that retrieves all the possible settings for a certain config setting.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function api_get_settings_options($var)
{
    $table_settings_options = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
    $var = Database::escape_string($var);
    $sql = "SELECT * FROM $table_settings_options
            WHERE variable = '$var'
            ORDER BY id";
    $result = Database::query($sql);
    $settings_options_array = [];
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $settings_options_array[] = $row;
    }

    return $settings_options_array;
}

/**
 * @param array $params
 */
function api_set_setting_option($params)
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
    if (empty($params['id'])) {
        Database::insert($table, $params);
    } else {
        Database::update($table, $params, ['id = ? ' => $params['id']]);
    }
}

/**
 * @param array $params
 */
function api_set_setting_simple($params)
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $url_id = api_get_current_access_url_id();

    if (empty($params['id'])) {
        $params['access_url'] = $url_id;
        Database::insert($table, $params);
    } else {
        Database::update($table, $params, ['id = ? ' => [$params['id']]]);
    }
}

/**
 * @param int $id
 */
function api_delete_setting_option($id)
{
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
    if (!empty($id)) {
        Database::delete($table, ['id = ? ' => $id]);
    }
}

/**
 * Sets a platform configuration setting to a given value.
 *
 * @param string    The variable we want to update
 * @param string    The value we want to record
 * @param string    The sub-variable if any (in most cases, this will remain null)
 * @param string    The category if any (in most cases, this will remain null)
 * @param int       The access_url for which this parameter is valid
 * @param string $cat
 *
 * @return bool|null
 */
function api_set_setting($var, $value, $subvar = null, $cat = null, $access_url = 1)
{
    if (empty($var)) {
        return false;
    }
    $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $var = Database::escape_string($var);
    $value = Database::escape_string($value);
    $access_url = (int) $access_url;
    if (empty($access_url)) {
        $access_url = 1;
    }
    $select = "SELECT id FROM $t_settings WHERE variable = '$var' ";
    if (!empty($subvar)) {
        $subvar = Database::escape_string($subvar);
        $select .= " AND subkey = '$subvar'";
    }
    if (!empty($cat)) {
        $cat = Database::escape_string($cat);
        $select .= " AND category = '$cat'";
    }
    if ($access_url > 1) {
        $select .= " AND access_url = $access_url";
    } else {
        $select .= " AND access_url = 1 ";
    }

    $res = Database::query($select);
    if (Database::num_rows($res) > 0) {
        // Found item for this access_url.
        $row = Database::fetch_array($res);
        $sql = "UPDATE $t_settings SET selected_value = '$value'
                WHERE id = ".$row['id'];
        Database::query($sql);
    } else {
        // Item not found for this access_url, we have to check if it exist with access_url = 1
        $select = "SELECT * FROM $t_settings
                   WHERE variable = '$var' AND access_url = 1 ";
        // Just in case
        if ($access_url == 1) {
            if (!empty($subvar)) {
                $select .= " AND subkey = '$subvar'";
            }
            if (!empty($cat)) {
                $select .= " AND category = '$cat'";
            }
            $res = Database::query($select);
            if (Database::num_rows($res) > 0) {
                // We have a setting for access_url 1, but none for the current one, so create one.
                $row = Database::fetch_array($res);
                $insert = "INSERT INTO $t_settings (variable, subkey, type,category, selected_value, title, comment, scope, subkeytext, access_url)
                        VALUES
                        ('".$row['variable']."',".(!empty($row['subkey']) ? "'".$row['subkey']."'" : "NULL").",".
                        "'".$row['type']."','".$row['category']."',".
                        "'$value','".$row['title']."',".
                        "".(!empty($row['comment']) ? "'".$row['comment']."'" : "NULL").",".(!empty($row['scope']) ? "'".$row['scope']."'" : "NULL").",".
                        "".(!empty($row['subkeytext']) ? "'".$row['subkeytext']."'" : "NULL").",$access_url)";
                Database::query($insert);
            } else {
                // Such a setting does not exist.
                //error_log(__FILE__.':'.__LINE__.': Attempting to update setting '.$var.' ('.$subvar.') which does not exist at all', 0);
            }
        } else {
            // Other access url.
            if (!empty($subvar)) {
                $select .= " AND subkey = '$subvar'";
            }
            if (!empty($cat)) {
                $select .= " AND category = '$cat'";
            }
            $res = Database::query($select);

            if (Database::num_rows($res) > 0) {
                // We have a setting for access_url 1, but none for the current one, so create one.
                $row = Database::fetch_array($res);
                if ($row['access_url_changeable'] == 1) {
                    $insert = "INSERT INTO $t_settings (variable,subkey, type,category, selected_value,title, comment,scope, subkeytext,access_url, access_url_changeable) VALUES
                            ('".$row['variable']."',".
                            (!empty($row['subkey']) ? "'".$row['subkey']."'" : "NULL").",".
                            "'".$row['type']."','".$row['category']."',".
                            "'$value','".$row['title']."',".
                            "".(!empty($row['comment']) ? "'".$row['comment']."'" : "NULL").",".
                            (!empty($row['scope']) ? "'".$row['scope']."'" : "NULL").",".
                            "".(!empty($row['subkeytext']) ? "'".$row['subkeytext']."'" : "NULL").",$access_url,".$row['access_url_changeable'].")";
                    Database::query($insert);
                }
            } else { // Such a setting does not exist.
                //error_log(__FILE__.':'.__LINE__.': Attempting to update setting '.$var.' ('.$subvar.') which does not exist at all. The access_url is: '.$access_url.' ',0);
            }
        }
    }
}

/**
 * Sets a whole category of settings to one specific value.
 *
 * @param string    Category
 * @param string    Value
 * @param int       Access URL. Optional. Defaults to 1
 * @param array     Optional array of filters on field type
 * @param string $category
 * @param string $value
 *
 * @return bool
 */
function api_set_settings_category($category, $value = null, $access_url = 1, $fieldtype = [])
{
    if (empty($category)) {
        return false;
    }
    $category = Database::escape_string($category);
    $t_s = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $access_url = (int) $access_url;
    if (empty($access_url)) {
        $access_url = 1;
    }
    if (isset($value)) {
        $value = Database::escape_string($value);
        $sql = "UPDATE $t_s SET selected_value = '$value'
                WHERE category = '$category' AND access_url = $access_url";
        if (is_array($fieldtype) && count($fieldtype) > 0) {
            $sql .= " AND ( ";
            $i = 0;
            foreach ($fieldtype as $type) {
                if ($i > 0) {
                    $sql .= ' OR ';
                }
                $type = Database::escape_string($type);
                $sql .= " type='".$type."' ";
                $i++;
            }
            $sql .= ")";
        }
        $res = Database::query($sql);

        return $res !== false;
    } else {
        $sql = "UPDATE $t_s SET selected_value = NULL
                WHERE category = '$category' AND access_url = $access_url";
        if (is_array($fieldtype) && count($fieldtype) > 0) {
            $sql .= " AND ( ";
            $i = 0;
            foreach ($fieldtype as $type) {
                if ($i > 0) {
                    $sql .= ' OR ';
                }
                $type = Database::escape_string($type);
                $sql .= " type='".$type."' ";
                $i++;
            }
            $sql .= ")";
        }
        $res = Database::query($sql);

        return $res !== false;
    }
}

/**
 * Gets all available access urls in an array (as in the database).
 *
 * @return array An array of database records
 */
function api_get_access_urls($from = 0, $to = 1000000, $order = 'url', $direction = 'ASC')
{
    $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
    $from = (int) $from;
    $to = (int) $to;
    $order = Database::escape_string($order);
    $direction = Database::escape_string($direction);
    $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;

    $sql = "SELECT id, url, description, active, created_by, tms
            FROM $table
            ORDER BY `$order` $direction
            LIMIT $to OFFSET $from";
    $res = Database::query($sql);

    return Database::store_result($res);
}

/**
 * Gets the access url info in an array.
 *
 * @param int  $id            Id of the access url
 * @param bool $returnDefault Set to false if you want the real URL if URL 1 is still 'http://localhost/'
 *
 * @return array All the info (url, description, active, created_by, tms)
 *               from the access_url table
 *
 * @author Julio Montoya
 */
function api_get_access_url($id, $returnDefault = true)
{
    static $staticResult;
    $id = (int) $id;

    if (isset($staticResult[$id])) {
        $result = $staticResult[$id];
    } else {
        // Calling the Database:: library dont work this is handmade.
        $table_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
        $sql = "SELECT url, description, active, created_by, tms
                FROM $table_access_url WHERE id = '$id' ";
        $res = Database::query($sql);
        $result = @Database::fetch_array($res);
        $staticResult[$id] = $result;
    }

    // If the result url is 'http://localhost/' (the default) and the root_web
    // (=current url) is different, and the $id is = 1 (which might mean
    // api_get_current_access_url_id() returned 1 by default), then return the
    // root_web setting instead of the current URL
    // This is provided as an option to avoid breaking the storage of URL-specific
    // homepages in home/localhost/
    if ($id === 1 && $returnDefault === false) {
        $currentUrl = api_get_current_access_url_id();
        // only do this if we are on the main URL (=1), otherwise we could get
        // information on another URL instead of the one asked as parameter
        if ($currentUrl === 1) {
            $rootWeb = api_get_path(WEB_PATH);
            $default = 'http://localhost/';
            if ($result['url'] === $default && $rootWeb != $default) {
                $result['url'] = $rootWeb;
            }
        }
    }

    return $result;
}

/**
 * Gets all the current settings for a specific access url.
 *
 * @param string    The category, if any, that we want to get
 * @param string    Whether we want a simple list (display a category) or
 * a grouped list (group by variable as in settings.php default). Values: 'list' or 'group'
 * @param int       Access URL's ID. Optional. Uses 1 by default, which is the unique URL
 *
 * @return array Array of database results for the current settings of the current access URL
 */
function api_get_settings($cat = null, $ordering = 'list', $access_url = 1, $url_changeable = 0)
{
    // Try getting settings from cache first (avoids query w/ ~375 rows result)
    $apcVarName = '';
    $apcVar = [];
    $cacheAvailable = api_get_configuration_value('apc');
    if ($cacheAvailable) {
        $apcVarName = api_get_configuration_value('apc_prefix').'settings';
        $catName = (empty($cat) ? 'global' : $cat);

        if (apcu_exists($apcVarName)) {
            $apcVar = apcu_fetch($apcVarName);
            if (!empty($apcVar[$catName]) && !empty($apcVar[$catName][$ordering]) && isset($apcVar[$catName][$ordering][$url_changeable])) {
                return $apcVar[$catName][$ordering][$url_changeable];
            }
        }
    }
    // Could not find settings in cache (or already expired), so query DB
    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $access_url = (int) $access_url;
    $where_condition = '';
    if ($url_changeable == 1) {
        $where_condition = " AND access_url_changeable= '1' ";
    }
    if (empty($access_url) || $access_url == -1) {
        $access_url = 1;
    }
    $sql = "SELECT * FROM $table
            WHERE access_url = $access_url  $where_condition ";

    if (!empty($cat)) {
        $cat = Database::escape_string($cat);
        $sql .= " AND category='$cat' ";
    }
    if ($ordering == 'group') {
        $sql .= " ORDER BY id ASC";
    } else {
        $sql .= " ORDER BY 1,2 ASC";
    }
    $result = Database::query($sql);
    if ($result === null) {
        return [];
    }
    $result = Database::store_result($result, 'ASSOC');

    if ($cacheAvailable) {
        // If we got here, it means cache is available but the settings
        // were not recently stored, so now we have them, let's store them
        if (empty($apcVar[$catName])) {
            $apcVar[$catName] = [];
        }
        if (empty($apcVar[$catName][$ordering])) {
            $apcVar[$catName][$ordering] = [];
        }
        $apcVar[$catName][$ordering][$url_changeable] = $result;
        apcu_store($apcVarName, $apcVar, 600);
    }

    return $result;
}

/**
 * @param string $value       The value we want to record
 * @param string $variable    The variable name we want to insert
 * @param string $subKey      The subkey for the variable we want to insert
 * @param string $type        The type for the variable we want to insert
 * @param string $category    The category for the variable we want to insert
 * @param string $title       The title
 * @param string $comment     The comment
 * @param string $scope       The scope
 * @param string $subKeyText  The subkey text
 * @param int    $accessUrlId The access_url for which this parameter is valid
 * @param int    $visibility  The changeability of this setting for non-master urls
 *
 * @return int The setting ID
 */
function api_add_setting(
    $value,
    $variable,
    $subKey = '',
    $type = 'textfield',
    $category = '',
    $title = '',
    $comment = '',
    $scope = '',
    $subKeyText = '',
    $accessUrlId = 1,
    $visibility = 0
) {
    $em = Database::getManager();
    $settingRepo = $em->getRepository('ChamiloCoreBundle:SettingsCurrent');
    $accessUrlId = (int) $accessUrlId ?: 1;

    if (is_array($value)) {
        $value = serialize($value);
    } else {
        $value = trim($value);
    }

    $criteria = ['variable' => $variable, 'accessUrl' => $accessUrlId];

    if (!empty($subKey)) {
        $criteria['subkey'] = $subKey;
    }

    // Check if this variable doesn't exist already
    /** @var SettingsCurrent $setting */
    $setting = $settingRepo->findOneBy($criteria);

    if ($setting) {
        $setting->setSelectedValue($value);

        $em->persist($setting);
        $em->flush();

        return $setting->getId();
    }

    // Item not found for this access_url, we have to check if the whole thing is missing
    // (in which case we ignore the insert) or if there *is* a record but just for access_url = 1
    $setting = new SettingsCurrent();
    $setting
        ->setVariable($variable)
        ->setSelectedValue($value)
        ->setType($type)
        ->setCategory($category)
        ->setSubkey($subKey)
        ->setTitle($title)
        ->setComment($comment)
        ->setScope($scope)
        ->setSubkeytext($subKeyText)
        ->setAccessUrl($accessUrlId)
        ->setAccessUrlChangeable($visibility);

    $em->persist($setting);
    $em->flush();

    return $setting->getId();
}

/**
 * Checks wether a user can or can't view the contents of a course.
 *
 * @deprecated use CourseManager::is_user_subscribed_in_course
 *
 * @param int $userid User id or NULL to get it from $_SESSION
 * @param int $cid    course id to check whether the user is allowed
 *
 * @return bool
 */
function api_is_course_visible_for_user($userid = null, $cid = null)
{
    if ($userid === null) {
        $userid = api_get_user_id();
    }
    if (empty($userid) || strval(intval($userid)) != $userid) {
        if (api_is_anonymous()) {
            $userid = api_get_anonymous_id();
        } else {
            return false;
        }
    }
    $cid = Database::escape_string($cid);

    $courseInfo = api_get_course_info($cid);
    $courseId = $courseInfo['real_id'];
    $is_platformAdmin = api_is_platform_admin();

    $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
    $course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);

    $sql = "SELECT
                $course_table.category_code,
                $course_table.visibility,
                $course_table.code,
                $course_cat_table.code
            FROM $course_table
            LEFT JOIN $course_cat_table
                ON $course_table.category_code = $course_cat_table.code
            WHERE
                $course_table.code = '$cid'
            LIMIT 1";

    $result = Database::query($sql);

    if (Database::num_rows($result) > 0) {
        $visibility = Database::fetch_array($result);
        $visibility = $visibility['visibility'];
    } else {
        $visibility = 0;
    }
    // Shortcut permissions in case the visibility is "open to the world".
    if ($visibility === COURSE_VISIBILITY_OPEN_WORLD) {
        return true;
    }

    $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

    $sql = "SELECT
                is_tutor, status
            FROM $tbl_course_user
            WHERE
                user_id  = '$userid' AND
                relation_type <> '".COURSE_RELATION_TYPE_RRHH."' AND
                c_id = $courseId
            LIMIT 1";

    $result = Database::query($sql);

    if (Database::num_rows($result) > 0) {
        // This user has got a recorded state for this course.
        $cuData = Database::fetch_array($result);
        $is_courseMember = true;
        $is_courseAdmin = ($cuData['status'] == 1);
    }

    if (!$is_courseAdmin) {
        // This user has no status related to this course.
        // Is it the session coach or the session admin?
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $sql = "SELECT
                    session.id_coach, session_admin_id, session.id
                FROM
                    $tbl_session as session
                INNER JOIN $tbl_session_course
                    ON session_rel_course.session_id = session.id
                    AND session_rel_course.c_id = '$courseId'
                LIMIT 1";

        $result = Database::query($sql);
        $row = Database::store_result($result);

        if ($row[0]['id_coach'] == $userid) {
            $is_courseMember = true;
            $is_courseAdmin = false;
        } elseif ($row[0]['session_admin_id'] == $userid) {
            $is_courseMember = false;
            $is_courseAdmin = false;
        } else {
            // Check if the current user is the course coach.
            $sql = "SELECT 1
                    FROM $tbl_session_course
                    WHERE session_rel_course.c_id = '$courseId'
                    AND session_rel_course.id_coach = '$userid'
                    LIMIT 1";

            $result = Database::query($sql);

            //if ($row = Database::fetch_array($result)) {
            if (Database::num_rows($result) > 0) {
                $is_courseMember = true;
                $tbl_user = Database::get_main_table(TABLE_MAIN_USER);

                $sql = "SELECT status FROM $tbl_user
                        WHERE user_id = $userid
                        LIMIT 1";

                $result = Database::query($sql);

                if (Database::result($result, 0, 0) == 1) {
                    $is_courseAdmin = true;
                } else {
                    $is_courseAdmin = false;
                }
            } else {
                // Check if the user is a student is this session.
                $sql = "SELECT  id
                        FROM $tbl_session_course_user
                        WHERE
                            user_id  = '$userid' AND
                            c_id = '$courseId'
                        LIMIT 1";

                if (Database::num_rows($result) > 0) {
                    // This user haa got a recorded state for this course.
                    while ($row = Database::fetch_array($result)) {
                        $is_courseMember = true;
                        $is_courseAdmin = false;
                    }
                }
            }
        }
    }

    switch ($visibility) {
        case COURSE_VISIBILITY_OPEN_WORLD:
            return true;
        case COURSE_VISIBILITY_OPEN_PLATFORM:
            return isset($userid);
        case COURSE_VISIBILITY_REGISTERED:
        case COURSE_VISIBILITY_CLOSED:
            return $is_platformAdmin || $is_courseMember || $is_courseAdmin;
        case COURSE_VISIBILITY_HIDDEN:
            return $is_platformAdmin;
    }

    return false;
}

/**
 * Returns whether an element (forum, message, survey ...) belongs to a session or not.
 *
 * @param string the tool of the element
 * @param int the element id in database
 * @param int the session_id to compare with element session id
 *
 * @return bool true if the element is in the session, false else
 */
function api_is_element_in_the_session($tool, $element_id, $session_id = null)
{
    if (is_null($session_id)) {
        $session_id = api_get_session_id();
    }

    $element_id = (int) $element_id;

    if (empty($element_id)) {
        return false;
    }

    // Get information to build query depending of the tool.
    switch ($tool) {
        case TOOL_SURVEY:
            $table_tool = Database::get_course_table(TABLE_SURVEY);
            $key_field = 'survey_id';
            break;
        case TOOL_ANNOUNCEMENT:
            $table_tool = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $key_field = 'id';
            break;
        case TOOL_AGENDA:
            $table_tool = Database::get_course_table(TABLE_AGENDA);
            $key_field = 'id';
            break;
        case TOOL_GROUP:
            $table_tool = Database::get_course_table(TABLE_GROUP);
            $key_field = 'id';
            break;
        default:
            return false;
    }
    $course_id = api_get_course_int_id();

    $sql = "SELECT session_id FROM $table_tool
            WHERE c_id = $course_id AND $key_field =  ".$element_id;
    $rs = Database::query($sql);
    if ($element_session_id = Database::result($rs, 0, 0)) {
        if ($element_session_id == intval($session_id)) {
            // The element belongs to the session.
            return true;
        }
    }

    return false;
}

/**
 * Replaces "forbidden" characters in a filename string.
 *
 * @param string $filename
 * @param bool   $treat_spaces_as_hyphens
 *
 * @return string
 */
function api_replace_dangerous_char($filename, $treat_spaces_as_hyphens = true)
{
    // Some non-properly encoded file names can cause the whole file to be
    // skipped when uploaded. Avoid this by detecting the encoding and
    // converting to UTF-8, setting the source as ASCII (a reasonably
    // limited characters set) if nothing could be found (BT#
    $encoding = api_detect_encoding($filename);
    if (empty($encoding)) {
        $encoding = 'ASCII';
        if (!api_is_valid_ascii($filename)) {
            // try iconv and try non standard ASCII a.k.a CP437
            // see BT#15022
            if (function_exists('iconv')) {
                $result = iconv('CP437', 'UTF-8', $filename);
                if (api_is_valid_utf8($result)) {
                    $filename = $result;
                    $encoding = 'UTF-8';
                }
            }
        }
    }

    $filename = api_to_system_encoding($filename, $encoding);

    $url = URLify::filter(
        $filename,
        250,
        '',
        true,
        false,
        false,
        false,
        $treat_spaces_as_hyphens
    );

    // Replace multiple dots at the end.
    $regex = "/\.+$/";
    $url = preg_replace($regex, '', $url);

    return $url;
}

/**
 * Fixes the $_SERVER['REQUEST_URI'] that is empty in IIS6.
 *
 * @author Ivan Tcholakov, 28-JUN-2006.
 */
function api_request_uri()
{
    if (!empty($_SERVER['REQUEST_URI'])) {
        return $_SERVER['REQUEST_URI'];
    }
    $uri = $_SERVER['SCRIPT_NAME'];
    if (!empty($_SERVER['QUERY_STRING'])) {
        $uri .= '?'.$_SERVER['QUERY_STRING'];
    }
    $_SERVER['REQUEST_URI'] = $uri;

    return $uri;
}

/**
 * Gets the current access_url id of the Chamilo Platform.
 *
 * @return int access_url_id of the current Chamilo Installation or 1 if multiple_access_urls is not enabled
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
function api_get_current_access_url_id()
{
    if ('cli' === PHP_SAPI) {
        $accessUrlId = api_get_configuration_value('access_url');
        if (!empty($accessUrlId)) {
            return $accessUrlId;
        }
    }

    static $id;
    if (!empty($id)) {
        return (int) $id;
    }

    if (!api_get_multiple_access_url()) {
        // If the feature is not enabled, assume 1 and return before querying
        // the database
        return 1;
    }

    $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
    $path = Database::escape_string(api_get_path(WEB_PATH));
    $sql = "SELECT id FROM $table WHERE url = '".$path."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $id = Database::result($result, 0, 0);
        if ($id === false) {
            return -1;
        }

        return (int) $id;
    }

    $id = 1;

    //if the url in WEB_PATH was not found, it can only mean that there is
    // either a configuration problem or the first URL has not been defined yet
    // (by default it is http://localhost/). Thus the more sensible thing we can
    // do is return 1 (the main URL) as the user cannot hack this value anyway
    return 1;
}

/**
 * Gets the registered urls from a given user id.
 *
 * @param int $user_id
 * @param int $checkCourseId the course id to check url access
 *
 * @return array
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
function api_get_access_url_from_user($user_id, $checkCourseId = null)
{
    $user_id = (int) $user_id;
    $table_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $table_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
    $includeIds = "";
    if (isset($checkCourseId)) {
        $cid = (int) $checkCourseId;
        $tblUrlCourse = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql = "SELECT access_url_id
            FROM $tblUrlCourse url_rel_course
            INNER JOIN $table_url u
            ON (url_rel_course.access_url_id = u.id)
            WHERE c_id = $cid";
        $rs = Database::query($sql);
        $courseUrlIds = [];
        if (Database::num_rows($rs) > 0) {
            while ($rowC = Database::fetch_array($rs, 'ASSOC')) {
                $courseUrlIds[] = $rowC['access_url_id'];
            }
        }
        if (!empty($courseUrlIds)) {
            $includeIds = " AND access_url_id IN (".implode(',', $courseUrlIds).")";
        }
    }

    $sql = "SELECT access_url_id
            FROM $table_url_rel_user url_rel_user
            INNER JOIN $table_url u
            ON (url_rel_user.access_url_id = u.id)
            WHERE user_id = $user_id $includeIds
            ORDER BY access_url_id";
    $result = Database::query($sql);
    $list = [];
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $list[] = $row['access_url_id'];
    }

    return $list;
}

/**
 * Gets the status of a user in a course.
 *
 * @param int $user_id
 * @param int $courseId
 *
 * @return int user status
 */
function api_get_status_of_user_in_course($user_id, $courseId)
{
    $tbl_rel_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    if (!empty($user_id) && !empty($courseId)) {
        $user_id = intval($user_id);
        $courseId = intval($courseId);
        $sql = 'SELECT status
                FROM '.$tbl_rel_course_user.'
                WHERE user_id='.$user_id.' AND c_id = '.$courseId;
        $result = Database::query($sql);
        $row_status = Database::fetch_array($result, 'ASSOC');

        return $row_status['status'];
    } else {
        return 0;
    }
}

/**
 * Checks whether the curent user is in a group or not.
 *
 * @param string        The group id - optional (takes it from session if not given)
 * @param string        The course code - optional (no additional check by course if course code is not given)
 *
 * @return bool
 *
 * @author Ivan Tcholakov
 */
function api_is_in_group($groupIdParam = null, $courseCodeParam = null)
{
    if (!empty($courseCodeParam)) {
        $courseCode = api_get_course_id();
        if (!empty($courseCode)) {
            if ($courseCodeParam != $courseCode) {
                return false;
            }
        } else {
            return false;
        }
    }

    $groupId = api_get_group_id();

    if (!empty($groupId)) {
        if (!empty($groupIdParam)) {
            return $groupIdParam == $groupId;
        } else {
            return true;
        }
    }

    return false;
}

/**
 * Checks whether a secret key is valid.
 *
 * @param string $original_key_secret - secret key from (webservice) client
 * @param string $security_key        - security key from Chamilo
 *
 * @return bool - true if secret key is valid, false otherwise
 */
function api_is_valid_secret_key($original_key_secret, $security_key)
{
    if (empty($original_key_secret) || empty($security_key)) {
        return false;
    }

    return (string) $original_key_secret === sha1($security_key);
}

/**
 * Checks whether a user is into course.
 *
 * @param int $course_id - the course id
 * @param int $user_id   - the user id
 *
 * @return bool
 */
function api_is_user_of_course($course_id, $user_id)
{
    $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $sql = 'SELECT user_id FROM '.$tbl_course_rel_user.'
            WHERE
                c_id ="'.intval($course_id).'" AND
                user_id = "'.intval($user_id).'" AND
                relation_type <> '.COURSE_RELATION_TYPE_RRHH.' ';
    $result = Database::query($sql);

    return Database::num_rows($result) == 1;
}

/**
 * Checks whether the server's operating system is Windows (TM).
 *
 * @return bool - true if the operating system is Windows, false otherwise
 */
function api_is_windows_os()
{
    if (function_exists('php_uname')) {
        // php_uname() exists as of PHP 4.0.2, according to the documentation.
        // We expect that this function will always work for Chamilo 1.8.x.
        $os = php_uname();
    }
    // The following methods are not needed, but let them stay, just in case.
    elseif (isset($_ENV['OS'])) {
        // Sometimes $_ENV['OS'] may not be present (bugs?)
        $os = $_ENV['OS'];
    } elseif (defined('PHP_OS')) {
        // PHP_OS means on which OS PHP was compiled, this is why
        // using PHP_OS is the last choice for detection.
        $os = PHP_OS;
    } else {
        return false;
    }

    return strtolower(substr((string) $os, 0, 3)) == 'win';
}

/**
 * This function informs whether the sent request is XMLHttpRequest.
 */
function api_is_xml_http_request()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * This wrapper function has been implemented for avoiding some known problems about the function getimagesize().
 *
 * @see http://php.net/manual/en/function.getimagesize.php
 * @see http://www.dokeos.com/forum/viewtopic.php?t=12345
 * @see http://www.dokeos.com/forum/viewtopic.php?t=16355
 *
 * @return int
 */
function api_getimagesize($path)
{
    $image = new Image($path);

    return $image->get_image_size();
}

/**
 * This function resizes an image, with preserving its proportions (or aspect ratio).
 *
 * @author Ivan Tcholakov, MAY-2009.
 *
 * @param int $image         System path or URL of the image
 * @param int $target_width  Targeted width
 * @param int $target_height Targeted height
 *
 * @return array Calculated new width and height
 */
function api_resize_image($image, $target_width, $target_height)
{
    $image_properties = api_getimagesize($image);

    return api_calculate_image_size(
        $image_properties['width'],
        $image_properties['height'],
        $target_width,
        $target_height
    );
}

/**
 * This function calculates new image size, with preserving image's proportions (or aspect ratio).
 *
 * @author Ivan Tcholakov, MAY-2009.
 * @author The initial idea has been taken from code by Patrick Cool, MAY-2004.
 *
 * @param int $image_width   Initial width
 * @param int $image_height  Initial height
 * @param int $target_width  Targeted width
 * @param int $target_height Targeted height
 *
 * @return array Calculated new width and height
 */
function api_calculate_image_size(
    $image_width,
    $image_height,
    $target_width,
    $target_height
) {
    // Only maths is here.
    $result = ['width' => $image_width, 'height' => $image_height];
    if ($image_width <= 0 || $image_height <= 0) {
        return $result;
    }
    $resize_factor_width = $target_width / $image_width;
    $resize_factor_height = $target_height / $image_height;
    $delta_width = $target_width - $image_width * $resize_factor_height;
    $delta_height = $target_height - $image_height * $resize_factor_width;
    if ($delta_width > $delta_height) {
        $result['width'] = ceil($image_width * $resize_factor_height);
        $result['height'] = ceil($image_height * $resize_factor_height);
    } elseif ($delta_width < $delta_height) {
        $result['width'] = ceil($image_width * $resize_factor_width);
        $result['height'] = ceil($image_height * $resize_factor_width);
    } else {
        $result['width'] = ceil($target_width);
        $result['height'] = ceil($target_height);
    }

    return $result;
}

/**
 * Returns a list of Chamilo's tools or
 * checks whether a given identificator is a valid Chamilo's tool.
 *
 * @author Isaac flores paz
 *
 * @param string The tool name to filter
 *
 * @return mixed Filtered string or array
 */
function api_get_tools_lists($my_tool = null)
{
    $tools_list = [
        TOOL_DOCUMENT,
        TOOL_THUMBNAIL,
        TOOL_HOTPOTATOES,
        TOOL_CALENDAR_EVENT,
        TOOL_LINK,
        TOOL_COURSE_DESCRIPTION,
        TOOL_SEARCH,
        TOOL_LEARNPATH,
        TOOL_ANNOUNCEMENT,
        TOOL_FORUM,
        TOOL_THREAD,
        TOOL_POST,
        TOOL_DROPBOX,
        TOOL_QUIZ,
        TOOL_USER,
        TOOL_GROUP,
        TOOL_BLOGS,
        TOOL_CHAT,
        TOOL_STUDENTPUBLICATION,
        TOOL_TRACKING,
        TOOL_HOMEPAGE_LINK,
        TOOL_COURSE_SETTING,
        TOOL_BACKUP,
        TOOL_COPY_COURSE_CONTENT,
        TOOL_RECYCLE_COURSE,
        TOOL_COURSE_HOMEPAGE,
        TOOL_COURSE_RIGHTS_OVERVIEW,
        TOOL_UPLOAD,
        TOOL_COURSE_MAINTENANCE,
        TOOL_SURVEY,
        TOOL_WIKI,
        TOOL_GLOSSARY,
        TOOL_GRADEBOOK,
        TOOL_NOTEBOOK,
        TOOL_ATTENDANCE,
        TOOL_COURSE_PROGRESS,
    ];
    if (empty($my_tool)) {
        return $tools_list;
    }

    return in_array($my_tool, $tools_list) ? $my_tool : '';
}

/**
 * Checks whether we already approved the last version term and condition.
 *
 * @param int user id
 *
 * @return bool true if we pass false otherwise
 */
function api_check_term_condition($userId)
{
    if (api_get_setting('allow_terms_conditions') === 'true') {
        // Check if exists terms and conditions
        if (LegalManager::count() == 0) {
            return true;
        }

        $extraFieldValue = new ExtraFieldValue('user');
        $data = $extraFieldValue->get_values_by_handler_and_field_variable(
            $userId,
            'legal_accept'
        );

        if (!empty($data) && isset($data['value']) && !empty($data['value'])) {
            $result = $data['value'];
            $user_conditions = explode(':', $result);
            $version = $user_conditions[0];
            $langId = $user_conditions[1];
            $realVersion = LegalManager::get_last_version($langId);

            return $version >= $realVersion;
        }

        return false;
    }

    return false;
}

/**
 * Gets all information of a tool into course.
 *
 * @param int The tool id
 *
 * @return array
 */
function api_get_tool_information_by_name($name)
{
    $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
    $course_id = api_get_course_int_id();
    $sql = "SELECT * FROM $t_tool
            WHERE c_id = $course_id  AND name = '".Database::escape_string($name)."' ";
    $rs = Database::query($sql);

    return Database::fetch_array($rs, 'ASSOC');
}

/**
 * Function used to protect a "global" admin script.
 * The function blocks access when the user has no global platform admin rights.
 * Global admins are the admins that are registered in the main.admin table
 * AND the users who have access to the "principal" portal.
 * That means that there is a record in the main.access_url_rel_user table
 * with his user id and the access_url_id=1.
 *
 * @author Julio Montoya
 *
 * @param int $user_id
 *
 * @return bool
 */
function api_is_global_platform_admin($user_id = null)
{
    $user_id = (int) $user_id;
    if (empty($user_id)) {
        $user_id = api_get_user_id();
    }
    if (api_is_platform_admin_by_id($user_id)) {
        $urlList = api_get_access_url_from_user($user_id);
        // The admin is registered in the first "main" site with access_url_id = 1
        if (in_array(1, $urlList)) {
            return true;
        } else {
            return false;
        }
    }

    return false;
}

/**
 * @param int  $admin_id_to_check
 * @param int  $my_user_id
 * @param bool $allow_session_admin
 *
 * @return bool
 */
function api_global_admin_can_edit_admin(
    $admin_id_to_check,
    $my_user_id = null,
    $allow_session_admin = false
) {
    if (empty($my_user_id)) {
        $my_user_id = api_get_user_id();
    }

    $iam_a_global_admin = api_is_global_platform_admin($my_user_id);
    $user_is_global_admin = api_is_global_platform_admin($admin_id_to_check);

    if ($iam_a_global_admin) {
        // Global admin can edit everything
        return true;
    } else {
        // If i'm a simple admin
        $is_platform_admin = api_is_platform_admin_by_id($my_user_id);

        if ($allow_session_admin) {
            $is_platform_admin = api_is_platform_admin_by_id($my_user_id) || (api_get_user_status($my_user_id) == SESSIONADMIN);
        }

        if ($is_platform_admin) {
            if ($user_is_global_admin) {
                return false;
            } else {
                return true;
            }
        } else {
            return false;
        }
    }
}

/**
 * @param int  $admin_id_to_check
 * @param int  $my_user_id
 * @param bool $allow_session_admin
 *
 * @return bool|null
 */
function api_protect_super_admin($admin_id_to_check, $my_user_id = null, $allow_session_admin = false)
{
    if (api_global_admin_can_edit_admin($admin_id_to_check, $my_user_id, $allow_session_admin)) {
        return true;
    } else {
        api_not_allowed();
    }
}

/**
 * Function used to protect a global admin script.
 * The function blocks access when the user has no global platform admin rights.
 * See also the api_is_global_platform_admin() function wich defines who's a "global" admin.
 *
 * @author Julio Montoya
 */
function api_protect_global_admin_script()
{
    if (!api_is_global_platform_admin()) {
        api_not_allowed();

        return false;
    }

    return true;
}

/**
 * Get active template.
 *
 * @param string    theme type (optional: default)
 * @param string    path absolute(abs) or relative(rel) (optional:rel)
 *
 * @return string actived template path
 */
function api_get_template($path_type = 'rel')
{
    $path_types = ['rel', 'abs'];
    $template_path = '';
    if (in_array($path_type, $path_types)) {
        if ($path_type == 'rel') {
            $template_path = api_get_path(SYS_TEMPLATE_PATH);
        } else {
            $template_path = api_get_path(WEB_TEMPLATE_PATH);
        }
    }
    $actived_theme = 'default';
    if (api_get_setting('active_template')) {
        $actived_theme = api_get_setting('active_template');
    }
    $actived_theme_path = $template_path.$actived_theme.DIRECTORY_SEPARATOR;

    return $actived_theme_path;
}

/**
 * Check browser support for specific file types or features
 * This function checks if the user's browser supports a file format or given
 * feature, or returns the current browser and major version when
 * $format=check_browser. Only a limited number of formats and features are
 * checked by this method. Make sure you check its definition first.
 *
 * @param string $format Can be a file format (extension like svg, webm, ...) or a feature (like autocapitalize, ...)
 *
 * @return bool or return text array if $format=check_browser
 *
 * @author Juan Carlos Raña Trabado
 */
function api_browser_support($format = '')
{
    $browser = new Browser();
    $current_browser = $browser->getBrowser();
    $a_versiontemp = explode('.', $browser->getVersion());
    $current_majorver = $a_versiontemp[0];

    static $result;

    if (isset($result[$format])) {
        return $result[$format];
    }

    // Native svg support
    if ($format == 'svg') {
        if (($current_browser == 'Internet Explorer' && $current_majorver >= 9) ||
            ($current_browser == 'Firefox' && $current_majorver > 1) ||
            ($current_browser == 'Safari' && $current_majorver >= 4) ||
            ($current_browser == 'Chrome' && $current_majorver >= 1) ||
            ($current_browser == 'Opera' && $current_majorver >= 9)
        ) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'pdf') {
        // native pdf support
        if (($current_browser == 'Chrome' && $current_majorver >= 6) ||
            ('Firefox' === $current_browser && $current_majorver >= 15)
        ) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'tif' || $format == 'tiff') {
        //native tif support
        if ($current_browser == 'Safari' && $current_majorver >= 5) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'ogg' || $format == 'ogx' || $format == 'ogv' || $format == 'oga') {
        //native ogg, ogv,oga support
        if (($current_browser == 'Firefox' && $current_majorver >= 3) ||
            ($current_browser == 'Chrome' && $current_majorver >= 3) ||
            ($current_browser == 'Opera' && $current_majorver >= 9)) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'mpg' || $format == 'mpeg') {
        //native mpg support
        if (($current_browser == 'Safari' && $current_majorver >= 5)) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'mp4') {
        //native mp4 support (TODO: Android, iPhone)
        if ($current_browser == 'Android' || $current_browser == 'iPhone') {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'mov') {
        //native mov support( TODO:check iPhone)
        if ($current_browser == 'Safari' && $current_majorver >= 5 || $current_browser == 'iPhone') {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'avi') {
        //native avi support
        if ($current_browser == 'Safari' && $current_majorver >= 5) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'wmv') {
        //native wmv support
        if ($current_browser == 'Firefox' && $current_majorver >= 4) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'webm') {
        //native webm support (TODO:check IE9, Chrome9, Android)
        if (($current_browser == 'Firefox' && $current_majorver >= 4) ||
            ($current_browser == 'Opera' && $current_majorver >= 9) ||
            ($current_browser == 'Internet Explorer' && $current_majorver >= 9) ||
            ($current_browser == 'Chrome' && $current_majorver >= 9) ||
            $current_browser == 'Android'
        ) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'wav') {
        //native wav support (only some codecs !)
        if (($current_browser == 'Firefox' && $current_majorver >= 4) ||
            ($current_browser == 'Safari' && $current_majorver >= 5) ||
            ($current_browser == 'Opera' && $current_majorver >= 9) ||
            ($current_browser == 'Internet Explorer' && $current_majorver >= 9) ||
            ($current_browser == 'Chrome' && $current_majorver > 9) ||
            $current_browser == 'Android' ||
            $current_browser == 'iPhone'
        ) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'mid' || $format == 'kar') {
        //native midi support (TODO:check Android)
        if ($current_browser == 'Opera' && $current_majorver >= 9 || $current_browser == 'Android') {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'wma') {
        //native wma support
        if ($current_browser == 'Firefox' && $current_majorver >= 4) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'au') {
        //native au support
        if ($current_browser == 'Safari' && $current_majorver >= 5) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'mp3') {
        //native mp3 support (TODO:check Android, iPhone)
        if (($current_browser == 'Safari' && $current_majorver >= 5) ||
            ($current_browser == 'Chrome' && $current_majorver >= 6) ||
            ($current_browser == 'Internet Explorer' && $current_majorver >= 9) ||
            $current_browser == 'Android' ||
            $current_browser == 'iPhone' ||
            $current_browser == 'Firefox'
        ) {
            $result[$format] = true;

            return true;
        } else {
            $result[$format] = false;

            return false;
        }
    } elseif ($format == 'autocapitalize') {
        // Help avoiding showing the autocapitalize option if the browser doesn't
        // support it: this attribute is against the HTML5 standard
        if ($current_browser == 'Safari' || $current_browser == 'iPhone') {
            return true;
        } else {
            return false;
        }
    } elseif ($format == "check_browser") {
        $array_check_browser = [$current_browser, $current_majorver];

        return $array_check_browser;
    } else {
        $result[$format] = false;

        return false;
    }
}

/**
 * This function checks if exist path and file browscap.ini
 * In order for this to work, your browscap configuration setting in php.ini
 * must point to the correct location of the browscap.ini file on your system
 * http://php.net/manual/en/function.get-browser.php.
 *
 * @return bool
 *
 * @author Juan Carlos Raña Trabado
 */
function api_check_browscap()
{
    $setting = ini_get('browscap');
    if ($setting) {
        $browser = get_browser($_SERVER['HTTP_USER_AGENT'], true);
        if (strpos($setting, 'browscap.ini') && !empty($browser)) {
            return true;
        }
    }

    return false;
}

/**
 * Returns the <script> HTML tag.
 */
function api_get_js($file)
{
    return '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/'.$file.'"></script>'."\n";
}

/**
 * Returns the <script> HTML tag.
 *
 * @return string
 */
function api_get_asset($file)
{
    return '<script src="'.api_get_path(WEB_PUBLIC_PATH).'assets/'.$file.'"></script>'."\n";
}

/**
 * Returns the <script> HTML tag.
 *
 * @param string $file
 * @param string $media
 *
 * @return string
 */
function api_get_css_asset($file, $media = 'screen')
{
    return '<link href="'.api_get_path(WEB_PUBLIC_PATH).'assets/'.$file.'" rel="stylesheet" media="'.$media.'" type="text/css" />'."\n";
}

/**
 * Returns the <link> HTML tag.
 *
 * @param string $file
 * @param string $media
 */
function api_get_css($file, $media = 'screen')
{
    return '<link href="'.$file.'" rel="stylesheet" media="'.$media.'" type="text/css" />'."\n";
}

/**
 * Returns the js header to include the jquery library.
 */
function api_get_jquery_js()
{
    return api_get_asset('jquery/dist/jquery.min.js');
}

/**
 * Returns the jquery path.
 *
 * @return string
 */
function api_get_jquery_web_path()
{
    return api_get_path(WEB_PUBLIC_PATH).'assets/jquery/dist/jquery.min.js';
}

/**
 * @return string
 */
function api_get_jquery_ui_js_web_path()
{
    return api_get_path(WEB_PUBLIC_PATH).'assets/jquery-ui/jquery-ui.min.js';
}

/**
 * @return string
 */
function api_get_jquery_ui_css_web_path()
{
    return api_get_path(WEB_PUBLIC_PATH).'assets/jquery-ui/themes/smoothness/jquery-ui.min.css';
}

/**
 * Returns the jquery-ui library js headers.
 *
 * @param   bool    add the jqgrid library
 *
 * @return string html tags
 */
function api_get_jquery_ui_js($include_jqgrid = false)
{
    $libraries = [];
    if ($include_jqgrid) {
        $libraries[] = 'jqgrid';
    }

    return api_get_jquery_libraries_js($libraries);
}

function api_get_jqgrid_js()
{
    return api_get_jquery_libraries_js(['jqgrid']);
}

/**
 * Returns the jquery library js and css headers.
 *
 * @param   array   list of jquery libraries supported jquery-ui, jqgrid
 * @param   bool    add the jquery library
 *
 * @return string html tags
 */
function api_get_jquery_libraries_js($libraries)
{
    $js = '';
    $js_path = api_get_path(WEB_LIBRARY_PATH).'javascript/';

    //jqgrid js and css
    if (in_array('jqgrid', $libraries)) {
        $languaje = 'en';
        $platform_isocode = strtolower(api_get_language_isocode());

        //languages supported by jqgrid see files in main/inc/lib/javascript/jqgrid/js/i18n
        $jqgrid_langs = [
            'bg', 'bg1251', 'cat', 'cn', 'cs', 'da', 'de', 'el', 'en', 'es', 'fa', 'fi', 'fr', 'gl', 'he', 'hu', 'is', 'it', 'ja', 'nl', 'no', 'pl', 'pt-br', 'pt', 'ro', 'ru', 'sk', 'sr', 'sv', 'tr', 'ua',
        ];

        if (in_array($platform_isocode, $jqgrid_langs)) {
            $languaje = $platform_isocode;
        }
        //$js .= '<link rel="stylesheet" href="'.$js_path.'jqgrid/css/ui.jqgrid.css" type="text/css">';
        $js .= api_get_css($js_path.'jqgrid/css/ui.jqgrid.css');
        $js .= api_get_js('jqgrid/js/i18n/grid.locale-'.$languaje.'.js');
        $js .= api_get_js('jqgrid/js/jquery.jqGrid.min.js');
    }

    //Document multiple upload funcionality
    if (in_array('jquery-upload', $libraries)) {
        $js .= api_get_asset('blueimp-load-image/js/load-image.all.min.js');
        $js .= api_get_asset('blueimp-canvas-to-blob/js/canvas-to-blob.min.js');
        $js .= api_get_asset('jquery-file-upload/js/jquery.iframe-transport.js');
        $js .= api_get_asset('jquery-file-upload/js/jquery.fileupload.js');
        $js .= api_get_asset('jquery-file-upload/js/jquery.fileupload-process.js');
        $js .= api_get_asset('jquery-file-upload/js/jquery.fileupload-image.js');
        $js .= api_get_asset('jquery-file-upload/js/jquery.fileupload-audio.js');
        $js .= api_get_asset('jquery-file-upload/js/jquery.fileupload-video.js');
        $js .= api_get_asset('jquery-file-upload/js/jquery.fileupload-validate.js');

        $js .= api_get_css(api_get_path(WEB_PUBLIC_PATH).'assets/jquery-file-upload/css/jquery.fileupload.css');
        $js .= api_get_css(api_get_path(WEB_PUBLIC_PATH).'assets/jquery-file-upload/css/jquery.fileupload-ui.css');
    }

    // jquery datepicker
    if (in_array('datepicker', $libraries)) {
        $languaje = 'en-GB';
        $platform_isocode = strtolower(api_get_language_isocode());

        // languages supported by jqgrid see files in main/inc/lib/javascript/jqgrid/js/i18n
        $datapicker_langs = [
            'af', 'ar', 'ar-DZ', 'az', 'bg', 'bs', 'ca', 'cs', 'cy-GB', 'da', 'de', 'el', 'en-AU', 'en-GB', 'en-NZ', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fo', 'fr', 'fr-CH', 'gl', 'he', 'hi', 'hr', 'hu', 'hy', 'id', 'is', 'it', 'ja', 'ka', 'kk', 'km', 'ko', 'lb', 'lt', 'lv', 'mk', 'ml', 'ms', 'nl', 'nl-BE', 'no', 'pl', 'pt', 'pt-BR', 'rm', 'ro', 'ru', 'sk', 'sl', 'sq', 'sr', 'sr-SR', 'sv', 'ta', 'th', 'tj', 'tr', 'uk', 'vi', 'zh-CN', 'zh-HK', 'zh-TW',
        ];
        if (in_array($platform_isocode, $datapicker_langs)) {
            $languaje = $platform_isocode;
        }

        $js .= api_get_js('jquery-ui/jquery-ui-i18n.min.js');
        $script = '<script>
        $(function(){
            $.datepicker.setDefaults($.datepicker.regional["'.$languaje.'"]);
            $.datepicker.regional["local"] = $.datepicker.regional["'.$languaje.'"];
        });
        </script>
        ';
        $js .= $script;
    }

    return $js;
}

/**
 * Returns the URL to the course or session, removing the complexity of the URL
 * building piece by piece.
 *
 * This function relies on api_get_course_info()
 *
 * @param string $courseCode The course code - optional (takes it from context if not given)
 * @param int    $sessionId  The session ID  - optional (takes it from context if not given)
 * @param int    $groupId    The group ID - optional (takes it from context if not given)
 *
 * @return string The URL to a course, a session, or empty string if nothing works e.g. https://localhost/courses/ABC/index.php?session_id=3&gidReq=1
 *
 * @author  Julio Montoya <gugli100@gmail.com>
 */
function api_get_course_url($courseCode = null, $sessionId = null, $groupId = null)
{
    $courseDirectory = '';
    $url = '';
    // If courseCode not set, get context or []
    if (empty($courseCode)) {
        $courseInfo = api_get_course_info();
    } else {
        $courseInfo = api_get_course_info($courseCode);
    }

    // If course defined, get directory, otherwise keep empty string
    if (!empty($courseInfo['directory'])) {
        $courseDirectory = $courseInfo['directory'];
    }

    // If sessionId not set, get context or 0
    if (empty($sessionId)) {
        $sessionId = api_get_session_id();
    }

    // If groupId not set, get context or 0
    if (empty($groupId)) {
        $groupId = api_get_group_id();
    }

    // Build the URL
    if (!empty($courseDirectory)) {
        // directory not empty, so we do have a course
        $url = api_get_path(WEB_COURSE_PATH).$courseDirectory.'/index.php?id_session='.$sessionId.'&gidReq='.$groupId;
    } elseif (!empty($sessionId) && api_get_configuration_value('remove_session_url') !== true) {
        // if the course was unset and the session was set, send directly to the session
        $url = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$sessionId;
    }
    // if not valid combination was found, return an empty string
    return $url;
}

/**
 * Check if the current portal has the $_configuration['multiple_access_urls'] parameter on.
 *
 * @return bool true if multi site is enabled
 */
function api_get_multiple_access_url()
{
    global $_configuration;
    if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
        return true;
    }

    return false;
}

/**
 * Just a synonym for api_get_multiple_access_url().
 *
 * @return bool
 */
function api_is_multiple_url_enabled()
{
    return api_get_multiple_access_url();
}

/**
 * Returns a md5 unique id.
 *
 * @todo add more parameters
 */
function api_get_unique_id()
{
    $id = md5(time().uniqid().api_get_user_id().api_get_course_id().api_get_session_id());

    return $id;
}

/**
 * Get home path.
 *
 * @return string
 */
function api_get_home_path()
{
    // FIX : Start the routing determination from central path definition
    $home = api_get_path(SYS_HOME_PATH);
    if (api_get_multiple_access_url()) {
        $access_url_id = api_get_current_access_url_id();
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
        $clean_url = api_replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';
        if ($clean_url != 'localhost/') {
            // means that the multiple URL was not well configured we don't rename the $home variable
            return "{$home}{$clean_url}";
        }
    }

    return $home;
}

/**
 * @param int Course id
 * @param int tool id: TOOL_QUIZ, TOOL_FORUM, TOOL_STUDENTPUBLICATION, TOOL_LEARNPATH
 * @param int the item id (tool id, exercise id, lp id)
 *
 * @return bool
 */
function api_resource_is_locked_by_gradebook($item_id, $link_type, $course_code = null)
{
    if (api_is_platform_admin()) {
        return false;
    }
    if (api_get_setting('gradebook_locking_enabled') == 'true') {
        if (empty($course_code)) {
            $course_code = api_get_course_id();
        }
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
        $item_id = intval($item_id);
        $link_type = intval($link_type);
        $course_code = Database::escape_string($course_code);
        $sql = "SELECT locked FROM $table
                WHERE locked = 1 AND ref_id = $item_id AND type = $link_type AND course_code = '$course_code' ";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return true;
        }
    }

    return false;
}

/**
 * Blocks a page if the item was added in a gradebook.
 *
 * @param int       exercise id, work id, thread id,
 * @param int       LINK_EXERCISE, LINK_STUDENTPUBLICATION, LINK_LEARNPATH LINK_FORUM_THREAD, LINK_ATTENDANCE
 * see gradebook/lib/be/linkfactory
 * @param string    course code
 *
 * @return false|null
 */
function api_block_course_item_locked_by_gradebook($item_id, $link_type, $course_code = null)
{
    if (api_is_platform_admin()) {
        return false;
    }

    if (api_resource_is_locked_by_gradebook($item_id, $link_type, $course_code)) {
        $message = Display::return_message(get_lang('ResourceLockedByGradebook'), 'warning');
        api_not_allowed(true, $message);
    }
}

/**
 * Checks the PHP version installed is enough to run Chamilo.
 *
 * @param string Include path (used to load the error page)
 */
function api_check_php_version($my_inc_path = null)
{
    if (!function_exists('version_compare') || version_compare(phpversion(), REQUIRED_PHP_VERSION, '<')) {
        $global_error_code = 1;
        // Incorrect PHP version
        $global_page = $my_inc_path.'global_error_message.inc.php';
        if (file_exists($global_page)) {
            require $global_page;
        }
        exit;
    }
}

/**
 * Checks whether the Archive directory is present and writeable. If not,
 * prints a warning message.
 */
function api_check_archive_dir()
{
    if (is_dir(api_get_path(SYS_ARCHIVE_PATH)) && !is_writable(api_get_path(SYS_ARCHIVE_PATH))) {
        $message = Display::return_message(get_lang('ArchivesDirectoryNotWriteableContactAdmin'), 'warning');
        api_not_allowed(true, $message);
    }
}

/**
 * Returns an array of global configuration settings which should be ignored
 * when printing the configuration settings screens.
 *
 * @return array Array of strings, each identifying one of the excluded settings
 */
function api_get_locked_settings()
{
    return [
        'server_type',
        'permanently_remove_deleted_files',
        'account_valid_duration',
        'service_ppt2lp',
        'wcag_anysurfer_public_pages',
        'upload_extensions_list_type',
        'upload_extensions_blacklist',
        'upload_extensions_whitelist',
        'upload_extensions_skip',
        'upload_extensions_replace_by',
        'hide_dltt_markup',
        'split_users_upload_directory',
        'permissions_for_new_directories',
        'permissions_for_new_files',
        'platform_charset',
        'ldap_description',
        'cas_activate',
        'cas_server',
        'cas_server_uri',
        'cas_port',
        'cas_protocol',
        'cas_add_user_activate',
        'update_user_info_cas_with_ldap',
        'languagePriority1',
        'languagePriority2',
        'languagePriority3',
        'languagePriority4',
        'login_is_email',
        'chamilo_database_version',
    ];
}

/**
 * Checks if the user is corrently logged in. Returns the user ID if he is, or
 * false if he isn't. If the user ID is given and is an integer, then the same
 * ID is simply returned.
 *
 * @param  int User ID
 *
 * @return bool Integer User ID is logged in, or false otherwise
 */
function api_user_is_login($user_id = null)
{
    $user_id = empty($user_id) ? api_get_user_id() : (int) $user_id;

    return $user_id && !api_is_anonymous();
}

/**
 * Guess the real ip for register in the database, even in reverse proxy cases.
 * To be recognized, the IP has to be found in either $_SERVER['REMOTE_ADDR'] or
 * in $_SERVER['HTTP_X_FORWARDED_FOR'], which is in common use with rproxies.
 * Note: the result of this function is not SQL-safe. Please escape it before
 * inserting in a database.
 *
 * @return string the user's real ip (unsafe - escape it before inserting to db)
 *
 * @author Jorge Frisancho Jibaja <jrfdeft@gmail.com>, USIL - Some changes to allow the use of real IP using reverse proxy
 *
 * @version CEV CHANGE 24APR2012
 */
function api_get_real_ip()
{
    $ip = trim($_SERVER['REMOTE_ADDR']);
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        if (preg_match('/,/', $_SERVER['HTTP_X_FORWARDED_FOR'])) {
            @list($ip1, $ip2) = @explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            $ip1 = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $ip = trim($ip1);
    }

    return $ip;
}

/**
 * Checks whether an IP is included inside an IP range.
 *
 * @param string IP address
 * @param string IP range
 * @param string $ip
 *
 * @return bool True if IP is in the range, false otherwise
 *
 * @author claudiu at cnixs dot com  on http://www.php.net/manual/fr/ref.network.php#55230
 * @author Yannick Warnier for improvements and managment of multiple ranges
 *
 * @todo check for IPv6 support
 */
function api_check_ip_in_range($ip, $range)
{
    if (empty($ip) or empty($range)) {
        return false;
    }
    $ip_ip = ip2long($ip);
    // divide range param into array of elements
    if (strpos($range, ',') !== false) {
        $ranges = explode(',', $range);
    } else {
        $ranges = [$range];
    }
    foreach ($ranges as $range) {
        $range = trim($range);
        if (empty($range)) {
            continue;
        }
        if (strpos($range, '/') === false) {
            if (strcmp($ip, $range) === 0) {
                return true; // there is a direct IP match, return OK
            }
            continue; //otherwise, get to the next range
        }
        // the range contains a "/", so analyse completely
        list($net, $mask) = explode("/", $range);

        $ip_net = ip2long($net);
        // mask binary magic
        $ip_mask = ~((1 << (32 - $mask)) - 1);

        $ip_ip_net = $ip_ip & $ip_mask;
        if ($ip_ip_net == $ip_net) {
            return true;
        }
    }

    return false;
}

function api_check_user_access_to_legal($courseInfo)
{
    if (empty($courseInfo)) {
        return false;
    }

    $visibility = (int) $courseInfo['visibility'];
    $visibilityList = [COURSE_VISIBILITY_OPEN_WORLD, COURSE_VISIBILITY_OPEN_PLATFORM];

    return
        in_array($visibility, $visibilityList) ||
        api_is_drh() ||
        (COURSE_VISIBILITY_REGISTERED === $visibility && 1 === (int) $courseInfo['subscribe']);
}

/**
 * Checks if the global chat is enabled or not.
 *
 * @return bool
 */
function api_is_global_chat_enabled()
{
    return
        !api_is_anonymous() &&
        api_get_setting('allow_global_chat') === 'true' &&
        api_get_setting('allow_social_tool') === 'true';
}

/**
 * @todo Fix tool_visible_by_default_at_creation labels
 *
 * @param int   $item_id
 * @param int   $tool_id
 * @param int   $group_id   id
 * @param array $courseInfo
 * @param int   $sessionId
 * @param int   $userId
 */
function api_set_default_visibility(
    $item_id,
    $tool_id,
    $group_id = 0,
    $courseInfo = [],
    $sessionId = 0,
    $userId = 0
) {
    $courseInfo = empty($courseInfo) ? api_get_course_info() : $courseInfo;
    $courseId = $courseInfo['real_id'];
    $courseCode = $courseInfo['code'];
    $sessionId = empty($sessionId) ? api_get_session_id() : $sessionId;
    $userId = empty($userId) ? api_get_user_id() : $userId;

    // if group is null force group_id = 0, this force is needed to create a LP folder with group = 0
    if (is_null($group_id)) {
        $group_id = 0;
    } else {
        $group_id = empty($group_id) ? api_get_group_id() : $group_id;
    }

    $groupInfo = [];
    if (!empty($group_id)) {
        $groupInfo = GroupManager::get_group_properties($group_id);
    }
    $original_tool_id = $tool_id;

    switch ($tool_id) {
        case TOOL_LINK:
        case TOOL_LINK_CATEGORY:
            $tool_id = 'links';
            break;
        case TOOL_DOCUMENT:
            $tool_id = 'documents';
            break;
        case TOOL_LEARNPATH:
            $tool_id = 'learning';
            break;
        case TOOL_ANNOUNCEMENT:
            $tool_id = 'announcements';
            break;
        case TOOL_FORUM:
        case TOOL_FORUM_CATEGORY:
        case TOOL_FORUM_THREAD:
            $tool_id = 'forums';
            break;
        case TOOL_QUIZ:
            $tool_id = 'quiz';
            break;
    }
    $setting = api_get_setting('tool_visible_by_default_at_creation');

    if (isset($setting[$tool_id])) {
        $visibility = 'invisible';
        if ($setting[$tool_id] == 'true') {
            $visibility = 'visible';
        }

        // Read the portal and course default visibility
        if ($tool_id === 'documents') {
            $visibility = DocumentManager::getDocumentDefaultVisibility($courseInfo);
        }

        api_item_property_update(
            $courseInfo,
            $original_tool_id,
            $item_id,
            $visibility,
            $userId,
            $groupInfo,
            null,
            null,
            null,
            $sessionId
        );

        // Fixes default visibility for tests
        switch ($original_tool_id) {
            case TOOL_QUIZ:
                if (empty($sessionId)) {
                    $objExerciseTmp = new Exercise($courseId);
                    $objExerciseTmp->read($item_id);
                    if ($visibility == 'visible') {
                        $objExerciseTmp->enable();
                        $objExerciseTmp->save();
                    } else {
                        $objExerciseTmp->disable();
                        $objExerciseTmp->save();
                    }
                }
                break;
        }
    }
}

/**
 * @return string
 */
function api_get_security_key()
{
    return api_get_configuration_value('security_key');
}

/**
 * @param int $user_id
 * @param int $courseId
 * @param int $session_id
 *
 * @return array
 */
function api_detect_user_roles($user_id, $courseId, $session_id = 0)
{
    $user_roles = [];
    $courseInfo = api_get_course_info_by_id($courseId);
    $course_code = $courseInfo['code'];

    $url_id = api_get_current_access_url_id();
    if (api_is_platform_admin_by_id($user_id, $url_id)) {
        $user_roles[] = PLATFORM_ADMIN;
    }

    /*if (api_is_drh()) {
        $user_roles[] = DRH;
    }*/

    if (!empty($session_id)) {
        if (SessionManager::user_is_general_coach($user_id, $session_id)) {
            $user_roles[] = SESSION_GENERAL_COACH;
        }
    }

    if (!empty($course_code)) {
        if (empty($session_id)) {
            if (CourseManager::is_course_teacher($user_id, $course_code)) {
                $user_roles[] = COURSEMANAGER;
            }
            if (CourseManager::get_tutor_in_course_status($user_id, $courseInfo['real_id'])) {
                $user_roles[] = COURSE_TUTOR;
            }

            if (CourseManager::is_user_subscribed_in_course($user_id, $course_code)) {
                $user_roles[] = COURSE_STUDENT;
            }
        } else {
            $user_status_in_session = SessionManager::get_user_status_in_course_session(
                $user_id,
                $courseId,
                $session_id
            );

            if (!empty($user_status_in_session)) {
                if ($user_status_in_session == 0) {
                    $user_roles[] = SESSION_STUDENT;
                }
                if ($user_status_in_session == 2) {
                    $user_roles[] = SESSION_COURSE_COACH;
                }
            }

            /*if (api_is_course_session_coach($user_id, $course_code, $session_id)) {
               $user_roles[] = SESSION_COURSE_COACH;
            }*/
        }
    }

    return $user_roles;
}

/**
 * @param int $courseId
 * @param int $session_id
 *
 * @return bool
 */
function api_coach_can_edit_view_results($courseId = null, $session_id = null)
{
    if (api_is_platform_admin()) {
        return true;
    }

    $user_id = api_get_user_id();

    if (empty($courseId)) {
        $courseId = api_get_course_int_id();
    }

    if (empty($session_id)) {
        $session_id = api_get_session_id();
    }

    $roles = api_detect_user_roles($user_id, $courseId, $session_id);

    if (in_array(SESSION_COURSE_COACH, $roles)) {
        //return api_get_setting('session_tutor_reports_visibility') == 'true';
        return true;
    } else {
        if (in_array(COURSEMANAGER, $roles)) {
            return true;
        }

        return false;
    }
}

/**
 * @param string $file
 *
 * @return string
 */
function api_get_js_simple($file)
{
    return '<script src="'.$file.'"></script>'."\n";
}

function api_set_settings_and_plugins()
{
    global $_configuration;
    $_setting = [];
    $_plugins = [];

    // access_url == 1 is the default chamilo location
    $settings_by_access_list = [];
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != 1) {
        $url_info = api_get_access_url($_configuration['access_url']);
        if ($url_info['active'] == 1) {
            $settings_by_access = api_get_settings(null, 'list', $_configuration['access_url'], 1);
            foreach ($settings_by_access as &$row) {
                if (empty($row['variable'])) {
                    $row['variable'] = 0;
                }
                if (empty($row['subkey'])) {
                    $row['subkey'] = 0;
                }
                if (empty($row['category'])) {
                    $row['category'] = 0;
                }
                $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']] = $row;
            }
        }
    }

    $result = api_get_settings(null, 'list', 1);

    foreach ($result as &$row) {
        if ($access_url_id != 1) {
            if ($url_info['active'] == 1) {
                $var = empty($row['variable']) ? 0 : $row['variable'];
                $subkey = empty($row['subkey']) ? 0 : $row['subkey'];
                $category = empty($row['category']) ? 0 : $row['category'];
            }

            if ($row['access_url_changeable'] == 1 && $url_info['active'] == 1) {
                if (isset($settings_by_access_list[$var]) &&
                    $settings_by_access_list[$var][$subkey][$category]['selected_value'] != '') {
                    if ($row['subkey'] == null) {
                        $_setting[$row['variable']] = $settings_by_access_list[$var][$subkey][$category]['selected_value'];
                    } else {
                        $_setting[$row['variable']][$row['subkey']] = $settings_by_access_list[$var][$subkey][$category]['selected_value'];
                    }
                } else {
                    if ($row['subkey'] == null) {
                        $_setting[$row['variable']] = $row['selected_value'];
                    } else {
                        $_setting[$row['variable']][$row['subkey']] = $row['selected_value'];
                    }
                }
            } else {
                if ($row['subkey'] == null) {
                    $_setting[$row['variable']] = $row['selected_value'];
                } else {
                    $_setting[$row['variable']][$row['subkey']] = $row['selected_value'];
                }
            }
        } else {
            if ($row['subkey'] == null) {
                $_setting[$row['variable']] = $row['selected_value'];
            } else {
                $_setting[$row['variable']][$row['subkey']] = $row['selected_value'];
            }
        }
    }

    $result = api_get_settings('Plugins', 'list', $access_url_id);
    $_plugins = [];
    foreach ($result as &$row) {
        $key = &$row['variable'];
        if (is_string($_setting[$key])) {
            $_setting[$key] = [];
        }
        $_setting[$key][] = $row['selected_value'];
        $_plugins[$key][] = $row['selected_value'];
    }

    $_SESSION['_setting'] = $_setting;
    $_SESSION['_plugins'] = $_plugins;
}

/**
 * Modify default memory_limit and max_execution_time limits
 * Needed when processing long tasks.
 */
function api_set_more_memory_and_time_limits()
{
    if (function_exists('ini_set')) {
        api_set_memory_limit('2048M');
        ini_set('max_execution_time', 3600);
    }
}

/**
 * Tries to set memory limit, if authorized and new limit is higher than current.
 *
 * @param string $mem New memory limit
 *
 * @return bool True on success, false on failure or current is higher than suggested
 * @assert (null) === false
 * @assert (-1) === false
 * @assert (0) === true
 * @assert ('1G') === true
 */
function api_set_memory_limit($mem)
{
    //if ini_set() not available, this function is useless
    if (!function_exists('ini_set') || is_null($mem) || $mem == -1) {
        return false;
    }

    $memory_limit = ini_get('memory_limit');
    if (api_get_bytes_memory_limit($mem) > api_get_bytes_memory_limit($memory_limit)) {
        ini_set('memory_limit', $mem);

        return true;
    }

    return false;
}

/**
 * Gets memory limit in bytes.
 *
 * @param string The memory size (128M, 1G, 1000K, etc)
 *
 * @return int
 * @assert (null) === false
 * @assert ('1t')  === 1099511627776
 * @assert ('1g')  === 1073741824
 * @assert ('1m')  === 1048576
 * @assert ('100k') === 102400
 */
function api_get_bytes_memory_limit($mem)
{
    $size = strtolower(substr($mem, -1));

    switch ($size) {
        case 't':
            $mem = intval(substr($mem, -1)) * 1024 * 1024 * 1024 * 1024;
            break;
        case 'g':
            $mem = intval(substr($mem, 0, -1)) * 1024 * 1024 * 1024;
            break;
        case 'm':
            $mem = intval(substr($mem, 0, -1)) * 1024 * 1024;
            break;
        case 'k':
            $mem = intval(substr($mem, 0, -1)) * 1024;
            break;
        default:
            // we assume it's integer only
            $mem = intval($mem);
            break;
    }

    return $mem;
}

/**
 * Finds all the information about a user from username instead of user id.
 *
 * @param string $officialCode
 *
 * @return array $user_info user_id, lastname, firstname, username, email, ...
 *
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
function api_get_user_info_from_official_code($officialCode)
{
    if (empty($officialCode)) {
        return false;
    }
    $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
            WHERE official_code ='".Database::escape_string($officialCode)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $result_array = Database::fetch_array($result);

        return _api_format_user($result_array);
    }

    return false;
}

/**
 * @param string $usernameInputId
 * @param string $passwordInputId
 *
 * @return string|null
 */
function api_get_password_checker_js($usernameInputId, $passwordInputId)
{
    $checkPass = api_get_setting('allow_strength_pass_checker');
    $useStrengthPassChecker = $checkPass === 'true';

    if ($useStrengthPassChecker === false) {
        return null;
    }

    $minRequirements = Security::getPasswordRequirements()['min'];

    $options = [
        'rules' => [],
    ];

    if ($minRequirements['length'] > 0) {
        $options['rules'][] = [
            'minChar' => $minRequirements['length'],
            'pattern' => '.',
            'helpText' => sprintf(
                get_lang('NewPasswordRequirementMinXLength'),
                $minRequirements['length']
            ),
        ];
    }

    if ($minRequirements['lowercase'] > 0) {
        $options['rules'][] = [
            'minChar' => $minRequirements['lowercase'],
            'pattern' => '[a-z]',
            'helpText' => sprintf(
                get_lang('NewPasswordRequirementMinXLowercase'),
                $minRequirements['lowercase']
            ),
        ];
    }

    if ($minRequirements['uppercase'] > 0) {
        $options['rules'][] = [
            'minChar' => $minRequirements['uppercase'],
            'pattern' => '[A-Z]',
            'helpText' => sprintf(
                get_lang('NewPasswordRequirementMinXUppercase'),
                $minRequirements['uppercase']
            ),
        ];
    }

    if ($minRequirements['numeric'] > 0) {
        $options['rules'][] = [
            'minChar' => $minRequirements['numeric'],
            'pattern' => '[0-9]',
            'helpText' => sprintf(
                get_lang('NewPasswordRequirementMinXNumeric'),
                $minRequirements['numeric']
            ),
        ];
    }

    if ($minRequirements['specials'] > 0) {
        $options['rules'][] = [
            'minChar' => $minRequirements['specials'],
            'pattern' => '[!"#$%&\'()*+,\-./\\\:;<=>?@[\\]^_`{|}~]',
            'helpText' => sprintf(
                get_lang('NewPasswordRequirementMinXSpecials'),
                $minRequirements['specials']
            ),
        ];
    }

    $js = api_get_js('password-checker/password-checker.js');
    $js .= "<script>
    $(function() {
        $('".$passwordInputId."').passwordChecker(".json_encode($options).");
    });
    </script>";

    return $js;
}

/**
 * create an user extra field called 'captcha_blocked_until_date'.
 *
 * @param string $username
 *
 * @return bool
 */
function api_block_account_captcha($username)
{
    $userInfo = api_get_user_info_from_username($username);
    if (empty($userInfo)) {
        return false;
    }
    $minutesToBlock = api_get_setting('captcha_time_to_block');
    $time = time() + $minutesToBlock * 60;
    UserManager::update_extra_field_value(
        $userInfo['user_id'],
        'captcha_blocked_until_date',
        api_get_utc_datetime($time)
    );

    return true;
}

/**
 * @param string $username
 *
 * @return bool
 */
function api_clean_account_captcha($username)
{
    $userInfo = api_get_user_info_from_username($username);
    if (empty($userInfo)) {
        return false;
    }
    Session::erase('loginFailedCount');
    UserManager::update_extra_field_value(
        $userInfo['user_id'],
        'captcha_blocked_until_date',
        null
    );

    return true;
}

/**
 * @param string $username
 *
 * @return bool
 */
function api_get_user_blocked_by_captcha($username)
{
    $userInfo = api_get_user_info_from_username($username);
    if (empty($userInfo)) {
        return false;
    }
    $data = UserManager::get_extra_user_data_by_field(
        $userInfo['user_id'],
        'captcha_blocked_until_date'
    );
    if (isset($data) && isset($data['captcha_blocked_until_date'])) {
        return $data['captcha_blocked_until_date'];
    }

    return false;
}

/**
 * Remove tags from HTML anf return the $in_number_char first non-HTML char
 * Postfix the text with "..." if it has been truncated.
 *
 * @param string $text
 * @param int    $number
 *
 * @return string
 *
 * @author hubert borderiou
 */
function api_get_short_text_from_html($text, $number)
{
    // Delete script and style tags
    $text = preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $text);
    $text = api_html_entity_decode($text);
    $out_res = api_remove_tags_with_space($text, false);
    $postfix = "...";
    if (strlen($out_res) > $number) {
        $out_res = substr($out_res, 0, $number).$postfix;
    }

    return $out_res;
}

/**
 * Replace tags with a space in a text.
 * If $in_double_quote_replace, replace " with '' (for HTML attribute purpose, for exemple).
 *
 * @return string
 *
 * @author hubert borderiou
 */
function api_remove_tags_with_space($in_html, $in_double_quote_replace = true)
{
    $out_res = $in_html;
    if ($in_double_quote_replace) {
        $out_res = str_replace('"', "''", $out_res);
    }
    // avoid text stuck together when tags are removed, adding a space after >
    $out_res = str_replace(">", "> ", $out_res);
    $out_res = strip_tags($out_res);

    return $out_res;
}

/**
 * If true, the drh can access all content (courses, users) inside a session.
 *
 * @return bool
 */
function api_drh_can_access_all_session_content()
{
    return api_get_setting('drh_can_access_all_session_content') === 'true';
}

/**
 * @param string $tool
 * @param string $setting
 * @param int    $defaultValue
 *
 * @return string
 */
function api_get_default_tool_setting($tool, $setting, $defaultValue)
{
    global $_configuration;
    if (isset($_configuration[$tool]) &&
        isset($_configuration[$tool]['default_settings']) &&
        isset($_configuration[$tool]['default_settings'][$setting])
    ) {
        return $_configuration[$tool]['default_settings'][$setting];
    }

    return $defaultValue;
}

/**
 * Checks if user can login as another user.
 *
 * @param int $loginAsUserId the user id to log in
 * @param int $userId        my user id
 *
 * @return bool
 */
function api_can_login_as($loginAsUserId, $userId = null)
{
    if (empty($userId)) {
        $userId = api_get_user_id();
    }
    if ($loginAsUserId == $userId) {
        return false;
    }

    if (empty($loginAsUserId)) {
        return false;
    }

    if ($loginAsUserId != strval(intval($loginAsUserId))) {
        return false;
    }

    // Check if the user to login is an admin
    if (api_is_platform_admin_by_id($loginAsUserId)) {
        // Only super admins can login to admin accounts
        if (!api_global_admin_can_edit_admin($loginAsUserId)) {
            return false;
        }
    }

    $userInfo = api_get_user_info($loginAsUserId);
    $isDrh = function () use ($loginAsUserId) {
        if (api_is_drh()) {
            if (api_drh_can_access_all_session_content()) {
                $users = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                    'drh_all',
                    api_get_user_id()
                );
                $userList = [];
                if (is_array($users)) {
                    foreach ($users as $user) {
                        $userList[] = $user['user_id'];
                    }
                }
                if (in_array($loginAsUserId, $userList)) {
                    return true;
                }
            } else {
                if (api_is_drh() &&
                    UserManager::is_user_followed_by_drh($loginAsUserId, api_get_user_id())
                ) {
                    return true;
                }
            }
        }

        return false;
    };

    $loginAsStatusForSessionAdmins = [STUDENT];

    if (api_get_configuration_value('allow_session_admin_login_as_teacher')) {
        $loginAsStatusForSessionAdmins[] = COURSEMANAGER;
    }

    return api_is_platform_admin() ||
        (api_is_session_admin() && in_array($userInfo['status'], $loginAsStatusForSessionAdmins)) ||
        $isDrh();
}

/**
 * @return bool
 */
function api_is_allowed_in_course()
{
    if (api_is_platform_admin()) {
        return true;
    }

    return Session::read('is_allowed_in_course');
}

function api_set_cookie($name, $value, $expires = 0)
{
    $expires = (int) $expires;
    setcookie($name, $value, $expires, '', '', api_is_https(), true);
}

/**
 * Set the cookie to go directly to the course code $in_firstpage
 * after login.
 *
 * @param string $value is the course code of the course to go
 */
function api_set_firstpage_parameter($value)
{
    api_set_cookie('GotoCourse', $value);
}

/**
 * Delete the cookie to go directly to the course code $in_firstpage
 * after login.
 */
function api_delete_firstpage_parameter()
{
    api_set_cookie('GotoCourse', '', time() - 3600);
}

/**
 * @return bool if course_code for direct course access after login is set
 */
function exist_firstpage_parameter()
{
    return isset($_COOKIE['GotoCourse']) && $_COOKIE['GotoCourse'] != '';
}

/**
 * @return string return the course_code of the course where user login
 */
function api_get_firstpage_parameter()
{
    return $_COOKIE['GotoCourse'];
}

/**
 * Return true on https install.
 *
 * @return bool
 */
function api_is_https()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_configuration['force_https_forwarded_proto'])
    ) {
        $isSecured = true;
    } else {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $isSecured = true;
        } else {
            $isSecured = false;
            // last chance
            if (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) {
                $isSecured = true;
            }
        }
    }

    return $isSecured;
}

/**
 * Return protocol (http or https).
 *
 * @return string
 */
function api_get_protocol()
{
    return api_is_https() ? 'https' : 'http';
}

/**
 * Return a string where " are replaced with 2 '
 * It is useful when you pass a PHP variable in a Javascript browser dialog
 * e.g. : alert("<?php get_lang('Message') ?>");
 * and message contains character ".
 *
 * @param string $in_text
 *
 * @return string
 */
function convert_double_quote_to_single($in_text)
{
    return api_preg_replace('/"/', "''", $in_text);
}

/**
 * Get origin.
 *
 * @param string
 *
 * @return string
 */
function api_get_origin()
{
    return isset($_REQUEST['origin']) ? urlencode(Security::remove_XSS(urlencode($_REQUEST['origin']))) : '';
}

/**
 * Warns an user that the portal reach certain limit.
 *
 * @param string $limitName
 */
function api_warn_hosting_contact($limitName)
{
    $hostingParams = api_get_configuration_value(1);
    $email = null;

    if (!empty($hostingParams)) {
        if (isset($hostingParams['hosting_contact_mail'])) {
            $email = $hostingParams['hosting_contact_mail'];
        }
    }

    if (!empty($email)) {
        $subject = get_lang('HostingWarningReached');
        $body = get_lang('PortalName').': '.api_get_path(WEB_PATH)." \n ";
        $body .= get_lang('PortalLimitType').': '.$limitName." \n ";
        if (isset($hostingParams[$limitName])) {
            $body .= get_lang('Value').': '.$hostingParams[$limitName];
        }
        api_mail_html(null, $email, $subject, $body);
    }
}

/**
 * Gets value of a variable from app/config/configuration.php
 * Variables that are not set in the configuration.php file but set elsewhere:
 * - virtual_css_theme_folder (vchamilo plugin)
 * - access_url (global.inc.php)
 * - apc/apc_prefix (global.inc.php).
 *
 * @param string $variable
 *
 * @return bool|mixed
 */
function api_get_configuration_value($variable)
{
    global $_configuration;
    // Check the current url id, id = 1 by default
    $urlId = isset($_configuration['access_url']) ? (int) $_configuration['access_url'] : 1;

    $variable = trim($variable);

    // Check if variable exists
    if (isset($_configuration[$variable])) {
        if (is_array($_configuration[$variable]) && api_is_multiple_url_enabled() && isset($_configuration[$variable][0]) && is_int(array_keys($_configuration[$variable])[0])) {
            // It has been configured for at least one sub URL, so we will not return the complete variable
            /*
             * The idea is that if the first level key of the configuration variable is an int
             * then it is a multiURL configuration and if it's a string then it's a configuration that is not multiURL.
             * For example if in app/config/configuration.php you have set :
             * $_configuration['ticket_project_user_roles'] = [
             *     'permissions' => [
             *         1 => [17] // project_id = 1, STUDENT_BOSS = 17
             *     ]
             * ];
             * You do not want to enter this block even if multiURL is activated because the option is configured globally
             * and you want to return the full array.
             * The is_int is to consider only the option that are array and configured for multiURL
             * which means there is an int as the first level key of the array.
             */
            // Check if it exists for the sub portal
            if (array_key_exists($urlId, $_configuration[$variable])) {
                return $_configuration[$variable][$urlId];
            } else {
                // Try to found element with id = 1 (master portal)
                if (array_key_exists(1, $_configuration[$variable])) {
                    return $_configuration[$variable][1];
                } else {
                    // The value was there for other URLs but not the main URL nor the current URL
                    return null;
                }
            }
        }

        return $_configuration[$variable];
    }

    return false;
}

/**
 * Gets value of a variable from app/config/mail.conf.php.
 *
 * @param string $variable
 *
 * @return bool|mixed
 */
function api_get_mail_configuration_value($variable)
{
    global $_configuration;
    global $platform_email;

    // Check the current url id, id = 1 by default
    $urlId = isset($_configuration['access_url']) ? (int) $_configuration['access_url'] : 1;

    $variable = trim($variable);

    // Check if variable exists for the sub portal
    if (api_is_multiple_url_enabled() && isset($platform_email[$urlId]) && isset($platform_email[$urlId][$variable])) {
        return $platform_email[$urlId][$variable];
    } elseif (isset($platform_email[1]) && isset($platform_email[1][$variable])) {
        // Try to find element with id = 1 (master portal)
        return $platform_email[1][$variable];
    } elseif (isset($platform_email[$variable])) {
        // If variable is not found for the sub portal or master portal, try to find the default element
        return $platform_email[$variable];
    }

    return false;
}

/**
 * Retreives and returns a value in a hierarchical configuration array
 * api_get_configuration_sub_value('a/b/c') returns api_get_configuration_value('a')['b']['c'].
 *
 * @param string $path      the successive array keys, separated by the separator
 * @param mixed  $default   value to be returned if not found, null by default
 * @param string $separator '/' by default
 * @param array  $array     the active configuration array by default
 *
 * @return mixed the found value or $default
 */
function api_get_configuration_sub_value($path, $default = null, $separator = '/', $array = null)
{
    $pos = strpos($path, $separator);
    if (false === $pos) {
        if (is_null($array)) {
            return api_get_configuration_value($path);
        }
        if (is_array($array) && array_key_exists($path, $array)) {
            return $array[$path];
        }

        return $default;
    }
    $key = substr($path, 0, $pos);
    if (is_null($array)) {
        $newArray = api_get_configuration_value($key);
    } elseif (is_array($array) && array_key_exists($key, $array)) {
        $newArray = $array[$key];
    } else {
        return $default;
    }
    if (is_array($newArray)) {
        $newPath = substr($path, $pos + 1);

        return api_get_configuration_sub_value($newPath, $default, $separator, $newArray);
    }

    return $default;
}

/**
 * Retrieves and returns a value in a hierarchical configuration array
 * api_array_sub_value($array, 'a/b/c') returns $array['a']['b']['c'].
 *
 * @param array  $array     the recursive array that contains the value to be returned (or not)
 * @param string $path      the successive array keys, separated by the separator
 * @param mixed  $default   the value to be returned if not found
 * @param string $separator the separator substring
 *
 * @return mixed the found value or $default
 */
function api_array_sub_value($array, $path, $default = null, $separator = '/')
{
    $pos = strpos($path, $separator);
    if (false === $pos) {
        if (is_array($array) && array_key_exists($path, $array)) {
            return $array[$path];
        }

        return $default;
    }
    $key = substr($path, 0, $pos);
    if (is_array($array) && array_key_exists($key, $array)) {
        $newArray = $array[$key];
    } else {
        return $default;
    }
    if (is_array($newArray)) {
        $newPath = substr($path, $pos + 1);

        return api_array_sub_value($newArray, $newPath, $default);
    }

    return $default;
}

/**
 * Returns supported image extensions in the portal.
 *
 * @param bool $supportVectors Whether vector images should also be accepted or not
 *
 * @return array Supported image extensions in the portal
 */
function api_get_supported_image_extensions($supportVectors = true)
{
    // jpg can also be called jpeg, jpe, jfif and jif. See https://en.wikipedia.org/wiki/JPEG#JPEG_filename_extensions
    $supportedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'jpe', 'jfif', 'jif'];
    if ($supportVectors) {
        array_push($supportedImageExtensions, 'svg');
    }
    if (version_compare(PHP_VERSION, '5.5.0', '>=')) {
        array_push($supportedImageExtensions, 'webp');
    }

    return $supportedImageExtensions;
}

/**
 * This setting changes the registration status for the campus.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version August 2006
 *
 * @param bool $listCampus Whether we authorize
 *
 * @todo the $_settings should be reloaded here. => write api function for this and use this in global.inc.php also.
 */
function api_register_campus($listCampus = true)
{
    $tbl_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    $sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='registered'";
    Database::query($sql);

    if (!$listCampus) {
        $sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='donotlistcampus'";
        Database::query($sql);
    }
}

/**
 * Checks whether current user is a student boss.
 *
 * @global array $_user
 *
 * @return bool
 */
function api_is_student_boss()
{
    $_user = api_get_user_info();

    return isset($_user['status']) && $_user['status'] == STUDENT_BOSS;
}

/**
 * Check whether the user type should be exclude.
 * Such as invited or anonymous users.
 *
 * @param bool $checkDB Optional. Whether check the user status
 * @param int  $userId  Options. The user id
 *
 * @return bool
 */
function api_is_excluded_user_type($checkDB = false, $userId = 0)
{
    if ($checkDB) {
        $userId = empty($userId) ? api_get_user_id() : (int) $userId;

        if ($userId == 0) {
            return true;
        }

        $userInfo = api_get_user_info($userId);

        switch ($userInfo['status']) {
            case INVITEE:
            case ANONYMOUS:
                return true;
            default:
                return false;
        }
    }

    $isInvited = api_is_invitee();
    $isAnonymous = api_is_anonymous();

    if ($isInvited || $isAnonymous) {
        return true;
    }

    return false;
}

/**
 * Get the user status to ignore in reports.
 *
 * @param string $format Optional. The result type (array or string)
 *
 * @return array|string
 */
function api_get_users_status_ignored_in_reports($format = 'array')
{
    $excludedTypes = [
        INVITEE,
        ANONYMOUS,
    ];

    if ($format == 'string') {
        return implode(', ', $excludedTypes);
    }

    return $excludedTypes;
}

/**
 * Set the Site Use Cookie Warning for 1 year.
 */
function api_set_site_use_cookie_warning_cookie()
{
    api_set_cookie('ChamiloUsesCookies', 'ok', time() + 31556926);
}

/**
 * Return true if the Site Use Cookie Warning Cookie warning exists.
 *
 * @return bool
 */
function api_site_use_cookie_warning_cookie_exist()
{
    return isset($_COOKIE['ChamiloUsesCookies']);
}

/**
 * Given a number of seconds, format the time to show hours, minutes and seconds.
 *
 * @param int    $time         The time in seconds
 * @param string $originFormat Optional. PHP o JS
 *
 * @return string (00h00'00")
 */
function api_format_time($time, $originFormat = 'php')
{
    $h = get_lang('h');
    $hours = $time / 3600;
    $mins = ($time % 3600) / 60;
    $secs = ($time % 60);

    if ($time < 0) {
        $hours = 0;
        $mins = 0;
        $secs = 0;
    }

    if ($originFormat == 'js') {
        $formattedTime = trim(sprintf("%02d : %02d : %02d", $hours, $mins, $secs));
    } else {
        $formattedTime = trim(sprintf("%02d$h%02d'%02d\"", $hours, $mins, $secs));
    }

    return $formattedTime;
}

/**
 * Create a new empty directory with index.html file.
 *
 * @param string $name            The new directory name
 * @param string $parentDirectory Directory parent directory name
 *
 * @return bool Return true if the directory was create. Otherwise return false
 */
function api_create_protected_dir($name, $parentDirectory)
{
    $isCreated = false;

    if (!is_writable($parentDirectory)) {
        return false;
    }

    $fullPath = $parentDirectory.api_replace_dangerous_char($name);

    if (mkdir($fullPath, api_get_permissions_for_new_directories(), true)) {
        $fp = fopen($fullPath.'/index.html', 'w');

        if ($fp) {
            if (fwrite($fp, '<html><head><title></title></head><body></body></html>')) {
                $isCreated = true;
            }
        }

        fclose($fp);
    }

    return $isCreated;
}

/**
 * Sends an HTML email using the phpmailer class (and multipart/alternative to downgrade gracefully)
 * Sender name and email can be specified, if not specified
 * name and email of the platform admin are used.
 *
 * @author Bert Vanderkimpen ICT&O UGent
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @param string    name of recipient
 * @param string    email of recipient
 * @param string    email subject
 * @param string    email body
 * @param string    sender name
 * @param string    sender e-mail
 * @param array  $extra_headers        in form $headers = array($name => $value) to allow parsing
 * @param array  $data_file            (path and filename)
 * @param bool   $embedded_image       True for attaching a embedded file inside content html (optional)
 * @param array  $additionalParameters
 * @param string $sendErrorTo          If there's an error while sending the email, $sendErrorTo will receive a notification
 *
 * @return int true if mail was sent
 *
 * @see             PHPMailer.php
 */
function api_mail_html(
    $recipient_name,
    $recipient_email,
    $subject,
    $message,
    $senderName = '',
    $senderEmail = '',
    $extra_headers = [],
    $data_file = [],
    $embedded_image = false,
    $additionalParameters = [],
    $sendErrorTo = ''
) {
    if (true === api_get_configuration_value('disable_send_mail')) {
        return true;
    }

    $mail = new PHPMailer();

    if (!empty(api_get_mail_configuration_value('XOAUTH2_METHOD'))) {
        $provider = new GenericProvider([
            'clientId' => api_get_mail_configuration_value('XOAUTH2_CLIENT_ID'),
            'clientSecret' => api_get_mail_configuration_value('XOAUTH2_CLIENT_SECRET'),
            'urlAuthorize' => api_get_mail_configuration_value('XOAUTH2_URL_AUTHORIZE'),
            'urlAccessToken' => api_get_mail_configuration_value('XOAUTH2_URL_ACCES_TOKEN'),
            'urlResourceOwnerDetails' => api_get_mail_configuration_value('XOAUTH2_URL_RESOURCE_OWNER_DETAILS'),
            'scopes' => api_get_mail_configuration_value('XOAUTH2_SCOPES'),
        ]);
        $mail->AuthType = 'XOAUTH2';
        $mail->setOAuth(
            new OAuth([
                'provider' => $provider,
                'clientId' => api_get_mail_configuration_value('XOAUTH2_CLIENT_ID'),
                'clientSecret' => api_get_mail_configuration_value('XOAUTH2_CLIENT_SECRET'),
                'refreshToken' => api_get_mail_configuration_value('XOAUTH2_REFRESH_TOKEN'),
                'userName' => api_get_mail_configuration_value('SMTP_USER'),
            ])
        );
    }

    $mail->Mailer = api_get_mail_configuration_value('SMTP_MAILER');
    $mail->Host = api_get_mail_configuration_value('SMTP_HOST');
    $mail->Port = api_get_mail_configuration_value('SMTP_PORT');
    $mail->CharSet = api_get_mail_configuration_value('SMTP_CHARSET') ?: 'UTF-8';
    // Stay far below SMTP protocol 980 chars limit.
    $mail->WordWrap = 200;
    $mail->SMTPOptions = api_get_mail_configuration_value('SMTPOptions') ?: [];

    if (api_get_mail_configuration_value('SMTP_AUTH')) {
        $mail->SMTPAuth = 1;
        $mail->Username = api_get_mail_configuration_value('SMTP_USER');
        $mail->Password = api_get_mail_configuration_value('SMTP_PASS');
        if (api_get_mail_configuration_value('SMTP_SECURE')) {
            $mail->SMTPSecure = api_get_mail_configuration_value('SMTP_SECURE');
        }
    }
    $mail->SMTPDebug = api_get_mail_configuration_value('SMTP_DEBUG') ?: 0;

    // 5 = low, 1 = high
    $mail->Priority = 3;
    $mail->SMTPKeepAlive = true;

    api_set_noreply_and_from_address_to_mailer(
        $mail,
        ['name' => $senderName, 'email' => $senderEmail],
        !empty($extra_headers['reply_to']) ? $extra_headers['reply_to'] : []
    );

    if (!empty($sendErrorTo) && PHPMailer::ValidateAddress($sendErrorTo)) {
        $mail->AddCustomHeader('Errors-To', $sendErrorTo);
    }

    unset($extra_headers['reply_to']);

    $mail->Subject = $subject;
    $mail->AltBody = strip_tags(
        str_replace('<br />', "\n", api_html_entity_decode($message))
    );

    $list = api_get_configuration_value('send_all_emails_to');
    if (!empty($list) && isset($list['emails'])) {
        foreach ($list['emails'] as $email) {
            $mail->AddAddress($email);
        }
    }

    // Send embedded image.
    if ($embedded_image) {
        // Get all images html inside content.
        preg_match_all("/<img\s+.*?src=[\"\']?([^\"\' >]*)[\"\']?[^>]*>/i", $message, $m);
        // Prepare new tag images.
        $new_images_html = [];
        $i = 1;
        if (!empty($m[1])) {
            foreach ($m[1] as $image_path) {
                $real_path = realpath($image_path);
                $filename = basename($image_path);
                $image_cid = $filename.'_'.$i;
                $encoding = 'base64';
                $image_type = mime_content_type($real_path);
                $mail->AddEmbeddedImage(
                    $real_path,
                    $image_cid,
                    $filename,
                    $encoding,
                    $image_type
                );
                $new_images_html[] = '<img src="cid:'.$image_cid.'" />';
                $i++;
            }
        }

        // Replace origin image for new embedded image html.
        $x = 0;
        if (!empty($m[0])) {
            foreach ($m[0] as $orig_img) {
                $message = str_replace($orig_img, $new_images_html[$x], $message);
                $x++;
            }
        }
    }

    $extendedFooterMessageConfig = api_get_configuration_value('notifications_extended_footer_message');
    if ($extendedFooterMessageConfig) {
        $platformLanguage = api_get_interface_language();
        $extendedFooterMessage = api_get_configuration_value('notifications_extended_footer_message')[$platformLanguage];

        if ($extendedFooterMessage) {
            $message .= '<br /><hr><i>'.'<p>'.implode('<br/><br/>', $extendedFooterMessage['paragraphs']).'</p>';
        }
    }

    $mailView = new Template(null, false, false, false, false, false, false);

    $noReply = api_get_setting('noreply_email_address');
    if (!empty($noReply)) {
        $message .= '<br />'.get_lang('ThisIsAutomaticEmailNoReply');
    }
    $mailView->assign('content', $message);

    if (isset($additionalParameters['link'])) {
        $mailView->assign('link', $additionalParameters['link']);
    }
    if (isset($additionalParameters['logo'])) {
        $mailView->assign('logo', $additionalParameters['logo']);
    }
    $mailView->assign('mail_header_style', api_get_configuration_value('mail_header_style'));
    $mailView->assign('mail_content_style', api_get_configuration_value('mail_content_style'));
    $mailView->assign('include_ldjson', (empty(api_get_mail_configuration_value('EXCLUDE_JSON')) ? true : false));
    $layout = $mailView->get_template('mail/mail.tpl');
    $mail->Body = $mailView->fetch($layout);

    if (isset($additionalParameters['checkUrls'])) {
        $useMultipleUrl = api_get_configuration_value('multiple_access_urls');
        if ($useMultipleUrl) {
            $accessConfig = [];
            $accessUrls = api_get_access_url_from_user($additionalParameters['userId'], $additionalParameters['courseId']);
            if (!empty($accessUrls)) {
                $accessConfig['multiple_access_urls'] = true;
                $accessConfig['access_url'] = (int) $accessUrls[0];
                $params = ['variable = ? AND access_url = ?' => ['stylesheets', $accessConfig['access_url']]];
                $settings = api_get_settings_params_simple($params);
                if (!empty($settings['selected_value'])) {
                    $accessConfig['theme_dir'] = \Template::getThemeDir($settings['selected_value']);
                }
            }
            // To replace the current urls by access url user
            $mail->Body = str_replace(api_get_path(WEB_PATH), api_get_path(WEB_PATH, $accessConfig), $mail->Body);
            if (!empty($accessConfig['theme_dir'])) {
                $mail->Body = str_replace('themes/chamilo/', $accessConfig['theme_dir'], $mail->Body);
            }
        }
    }
    // Attachment.
    if (!empty($data_file)) {
        foreach ($data_file as $file_attach) {
            if (!empty($file_attach['path']) && !empty($file_attach['filename'])) {
                $mail->AddAttachment($file_attach['path'], $file_attach['filename']);
            }
        }
    }

    // Only valid addresses are accepted.
    if (is_array($recipient_email)) {
        foreach ($recipient_email as $dest) {
            if (api_valid_email($dest)) {
                $mail->AddAddress($dest, $recipient_name);
            }
        }
    } else {
        if (api_valid_email($recipient_email)) {
            $mail->AddAddress($recipient_email, $recipient_name);
        } else {
            return 0;
        }
    }

    if (is_array($extra_headers) && count($extra_headers) > 0) {
        foreach ($extra_headers as $key => $value) {
            switch (strtolower($key)) {
                case 'encoding':
                case 'content-transfer-encoding':
                    $mail->Encoding = $value;
                    break;
                case 'charset':
                    $mail->CharSet = $value;
                    break;
                case 'contenttype':
                case 'content-type':
                    $mail->ContentType = $value;
                    break;
                default:
                    $mail->AddCustomHeader($key, $value);
                    break;
            }
        }
    } else {
        if (!empty($extra_headers)) {
            $mail->AddCustomHeader($extra_headers);
        }
    }

    // WordWrap the html body (phpMailer only fixes AltBody) FS#2988
    $mail->Body = $mail->WrapText($mail->Body, $mail->WordWrap);

    if (!empty(api_get_mail_configuration_value('DKIM')) &&
        !empty(api_get_mail_configuration_value('DKIM_SELECTOR')) &&
        !empty(api_get_mail_configuration_value('DKIM_DOMAIN')) &&
        (!empty(api_get_mail_configuration_value('DKIM_PRIVATE_KEY_STRING')) || !empty(api_get_mail_configuration_value('DKIM_PRIVATE_KEY')))) {
        $mail->DKIM_selector = api_get_mail_configuration_value('DKIM_SELECTOR');
        $mail->DKIM_domain = api_get_mail_configuration_value('DKIM_DOMAIN');
        if (!empty(api_get_mail_configuration_value('SMTP_UNIQUE_SENDER'))) {
            $mail->DKIM_identity = api_get_mail_configuration_value('SMTP_FROM_EMAIL');
        }
        $mail->DKIM_private_string = api_get_mail_configuration_value('DKIM_PRIVATE_KEY_STRING');
        $mail->DKIM_private = api_get_mail_configuration_value('DKIM_PRIVATE_KEY');
    }

    // Send the mail message.
    $sent = $mail->Send();
    if (!$sent) {
        error_log('ERROR: mail not sent to '.$recipient_name.' ('.$recipient_email.') because of '.$mail->ErrorInfo.'<br />');
    }

    if ($mail->SMTPDebug >= 1) {
        error_log(
            "Mail debug:: ".
            "Protocol: ".$mail->Mailer.' :: '.
            "Host/Port: ".$mail->Host.':'.$mail->Port.' :: '.
            "Authent/Open: ".($mail->SMTPAuth ? 'Authent' : 'Open').' :: '.
            ($mail->SMTPAuth ? "  User/Pass: ".$mail->Username.':'.$mail->Password : '').' :: '.
            "Sender: ".$mail->Sender.
            "Recipient email: ".$recipient_email.
            "Subject: ".$subject
        );
    }

    if (!$sent) {
        return 0;
    }

    if (!empty($additionalParameters)) {
        $plugin = new AppPlugin();
        $smsPlugin = $plugin->getSMSPluginLibrary();
        if ($smsPlugin) {
            $smsPlugin->send($additionalParameters);
        }
    }

    // Clear all the addresses.
    $mail->ClearAddresses();

    // Clear all attachments
    $mail->ClearAttachments();

    return 1;
}

/**
 * Checks access to a course group.
 *
 * @param string $tool       Possible values: GroupManager::GROUP_TOOL_*
 * @param bool   $showHeader
 */
function api_protect_course_group($tool, $showHeader = true)
{
    $groupId = api_get_group_id();
    if (!empty($groupId)) {
        if (api_is_platform_admin()) {
            return true;
        }

        if (api_is_allowed_to_edit(false, true, true)) {
            return true;
        }

        $userId = api_get_user_id();
        $sessionId = api_get_session_id();
        if (!empty($sessionId)) {
            if (api_is_coach($sessionId, api_get_course_int_id())) {
                return true;
            }

            if (api_is_drh()) {
                if (SessionManager::isUserSubscribedAsHRM($sessionId, $userId)) {
                    return true;
                }
            }
        }

        $groupInfo = GroupManager::get_group_properties($groupId);

        // Group doesn't exists
        if (empty($groupInfo)) {
            api_not_allowed($showHeader);
        }

        // Check group access
        $allow = GroupManager::user_has_access(
            $userId,
            $groupInfo['iid'],
            $tool
        );

        if (!$allow) {
            api_not_allowed($showHeader);
        }
    }

    return false;
}

/**
 * Check if a date is in a date range.
 *
 * @param datetime $startDate
 * @param datetime $endDate
 * @param datetime $currentDate
 *
 * @return bool true if date is in rage, false otherwise
 */
function api_is_date_in_date_range($startDate, $endDate, $currentDate = null)
{
    $startDate = strtotime(api_get_local_time($startDate));
    $endDate = strtotime(api_get_local_time($endDate));
    $currentDate = strtotime(api_get_local_time($currentDate));

    if ($currentDate >= $startDate && $currentDate <= $endDate) {
        return true;
    }

    return false;
}

/**
 * Eliminate the duplicates of a multidimensional array by sending the key.
 *
 * @param array $array multidimensional array
 * @param int   $key   key to find to compare
 *
 * @return array
 */
function api_unique_multidim_array($array, $key)
{
    $temp_array = [];
    $i = 0;
    $key_array = [];

    foreach ($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }

    return $temp_array;
}

/**
 * Limit the access to Session Admins when the limit_session_admin_role
 * configuration variable is set to true.
 */
function api_protect_limit_for_session_admin()
{
    $limitAdmin = api_get_setting('limit_session_admin_role');
    if (api_is_session_admin() && $limitAdmin === 'true') {
        api_not_allowed(true);
    }
}

/**
 * Limits that a session admin has access to list users.
 * When limit_session_admin_list_users configuration variable is set to true.
 */
function api_protect_session_admin_list_users()
{
    $limitAdmin = api_get_configuration_value('limit_session_admin_list_users');

    if (api_is_session_admin() && true === $limitAdmin) {
        api_not_allowed(true);
    }
}

/**
 * @return bool
 */
function api_is_student_view_active()
{
    $studentView = Session::read('studentview');

    return $studentView == 'studentview';
}

/**
 * Adds a file inside the upload/$type/id.
 *
 * @param string $type
 * @param array  $file
 * @param int    $itemId
 * @param string $cropParameters
 *
 * @return array|bool
 */
function api_upload_file($type, $file, $itemId, $cropParameters = '')
{
    $upload = process_uploaded_file($file);
    if ($upload) {
        $name = api_replace_dangerous_char($file['name']);

        // No "dangerous" files
        $name = disable_dangerous_file($name);

        $pathId = '/'.substr((string) $itemId, 0, 1).'/'.$itemId.'/';
        $path = api_get_path(SYS_UPLOAD_PATH).$type.$pathId;

        if (!is_dir($path)) {
            mkdir($path, api_get_permissions_for_new_directories(), true);
        }

        $pathToSave = $path.$name;
        $result = moveUploadedFile($file, $pathToSave);

        if ($result) {
            if (!empty($cropParameters)) {
                $image = new Image($pathToSave);
                $image->crop($cropParameters);
            }

            return ['path_to_save' => $pathId.$name];
        }
    }

    return false;
}

/**
 * @param string $type
 * @param int    $itemId
 * @param string $file
 *
 * @return bool
 */
function api_get_uploaded_web_url($type, $itemId, $file)
{
    return api_get_uploaded_file($type, $itemId, $file, true);
}

/**
 * @param string $type
 * @param int    $itemId
 * @param string $file
 * @param bool   $getUrl
 *
 * @return bool
 */
function api_get_uploaded_file($type, $itemId, $file, $getUrl = false)
{
    $itemId = (int) $itemId;
    $pathId = '/'.substr((string) $itemId, 0, 1).'/'.$itemId.'/';
    $path = api_get_path(SYS_UPLOAD_PATH).$type.$pathId;
    $file = basename($file);
    $file = $path.'/'.$file;
    if (Security::check_abs_path($file, $path) && is_file($file) && file_exists($file)) {
        if ($getUrl) {
            return str_replace(api_get_path(SYS_UPLOAD_PATH), api_get_path(WEB_UPLOAD_PATH), $file);
        }

        return $file;
    }

    return false;
}

/**
 * @param string $type
 * @param int    $itemId
 * @param string $file
 * @param string $title
 */
function api_download_uploaded_file($type, $itemId, $file, $title = '')
{
    $file = api_get_uploaded_file($type, $itemId, $file);
    if ($file) {
        if (Security::check_abs_path($file, api_get_path(SYS_UPLOAD_PATH).$type)) {
            DocumentManager::file_send_for_download($file, true, $title);
            exit;
        }
    }
    api_not_allowed(true);
}

/**
 * @param string $type
 * @param string $file
 */
function api_remove_uploaded_file($type, $file)
{
    $typePath = api_get_path(SYS_UPLOAD_PATH).$type;
    $path = $typePath.'/'.$file;
    if (Security::check_abs_path($path, $typePath) && file_exists($path) && is_file($path)) {
        unlink($path);
    }
}

/**
 * @param string $type
 * @param int    $itemId
 * @param string $file
 *
 * @return bool
 */
function api_remove_uploaded_file_by_id($type, $itemId, $file)
{
    $file = api_get_uploaded_file($type, $itemId, $file, false);
    $typePath = api_get_path(SYS_UPLOAD_PATH).$type;
    if (Security::check_abs_path($file, $typePath) && file_exists($file) && is_file($file)) {
        unlink($file);

        return true;
    }

    return false;
}

/**
 * Converts string value to float value.
 *
 * 3.141516 => 3.141516
 * 3,141516 => 3.141516
 *
 * @todo WIP
 *
 * @param string $number
 *
 * @return float
 */
function api_float_val($number)
{
    $number = (float) str_replace(',', '.', trim($number));

    return $number;
}

/**
 * Converts float values
 * Example if $decimals = 2.
 *
 * 3.141516 => 3.14
 * 3,141516 => 3,14
 *
 * @param string $number            number in iso code
 * @param int    $decimals
 * @param string $decimalSeparator
 * @param string $thousandSeparator
 *
 * @return bool|string
 */
function api_number_format($number, $decimals = 0, $decimalSeparator = '.', $thousandSeparator = ',')
{
    $number = api_float_val($number);

    return number_format($number, $decimals, $decimalSeparator, $thousandSeparator);
}

/**
 * Set location url with a exit break by default.
 *
 * @param string $url
 * @param bool   $exit
 */
function api_location($url, $exit = true)
{
    header('Location: '.$url);

    if ($exit) {
        exit;
    }
}

/**
 * @return string
 */
function api_get_web_url()
{
    if (api_get_setting('server_type') === 'test') {
        return api_get_path(WEB_PATH).'web/app_dev.php/';
    } else {
        return api_get_path(WEB_PATH).'web/';
    }
}

/**
 * @param string $from
 * @param string $to
 *
 * @return string
 */
function api_get_relative_path($from, $to)
{
    // some compatibility fixes for Windows paths
    $from = is_dir($from) ? rtrim($from, '\/').'/' : $from;
    $to = is_dir($to) ? rtrim($to, '\/').'/' : $to;
    $from = str_replace('\\', '/', $from);
    $to = str_replace('\\', '/', $to);

    $from = explode('/', $from);
    $to = explode('/', $to);
    $relPath = $to;

    foreach ($from as $depth => $dir) {
        // find first non-matching dir
        if ($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if ($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './'.$relPath[0];
            }
        }
    }

    return implode('/', $relPath);
}

/**
 * Unserialize content using Brummann\Polyfill\Unserialize.
 *
 * @param string $type
 * @param string $serialized
 *
 * @return mixed
 */
function api_unserialize_content($type, $serialized, $ignoreErrors = false)
{
    switch ($type) {
        case 'career':
        case 'sequence_graph':
            $allowedClasses = [Graph::class, VerticesMap::class, Vertices::class, Edges::class];
            break;
        case 'lp':
            $allowedClasses = [
                learnpath::class,
                learnpathItem::class,
                aicc::class,
                aiccBlock::class,
                aiccItem::class,
                aiccObjective::class,
                aiccResource::class,
                scorm::class,
                scormItem::class,
                scormMetadata::class,
                scormOrganization::class,
                scormResource::class,
                Link::class,
                LpItem::class,
            ];
            break;
        case 'course':
            $allowedClasses = [
                Course::class,
                Announcement::class,
                Attendance::class,
                CalendarEvent::class,
                CourseCopyLearnpath::class,
                CourseCopyTestCategory::class,
                CourseDescription::class,
                CourseSession::class,
                Document::class,
                Forum::class,
                ForumCategory::class,
                ForumPost::class,
                ForumTopic::class,
                Glossary::class,
                GradeBookBackup::class,
                Link::class,
                LinkCategory::class,
                Quiz::class,
                QuizQuestion::class,
                QuizQuestionOption::class,
                ScormDocument::class,
                Survey::class,
                SurveyInvitation::class,
                SurveyQuestion::class,
                Thematic::class,
                ToolIntro::class,
                Wiki::class,
                Work::class,
                stdClass::class,
            ];
            break;
        case 'not_allowed_classes':
        default:
            $allowedClasses = false;
    }

    if ($ignoreErrors) {
        return @Unserialize::unserialize(
            $serialized,
            ['allowed_classes' => $allowedClasses]
        );
    }

    return Unserialize::unserialize(
        $serialized,
        ['allowed_classes' => $allowedClasses]
    );
}

/**
 * Set the From and ReplyTo properties to PHPMailer instance.
 *
 * @throws \PHPMailer\PHPMailer\Exception
 */
function api_set_noreply_and_from_address_to_mailer(PHPMailer $mailer, array $sender, array $replyToAddress = [])
{
    $noReplyAddress = api_get_setting('noreply_email_address');
    $avoidReplyToAddress = false;

    if (!empty($noReplyAddress)) {
        $avoidReplyToAddress = api_get_configuration_value('mail_no_reply_avoid_reply_to');
    }

    $notification = new Notification();
    // If the parameter is set don't use the admin.
    $senderName = !empty($sender['name']) ? $sender['name'] : $notification->getDefaultPlatformSenderName();
    $senderEmail = !empty($sender['email']) ? $sender['email'] : $notification->getDefaultPlatformSenderEmail();

    // Send errors to the platform admin
    $adminEmail = api_get_setting('emailAdministrator');
    if (PHPMailer::ValidateAddress($adminEmail)) {
        $mailer->AddCustomHeader('Errors-To: '.$adminEmail);
    }

    // Reply to first
    if (!$avoidReplyToAddress) {
        if (
            !empty($replyToAddress) &&
            PHPMailer::ValidateAddress($replyToAddress['mail'])
        ) {
            $mailer->AddReplyTo($replyToAddress['mail'], $replyToAddress['name']);
            //$mailer->Sender = $replyToAddress['mail'];
        }
    }

    //If the SMTP configuration only accept one sender
    if (
        !empty(api_get_mail_configuration_value('SMTP_UNIQUE_SENDER')) &&
        api_get_mail_configuration_value('SMTP_UNIQUE_SENDER')
    ) {
        $senderName = $notification->getDefaultPlatformSenderName();
        $senderEmail = $notification->getDefaultPlatformSenderEmail();

        if (PHPMailer::ValidateAddress($senderEmail)) {
            //force-set Sender to $senderEmail, otherwise SetFrom only does it if it is currently empty
            $mailer->Sender = $senderEmail;
        }
    }

    $mailer->SetFrom($senderEmail, $senderName, !$avoidReplyToAddress);
}

/**
 * @param string $template
 *
 * @return string
 */
function api_find_template($template)
{
    return Template::findTemplateFilePath($template);
}

/**
 * Returns an array of languages (English names like "english", "french", etc)
 * to ISO 639-1 codes (fr, es, etc) for use (for example) to show flags
 * Note: 'english' is returned as 'gb'.
 *
 * @return array
 */
function api_get_language_list_for_flag()
{
    $table = Database::get_main_table(TABLE_MAIN_LANGUAGE);
    $sql = "SELECT english_name, isocode FROM $table
            ORDER BY original_name ASC";
    static $languages = [];
    if (empty($languages)) {
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $languages[$row['english_name']] = $row['isocode'];
        }
        $languages['english'] = 'gb';
    }

    return $languages;
}

/**
 * Generate the Javascript required for the on-page translation of
 * multi-language strings.
 *
 * @throws Exception
 *
 * @return string
 */
function api_get_language_translate_html()
{
    $translate = api_get_configuration_value('translate_html');

    if (!$translate) {
        return '';
    }

    $languageList = api_get_languages();
    $hideAll = '';
    foreach ($languageList['all'] as $language) {
        $hideAll .= '
        $("span:lang('.$language['isocode'].')").filter(
            function(e, val) {
                // Only find the spans if they have set the lang
                if ($(this).attr("lang") == null) {
                    return false;
                }

                // Ignore ckeditor classes
                return !this.className.match(/cke(.*)/);
        }).hide();'."\n";
    }

    $userInfo = api_get_user_info();
    $languageId = 0;
    if (!empty($userInfo['language'])) {
        $languageId = api_get_language_id($userInfo['language']);
    } elseif (!empty($_GET['language'])) {
        $languageId = api_get_language_id($_GET['language']);
    }
    $languageInfo = api_get_language_info($languageId);
    $isoCode = 'en';

    if (!empty($languageInfo)) {
        $isoCode = $languageInfo['isocode'];
    }

    return '
            $(function() {
                '.$hideAll.'
                var defaultLanguageFromUser = "'.$isoCode.'";

                $("span:lang('.$isoCode.')").filter(
                    function() {
                        // Ignore ckeditor classes
                        return !this.className.match(/cke(.*)/);
                }).show();

                var defaultLanguage = "";
                var langFromUserFound = false;

                $(this).find("span").filter(
                    function() {
                        // Ignore ckeditor classes
                        return !this.className.match(/cke(.*)/);
                }).each(function() {
                    defaultLanguage = $(this).attr("lang");
                    if (defaultLanguage) {
                        $(this).before().next("br").remove();
                        if (defaultLanguageFromUser == defaultLanguage) {
                            langFromUserFound = true;
                        }
                    }
                });

                // Show default language
                if (langFromUserFound == false && defaultLanguage) {
                    $("span:lang("+defaultLanguage+")").filter(
                    function() {
                            // Ignore ckeditor classes
                            return !this.className.match(/cke(.*)/);
                    }).show();
                }
            });
    ';
}

/**
 * Filter a multi-language HTML string (for the multi-language HTML
 * feature) into the given language (strip the rest).
 *
 * @param string $htmlString The HTML string to "translate". Usually <p><span lang="en">Some string</span></p><p><span lang="fr">Une chaîne</span></p>
 * @param string $language   The language in which we want to get the
 *
 * @throws Exception
 *
 * @return string The filtered string in the given language, or the full string if no translated string was identified
 */
function api_get_filtered_multilingual_HTML_string($htmlString, $language = null)
{
    if (api_get_configuration_value('translate_html') != true) {
        return $htmlString;
    }
    $userInfo = api_get_user_info();
    $languageId = 0;
    if (!empty($language)) {
        $languageId = api_get_language_id($language);
    } elseif (!empty($userInfo['language'])) {
        $languageId = api_get_language_id($userInfo['language']);
    }
    $languageInfo = api_get_language_info($languageId);
    $isoCode = 'en';

    if (!empty($languageInfo)) {
        $isoCode = $languageInfo['isocode'];
    }

    // Split HTML in the separate language strings
    // Note: some strings might look like <p><span ..>...</span></p> but others might be like combine 2 <span> in 1 <p>
    if (!preg_match('/<span.*?lang="(\w\w)">/is', $htmlString)) {
        return $htmlString;
    }
    $matches = [];
    preg_match_all('/<span.*?lang="(\w\w)">(.*?)<\/span>/is', $htmlString, $matches);
    if (!empty($matches)) {
        // matches[0] are the full string
        // matches[1] are the languages
        // matches[2] are the strings
        foreach ($matches[1] as $id => $match) {
            if ($match == $isoCode) {
                return $matches[2][$id];
            }
        }
        // Could find the pattern but could not find our language. Return the first language found.
        return $matches[2][0];
    }
    // Could not find pattern. Just return the whole string. We shouldn't get here.
    return $htmlString;
}

/**
 * Get the print.css file for current theme.
 * Only the file path or the file contents when $getFileContents is true.
 */
function api_get_print_css(bool $getFileContents = true, bool $useWebPath = false): string
{
    $sysCssPath = api_get_path(SYS_CSS_PATH);
    $cssFile = $sysCssPath.'themes/'.api_get_visual_theme().'/print.css';

    if (!file_exists($cssFile)) {
        $cssFile = $sysCssPath.'print.css';
    }

    if ($getFileContents) {
        return file_get_contents($cssFile);
    }

    if ($useWebPath) {
        return str_replace($sysCssPath, api_get_path(WEB_CSS_PATH), $cssFile);
    }

    return $cssFile;
}

function api_protect_webservices()
{
    if (api_get_configuration_value('disable_webservices')) {
        echo "Webservices are disabled. \n";
        echo "To enable, add \$_configuration['disable_webservices'] = true; in configuration.php";
        exit;
    }
}

function api_filename_has_blacklisted_stream_wrapper(string $filename)
{
    if (strpos($filename, '://') > 0) {
        $wrappers = stream_get_wrappers();
        $allowedWrappers = ['http', 'https', 'file'];

        foreach ($wrappers as $wrapper) {
            if (in_array($wrapper, $allowedWrappers)) {
                continue;
            }

            if (stripos($filename, $wrapper.'://') === 0) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Calculate the percent between two numbers.
 *
 * @return string
 */
function api_calculate_increment_percent(int $newValue, int $oldValue)
{
    if ($oldValue <= 0) {
        $result = " - ";
    } else {
        $result = ' '.round(100 * (($newValue / $oldValue) - 1), 2).' %';
    }

    return $result;
}

/**
 * Erase settings from cache (because of some update) if applicable.
 *
 * @param int $url_id The ID of the present URL
 */
function api_flush_settings_cache(int $url_id): bool
{
    global $_configuration;
    $cacheAvailable = api_get_configuration_value('apc');
    if (!$cacheAvailable) {
        return false;
    }
    $apcRootVarName = api_get_configuration_value('apc_prefix');
    // Delete the APCu-stored settings array, if present
    $apcVarName = $apcRootVarName.'settings';
    apcu_delete($apcVarName);
    if (api_is_multiple_url_enabled() && $url_id === 1) {
        // if we are on the main URL of a multi-url portal, we must
        // invalidate the cache for all other URLs as well as some
        // main settings span multiple URLs
        $urls = api_get_access_urls();
        foreach ($urls as $i => $row) {
            if ($row['id'] == 1) {
                continue;
            }
            $apcVarName = $_configuration['main_database'].'_'.$row['id'].'_settings';
            apcu_delete($apcVarName);
        }
    }

    return true;
}
