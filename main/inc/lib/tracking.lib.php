<?php
/* For licensing terms, see /license.txt */
/**
 *    This is the tracking library for Chamilo
 *    Include/require it in your code to use its functionality.
 *
 *    @package chamilo.library
 *    @author Julio Montoya <gugli100@gmail.com> (Score average fixes)
 */
/**
 * Code
 */
define('SESSION_LINK_TARGET','_self');
/**
 * Class
 * @package chamilo.library
 */
class Tracking {

	/**
	 * Calculates the time spent on the platform by a user
	 * @param int    User id
	 * @param bool    True for calculating only time spent last week (optional)
	 * @param int    Timestamp for filtering by Day (optional, default = 0)
	 * @return timestamp $nb_seconds
	 */
	public static function get_time_spent_on_the_platform($user_id, $last_week = false, $by_day = 0) {

		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

		$condition_time = '';
		if ($last_week) {
			$a_last_week = get_last_week();
			$fday_last_week = date('Y-m-d H:i:s',$a_last_week[0]);
			$lday_last_week = date('Y-m-d H:i:s',$a_last_week[6]);
			$condition_time = ' AND (login_date >= "'.$fday_last_week.'" AND logout_date <= "'.$lday_last_week.'") ';
		} else if (!empty($by_day)) {
			$fdate_time = date('Y-m-d',$by_day).' 00:00:00';
			$ldate_time = date('Y-m-d',$by_day).' 23:59:59';
			$condition_time = ' AND (login_date >= "'.$fdate_time.'" AND logout_date <= "'.$ldate_time.'" ) ';
		}

		$sql = 'SELECT login_date, logout_date FROM '.$tbl_track_login.'
                        WHERE login_user_id = '.intval($user_id).$condition_time;
		$rs = Database::query($sql);

		$nb_seconds = 0;

		$wrong_logout_dates = false;

		while ($a_connections = Database::fetch_array($rs)) {

			$s_login_date = $a_connections["login_date"];
			$s_logout_date = $a_connections["logout_date"];

			$i_timestamp_login_date = strtotime($s_login_date);
			$i_timestamp_logout_date = strtotime($s_logout_date);

			if ($i_timestamp_logout_date > 0) {
				// @TODO YW 20110708: for some reason the result here is often
				// negative, resulting in a negative time total. Considering the
				// logout_date is > 0, this can only mean that the database
				// contains items where the login_date is higher (=later) than
				// the logout date for a specific connexion. This has to be
				// analyzed and fixed. Also see the get_time_spent_on_the_course
				// for SQL summing.
				$nb_seconds += abs($i_timestamp_logout_date - $i_timestamp_login_date);
			} else { // there are wrong datas in db, then we can't give a wrong time
				$wrong_logout_dates = true;
			}

		}

		if($nb_seconds>0 || !$wrong_logout_dates)
		{
			return $nb_seconds;
		}
		else
		{
			return -1; //-1 means we have wrong datas in the db
		}
	}

	/**
	 * Calculates the time spent on the course
	 * @param     integer     User id
	 * @param     string         Course code
	 * @param     int            Session id (optional)
	 * @return     timestamp     Time in seconds
	 */
	public static function get_time_spent_on_the_course($user_id, $course_code, $session_id = 0) {
		// protect datas
		$course_code = Database::escape_string($course_code);
		$session_id  = intval($session_id);

		$tbl_track_course = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

		$condition_user = "";
		if (is_array($user_id)) {
			$condition_user = " AND user_id IN (".implode(',',$user_id).") ";
		} else {
			$user_id = intval($user_id);
			$condition_user = " AND user_id = '$user_id' ";
		}

		$sql = " SELECT SUM(UNIX_TIMESTAMP(logout_course_date)-UNIX_TIMESTAMP(login_course_date)) as nb_seconds
                FROM $tbl_track_course
                WHERE course_code='$course_code' AND session_id = '$session_id' $condition_user";

		$rs = Database::query($sql);
		$row = Database::fetch_array($rs);
		return $row['nb_seconds'];
	}

	/**
	 * Get first connection date for a student
	 * @param    int                  Student id
	 * @return    string|bool     Date format long without day or false if there are no connections
	 */
	public static function get_first_connection_date($student_id) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql = 'SELECT login_date FROM ' . $tbl_track_login . '
                WHERE login_user_id = ' . intval($student_id) . '
                ORDER BY login_date ASC LIMIT 0,1';

		$rs = Database::query($sql);
		if(Database::num_rows($rs)>0) {
			if ($first_login_date = Database::result($rs, 0, 0)) {
				return api_convert_and_format_date($first_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
			}
		}
		return false;
	}


	/**
	 * Get las connection date for a student
	 * @param    int                  Student id
	 * @param    bool            Show a warning message (optional)
	 * @param    bool            True for returning results in timestamp (optional)
	 * @return    string|int|bool Date format long without day, false if there are no connections or timestamp if parameter $return_timestamp is true
	 */
	public static function get_last_connection_date($student_id, $warning_message = false, $return_timestamp = false) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql = 'SELECT login_date FROM ' . $tbl_track_login . '
                        WHERE login_user_id = ' . intval($student_id) . '
                        ORDER BY login_date DESC LIMIT 0,1';

		$rs = Database::query($sql);
		if(Database::num_rows($rs)>0) {
			if ($last_login_date = Database::result($rs, 0, 0)) {
				$last_login_date = api_get_local_time($last_login_date);
				if ($return_timestamp) {
					return api_strtotime($last_login_date,'UTC');
				} else {
					if (!$warning_message) {
						return api_format_date($last_login_date, DATE_FORMAT_SHORT);
					} else {
						$timestamp = api_strtotime($last_login_date,'UTC');
						$currentTimestamp = time();

						//If the last connection is > than 7 days, the text is red
						//345600 = 7 days in seconds
						if ($currentTimestamp - $timestamp > 604800)
						{
							return '<span style="color: #F00;">' . api_format_date($last_login_date, DATE_FORMAT_SHORT) . '</span>';
						}
						else
						{
							return api_format_date($last_login_date, DATE_FORMAT_SHORT);
						}
					}
				}
			}
		}
		return false;
	}


	/**
	 * Get first user's connection date on the course
	 * @param     int         User id
	 * @param    string        Course code
	 * @param    int            Session id (optional, default=0)
	 * @return    string|bool    Date with format long without day or false if there is no date
	 */
	public static function get_first_connection_date_on_the_course($student_id, $course_code, $session_id = 0, $convert_date = true) {

		// protect data
		$student_id  = intval($student_id);
		$course_code = Database::escape_string($course_code);
		$session_id  = intval($session_id);

		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = 'SELECT login_course_date FROM '.$tbl_track_login.'
                        WHERE user_id = '.$student_id.'
                        AND course_code = "'.$course_code.'"
                        AND session_id = '.$session_id.'
                        ORDER BY login_course_date ASC LIMIT 0,1';
		$rs = Database::query($sql);
		if (Database::num_rows($rs)>0) {
			if ($first_login_date = Database::result($rs, 0, 0)) {
				if ($convert_date) {
					return api_convert_and_format_date($first_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
				} else {
					return $first_login_date;
				}
			}
		}
		return false;
	}

	/**
	 * Get last user's connection date on the course
	 * @param     int         User id
	 * @param    string        Course code
	 * @param    int            Session id (optional, default=0)
	 * @return    string|bool    Date with format long without day or false if there is no date
	 */
	public static function get_last_connection_date_on_the_course($student_id, $course_code, $session_id = 0, $convert_date = true) {

		// protect data
		$student_id  = intval($student_id);
		$course_code = Database::escape_string($course_code);
		$session_id  = intval($session_id);

		$tbl_track_e_course_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = 'SELECT login_course_date FROM '.$tbl_track_e_course_access.'
                        WHERE user_id = '.$student_id.'
                        AND course_code = "'.$course_code.'"
                        AND session_id = '.$session_id.'
                        ORDER BY login_course_date DESC LIMIT 0,1';

		$rs = Database::query($sql);
		if (Database::num_rows($rs)>0) {
			if ($last_login_date = Database::result($rs, 0, 0)) {
				$last_login_date = api_get_local_time($last_login_date, null, date_default_timezone_get());
				$timestamp = api_strtotime($last_login_date);
				$currentTimestamp = time();
				//If the last connection is > than 7 days, the text is red
				//345600 = 7 days in seconds
				if ($currentTimestamp - $timestamp > 604800) {
					if ($convert_date) {
						return '<span style="color: #F00;">' . api_format_date($last_login_date, DATE_FORMAT_SHORT) . (api_is_allowed_to_edit()?' <a href="'.api_get_path(REL_CODE_PATH).'announcements/announcements.php?action=add&remind_inactive='.$student_id.'" title="'.get_lang('RemindInactiveUser').'"><img align="middle" src="'.api_get_path(WEB_IMG_PATH).'messagebox_warning.gif" /></a>':'').'</span>';
					} else {
						return $last_login_date;
					}
				} else {
					if ($convert_date) {
						return api_format_date($last_login_date, DATE_FORMAT_SHORT);
					} else {
						return $last_login_date;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Get count of the connections to the course during a specified period
	 * @param   string  Course code
	 * @param   int     Session id (optional)
	 * @param   int     Datetime from which to collect data (defaults to 0)
	 * @param   int     Datetime to which to collect data (defaults to now)
	 * @return  int     count connections
	 */
	public static function get_course_connections_count($course_code, $session_id = 0, $start = 0, $stop = null) {

		// protect data
		$month_filter = '';
		if ($start < 0) {
			$start = 0;
		}
		if (!isset($stop) or ($stop < 0)) {
			$stop = api_get_utc_datetime();
		}
		$month_filter = " AND login_course_date > '$start' AND login_course_date < '$stop' ";

		$course_code = Database::escape_string($course_code);
		$session_id  = intval($session_id);
		$count = 0;

		$tbl_track_e_course_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = "SELECT count(*) as count_connections FROM $tbl_track_e_course_access WHERE course_code = '$course_code' AND session_id = $session_id $month_filter";
		$rs = Database::query($sql);
		if (Database::num_rows($rs)>0) {
			$row = Database::fetch_object($rs);
			$count = $row->count_connections;
		}
		return $count;
	}

	/**
	 * Get count courses per student
	 * @param     int        Student id
	 * @param    bool    Include sessions (optional)
	 * @return  int        count courses
	 */
	public static function count_course_per_student($user_id, $include_sessions = true) {

		$user_id = intval($user_id);
		$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$sql = 'SELECT DISTINCT course_code
                        FROM ' . $tbl_course_rel_user . '
                        WHERE user_id = ' . $user_id.' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH;
		$rs = Database::query($sql);
		$nb_courses = Database::num_rows($rs);

		if ($include_sessions) {
			$sql = 'SELECT DISTINCT course_code
                            FROM ' . $tbl_session_course_rel_user . '
                            WHERE id_user = ' . $user_id;
			$rs = Database::query($sql);
			$nb_courses += Database::num_rows($rs);
		}

		return $nb_courses;
	}

	/**
	 * Gets the score average from all tests in a course by student
	 *
	 * @param    mixed		Student(s) id
	 * @param    string 	Course code
	 * @param    int    	Exercise id (optional), filtered by exercise
	 * @param    int    	Session id (optional), if param $session_id is null it'll return results including sessions, 0 = session is not filtered
	 * @return   string 	value (number %) Which represents a round integer about the score average.
	 */
	public static function get_avg_student_exercise_score($student_id, $course_code, $exercise_id = 0, $session_id = null) {

		// protect datas
		$course_code = Database::escape_string($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		if (!empty($a_course)) {
			// table definition
			$tbl_course_quiz = Database::get_course_table(TABLE_QUIZ_TEST);
			$tbl_stats_exercise = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);

			// Compose a filter based on optional exercise given
			$condition_quiz = "";
			if (!empty($exercise_id)) {
				$exercise_id = intval($exercise_id);
				$condition_quiz =" AND id = $exercise_id ";
			}

			// Compose a filter based on optional session id given
			$condition_session = "";
			if (isset($session_id)) {
				$session_id = intval($session_id);
				$condition_session = " AND session_id = $session_id ";
			}
			$sql = "SELECT count(id) FROM $tbl_course_quiz 
					WHERE c_id = {$a_course['real_id']} AND active <> -1 $condition_quiz ";			
			$count_quiz = Database::fetch_row(Database::query($sql));

			$quiz_avg_total_score = 0;
			if (!empty($count_quiz[0]) && !empty($student_id)) {
				$condition_user = "";
				if (is_array($student_id)) {
					$condition_user = " AND exe_user_id IN (".implode(',',$student_id).") ";
				} else {
					$condition_user = " AND exe_user_id = '$student_id' ";
				}

				if (empty($exercise_id)) {
					$sql = "SELECT id FROM $tbl_course_quiz 
							WHERE c_id = {$a_course['real_id']} AND active <> -1 $condition_quiz";
					$exercises = Database::fetch_row(Database::query($sql));
					$exercise_list = array();
					$exercise_id = 0;
					if (!empty($exercises)) {
						foreach($exercises as $row) {
							$exercise_list[] = $row['id'];
						}
						$exercise_id = implode("','",$exercise_list);
					}
				}

				$count_quiz = Database::fetch_row(Database::query($sql));

				$sql = "SELECT SUM(exe_result/exe_weighting*100) as avg_score, COUNT(*) as num_attempts
                        FROM $tbl_stats_exercise
                        WHERE exe_exo_id IN ('".$exercise_id."')
				$condition_user
                        AND orig_lp_id = 0
                        AND exe_cours_id = '$course_code'
                        AND orig_lp_item_id = 0 $condition_session
                        ORDER BY exe_date DESC";

				$res = Database::query($sql);
				$row = Database::fetch_array($res);
				$quiz_avg_score = 0;
				if (!empty($row['avg_score'])) {
					$quiz_avg_score = round($row['avg_score'],2);
				}
				if(!empty($row['num_attempts'])) {
					$quiz_avg_score = round($quiz_avg_score / $row['num_attempts'], 2);
				}
				if (is_array($student_id)) {
					$quiz_avg_score = round($quiz_avg_score / count($student_id), 2);
				}
				return $quiz_avg_score;
			}
		}
		return null;
	}


	/**
	 * Get count student's exercise attempts
	 * @param     int     Student id
	 * @param    string    Course code
	 * @param    int        Exercise id
	 * @param    int        Learning path id (optional), for showing attempts inside a learning path $lp_id and $lp_item_id params are required.
	 * @param    int        Learning path item id (optional), for showing attempts inside a learning path $lp_id and $lp_item_id params are required.
	 * @return  int     count of attempts
	 */
	public function count_student_exercise_attempts($student_id, $course_code, $exercise_id, $lp_id = 0, $lp_item_id = 0, $session_id = 0) {

		$course_code = Database::escape_string($course_code);
		$course_info = CourseManager :: get_course_information($course_code);
		$student_id  = intval($student_id);
		$exercise_id = intval($exercise_id);
		$session_id = intval($session_id);
		$count_attempts = 0;

		if (!empty($lp_id)) $lp_id = intval($lp_id);
		if (!empty($lp_item_id)) $lp_id = intval($lp_item_id);

		if (!empty($course_info['db_name'])) {
			$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES, $course_info['db_name']);

			$sql = "SELECT COUNT(ex.exe_id) as essais FROM $tbl_stats_exercices AS ex
                    WHERE  ex.exe_cours_id = '$course_code'
                    AND ex.exe_exo_id = $exercise_id
                    AND orig_lp_id = $lp_id
                    AND orig_lp_item_id = $lp_item_id
                    AND exe_user_id= $student_id 
                    AND session_id = $session_id ";
			
			$rs = Database::query($sql);
			$row = Database::fetch_row($rs);
			$count_attempts = $row[0];
		}
		return $count_attempts;

	}

	/**
	 * Returns the average student progress in the learning paths of the given
	 * course.
	 * @param   int/array    Student id(s)
	 * @param   string        Course code
	 * @param     array         Limit average to listed lp ids
	 * @param    int            Session id (optional), if parameter $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
	 * @param    bool        Will return an array of the type: [sum_of_progresses, number] if it is set to true
	 * @return double        Average progress of the user in this course
	 */
	public static function get_avg_student_progress($student_id, $course_code, $lp_ids = array(), $session_id = null, $return_array = false) {

		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		if (!empty($a_course)) {
			// table definition
			$tbl_course_lp_view = Database :: get_course_table(TABLE_LP_VIEW);
			$tbl_course_lp = Database :: get_course_table(TABLE_LP_MAIN);

			// Compose a filter based on optional learning paths list given
			$condition_lp = "";

			if (!empty($lp_ids)) {
				if (count($lp_ids) > 0) {
					$condition_lp ="  AND id IN(".implode(',',$lp_ids).") ";
				}
			}
			$session_id = intval($session_id);
			$sql = "SELECT id FROM $tbl_course_lp lp WHERE c_id = {$a_course['real_id']} $condition_lp";
			$res_count_lp = Database::query($sql);
			// count the number of learning paths
			$lp_id = array();
			while ($row_lp = Database::fetch_array($res_count_lp,'ASSOC')) {
				//$visibility = api_get_item_visibility($a_course, TOOL_LEARNPATH, $row_lp['id'], $session_id);
				//  if ($visibility == 1) {
				$lp_id[] = $row_lp['id'];
				//}
				}
				$count_lp = count($lp_id);

				$avg_progress = 0;
				//if there is at least one learning path and one student
				if ($count_lp>0 && !empty($student_id)) {
					$condition_user = "";
					if (is_array($student_id)) {
						array_walk($student_id,'intval');
						$condition_user = " lp_view.user_id IN (".implode(',',$student_id).") AND ";
					} else {
						$student_id = intval($student_id);
						$condition_user = " lp_view.user_id = '$student_id' AND ";
					}
					// Get last view for each student (in case of multi-attempt)
					// Also filter on LPs of this session
					$sql_maxes = "SELECT MAX(view_count), progress FROM $tbl_course_lp_view lp_view ".
                             	"WHERE 	c_id = {$a_course['real_id']} AND 
                             			$condition_user session_id = $session_id AND 
                             			lp_view.lp_id IN (".implode(',',$lp_id).") ".
                             "GROUP BY lp_id, user_id";                
					$res_maxes = Database::query($sql_maxes);
					$sum =  0;
					while ($row_maxes = Database::fetch_array($res_maxes)) {
						$sum += $row_maxes[1];
					}
					// average progress = total sum divided by the number of views
					// summed up.
					$number_items = count($lp_id);
					if ($number_items == 0) {
						return 0; //not necessary to return something else if there is no view
					}
					if (!$return_array) {
						$avg_progress = round($sum / $number_items, 1);
						return $avg_progress;
					} else {
						return array($sum, $number_items);
					}
				}
			}
			return null;
		}


		/**
		 * This function gets:
		 * 1. The score average from all SCORM Test items in all LP in a course-> All the answers / All the max scores.
		 * 2. The score average from all Tests (quiz) in all LP in a course-> All the answers / All the max scores.
		 * 3. And finally it will return the average between 1. and 2.
		 * @todo improve performance, when loading 1500 users with 20 lps the script dies
		 * This function does not take the results of a Test out of a LP
		 *
		 * @param   mixed       Array of user ids or an user id
		 * @param   string      Course code
		 * @param   array       List of LP ids
		 * @param   int         Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @param   bool        Returns an array of the type [sum_score, num_score] if set to true
		 * @param   bool        get only the latest attempts or ALL attempts
		 * @return  string      Value (number %) Which represents a round integer explain in got in 3.
		 */
		public static function get_avg_student_score($student_id, $course_code, $lp_ids = array(), $session_id = null, $return_array = false, $get_only_latest_attempt_results = false) {
			$debug = false;
			if (empty($lp_ids)) {
				$debug = false;
			}

			if ($debug) echo '<h1>Tracking::get_avg_student_score</h1>';
			// get global tables names
			$course_table               = Database :: get_main_table(TABLE_MAIN_COURSE);
			$course_user_table          = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
			$table_session_course_user  = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$tbl_stats_exercices        = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
			$tbl_stats_attempts         = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

			$course = CourseManager :: get_course_information($course_code);

			if (!empty($course)) {

				// get course tables names
				$tbl_quiz_questions = Database :: get_course_table(TABLE_QUIZ_QUESTION);
				$lp_table           = Database :: get_course_table(TABLE_LP_MAIN);
				$lp_item_table      = Database :: get_course_table(TABLE_LP_ITEM);
				$lp_view_table      = Database :: get_course_table(TABLE_LP_VIEW);
				$lp_item_view_table = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
				
				$course_id = $course['real_id'];

				// Compose a filter based on optional learning paths list given

				$condition_lp = "";
				if (count($lp_ids) > 0) {
					$condition_lp =" AND id IN(".implode(',',$lp_ids).") ";
				}

				// Compose a filter based on optional session id
				$condition_session = "";

				$session_id = intval($session_id);
				if (count($lp_ids) > 0) {
					$condition_session = " AND session_id = $session_id ";
				} else {
					$condition_session = " WHERE session_id = $session_id ";
				}

				// Check the real number of LPs corresponding to the filter in the
				// database (and if no list was given, get them all)

				if (empty($session_id)) {
					$sql = "SELECT DISTINCT(id), use_max_score FROM $lp_table WHERE c_id = $course_id AND session_id = 0 $condition_lp ";
				} else {
					$sql = "SELECT DISTINCT(id), use_max_score FROM $lp_table WHERE c_id = $course_id $condition_lp ";
				}

				$res_row_lp   = Database::query($sql);
				$count_row_lp = Database::num_rows($res_row_lp);

				$lp_list = $use_max_score = array();
				while ($row_lp = Database::fetch_array($res_row_lp)) {
					$lp_list[]                     = $row_lp['id'];
					$use_max_score[$row_lp['id']]  = $row_lp['use_max_score'];
				}

				if ($debug) {
					echo 'Use max score or not list '; var_dump($use_max_score);
				}

				// Init local variables that will be used through the calculation
				$progress = 0;

				// prepare filter on users
				$condition_user1 = "";

				if (is_array($student_id)) {
					array_walk($student_id, 'intval');
					$condition_user1 =" AND user_id IN (".implode(',', $student_id).") ";
				} else {
					$condition_user1 =" AND user_id = $student_id ";
				}

				if ($count_row_lp > 0 && !empty($student_id)) {

					// Getting latest LP result for a student
					//@todo problem when a  course have more than 1500 users
					$sql = "SELECT MAX(view_count) as vc, id, progress, lp_id, user_id  FROM $lp_view_table
                            WHERE 	c_id = $course_id AND 
                            		lp_id IN (".implode(',',$lp_list).") 
                            		$condition_user1 AND 
									session_id = $session_id 
							GROUP BY lp_id, user_id";
					if ($debug) echo $sql;
					$rs_last_lp_view_id = Database::query($sql);

					$global_result = 0;

					if (Database::num_rows($rs_last_lp_view_id) > 0) {
						// Cycle through each line of the results (grouped by lp_id, user_id)
						$exe_list = array();
						while ($row_lp_view = Database::fetch_array($rs_last_lp_view_id)) {
							$count_items = 0;
							$lp_partial_total = 0;

							$list = array();
							$lp_view_id = $row_lp_view['id'];
							$progress   = $row_lp_view['progress'];
							$lp_id      = $row_lp_view['lp_id'];
							$user_id    = $row_lp_view['user_id'];
							if ($debug) echo '<h2>LP id '.$lp_id.'</h2>';

							if ($get_only_latest_attempt_results) {
								//if (1) {
								//Getting lp_items done by the user
								$sql  = "SELECT DISTINCT lp_item_id FROM $lp_item_view_table 
										WHERE c_id = $course_id AND lp_view_id = $lp_view_id ORDER BY lp_item_id";
								$res_lp_item = Database::query($sql);

								while ($row_lp_item = Database::fetch_array($res_lp_item,'ASSOC')) {
									$my_lp_item_id = $row_lp_item['lp_item_id'];

									//Getting the most recent attempt
									$sql = "SELECT lp_iv.id as lp_item_view_id, lp_iv.score as score,lp_i.max_score, lp_iv.max_score as max_score_item_view, lp_i.path, lp_i.item_type, lp_i.id as iid
                                            FROM $lp_item_view_table as lp_iv INNER JOIN $lp_item_table as lp_i ON lp_i.id = lp_iv.lp_item_id AND (lp_i.item_type='sco' OR lp_i.item_type='".TOOL_QUIZ."') 
                                            WHERE 	lp_iv.c_id = $course_id AND 
                                            		lp_i.c_id  = $course_id AND
													lp_item_id = $my_lp_item_id AND 
													lp_view_id = $lp_view_id 
											ORDER BY view_count DESC 
											LIMIT 1";
									$res_lp_item_result = Database::query($sql);
									while ($row_max_score = Database::fetch_array($res_lp_item_result,'ASSOC')) {
										$list[]= $row_max_score;
									}
								}
							} else {
								// For the currently analysed view, get the score and
								// max_score of each item if it is a sco or a TOOL_QUIZ
								$sql_max_score = "SELECT lp_iv.id as lp_item_view_id, lp_iv.score as score,lp_i.max_score, lp_iv.max_score as max_score_item_view, lp_i.path, lp_i.item_type, lp_i.id as iid
                                                  FROM $lp_item_view_table as lp_iv INNER JOIN $lp_item_table as lp_i ON lp_i.id = lp_iv.lp_item_id AND (lp_i.item_type='sco' OR lp_i.item_type='".TOOL_QUIZ."') 
                                                  WHERE lp_iv.c_id = $course_id AND 
                                            			lp_i.c_id  = $course_id AND
														lp_view_id = $lp_view_id ";
								if ($debug) echo $sql_max_score.'<br />';

								$res_max_score = Database::query($sql_max_score);
								 
								while ($row_max_score = Database::fetch_array($res_max_score,'ASSOC')) {
									$list[]= $row_max_score;
								}
							}
							$count_total_loop = 0;

							// Go through each scorable element of this view
							 
							$score_of_scorm_calculate = 0;

							foreach ($list as $row_max_score) {
								$max_score              = $row_max_score['max_score'];  //Came from the original lp_item
								$max_score_item_view    = $row_max_score['max_score_item_view']; //Came from the lp_item_view
								$score                  = $row_max_score['score'];

								if ($debug) echo '<h3>Item Type: ' .$row_max_score['item_type'].'</h3>';

								if ($row_max_score['item_type'] == 'sco') {
									//var_dump($row_max_score);
									// Check if it is sco (easier to get max_score)
									//when there's no max score, we assume 100 as the max score, as the SCORM 1.2 says that the value should always be between 0 and 100.
									if ($max_score == 0 || is_null($max_score) || $max_score == '') {
										//Chamilo style
										if ($use_max_score[$lp_id]) {
											$max_score = 100;
										} else {
											//Overwrites max score = 100 to use the one that came in the lp_item_view see BT#1613
											$max_score = $max_score_item_view;
										}
									}
									//Avoid division by zero errors
									if (!empty($max_score)) {
										$lp_partial_total += $score/$max_score;
									}
									if ($debug) echo '<b>$lp_partial_total, $score, $max_score '.$lp_partial_total.' '.$score.' '.$max_score.'</b><br />';
								} else {
									// Case of a TOOL_QUIZ element
									$item_id    = $row_max_score['iid'];
									$item_path  = $row_max_score['path'];
									$lp_item_view_id  = $row_max_score['lp_item_view_id'];

									// Get last attempt to this exercise  through
									// the current lp for the current user
									$sql_last_attempt = "SELECT exe_id FROM $tbl_stats_exercices WHERE
                                            exe_exo_id           = '$item_path' AND 
                                            exe_user_id          = $user_id AND 
                                            orig_lp_item_id      = $item_id AND 
                                            orig_lp_item_view_id = $lp_item_view_id AND 
                                            exe_cours_id         = '$course_code'  AND
                                            session_id           = $session_id 
                                            ORDER BY exe_date DESC LIMIT 1";
									if ($debug) echo $sql_last_attempt .'<br />';
									$result_last_attempt = Database::query($sql_last_attempt);
									$num = Database :: num_rows($result_last_attempt);
									if ($num > 0 ) {
										$id_last_attempt = Database :: result($result_last_attempt, 0, 0);
										if ($debug) echo $id_last_attempt.'<br />';
										/* if (in_array($id_last_attempt, $exe_list)) {
										 continue;
										}*/
										//$exe_list[] = $id_last_attempt;
										//echo $id_last_attempt .'<br />';

										// Within the last attempt number tracking, get the sum of
										// the max_scores of all questions that it was
										// made of (we need to make this call dynamic
										// because of random questions selection)
										$sql = "SELECT SUM(t.ponderation) as maxscore FROM
                                                ( SELECT distinct question_id, marks, ponderation FROM $tbl_stats_attempts AS at INNER JOIN  $tbl_quiz_questions AS q ON (q.id = at.question_id) 
                                                  WHERE exe_id ='$id_last_attempt' ) AS t";
										$res_max_score_bis = Database::query($sql);
										$row_max_score_bis = Database :: fetch_array($res_max_score_bis);
										if (!empty($row_max_score_bis['maxscore'])) {
											$max_score = $row_max_score_bis['maxscore'];
										}
										if (!empty($max_score)) {
											$lp_partial_total            += $score/$max_score;
										}
										if ($debug) echo '$lp_partial_total, $score, $max_score <b>'.$lp_partial_total.' '.$score.' '.$max_score.'</b><br />';
									}
								}

								if (in_array($row_max_score['item_type'], array('quiz','sco'))) {
									// Normal way
									if ($use_max_score[$lp_id]) {
										$count_items++;
									} else {
										if ($max_score != '') {
											$count_items++;
										}
									}
									if ($debug) echo '$count_items: '.$count_items;
								}
							} //end for

							$score_of_scorm_calculate += $count_items?(($lp_partial_total/$count_items)*100):0;

							if ($debug) echo '<h3>$count_items '.$count_items.'</h3>';
							if ($debug) echo '<h3>$score_of_scorm_calculate '.$score_of_scorm_calculate.'</h3>';
							 
							// var_dump($score_of_scorm_calculate);
							$global_result += $score_of_scorm_calculate;
							if ($debug) echo '<h3>$global_result '.$global_result.'</h3>';
						} // end while
					}

					$lp_with_quiz = 0;
					if ($debug) var_dump($lp_list);
					foreach($lp_list as $lp_id) {
						//Check if LP have a score we asume that all SCO have an score
						$sql = "SELECT count(id) as count FROM $lp_item_table WHERE c_id = $course_id AND  (item_type = 'quiz' OR item_type = 'sco') AND lp_id = ".$lp_id;
						if ($debug) echo $sql;
						$result_have_quiz = Database::query($sql);

						if (Database::num_rows($result_have_quiz) > 0 ) {
							$row = Database::fetch_array($result_have_quiz,'ASSOC');
							if (is_numeric($row['count']) && $row['count'] != 0) {
								$lp_with_quiz ++;
							}
						}
					}

					if ($debug) echo '<h3>$lp_with_quiz '.$lp_with_quiz.' </h3>';
					if ($debug) echo '<h3>Final return</h3>';

					if ($lp_with_quiz != 0 ) {
						if (!$return_array) {
							$score_of_scorm_calculate = round(($global_result/$lp_with_quiz),2);
							if ($debug) var_dump($score_of_scorm_calculate);
							if (empty($lp_ids)) {
								//$score_of_scorm_calculate = round($score_of_scorm_calculate/count($lp_list),2);
								if ($debug) echo '<h2>All lps fix: '.$score_of_scorm_calculate.'</h2>';
							}
							return $score_of_scorm_calculate;
						} else {
							if ($debug) var_dump($global_result, $lp_with_quiz);
							return array($global_result, $lp_with_quiz);
						}
					} else {
						return '-';
					}
				}
			}
			return null;
		}


		/**
		 * This function gets time spent in learning path for a student inside a course
		 * @param     int|array    Student id(s)
		 * @param     string         Course code
		 * @param     array         Limit average to listed lp ids
		 * @param     int            Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @return     int         Total time
		 */
		public static function get_time_spent_in_lp($student_id, $course_code, $lp_ids = array(), $session_id = null) {

			$course = CourseManager :: get_course_information($course_code);
			$student_id = intval($student_id);
			$total_time = 0;

			if (!empty($course)) {

				$lp_table   = Database :: get_course_table(TABLE_LP_MAIN);
				$t_lpv      = Database :: get_course_table(TABLE_LP_VIEW);
				$t_lpiv     = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
				
				$course_id	 = $course['real_id'];

				// Compose a filter based on optional learning paths list given
				$condition_lp = "";
				if(count($lp_ids) > 0) {
					$condition_lp =" AND id IN(".implode(',',$lp_ids).") ";
				}

				// Compose a filter based on optional session id
				$condition_session = "";
				$session_id = intval($session_id);

				
				if (isset($session_id)) {
					$condition_session = " AND session_id = $session_id ";
				}

				// Check the real number of LPs corresponding to the filter in the
				// database (and if no list was given, get them all)
				//$res_row_lp = Database::query("SELECT DISTINCT(id) FROM $lp_table $condition_lp $condition_session");
				$res_row_lp = Database::query("SELECT DISTINCT(id) FROM $lp_table WHERE c_id = $course_id $condition_lp");
				$count_row_lp = Database::num_rows($res_row_lp);

				// calculates time
				if ($count_row_lp > 0) {
					while ($row_lp = Database::fetch_array($res_row_lp)) {
						$lp_id = intval($row_lp['id']);
						$sql = 'SELECT SUM(total_time)
                            FROM '.$t_lpiv.' AS item_view
                            INNER JOIN '.$t_lpv.' AS view
                                ON item_view.lp_view_id = view.id
                                WHERE
                                item_view.c_id 		= '.$course_id.' AND
                                view.c_id 			= '.$course_id.' AND 
                                view.lp_id 			= '.$lp_id.'
                                AND view.user_id 	= '.$student_id.' AND 
								session_id 			= '.$session_id;

						$rs = Database::query($sql);
						if (Database :: num_rows($rs) > 0) {
							$total_time += Database :: result($rs, 0, 0);
						}
					}
				}
			}
			return $total_time;
		}

		/**
		 * This function gets last connection time to one learning path
		 * @param     int|array    Student id(s)
		 * @param     string         Course code
		 * @param     int         Learning path id
		 * @return     int         Total time
		 */
		public static function get_last_connection_time_in_lp($student_id, $course_code, $lp_id, $session_id = 0) {
			$course = CourseManager :: get_course_information($course_code);
			$student_id = intval($student_id);
			$lp_id = intval($lp_id);
			$last_time = 0;
			$session_id = intval($session_id);

			if (!empty($course)) {
				
				$course_id	 = $course['real_id'];

				$lp_table    = Database :: get_course_table(TABLE_LP_MAIN);
				$t_lpv       = Database :: get_course_table(TABLE_LP_VIEW);
				$t_lpiv      = Database :: get_course_table(TABLE_LP_ITEM_VIEW);

				// Check the real number of LPs corresponding to the filter in the
				// database (and if no list was given, get them all)
				$res_row_lp = Database::query("SELECT id FROM $lp_table WHERE c_id = $course_id AND id = $lp_id ");
				$count_row_lp = Database::num_rows($res_row_lp);

				// calculates last connection time
				if ($count_row_lp > 0) {
					$sql = 'SELECT MAX(start_time)
                        FROM ' . $t_lpiv . ' AS item_view
                        INNER JOIN ' . $t_lpv . ' AS view
                            ON item_view.lp_view_id = view.id
                            WHERE
                            item_view.c_id 		= '.$course_id.' AND
                            view.c_id 			= '.$course_id.' AND  
                            view.lp_id 			= '.$lp_id.'
                            AND view.user_id 	= '.$student_id.' 
                            AND view.session_id = '.$session_id;
					$rs = Database::query($sql);
					if (Database :: num_rows($rs) > 0) {
						$last_time = Database :: result($rs, 0, 0);
					}
				}
			}
			return $last_time;
		}

		/**
		 * gets the list of students followed by coach
		 * @param     int     Coach id
		 * @return     array     List of students
		 */
		public static function get_student_followed_by_coach($coach_id) {
			$coach_id = intval($coach_id);

			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session_user = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
			$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

			$a_students = array ();

			// At first, courses where $coach_id is coach of the course //
			$sql = 'SELECT id_session, course_code FROM ' . $tbl_session_course_user . ' WHERE id_user=' . $coach_id.' AND status=2';

			global $_configuration;
			if ($_configuration['multiple_access_urls']) {
				$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1) {
					$sql = 'SELECT scu.id_session, scu.course_code
                        FROM ' . $tbl_session_course_user . ' scu INNER JOIN '.$tbl_session_rel_access_url.'  sru
                        ON (scu.id_session=sru.session_id)
                        WHERE scu.id_user=' . $coach_id.' AND scu.status=2 AND sru.access_url_id = '.$access_url_id;
				}
			}

			$result = Database::query($sql);

			while ($a_courses = Database::fetch_array($result)) {

				$course_code = $a_courses["course_code"];
				$id_session = $a_courses["id_session"];

				$sql = "SELECT distinct    srcru.id_user
                                FROM $tbl_session_course_user AS srcru, $tbl_session_user sru
                                WHERE srcru.id_user = sru.id_user AND sru.relation_type<>".SESSION_RELATION_TYPE_RRHH." AND srcru.id_session = sru.id_session AND srcru.course_code='$course_code' AND srcru.id_session='$id_session'";

				$rs = Database::query($sql);

				while ($row = Database::fetch_array($rs)) {
					$a_students[$row['id_user']] = $row['id_user'];
				}
			}

			// Then, courses where $coach_id is coach of the session    //

			$sql = 'SELECT session_course_user.id_user
                        FROM ' . $tbl_session_course_user . ' as session_course_user
                        INNER JOIN     '.$tbl_session_user.' sru ON session_course_user.id_user = sru.id_user AND session_course_user.id_session = sru.id_session
                        INNER JOIN ' . $tbl_session_course . ' as session_course
                            ON session_course.course_code = session_course_user.course_code
                            AND session_course_user.id_session = session_course.id_session
                        INNER JOIN ' . $tbl_session . ' as session
                            ON session.id = session_course.id_session
                            AND session.id_coach = ' . $coach_id;
			if ($_configuration['multiple_access_urls']) {
				$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1){
					$sql = 'SELECT session_course_user.id_user
                FROM ' . $tbl_session_course_user . ' as session_course_user
                INNER JOIN     '.$tbl_session_user.' sru ON session_course_user.id_user = sru.id_user AND session_course_user.id_session = sru.id_session
                INNER JOIN ' . $tbl_session_course . ' as session_course
                    ON session_course.course_code = session_course_user.course_code
                    AND session_course_user.id_session = session_course.id_session
                INNER JOIN ' . $tbl_session . ' as session
                    ON session.id = session_course.id_session
                    AND session.id_coach = ' . $coach_id.'
                INNER JOIN '.$tbl_session_rel_access_url.'  session_rel_url
                    ON session.id = session_rel_url.session_id WHERE access_url_id = '.$access_url_id;
				}
			}

			$result = Database::query($sql);

			while ($row = Database::fetch_array($result)) {
				$a_students[$row['id_user']] = $row['id_user'];
			}
			return $a_students;
		}

		/**
		 * Get student followed by a coach inside a session
		 * @param    int        Session id
		 * @param    int        Coach id
		 * @return     array    students list
		 */
		public static function get_student_followed_by_coach_in_a_session($id_session, $coach_id) {

			$coach_id = intval($coach_id);

			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

			$a_students = array ();

			//////////////////////////////////////////////////////////////
			// At first, courses where $coach_id is coach of the course //
			//////////////////////////////////////////////////////////////
			$sql = 'SELECT course_code FROM ' . $tbl_session_course_user . ' WHERE id_session="' . $id_session . '" AND id_user=' . $coach_id.' AND status=2';

			$result = Database::query($sql);

			while ($a_courses = Database::fetch_array($result)) {
				$course_code = $a_courses["course_code"];

				$sql = "SELECT distinct    srcru.id_user
                                FROM $tbl_session_course_user AS srcru
                                WHERE course_code='$course_code' and id_session = '" . $id_session . "'";

				$rs = Database::query($sql);

				while ($row = Database::fetch_array($rs)) {
					$a_students[$row['id_user']] = $row['id_user'];
				}
			}

			//////////////////////////////////////////////////////////////
			// Then, courses where $coach_id is coach of the session    //
			//////////////////////////////////////////////////////////////

			$dsl_session_coach = 'SELECT id_coach FROM ' . $tbl_session . ' WHERE id="' . $id_session . '" AND id_coach="' . $coach_id . '"';
			$result = Database::query($dsl_session_coach);
			//He is the session_coach so we select all the users in the session
			if (Database::num_rows($result) > 0) {
				$sql = 'SELECT DISTINCT srcru.id_user FROM ' . $tbl_session_course_user . ' AS srcru WHERE id_session="' . $id_session . '"';
				$result = Database::query($sql);
				while ($row = Database::fetch_array($result)) {
					$a_students[$row['id_user']] = $row['id_user'];
				}
			}
			return $a_students;
		}

		/**
		 * Check if a coach is allowed to follow a student
		 * @param    int        Coach id
		 * @param    int        Student id
		 * @return    bool
		 */
		public static function is_allowed_to_coach_student($coach_id, $student_id) {
			$coach_id = intval($coach_id);
			$student_id = intval($student_id);

			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

			//////////////////////////////////////////////////////////////
			// At first, courses where $coach_id is coach of the course //
			//////////////////////////////////////////////////////////////
			/*$sql = 'SELECT 1
			FROM ' . $tbl_session_course_user . ' AS session_course_user
			INNER JOIN ' . $tbl_session_course . ' AS session_course
			ON session_course.course_code = session_course_user.course_code
			AND id_coach=' . $coach_id . '
			WHERE id_user=' . $student_id;*/

			$sql = 'SELECT 1 FROM ' . $tbl_session_course_user . ' WHERE id_user=' . $coach_id .' AND status=2';

			$result = Database::query($sql);
			if (Database::num_rows($result) > 0) {
				return true;
			}

			//////////////////////////////////////////////////////////////
			// Then, courses where $coach_id is coach of the session    //
			//////////////////////////////////////////////////////////////

			$sql = 'SELECT session_course_user.id_user
                        FROM ' . $tbl_session_course_user . ' as session_course_user
                        INNER JOIN ' . $tbl_session_course . ' as session_course
                            ON session_course.course_code = session_course_user.course_code
                        INNER JOIN ' . $tbl_session . ' as session
                            ON session.id = session_course.id_session
                            AND session.id_coach = ' . $coach_id . '
                        WHERE id_user = ' . $student_id;
			$result = Database::query($sql);
			if (Database::num_rows($result) > 0) {
				return true;
			}

			return false;

		}


		/**
		 * Get courses followed by coach
		 * @param     int        Coach id
		 * @param    int        Session id (optional)
		 * @return    array    Courses list
		 */
		public static function get_courses_followed_by_coach($coach_id, $id_session = '')
		{

			$coach_id = intval($coach_id);
			if (!empty ($id_session))
			$id_session = intval($id_session);

			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
			$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);

			//////////////////////////////////////////////////////////////
			// At first, courses where $coach_id is coach of the course //
			//////////////////////////////////////////////////////////////
			$sql = 'SELECT DISTINCT course_code FROM ' . $tbl_session_course_user . ' WHERE id_user=' . $coach_id.' AND status=2';

			global $_configuration;
			if ($_configuration['multiple_access_urls']) {
				$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1){
					$sql = 'SELECT DISTINCT scu.course_code FROM ' . $tbl_session_course_user . ' scu INNER JOIN '.$tbl_course_rel_access_url.' cru
                        ON (scu.course_code = cru.course_code)
                        WHERE scu.id_user=' . $coach_id.' AND scu.status=2 AND cru.access_url_id = '.$access_url_id;
				}
			}

			if (!empty ($id_session))
			$sql .= ' AND id_session=' . $id_session;
			$result = Database::query($sql);
			while ($row = Database::fetch_array($result)) {
				$a_courses[$row['course_code']] = $row['course_code'];
			}

			//////////////////////////////////////////////////////////////
			// Then, courses where $coach_id is coach of the session    //
			//////////////////////////////////////////////////////////////
			$sql = 'SELECT DISTINCT session_course.course_code
                        FROM ' . $tbl_session_course . ' as session_course
                        INNER JOIN ' . $tbl_session . ' as session
                            ON session.id = session_course.id_session
                            AND session.id_coach = ' . $coach_id . '
                        INNER JOIN ' . $tbl_course . ' as course
                            ON course.code = session_course.course_code';

			if ($_configuration['multiple_access_urls']) {
				$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1){
					$sql = 'SELECT DISTINCT session_course.course_code
                        FROM ' . $tbl_session_course . ' as session_course
                        INNER JOIN ' . $tbl_session . ' as session
                            ON session.id = session_course.id_session
                            AND session.id_coach = ' . $coach_id . '
                        INNER JOIN ' . $tbl_course . ' as course
                            ON course.code = session_course.course_code
                         INNER JOIN '.$tbl_course_rel_access_url.' course_rel_url
                        ON (session_course.course_code = course_rel_url.course_code)';
				}
			}

			if (!empty ($id_session)) {
				$sql .= ' WHERE session_course.id_session=' . $id_session;
				if ($_configuration['multiple_access_urls'])
				$sql .=  ' AND access_url_id = '.$access_url_id;
			}  else {
				if ($_configuration['multiple_access_urls'])
				$sql .=  ' WHERE access_url_id = '.$access_url_id;
			}

			$result = Database::query($sql);

			while ($row = Database::fetch_array($result)) {
				$a_courses[$row['course_code']] = $row['course_code'];
			}

			return $a_courses;
		}

		/**
		 * Get sessions coached by user
		 * @param    int        Coach id
		 * @return    array    Sessions list
		 */
		public static function get_sessions_coached_by_user($coach_id) {
			// table definition
			$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
			$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

			// protect datas
			$coach_id = intval($coach_id);

			// session where we are general coach
			$sql = 'SELECT DISTINCT id, name, date_start, date_end
                        FROM ' . $tbl_session . '
                        WHERE id_coach=' . $coach_id;

			global $_configuration;
			if ($_configuration['multiple_access_urls']) {
				$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1){
					$sql = 'SELECT DISTINCT id, name, date_start, date_end
                        FROM ' . $tbl_session . ' session INNER JOIN '.$tbl_session_rel_access_url.' session_rel_url
                        ON (session.id = session_rel_url.session_id)
                        WHERE id_coach=' . $coach_id.' AND access_url_id = '.$access_url_id;
				}
			}

			$rs = Database::query($sql);

			while ($row = Database::fetch_array($rs))
			{
				$a_sessions[$row["id"]] = $row;
			}

			// session where we are coach of a course
			$sql = 'SELECT DISTINCT session.id, session.name, session.date_start, session.date_end
                        FROM ' . $tbl_session . ' as session
                        INNER JOIN ' . $tbl_session_course_user . ' as session_course_user
                            ON session.id = session_course_user.id_session
                            AND session_course_user.id_user=' . $coach_id.' AND session_course_user.status=2';

			global $_configuration;
			if ($_configuration['multiple_access_urls']) {
				$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1){
					$sql = 'SELECT DISTINCT session.id, session.name, session.date_start, session.date_end
                        FROM ' . $tbl_session . ' as session
                        INNER JOIN ' . $tbl_session_course_user . ' as session_course_user
                            ON session.id = session_course_user.id_session AND session_course_user.id_user=' . $coach_id.' AND session_course_user.status=2
                        INNER JOIN '.$tbl_session_rel_access_url.' session_rel_url
                        ON (session.id = session_rel_url.session_id)
                        WHERE access_url_id = '.$access_url_id;
				}
			}

			$rs = Database::query($sql);

			while ($row = Database::fetch_array($rs))
			{
				$a_sessions[$row["id"]] = $row;
			}

			if (is_array($a_sessions)) {
				foreach ($a_sessions as & $session) {
					if ($session['date_start'] == '0000-00-00') {
						$session['status'] = get_lang('SessionActive');
					}
					else {
						$date_start = explode('-', $session['date_start']);
						$time_start = mktime(0, 0, 0, $date_start[1], $date_start[2], $date_start[0]);
						$date_end = explode('-', $session['date_end']);
						$time_end = mktime(0, 0, 0, $date_end[1], $date_end[2], $date_end[0]);
						if ($time_start < time() && time() < $time_end) {
							$session['status'] = get_lang('SessionActive');
						}
						else{
							if (time() < $time_start) {
								$session['status'] = get_lang('SessionFuture');
							}
							else{
								if (time() > $time_end) {
									$session['status'] = get_lang('SessionPast');
								}
							}
						}
					}
				}
			}
			return $a_sessions;

		}

		/**
		 * Get courses list from a session
		 * @param    int        Session id
		 * @return    array    Courses list
		 */
		public static function get_courses_list_from_session($session_id) {
			//protect datas
			$session_id = intval($session_id);

			// table definition
			$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
			$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);

			$sql = 'SELECT DISTINCT course_code
                        FROM ' . $tbl_session_course . '
                        WHERE id_session=' . $session_id;

			$rs = Database::query($sql);
			$a_courses = array ();
			while ($row = Database::fetch_array($rs)) {
				$a_courses[$row['course_code']] = $row;
			}
			return $a_courses;
		}


		/**
		 * Count assignments per student
		 * @param    int|array   Student id(s)
		 * @param    string        Course code
		 * @param    int            Session id (optional), if param $session_id is null(default) return count of assignments including sessions, 0 = session is not filtered
		 * @return    int            Count of assignments
		 */
		public static function count_student_assignments($student_id, $course_code, $session_id = null) {
			// protect datas
			$course_code = Database::escape_string($course_code);
			// get the informations of the course
			$a_course = CourseManager :: get_course_information($course_code);
			if (!empty($a_course)) {
				// table definition
				$tbl_item_property          = Database :: get_course_table(TABLE_ITEM_PROPERTY);
				$tbl_student_publication 	= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
				
				$course_id	 = $a_course['real_id'];

				$condition_user = "";
				if (is_array($student_id)) {
					$condition_user = " AND ip.insert_user_id IN (".implode(',',$student_id).") ";
				} else {
					$condition_user = " AND ip.insert_user_id = '$student_id' ";
				}

				$condition_session = "";
				if (isset($session_id)) {
					$session_id = intval($session_id);
					$condition_session = " AND pub.session_id = $session_id ";
				}

				$sql = "SELECT count(ip.tool) 
						FROM $tbl_item_property ip INNER JOIN $tbl_student_publication pub 
								ON ip.ref = pub.id 
						WHERE 	ip.c_id  = $course_id AND 
								pub.c_id  = $course_id AND
								ip.tool='work' 
								$condition_user $condition_session ";
				$rs = Database::query($sql);
				$row = Database::fetch_row($rs);
				return $row[0];
			}
			return null;
		}


		/**
		 * Count messages per student inside forum tool
		 * @param    int        Student id
		 * @param    string    Course code
		 * @param    int        Session id (optional), if param $session_id is null(default) return count of messages including sessions, 0 = session is not filtered
		 * @return    int        Count of messages
		 */
		function count_student_messages($student_id, $course_code, $session_id = null) {
			require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

			// protect datas
			$student_id = intval($student_id);
			$course_code = addslashes($course_code);

			// get the informations of the course
			$a_course = CourseManager :: get_course_information($course_code);

			if (!empty($a_course)) {
				
				// table definition
				$tbl_forum_post = Database :: get_course_table(TABLE_FORUM_POST);
				$tbl_forum      = Database :: get_course_table(TABLE_FORUM);
				
				$course_id	 = $a_course['real_id'];

				$condition_user = "";
				if (is_array($student_id)) {
					$condition_user = " AND post.poster_id IN (".implode(',',$student_id).") ";
				} else {
					$condition_user = " AND post.poster_id = '$student_id' ";
				}

				$condition_session = "";
				if (isset($session_id)) {
					$session_id = intval($session_id);
					$condition_session = " AND forum.session_id = $session_id";
				}

				$sql = "SELECT 1 FROM $tbl_forum_post post INNER JOIN $tbl_forum forum 
						ON forum.forum_id = post.forum_id 
						
						WHERE 	post.c_id  = $course_id AND
								forum.c_id = $course_id
								$condition_user $condition_session 
				";
				
				$rs = Database::query($sql);
				return Database::num_rows($rs);
			} else {
				return null;
			}
		}

		/**
		 * This function counts the number of post by course
		 * @param      string     Course code
		 * @param    int        Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @return    int     The number of post by course
		 */
		public static function count_number_of_posts_by_course($course_code, $session_id = null) {
			//protect data
			$course_code = Database::escape_string($course_code);
			// get the informations of the course
			$a_course = CourseManager :: get_course_information($course_code);
			$count = 0;
			if (!empty($a_course)) {
				$tbl_posts 		= Database :: get_course_table(TABLE_FORUM_POST);
				$tbl_forums 	= Database :: get_course_table(TABLE_FORUM);
				
				$condition_session = '';
				if (isset($session_id)) {
					$session_id = intval($session_id);
					$condition_session = ' AND f.session_id = '. $session_id;
				}
				
				$course_id	 = $a_course['real_id'];
				
				$sql = "SELECT count(*) FROM $tbl_posts p INNER JOIN $tbl_forums f 
						ON f.forum_id = p.forum_id
						WHERE 	p.c_id = $course_id AND 
								f.c_id = $course_id
								$condition_session						  
						";
				$result = Database::query($sql);
				$row = Database::fetch_row($result);
				$count = $row[0];
				return $count;
			} else {
				return null;
			}
		}

		/**
		 * This function counts the number of threads by course
		 * @param      string     Course code
		 * @param    int        Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @return    int     The number of threads by course
		 */
		public static function count_number_of_threads_by_course($course_code, $session_id = null) {
			//protect data
			$course_code = Database::escape_string($course_code);
			// get the informations of the course
			$a_course = CourseManager :: get_course_information($course_code);
			$count = 0;
			if (!empty($a_course['db_name'])) {
				$tbl_threads = Database :: get_course_table(TABLE_FORUM_THREAD, $a_course['db_name']);
				$tbl_forums = Database :: get_course_table(TABLE_FORUM, $a_course['db_name']);
				$condition_session = '';
				if (isset($session_id)) {
					$session_id = intval($session_id);
					$condition_session = ' WHERE f.session_id = '. $session_id;
				}
				$sql = "SELECT count(*) FROM $tbl_threads t INNER JOIN $tbl_forums f ON f.forum_id = t.forum_id $condition_session ";
				$result = Database::query($sql);
				$row = Database::fetch_row($result);
				$count = $row[0];
				return $count;
			} else {
				return null;
			}
		}

		/**
		 * This function counts the number of forums by course
		 * @param      string     Course code
		 * @param    int        Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @return    int     The number of forums by course
		 */
		public static function count_number_of_forums_by_course($course_code, $session_id = null) {
			//protect data
			$course_code = addslashes($course_code);
			// get the informations of the course
			$a_course = CourseManager :: get_course_information($course_code);
			$count = 0;
			if (!empty($a_course['db_name'])) {

				$condition_session = '';
				if (isset($session_id)) {
					$session_id = intval($session_id);
					$condition_session = ' WHERE session_id = '. $session_id;
				}

				$tbl_forums = Database :: get_course_table(TABLE_FORUM, $a_course['db_name']);
				$sql = "SELECT count(*) FROM $tbl_forums $condition_session";
				$result = Database::query($sql);
				$row = Database::fetch_row($result);
				$count = $row[0];
				return $count;
			} else {
				return null;
			}
		}

		/**
		 * This function counts the chat last connections by course in x days
		 * @param      string     Course code
		 * @param      int     Last x days
		 * @param    int        Session id (optional)
		 * @return     int     Chat last connections by course in x days
		 */
		public static function chat_connections_during_last_x_days_by_course($course_code,$last_days, $session_id = 0) {
			//protect data
			$last_days   = intval($last_days);
			$course_code = Database::escape_string($course_code);
			$session_id  = intval($session_id);
			// get the informations of the course
			$a_course = CourseManager :: get_course_information($course_code);
			$count = 0;
			if (!empty($a_course['db_name'])) {
				$tbl_stats_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS, $a_course['db_name']);

				$sql = "SELECT count(*) FROM $tbl_stats_access WHERE DATE_SUB(NOW(),INTERVAL $last_days DAY) <= access_date
                    AND access_cours_code = '$course_code' AND access_tool='".TOOL_CHAT."' AND access_session_id='$session_id' ";
				$result = Database::query($sql);
				$row = Database::fetch_row($result);
				$count = $row[0];
				return $count;
			} else {
				return null;
			}
		}

		/**
		 * This function gets the last student's connection in chat
		 * @param      int     Student id
		 * @param      string     Course code
		 * @param    int        Session id (optional)
		 * @return     string    datetime formatted without day (e.g: February 23, 2010 10:20:50 )
		 */
		public static function chat_last_connection($student_id, $course_code, $session_id = 0) {

			//protect datas
			$student_id = intval($student_id);
			$course_code= Database::escape_string($course_code);
			$session_id    = intval($session_id);
			$date_time  = '';

			// table definition
			$tbl_stats_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
			$sql = "SELECT access_date FROM $tbl_stats_access
                 WHERE access_tool='".TOOL_CHAT."' AND access_user_id='$student_id' AND access_cours_code = '$course_code' AND access_session_id = '$session_id' ORDER BY access_date DESC limit 1";
			$rs = Database::query($sql);
			if (Database::num_rows($rs) > 0) {
				$row = Database::fetch_array($rs);
				$date_time = api_convert_and_format_date($row['access_date'], null, date_default_timezone_get());
			}
			return $date_time;
		}

		/**
		 * Get count student's visited links
		 * @param    int        Student id
		 * @param    string    Course code
		 * @param    int        Session id (optional)
		 * @return    int        count of visited links
		 */
		public static function count_student_visited_links($student_id, $course_code, $session_id = 0) {

			// protect datas
			$student_id  = intval($student_id);
			$course_code = Database::escape_string($course_code);
			$session_id  = intval($session_id);

			// table definition
			$tbl_stats_links = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);

			$sql = 'SELECT 1
                        FROM '.$tbl_stats_links.'
                        WHERE links_user_id= '.$student_id.'
                        AND links_cours_id = "'.$course_code.'"
                        AND links_session_id = '.$session_id.' ';

			$rs = Database::query($sql);
			return Database::num_rows($rs);
		}

		/**
		 * Get count student downloaded documents
		 * @param    int        Student id
		 * @param    string    Course code
		 * @param    int        Session id (optional)
		 * @return    int        Count downloaded documents
		 */
		public static function count_student_downloaded_documents($student_id, $course_code, $session_id = 0) {
			// protect datas
			$student_id  = intval($student_id);
			$course_code = Database::escape_string($course_code);
			$session_id  = intval($session_id);

			// table definition
			$tbl_stats_documents = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);

			$sql = 'SELECT 1
                        FROM ' . $tbl_stats_documents . '
                        WHERE down_user_id = '.$student_id.'
                        AND down_cours_id  = "'.$course_code.'"
                        AND down_session_id = '.$session_id.' ';
			$rs = Database::query($sql);
			return Database::num_rows($rs);
		}

		/**
		 * Get course list inside a session from a student
		 * @param    int        Student id
		 * @param    int        Session id (optional)
		 * @return    array    Courses list
		 */
		public static function get_course_list_in_session_from_student($user_id, $id_session = 0) {
			$user_id = intval($user_id);
			$id_session = intval($id_session);
			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$sql = 'SELECT course_code FROM ' . $tbl_session_course_user . ' WHERE id_user="' . $user_id . '" AND id_session="' . $id_session . '"';
			$result = Database::query($sql);
			$a_courses = array ();
			while ($row = Database::fetch_array($result)) {
				$a_courses[$row['course_code']] = $row['course_code'];
			}
			return $a_courses;
		}

		/**
		 * Get inactives students in course
		 * @param    string    Course code
		 * @param    string    Since login course date (optional, default = 'never')
		 * @param    int        Session id    (optional)
		 * @return    array    Inactives users
		 */
		public static function get_inactives_students_in_course($course_code, $since = 'never', $session_id=0) {
			$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$table_course_rel_user            = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
			$inner = '';
			if($session_id!=0)
			{
				$inner = ' INNER JOIN '.$tbl_session_course_user.' session_course_user
                        ON stats_login.course_code = session_course_user.course_code
                        AND session_course_user.id_session = '.intval($session_id).'
                        AND session_course_user.id_user = stats_login.user_id ';
			}
			$sql = 'SELECT user_id, MAX(login_course_date) max_date FROM'.$tbl_track_login.' stats_login'.$inner.'
                GROUP BY user_id
                HAVING DATE_SUB( NOW(), INTERVAL '.$since.' DAY) > max_date ';
			//HAVING DATE_ADD(max_date, INTERVAL '.$since.' DAY) < NOW() ';

			if ($since == 'never') {
				$sql = 'SELECT course_user.user_id FROM '.$table_course_rel_user.' course_user
                        LEFT JOIN '. $tbl_track_login.' stats_login
                        ON course_user.user_id = stats_login.user_id AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' '.
				$inner.'
                    WHERE course_user.course_code = \''.Database::escape_string($course_code).'\'
                    AND stats_login.login_course_date IS NULL
                    GROUP BY course_user.user_id';
			}
			$rs = Database::query($sql);
			$inactive_users = array();
			while($user = Database::fetch_array($rs))
			{
				$inactive_users[] = $user['user_id'];
			}
			return $inactive_users;
		}

		/**
		 * Get count login per student
		 * @param    int        Student id
		 * @param    string    Course code
		 * @param    int        Session id (optional)
		 * @return    int        count login
		 */
		public static function count_login_per_student($student_id, $course_code, $session_id = 0) {
			$student_id  = intval($student_id);
			$course_code = Database::escape_string($course_code);
			$session_id  = intval($session_id);
			$tbl_course_rel_user = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);

			$sql = 'SELECT '.$student_id.'
        FROM ' . $tbl_course_rel_user . '
        WHERE access_user_id=' . $student_id . '
        AND access_cours_code="' . $course_code . '" AND access_session_id = "'.$session_id.'" ';

			$rs = Database::query($sql);
			$nb_login = Database::num_rows($rs);

			return $nb_login;
		}


		/**
		 * Get students followed by a human resources manager
		 * @param    int        Drh id
		 * @return    array    Student list
		 */
		public static function get_student_followed_by_drh($hr_dept_id) {

			$hr_dept_id = intval($hr_dept_id);
			$a_students = array ();

			$tbl_organism = Database :: get_main_table(TABLE_MAIN_ORGANISM);
			$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

			$sql = 'SELECT DISTINCT user_id FROM '.$tbl_user.' as user
                WHERE hr_dept_id='.$hr_dept_id;
			$rs = Database::query($sql);

			while($user = Database :: fetch_array($rs))
			{
				$a_students[$user['user_id']] = $user['user_id'];
			}

			return $a_students;
		}

		/**
		 * Gets the average of test and scorm inside a learning path
		 * @param    int     User id
		 * @param     string     Course id
		 * @return    float    average of test
		 * @author     isaac flores paz 
		 * @deprecated get_avg_student_score should be use
		 */
		public static function get_average_test_scorm_and_lp ($user_id,$course_id) {

			//the score inside the Reporting table
			$course_info    = api_get_course_info($course_id);
			$lp_table         = Database :: get_course_table(TABLE_LP_MAIN,$course_info['dbName']);
			$lp_view_table    = Database  :: get_course_table(TABLE_LP_VIEW,$course_info['dbName']);
			$lp_item_view_table = Database  :: get_course_table(TABLE_LP_ITEM_VIEW,$course_info['dbName']);
			$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM,$course_info['dbName']);
			$sql_type='SELECT id, lp_type FROM '.$lp_table;
			$rs_type=Database::query($sql_type);
			$average_data=0;
			$count_loop=0;
			$lp_list = array();
			while ($row_type=Database::fetch_array($rs_type)) {
				$lp_list[] = $row_type['id'];
				if ($row_type['lp_type']==1) {
					//lp chamilo

					$sql = "SELECT id FROM $lp_view_table  WHERE user_id = '".intval($user_id)."' and lp_id='".$row_type['id']."'";
					$rs_last_lp_view_id = Database::query($sql);
					$lp_view_id = intval(Database::result($rs_last_lp_view_id,0,'id'));

					$sql_list_view='SELECT li.max_score,lv.user_id,liw.score,(liw.score/li.max_score) as sum_data FROM '.$lp_item_table.' li INNER JOIN '.$lp_view_table.' lv
                    ON li.lp_id=lv.lp_id INNER JOIN '.$lp_item_view_table.' liw ON liw.lp_item_id=li.id WHERE lv.user_id="'.$user_id.'" AND li.item_type="quiz" AND liw.lp_view_id="'.$lp_view_id.'"';
					$sum=0;
					$tot=0;
					$rs_list_view1=Database::query($sql_list_view);
					while ($row_list_view=Database::fetch_array($rs_list_view1)) {
						$sum=$sum+$row_list_view['sum_data'];
						$tot++;
					}
					if ($tot==0) {
						$tot=1;
					}
					$average_data1=$sum/$tot;

					$sql_list_view='';
					$rs_last_lp_view_id='';

				} elseif ($row_type['lp_type']==2) {
					//lp scorm
					$sql = "SELECT id FROM $lp_view_table  WHERE user_id = '".intval($user_id)."' and lp_id='".$row_type['id']."'";
					$rs_last_lp_view_id = Database::query($sql);
					$lp_view_id = intval(Database::result($rs_last_lp_view_id,0,'id'));

					$sql_list_view='SELECT li.max_score,lv.user_id,liw.score,((liw.score/li.max_score)*100) as sum_data FROM '.$lp_item_table.' li INNER JOIN '.$lp_view_table.' lv
                    ON li.lp_id=lv.lp_id INNER JOIN '.$lp_item_view_table.' liw ON liw.lp_item_id=li.id WHERE lv.user_id="'.$user_id.'" AND (li.item_type="sco" OR li.item_type="quiz") AND liw.lp_view_id="'.$lp_view_id.'"';
					$tot=0;
					$sum=0;

					$rs_list_view2=Database::query($sql_list_view);
					while ($row_list_view=Database::fetch_array($rs_list_view2)) {
						$sum=$sum+$row_list_view['sum_data'];
						$tot++;
					}
					if ($tot==0) {
						$tot=1;
					}
					$average_data2=$sum/$tot;
				}
				$average_data_sum=$average_data_sum+$average_data1+$average_data2;
				$average_data2=0;
				$average_data1=0;
				$count_loop++;
			}

			//We only count the LP that have an exercise to get the average
			$lp_with_quiz = 0;
			foreach($lp_list as $lp_id) {

				//check if LP have a score
				$sql = "SELECT count(id) as count FROM $lp_item_table
                    WHERE item_type = 'quiz' AND lp_id = ".$lp_id." ";
				$result_have_quiz = Database::query($sql);

				if (Database::num_rows($result_have_quiz) > 0 ) {
					$row = Database::fetch_array($result_have_quiz,'ASSOC');
					if (is_numeric($row['count']) && $row['count'] != 0) {
						$lp_with_quiz++;
					}
				}
			}

			if ($lp_with_quiz > 0) {
				$avg_student_score = round(($average_data_sum / $lp_with_quiz * 100), 2);
			}
			return $avg_student_score;
		}

		/**
		 * get count clicks about tools most used by course
		 * @param      string     Course code
		 * @param    int        Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @return    array     tools data
		 */
		public static function get_tools_most_used_by_course($course_code, $session_id = null) {
			//protect data
			$course_code = Database::escape_string($course_code);
			$data = array();
			$TABLETRACK_ACCESS    = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
			$condition_session     = '';
			if (isset($session_id)) {
				$session_id = intval($session_id);
				$condition_session = ' AND access_session_id = '. $session_id;
			}
			$sql = "SELECT access_tool, COUNT(DISTINCT access_user_id),count( access_tool ) as count_access_tool
	            	FROM $TABLETRACK_ACCESS
	            	WHERE access_tool IS NOT NULL AND access_tool != '' AND access_cours_code = '$course_code' $condition_session
	            	GROUP BY access_tool
	            	ORDER BY count_access_tool DESC
	            	LIMIT 0, 3";
			$rs = Database::query($sql);
			if (Database::num_rows($rs) > 0) {
				while ($row = Database::fetch_array($rs)) {
					$data[] = $row;
				}
			}
			return $data;
		}

		/**
		 * get documents most downloaded by course
		 * @param      string     Course code
		 * @param    int        Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @param    int        Limit (optional, default = 0, 0 = without limit)
		 * @return    array     documents downloaded
		 */
		public static function get_documents_most_downloaded_by_course($course_code, $session_id = null, $limit = 0) {

			//protect data
			$course_code = Database::escape_string($course_code);
			$data = array();

			$TABLETRACK_DOWNLOADS   = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
			$condition_session = '';
			if (isset($session_id)) {
				$session_id = intval($session_id);
				$condition_session = ' AND down_session_id = '. $session_id;
			}
			$sql = "SELECT down_doc_path, COUNT(DISTINCT down_user_id), COUNT(down_doc_path) as count_down
            FROM $TABLETRACK_DOWNLOADS
            WHERE down_cours_id = '$course_code'
			$condition_session
            GROUP BY down_doc_path
            ORDER BY count_down DESC
            LIMIT 0,  $limit";
			$rs = Database::query($sql);

			if (Database::num_rows($rs) > 0) {
				while ($row = Database::fetch_array($rs)) {
					$data[] = $row;
				}
			}
			return $data;
		}

		/**
		 * get links most visited by course
		 * @param      string     Course code
		 * @param    int        Session id (optional), if param $session_id is null(default) it'll return results including sessions, 0 = session is not filtered
		 * @return    array     links most visited
		 */
		public static function get_links_most_visited_by_course($course_code, $session_id = null) {

			//protect data
			$course_code = Database::escape_string($course_code);
			$course_info = api_get_course_info($course_code);
			$data = array();

			$TABLETRACK_LINKS       = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
			$TABLECOURSE_LINKS      = Database::get_course_table(TABLE_LINK, $course_info['dbName']);

			$condition_session = '';
			if (isset($session_id)) {
				$session_id = intval($session_id);
				$condition_session = ' AND cl.session_id = '.$session_id;
			}

			$sql = "SELECT cl.title, cl.url,count(DISTINCT sl.links_user_id), count(cl.title) as count_visits
		            FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
		            WHERE sl.links_link_id = cl.id
		                AND sl.links_cours_id = '$course_code'
			$condition_session
		            GROUP BY cl.title, cl.url
		            ORDER BY count_visits DESC
		            LIMIT 0, 3";
			$rs = Database::query($sql);
			if (Database::num_rows($rs) > 0) {
				while ($row = Database::fetch_array($rs)) {
					$data[] = $row;
				}
			}
			return $data;
		}

		/**
		 * Shows the user progress (when clicking in the Progress tab)
		 * @param   int     user id
		 * @return  string  html code
		 */
		function show_user_progress($user_id, $session_id = 0, $extra_params = '', $show_courses = true) {
			require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
			require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';

			global $_configuration;
			$tbl_course		            = Database :: get_main_table(TABLE_MAIN_COURSE);
			$tbl_session		        = Database :: get_main_table(TABLE_MAIN_SESSION);
			$tbl_course_user            = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
			$tbl_access_rel_course      = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
			$tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$tbl_access_rel_session     = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$tbl_access_rel_course      = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
			$user_id                    = intval($user_id);

			// get course list
			if ($_configuration['multiple_access_urls']) {
				$sql = 'SELECT cu.course_code as code, title
                		FROM '.$tbl_course_user.' cu INNER JOIN '.$tbl_access_rel_course.' a  ON(a.course_code = cu.course_code) INNER JOIN '.$tbl_course.' c ON( cu.course_code = c.code)
                		WHERE user_id='.$user_id.' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND access_url_id = '.api_get_current_access_url_id().' ORDER BY title ';
			} else {
				$sql = 'SELECT course_code as code, title FROM '.$tbl_course_user.' u INNER JOIN '.$tbl_course.' c ON(course_code = c.code) 
						WHERE u.user_id='.$user_id.' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' ORDER BY title ';
			}

			$rs = Database::query($sql);
			$courses = $course_in_session = $temp_course_in_session = array();
			
			$courses = array();
			while($row = Database :: fetch_array($rs, 'ASSOC')) {
				$courses[$row['code']] = $row['title'];
			}
				
			/*
			if (!empty($courses)) {
				//sort need to be improved
				sort($courses);
			}*/
			
			// Get the list of sessions where the user is subscribed as student
			if ($_configuration['multiple_access_urls']) {
				$sql = 'SELECT DISTINCT cu.course_code, id_session as session_id, name FROM '.$tbl_session_course_user.' cu INNER JOIN '.$tbl_access_rel_session.' a  ON(a.session_id = cu.id_session) INNER JOIN '.$tbl_session.' s  ON(s.id = a.session_id) 
                		WHERE id_user='.$user_id.' AND access_url_id = '.api_get_current_access_url_id().' ORDER BY name ';
			} else {
				$sql = 'SELECT DISTINCT course_code, id_session as session_id, name FROM '.$tbl_session_course_user.' u INNER JOIN '.$tbl_session.' s ON(s.id = u.id_session)
                		WHERE id_user='.$user_id.' ORDER BY name ';
			}

			$rs = Database::query($sql);
			$simple_session_array = array();
			while ($row = Database :: fetch_array($rs)) {
				$course_info = CourseManager::get_course_information($row['course_code']);
				$temp_course_in_session[$row['session_id']]['course_list'][$course_info['id']]  = $course_info;
				$temp_course_in_session[$row['session_id']]['name'] = $row['name'];
				$simple_session_array[$row['session_id']] = $row['name'];
			}			
			foreach($simple_session_array as $my_session_id => $session_name) {
				$course_list = $temp_course_in_session[$my_session_id]['course_list'];
				$my_course_data = array();
				
				foreach($course_list as $course_data) {
					$my_course_data[$course_data['id']] = $course_data['title'];
				}
				$my_course_data = utf8_sort($my_course_data);											
				$final_course_data = array();
				
				foreach($my_course_data as $course_id => $value) {
					$final_course_data[$course_id] = $course_list[$course_id];
				}
				$course_in_session[$my_session_id]['course_list'] = $final_course_data;
				$course_in_session[$my_session_id]['name'] = $session_name;
			}			
			 
			$html = '';

			// Course list
			
			if ($show_courses) {
				if (!empty($courses)) {
					$html .= Display::tag('h1', Display::return_icon('course.png', get_lang('MyCourses'), array(), 22).' '.get_lang('MyCourses'));
					$html .= '<table class="data_table" width="100%">';
					$html .= '<tr>
                              '.Display::tag('th', get_lang('Course'),          		array('width'=>'300px')).'
                              '.Display::tag('th', get_lang('TimeSpentInTheCourse'),    array('class'=>'head')).'
                              '.Display::tag('th', get_lang('Progress'),        		array('class'=>'head')).'
                              '.Display::tag('th', get_lang('Score').Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array('align' => 'absmiddle', 'hspace' => '3px')),array('class'=>'head')).'
                              '.Display::tag('th', get_lang('LastConnexion'),   		array('class'=>'head')).'      
                              '.Display::tag('th', get_lang('Details'),         		array('class'=>'head')).'
                            </tr>';

					foreach ($courses as $course_code => $course_title) {
						$weighting = 0;

						$total_time_login      = Tracking :: get_time_spent_on_the_course($user_id, $course_code);
						$time                  = api_time_to_hms($total_time_login);
						$progress              = Tracking :: get_avg_student_progress($user_id, $course_code);
						$percentage_score      = Tracking :: get_avg_student_score($user_id, $course_code, array());
						$last_connection       = Tracking :: get_last_connection_date_on_the_course($user_id, $course_code);

						if ($course_code == $_GET['course'] && empty($_GET['session_id'])) {
							$html .= '<tr class="row_odd" style="background-color:#FBF09D">';
						} else {
							$html .= '<tr class="row_even">';
						}
						$url = api_get_course_url($course_code, $session_id);
						$course_url = Display::url($course_title, $url, array('target'=>SESSION_LINK_TARGET));
						$html .= '<td>'.$course_url.'</td>';

						$html .= '<td align="center">'.$time.'</td>';
						$html .= '<td align="center">'.$progress.'%</td>';

						$html .= '<td align="center">';
						if (is_numeric($percentage_score)) {
							$html .= $percentage_score.'%';
						} else {
							$html .= '0%';
						}
						$html .= '</td>';
						$html .= '<td align="center">'.$last_connection.'</td>';
						$html .= '<td align="center">';
						if ($course_code == $_GET['course'] && empty($_GET['session_id'])) {
							$html .= '<a href="#">';
							$html .= Display::return_icon('2rightarrow_na.gif', get_lang('Details'));
						} else {
							$html .= '<a href="'.api_get_self().'?course='.$course_code.$extra_params.'">';
							$html .= Display::return_icon('2rightarrow.gif', get_lang('Details'));
						}
						$html .= '</a>';
						$html .= '</td></tr>';
					}
					$html .= '</table>';
				}
			}

			// Session list
			if (!empty($course_in_session)) {
				$main_session_graph = '';
				
				if (!isset($_GET['session_id']) && !isset($_GET['course'])) {
						
					//Load graphics only when calling to an specific session					
					$session_graph = array();
					
					$all_exercise_graph_name_list = array();
					$all_user_results = array();
					$all_exercise_graph_list = array();
					
					$all_exercise_start_time = array();
					
					foreach ($course_in_session as $my_session_id => $session_data) {
						
						$course_list  = $session_data['course_list'];				
						$session_name = $session_data['name'];
											
						$user_count = count(SessionManager::get_users_by_session($my_session_id));
												
						$exercise_graph_name_list = array();
						$user_results = array();
						$exercise_graph_list = array();
						
						foreach ($course_list as $course_data) {
							 
							$exercise_list = get_all_exercises($course_data, $my_session_id);
							
							foreach($exercise_list as $exercise_data) {
								$exercise_obj = New Exercise($course_data['id']);
								$exercise_obj->read($exercise_data['id']);
								if ($exercise_obj->is_visible()) {
									$best_average = intval(get_best_average_score_by_exercise($exercise_data['id'], $course_data['code'], $my_session_id, $user_count));
									$exercise_graph_list[] 		= $best_average;
									$all_exercise_graph_list[] 	= $best_average;
									
									$user_result_data 	   		= get_best_attempt_by_user(api_get_user_id(), $exercise_data['id'], $course_data['code'], $my_session_id);
									$score = 0;
									if (!empty($user_result_data['exe_weighting']) && intval($user_result_data['exe_weighting']) != 0) {
										$score = intval($user_result_data['exe_result']/$user_result_data['exe_weighting'] * 100);
									}
									//$user_results[] = 100;
									$user_results[] = $score;
									$time = api_strtotime($exercise_data['start_time']) ? api_strtotime($exercise_data['start_time']) : 0;
									$all_exercise_start_time[] = $time ;
									$all_user_results[] = $score;
									if (count($exercise_list)<=10) {
										$title = cut($course_data['title'], 30)." \n ".cut($exercise_data['title'], 30);
										$exercise_graph_name_list[]= $title;
										$all_exercise_graph_name_list[] = $title;
									} else {
										// if there are more than 10 results, space becomes difficult to find, so only show the title of the exercise, not the tool
										$title = cut($exercise_data['title'], 30);
										$exercise_graph_name_list[]= $title;
										$all_exercise_graph_name_list[]= $title;
									}
								}
							}			
						}
						//Graph per session
						if (!empty($user_results) && !empty($exercise_graph_list)) {						
							//$session_graph[$my_session_id] = self::generate_session_exercise_graph($exercise_graph_name_list, $user_results, $exercise_graph_list);
						}
					}
				
				
					//Complete graph
					if (!empty($all_user_results) && !empty($all_exercise_graph_list)) {						
						asort($all_exercise_start_time);
												
						//Fix exams order
						$final_all_exercise_graph_name_list = array();
						$final_all_user_results = array();
						$final_all_exercise_graph_list = array();
						
						foreach($all_exercise_start_time as $key => $time) {
							$label_time = '';
							if (!empty($time)) {
								$label_time = date('d-m-y', $time);
								//$label_time = api_format_date($time, DATE_FORMAT_NUMBER);
							}
							$final_all_exercise_graph_name_list[] 	= $all_exercise_graph_name_list[$key].' '.$label_time;
							$final_all_user_results[] 				= $all_user_results[$key];
							$final_all_exercise_graph_list[] 		= $all_exercise_graph_list[$key];
						}
						//var_dump($final_all_exercise_graph_name_list, $final_all_user_results, $final_all_exercise_graph_list);
						$main_session_graph = self::generate_session_exercise_graph($final_all_exercise_graph_name_list, $final_all_user_results, $final_all_exercise_graph_list);
					}
				}
				
				
				$html .= Display::tag('h1',Display::return_icon('session.png', get_lang('Sessions'), array(), 22).' '.get_lang('Sessions'));
				
				$html .= '<table class="data_table" width="100%">';
				//'.Display::tag('th', get_lang('DoneExercises'),            array('class'=>'head')).'
				$html .= '<tr>
                      '.Display::tag('th', get_lang('Session'),                  array('width'=>'300px')).'
                      '.Display::tag('th', get_lang('PublishedExercises'),       array('width'=>'300px')).'
                      '.Display::tag('th', get_lang('NewExercises'),            array('class'=>'head')).'                                            
                      '.Display::tag('th', get_lang('AverageExerciseResult'),    array('class'=>'head')).'
                      '.Display::tag('th', get_lang('Details'),                  array('class'=>'head')).'                        
                      </tr>';

				foreach ($course_in_session as $my_session_id => $session_data) {
					$course_list  = $session_data['course_list'];
					$session_name = $session_data['name'];
					 
					if (isset($session_id) && !empty($session_id)) {
						if ($session_id != $my_session_id) {
							continue;
						}
					}
					 
					$all_exercises = 0;
					$all_unanswered_exercises_by_user = 0;
					$all_done_exercise = 0;
					$all_average = 0;

					$stats_array = array();

					foreach($course_list as $course_data) {						
						//All exercises in the course @todo change for a real count						
						$exercises          = get_all_exercises($course_data, $my_session_id);
						$count_exercises = 0;
						if (!empty($exercises)) {
							$count_exercises 	= count($exercises);
						}
						
						//Count of user results						
						//$done_exercises     = get_count_exercises_attempted_by_course($course_data['code'], $my_session_id);
						$done_exercises     = null;
						
						$answered_exercises = 0;
						if (!empty($exercises)) {
							foreach($exercises as $exercise_item) {
								$attempts = count_exercise_attempts_by_user(api_get_user_id(), $exercise_item['id'], $course_data['code'], $my_session_id);
								if ($attempts > 1)  {
									$answered_exercises++;
								}
							}						
						}
						
						//Average
						$average            = get_average_score_by_course($course_data['code'], $my_session_id);

						$all_exercises     += $count_exercises;
						
						$all_unanswered_exercises_by_user += $count_exercises - $answered_exercises;
						//$all_done_exercise += $done_exercises;
						$all_average       += $average;
						//$stats_array[$course_data['code']] = array('exercises'=>$count_exercises, 'unanswered_exercises_by_user'=>$answered_exercises,'done_exercises'=>$done_exercises, 'average'=>$average);
					}

					$all_average = $all_average /  count($course_list);

					if (isset($_GET['session_id']) && $my_session_id == $_GET['session_id']) {
						$html .= '<tr style="background-color:#FBF09D">';
					} else {
						$html .= '<tr>';
					}
					$url = api_get_path(WEB_CODE_PATH)."session/?session_id={$my_session_id}";
					
					$html .= Display::tag('td', Display::url($session_name, $url, array('target'=>SESSION_LINK_TARGET)));
					$html .= Display::tag('td', $all_exercises);
					$html .= Display::tag('td', $all_unanswered_exercises_by_user);
					
					//$html .= Display::tag('td', $all_done_exercise);
					$html .= Display::tag('td', convert_to_percentage($all_average));

					 
					if (isset($_GET['session_id']) && $my_session_id == $_GET['session_id']) {
						$icon = Display::url(Display::return_icon('2rightarrow_na.gif', get_lang('Details')), '?session_id='.$my_session_id);
					} else {
						$icon = Display::url(Display::return_icon('2rightarrow.gif', get_lang('Details')), '?session_id='.$my_session_id);
					}
					$html .= Display::tag('td', $icon);
					$html .= '</tr>';
				}
				$html .= '</table><br />';	
				$html .= Display::div($main_session_graph, array('id'=>'session_graph','class'=>'chart-session', 'style'=>'position:relative; text-align: center;') );
			
				//Checking selected session

				if (isset($_GET['session_id'])) {
					$session_id_from_get = intval($_GET['session_id']);

					$session_data 	= $course_in_session[$session_id_from_get];
					$course_list 	= $session_data['course_list'];
					
					$html .= Display::tag('h2',$session_data['name'].' - '.get_lang('CourseList'));

					$html .= '<table class="data_table" width="100%">';
					//'.Display::tag('th', get_lang('DoneExercises'),         array('class'=>'head')).'
					$html .= '
                        <tr>
                          <th width="300px">'.get_lang('Course').'</th>
                          '.Display::tag('th', get_lang('PublishedExercises'),    	array('class'=>'head')).'
                          '.Display::tag('th', get_lang('NewExercises'),    		array('class'=>'head')).'
                          '.Display::tag('th', get_lang('MyAverage'), 				array('class'=>'head')).'                                
                          '.Display::tag('th', get_lang('AverageExerciseResult'), 	array('class'=>'head')).'                          
                          '.Display::tag('th', get_lang('TimeSpentInTheCourse'),    array('class'=>'head')).'
                          '.Display::tag('th', get_lang('LPProgress')     ,      	array('class'=>'head')).'
                          '.Display::tag('th', get_lang('Score').Display::return_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array ('align' => 'absmiddle', 'hspace' => '3px')), array('class'=>'head')).'
                          '.Display::tag('th', get_lang('LastConnexion'),         	array('class'=>'head')).'      
                          '.Display::tag('th', get_lang('Details'),               	array('class'=>'head')).'
                        </tr>';

					foreach ($course_list as $course_data) {
						$course_code 	= $course_data['code'];
						$course_title 	= $course_data['title'];						

						//All exercises in the course @todo change for a real count
						$exercises          = get_all_exercises($course_data, $session_id_from_get);
						$count_exercises = 0;
						if (!empty($exercises)) {
							$count_exercises 	= count($exercises);
						}
						//Count of user results
						//$done_exercises     = get_best_exercise_results_by_course($course_code, $session_id_from_get);
						
						//From course exercises NOT from LP exercises!!!
						//$done_exercises     = get_count_exercises_attempted_by_course($course_code, $session_id_from_get);	
						$answered_exercises = 0;
						foreach($exercises as $exercise_item) {							
							$attempts = count_exercise_attempts_by_user(api_get_user_id(), $exercise_item['id'], $course_code, $session_id_from_get);							
							if ($attempts > 1)  {
								$answered_exercises++;
							}
						}
						
						$unanswered_exercises = $count_exercises - $answered_exercises;
						
						//Average
						$average            = get_average_score_by_course($course_code, $session_id_from_get);
						$my_average			= get_average_score_by_course_by_user(api_get_user_id(), $course_code, $session_id_from_get);
						 
						$stats_array[$course_code] = array(	'exercises'						=> $count_exercises, 
															'unanswered_exercises_by_user'	=> $unanswered_exercises,
															'done_exercises'				=> $done_exercises, 
															'average'						=> $average, 			
															'my_average'					=> $my_average);

						$weighting = 0;
						$last_connection       = Tracking :: get_last_connection_date_on_the_course($user_id, $course_code, $session_id_from_get);
						$progress              = Tracking :: get_avg_student_progress($user_id, $course_code,array(), $session_id_from_get);
						$total_time_login      = Tracking :: get_time_spent_on_the_course($user_id, $course_code, $session_id_from_get);
						$time                  = api_time_to_hms($total_time_login);
						$percentage_score      = Tracking :: get_avg_student_score($user_id, $course_code, array(), $session_id_from_get);

						if ($course_code == $_GET['course'] && $_GET['session_id'] == $session_id_from_get) {
							$html .= '<tr class="row_odd" style="background-color:#FBF09D" >';
						} else {
							$html .= '<tr class="row_even">';
						}

						$url        = api_get_course_url($course_code, $session_id_from_get);
						$course_url = Display::url($course_title, $url, array('target'=>SESSION_LINK_TARGET));

						$html .= Display::tag('td', $course_url);
						$html .= Display::tag('td', $stats_array[$course_code]['exercises']);
						$html .= Display::tag('td', $stats_array[$course_code]['unanswered_exercises_by_user']);
						//$html .= Display::tag('td', $stats_array[$course_code]['done_exercises']);
						$html .= Display::tag('td', convert_to_percentage($stats_array[$course_code]['my_average']));
						
						$html .= Display::tag('td', $stats_array[$course_code]['average'] == 0 ? '-' : '('.convert_to_percentage($stats_array[$course_code]['average']).')');						
						$html .= Display::tag('td', $time, array('align'=>'center'));

						if (is_numeric($progress)) {
							$progress = $progress.'%';
						} else {
							$progress = '0%';
						}
						//Progress
						$html .= Display::tag('td', $progress, array('align'=>'center'));
						if (is_numeric($percentage_score)) {
							$percentage_score = $percentage_score.'%';
						} else {
							$percentage_score = '0%';
						}			
						//Score			
						$html .= Display::tag('td', $percentage_score, array('align'=>'center'));
						$html .= Display::tag('td', $last_connection,  array('align'=>'center'));

						if ($course_code == $_GET['course'] && $_GET['session_id'] == $session_id_from_get) {
							$details = '<a href="#">';
							$details .=Display::return_icon('2rightarrow_na.gif', get_lang('Details'));
						} else {
							$details = '<a href="'.api_get_self().'?course='.$course_code.'&session_id='.$session_id_from_get.$extra_params.'">';
							$details .=Display::return_icon('2rightarrow.gif', get_lang('Details'));
						}
						$details .= '</a>';
						$html .= Display::tag('td', $details, array('align'=>'center'));
						$html .= '</tr>';
					}
					$html .= '</table>';

					if (!empty($session_graph[$session_id_from_get])) {
						//$html .= Display::div($session_graph[$session_id_from_get], array('id'=>'session_graph','class'=>'chart-session', 'style'=>'position:relative; text-align: center;') );
					}
					
				}
			}
			if (!empty($html)) {
				$html = Display::div($html, array('class'=>'rounded_div', 'style'=>'position:relative; float:none; width:95%'));
			}
			return $html;
		}


		/**
		 * Shows the user detail progress (when clicking in the details link)
		 * @param   int     user id
		 * @param   string  course code
		 * @param   int     session id
		 * @return  string  html code
		 */
		function show_course_detail($user_id, $course_code, $session_id) {
			$html = '';
			if (isset($course_code)) {
				require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';

				$user_id                    = intval($user_id);
				$session_id                 = intval($session_id);
				$course                     = Database::escape_string($course_code);
				$course_info                = CourseManager::get_course_information($course);

				$tbl_user                   = Database :: get_main_table(TABLE_MAIN_USER);
				$tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
				$tbl_session_course         = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
				$tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
				$tbl_course_lp_view         = Database :: get_course_table(TABLE_LP_VIEW,   $course_info['db_name']);
				$tbl_course_lp_view_item    = Database :: get_course_table(TABLE_LP_ITEM_VIEW, $course_info['db_name']);
				$tbl_course_lp              = Database :: get_course_table(TABLE_LP_MAIN,   $course_info['db_name']);
				$tbl_course_lp_item         = Database :: get_course_table(TABLE_LP_ITEM,   $course_info['db_name']);
				$tbl_course_quiz            = Database :: get_course_table(TABLE_QUIZ_TEST, $course_info['db_name']);
				
				$session_name = api_get_session_name($session_id);
				$html .= Display::tag('h2', $course_info['title']);
				
				$html .= '<table class="data_table" width="100%">';
				
				// This code was commented on purpose see BT#924
				
				/*$sql = 'SELECT visibility FROM '.$course_info['db_name'].'.'.TABLE_TOOL_LIST.' WHERE name="quiz"';
				 $result_visibility_tests = Database::query($sql);
				
				if (Database::result($result_visibility_tests, 0, 'visibility') == 1) {*/
				
				//Course details
				$html .= '<tr>
				                <th class="head" style="color:#000">'.get_lang('Exercices').'</th>
				                <th class="head" style="color:#000">'.get_lang('Attempts').'</th>                    
				                <th class="head" style="color:#000">'.get_lang('BestAttempt').'</th>
				                <th class="head" style="color:#000">'.get_lang('Ranking').'</th>
				                <th class="head" style="color:#000">'.get_lang('BestResultInCourse').'</th>
				                <th class="head" style="color:#000">'.get_lang('Statistics').' '.Display :: return_icon('info3.gif', get_lang('OnlyBestResultsPerStudent'), array('align' => 'absmiddle', 'hspace' => '3px')).'</th>                                        
				                </tr>';
				
				if (empty($session_id)) {
					$sql_exercices = "SELECT quiz.title,id, results_disabled FROM ".$tbl_course_quiz." AS quiz WHERE active='1' AND session_id = 0";
				} else {
					$sql_exercices = "SELECT quiz.title,id, results_disabled FROM ".$tbl_course_quiz." AS quiz WHERE active='1'";
				}
				$result_exercices = Database::query($sql_exercices);
				$to_graph_exercise_result = array();
				if (Database::num_rows($result_exercices) > 0) {
					$score = $weighting = $exe_id = 0;
					while ($exercices = Database::fetch_array($result_exercices)) {
						//if ($exercices['id'] != 3) continue;
						$score = $weighting = $attempts = 0;
						//Getting count of attempts by user
						$attempts      = count_exercise_attempts_by_user(api_get_user_id(), $exercices['id'], $course_info['code'], $session_id);
						//For graphics
						$best_exercise_stats = get_best_exercise_results_by_user($exercices['id'], $course_info['code'], $session_id);
						$to_graph_exercise_result[$exercices['id']] = array('title'=>$exercices['title'], 'data'=>$best_exercise_stats);
				
						$html .= '<tr class="row_even">';
						$url = api_get_path(WEB_CODE_PATH)."exercice/overview.php?cidReq={$course_info['code']}&id_session=$session_id&exerciseId={$exercices['id']}";
						$exercices['title'] = Display::url($exercices['title'], $url, array('target'=>SESSION_LINK_TARGET));
						$html .= Display::tag('td', $exercices['title']);
				
						//Exercise configuration show results show results or show only score
						if ($exercices['results_disabled'] == 0 || $exercices['results_disabled'] == 2) {
							$latest_attempt_url = '';
							$best_score = $position = $percentage_score_result  = '-';
							$graph = $normal_graph = null;
				
							//Getting best results
							$best_score_data = get_best_attempt_in_course($exercices['id'], $course_info['code'], $session_id);
							$best_score      = show_score($best_score_data['exe_result'], $best_score_data['exe_weighting']);
				
							if ($attempts > 0) {
								$exercise_stat = get_best_attempt_by_user(api_get_user_id(), $exercices['id'], $course_info['code'], $session_id);
								if (!empty($exercise_stat)) {
				
									//Always getting the BEST attempt
									$score          = $exercise_stat['exe_result'];
									$weighting      = $exercise_stat['exe_weighting'];
									$exe_id         = $exercise_stat['exe_id'];
				
									//$latest_attempt_url .= '<a href="../exercice/exercise_show.php?origin=myprogress&id='.$exe_id.'&cidReq='.$course_info['code'].'&id_session='.$session_id.'"> '.Display::return_icon('quiz.gif', get_lang('Quiz')).' </a>';
									$latest_attempt_url .= '../exercice/exercise_show.php?origin=myprogress&id='.$exe_id.'&cidReq='.$course_info['code'].'&id_session='.$session_id;
									$percentage_score_result = Display::url(show_score($score, $weighting), $latest_attempt_url);
									$my_score = 0;
									if (!empty($weighting) && intval($weighting) != 0) {
										$my_score = $score/$weighting;
									}
									$position = get_exercise_result_ranking($my_score, $exe_id, $exercices['id'], $course_info['code'], $session_id);
				
									$graph         = self::generate_exercise_result_thumbnail_graph($to_graph_exercise_result[$exercices['id']]);
									$normal_graph  = self::generate_exercise_result_graph($to_graph_exercise_result[$exercices['id']]);
								}
							}
				
							echo Display::div($normal_graph, array('id'=>'main_graph_'.$exercices['id'],'class'=>'dialog', 'style'=>'display:none') );
				
							if (empty($graph)) {
								$graph = '-';
							} else {
								$graph = Display::url($graph, '#', array('id'=>$exercices['id'], 'class'=>'opener'));
							}
				
							$html .= Display::tag('td', $attempts,                 array('align'=>'center'));
							$html .= Display::tag('td', $percentage_score_result,  array('align'=>'center'));
							$html .= Display::tag('td', $position,                 array('align'=>'center'));
							$html .= Display::tag('td', $best_score,               array('align'=>'center'));
							$html .= Display::tag('td', $graph,                    array('align'=>'center'));
							//$html .= Display::tag('td', $latest_attempt_url,       array('align'=>'center', 'width'=>'25'));
				
						} else {
							// Exercise configuration NO results
							$html .= Display::tag('td', $attempts,    array('align'=>'center'));
							$html .= Display::tag('td', '-',          array('align'=>'center'));
							$html .= Display::tag('td', '-',          array('align'=>'center'));
							$html .= Display::tag('td', '-',          array('align'=>'center'));
							$html .= Display::tag('td', '-',          array('align'=>'center'));
						}
						$html .= '</tr>';
					}
				} else {
					$html .= '<tr><td colspan="5" align="center">'.get_lang('NoEx').'</td></tr>';
				}
				$html .= '</table>';
				
				
				//LP table results
				
				$html .='<table class="data_table" width="100%">';
				$html .= Display::tag('th', get_lang('Learnpaths'),    array('class'=>'head', 'style'=>'color:#000'));
				$html .= Display::tag('th', get_lang('LatencyTimeSpent'),          array('class'=>'head', 'style'=>'color:#000'));
				$html .= Display::tag('th', get_lang('Progress'),      array('class'=>'head', 'style'=>'color:#000'));
				$html .= Display::tag('th', get_lang('Score'),         array('class'=>'head', 'style'=>'color:#000'));
				$html .= Display::tag('th', get_lang('LastConnexion'), array('class'=>'head', 'style'=>'color:#000'));
				$html .= '</tr>';

				if (empty($session_id)) {
					$sql_learnpath = "SELECT lp.name,lp.id FROM ".$tbl_course_lp." AS lp  WHERE session_id = 0 ORDER BY lp.display_order";
				} else {
					$sql_learnpath = "SELECT lp.name,lp.id FROM ".$tbl_course_lp." AS lp ORDER BY lp.display_order";
				}

				$result_learnpath = Database::query($sql_learnpath);
				if (Database::num_rows($result_learnpath) > 0) {
					while($learnpath = Database::fetch_array($result_learnpath)) {
						$progress               = Tracking::get_avg_student_progress($user_id, $course, array($learnpath['id']), $session_id);
						$last_connection_in_lp  = Tracking::get_last_connection_time_in_lp($user_id, $course, $learnpath['id'], $session_id);
						$time_spent_in_lp       = Tracking::get_time_spent_in_lp($user_id, $course, array($learnpath['id']), $session_id);
						$percentage_score 		= Tracking::get_avg_student_score($user_id, $course, array($learnpath['id']), $session_id);
						if (is_numeric($percentage_score)) {
							$percentage_score = $percentage_score.'%';
						} else {
							$percentage_score = '0%';
						}

						$time_spent_in_lp       = api_time_to_hms($time_spent_in_lp);

						$html .= '<tr class="row_even">';
						$url = api_get_path(WEB_CODE_PATH)."newscorm/lp_controller.php?cidReq={$course_code}&id_session=$session_id&lp_id={$learnpath['id']}&action=view";						
						$html .= Display::tag('td', Display::url($learnpath['name'], $url, array('target'=>SESSION_LINK_TARGET)));
						$html .= Display::tag('td', $time_spent_in_lp, array('align'=>'center'));
						if (is_numeric($progress)) {
							$progress = $progress.'%';
						}
						$html .= Display::tag('td', $progress, array('align'=>'center'));
						$html .= Display::tag('td', $percentage_score);

						$last_connection = '-';
						if (!empty($last_connection_in_lp)) {
							$last_connection = api_convert_and_format_date($last_connection_in_lp, DATE_TIME_FORMAT_LONG);
						}
						$html .= Display::tag('td', $last_connection, array('align'=>'center','width'=>'180px'));
						$html .= "</tr>";
					}
				} else {
					$html .= '<tr>
                          	<td colspan="4" align="center">
                            	'.get_lang('NoLearnpath').'
							</td>
						  </tr>';
				}
				$html .='</table>';
				
				
			}
			if (!empty($html)) {
				$html = Display::div($html, array('class'=>'rounded_div', 'style'=>'position:relative; float:none; width:95%'));
			}
			return $html;
		}

		/**
		 * Generates an histogram
		 *
		 * @param 	array	list of exercise names
		 * @param 	array	my results 0 to 100
		 * @param 	array	average scores 0-100
		 */
		function generate_session_exercise_graph($names, $my_results, $average) {
			require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
			require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
			require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';

			$cache = new pCache();

			// Dataset definition
			$data_set = new pData();

			// Dataset definition
			$data_set->AddPoint($average,	 "Serie1");
			$data_set->AddPoint($my_results, "Serie2");
			$data_set->AddPoint($names,		 "Serie3");
			$data_set->AddAllSeries();
			$data_set->SetAbsciseLabelSerie('Serie3');
			$data_set->SetSerieName(get_lang('AverageScore'),"Serie1");
			$data_set->SetSerieName(get_lang('MyResults'),	 "Serie2");

			//$data_set->SetYAxisName(get_lang("Percentage"));

			$data_set->SetYAxisUnit("%");

			// Initialise the graph
			$main_width    = 860;
			$main_height   = 500;
			$y_label_angle = 50;
			$data_set->RemoveSerie("Serie3");
			$graph = new pChart($main_width, $main_height);
			//See 3.2 BT#2797
			$graph->setFixedScale(0,100);

			$graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);
			$graph->setGraphArea(65,50,$main_width-20, $main_height-140);
			
			$graph->drawFilledRoundedRectangle(7,7,$main_width-7,$main_height-7,5,240,240,240);
			$graph->drawRoundedRectangle(5,5,$main_width-5,$main_height -5,5,230,230,230);
			$graph->drawGraphArea(255,255,255,TRUE);

			//SCALE_NORMAL, SCALE_START0, SCALE_ADDALLSTART0, SCALE_ADDALL
			$graph->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_NORMAL ,150,150,150,TRUE,$y_label_angle,1, TRUE);
			$graph->drawGrid(4,TRUE,230,230,230,70);

			// Draw the 0 line
			$graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',6);
			$graph->drawTreshold(0,143,55,72,TRUE,TRUE);

			// Draw the cubic curve graph
			$graph->drawLineGraph($data_set->GetData(),$data_set->GetDataDescription());
			$graph->drawPlotGraph($data_set->GetData(),$data_set->GetDataDescription(),1,1,230,255,255);

			// Finish the graph
			$graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',10);
			$graph->drawLegend($main_width - 150,70,$data_set->GetDataDescription(),255,255,255);
			
			$graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',11);
			$graph->drawTitle(50, 30, get_lang('ExercisesInTimeProgressChart'), 50,50,50,$main_width-110, true);

			// $main_graph = new pChart($main_width,$main_height);

			$graph_id = 'generate_session_exercise_graph'.Security::remove_XSS($_GET['course']).'-'.intval($_GET['session_id']).'-'.api_get_user_id();
			if ($cache->IsInCache($graph_id, $data_set->GetData())) {
			//if (0) {
				//if we already created the img
				//echo 'in cache';
				$img_file = $cache->GetHash($graph_id,$data_set->GetData());
			} else {
				$cache->WriteToCache($graph_id, $data_set->GetData(), $graph);
				ob_start();
				$graph->Stroke();
				ob_end_clean();
				$img_file = $cache->GetHash($graph_id, $data_set->GetData());
			}
			$html = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
			return $html;
		}

		/**
		 *
		 * Returns a thumbnail of the function generate_exercise_result_graph
		 * @param 	array attempts

		 */
		function generate_exercise_result_thumbnail_graph($attempts) {
			require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
			require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
			require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';

			$exercise_title = $attempts['title'];
			$attempts       = $attempts['data'];
			$my_exercise_result_array = $exercise_result = array();
			if (empty($attempts)) {
				return null;
			}
			 
			foreach ($attempts as $attempt) {
				if (api_get_user_id() == $attempt['exe_user_id']) {
					if ($attempt['exe_weighting'] != 0 ) {
						$my_exercise_result_array[]= $attempt['exe_result']/$attempt['exe_weighting'];
					}
				} else {
					if ($attempt['exe_weighting'] != 0 ) {
						$exercise_result[]=  $attempt['exe_result']/$attempt['exe_weighting'];
					}
				}
			}

			//Getting best result
			rsort($my_exercise_result_array);
			$my_exercise_result = 0;
			if (isset($my_exercise_result_array[0])) {
				$my_exercise_result = $my_exercise_result_array[0] *100;
			}

			//var_dump($exercise_result, $my_exercise_result);

			$max     = 100;
			$pieces  = 5 ;
			$part    = round($max / $pieces);
			$x_axis = array();
			$final_array = array();
			$my_final_array = array();

			for ($i=1; $i <=$pieces; $i++) {
				$sum = 1;
				if ($i == 1) {
					$sum = 0;
				}
				$min = ($i-1)*$part + $sum;
				$max = ($i)*$part;
				$x_axis[]= $min." - ".$max;
				$count = 0;
				foreach($exercise_result as $result) {
					$percentage = $result*100;
					//echo $percentage.' - '.$min.' - '.$max."<br />";
					if ($percentage >= $min && $percentage <= $max) {
						//echo ' is > ';
						$count++;
					}
				}
				//echo '<br />';
				$final_array[]= $count;

				if ($my_exercise_result >= $min && $my_exercise_result <= $max) {
					$my_final_array[] = 1;
				} else {
					$my_final_array[] = 0;
				}
			}

			//var_dump($my_final_array, $final_array); exit;

			//Fix to remove the data of the user with my data

			for($i = 0; $i<=count($my_final_array); $i++) {
				if (!empty($my_final_array[$i])) {
					$my_final_array[$i] =  $final_array[$i] + 1; //Add my result
					$final_array[$i] = 0;
				}
			}
			//var_dump($my_final_array, $final_array); echo '<br />';

			//echo '<pre>'; var_dump($my_exercise_result, $exercise_result,$x_axis);
			 
			$cache = new pCache();

			// Dataset definition
			$data_set = new pData();
			$data_set->AddPoint($final_array,"Serie1");
			$data_set->AddPoint($my_final_array,"Serie2");
			//$data_set->AddPoint($x_axis,"Serie3");
			$data_set->AddAllSeries();
			 
			// Initialise the graph

			$main_width  = 80;
			$main_height = 35;

			$thumbnail_graph = new pChart($main_width, $main_height);

			$thumbnail_graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);
			//$thumbnail_graph->setGraphArea(50,30,680,200);
			$thumbnail_graph->drawFilledRoundedRectangle(2,2,$main_width-2,$main_height-2,2,230,230,230);
			$thumbnail_graph->setGraphArea(5,5,$main_width-5,$main_height-5);
			$thumbnail_graph->drawGraphArea(255,255,255);

			//SCALE_NORMAL, SCALE_START0, SCALE_ADDALLSTART0
			$thumbnail_graph->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_ADDALLSTART0, 150,150,150,FALSE,0,1,TRUE);

			$thumbnail_graph->drawOverlayBarGraph($data_set->GetData(),$data_set->GetDataDescription(), 100);

			// Finish the graph
			$graph_id = 'thumbnail_exercise_result_graph_'.Security::remove_XSS($_GET['course']).'-'.intval($_GET['session_id']).'-'.api_get_user_id();

			if ($cache->IsInCache($graph_id, $data_set->GetData())) {
				//if (0) {
				//if we already created the img
				//echo 'in cache';
				$img_file = $cache->GetHash($graph_id,$data_set->GetData());
			} else {
				$cache->WriteToCache($graph_id, $data_set->GetData(), $thumbnail_graph);
				ob_start();
				$thumbnail_graph->Stroke();
				ob_end_clean();
				$img_file = $cache->GetHash($graph_id, $data_set->GetData());
			}
			$html = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
			return $html;
		}

		/**
		 * Generates a big graph with the number of best results
		 * @param	array
		 */
		function generate_exercise_result_graph($attempts) {

			require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
			require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
			require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';

			$exercise_title = $attempts['title'];
			$attempts       = $attempts['data'];
			$my_exercise_result_array = $exercise_result = array();
			if (empty($attempts)) {
				return null;
			}
			foreach ($attempts as $attempt) {
				if (api_get_user_id() == $attempt['exe_user_id']) {
					if ($attempt['exe_weighting'] != 0 ) {
						$my_exercise_result_array[]= $attempt['exe_result']/$attempt['exe_weighting'];
					}
				} else {
					if ($attempt['exe_weighting'] != 0 ) {
						$exercise_result[]=  $attempt['exe_result']/$attempt['exe_weighting'];
					}
				}
			}

			//Getting best result
			rsort($my_exercise_result_array);
			$my_exercise_result = 0;
			if (isset($my_exercise_result_array[0])) {
				$my_exercise_result = $my_exercise_result_array[0] *100;
			}

			//var_dump($exercise_result, $my_exercise_result);

			$max = 100;
			$pieces = 5 ;
			$part = round($max / $pieces);
			$x_axis = array();
			$final_array = array();
			$my_final_array = array();

			for ($i=1; $i <=$pieces; $i++) {
				$sum = 1;
				if ($i == 1) {
					$sum = 0;
				}
				$min = ($i-1)*$part + $sum;
				$max = ($i)*$part;
				$x_axis[]= $min." - ".$max;
				$count = 0;
				foreach($exercise_result as $result) {
					$percentage = $result*100;
					//echo $percentage.' - '.$min.' - '.$max."<br />";
					if ($percentage >= $min && $percentage <= $max) {
						//echo ' is > ';
						$count++;
					}
				}
				//echo '<br />';
				$final_array[]= $count;

				if ($my_exercise_result >= $min && $my_exercise_result <= $max) {
					$my_final_array[] = 1;
				} else {
					$my_final_array[] = 0;
				}
			}

			//var_dump($my_final_array, $final_array); exit;

			//Fix to remove the data of the user with my data

			for($i = 0; $i<=count($my_final_array); $i++) {
				if (!empty($my_final_array[$i])) {
					$my_final_array[$i] =  $final_array[$i] + 1; //Add my result
					$final_array[$i] = 0;
				}
			}
			//var_dump($my_final_array, $final_array); echo '<br />';

			//echo '<pre>'; var_dump($my_exercise_result, $exercise_result,$x_axis);

			$cache = new pCache();

			// Dataset definition
			$data_set = new pData();
			$data_set->AddPoint($final_array,"Serie1");
			$data_set->AddPoint($my_final_array,"Serie2");
			$data_set->AddPoint($x_axis,"Serie3");
			$data_set->AddAllSeries();

			$data_set->SetAbsciseLabelSerie('Serie3');
			$data_set->SetSerieName(get_lang('Score'),"Serie1");
			$data_set->SetSerieName(get_lang('MyResults'),"Serie2");

			$data_set->SetXAxisName(get_lang("Score"));

			// Initialise the graph
			$main_width  = 500;
			$main_height = 250;

			$main_graph = new pChart($main_width,$main_height);

			$main_graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);
			$main_graph->setGraphArea(50,30, $main_width -20,$main_height -50);

			$main_graph->drawFilledRoundedRectangle(10,10, $main_width- 10,$main_height -10,5,240,240,240);
			$main_graph->drawRoundedRectangle(7,7,$main_width - 7,$main_height  - 7,5,230,230,230);

			$main_graph->drawGraphArea(255,255,255,TRUE);

			//SCALE_NORMAL, SCALE_START0, SCALE_ADDALLSTART0
			$main_graph->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_ADDALLSTART0, 150,150,150,TRUE,0,1,TRUE);
			 
			$main_graph->drawGrid(4,TRUE,230,230,230,50);

			// Draw the 0 line
			$main_graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',6);
			//  $main_graph->drawTreshold(0,143,55,72,TRUE,TRUE);

			// Draw the bar graph
			$data_set->RemoveSerie("Serie3");

			//$main_graph->drawBarGraph($data_set->GetData(),$data_set->GetDataDescription(),TRUE);

			//$main_graph->drawStackedBarGraph($data_set->GetData(),$data_set->GetDataDescription(),TRUE);
			$main_graph->drawOverlayBarGraph($data_set->GetData(),$data_set->GetDataDescription(), 100);


			// Finish the graph
			$main_graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);
			$main_graph->drawLegend($main_width - 120,$main_height -100,$data_set->GetDataDescription(),255,255,255);
			$main_graph->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);
			$main_graph->drawTitle(180,22,$exercise_title,50,50,50);
			$graph_id = 'exercise_result_graph'.Security::remove_XSS($_GET['course']).'-'.intval($_GET['session_id']).'-'.api_get_user_id();
			if ($cache->IsInCache($graph_id, $data_set->GetData())) {
				//if (0) {
				//if we already created the img
				//echo 'in cache';
				$img_file = $cache->GetHash($graph_id,$data_set->GetData());
			} else {
				$cache->WriteToCache($graph_id, $data_set->GetData(), $main_graph);
				ob_start();
				$main_graph->Stroke();
				ob_end_clean();
				$img_file = $cache->GetHash($graph_id, $data_set->GetData());
			}
			$html = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
			return $html;
		}
}
/**
 * @package chamilo.tracking
 */
class TrackingCourseLog {

	function count_item_resources() {
		global $session_id;

		$table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);

		$sql = "SELECT count(tool) AS total_number_of_items FROM $table_item_property track_resource, $table_user user" .
                " WHERE track_resource.insert_user_id = user.user_id AND id_session = $session_id ";

		if (isset($_GET['keyword'])) {
			$keyword = Database::escape_string(trim($_GET['keyword']));
			$sql .= " AND (user.username LIKE '%".$keyword."%' OR lastedit_type LIKE '%".$keyword."%' OR tool LIKE '%".$keyword."%')";
		}

		$sql .= " AND tool IN ('document', 'learnpath', 'quiz', 'glossary', 'link', 'course_description', 'announcement', 'thematic', 'thematic_advance', 'thematic_plan')";
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
		return $obj->total_number_of_items;
	}

	function get_item_resources_data($from, $number_of_items, $column, $direction) {
		global $dateTimeFormatLong, $session_id;

		$table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$table_session = Database :: get_main_table(TABLE_MAIN_SESSION);

		$sql = "SELECT
                     tool as col0,
                    lastedit_type as col1,
                    ref as ref,
                    user.username as col3,
                    insert_date as col5,
                    visibility as col6
                FROM $table_item_property track_resource, $table_user user
                WHERE track_resource.insert_user_id = user.user_id AND id_session = $session_id ";

		if (isset($_GET['keyword'])) {
			$keyword = Database::escape_string(trim($_GET['keyword']));
			$sql .= " AND (user.username LIKE '%".$keyword."%' OR lastedit_type LIKE '%".$keyword."%' OR tool LIKE '%".$keyword."%') ";
		}

		$sql .= " AND tool IN ('document', 'learnpath', 'quiz', 'glossary', 'link', 'course_description', 'announcement', 'thematic', 'thematic_advance', 'thematic_plan')";

		if ($column == 0) {
			$column = '0';
		}
		if ($column != '' && $direction != '') {
			if ($column != 2 && $column != 4) {
				$sql .=    " ORDER BY col$column $direction";
			}
		} else {
			$sql .=    " ORDER BY col5 DESC ";
		}

		$sql .= " LIMIT $from, $number_of_items ";

		$res = Database::query($sql);
		$resources = array ();
		$thematic_tools = array('thematic', 'thematic_advance', 'thematic_plan');
		while ($row = Database::fetch_array($res)) {
			$ref = $row['ref'];
			$table_name = TrackingCourseLog::get_tool_name_table($row['col0']);
			$table_tool = Database :: get_course_table($table_name['table_name']);

			$id = $table_name['id_tool'];
			$recorset = false;

			if (in_array($row['col0'], array('thematic_plan', 'thematic_advance'))) {
				$tbl_thematic = Database :: get_course_table(TABLE_THEMATIC);
				$sql = "SELECT thematic_id FROM $table_tool WHERE id = $ref";
				$rs_thematic  = Database::query($sql);
				if (Database::num_rows($rs_thematic)) {
					$row_thematic = Database::fetch_array($rs_thematic);
					$thematic_id = $row_thematic['thematic_id'];

					$query = "SELECT session.id, session.name, user.username FROM $tbl_thematic t, $table_session session, $table_user user" .
	                        " WHERE t.session_id = session.id AND session.id_coach = user.user_id AND t.id = $thematic_id";
					$recorset = Database::query($query);
				}
			} else {
				$query = "SELECT session.id, session.name, user.username FROM $table_tool tool, $table_session session, $table_user user" .
                        " WHERE tool.session_id = session.id AND session.id_coach = user.user_id AND tool.$id = $ref";
				$recorset = Database::query($query);
			}

			if (!empty($recorset)) {
				$obj = Database::fetch_object($recorset);
				 

				$name_session = '';
				$coach_name = '';
				if (!empty($obj)) {
					$name_session = $obj->name;
					$coach_name   = $obj->username;
				}

				$url_tool = api_get_path(WEB_CODE_PATH).$table_name['link_tool'];
				$row[0] = '';
				if ($row['col6'] != 2) {
					if (in_array($row['col0'], $thematic_tools)) {

						$exp_thematic_tool = explode('_', $row['col0']);
						$thematic_tool_title = '';
						if (is_array($exp_thematic_tool)) {
							foreach ($exp_thematic_tool as $exp) {
								$thematic_tool_title .= api_ucfirst($exp);
							}
						} else {
							$thematic_tool_title = api_ucfirst($row['col0']);
						}

						$row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'&action=thematic_details">'.get_lang($thematic_tool_title).'</a>';
					} else {
						$row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'">'.get_lang('Tool'.api_ucfirst($row['col0'])).'</a>';
					}
				} else {
					$row[0] = api_ucfirst($row['col0']);
				}
				$row[1] = get_lang($row[1]);
				$row[5] = api_convert_and_format_date($row['col5'], null, date_default_timezone_get());
				$row[4] = '';
				//@todo Improve this code please
				switch ($table_name['table_name']) {
					case 'document' :
						$query_document = "SELECT tool.title as title FROM $table_tool tool WHERE id = $ref";
						$rs_document = Database::query($query_document);
						$obj_document = Database::fetch_object($rs_document);
						$row[4] = $obj_document->title;

						break;
					case 'announcement':
						$query_document = "SELECT title FROM $table_tool " .
                                            " WHERE id = $ref";
						$rs_document = Database::query($query_document);
						$obj_document = Database::fetch_object($rs_document);
						$row[4] = $obj_document->title;
						break;
					case 'glossary':
						$query_document = "SELECT name FROM $table_tool " .
                                                " WHERE glossary_id = $ref";
						$rs_document = Database::query($query_document);
						$obj_document = Database::fetch_object($rs_document);
						$row[4] = $obj_document->name;
						break;
					case 'lp':
						$query_document = "SELECT name FROM $table_tool " .
                                                " WHERE id = $ref";
						$rs_document = Database::query($query_document);
						$obj_document = Database::fetch_object($rs_document);
						$row[4] = $obj_document->name;
						break;
					case 'quiz':
						$query_document = "SELECT title FROM $table_tool " .
                                                " WHERE id = $ref";
						$rs_document = Database::query($query_document);
						$obj_document = Database::fetch_object($rs_document);
						$row[4] = $obj_document->title;
						break;

					case 'course_description':
						$query_document = "SELECT title FROM $table_tool " .
                                                " WHERE id = $ref";
						$rs_document = Database::query($query_document);
						$obj_document = Database::fetch_object($rs_document);
						$row[4] = $obj_document->title;
						break;

					case 'thematic':
						$rs = Database::query("SELECT title FROM $table_tool WHERE id = $ref");
						if (Database::num_rows($rs) > 0) {
							$obj = Database::fetch_object($rs);
							$row[4] = $obj->title;
						}
						break;
					case 'thematic_advance':
						$rs = Database::query("SELECT content FROM $table_tool WHERE id = $ref");
						if (Database::num_rows($rs) > 0) {
							$obj = Database::fetch_object($rs);
							$row[4] = $obj->content;
						}
						break;
					case 'thematic_plan':
						$rs = Database::query("SELECT title FROM $table_tool WHERE id = $ref");
						if (Database::num_rows($rs) > 0) {
							$obj = Database::fetch_object($rs);
							$row[4] = $obj->title;
						}
						break;

					default:
						break;
				}

				$row2 = $name_session;
				if (!empty($coach_name)) {
					$row2 .= '<br />'.get_lang('Coach').': '.$coach_name;
				}
				$row[2] = $row2;
				$resources[] = $row;
			}
		}

		return $resources;
	}

	function get_tool_name_table($tool) {
		switch ($tool) {
			case 'document':
				$table_name = TABLE_DOCUMENT;
				$link_tool = 'document/document.php';
				$id_tool = 'id';
				break;
			case 'learnpath':
				$table_name = TABLE_LP_MAIN;
				$link_tool = 'newscorm/lp_controller.php';
				$id_tool = 'id';
				break;
			case 'quiz':
				$table_name = TABLE_QUIZ_TEST;
				$link_tool = 'exercice/exercice.php';
				$id_tool = 'id';
				break;
			case 'glossary':
				$table_name = TABLE_GLOSSARY;
				$link_tool = 'glossary/index.php';
				$id_tool = 'glossary_id';
				break;
			case 'link':
				$table_name = TABLE_LINK;
				$link_tool = 'link/link.php';
				$id_tool = 'id';
				break;
			case 'course_description':
				$table_name = TABLE_COURSE_DESCRIPTION;
				$link_tool = 'course_description/';
				$id_tool = 'id';
				break;
			case 'announcement':
				$table_name = TABLE_ANNOUNCEMENT;
				$link_tool = 'announcements/announcements.php';
				$id_tool = 'id';
				break;
			case 'thematic':
				$table_name = TABLE_THEMATIC;
				$link_tool = 'course_progress/index.php';
				$id_tool = 'id';
				break;
			case 'thematic_advance':
				$table_name = TABLE_THEMATIC_ADVANCE;
				$link_tool = 'course_progress/index.php';
				$id_tool = 'id';
				break;
			case 'thematic_plan':
				$table_name = TABLE_THEMATIC_PLAN;
				$link_tool = 'course_progress/index.php';
				$id_tool = 'id';
				break;
			default:
				$table_name = $tool;
			break;
		}
		return array('table_name' => $table_name,'link_tool' => $link_tool,'id_tool' => $id_tool);
	}

	function display_additional_profile_fields() {
		// getting all the extra profile fields that are defined by the platform administrator
		$extra_fields = UserManager :: get_extra_fields(0,50,5,'ASC');


		// creating the form
		$return = '<form action="courseLog.php" method="get" name="additional_profile_field_form" id="additional_profile_field_form">';

		// the select field with the additional user profile fields (= this is where we select the field of which we want to see
		// the information the users have entered or selected.
		$return .= '<select name="additional_profile_field">';
		$return .= '<option value="-">'.get_lang('SelectFieldToAdd').'</option>';
		$extra_fields_to_show = 0;
		foreach ($extra_fields as $key=>$field) {
			// show only extra fields that are visible + and can be filtered, added by J.Montoya
			if ($field[6]==1 && $field[8] == 1) {
				if (isset($_GET['additional_profile_field']) && $field[0] == $_GET['additional_profile_field'] ) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
				$extra_fields_to_show++;
				$return .= '<option value="'.$field[0].'" '.$selected.'>'.$field[3].'</option>';
			}
		}
		$return .= '</select>';

		// the form elements for the $_GET parameters (because the form is passed through GET
		foreach ($_GET as $key=>$value){
			if ($key <> 'additional_profile_field')    {
				$return .= '<input type="hidden" name="'.Security::remove_XSS($key).'" value="'.Security::remove_XSS($value).'" />';
			}
		}
		// the submit button
		$return .= '<button class="save" type="submit">'.get_lang('AddAdditionalProfileField').'</button>';
		$return .= '</form>';
		if ($extra_fields_to_show > 0) {
			return $return;
		} else {
			return '';
		}
	}

	/**
	 * This function gets all the information of a certrain ($field_id) additional profile field.
	 * It gets the information of all the users so that it can be displayed in the sortable table or in the csv or xls export
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @since October 2009
	 * @version 1.8.7
	 */
	function get_addtional_profile_information_of_field($field_id){
		// Database table definition
		$table_user             = Database::get_main_table(TABLE_MAIN_USER);
		$table_user_field_values     = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

		$sql = "SELECT user.user_id, field.field_value FROM $table_user user, $table_user_field_values field
            WHERE user.user_id = field.user_id
            AND field.field_id='".intval($field_id)."'";
		$result = Database::query($sql);
		while($row = Database::fetch_array($result)) {
			$return[$row['user_id']][] = $row['field_value'];
		}
		return $return;
	}

	/**
	 * This function gets all the information of a certrain ($field_id) additional profile field for a specific list of users is more efficent than  get_addtional_profile_information_of_field() function
	 * It gets the information of all the users so that it can be displayed in the sortable table or in the csv or xls export
	 *
	 * @author    Julio Montoya <gugli100@gmail.com>
	 * @param    int field id
	 * @param    array list of user ids
	 * @return    array
	 * @since    Nov 2009
	 * @version    1.8.6.2
	 */
	function get_addtional_profile_information_of_field_by_user($field_id, $users) {
		// Database table definition
		$table_user                 = Database::get_main_table(TABLE_MAIN_USER);
		$table_user_field_values     = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$result_extra_field         = UserManager::get_extra_field_information($field_id);

		if (!empty($users)) {
			if ($result_extra_field['field_type'] == USER_FIELD_TYPE_TAG ) {
				foreach($users as $user_id) {
					$user_result = UserManager::get_user_tags($user_id, $field_id);
					$tag_list = array();
					foreach($user_result as $item) {
						$tag_list[] = $item['tag'];
					}
					$return[$user_id][] = implode(', ',$tag_list);
				}
			} else {
				$new_user_array = array();
				foreach($users as $user_id) {
					$new_user_array[]= "'".$user_id."'";
				}
				$users = implode(',',$new_user_array);
				//selecting only the necessary information NOT ALL the user list
				$sql = "SELECT user.user_id, field.field_value FROM $table_user user INNER JOIN $table_user_field_values field
                        ON (user.user_id = field.user_id)
                        WHERE field.field_id=".intval($field_id)." AND user.user_id IN ($users)";

				$result = Database::query($sql);
				while($row = Database::fetch_array($result)) {
					// get option value for field type double select by id
					if (!empty($row['field_value'])) {
						if ($result_extra_field['field_type'] == USER_FIELD_TYPE_DOUBLE_SELECT) {
							$id_double_select = explode(';',$row['field_value']);
							if (is_array($id_double_select)) {
								$value1 = $result_extra_field['options'][$id_double_select[0]]['option_value'];
								$value2 = $result_extra_field['options'][$id_double_select[1]]['option_value'];
								$row['field_value'] = ($value1.';'.$value2);
							}
						}
					}
					// get other value from extra field
					$return[$row['user_id']][] = $row['field_value'];
				}
			}
		}
		return $return;
	}

	/**
	 * count the number of students in this course (used for SortableTable)
	 * Deprecated
	 */
	function count_student_in_course() {
		global $nbStudents;
		return $nbStudents;
	}

	function sort_users($a, $b) {
		return strcmp(trim(api_strtolower($a[$_SESSION['tracking_column']])), trim(api_strtolower($b[$_SESSION['tracking_column']])));
	}

	function sort_users_desc($a, $b) {
		return strcmp( trim(api_strtolower($b[$_SESSION['tracking_column']])), trim(api_strtolower($a[$_SESSION['tracking_column']])));
	}

	/**
	 * Get number of users for sortable with pagination
	 * @return int
	 */
	function get_number_of_users() {
		global $user_ids;
		return count($user_ids);
	}

	/**
	 * Get data for users list in sortable with pagination
	 * @return array
	 */
	function get_user_data($from, $number_of_items, $column, $direction) {
		global $user_ids, $course_code, $additional_user_profile_info, $export_csv, $is_western_name_order, $csv_content, $session_id, $_configuration;

		$course_code        = Database::escape_string($course_code);
		$tbl_user           = Database::get_main_table(TABLE_MAIN_USER);
		$tbl_url_rel_user   = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

		$course_info        = CourseManager::get_course_information($course_code);
		$access_url_id      = api_get_current_access_url_id();

		// get all users data from a course for sortable with limit
		$condition_user = "";
		if (is_array($user_ids)) {
			$user_ids = array_map('intval', $user_ids);
			$condition_user = " WHERE user.user_id IN (".implode(',',$user_ids).") ";
		} else {
			$user_ids = intval($user_ids);
			$condition_user = " WHERE user.user_id = $user_ids ";
		}
		 
		if (!empty($_GET['user_keyword'])) {
			$keyword = trim(Database::escape_string($_GET['user_keyword']));
			$condition_user .=  " AND  (user.firstname LIKE '%".$keyword."%' OR user.lastname LIKE '%".$keyword."%'  OR user.username LIKE '%".$keyword."%'  OR user.email LIKE '%".$keyword."%' ) ";
		}

		if ($_configuration['multiple_access_urls']) {
			$url_table = ", ".$tbl_url_rel_user."as url_users";
			$url_condition = " AND user.user_id = url_users.user_id AND access_url_id='$access_url_id'";
		}

		$sql = "SELECT user.user_id as user_id,
                user.official_code  as col0,
                user.lastname       as col1,
                user.firstname      as col2
                FROM $tbl_user as user $url_table
		$condition_user $url_condition";

		if (!in_array($direction, array('ASC','DESC'))) {
			$direction = 'ASC';
		}
		$column = intval($column);

		if ($is_western_name_order) {
			$original_column = $column;
			if ($original_column == 1) {
				$column = 2;
			}
			if ($original_column == 2) {
				$column = 1;
			}
		}
		$from            = intval($from);
		$number_of_items = intval($number_of_items);

		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";

		$res = Database::query($sql);
		$users = array ();
		$t = time();
		$row = array();
		while ($user = Database::fetch_array($res, 'ASSOC')) {
			//$user['user_id'] = $user['user_id']
			$user['official_code']  = $user['col0'];
			if ($is_western_name_order) {
				$user['lastname']       = $user['col2'];
				$user['firstname']      = $user['col1'];
			} else {
				$user['lastname']       = $user['col1'];
				$user['firstname']      = $user['col2'];
			}
			$user['time'] = api_time_to_hms(Tracking::get_time_spent_on_the_course($user['user_id'], $course_code, $session_id));

			$avg_student_score      = Tracking::get_avg_student_score($user['user_id'], $course_code, array(), $session_id);

			$avg_student_progress   = Tracking::get_avg_student_progress($user['user_id'], $course_code, array(), $session_id);
			if (empty($avg_student_progress)) {
				$avg_student_progress=0;
			}
			$user['average_progress'] = $avg_student_progress.'%';

			if (is_numeric($avg_student_score)) {
				$user['student_score'] = $avg_student_score.'%';
			} else {
				$user['student_score'] = $avg_student_score;
			}
			$user['count_assignments']  = Tracking::count_student_assignments($user['user_id'], $course_code, $session_id);
			$user['count_messages']     = Tracking::count_student_messages($user['user_id'], $course_code, $session_id);
			$user['first_connection']   = Tracking::get_first_connection_date_on_the_course($user['user_id'], $course_code, $session_id);
			$user['last_connection']    = Tracking::get_last_connection_date_on_the_course($user['user_id'], $course_code, $session_id);

			// we need to display an additional profile field
			$user['additional']='';
			if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {
				if (isset($additional_user_profile_info[$user['user_id']]) && is_array($additional_user_profile_info[$user['user_id']])) {
					$user['additional'] = implode(', ', $additional_user_profile_info[$user['user_id']]);
				}
			}
			$user['link'] = '<center><a href="../mySpace/myStudents.php?student='.$user['user_id'].'&details=true&course='.$course_code.'&origin=tracking_course&id_session='.$session_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></center>';

			// store columns in array $users
			$user_row = array($user['official_code'],
			$user['lastname'],
			$user['firstname'],
			$user['time'],
			$user['average_progress'],
			$user['student_score'],
			$user['count_assignments'],
			$user['count_messages'],
			$user['first_connection'],
			$user['last_connection'],
			$user['additional'],
			$user['link']);
			$users[] = $user_row;
			if ($export_csv) {
				unset($user_row[11]);
				unset($user_row[12]);
				$csv_content[] = $user_row;
			}
		}
		return $users;
	}
}
/**
 * @package chamilo.tracking
 */
class TrackingUserLog {

	/**
	 * Displays the number of logins every month for a specific user in a specific course.
	 */
	function display_login_tracking_info($view, $user_id, $course_id, $session_id = 0)
	{
		$MonthsLong = $GLOBALS['MonthsLong'];

		// protected data
		$user_id = intval($user_id);
		$session_id = intval($session_id);
		$course_id = Database::escape_string($course_id);

		$track_access_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
		$tempView = $view;
		if(substr($view,0,1) == '1') {
			$new_view = substr_replace($view,'0',0,1);
			echo "
                <tr>
                    <td valign='top'>
                    <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font>" .
                    "<b>".get_lang('LoginsAndAccessTools')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".$user_id."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=10000'>".get_lang('ExportAsCSV')."</a>]
                    </td>
                </tr>
                ";
			echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('LoginsDetails')."<br>";

			$sql = "SELECT UNIX_TIMESTAMP(access_date), count(access_date)
                        FROM $track_access_table
                        WHERE access_user_id = '$user_id'
                        AND access_cours_code = '$course_id'
                        AND access_session_id = '$session_id'
                        GROUP BY YEAR(access_date),MONTH(access_date)
                        ORDER BY YEAR(access_date),MONTH(access_date) ASC";

			echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
			//$results = getManyResults2Col($sql);
			$results = getManyResults3Col($sql);

			echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
			echo "<tr>
                    <td class='secLine'>
                    ".get_lang('LoginsTitleMonthColumn')."
                    </td>
                    <td class='secLine'>
                    ".get_lang('LoginsTitleCountColumn')."
                    </td>
                </tr>";
			$total = 0;
			if (is_array($results)) {
				for($j = 0 ; $j < count($results) ; $j++) {
					echo "<tr>";
					echo "<td class='content'><a href='logins_details.php?uInfo=".$user_id."&reqdate=".$results[$j][0]."&view=".Security::remove_XSS($view)."'>".$MonthsLong[date('n', $results[$j][0])-1].' '.date('Y', $results[$j][0])."</a></td>";
					echo "<td valign='top' align='right' class='content'>".$results[$j][1]."</td>";
					echo"</tr>";
					$total = $total + $results[$j][1];
				}
				echo "<tr>";
				echo "<td>".get_lang('Total')."</td>";
				echo "<td align='right' class='content'>".$total."</td>";
				echo"</tr>";
			} else {
				echo "<tr>";
				echo "<td colspan='2'><center>".get_lang('NoResult')."</center></td>";
				echo"</tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		} else {
			$new_view = substr_replace($view,'1',0,1);
			echo "
                <tr>
                    <td valign='top'>
                    +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".$user_id."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('LoginsAndAccessTools')."</a>
                    </td>
                </tr>
            ";
		}
	}

	/**
	 * Displays the exercise results for a specific user in a specific course.
	 * @todo remove globals
	 */
	function display_exercise_tracking_info($view, $user_id, $course_id)
	{
		global $TABLECOURSE_EXERCICES, $TABLETRACK_EXERCICES, $dateTimeFormatLong;
		if(substr($view,1,1) == '1')
		{
			$new_view = substr_replace($view,'0',1,1);
			echo "<tr>
                    <td valign='top'>
                        <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('ExercicesResults')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=01000'>".get_lang('ExportAsCSV')."</a>]
                    </td>
                </tr>";
			echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('ExercicesDetails')."<br />";

			$sql = "SELECT ce.title, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                FROM $TABLECOURSE_EXERCICES AS ce , $TABLETRACK_EXERCICES AS te
                WHERE te.exe_cours_id = '".Database::escape_string($course_id)."'
                    AND te.exe_user_id = '".Database::escape_string($user_id)."'
                    AND te.exe_exo_id = ce.id
                ORDER BY ce.title ASC, te.exe_date ASC";

			$hpsql = "SELECT te.exe_name, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                FROM $TBL_TRACK_HOTPOTATOES AS te
                WHERE te.exe_user_id = '".Database::escape_string($user_id)."' AND te.exe_cours_id = '".Database::escape_string($course_id)."'
                ORDER BY te.exe_cours_id ASC, te.exe_date ASC";

			$hpresults = getManyResultsXCol($hpsql, 4);

			$NoTestRes = 0;
			$NoHPTestRes = 0;

			echo "<tr>\n<td style='padding-left : 40px;padding-right : 40px;'>\n";
			$results = getManyResultsXCol($sql, 4);
			echo "<table cellpadding='2' cellspacing='1' border='0' align='center'>\n";
			echo "
                <tr bgcolor='#E6E6E6'>
                    <td>
                    ".get_lang('ExercicesTitleExerciceColumn')."
                    </td>
                    <td>
                    ".get_lang('Date')."
                    </td>
                    <td>
                    ".get_lang('ExercicesTitleScoreColumn')."
                    </td>
                </tr>";

			if (is_array($results)) {
				for($i = 0; $i < sizeof($results); $i++) {
					$display_date = api_convert_and_format_date($results[$i][3], null, date_default_timezone_get());
					echo "<tr>\n";
					echo "<td class='content'>".$results[$i][0]."</td>\n";
					echo "<td class='content'>".$display_date."</td>\n";
					echo "<td valign='top' align='right' class='content'>".$results[$i][1]." / ".$results[$i][2]."</td>\n";
					echo "</tr>\n";
				}
			} else {
				// istvan begin
				$NoTestRes = 1;
			}

			// The Result of Tests
			if(is_array($hpresults)) {
				for($i = 0; $i < sizeof($hpresults); $i++) {
					$title = GetQuizName($hpresults[$i][0],'');
					if ($title == '')
					$title = basename($hpresults[$i][0]);
					$display_date = api_convert_and_format_date($hpresults[$i][3], null, date_default_timezone_get());
					?>
<tr>
	<td class="content"><?php echo $title; ?></td>
	<td class="content" align="center"><?php echo $display_date; ?></td>
	<td class="content" align="center"><?php echo $hpresults[$i][1]; ?> / <?php echo $hpresults[$i][2]; ?>
	</td>
</tr>

<?php        }
			} else {
				$NoHPTestRes = 1;
			}

			if ($NoTestRes == 1 && $NoHPTestRes == 1) {
				echo "<tr>\n";
				echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>\n";
				echo "</tr>\n";
			}
			echo "</table>";
			echo "</td>\n</tr>\n";
		} else {
			$new_view = substr_replace($view,'1',1,1);
			echo "
                <tr>
                    <td valign='top'>
                        +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=$user_id&view=".$new_view."' class='specialLink'>".get_lang('ExercicesResults')."</a>
                    </td>
                </tr>";
		}
	}

	/**
	 * Displays the student publications for a specific user in a specific course.
	 * @todo remove globals
	 */
	function display_student_publications_tracking_info($view, $user_id, $course_id)
	{
		global $TABLETRACK_UPLOADS, $TABLECOURSE_WORK, $dateTimeFormatLong, $_course;
		if(substr($view,2,1) == '1') {
			$new_view = substr_replace($view,'0',2,1);
			echo "<tr>
                        <td valign='top'>
                        <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('WorkUploads')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00100'>".get_lang('ExportAsCSV')."</a>]
                        </td>
                </tr>";
			echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('WorksDetails')."<br>";
			$sql = "SELECT u.upload_date, w.title, w.author,w.url
                                FROM $TABLETRACK_UPLOADS u , $TABLECOURSE_WORK w
                                WHERE u.upload_work_id = w.id
                                    AND u.upload_user_id = '".Database::escape_string($user_id)."'
                                    AND u.upload_cours_id = '".Database::escape_string($course_id)."'
                                ORDER BY u.upload_date DESC";
			echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
			$results = getManyResultsXCol($sql,4);
			echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
			echo "<tr>
                    <td class='secLine' width='40%'>
                    ".get_lang('WorkTitle')."
                    </td>
                    <td class='secLine' width='30%'>
                    ".get_lang('WorkAuthors')."
                    </td>
                    <td class='secLine' width='30%'>
                    ".get_lang('Date')."
                    </td>
                </tr>";
			if (is_array($results)) {
				for($j = 0 ; $j < count($results) ; $j++) {
					$pathToFile = api_get_path(WEB_COURSE_PATH).$_course['path']."/".$results[$j][3];
					$beautifulDate = api_convert_and_format_date($results[$j][0], null, date_default_timezone_get());
					echo "<tr>";
					echo "<td class='content'>"
					."<a href ='".$pathToFile."'>".$results[$j][1]."</a>"
					."</td>";
					echo "<td class='content'>".$results[$j][2]."</td>";
					echo "<td class='content'>".$beautifulDate."</td>";
					echo"</tr>";
				}
			} else {
				echo "<tr>";
				echo "<td colspan='3'><center>".get_lang('NoResult')."</center></td>";
				echo"</tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		} else {
			$new_view = substr_replace($view,'1',2,1);
			echo "
                <tr>
                        <td valign='top'>
                        +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('WorkUploads')."</a>
                        </td>
                </tr>
            ";
		}
	}

	/**
	 * Displays the links followed for a specific user in a specific course.
	 * @todo remove globals
	 */
	function display_links_tracking_info($view, $user_id, $course_id)
	{
		global $TABLETRACK_LINKS, $TABLECOURSE_LINKS;
		if(substr($view,3,1) == '1') {
			$new_view = substr_replace($view,'0',3,1);
			echo "
                <tr>
                        <td valign='top'>
                        <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('LinksAccess')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00010'>".get_lang('ExportAsCSV')."</a>]
                        </td>
                </tr>
            ";
			echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('LinksDetails')."<br>";
			$sql = "SELECT cl.title, cl.url
                        FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
                        WHERE sl.links_link_id = cl.id
                            AND sl.links_cours_id = '".Database::escape_string($course_id)."'
                            AND sl.links_user_id = '".Database::escape_string($user_id)."'
                        GROUP BY cl.title, cl.url";
			echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
			$results = getManyResults2Col($sql);
			echo "<table cellpadding='2' cellspacing='1' border='0' align=center>";
			echo "<tr>
                    <td class='secLine'>
                    ".get_lang('LinksTitleLinkColumn')."
                    </td>
                </tr>";
			if (is_array($results)) {
				for($j = 0 ; $j < count($results) ; $j++) {
					echo "<tr>";
					echo "<td class='content'><a href='".$results[$j][1]."'>".$results[$j][0]."</a></td>";
					echo"</tr>";
				}
			} else {
				echo "<tr>";
				echo "<td ><center>".get_lang('NoResult')."</center></td>";
				echo"</tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		} else {
			$new_view = substr_replace($view,'1',3,1);
			echo "
                <tr>
                        <td valign='top'>
                        +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('LinksAccess')."</a>
                        </td>
                </tr>
            ";
		}
	}

	/**
	 * Displays the documents downloaded for a specific user in a specific course.
	 * @param     string    kind of view inside tracking info
	 * @param    int        User id
	 * @param    string    Course code
	 * @param    int        Session id (optional, default = 0)
	 * @return     void
	 */
	function display_document_tracking_info($view, $user_id, $course_id, $session_id = 0) {

		// protect data
		$user_id     = intval($user_id);
		$course_id     = Database::escape_string($course_id);
		$session_id = intval($session_id);

		$downloads_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
		if(substr($view,4,1) == '1')
		{
			$new_view = substr_replace($view,'0',4,1);
			echo "
                <tr>
                        <td valign='top'>
                        <font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font><b>".get_lang('DocumentsAccess')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=00001'>".get_lang('ExportAsCSV')."</a>]
                        </td>
                </tr>
            ";
			echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('DocumentsDetails')."<br>";

			$sql = "SELECT down_doc_path
                        FROM $downloads_table
                        WHERE down_cours_id = '".$course_id."'
                            AND down_user_id = '$user_id'
                            AND down_session_id = '$session_id'
                        GROUP BY down_doc_path";

			echo "<tr><td style='padding-left : 40px;padding-right : 40px;'>";
			$results = getManyResults1Col($sql);
			echo "<table cellpadding='2' cellspacing='1' border='0' align='center'>";
			echo "<tr>
                    <td class='secLine'>
                    ".get_lang('DocumentsTitleDocumentColumn')."
                    </td>
                </tr>";
			if (is_array($results)) {
				for($j = 0 ; $j < count($results) ; $j++) {
					echo "<tr>";
					echo "<td class='content'>".$results[$j]."</td>";
					echo"</tr>";
				}
			} else {
				echo "<tr>";
				echo "<td><center>".get_lang('NoResult')."</center></td>";
				echo"</tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		} else {
			$new_view = substr_replace($view,'1',4,1);
			echo "
                <tr>
                        <td valign='top'>
                        +<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('DocumentsAccess')."</a>
                        </td>
                </tr>
            ";
		}
	}
}
/**
 * @package chamilo.tracking
 */
class TrackingUserLogCSV {

	/**
	 * Displays the number of logins every month for a specific user in a specific course.
	 */
	function display_login_tracking_info($view, $user_id, $course_id, $session_id = 0)
	{
		$MonthsLong = $GLOBALS['MonthsLong'];
		$track_access_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);

		// protected data
		$user_id    = intval($user_id);
		$session_id = intval($session_id);
		$course_id  = Database::escape_string($course_id);

		$tempView = $view;
		if(substr($view,0,1) == '1')
		{
			$new_view = substr_replace($view,'0',0,1);
			$title[1]= get_lang('LoginsAndAccessTools').get_lang('LoginsDetails');

			$sql = "SELECT UNIX_TIMESTAMP(access_date), count(access_date)
                        FROM $track_access_table
                        WHERE access_user_id = '$user_id'
                        AND access_cours_code = '".$course_id."'
                        AND access_session_id = '$session_id'
                        GROUP BY YEAR(access_date),MONTH(access_date)
                        ORDER BY YEAR(access_date),MONTH(access_date) ASC";

			//$results = getManyResults2Col($sql);
			$results = getManyResults3Col($sql);

			$title_line= get_lang('LoginsTitleMonthColumn').';'.get_lang('LoginsTitleCountColumn')."\n";
			$line='';
			$total = 0;
			if (is_array($results))
			{
				for($j = 0 ; $j < count($results) ; $j++)
				{
					$line .= $results[$j][0].';'.$results[$j][1]."\n";
					$total = $total + $results[$j][1];
				}
				$line .= get_lang('Total').";".$total."\n";
			}
			else
			{
				$line= get_lang('NoResult')."</center></td>";
			}
		}
		else
		{
			$new_view = substr_replace($view,'1',0,1);
		}
		return array($title_line, $line);
	}

	/**
	 * Displays the exercise results for a specific user in a specific course.
	 * @todo remove globals
	 */
	function display_exercise_tracking_info($view, $user_id, $course_id) {
		global $TABLECOURSE_EXERCICES, $TABLETRACK_EXERCICES, $TABLETRACK_HOTPOTATOES, $dateTimeFormatLong;
		if (substr($view,1,1) == '1') {
			$new_view = substr_replace($view,'0',1,1);

			$title[1]= get_lang('ExercicesDetails');
			$line='';

			$sql = "SELECT ce.title, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                FROM $TABLECOURSE_EXERCICES AS ce , $TABLETRACK_EXERCICES AS te
                WHERE te.exe_cours_id = '$course_id'
                    AND te.exe_user_id = '$user_id'
                    AND te.exe_exo_id = ce.id
                ORDER BY ce.title ASC, te.exe_date ASC";

			$hpsql = "SELECT te.exe_name, te.exe_result , te.exe_weighting, UNIX_TIMESTAMP(te.exe_date)
                FROM $TABLETRACK_HOTPOTATOES AS te
                WHERE te.exe_user_id = '$user_id' AND te.exe_cours_id = '$course_id'
                ORDER BY te.exe_cours_id ASC, te.exe_date ASC";

			$hpresults = getManyResultsXCol($hpsql, 4);

			$NoTestRes = 0;
			$NoHPTestRes = 0;

			$results = getManyResultsXCol($sql, 4);
			$title_line=get_lang('ExercicesTitleExerciceColumn').";".get_lang('Date').';'.get_lang('ExercicesTitleScoreColumn')."\n";

			if (is_array($results))
			{
				for($i = 0; $i < sizeof($results); $i++)
				{
					$display_date = api_convert_and_format_date($results[$i][3], null, date_default_timezone_get());
					$line .= $results[$i][0].";".$display_date.";".$results[$i][1]." / ".$results[$i][2]."\n";
				}
			}
			else // istvan begin
			{
				$NoTestRes = 1;
			}

			// The Result of Tests
			if(is_array($hpresults))
			{
				for($i = 0; $i < sizeof($hpresults); $i++)
				{
					$title = GetQuizName($hpresults[$i][0],'');

					if ($title == '')
					$title = basename($hpresults[$i][0]);

					$display_date = api_convert_and_format_date($hpresults[$i][3], null, date_default_timezone_get());

					$line .= $title.';'.$display_date.';'.$hpresults[$i][1].'/'.$hpresults[$i][2]."\n";
				}
			}
			else
			{
				$NoHPTestRes = 1;
			}

			if ($NoTestRes == 1 && $NoHPTestRes == 1)
			{
				$line=get_lang('NoResult');
			}
		}
		else
		{
			$new_view = substr_replace($view,'1',1,1);

		}
		return array($title_line, $line);
	}

	/**
	 * Displays the student publications for a specific user in a specific course.
	 * @todo remove globals
	 */
	function display_student_publications_tracking_info($view, $user_id, $course_id) {
		global $TABLETRACK_UPLOADS, $TABLECOURSE_WORK, $dateTimeFormatLong, $_course;
		if (substr($view,2,1) == '1') {
			$new_view = substr_replace($view,'0',2,1);
			$sql = "SELECT u.upload_date, w.title, w.author, w.url
                    FROM $TABLETRACK_UPLOADS u , $TABLECOURSE_WORK w
                    WHERE u.upload_work_id = w.id
                        AND u.upload_user_id = '$user_id'
                        AND u.upload_cours_id = '$course_id'
                    ORDER BY u.upload_date DESC";
			$results = getManyResultsXCol($sql,4);

			$title[1]=get_lang('WorksDetails');
			$line='';
			$title_line=get_lang('WorkTitle').";".get_lang('WorkAuthors').";".get_lang('Date')."\n";

			if (is_array($results)) {
				for($j = 0 ; $j < count($results) ; $j++) {
					$pathToFile = api_get_path(WEB_COURSE_PATH).$_course['path']."/".$results[$j][3];
					$beautifulDate = api_convert_and_format_date($results[$j][0], null, date_default_timezone_get());
					$line .= $results[$j][1].";".$results[$j][2].";".$beautifulDate."\n";
				}

			} else {
				$line= get_lang('NoResult');
			}
		} else {
			$new_view = substr_replace($view,'1',2,1);
		}
		return array($title_line, $line);
	}

	/**
	 * Displays the links followed for a specific user in a specific course.
	 * @todo remove globals
	 */
	function display_links_tracking_info($view, $user_id, $course_id) {
		global $TABLETRACK_LINKS, $TABLECOURSE_LINKS;
		if (substr($view,3,1) == '1') {
			$new_view = substr_replace($view,'0',3,1);
			$title[1]=get_lang('LinksDetails');
			$sql = "SELECT cl.title, cl.url
                        FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
                        WHERE sl.links_link_id = cl.id
                            AND sl.links_cours_id = '$course_id'
                            AND sl.links_user_id = '$user_id'
                        GROUP BY cl.title, cl.url";
			$results = getManyResults2Col($sql);
			$title_line= get_lang('LinksTitleLinkColumn')."\n";
			if (is_array($results)) {
				for ($j = 0 ; $j < count($results) ; $j++) {
					$line .= $results[$j][0]."\n";
				}
			} else {
				$line=get_lang('NoResult');
			}
		} else {
			$new_view = substr_replace($view,'1',3,1);
		}
		return array($title_line, $line);
	}

	/**
	 * Displays the documents downloaded for a specific user in a specific course.
	 * @param     string    kind of view inside tracking info
	 * @param    int        User id
	 * @param    string    Course code
	 * @param    int        Session id (optional, default = 0)
	 * @return     void
	 */
	function display_document_tracking_info($view, $user_id, $course_id, $session_id = 0) {
		// protect data
		$user_id     = intval($user_id);
		$course_id     = Database::escape_string($course_id);
		$session_id = intval($session_id);

		$downloads_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);

		if (substr($view,4,1) == '1') {
			$new_view = substr_replace($view,'0',4,1);
			$title[1]= get_lang('DocumentsDetails');

			$sql = "SELECT down_doc_path
                        FROM $downloads_table
                        WHERE down_cours_id = '$course_id'
                            AND down_user_id = '$user_id'
                            AND down_session_id = '$session_id'
                        GROUP BY down_doc_path";

			$results = getManyResults1Col($sql);
			$title_line = get_lang('DocumentsTitleDocumentColumn')."\n";
			if (is_array($results)) {
				for ($j = 0 ; $j < count($results) ; $j++) {
					$line .= $results[$j]."\n";
				}
			} else {
				$line = get_lang('NoResult');
			}
		} else {
			$new_view = substr_replace($view,'1',4,1);
		}
		return array($title_line, $line);
	}


}
