<?php // $Id: database.lib.php 22265 2009-07-20 23:26:43Z juliomontoya $
/* See license terms in /dokeos_license.txt */
/**
==============================================================================
*	This is the main database library for Dokeos.
*	Include/require it in your code to use its functionality.
*   Because this library contains all the basic database calls, it could be
*   replaced by another library for say, PostgreSQL, to actually use Dokeos
*   with another database (this is not ready yet because a lot of code still
*   uses the MySQL database functions extensively).
*
*	@package dokeos.library
* 	@todo the table constants have all to start with TABLE_
* 		  This is because of the analogy with the tool constants TOOL_
==============================================================================
*/

// TODO: It seems more natural if the function api_store_result() in
// the main API had an implementation here as Database::store_result()

/*
==============================================================================
		CONSTANTS
==============================================================================
*/

// Main database tables
define('TABLE_MAIN_COURSE', 'course');
define('TABLE_MAIN_USER', 'user');
define('TABLE_MAIN_CLASS', 'class');
define('TABLE_MAIN_ADMIN', 'admin');
define('TABLE_MAIN_COURSE_CLASS', 'course_rel_class');
define('TABLE_MAIN_COURSE_USER', 'course_rel_user');
define('TABLE_MAIN_CLASS_USER', 'class_user');
define('TABLE_MAIN_CATEGORY', 'course_category');
define('TABLE_MAIN_COURSE_MODULE', 'course_module');
define('TABLE_MAIN_SYSTEM_ANNOUNCEMENTS', 'sys_announcement');
define('TABLE_MAIN_LANGUAGE', 'language');
define('TABLE_MAIN_SETTINGS_OPTIONS', 'settings_options');
define('TABLE_MAIN_SETTINGS_CURRENT', 'settings_current');
define('TABLE_MAIN_SESSION', 'session');
define('TABLE_MAIN_SESSION_COURSE', 'session_rel_course');
define('TABLE_MAIN_SESSION_USER', 'session_rel_user');
define('TABLE_MAIN_SESSION_CLASS', 'session_rel_class');
define('TABLE_MAIN_SESSION_COURSE_USER', 'session_rel_course_rel_user');
define('TABLE_MAIN_SHARED_SURVEY', 'shared_survey');
define('TABLE_MAIN_SHARED_SURVEY_QUESTION', 'shared_survey_question');
define('TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION', 'shared_survey_question_option');
define('TABLE_MAIN_TEMPLATES', 'templates');
define('TABLE_MAIN_SYSTEM_TEMPLATE', 'system_template');
define('TABLE_MAIN_OPENID_ASSOCIATION', 'openid_association');

// Gradebook
define('TABLE_MAIN_GRADEBOOK_CATEGORY', 	'gradebook_category');
define('TABLE_MAIN_GRADEBOOK_EVALUATION', 	'gradebook_evaluation');
define('TABLE_MAIN_GRADEBOOK_LINKEVAL_LOG', 'gradebook_linkeval_log');
define('TABLE_MAIN_GRADEBOOK_RESULT', 		'gradebook_result');
define('TABLE_MAIN_GRADEBOOK_RESULT_LOG', 	'gradebook_result_log');
define('TABLE_MAIN_GRADEBOOK_LINK', 		'gradebook_link');
define('TABLE_MAIN_GRADEBOOK_SCORE_DISPLAY','gradebook_score_display');

// Profiling
define('TABLE_MAIN_USER_FIELD',			'user_field');
define('TABLE_MAIN_USER_FIELD_OPTIONS',	'user_field_options');
define('TABLE_MAIN_USER_FIELD_VALUES',	'user_field_values');

// Search engine
define('TABLE_MAIN_SPECIFIC_FIELD',			'specific_field');
define('TABLE_MAIN_SPECIFIC_FIELD_VALUES',	'specific_field_values');
define('TABLE_MAIN_SEARCH_ENGINE_REF',		'search_engine_ref');

// Access URLs
define('TABLE_MAIN_ACCESS_URL', 'access_url');
define('TABLE_MAIN_ACCESS_URL_REL_USER',	'access_url_rel_user');
define('TABLE_MAIN_ACCESS_URL_REL_COURSE', 	'access_url_rel_course');
define('TABLE_MAIN_ACCESS_URL_REL_SESSION', 'access_url_rel_session');

// Global calendar
define('TABLE_MAIN_SYSTEM_CALENDAR', 'sys_calendar');

// Reservation System
define('TABLE_MAIN_RESERVATION_ITEM', 'reservation_item');
define('TABLE_MAIN_RESERVATION_RESERVATION', 'reservation_main');
define('TABLE_MAIN_RESERVATION_SUBSCRIBTION', 'reservation_subscription');
define('TABLE_MAIN_RESERVATION_CATEGORY', 'reservation_category');
define('TABLE_MAIN_RESERVATION_ITEM_RIGHTS', 'reservation_item_rights');

// Social networking
define('TABLE_MAIN_USER_FRIEND', 'user_friend');
define('TABLE_MAIN_USER_FRIEND_RELATION_TYPE', 'user_friend_relation_type');

// Web services
define('TABLE_MAIN_USER_API_KEY', 'user_api_key');
define('TABLE_MAIN_COURSE_FIELD',			'course_field');
define('TABLE_MAIN_COURSE_FIELD_VALUES',	'course_field_values');
define('TABLE_MAIN_SESSION_FIELD',			'session_field');
define('TABLE_MAIN_SESSION_FIELD_VALUES',	'session_field_values');

// Message
define('TABLE_MAIN_MESSAGE', 'message');

// Term and conditions
define('TABLE_MAIN_LEGAL', 'legal');

// Statistic database tables
define('TABLE_STATISTIC_TRACK_E_LASTACCESS', 'track_e_lastaccess');
define('TABLE_STATISTIC_TRACK_E_ACCESS', 'track_e_access');
define('TABLE_STATISTIC_TRACK_E_LOGIN', 'track_e_login');
define('TABLE_STATISTIC_TRACK_E_DOWNLOADS', 'track_e_downloads');
define('TABLE_STATISTIC_TRACK_E_LINKS', 'track_e_links');
define('TABLE_STATISTIC_TRACK_E_ONLINE', 'track_e_online');
define('TABLE_STATISTIC_TRACK_E_HOTPOTATOES', 'track_e_hotpotatoes');
define('TABLE_STATISTIC_TRACK_E_COURSE_ACCESS', 'track_e_course_access');
define('TABLE_STATISTIC_TRACK_E_EXERCICES', 'track_e_exercices');
define('TABLE_STATISTIC_TRACK_E_ATTEMPT', 'track_e_attempt');
define('TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING', 'track_e_attempt_recording');
define('TABLE_STATISTIC_TRACK_E_DEFAULT', 'track_e_default');
define('TABLE_STATISTIC_TRACK_E_UPLOADS', 'track_e_uploads');
define('TABLE_STATISTIC_TRACK_E_HOTSPOT', 'track_e_hotspot');

// SCORM database tables
define('TABLE_SCORM_MAIN', 'scorm_main');
define('TABLE_SCORM_SCO_DATA', 'scorm_sco_data');

// Course tables
define('TABLE_AGENDA', 'calendar_event');
define('TABLE_AGENDA_REPEAT', 'calendar_event_repeat');
define('TABLE_AGENDA_REPEAT_NOT', 'calendar_event_repeat_not');
define('TABLE_AGENDA_ATTACHMENT', 'calendar_event_attachment');
define('TABLE_ANNOUNCEMENT', 'announcement');
define('TABLE_CHAT_CONNECTED', 'chat_connected'); // @todo: probably no longer in use !!!
define('TABLE_COURSE_DESCRIPTION', 'course_description');
define('TABLE_DOCUMENT', 'document');
define('TABLE_ITEM_PROPERTY', 'item_property');
define('TABLE_LINK', 'link');
define('TABLE_LINK_CATEGORY', 'link_category');
define('TABLE_TOOL_LIST', 'tool');
define('TABLE_TOOL_INTRO', 'tool_intro');
define('TABLE_SCORMDOC', 'scormdocument');
define('TABLE_STUDENT_PUBLICATION', 'student_publication');
define('TABLE_STUDENT_PUBLICATION_ASSIGNMENT', 'student_publication_assignment');
define('CHAT_CONNECTED_TABLE', 'chat_connected');

// Course forum tables
define('TABLE_FORUM_CATEGORY', 'forum_category');
define('TABLE_FORUM', 'forum_forum');
define('TABLE_FORUM_THREAD', 'forum_thread');
define('TABLE_FORUM_POST', 'forum_post');
define('TABLE_FORUM_ATTACHMENT', 'forum_attachment');
define('TABLE_FORUM_MAIL_QUEUE', 'forum_mailcue');
define('TABLE_FORUM_THREAD_QUALIFY', 'forum_thread_qualify');
define('TABLE_FORUM_THREAD_QUALIFY_LOG', 'forum_thread_qualify_log');
define('TABLE_FORUM_NOTIFICATION', 'forum_notification');

// Course group tables
define('TABLE_GROUP', 'group_info');
define('TABLE_GROUP_USER', 'group_rel_user');
define('TABLE_GROUP_TUTOR', 'group_rel_tutor');
define('TABLE_GROUP_CATEGORY', 'group_category');

// Course dropbox tables
define('TABLE_DROPBOX_CATEGORY', 'dropbox_category');
define('TABLE_DROPBOX_FEEDBACK', 'dropbox_feedback');
define('TABLE_DROPBOX_POST', 'dropbox_post');
define('TABLE_DROPBOX_FILE', 'dropbox_file');
define('TABLE_DROPBOX_PERSON', 'dropbox_person');

// Course quiz (or test, or exercice) tables
define('TABLE_QUIZ_QUESTION', 'quiz_question');
define('TABLE_QUIZ_TEST', 'quiz');
define('TABLE_QUIZ_ANSWER', 'quiz_answer');
define('TABLE_QUIZ_TEST_QUESTION', 'quiz_rel_question');

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
define('TABLE_USER_INFO', 'userinfo_def');
define('TABLE_USER_INFO_CONTENT', 'userinfo_content');

// Course settings table
define('TABLE_COURSE_SETTING', 'course_setting');

// Course online tables
define('TABLE_ONLINE_LINK', 'online_link');
define('TABLE_ONLINE_CONNECTED', 'online_connected');

// Dokeos_user database
define('TABLE_PERSONAL_AGENDA', 'personal_agenda');
define('TABLE_PERSONAL_AGENDA_REPEAT', 'personal_agenda_repeat');
define('TABLE_PERSONAL_AGENDA_REPEAT_NOT', 'personal_agenda_repeat_not');
define('TABLE_USER_COURSE_CATEGORY', 'user_course_category');

// Survey
// @TODO: Are these MAIN tables or course tables?
// @TODO: Probably these constants are obsolete.
define('TABLE_MAIN_SURVEY', 'survey');
define('TABLE_MAIN_GROUP', 'survey_group');
define('TABLE_MAIN_SURVEYQUESTION', 'questions');

// Survey
define('TABLE_SURVEY', 'survey');
define('TABLE_SURVEY_QUESTION', 'survey_question');
define('TABLE_SURVEY_QUESTION_OPTION', 'survey_question_option');
define('TABLE_SURVEY_INVITATION', 'survey_invitation');
define('TABLE_SURVEY_ANSWER', 'survey_answer');
define('TABLE_SURVEY_QUESTION_GROUP', 'survey_group');
define('TABLE_SURVEY_REPORT', 'survey_report');

// Wiki tables
define('TABLE_WIKI', 'wiki');
define('TABLE_WIKI_CONF', 'wiki_conf');
define('TABLE_WIKI_DISCUSS', 'wiki_discuss');
define('TABLE_WIKI_MAILCUE', 'wiki_mailcue');

// Glossary
define('TABLE_GLOSSARY', 'glossary');

// Notebook
define('TABLE_NOTEBOOK', 'notebook');

// Message
define('TABLE_MESSAGE', 'message');

// Metadata
define('TABLE_METADATA', 'metadata');


/*
==============================================================================
		DATABASE CLASS
		the class and its functions
==============================================================================
*/

/**
 *	@package dokeos.library
 */

class Database {

	/*
	-----------------------------------------------------------------------------
		Accessor Functions
		Usually, you won't need these directly but instead
		rely on of the get_xxx_table functions.
	-----------------------------------------------------------------------------
	*/

	/**
	 *	Returns the name of the main Dokeos database.
	 */
	public static function get_main_database() {
		global $_configuration;
		return $_configuration['main_database'];
	}

	/**
	 *	Returns the name of the Dokeos statistics database.
	 */
	public static function get_statistic_database() {
		global $_configuration;
		return $_configuration['statistics_database'];
	}

	/**
	 *	Returns the name of the Dokeos SCORM database.
	 *	@deprecated
	 */
	public static function get_scorm_database() {
		global $_configuration;
		return $_configuration['scorm_database'];
	}

	/**
	 *	Returns the name of the database where all the personal stuff of the user is stored
	 */
	public static function get_user_personal_database() {
		global $_configuration;
		return $_configuration['user_personal_database'];
	}

	/**
	 *	Returns the name of the main Dokeos database.
	 */
	public static function get_current_course_database() {
		$course_info = api_get_course_info();
		return $course_info['dbName'];
	}

	/**
	 *	Returns the glued name of the current course database.
	 */
	public static function get_current_course_glued_database() {
		$course_info = api_get_course_info();
		return $course_info['dbNameGlu'];
	}

	/**
	 *	The glue is the string needed between database and table.
	 *	The trick is: in multiple databases, this is a period (with backticks)
	 *	In single database, this can be e.g. an underscore so we just fake
	 *	there are multiple databases and the code can be written independent
	 *	of the single / multiple database setting.
	 */
	public static function get_database_glue() {
		global $_configuration;
		return $_configuration['db_glue'];
	}

	/**
	 *	Returns the database prefix.
	 *	All created COURSE databases are prefixed with this string.
	 *
	 *	TIP: this can be convenient e.g. if you have multiple Dokeos installations
	 *	on the same physical server.
	 */
	public static function get_database_name_prefix() {
		global $_configuration;
		return $_configuration['db_prefix'];
	}

	/**
	 *	Returns the course table prefix for single database.
	 *	Not certain exactly when this is used.
	 *	Do research.
	 *	It's used in local.inc.php.
	 */
	public static function get_course_table_prefix() {
		global $_configuration;
		return $_configuration['table_prefix'];
	}

	/*
	-----------------------------------------------------------------------------
		Table Name functions
		use these functions to get a table name for queries,
		instead of constructing them yourself.

		Backticks automatically surround the result,
		e.g. `COURSE_NAME`.`link`
		so the queries can look cleaner.

		Example:
		$table = Database::get_course_table(TABLE_DOCUMENT);
		$sql_query = "SELECT * FROM $table WHERE $condition";
		$sql_result = Database::query($sql_query, __FILE__, __LINE__);
		$result = Database::fetch_array($sql_result);
	-----------------------------------------------------------------------------
	*/

	/**
	 * A more generic function than the other get_main_xxx_table functions,
	 * this one can return the correct complete name of any table of the main database of which you pass
	 * the short name as a parameter.
	 * Please define table names as constants in this library and use them
	 * instead of directly using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 */
	public static function get_main_table($short_table_name) {
		return self::format_table_name(self::get_main_database(), $short_table_name);
	}

	/**
	 * A more generic function than the older get_course_xxx_table functions,
	 * this one can return the correct complete name of any course table of which you pass
	 * the short name as a parameter.
	 * Please define table names as constants in this library and use them
	 * instead of directly using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 * @param string $database_name, optional, name of the course database
	 * - if you don't specify this, you work on the current course.
	 */
	public static function get_course_table($short_table_name, $database_name = '') {
		return self::format_glued_course_table_name(self::fix_database_parameter($database_name), $short_table_name);
	}

	/**
	 * Get a complete course table name from a course code
	 *
	 * @param string $course_code
	 * @param string $table the name of the table
	 */
	public static function get_course_table_from_code($course_code, $table) {
		$course_table = self::get_main_table(TABLE_MAIN_COURSE);
		$course_cat_table = self::get_main_table(TABLE_MAIN_CATEGORY);
		$result = self::fetch_array(self::query(
			"SELECT $course_table.db_name, $course_cat_table.code
			FROM $course_table
				LEFT JOIN $course_cat_table
					ON $course_table.category_code =  $course_cat_table.code
			WHERE $course_table.code = '$course_code'
			LIMIT 1", __FILE__, __LINE__));
		return sprintf("%s.%s", $result[0], $table);
    }

	/**
	 * This generic function returns the correct and complete name of any statistic table
	 * of which you pass the short name as a parameter.
	 * Please define table names as constants in this library and use them
	 * instead of directly using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 */
	public static function get_statistic_table($short_table_name) {
		return self::format_table_name(self::get_statistic_database(), $short_table_name);
	}

	/**
	 * This generic function returns the correct and complete name of any scorm
	 * table of which you pass the short name as a parameter. Please define
	 * table names as constants in this library and use them instead of directly
	 * using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 */
	public static function get_scorm_table($short_table_name) {
		return self::format_table_name(self::get_scorm_database(), $short_table_name);
	}

	/**
	 * This generic function returns the correct and complete name of any scorm
	 * table of which you pass the short name as a parameter. Please define
	 * table names as constants in this library and use them instead of directly
	 * using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 */
	public static function get_user_personal_table($short_table_name) {
		return self::format_table_name(self::get_user_personal_database(), $short_table_name);
	}

	// TODO: This method should not belong to Database class. The internationaization ibrary is a better place.
	/**
	 * Returns the isocode corresponding to the language directory given.
	 * @param string $language	This is the name of the folder containing translations for the corresponding language (e.g arabic, english).
	 * If $language is omitted, interface language is assumed then.
	 * @return string			The found isocode or null on error..
	 * Returned codes are according to the following standards (in order of preference):
	 * - ISO 639-1 : Alpha-2 code (two-letters code - en, fr, es, ...)
	 * - RFC 4646  : five-letter code based on the ISO 639 two-letter language codes
	 *   and the ISO 3166 two-letter territory codes (pt-BR, ...)
	 * - ISO 639-2 : Alpha-3 code (three-letters code - ast, fur, ...)
	 */
	public static function get_language_isocode($language) {
		static $iso_code = array();
		if (empty($language)) {
			$language = api_get_interface_language();
		}
		if (!isset($iso_code[$language])) {
			$table = self::get_main_table(TABLE_MAIN_LANGUAGE);
			$sql_result = self::query("SELECT isocode FROM $table WHERE dokeos_folder = '$language'", __FILE__, __LINE__);
			if (self::num_rows($sql_result)) {
				$result = self::fetch_array($sql_result);
				$iso_code[$language] = $result['isocode'];
			} else {
				$iso_code[$language] = null;
			}
		}
		return $iso_code[$language];
	}

	/*
	-----------------------------------------------------------------------------
		Query Functions
		these execute a query and return the result(s).
	-----------------------------------------------------------------------------
	*/

	/**
	 *	@return a list (array) of all courses.
	 * 	@todo shouldn't this be in the course.lib.php script?
	 */
	public static function get_course_list() {
		$table = self::get_main_table(TABLE_MAIN_COURSE);
		return api_store_result(self::query("SELECT * FROM $table", __FILE__, __LINE__));
	}

	/**
	 *	Returns an array with all database fields for the specified course.
	 *
	 *	@param the real (system) code of the course (ID from inside the main course table)
	 * 	@todo shouldn't this be in the course.lib.php script?
	 */
	public static function get_course_info($course_code) {
		$course_code = self::escape_string($course_code);
		$table = self::get_main_table(TABLE_MAIN_COURSE);
		$result = self::generate_abstract_course_field_names(
			self::fetch_array(self::query("SELECT * FROM $table WHERE `code` = '$course_code'", __FILE__, __LINE__)));
		return $result === false ? array('db_name' => '') : $result;
	}

	/**
	 *	@param $user_id (integer): the id of the user
	 *	@return $user_info (array): user_id, lastname, firstname, username, email, ...
	 *	@author Patrick Cool <patrick.cool@UGent.be>, expanded to get info for any user
	 *	@author Roan Embrechts, first version + converted to Database API
	 *	@version 30 September 2004
	 *	@desc find all the information about a specified user. Without parameter this is the current user.
	 * 	@todo shouldn't this be in the user.lib.php script?
	 */
	public static function get_user_info_from_id($user_id = '') {
		if (empty($user_id)) {
			return $GLOBALS['_user'];
		}
		$table = self::get_main_table(TABLE_MAIN_USER);
		$user_id = self::escape_string($user_id);
		return self::generate_abstract_user_field_names(
			self::fetch_array(self::query("SELECT * FROM $table WHERE user_id = '$user_id'", __FILE__, __LINE__)));
	}

	/**
	 *	This creates an abstraction layer between database field names
	 *	and field names expected in code.
	 *
	 *	This helps when changing database names.
	 *	It's also useful now to get rid of the 'franglais'.
	 *
	 *	@todo	add more array entries to abstract course info from field names
	 *	@author	Roan Embrechts
	 *
	 * 	@todo what's the use of this function. I think this is better removed.
	 * 		  There should be consistency in the variable names and the use throughout the scripts
	 * 		  for the database name we should consistently use or db_name or database (db_name probably being the better one)
	 */
	public static function generate_abstract_course_field_names($result_array) {
		$visual_code = isset($result_array['visual_code']) ? $result_array['visual_code'] : null;
		$code        = isset($result_array['code']) ? $result_array['code'] : null;
		$title       = isset($result_array['title']) ? $result_array['title'] : null;
		$db_name     = isset($result_array['db_name']) ? $result_array['db_name'] : null;
		$category_code= isset($result_array['category_code']) ? $result_array['category_code'] : null;
		$result_array['official_code'] = $visual_code;
		$result_array['visual_code']   = $visual_code;
		$result_array['real_code']     = $code;
		$result_array['system_code']   = $code;
		$result_array['title']         = $title;
		$result_array['database']      = $db_name;
		$result_array['faculty']       = $category_code;
		//$result_array['directory'] = $result_array['directory'];
		/*
		still to do: (info taken from local.inc.php)

		$_course['id'          ]         = $cData['cours_id'         ]; //auto-assigned integer
		$_course['name'        ]         = $cData['title'            ];
		$_course['official_code']        = $cData['visual_code'        ]; // use in echo
		$_course['sysCode'     ]         = $cData['code'             ]; // use as key in db
		$_course['path'        ]         = $cData['directory'        ]; // use as key in path
		$_course['dbName'      ]         = $cData['db_name'           ]; // use as key in db list
		$_course['dbNameGlu'   ]         = $_configuration['table_prefix'] . $cData['dbName'] . $_configuration['db_glue']; // use in all queries
		$_course['titular'     ]         = $cData['tutor_name'       ];
		$_course['language'    ]         = $cData['course_language'   ];
		$_course['extLink'     ]['url' ] = $cData['department_url'    ];
		$_course['extLink'     ]['name'] = $cData['department_name'];
		$_course['categoryCode']         = $cData['faCode'           ];
		$_course['categoryName']         = $cData['faName'           ];

		$_course['visibility'  ]         = (bool) ($cData['visibility'] == 2 || $cData['visibility'] == 3);
		$_course['registrationAllowed']  = (bool) ($cData['visibility'] == 1 || $cData['visibility'] == 2);
		*/
		return $result_array;
	}

	/**
	 *	This creates an abstraction layer between database field names
	 *	and field names expected in code.
	 *
	 *	This helps when changing database names.
	 *	It's also useful now to get rid of the 'franglais'.
	 *
	 *	@todo add more array entries to abstract user info from field names
	 *	@author Roan Embrechts
	 *	@author Patrick Cool
	 *
	 * 	@todo what's the use of this function. I think this is better removed.
	 * 		There should be consistency in the variable names and the use throughout the scripts
	 */
	public static function generate_abstract_user_field_names($result_array) {
		$result_array['firstName'] 		= $result_array['firstname'];
		$result_array['lastName'] 		= $result_array['lastname'];
		$result_array['mail'] 			= $result_array['email'];
		#$result_array['picture_uri'] 	= $result_array['picture_uri'];
		#$result_array ['user_id']		= $result_array['user_id'];
		return $result_array;
	}

	/*
	-----------------------------------------------------------------------------
		Private Functions
		You should not access these from outside the class
		No effort is made to keep the names / results the same.
	-----------------------------------------------------------------------------
	*/

	/**
	 *	Glues a course database.
	 *	glue format from local.inc.php.
	 */
	public static function glue_course_database_name($database_name) {
		return self::get_course_table_prefix().$database_name.self::get_database_glue();
	}

	/**
	 *	@param string $database_name, can be empty to use current course db
	 *
	 *	@return the glued parameter if it is not empty,
	 *	or the current course database (glued) if the parameter is empty.
	 */
	public static function fix_database_parameter($database_name) {
		if (empty($database_name)) {
			$course_info = api_get_course_info();
			return $course_info['dbNameGlu'];
		}
		return self::glue_course_database_name($database_name);
	}

	/**
	 *	Structures a course database and table name to ready them
	 *	for querying. The course database parameter is considered glued:
	 *	e.g. COURSE001`.`
	 */
	public static function format_glued_course_table_name($database_name_with_glue, $table) {
		//$course_info = api_get_course_info();
		return '`'.$database_name_with_glue.$table.'`';
	}

	/**
	 *	Structures a database and table name to ready them
	 *	for querying. The database parameter is considered not glued,
	 *	just plain e.g. COURSE001
	 */
	public static function format_table_name($database, $table) {
		return '`'.$database.'`.`'.$table.'`';
	}

	/**
	 * Count the number of rows in a table
	 * @param string $table The table of which the rows should be counted
	 * @return int The number of rows in the given table.
	 */
	public static function count_rows($table) {
		$obj = self::fetch_object(self::query("SELECT COUNT(*) AS n FROM $table", __FILE__, __LINE__));
		return $obj->n;
	}

	/**
	 * Gets the ID of the last item inserted into the database
	 *
	 * @author Yannick Warnier <yannick.warnier@dokeos.com>
	 * @return integer The last ID as returned by the DB function
	 * @comment This should be updated to use ADODB at some point
	 */
	public static function get_last_insert_id() {
		return mysql_insert_id();
	}

	/**
	 * Escapes a string to insert into the database as text
	 * @param string		The string to escape
	 * @return string		The escaped string
	 * @author Yannick Warnier <yannick.warnier@dokeos.com>
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 */
	public static function escape_string($string) {
		return get_magic_quotes_gpc() ? mysql_real_escape_string(stripslashes($string)) : mysql_real_escape_string($string);
	}

	/**
	 * Gets the array from a SQL result (as returned by Database::query) - help achieving database independence
	 * @param resource		The result from a call to sql_query (e.g. Database::query)
	 * @param string		Optional: "ASSOC","NUM" or "BOTH", as the constant used in mysql_fetch_array.
	 * @return array		Array of results as returned by php
	 * @author Yannick Warnier <yannick.warnier@dokeos.com>
	 */
	public static function fetch_array($res, $option = 'BOTH') {
		return $option == 'ASSOC' ? mysql_fetch_array($res, MYSQL_ASSOC) : ($option == 'NUM' ? mysql_fetch_array($res, MYSQL_NUM) : mysql_fetch_array($res));
	}

	/**
	 * Gets the next row of the result of the SQL query (as returned by Database::query) in an object form
	 * @param	resource	The result from a call to sql_query (e.g. Database::query)
	 * @param	string		Optional class name to instanciate
	 * @param	array		Optional array of parameters
	 * @return	object		Object of class StdClass or the required class, containing the query result row
	 * @author	Yannick Warnier <yannick.warnier@dokeos.com>
	 */
	public static function fetch_object($res, $class = null, $params = null) {
		return !empty($class) ? (is_array($params) ? mysql_fetch_object($res, $class, $params) : mysql_fetch_object($res,$class)) : mysql_fetch_object($res);
	}

	/**
	 * Gets the array from a SQL result (as returned by Database::query) - help achieving database independence
	 * @param resource		The result from a call to sql_query (e.g. Database::query)
	 * @return array		Array of results as returned by php (mysql_fetch_row)
	 * @author Yannick Warnier <yannick.warnier@dokeos.com>
	 */
	public static function fetch_row($res) {
		return mysql_fetch_row($res);
	}

	/**
	 * Gets the number of rows from the last query result - help achieving database independence
	 * @param resource		The result
	 * @return integer		The number of rows contained in this result
	 * @author Yannick Warnier <yannick.warnier@dokeos.com>
	 **/
	public static function num_rows($res) {
		return mysql_num_rows($res);
	}

	public static function get_course_chat_connected_table($database_name = '') {
		return self::format_glued_course_table_name(self::fix_database_parameter($database_name), CHAT_CONNECTED_TABLE);
	}

	/**
	 * Acts as the relative *_result() function of most DB drivers and fetches a
	 * specific line and a field
	 * @param	resource	The database resource to get data from
	 * @param	integer		The row number
	 * @param	string		Optional field name or number
	 * @result	mixed		One cell of the result, or FALSE on error
	 */
	public static function result($resource, $row, $field = '') {
		return mysql_num_rows($resource) > 0 ? (!empty($field) ? mysql_result($resource, $row, $field) : mysql_result($resource, $row)) : null;
	}

	/**
	 * Recovers the last ID of any insert query executed over this SQL connection
	 * @return	integer	Unique ID of the latest inserted row
	 */
	public static function insert_id() {
		return mysql_insert_id();
	}

	/**
	 * Returns the number of affected rows
	 * @param	resource	Optional database resource
	 */
	public static function affected_rows($r = null) {
		return is_null($r) ? mysql_affected_rows($r) : mysql_affected_rows();
	}

	/**
	 * This function returns a resource
	 * documentation has been added by Arthur Portugal
	 * @author Olivier Brouckaert;
	 * @param string $query - SQL query
	 * @param string $file - optional, the file path and name of the error (__FILE__)
	 * @param string $line - optional, the line of the error (__LINE__)
	 * @return resource - the return value of the query
	 */

	public static function query($sql, $file = '', $line = 0) {
		return api_sql_query($sql, $file, $line);
	}

	public static function error() {
		return mysql_error();
	}

	/**
	 * Return course code from one given gradebook category's id
	 * @param int  Category ID
	 * @return string  Course code
	 * @todo move this function in a gradebook-related library
	 */
    public static function get_course_by_category($category_id) {
		$info = self::fetch_array(self::query('SELECT course_code FROM '.self::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY).' WHERE id='.$category_id, __FILE__, __LINE__), 'ASSOC');
		return $info ? $info['course_code'] : false;
    }

}
//end class Database
