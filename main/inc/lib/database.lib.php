<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) Patrick Cool, Ghent University
	Copyright (c) Yannick Warnier, Dokeos S.A.
	Copyright (c) Bart Mollet, Hogeschool Gent
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	This is the main database library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
============================================================================== 
*/
/*
============================================================================== 
		CONSTANTS
============================================================================== 
*/
//main database tables
define("MAIN_COURSE_TABLE", "course");
define("MAIN_USER_TABLE", "user");
define("MAIN_CLASS_TABLE", "class");
define("MAIN_ADMIN_TABLE", "admin");
define("MAIN_COURSE_CLASS_TABLE", "course_rel_class");
define("MAIN_COURSE_USER_TABLE", "course_rel_user");
define("MAIN_CLASS_USER_TABLE", "class_user");
define("MAIN_CATEGORY_TABLE", "course_category");
define("MAIN_COURSE_MODULE_TABLE", "course_module");
define("MAIN_SYSTEM_ANNOUNCEMENTS_TABLE", "sys_announcement");
define("MAIN_LANGUAGE_TABLE", "language");
define("MAIN_SETTINGS_OPTIONS_TABLE", "settings_options");
define("MAIN_SETTINGS_CURRENT_TABLE", "settings_current");
define("MAIN_SETTINGS_SERVICE_TABLE", "settings_service");
define("MAIN_SESSION_TABLE", "session");
define("MAIN_SESSION_COURSE_TABLE", "session_rel_course");
define("MAIN_SESSION_USER_TABLE", "session_rel_user");
define("MAIN_SESSION_CLASS_TABLE", "session_rel_class");
define("MAIN_SESSION_COURSE_USER_TABLE", "session_rel_course_rel_user");
define("MAIN_COURSE_SURVEY_TABLE", "course_rel_survey");
define("MAIN_SURVEY_USER_TABLE", "survey_user_info");
define("MAIN_SURVEY_REMINDER_TABLE","survey_reminder");
//statistic database tables
define("STATISTIC_TRACK_E_LASTACCESS_TABLE", "track_e_lastaccess");
define("STATISTIC_TRACK_E_ACCESS_TABLE", "track_e_access");
define("STATISTIC_TRACK_E_LOGIN_TABLE", "track_e_login");
define("STATISTIC_TRACK_E_DOWNLOADS_TABLE", "track_e_downloads");
define("STATISTIC_TRACK_E_LINKS_TABLE", "track_e_links");
define("STATISTIC_TRACK_E_ONLINE_TABLE", "track_e_online");
define("STATISTIC_TRACK_E_HOTPOTATOES_TABLE", "track_e_hotpotatoes");
define("STATISTIC_TRACK_E_COURSE_ACCESS_TABLE", "track_e_course_access");
//scorm database tables
define("SCORM_MAIN_TABLE", "scorm_main");
define("SCORM_SCO_DATA_TABLE", "scorm_sco_data");
//course tables
define("AGENDA_TABLE", "calendar_event");
define("ANNOUNCEMENT_TABLE", "announcement");
define("CHAT_CONNECTED_TABLE", "chat_connected");
define("COURSE_DESCRIPTION_TABLE", "course_description");
define("DOCUMENT_TABLE", "document");
define("LAST_TOOL_EDIT_TABLE", "item_property");
define("LINK_TABLE", "link");
define("LINK_CATEGORY_TABLE", "link_category");
define("TOOL_LIST_TABLE", "tool");
define("TOOL_INTRO_TABLE", "tool_intro");
define("SCORMDOC_TABLE", "scormdocument");
define("STUDENT_PUBLICATION_TABLE", "student_publication");
//course forum tables
define("TOOL_FORUM_CATEGORY_TABLE",'forum_category');
define("TOOL_FORUM_TABLE",'forum_forum');
define("TOOL_FORUM_THREAD_TABLE",'forum_thread');
define("TOOL_FORUM_POST_TABLE",'forum_post');
//course group tables
define("GROUP_TABLE", "group_info");
define("GROUP_USER_TABLE", "group_rel_user");
define("GROUP_TUTOR_TABLE", "group_rel_tutor");
define("GROUP_CATEGORY_TABLE", "group_category");
//course quiz tables
define("QUIZ_QUESTION_TABLE", "quiz_question");
define("QUIZ_TEST_TABLE", "quiz");
define("QUIZ_ANSWER_TABLE", "quiz_answer");
define("QUIZ_TEST_QUESTION_TABLE", "quiz_rel_question");
//linked resource table
define("LINKED_RESOURCES_TABLE", "resource");
//learnpath tables
define("LEARNPATH_MAIN_TABLE", "learnpath_main");
define("LEARNPATH_CHAPTER_TABLE", "learnpath_chapter");
define("LEARNPATH_ITEM_TABLE", "learnpath_item");
define("LEARNPATH_USER_TABLE", "learnpath_user");
// Smartblogs (Kevin Van Den Haute::kevin@develop-it.be)
// permission tables
define('PERMISSION_USER_TABLE', 'permission_user');
define('PERMISSION_TASK_TABLE', 'permission_task');
define('PERMISSION_GROUP_TABLE', 'permission_group');
// roles tables
define('ROLE_TABLE', 'role');
define('ROLE_PERMISSION_TABLE', 'role_permissions');
define('ROLE_USER_TABLE', 'role_user');
define('ROLE_GROUP_TABLE', 'role_group');
// blogs tables
define('BLOGS_TABLE', 'blogs');
define('BLOGS_POSTS_TABLE', 'blogs_posts');
define('BLOGS_COMMENTS_TABLE', 'blogs_comments');
define('BLOGS_REL_USER_TABLE', 'blogs_rel_user');
define('BLOGS_TASKS', 'blogs_tasks');
define('BLOGS_TASKS_REL_USER', 'blogs_tasks_rel_user');
define('BLOGS_RATING', 'blogs_rating');
define('BLOGS_TASKS_PERMISSIONS', 'permission_task');
//end of Smartblogs
// user information tables
define('USER_INFO_TABLE', 'userinfo_def');
define('USER_INFO_CONTENT_TABLE', 'userinfo_content');
// item property table
define('ITEM_PROPERTY_TABLE', 'item_property');
// course settings table
define('COURSE_SETTING_TABLE', 'course_setting');
// course online tables
define('ONLINE_LINK_TABLE', 'online_link');
define('ONLINE_CONNECTED_TABLE', 'online_connected');
// dokeos_user database
define("PERSONAL_AGENDA", "personal_agenda");
define("USER_COURSE_CATEGORY_TABLE", "user_course_category");
//Survey
define("MAIN_SURVEY_TABLE", "survey");
define("MAIN_GROUP_TABLE", "survey_group");
define("MAIN_SURVEYQUESTION_TABLE", "questions");
/*
============================================================================== 
		DATABASE CLASS
		the class and its functions
============================================================================== 
*/
/**
 *	@package dokeos.library
 */
class Database
{
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
	function get_main_database()
	{
		return $GLOBALS["mainDbName"];
	}
	/**
	*	Returns the name of the Dokeos statistics database.
	*/
	function get_statistic_database()
	{
		return $GLOBALS["statsDbName"];
	}
	/**
	*	Returns the name of the Dokeos SCORM database.
	*/
	function get_scorm_database()
	{
		return $GLOBALS["scormDbName"];
	}
	/**
	*	Returns the name of the database where all the personal stuff of the user is stored
	*/
	function get_user_personal_database()
	{
		return $GLOBALS["user_personal_database"];
	}
	/**
	*	Returns the name of the main Dokeos database.
	*/
	function get_current_course_database()
	{
		$course_info = api_get_course_info();
		return $course_info["dbName"];
	}
	/**
	*	Returns the glued name of the current course database.
	*/
	function get_current_course_glued_database()
	{
		$course_info = api_get_course_info();
		return $course_info["dbNameGlu"];
	}
	/**
	*	The glue is the string needed between database and table.
	*	The trick is: in multiple databases, this is a period (with backticks)
	*	In single database, this can be e.g. an underscore so we just fake
	*	there are multiple databases and the code can be written independent
	*	of the single / multiple database setting.
	*/
	function get_database_glue()
	{
		return $GLOBALS["dbGlu"];
	}
	/**
	*	Returns the database prefix.
	*	All created COURSE databases are prefixed with this string.
	*	
	*	TIP: this can be convenient e.g. if you have multiple Dokeos installations
	*	on the same physical server.
	*/
	function get_database_name_prefix()
	{
		return $GLOBALS["dbNamePrefix"];
	}
	/**
	*	Returns the course table prefix for single database.
	*	Not certain exactly when this is used.
	*	Do research.
	*	It's used in local.inc.php.
	*/
	function get_course_table_prefix()
	{
		return $GLOBALS["courseTablePrefix"];
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
		$table = Database::get_course_document_table();
		$sql_query = "SELECT * FROM $table WHERE $condition";
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
		$result = mysql_fetch_array($sql_result);
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
	function get_main_table($short_table_name)
	{
		$database = Database::get_main_database();
		return Database::format_table_name($database, $short_table_name);
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
	function get_course_table($short_table_name, $database_name = '')
	{
		$database_name_with_glue = Database::fix_database_parameter($database_name);
		return Database::format_glued_course_table_name($database_name_with_glue, $short_table_name);
	}
	/**
	 * This generic function returns the correct and complete name of any statistic table
	 * of which you pass the short name as a parameter. 
	 * Please define table names as constants in this library and use them
	 * instead of directly using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 */
	function get_statistic_table($short_table_name)
	{
		$database = Database::get_statistic_database();
		return Database::format_table_name($database, $short_table_name);
	}
	/**
	 * This generic function returns the correct and complete name of any scorm
	 * table of which you pass the short name as a parameter. Please define
	 * table names as constants in this library and use them instead of directly
	 * using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 */
	function get_scorm_table($short_table_name)
	{
		$database = Database::get_scorm_database();
		return Database::format_table_name($database, $short_table_name);
	}
	/**
	 * This generic function returns the correct and complete name of any scorm
	 * table of which you pass the short name as a parameter. Please define
	 * table names as constants in this library and use them instead of directly
	 * using magic words in your tool code.
	 *
	 * @param string $short_table_name, the name of the table
	 */
	function get_user_personal_table($short_table_name)
	{
		$database = Database::get_user_personal_database();
		return Database::format_table_name($database, $short_table_name);
	}
	/**
	*	Returns the isocode corresponding to the language directory given.
	*/
	function get_language_isocode($lang_folder)
	{
		$table = Database::get_main_table(MAIN_LANGUAGE_TABLE);
		$sql_query = "SELECT isocode FROM $table WHERE dokeos_folder = '$lang_folder'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		$result = $result['isocode'];
		return $result;
	}
	/**
	* Returns the name of the login table of the stats database.
	* @deprecated use Database::get_statistic_table(STATISTIC_TRACK_E_LOGIN_TABLE);
	*/
	function get_statistic_track_e_login_table()
	{
		return Database::get_statistic_table(STATISTIC_TRACK_E_LOGIN_TABLE);
	}
	/**
	* Returns the name of the main table of the SCORM database.
	* @deprecated use Database::get_scorm_table(SCORM_MAIN_TABLE);
	*/
	function get_scorm_main_table()
	{
		return Database::get_scorm_table(SCORM_MAIN_TABLE);
	}
	/**
	* Returns the name of the data table of the SCORM database.
	* @deprecated use Database::get_scorm_table(SCORM_SCO_DATA_TABLE);
	*/
	function get_scorm_sco_data_table()
	{
		return Database::get_scorm_table(SCORM_SCO_DATA_TABLE);
	}
	
	/*
	-----------------------------------------------------------------------------
		Query Functions
		these execute a query and return the result(s).
	-----------------------------------------------------------------------------
	*/
	
	/**
	*	@return a list (array) of all courses.
	*/
	function get_course_list()
	{
		$table = Database::get_main_table(MAIN_COURSE_TABLE);
		$sql_query = "SELECT * FROM $table";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = api_store_result($sql_result);
		return $result;
	}
	/**
	* @return a list (array) of all users.
	* @deprecated This function isn't used anywhere in the code.
	*/
	function get_user_list()
	{
		$table = Database::get_main_table(MAIN_USER_TABLE);
		$sql_query = "SELECT * FROM $table";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = api_store_result($sql_result);
		return $result;
	}
	
	/**
	*	Returns an array with all database fields for the specified course.
	*
	*	@param the real (system) code of the course (key of the main course table)
	*/
	function get_course_info($course_code)
	{
		$table = Database::get_main_table(MAIN_COURSE_TABLE);
		$sql_query = "SELECT * FROM $table WHERE `code` = '$course_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		$result = Database::generate_abstract_course_field_names($result);
		return $result;
	}
	/**
	*	@param $user_id (integer): the id of the user
	*	@return $user_info (array): user_id, lastname, firstname, username, email, ...
	*	@author Patrick Cool <patrick.cool@UGent.be>, expanded to get info for any user
	*	@author Roan Embrechts, first version + converted to Database API
	*	@version 30 September 2004
	*	@desc find all the information about a specified user. Without parameter this is the current user.
	*/
	function get_user_info_from_id($user_id = '')
	{
		$table = Database::get_main_table(MAIN_USER_TABLE);
		if ($user_id == '')
		{
			return $GLOBALS["_user"];
		}
		else
		{
			$sql_query = "SELECT * FROM $table WHERE `user_id` = '$user_id'";
			$result = api_sql_query($sql_query, __FILE__, __LINE__);
			$result_array = mysql_fetch_array($result);
			$result_array = Database::generate_abstract_user_field_names($result_array);
			return $result_array;
		}
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
	*/
	function generate_abstract_course_field_names($result_array)
	{
		$result_array["official_code"] = $result_array["visual_code"];
		$result_array["visual_code"] = $result_array["visual_code"];
		$result_array["real_code"] = $result_array["code"];
		$result_array["system_code"] = $result_array["code"];
		$result_array["title"] = $result_array['title'];
		$result_array["database"] = $result_array["db_name"];
		$result_array["faculty"] = $result_array["category_code"];
		//$result_array["directory"] = $result_array["directory"];
		/*
		still to do: (info taken from local.inc.php)
		
		$_course['id'          ]         = $cData['cours_id'         ]; //auto-assigned integer
		$_course['name'        ]         = $cData['title'            ];
		$_course['official_code']        = $cData['visual_code'        ]; // use in echo
		$_course['sysCode'     ]         = $cData['code'             ]; // use as key in db
		$_course['path'        ]         = $cData['directory'        ]; // use as key in path
		$_course['dbName'      ]         = $cData['db_name'           ]; // use as key in db list
		$_course['dbNameGlu'   ]         = $courseTablePrefix . $cData['dbName'] . $dbGlu; // use in all queries
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
	*/
	function generate_abstract_user_field_names($result_array)
	{
		$result_array['firstName'] 		= $result_array['firstname'];
		$result_array['lastName'] 		= $result_array['lastname'];
		$result_array['mail'] 			= $result_array['email'];
		#$result_array['picture_uri'] 	= $result_array['picture_uri'];
		#$result_array ['user_id'  ] 	= $result_array['user_id'   ];
		return $result_array;
	}
	
	
	/**
	* Changes the title of a course.
	* @deprecated Function not in use
	*/
	function update_course_title($course_code, $new_title)
	{
		$table = Database::get_main_table(MAIN_COURSE_TABLE);
		$sql_query = "UPDATE $table SET `title` = '$new_title' WHERE `code` = '$course_code' LIMIT 1";
		api_sql_query($sql_query, __FILE__, __LINE__);
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
	function glue_course_database_name($database_name)
	{
		$prefix = Database::get_course_table_prefix();
		$glue = Database::get_database_glue();
		$database_name_with_glue = $prefix.$database_name.$glue;
		return $database_name_with_glue;
	}
	/**
	*	@param string $database_name, can be empty to use current course db
	*
	*	@return the glued parameter if it is not empty,
	*	or the current course database (glued) if the parameter is empty.
	*/
	function fix_database_parameter($database_name)
	{
		if ($database_name == '')
		{
			$course_info = api_get_course_info();
			$database_name_with_glue = $course_info["dbNameGlu"];
		}
		else
		{
			$database_name_with_glue = Database::glue_course_database_name($database_name);
		}
		return $database_name_with_glue;
	}
	/**
	*	Structures a course database and table name to ready them
	*	for querying. The course database parameter is considered glued:
	*	e.g. COURSE001`.`
	*/
	function format_glued_course_table_name($database_name_with_glue, $table)
	{
		$course_info = api_get_course_info();
		return "`".$database_name_with_glue.$table."`";
	}
	/**
	*	Structures a database and table name to ready them
	*	for querying. The database parameter is considered not glued,
	*	just plain e.g. COURSE001
	*/
	function format_table_name($database, $table)
	{
		return "`".$database."`.`".$table."`";
	}
	/**
	 * Count the number of rows in a table
	 * @param string $table The table of which the rows should be counted
	 * @return int The number of rows in the given table.
	 */
	function count_rows($table)
	{
		$sql = "SELECT COUNT(*) AS n FROM $table";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = mysql_fetch_object($res);
		return $obj->n;
	}
	/**
	 * Gets the ID of the last item inserted into the database
	 *
	 * @author Yannick Warnier <yannick.warnier@dokeos.com>
	 * @return integer The last ID as returned by the DB function
	 * @comment This should be updated to use ADODB at some point
	 */
	function get_last_insert_id()
	{
		return mysql_insert_id();
	}
	/**
	 * Gets the array from a SQL result (as returned by api_sql_query) - help achieving database independence
	 * @param     resource    The result from a call to sql_query (e.g. api_sql_query)
	 * @param     string      Optional: "ASSOC","NUM" or "BOTH", as the constant used in mysql_fetch_array.
	 * @return    array       Array of results as returned by php
	 * @author    Yannick Warnier <yannick.warnier@dokeos.com>
	 */
	function fetch_array($res, $option = 'BOTH')
	{
		if ($option != 'BOTH')
		{
			if ($option == 'ASSOC')
			{
				return mysql_fetch_array($res, MYSQL_ASSOC);
			}
			elseif ($option == 'NUM')
			{
				return mysql_fetch_array($res, MYSQL_NUM);
			}
		}
		else
		{
			return mysql_fetch_array($res);
		}
	}
	/**
	 * Gets the number of rows from the last query result - help achieving database independence
	 * @param   resource    The result
	 * @return  integer     The number of rows contained in this result
	 * @author  Yannick Warnier <yannick.warnier@dokeos.com>
	 **/
	function num_rows($res)
	{
		return mysql_num_rows($res);
	}
	
	/**
	*	Returns the name of the tool table of a course.
	*	If no database parameter is present, the function works on the current course.
	*/
	function get_course_tool_list_table($database_name = '')
	{
		$database_name_with_glue = Database::fix_database_parameter($database_name);
		
		return Database::format_glued_course_table_name($database_name_with_glue, TOOL_LIST_TABLE);
	}
}
//end class Database
?>