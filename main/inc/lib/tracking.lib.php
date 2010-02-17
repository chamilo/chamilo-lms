<?php
/* For licensing terms, see /license.txt */
/**
==============================================================================
*	This is the tracking library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
*	@author Julio Montoya <gugli100@gmail.com> (Score average fixes)
==============================================================================
*/

class Tracking {

	/**
	 * Calculates the time spent on the platform by a user
	 * @param integer $user_id the user id
	 * @param bool	  optionally show time spent last week
	 * @return timestamp $nb_seconds
	 */
	public static function get_time_spent_on_the_platform($user_id, $last_week = false) {

		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

		$cond_last_week = '';
		if ($last_week) {			
			$a_last_week = get_last_week();			
			$fday_last_week = date('Y-m-d H:i:s',$a_last_week[0]);
			$lday_last_week = date('Y-m-d H:i:s',$a_last_week[6]);
			$cond_last_week = ' AND (login_date >= "'.$fday_last_week.'" AND login_date <= "'.$lday_last_week.'") ';	
		}
		
		$sql = 'SELECT login_date, logout_date FROM ' . $tbl_track_login . '
						WHERE login_user_id = ' . intval($user_id).$cond_last_week;

		$rs = Database::query($sql);

		$nb_seconds = 0;

		$wrong_logout_dates = false;

		while ($a_connections = Database::fetch_array($rs)) {

			$s_login_date = $a_connections["login_date"];
			$s_logout_date = $a_connections["logout_date"];

			$i_timestamp_login_date = strtotime($s_login_date);
			$i_timestamp_logout_date = strtotime($s_logout_date);

			if($i_timestamp_logout_date>0)
			{
				$nb_seconds += ($i_timestamp_logout_date - $i_timestamp_login_date);
			}
			else
			{ // there are wrong datas in db, then we can't give a wrong time
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
	 * @param integer $user_id the user id
	 * @param string $course_code the course code
	 * @return timestamp $nb_seconds
	 */
	public static function get_time_spent_on_the_course($user_id, $course_code) {
		// protect datas
		$course_code = Database::escape_string($course_code);
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
				WHERE course_code='$course_code' $condition_user";
		$rs = Database::query($sql);
		$row = Database::fetch_array($rs);
		return $row['nb_seconds'];
	}

	public static function get_first_connection_date($student_id) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql = 'SELECT login_date FROM ' . $tbl_track_login . '
						WHERE login_user_id = ' . intval($student_id) . '
						ORDER BY login_date ASC LIMIT 0,1';

		$rs = Database::query($sql);
		if(Database::num_rows($rs)>0)
		{
			if ($first_login_date = Database::result($rs, 0, 0)) {
				$first_login_date_local = api_get_local_time($first_login_date, null, null, date_default_timezone_get());
				return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($first_login_date_local));
			}
		}
		return false;
	}

	public static function get_last_connection_date($student_id, $warning_message = false, $return_timestamp = false) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql = 'SELECT login_date FROM ' . $tbl_track_login . '
						WHERE login_user_id = ' . intval($student_id) . '
						ORDER BY login_date DESC LIMIT 0,1';

		$rs = Database::query($sql);
		if(Database::num_rows($rs)>0)
		{
			if ($last_login_date = Database::result($rs, 0, 0))
			{
				$last_login_date = api_get_local_time($last_login_date, null, null, date_default_timezone_get());
				if ($return_timestamp)
				{
					return strtotime($last_login_date);
				}
				else
				{
					if (!$warning_message)
					{
						return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date));
					}
					else
					{
						$timestamp = strtotime($last_login_date);
						$currentTimestamp = mktime();

						//If the last connection is > than 7 days, the text is red
						//345600 = 7 days in seconds
						if ($currentTimestamp - $timestamp > 604800)
						{
							return '<span style="color: #F00;">' . format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date)) . '</span>';
						}
						else
						{
							return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date));
						}
					}
				}
			}
		}
		return false;
	}

	public static function get_first_connection_date_on_the_course($student_id, $course_code) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = 'SELECT login_course_date FROM ' . $tbl_track_login . '
						WHERE user_id = ' . intval($student_id) . '
						AND course_code = "' . Database::escape_string($course_code) . '"
						ORDER BY login_course_date ASC LIMIT 0,1';

		$rs = Database::query($sql);
		if(Database::num_rows($rs)>0)
		{
			if ($first_login_date = Database::result($rs, 0, 0)) {
				$first_login_date = api_get_local_time($first_login_date, null, null, date_default_timezone_get());
				return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($first_login_date));
			}
		}
		return false;
	}

	public static function get_last_connection_date_on_the_course($student_id, $course_code) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = 'SELECT login_course_date FROM ' . $tbl_track_login . '
						WHERE user_id = ' . intval($student_id) . '
						AND course_code = "' . Database::escape_string($course_code) . '"
						ORDER BY login_course_date DESC LIMIT 0,1';

		$rs = Database::query($sql);
		if(Database::num_rows($rs)>0)
		{
			if ($last_login_date = Database::result($rs, 0, 0)) {
				$last_login_date = api_get_local_time($last_login_date, null, null, date_default_timezone_get());
				$timestamp = strtotime($last_login_date);
				$currentTimestamp = mktime();
				//If the last connection is > than 7 days, the text is red
				//345600 = 7 days in seconds
				if ($currentTimestamp - $timestamp > 604800) {
					return '<span style="color: #F00;">' . format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date)) . (api_is_allowed_to_edit()?' <a href="'.api_get_path(REL_CODE_PATH).'announcements/announcements.php?action=add&remind_inactive='.$student_id.'" title="'.get_lang('RemindInactiveUser').'"><img align="middle" src="'.api_get_path(WEB_IMG_PATH).'messagebox_warning.gif" /></a>':'').'</span>';
				} else {
					return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date));
				}
			}
		}
		return false;
	}

	public static function count_course_per_student($user_id) {

		$user_id = intval($user_id);
		$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$sql = 'SELECT DISTINCT course_code
						FROM ' . $tbl_course_rel_user . '
						WHERE user_id = ' . $user_id.' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH;
		$rs = Database::query($sql);
		$nb_courses = Database::num_rows($rs);

		$sql = 'SELECT DISTINCT course_code
						FROM ' . $tbl_session_course_rel_user . '
						WHERE id_user = ' . $user_id;
		$rs = Database::query($sql);
		$nb_courses += Database::num_rows($rs);

		return $nb_courses;
	}

	/**
	 * This function gets the score average from all tests in a course by student
	 * @param int $student_id - or array for multiples User id (array(0=>1,1=>2))
	 * @param string $course_code - Course id
	 * @return string value (number %) Which represents a round integer about the score average.
	 */
	public static function get_avg_student_exercise_score($student_id, $course_code) {

		// protect datas
		$course_code = Database::escape_string($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		if(!empty($a_course['db_name'])) {
			// table definition
			$tbl_course_quiz = Database::get_course_table(TABLE_QUIZ_TEST,$a_course['db_name']);
			$tbl_stats_exercise = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
			$count_quiz = Database::fetch_row(Database::query("SELECT count(id) FROM $tbl_course_quiz WHERE active <> -1"));
			$quiz_avg_total_score = 0;
			if (!empty($count_quiz[0]) && !empty($student_id)) {
				$condition_user = "";
				if (is_array($student_id)) {
					$condition_user = " AND exe_user_id IN (".implode(',',$student_id).") ";
				} else {
					$condition_user = " AND exe_user_id = '$student_id' ";
				}
				$sql = "SELECT SUM(exe_result/exe_weighting*100) as avg_score
						FROM $tbl_stats_exercise
						WHERE exe_exo_id IN (SELECT id FROM $tbl_course_quiz WHERE active <> -1)
						$condition_user
						AND orig_lp_id = 0
						AND exe_cours_id = '$course_code'
						AND orig_lp_item_id = 0
						ORDER BY exe_date DESC";
				$res = Database::query($sql);
				$row = Database::fetch_array($res);
				$quiz_avg_score = 0;
				if (!empty($row['avg_score'])) {
					$quiz_avg_score = round($row['avg_score'],2);
				}
				$count_attempt = Database::fetch_row(Database::query("SELECT count(*) FROM $tbl_stats_exercise WHERE exe_exo_id IN (SELECT id FROM $tbl_course_quiz WHERE active <> -1) $condition_user AND orig_lp_id = 0 AND exe_cours_id = '$course_code' AND orig_lp_item_id = 0 ORDER BY exe_date DESC"));
				if(!empty($count_attempt[0])) {
					$quiz_avg_score = $quiz_avg_score / $count_attempt[0];
		        }
		        $quiz_avg_total_score = $quiz_avg_score;
				return $quiz_avg_total_score/$count_quiz[0];
			}
		}
		return null;
	}

    /**
     * Returns the average student progress in the learning paths of the given
     * course.
     * @param   int/array     Student id(s)
     * @param   string  Course code
     * @param   int     Session id (optional). Defaults to 0 (=no session filter)
     * @return double  Average progress of the user in this course
     */
	public static function get_avg_student_progress($student_id, $course_code, $session_id = 0) {
		// protect datas
		$course_code = addslashes($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		if (!empty($a_course['db_name'])) {
			// table definition
			$tbl_course_lp_view = Database :: get_course_table(TABLE_LP_VIEW, $a_course['db_name']);
			$tbl_course_lp = Database :: get_course_table(TABLE_LP_MAIN, $a_course['db_name']);
			// count the number of learning paths
			$session_id = intval($session_id);
			$condition_session = " lp.session_id = $session_id ";
			if ($session_id != 0) {
				$condition_session .= " OR lp.session_id = 0";
			}
			$res_count_lp = Database::query("SELECT id FROM $tbl_course_lp lp WHERE $condition_session");
			$count_lp = Database::num_rows($res_count_lp);
			$lp_id = array();
			while ($row_lp = Database::fetch_array($res_count_lp)) {
				$lp_id[] = $row_lp[0];
			}
			$avg_progress = 0;
			//if there is at least one learning path and one student
			if ($count_lp>0 && !empty($student_id)) {
                $condition_user = "";
                if (is_array($student_id)) {
                    $$r = array_walk($student_id,'intval');
                    $condition_user = " lp_view.user_id IN (".implode(',',$student_id).") AND ";
                } else {
                    $student_id = intval($student_id);
                    $condition_user = " lp_view.user_id = '$student_id' AND ";
                }
                // Get last view for each student (in case of multi-attempt)
                // Also filter on LPs of this session  
                $sql_maxes = "SELECT MAX(view_count), progress ".
                        "FROM $tbl_course_lp_view lp_view ".
                        "WHERE $condition_user ".
                        "lp_view.lp_id IN (".implode(',',$lp_id).") ".
                        "GROUP BY lp_id, user_id";
                $res_maxes = Database::query($sql_maxes);
                $sum = $number_items = 0;
                while ($row_maxes = Database::fetch_array($res_maxes)) {
                    $sum += $row_maxes[1];
                    $number_items++;
                }
                if ($number_items == 0) {
                    return 0; //not necessary to return something else if there is no view
                }
                // average progress = total sum divided by the number of views
                // summed up.
                $avg_progress = round($sum / $number_items, 1); 
                return $avg_progress;
			}
		}
		return null;
	}


	/**
	 * This function gets:
	 * 1. The score average from all SCORM Test items in all LP in a course-> All the answers / All the max scores.
	 * 2. The score average from all Tests (quiz) in all LP in a course-> All the answers / All the max scores.
	 * 3. And finally it will return the average between 1. and 2.
	 * This function does not take the results of a Test out of a LP
	 *
	 * @param User id
	 * @param Course id
	 * @param Array limit average to listed lp ids
	 * @return string value (number %) Which represents a round integer explain in got in 3.
	 * @todo Manage sessions
	 */
	public static function get_avg_student_score($student_id, $course_code, $lp_ids=array()) {
		// get global tables names
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
		$tbl_stats_attempts= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
		$course = CourseManager :: get_course_information($course_code);

		if (!empty($course['db_name'])) {
            // get course tables names
			$tbl_quiz_questions= Database :: get_course_table(TABLE_QUIZ_QUESTION,$course['db_name']);
			$lp_table = Database :: get_course_table(TABLE_LP_MAIN,$course['db_name']);
			$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM,$course['db_name']);
			$lp_view_table = Database  :: get_course_table(TABLE_LP_VIEW,$course['db_name']);
			$lp_item_view_table = Database  :: get_course_table(TABLE_LP_ITEM_VIEW,$course['db_name']);

			// Compose a filter based on optional learning paths list given
			$condition_lp = "";
			if(count($lp_ids) > 0) {
				$condition_lp =" WHERE id IN(".implode(',',$lp_ids).") ";
			}
            // Check the real number of LPs corresponding to the filter in the
            // database (and if no list was given, get them all)
			$res_row_lp = Database::query("SELECT DISTINCT(id) FROM $lp_table $condition_lp");
			$count_row_lp = Database::num_rows($res_row_lp);
			$lp_list = array();
			while ($row_lp = Database::fetch_array($res_row_lp)) {
				$lp_list[] = $row_lp[0];
			}

			// Init local variables that will be used through the calculation
			$lp_scorm_score_total = 0;
			$lp_scorm_result_score_total = 0;
			$lp_scorm_loop=0;
			$lp_count = 0;
			$progress = 0;

			// prepare filter on users
            $condition_user1 = "";
            if (is_array($student_id)) {
                array_walk($student_id,'intval');
                $condition_user1 =" AND user_id IN (".implode(',',$student_id).") ";
            } else {
                $condition_user1 =" AND user_id = '$student_id' ";
            }
			
			if ($count_row_lp>0 && !empty($student_id)) {

				// Get all views through learning paths filter
				$sql = "SELECT MAX(view_count) as vc, id, progress, lp_id, user_id ".
				        "FROM $lp_view_table ".
				        "WHERE lp_id IN (".implode(',',$lp_list).") ".
				        "$condition_user1 GROUP BY lp_id,user_id";
				$rs_last_lp_view_id = Database::query($sql);
                
				$count_views = 0;
				$score_of_scorm_calculate = 0;
				if (Database::num_rows($rs_last_lp_view_id) > 0) {
    				// Cycle through each line of the results (grouped by lp_id, user_id) 
					while ($row_lp_view = Database::fetch_array($rs_last_lp_view_id)) {

						$lp_view_id = $row_lp_view['id'];
						$progress = $row_lp_view['progress'];
						$lp_id = $row_lp_view['lp_id'];
						$user_id = $row_lp_view['user_id'];
						
						// For the currently analysed view, get the score and 
						// max_score of each item if it is a sco or a TOOL_QUIZ 
						$sql_max_score = "SELECT lp_iv.score as score,lp_i.max_score, lp_i.path, lp_i.item_type , lp_i.id as iid".
						          "	FROM $lp_item_view_table as lp_iv ".
						          "	INNER JOIN $lp_item_table as lp_i ".
						          "	ON lp_i.id = lp_iv.lp_item_id ".
						          "	AND (lp_i.item_type='sco' ". 
						          " OR lp_i.item_type='".TOOL_QUIZ."') ".
						          " WHERE lp_view_id='$lp_view_id'";

						$res_max_score = Database::query($sql_max_score);
						$count_total_loop = 0;
						$num_rows_max_score = Database::num_rows($res_max_score);

						// Go through each scorable element of this view
						while ($row_max_score = Database::fetch_array($res_max_score)) {
							$max_score = $row_max_score['max_score'];
							$score = $row_max_score['score'];
                            if ($row_max_score['item_type'] == 'sco') {
                                // Check if it is sco (easier to get max_score)
                            	//when there's no max score, we assume 100 as the max score, as the SCORM 1.2 says that the value should always be between 0 and 100.
								if ($max_score==0) {
									$max_score = 100;
								}
								$lp_scorm_result_score_total += ($score/$max_score);
								$current_value = $score/$max_score;
                            } else { 
                            	// Case of a TOOL_QUIZ element
                                $item_id = $row_max_score['iid'];
                                $item_path = $row_max_score['path'];
                                // Get last attempt to this exercise  through 
                                // the current lp for the current user
                                $sql_last_attempt = "SELECT exe_id FROM $tbl_stats_exercices ".
		                           " WHERE exe_exo_id = '$item_path' ".
		                           " AND exe_user_id = '$user_id' ".
		                           // " AND orig_lp_id = '$lp_id' ". //lp_id is already defined by the item_id
		                           " AND orig_lp_item_id = '$item_id' ".
		                           " AND exe_cours_id = '$course_code' ".
		                           " ORDER BY exe_date DESC limit 1";
		                        $result_last_attempt = Database::query($sql_last_attempt);
		                        $num = Database :: num_rows($result_last_attempt);
		                        if ($num > 0 ) {
                                    $id_last_attempt = Database :: result($result_last_attempt, 0, 0);
	                                   
	                                // Within the last attempt number tracking, get the sum of
	                                // the max_scores of all questions that it was
	                                // made of (we need to make this call dynamic
	                                // because of random questions selection)
			                        $sql = "SELECT SUM(t.ponderation) as maxscore ".
			                           " FROM ( SELECT distinct question_id, marks, ponderation ".
			                           " FROM $tbl_stats_attempts AS at " .
			                           " INNER JOIN  $tbl_quiz_questions AS q ".
			                           " ON (q.id = at.question_id) ".
			                           " WHERE exe_id ='$id_last_attempt' ) AS t";
			                        $res_max_score_bis = Database::query($sql);
			                        $row_max_score_bis = Database :: fetch_array($res_max_score_bis);
			                        if (!empty($row_max_score_bis['maxscore'])) {
			                        	$max_score = $row_max_score_bis['maxscore'];
			                        }
			                        $lp_scorm_result_score_total += ($score/$max_score);
	                                $current_value = $score/$max_score;
		                        } else {
		                        	//$lp_scorm_result_score_total += 0;
		                        }
		                    }
							$count_items++;
						}
						
						$score_of_scorm_calculate += $count_items?round((($lp_scorm_result_score_total/$count_items)*100),2):0;						
                        $count_views++;
                        
					}
				}

				if ( $count_views > 0 ) {
					$score_of_scorm_calculate = round(($score_of_scorm_calculate/$count_views),2);
				}
                return $score_of_scorm_calculate;
			}
		}
		return null;
	}

	/**
	 * gets the list of students followed by coach
	 * @param integer $coach_id the id of the coach
	 * @return Array the list of students
	 */
	public static function get_student_followed_by_coach($coach_id) {
		$coach_id = intval($coach_id);

		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_user = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

		$a_students = array ();

		//////////////////////////////////////////////////////////////
		// At first, courses where $coach_id is coach of the course //
		//////////////////////////////////////////////////////////////
		$sql = 'SELECT id_session, course_code FROM ' . $tbl_session_course_user . ' WHERE id_user=' . $coach_id.' AND status=2';

		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
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

			$sql = "SELECT distinct	srcru.id_user
								FROM $tbl_session_course_user AS srcru, $tbl_session_user sru
								WHERE srcru.id_user = sru.id_user AND sru.relation_type<>".SESSION_RELATION_TYPE_RRHH." AND srcru.id_session = sru.id_session AND srcru.course_code='$course_code' AND srcru.id_session='$id_session'";

			$rs = Database::query($sql);

			while ($row = Database::fetch_array($rs)) {
				$a_students[$row['id_user']] = $row['id_user'];
			}
		}

		//////////////////////////////////////////////////////////////
		// Then, courses where $coach_id is coach of the session    //
		//////////////////////////////////////////////////////////////

		$sql = 'SELECT session_course_user.id_user
						FROM ' . $tbl_session_course_user . ' as session_course_user
						INNER JOIN 	'.$tbl_session_user.' sru ON session_course_user.id_user = sru.id_user AND session_course_user.id_session = sru.id_session
						INNER JOIN ' . $tbl_session_course . ' as session_course
							ON session_course.course_code = session_course_user.course_code
							AND session_course_user.id_session = session_course.id_session
						INNER JOIN ' . $tbl_session . ' as session
							ON session.id = session_course.id_session
							AND session.id_coach = ' . $coach_id;
		if ($_configuration['multiple_access_urls']==true) {
			$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
				$sql = 'SELECT session_course_user.id_user
				FROM ' . $tbl_session_course_user . ' as session_course_user
				INNER JOIN 	'.$tbl_session_user.' sru ON session_course_user.id_user = sru.id_user AND session_course_user.id_session = sru.id_session
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

			$sql = "SELECT distinct	srcru.id_user
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
		if ($_configuration['multiple_access_urls']==true) {
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

		if ($_configuration['multiple_access_urls']==true) {
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
			if ($_configuration['multiple_access_urls']==true)
				$sql .=  ' AND access_url_id = '.$access_url_id;
		}  else {
			if ($_configuration['multiple_access_urls']==true)
				$sql .=  ' WHERE access_url_id = '.$access_url_id;
		}

		$result = Database::query($sql);

		while ($row = Database::fetch_array($result)) {
			$a_courses[$row['course_code']] = $row['course_code'];
		}

		return $a_courses;
	}

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
		if ($_configuration['multiple_access_urls']==true) {
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
		if ($_configuration['multiple_access_urls']==true) {
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

	public static function count_student_assignments($student_id, $course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		// protect datas
		$course_code = Database::escape_string($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		if (!empty($a_course['db_name'])) {
			// table definition
			$tbl_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY, $a_course['db_name']);
			$condition_user = "";
			if (is_array($student_id)) {
				$condition_user = " AND insert_user_id IN (".implode(',',$student_id).") ";
			} else {
				$condition_user = " AND insert_user_id = '$student_id' ";
			}
			$sql = "SELECT count(tool) FROM $tbl_item_property WHERE tool='work' $condition_user ";
			$rs = Database::query($sql);
			$row = Database::fetch_row($rs);
			return $row[0];
		}
		return null;
	}

	public static function count_student_messages($student_id, $course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		// protect datas
		$course_code = addslashes($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		if (!empty($a_course['db_name'])) {
			// table definition
			$tbl_messages = Database :: get_course_table(TABLE_FORUM_POST, $a_course['db_name']);
			$condition_user = "";
			if (is_array($student_id)) {
				$condition_user = " WHERE poster_id IN (".implode(',',$student_id).") ";
			} else {
				$condition_user = " WHERE poster_id = '$student_id' ";
			}
			$sql = "SELECT count(post_id) FROM $tbl_messages $condition_user ";
			$rs = Database::query($sql);
			$row = Database::fetch_row($rs);
			return $row[0];
		}
		return null;
	}

/**
* This function counts the number of post by course
* @param  string $course_code - Course ID
* @return	int the number of post by course
* @author Christian Fasanando <christian.fasanando@dokeos.com>,
* @version enero 2009, dokeos 1.8.6
*/
	public static function count_number_of_posts_by_course($course_code) {
		//protect data
		$course_code = addslashes($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		$count = 0;
		if (!empty($a_course['db_name'])) {
			$tbl_posts = Database :: get_course_table(TABLE_FORUM_POST, $a_course['db_name']);
			$sql = "SELECT count(*) FROM $tbl_posts";
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
* @param  string $course_code - Course ID
* @return	int the number of threads by course
* @author Christian Fasanando <christian.fasanando@dokeos.com>,
* @version enero 2009, dokeos 1.8.6
*/
	public static function count_number_of_threads_by_course($course_code) {
		//protect data
		$course_code = addslashes($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		$count = 0;
		if (!empty($a_course['db_name'])) {
			$tbl_threads = Database :: get_course_table(TABLE_FORUM_THREAD, $a_course['db_name']);
			$sql = "SELECT count(*) FROM $tbl_threads";
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
* @param  string $course_code - Course ID
* @return	int the number of forums by course
* @author Christian Fasanando <christian.fasanando@dokeos.com>,
* @version enero 2009, dokeos 1.8.6
*/
	public static function count_number_of_forums_by_course($course_code) {
		//protect data
		$course_code = addslashes($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		$count = 0;
		if (!empty($a_course['db_name'])) {
			$tbl_forums = Database :: get_course_table(TABLE_FORUM, $a_course['db_name']);
			$sql = "SELECT count(*) FROM $tbl_forums";
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
* @param  string $course_code - Course ID
* @param  int $last_days -  last x days
* @return	int the chat last connections by course in x days
* @author Christian Fasanando <christian.fasanando@dokeos.com>,
* @version enero 2009, dokeos 1.8.6
*/
	public static function chat_connections_during_last_x_days_by_course($course_code,$last_days) {
		//protect data
		$last_days = intval($last_days);
		$course_code = addslashes($course_code);
		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		$count = 0;
		if (!empty($a_course['db_name'])) {
			$tbl_stats_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS, $a_course['db_name']);

			$sql = "SELECT count(*) FROM $tbl_stats_access WHERE DATE_SUB(NOW(),INTERVAL $last_days DAY) <= access_date
					AND access_cours_code = '$course_code' AND access_tool='".TOOL_CHAT."'";
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
* @param  int $student_id - Student ID
* @param  string $course_code - Course ID
* @return string the last connection
* @author Christian Fasanando <christian.fasanando@dokeos.com>,
* @version enero 2009, dokeos 1.8.6
*/
	public static function chat_last_connection($student_id,$course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		//protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// get the informations of the course
		$a_course = CourseManager :: get_course_information($course_code);
		$date_time = '';
		if (!empty($a_course['db_name'])) {
			// table definition
			$tbl_stats_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS, $a_course['db_name']);
			$sql = "SELECT access_date FROM $tbl_stats_access
					 WHERE access_tool='".TOOL_CHAT."' AND access_user_id='$student_id' AND access_cours_code = '$course_code' ORDER BY access_date DESC limit 1";

			$rs = Database::query($sql);
			$row = Database::fetch_array($rs);
			$last_connection = api_get_local_time($row['access_date'], null, null, date_default_timezone_get());
			if (!empty($last_connection)) {
				$date_format_long = format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_connection));
				$time = explode(' ',$last_connection);
				$date_time = $date_format_long.' '.$time[1];
			}

			return $date_time;
		} else {
				return null;
		}
	}

	public static function count_student_visited_links($student_id, $course_code) {
		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// table definition
		$tbl_stats_links = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);

		$sql = 'SELECT 1
						FROM ' . $tbl_stats_links . '
						WHERE links_user_id=' . $student_id . '
						AND links_cours_id="' . $course_code . '"';

		$rs = Database::query($sql);
		return Database::num_rows($rs);
	}

	public static function count_student_downloaded_documents($student_id, $course_code) {
		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// table definition
		$tbl_stats_documents = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);

		$sql = 'SELECT 1
						FROM ' . $tbl_stats_documents . '
						WHERE down_user_id=' . $student_id . '
						AND down_cours_id="' . $course_code . '"';

		$rs = Database::query($sql);
		return Database::num_rows($rs);
	}

	public static function get_course_list_in_session_from_student($user_id, $id_session) {
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

	public static function get_inactives_students_in_course($course_code, $since, $session_id=0)
	{
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$table_course_rel_user			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
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
		$rs = api_sql_query($sql);
		$inactive_users = array();
		while($user = Database::fetch_array($rs))
		{
			$inactive_users[] = $user['user_id'];
		}
		return $inactive_users;
	}

	public static function count_login_per_student($student_id, $course_code) {
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);
		$tbl_course_rel_user = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);

		$sql = 'SELECT '.$student_id.'
		FROM ' . $tbl_course_rel_user . '
		WHERE access_user_id=' . $student_id . '
		AND access_cours_code="' . $course_code . '"';

		$rs = Database::query($sql);
		$nb_login = Database::num_rows($rs);

		return $nb_login;
	}


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
	 * allow get average  of test of scorm and lp
	 * @author isaac flores paz <florespaz@bidsoftperu.com>
	 * @param int the user id
	 * @param string the course id
	 */
	public static function get_average_test_scorm_and_lp ($user_id,$course_id) {

		//the score inside the Reporting table
		$course_info=api_get_course_info($course_id);
		$lp_table = Database :: get_course_table(TABLE_LP_MAIN,$course_info['dbName']);
		$lp_view_table = Database  :: get_course_table(TABLE_LP_VIEW,$course_info['dbName']);
		$lp_item_view_table = Database  :: get_course_table(TABLE_LP_ITEM_VIEW,$course_info['dbName']);
		$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM,$course_info['dbName']);
		$sql_type='SELECT id,lp_type FROM '.$lp_table;
		$rs_type=Database::query($sql_type);
		$average_data=0;
		$count_loop=0;
		while ($row_type=Database::fetch_array($rs_type)) {

			if ($row_type['lp_type']==1) {//lp dokeos

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

			} elseif ($row_type['lp_type']==2) {//lp scorm
					$sql = "SELECT id FROM $lp_view_table  WHERE user_id = '".intval($user_id)."' and lp_id='".$row_type['id']."'";
					$rs_last_lp_view_id = Database::query($sql);
					$lp_view_id = intval(Database::result($rs_last_lp_view_id,0,'id'));

					$sql_list_view='SELECT li.max_score,lv.user_id,liw.score,((liw.score/li.max_score)*100) as sum_data FROM '.$lp_item_table.' li INNER JOIN '.$lp_view_table.' lv
					ON li.lp_id=lv.lp_id INNER JOIN '.$lp_item_view_table.' liw ON liw.lp_item_id=li.id WHERE lv.user_id="'.$user_id.'" AND li.item_type="sco" AND liw.lp_view_id="'.$lp_view_id.'"';
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
		if ((int)$count_loop > 0) {
			$avg_student_score = round(($average_data_sum / $count_loop * 100), 2);
		}
		return $avg_student_score;
	}

}

class TrackingCourseLog {

	function count_item_resources() {
		$table_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT count(tool) AS total_number_of_items FROM $table_item_property track_resource, $table_user user" .
				" WHERE track_resource.insert_user_id = user.user_id";

		if (isset($_GET['keyword'])) {
			$keyword = Database::escape_string(trim($_GET['keyword']));
			$sql .= " AND (user.username LIKE '%".$keyword."%' OR lastedit_type LIKE '%".$keyword."%' OR tool LIKE '%".$keyword."%')";
		}

		$sql .= " AND tool IN ('document', 'learnpath', 'quiz', 'glossary', 'link', 'course_description')";
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
		return $obj->total_number_of_items;
	}

	function get_item_resources_data($from, $number_of_items, $column, $direction) {
		global $dateTimeFormatLong;
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
				WHERE track_resource.insert_user_id = user.user_id ";

		if (isset($_GET['keyword'])) {
			$keyword = Database::escape_string(trim($_GET['keyword']));
			$sql .= " AND (user.username LIKE '%".$keyword."%' OR lastedit_type LIKE '%".$keyword."%' OR tool LIKE '%".$keyword."%') ";
		}

		$sql .= " AND tool IN ('document', 'learnpath', 'quiz', 'glossary', 'link', 'course_description')";

		if ($column == 0) { $column = '0'; }
		if ($column != '' && $direction != '') {
			if ($column != 2 && $column != 4) {
				$sql .=	" ORDER BY col$column $direction";
			}
		} else {
			$sql .=	" ORDER BY col5 DESC ";
		}

		$sql .=	" LIMIT $from, $number_of_items ";

		$res = Database::query($sql) or die(Database::error());
		$resources = array ();

		while ($row = Database::fetch_array($res)) {
			$ref = $row['ref'];
			$table_name = TrackingCourseLog::get_tool_name_table($row['col0']);
			$table_tool = Database :: get_course_table($table_name['table_name']);
			$id = $table_name['id_tool'];
			$query = "SELECT session.id, session.name, user.username FROM $table_tool tool, $table_session session, $table_user user" .
						" WHERE tool.session_id = session.id AND session.id_coach = user.user_id AND tool.$id = $ref";
			$recorset = Database::query($query);

			if (!empty($recorset)) {

				$obj = Database::fetch_object($recorset);

				$name_session = '';
				$coach_name = '';
				if (!empty($obj)) {
					$name_session = $obj->name;
					$coach_name = $obj->username;
				}

				$url_tool = api_get_path(WEB_CODE_PATH).$table_name['link_tool'];

				$row[0] = '';
				if ($row['col6'] != 2) {
					$row[0] = '<a href="'.$url_tool.'?'.api_get_cidreq().'&'.$obj->id.'">'.api_ucfirst($row['col0']).'</a>';
				} else {
					$row[0] = api_ucfirst($row['col0']);
				}

				$row[1] = get_lang($row[1]);

				$row[5] = api_ucfirst(api_get_local_time($row['col5'], $dateTimeFormatLong, null, date_default_timezone_get()));

				$row[4] = '';
				if ($table_name['table_name'] == 'document') {
					$condition = 'tool.title as title';
					$query_document = "SELECT $condition FROM $table_tool tool" .
										" WHERE id = $ref";
					$rs_document = Database::query($query_document) or die(Database::error());
					$obj_document = Database::fetch_object($rs_document);
					$row[4] = $obj_document->title;
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
			default:
				$table_name = $tool;
				break;
		}
		return array('table_name' => $table_name,
					 'link_tool' => $link_tool,
					 'id_tool' => $id_tool);
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

		foreach ($extra_fields as $key=>$field) {
			// show only extra fields that are visible, added by J.Montoya
			if ($field[6]==1) {
				if ($field[0] == $_GET['additional_profile_field'] ) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
				$return .= '<option value="'.$field[0].'" '.$selected.'>'.$field[3].'</option>';
			}
		}
		$return .= '</select>';

		// the form elements for the $_GET parameters (because the form is passed through GET
		foreach ($_GET as $key=>$value){
			if ($key <> 'additional_profile_field')	{
				$return .= '<input type="hidden" name="'.$key.'" value="'.Security::remove_XSS($value).'" />';
			}
		}
		// the submit button
		$return .= '<button class="save" type="submit">'.get_lang('AddAdditionalProfileField').'</button>';
		$return .= '</form>';
		return $return;
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
		$table_user 			= Database::get_main_table(TABLE_MAIN_USER);
		$table_user_field_values 	= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

		$sql = "SELECT user.user_id, field.field_value FROM $table_user user, $table_user_field_values field
			WHERE user.user_id = field.user_id
			AND field.field_id='".intval($field_id)."'";
		$result = api_sql_query($sql);
		while($row = Database::fetch_array($result))
		{
			$return[$row['user_id']][] = $row['field_value'];
		}
		return $return;
	}

	/**
	 * This function gets all the information of a certrain ($field_id) additional profile field for a specific list of users is more efficent than  get_addtional_profile_information_of_field() function
	 * It gets the information of all the users so that it can be displayed in the sortable table or in the csv or xls export
	 *
	 * @author	Julio Montoya <gugli100@gmail.com>
	 * @param	int field id
	 * @param	array list of user ids
	 * @return	array
	 * @since	Nov 2009
	 * @version	1.8.6.2
	 */
	function get_addtional_profile_information_of_field_by_user($field_id, $users) {
		// Database table definition
		$table_user 				= Database::get_main_table(TABLE_MAIN_USER);
		$table_user_field_values 	= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$result_extra_field 		= UserManager::get_extra_field_information($field_id);

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

				$result = api_sql_query($sql);
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

		global $user_ids, $course_code, $additional_user_profile_info, $export_csv, $is_western_name_order, $csv_content;

		$course_code = Database::escape_string($course_code);
		$course_info = CourseManager :: get_course_information($course_code);
		$tbl_track_cours_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$tbl_user 				= Database :: get_main_table(TABLE_MAIN_USER);
		$tbl_item_property 		= Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_info['db_name']);
		$tbl_forum_post  		= Database :: get_course_table(TABLE_FORUM_POST, $course_info['db_name']);
		$tbl_course_lp_view 	= Database :: get_course_table(TABLE_LP_VIEW, $course_info['db_name']);
		$tbl_course_lp 			= Database :: get_course_table(TABLE_LP_MAIN, $course_info['db_name']);

		// get all users data from a course for sortable with limit
		$condition_user = "";
		if (is_array($user_ids)) {
			$condition_user = " WHERE user.user_id IN (".implode(',',$user_ids).") ";
		} else {
			$condition_user = " WHERE user.user_id = '$user_ids' ";
		}
		$sql = "SELECT user.user_id as col0,
				user.official_code as col1,
				user.lastname as col2,
				user.firstname as col3
				FROM $tbl_user as user
				$condition_user ";

		if (!in_array($direction, array('ASC','DESC'))) {
	    	$direction = 'ASC';
	    }

	    $column = intval($column);
	    $from = intval($from);
	    $number_of_items = intval($number_of_items);
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
		$res = Database::query($sql);
		$users = array ();
	    $t = time();
	   	$row = array();
		while ($user = Database::fetch_row($res)) {

			$row[0] = $user[1];
			if ($is_western_name_order) {
				$row[1] = $user[3];
				$row[2] = $user[2];
			} else {
				$row[1] = $user[2];
				$row[2] = $user[3];
			}
			$row[3] = api_time_to_hms(Tracking::get_time_spent_on_the_course($user[0], $course_code));
			$avg_student_score = Tracking::get_average_test_scorm_and_lp($user[0], $course_code);
			$avg_student_progress = Tracking::get_avg_student_progress($user[0], $course_code);
			if (empty($avg_student_score)) {$avg_student_score=0;}
			if (empty($avg_student_progress)) {$avg_student_progress=0;}
			$row[4] = $avg_student_progress.'%';
			$row[5] = $avg_student_score.'%';
			$row[6] = Tracking::count_student_assignments($user[0], $course_code);$user[4];
			$row[7] = Tracking::count_student_messages($user[0], $course_code);//$user[5];
			$row[8] = Tracking::get_first_connection_date_on_the_course($user[0], $course_code);
			$row[9] = Tracking::get_last_connection_date_on_the_course($user[0], $course_code);

			// we need to display an additional profile field
			if (isset($_GET['additional_profile_field']) AND is_numeric($_GET['additional_profile_field'])) {
				if (is_array($additional_user_profile_info[$user[0]])) {
					$row[10]=implode(', ', $additional_user_profile_info[$user[0]]);
				} else {
					$row[10]='&nbsp;';
				}
			}
			$row[11] = '<center><a href="../mySpace/myStudents.php?student='.$user[0].'&details=true&course='.$course_code.'&origin=tracking_course"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></center>';
			if ($export_csv) {
				$row[8] = strip_tags($row[8]);
				$row[9] = strip_tags($row[9]);
				unset($row[10]);
				unset($row[11]);
				$csv_content[] = $row;
			}
	        // store columns in array $users
	        $users[] = array($row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$row[8],$row[9],$row[10],$row[11]);
		}
		return $users;
	}
}

class TrackingUserLog {

	/**
	* Displays the number of logins every month for a specific user in a specific course.
	*/
	function display_login_tracking_info($view, $user_id, $course_id)
	{
		$MonthsLong = $GLOBALS['MonthsLong'];
		$track_access_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
		$tempView = $view;
		if(substr($view,0,1) == '1') {
			$new_view = substr_replace($view,'0',0,1);
			echo "
				<tr>
					<td valign='top'>
					<font color='#0000FF'>-&nbsp;&nbsp;&nbsp;</font>" .
					"<b>".get_lang('LoginsAndAccessTools')."</b>&nbsp;&nbsp;&nbsp;[<a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."'>".get_lang('Close')."</a>]&nbsp;&nbsp;&nbsp;[<a href='userLogCSV.php?".api_get_cidreq()."&uInfo=".Security::remove_XSS($_GET['uInfo'])."&view=10000'>".get_lang('ExportAsCSV')."</a>]
					</td>
				</tr>
				";
			echo "<tr><td style='padding-left : 40px;' valign='top'>".get_lang('LoginsDetails')."<br>";

			$sql = "SELECT UNIX_TIMESTAMP(access_date), count(access_date)
						FROM $track_access_table
						WHERE access_user_id = '".Database::escape_string($user_id)."'
						AND access_cours_code = '".Database::escape_string($course_id)."'
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
					//echo "<td class='content'><a href='logins_details.php?uInfo=$user_id&reqdate=".$results[$j][0]."'>".$langMonthNames['long'][date("n", $results[$j][0])-1]." ".date("Y", $results[$j][0])."</a></td>";
					echo "<td class='content'><a href='logins_details.php?uInfo=".Security::remove_XSS($user_id)."&reqdate=".$results[$j][0]."&view=".Security::remove_XSS($view)."'>".$MonthsLong[date('n', $results[$j][0])-1].' '.date('Y', $results[$j][0])."</a></td>";
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
					+<font color='#0000FF'>&nbsp;&nbsp;</font><a href='".api_get_self()."?uInfo=".Security::remove_XSS($user_id)."&view=".Security::remove_XSS($new_view)."' class='specialLink'>".get_lang('LoginsAndAccessTools')."</a>
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
					$display_date = api_get_local_time($results[$i][3], $dateTimeFormatLong, null, date_default_timezone_get());
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
					$display_date = api_get_local_time($hpresults[$i][3], $dateTimeFormatLong, null, date_default_timezone_get());
	?>
					<tr>
						<td class="content"><?php echo $title; ?></td>
						<td class="content" align="center"><?php echo $display_date; ?></td>
						<td class="content" align="center"><?php echo $hpresults[$i][1]; ?> / <?php echo $hpresults[$i][2]; ?></td>
					</tr>
	<?php		}
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
					$upload_date = api_get_local_time($results[$j][0], null, null, date_default_timezone_get());
					$timestamp = strtotime($upload_date);
					$beautifulDate = format_locale_date($dateTimeFormatLong,$timestamp);
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
	*/
	function display_document_tracking_info($view, $user_id, $course_id)
	{
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
						WHERE down_cours_id = '".Database::escape_string($course_id)."'
							AND down_user_id = '".Database::escape_string($user_id)."'
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

class TrackingUserLogCSV {

	/**
	* Displays the number of logins every month for a specific user in a specific course.
	*/
	function display_login_tracking_info($view, $user_id, $course_id)
	{
		$MonthsLong = $GLOBALS['MonthsLong'];
		$track_access_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
		$tempView = $view;
		if(substr($view,0,1) == '1')
		{
			$new_view = substr_replace($view,'0',0,1);
			$title[1]= get_lang('LoginsAndAccessTools').get_lang('LoginsDetails');

			$sql = "SELECT UNIX_TIMESTAMP(`access_date`), count(`access_date`)
						FROM $track_access_table
						WHERE `access_user_id` = '$user_id'
						AND `access_cours_code` = '".$course_id."'
						GROUP BY YEAR(`access_date`),MONTH(`access_date`)
						ORDER BY YEAR(`access_date`),MONTH(`access_date`) ASC";

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
	function display_exercise_tracking_info($view, $user_id, $course_id)
	{
		global $TABLECOURSE_EXERCICES, $TABLETRACK_EXERCICES, $TABLETRACK_HOTPOTATOES, $dateTimeFormatLong;
		if(substr($view,1,1) == '1')
		{
			$new_view = substr_replace($view,'0',1,1);

			$title[1]= get_lang('ExercicesDetails');
			$line='';

			$sql = "SELECT `ce`.`title`, `te`.`exe_result` , `te`.`exe_weighting`, UNIX_TIMESTAMP(`te`.`exe_date`)
				FROM $TABLECOURSE_EXERCICES AS ce , `$TABLETRACK_EXERCICES` AS `te`
				WHERE `te`.`exe_cours_id` = '$course_id'
					AND `te`.`exe_user_id` = '$user_id'
					AND `te`.`exe_exo_id` = `ce`.`id`
				ORDER BY `ce`.`title` ASC, `te`.`exe_date` ASC";

			$hpsql = "SELECT `te`.`exe_name`, `te`.`exe_result` , `te`.`exe_weighting`, UNIX_TIMESTAMP(`te`.`exe_date`)
				FROM `$TABLETRACK_HOTPOTATOES` AS te
				WHERE `te`.`exe_user_id` = '$user_id' AND `te`.`exe_cours_id` = '$course_id'
				ORDER BY `te`.`exe_cours_id` ASC, `te`.`exe_date` ASC";

			$hpresults = getManyResultsXCol($hpsql, 4);

			$NoTestRes = 0;
			$NoHPTestRes = 0;

			$results = getManyResultsXCol($sql, 4);
			$title_line=get_lang('ExercicesTitleExerciceColumn').";".get_lang('Date').';'.get_lang('ExercicesTitleScoreColumn')."\n";

			if (is_array($results))
			{
				for($i = 0; $i < sizeof($results); $i++)
				{
					$display_date = api_get_local_time($results[$i][3], $dateTimeFormatLong, null, date_default_timezone_get());
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
						
					$display_date = api_get_local_time($hpresults[$i][3], $dateTimeFormatLong, null, date_default_timezone_get());

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
	function display_student_publications_tracking_info($view, $user_id, $course_id)
	{
		global $TABLETRACK_UPLOADS, $TABLECOURSE_WORK, $dateTimeFormatLong;
		if(substr($view,2,1) == '1')
		{
			$new_view = substr_replace($view,'0',2,1);
			$sql = "SELECT `u`.`upload_date`, `w`.`title`, `w`.`author`,`w`.`url`
					FROM `$TABLETRACK_UPLOADS` `u` , $TABLECOURSE_WORK `w`
					WHERE `u`.`upload_work_id` = `w`.`id`
						AND `u`.`upload_user_id` = '$user_id'
						AND `u`.`upload_cours_id` = '$course_id'
					ORDER BY `u`.`upload_date` DESC";
			$results = getManyResultsXCol($sql,4);

			$title[1]=get_lang('WorksDetails');
			$line='';
			$title_line=get_lang('WorkTitle').";".get_lang('WorkAuthors').";".get_lang('Date')."\n";

			if (is_array($results))
			{
				for($j = 0 ; $j < count($results) ; $j++)
				{
					$pathToFile = api_get_path(WEB_COURSE_PATH).$_course['path']."/".$results[$j][3];
					$upload_date = api_get_local_time($results[$j][0], null, null, date_default_timezone_get());
					$timestamp = strtotime($upload_date);
					$beautifulDate = format_locale_date($dateTimeFormatLong,$timestamp);
					$line .= $results[$j][1].";".$results[$j][2].";".$beautifulDate."\n";
				}

			}
			else
			{
				$line= get_lang('NoResult');
			}
		}
		else
		{
			$new_view = substr_replace($view,'1',2,1);
		}
		return array($title_line, $line);
	}

	/**
	* Displays the links followed for a specific user in a specific course.
	* @todo remove globals
	*/
	function display_links_tracking_info($view, $user_id, $course_id)
	{
		global $TABLETRACK_LINKS, $TABLECOURSE_LINKS;
		if(substr($view,3,1) == '1')
		{
			$new_view = substr_replace($view,'0',3,1);
			$title[1]=get_lang('LinksDetails');
			$sql = "SELECT `cl`.`title`, `cl`.`url`
						FROM `$TABLETRACK_LINKS` AS sl, $TABLECOURSE_LINKS AS cl
						WHERE `sl`.`links_link_id` = `cl`.`id`
							AND `sl`.`links_cours_id` = '$course_id'
							AND `sl`.`links_user_id` = '$user_id'
						GROUP BY `cl`.`title`, `cl`.`url`";
			$results = getManyResults2Col($sql);
			$title_line= get_lang('LinksTitleLinkColumn')."\n";
			if (is_array($results))
			{
				for($j = 0 ; $j < count($results) ; $j++)
				{
						$line .= $results[$j][0]."\n";

				}

			}
			else
			{
				$line=get_lang('NoResult');
			}
		}
		else
		{
			$new_view = substr_replace($view,'1',3,1);
		}
		return array($title_line, $line);
	}

	/**
	* Displays the documents downloaded for a specific user in a specific course.
	*/
	function display_document_tracking_info($view, $user_id, $course_id)
	{
		$downloads_table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
		if(substr($view,4,1) == '1')
		{
			$new_view = substr_replace($view,'0',4,1);
			$title[1]= get_lang('DocumentsDetails');

			$sql = "SELECT `down_doc_path`
						FROM $downloads_table
						WHERE `down_cours_id` = '$course_id'
							AND `down_user_id` = '$user_id'
						GROUP BY `down_doc_path`";

			$results = getManyResults1Col($sql);
			$title_line = get_lang('DocumentsTitleDocumentColumn')."\n";
			if (is_array($results))
			{
				for($j = 0 ; $j < count($results) ; $j++)
				{
						$line .= $results[$j]."\n";
				}

			}
			else
			{
				$line=get_lang('NoResult');
			}
		}
		else
		{
			$new_view = substr_replace($view,'1',4,1);
		}
		return array($title_line, $line);
	}
}
?>
