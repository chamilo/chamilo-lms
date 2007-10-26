<?php
// $Id: tracking.lib.php 2007-28-02 15:51:53
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This is the tracking library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/

class Tracking {

	/**
	 * Calculates the time spent on the platform by a user
	 * @param integer $user_id the user id
	 * @return timestamp $nb_seconds
	 */
	function get_time_spent_on_the_platform($user_id) {

		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

		$sql = 'SELECT login_date, logout_date FROM ' . $tbl_track_login . ' 
						WHERE login_user_id = ' . intval($user_id).' AND logout_date IS NOT NULL';

		$rs = api_sql_query($sql);

		$nb_seconds = 0;

		while ($a_connections = mysql_fetch_array($rs)) {

			$s_login_date = $a_connections["login_date"];
			$s_logout_date = $a_connections["logout_date"];

			$i_timestamp_login_date = strtotime($s_login_date);
			$i_timestamp_logout_date = strtotime($s_logout_date);

			$nb_seconds += ($i_timestamp_logout_date - $i_timestamp_login_date);

		}

		return $nb_seconds;
	}

	/**
	 * Calculates the time spent on the course
	 * @param integer $user_id the user id
	 * @param string $course_code the course code
	 * @return timestamp $nb_seconds
	 */
	function get_time_spent_on_the_course($user_id, $course_code) {
		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		$tbl_track_course = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

		$sql = 'SELECT login_course_date, logout_course_date FROM ' . $tbl_track_course . ' 
						WHERE user_id = ' . intval($user_id) . '
						AND course_code="' . $course_code . '"';

		$rs = api_sql_query($sql);

		$nb_seconds = 0;

		while ($a_connections = mysql_fetch_array($rs)) {

			$s_login_date = $a_connections["login_course_date"];
			$s_logout_date = $a_connections["logout_course_date"];

			$i_timestamp_login_date = strtotime($s_login_date);
			$i_timestamp_logout_date = strtotime($s_logout_date);

			$nb_seconds += ($i_timestamp_logout_date - $i_timestamp_login_date);

		}

		return $nb_seconds;
	}

	function get_last_connection_date($student_id, $warning_message = false) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql = 'SELECT login_date FROM ' . $tbl_track_login . ' 
						WHERE login_user_id = ' . intval($student_id) . ' 
						ORDER BY login_date DESC LIMIT 0,1';

		$rs = api_sql_query($sql);
		if ($last_login_date = mysql_result($rs, 0, 0)) {
			if (!$warning_message) {
				return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date));
			} else {
				$timestamp = strtotime($last_login_date);
				$currentTimestamp = mktime();

				//If the last connection is > than 7 days, the text is red
				//345600 = 7 days in seconds 
				if ($currentTimestamp - $timestamp > 604800) {
					return '<span style="color: #F00;">' . format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date)) . '</span>';
				} else {
					return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date));
				}
			}
		} else {
			return false;
		}
	}

	function get_last_connection_date_on_the_course($student_id, $course_code) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = 'SELECT login_course_date FROM ' . $tbl_track_login . ' 
						WHERE user_id = ' . intval($student_id) . ' 
						AND course_code = "' . mysql_real_escape_string($course_code) . '"
						ORDER BY login_course_date DESC LIMIT 0,1';

		$rs = api_sql_query($sql);
		if ($last_login_date = mysql_result($rs, 0, 0)) {
			$timestamp = strtotime($last_login_date);
			$currentTimestamp = mktime();
			//If the last connection is > than 7 days, the text is red
			//345600 = 7 days in seconds 
			if ($currentTimestamp - $timestamp > 604800) {
				return '<span style="color: #F00;">' . format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date)) . ' <a href="'.api_get_path(REL_CLARO_PATH).'announcements/announcements.php?action=add&remind_inactive='.$student_id.'" title="'.get_lang('RemindInactiveUser').'"><img align="middle" src="'.api_get_path(WEB_IMG_PATH).'linphone.gif" /></a></span>';
			} else {
				return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date));
			}
		} else {
			return false;
		}
	}

	function count_course_per_student($user_id) {

		$user_id = intval($user_id);
		$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$sql = 'SELECT DISTINCT course_code
						FROM ' . $tbl_course_rel_user . '
						WHERE user_id = ' . $user_id;
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$nb_courses = mysql_num_rows($rs);

		$sql = 'SELECT DISTINCT course_code
						FROM ' . $tbl_session_course_rel_user . '
						WHERE id_user = ' . $user_id;
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$nb_courses += mysql_num_rows($rs);

		return $nb_courses;
	}

	function get_avg_student_progress($student_id, $course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);

		// table definition
		$tbl_course_lp_view = Database :: get_course_table(TABLE_LP_VIEW, $a_course['db_name']);
		$tbl_course_lp_view_item = Database :: get_course_table(TABLE_LP_ITEM_VIEW, $a_course['db_name']);
		$tbl_course_lp_item = Database :: get_course_table(TABLE_LP_ITEM, $a_course['db_name']);
		$tbl_course_lp = Database :: get_course_table(TABLE_LP_MAIN, $a_course['db_name']);

		//get the list of learning paths
		$sql = 'SELECT id FROM ' . $tbl_course_lp;
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$nb_lp = mysql_num_rows($rs);
		$avg_progress = 0;

		if ($nb_lp > 0) {
			while ($lp = Database :: fetch_array($rs)) {
				// get the progress in learning pathes	
				$sqlProgress = "SELECT progress
												FROM " . $tbl_course_lp_view . " AS lp_view
												WHERE lp_view.user_id = " . $student_id . " 
												AND lp_view.lp_id = " . $lp['id'] . "
											   ";
				$resultItem = api_sql_query($sqlProgress, __FILE__, __LINE__);
				$avg_progress += mysql_result($resultItem, 0, 0);
			}
			$avg_progress = round($avg_progress / $nb_lp, 1);
		}

		return $avg_progress;
	}

	function get_avg_student_score($student_id, $course_code) {
		
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$course = CourseManager :: get_course_information($course_code);
		$lp_table = Database :: get_course_table(TABLE_LP_MAIN,$course['db_name']);
		$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM,$course['db_name']);
		$lp_view_table = Database  :: get_course_table(TABLE_LP_VIEW,$course['db_name']);
		$lp_item_view_table = Database  :: get_course_table(TABLE_LP_ITEM_VIEW,$course['db_name']);

		$sql_course_lp = 'SELECT id FROM '.$lp_table;
		$sql_result_lp = api_sql_query($sql_course_lp, __FILE__, __LINE__);
		
		$lp_scorm_score_total = 0;
		$lp_scorm_weighting_total = 0;
		
		if(Database::num_rows($sql_result_lp)>0){
			//Scorm
			while($a_learnpath = mysql_fetch_array($sql_result_lp)){
				$sql = 'SELECT id, max_score 
						FROM '.$lp_item_table.' AS lp_item
						WHERE lp_id='.$a_learnpath['id'].'
						AND item_type="sco" LIMIT 1';
				
				$rs_lp_item_id_scorm = api_sql_query($sql, __FILE__, __LINE__);
				
				if(Database::num_rows($rs_lp_item_id_scorm)>0){
					$lp_item_id = mysql_result($rs_lp_item_id_scorm,0,'id');
					$lp_item__max_score = mysql_result($rs_lp_item_id_scorm,0,'max_score');				
					
					//We get the last view id of this LP
					$sql='SELECT max(id) as id FROM '.$lp_view_table.' WHERE lp_id='.$a_learnpath['id'].' AND user_id="'.intval($student_id).'"';	
					$rs_last_lp_view_id = api_sql_query($sql, __FILE__, __LINE__);
					$lp_view_id = mysql_result($rs_last_lp_view_id,0,'id');
					
					$sql='SELECT SUM(score)/count(lp_item_id) as score FROM '.$lp_item_view_table.' WHERE lp_view_id="'.$lp_view_id.'" GROUP BY lp_view_id';

					$rs_score = api_sql_query($sql, __FILE__, __LINE__);
					$lp_scorm_score = mysql_result($rs_score,0,'score');
					
					$lp_scorm_score = ($lp_scorm_score / $lp_item__max_score) * 100;
					
					$lp_scorm_score_total+=$lp_scorm_score;
					$lp_scorm_weighting_total+=100;
					
				}
			}
			mysql_data_seek($sql_result_lp,0);
			//Quizz in LP
			while($a_learnpath = Database::fetch_array($sql_result_lp)){
				
				$sql = 'SELECT id as item_id, max_score 
						FROM '.$lp_item_table.' AS lp_item
						WHERE lp_id='.$a_learnpath['id'].'
						AND item_type="quiz"';

				$rsItems = api_sql_query($sql, __FILE__, __LINE__);
				
				//We get the last view id of this LP
				$sql='SELECT max(id) as id FROM '.$lp_view_table.' WHERE lp_id='.$a_learnpath['id'].' AND user_id="'.intval($student_id).'"';	
				$rs_last_lp_view_id = api_sql_query($sql, __FILE__, __LINE__);
				$lp_view_id = mysql_result($rs_last_lp_view_id,0,'id');
				
				$total_score = $total_weighting = 0;
				while($item = Database :: fetch_array($rsItems, 'ASSOC'))
				{
					$sql = 'SELECT score as student_score 
							FROM '.$lp_item_view_table.' as lp_view_item
							WHERE lp_view_item.lp_item_id = '.$item['item_id'].'
							AND lp_view_id = "'.$lp_view_id.'"
							';

					$rsScores = api_sql_query($sql, __FILE__, __LINE__);
					$total_score += mysql_result($rsScores, 0, 0);
					$total_weighting += $item['max_score'];
					
					$lp_scorm_score_total += ($total_score/$total_weighting)*100;
					$lp_scorm_weighting_total+=100;
					
				}
				
			}
		}

		$totalScore = $lp_scorm_score_total;

		$pourcentageScore = round(($totalScore * 100) / $lp_scorm_weighting_total);

		return $pourcentageScore;
	}

	/**
	 * gets the list of students followed by coach
	 * @param integer $coach_id the id of the coach
	 * @return Array the list of students
	 */
	function get_student_followed_by_coach($coach_id) {
		$coach_id = intval($coach_id);

		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

		$a_students = array ();

		//////////////////////////////////////////////////////////////
		// At first, courses where $coach_id is coach of the course //
		//////////////////////////////////////////////////////////////
		$sql = 'SELECT id_session, course_code FROM ' . $tbl_session_course . ' WHERE id_coach=' . $coach_id;
		$result = api_sql_query($sql);

		while ($a_courses = mysql_fetch_array($result)) {
			$course_code = $a_courses["course_code"];
			$id_session = $a_courses["id_session"];

			$sql = "SELECT distinct	srcru.id_user  
								FROM $tbl_session_course_user AS srcru 
								WHERE course_code='$course_code' AND id_session='$id_session'";

			$rs = api_sql_query($sql);

			while ($row = mysql_fetch_array($rs)) {
				$a_students[$row['id_user']] = $row['id_user'];
			}
		}

		//////////////////////////////////////////////////////////////
		// Then, courses where $coach_id is coach of the session    //
		//////////////////////////////////////////////////////////////

		$sql = 'SELECT session_course_user.id_user 
						FROM ' . $tbl_session_course_user . ' as session_course_user
						INNER JOIN ' . $tbl_session_course . ' as session_course
							ON session_course.course_code = session_course_user.course_code
							AND session_course_user.id_session = session_course.id_session
						INNER JOIN ' . $tbl_session . ' as session
							ON session.id = session_course.id_session
							AND session.id_coach = ' . $coach_id;
		$result = api_sql_query($sql);

		while ($row = mysql_fetch_array($result)) {
			$a_students[$row['id_user']] = $row['id_user'];
		}
		return $a_students;
	}

	function get_student_followed_by_coach_in_a_session($id_session, $coach_id) {

		$coach_id = intval($coach_id);

		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

		$a_students = array ();

		//////////////////////////////////////////////////////////////
		// At first, courses where $coach_id is coach of the course //
		//////////////////////////////////////////////////////////////
		$sql = 'SELECT course_code FROM ' . $tbl_session_course . ' WHERE id_session="' . $id_session . '" AND id_coach=' . $coach_id;

		$result = api_sql_query($sql);

		while ($a_courses = mysql_fetch_array($result)) {
			$course_code = $a_courses["course_code"];

			$sql = "SELECT distinct	srcru.id_user  
								FROM $tbl_session_course_user AS srcru 
								WHERE course_code='$course_code' and id_session = '" . $id_session . "'";

			$rs = api_sql_query($sql, __FILE__, __LINE__);

			while ($row = mysql_fetch_array($rs)) {
				$a_students[$row['id_user']] = $row['id_user'];
			}
		}

		//////////////////////////////////////////////////////////////
		// Then, courses where $coach_id is coach of the session    //
		//////////////////////////////////////////////////////////////

		$dsl_session_coach = 'SELECT id_coach FROM ' . $tbl_session . ' WHERE id="' . $id_session . '" AND id_coach="' . $coach_id . '"';
		$result = api_sql_query($dsl_session_coach, __FILE__, __LINE__);
		//He is the session_coach so we select all the users in the session
		if (mysql_num_rows($result) > 0) {
			$sql = 'SELECT DISTINCT srcru.id_user FROM ' . $tbl_session_course_user . ' AS srcru WHERE id_session="' . $id_session . '"';
			$result = api_sql_query($sql);
			while ($row = mysql_fetch_array($result)) {
				$a_students[$row['id_user']] = $row['id_user'];
			}
		}
		return $a_students;
	}

	function is_allowed_to_coach_student($coach_id, $student_id) {
		$coach_id = intval($coach_id);
		$student_id = intval($student_id);

		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);

		//////////////////////////////////////////////////////////////
		// At first, courses where $coach_id is coach of the course //
		//////////////////////////////////////////////////////////////
		$sql = 'SELECT 1 
						FROM ' . $tbl_session_course_user . ' AS session_course_user
						INNER JOIN ' . $tbl_session_course . ' AS session_course
							ON session_course.course_code = session_course_user.course_code
							AND id_coach=' . $coach_id . ' 
						WHERE id_user=' . $student_id;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (mysql_num_rows($result) > 0) {
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
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (mysql_num_rows($result) > 0) {
			return true;
		}

		return false;

	}

	function get_courses_followed_by_coach($coach_id, $id_session = '') {

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
		$sql = 'SELECT DISTINCT course_code FROM ' . $tbl_session_course . ' WHERE id_coach=' . $coach_id;
		if (!empty ($id_session))
			$sql .= ' AND id_session=' . $id_session;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($row = mysql_fetch_array($result)) {
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
		if (!empty ($id_session))
			$sql .= ' WHERE session_course.id_session=' . $id_session;
		$result = api_sql_query($sql, __FILE__, __LINE__);

		while ($row = mysql_fetch_array($result)) {
			$a_courses[$row['course_code']] = $row['course_code'];
		}

		return $a_courses;
	}

	function get_sessions_coached_by_user($coach_id) {
		// table definition
		$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);

		// protect datas
		$coach_id = intval($coach_id);

		// session where we are general coach
		$sql = 'SELECT DISTINCT id, name, date_start, date_end
						FROM ' . $tbl_session . ' 
						WHERE id_coach=' . $coach_id;

		$rs = api_sql_query($sql);
		while ($row = mysql_fetch_array($rs)) {
			$a_sessions[$row["id"]] = $row;
		}

		// session where we are coach of a course
		$sql = 'SELECT DISTINCT session.id, session.name, session.date_start, session.date_end
						FROM ' . $tbl_session . ' as session
						INNER JOIN ' . $tbl_session_course . ' as session_course
							ON session.id = session_course.id_session
							AND session_course.id_coach=' . $coach_id;
		$rs = api_sql_query($sql);

		while ($row = mysql_fetch_array($rs)) {
			$a_sessions[$row["id"]] = $row;
		}

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

		return $a_sessions;

	}

	function get_courses_list_from_session($session_id) {
		//protect datas
		$session_id = intval($session_id);

		// table definition
		$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);

		$sql = 'SELECT DISTINCT course_code, id_coach 
						FROM ' . $tbl_session_course . '
						WHERE id_session=' . $session_id;

		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$a_courses = array ();
		while ($row = mysql_fetch_array($rs)) {
			$a_courses[$row['course_code']] = $row;
		}
		return $a_courses;
	}

	function count_student_assignments($student_id, $course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);

		// table definition
		$tbl_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY, $a_course['db_name']);
		$sql = 'SELECT 1
						FROM ' . $tbl_item_property . ' 
						WHERE insert_user_id=' . $student_id . '
						AND tool="work"';

		$rs = api_sql_query($sql, __LINE__, __FILE__);
		return mysql_num_rows($rs);
	}

	function count_student_messages($student_id, $course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);

		// table definition
		$tbl_messages = Database :: get_course_table(TABLE_FORUM_POST, $a_course['db_name']);
		$sql = 'SELECT 1
						FROM ' . $tbl_messages . ' 
						WHERE poster_id=' . $student_id;

		$rs = api_sql_query($sql, __LINE__, __FILE__);
		return mysql_num_rows($rs);
	}

	function count_student_visited_links($student_id, $course_code) {
		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// table definition
		$tbl_stats_links = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);

		$sql = 'SELECT 1
						FROM ' . $tbl_stats_links . ' 
						WHERE links_user_id=' . $student_id . '
						AND links_cours_id="' . $course_code . '"';

		$rs = api_sql_query($sql, __LINE__, __FILE__);
		return mysql_num_rows($rs);
	}

	function count_student_downloaded_documents($student_id, $course_code) {
		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// table definition
		$tbl_stats_documents = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);

		$sql = 'SELECT 1
						FROM ' . $tbl_stats_documents . ' 
						WHERE down_user_id=' . $student_id . '
						AND down_cours_id="' . $course_code . '"';

		$rs = api_sql_query($sql, __LINE__, __FILE__);
		return mysql_num_rows($rs);
	}

	function get_course_list_in_session_from_student($user_id, $id_session) {
		$user_id = intval($user_id);
		$id_session = intval($id_session);
		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$sql = 'SELECT course_code FROM ' . $tbl_session_course_user . ' WHERE id_user="' . $user_id . '" AND id_session="' . $id_session . '"';
		$result = api_sql_query($sql, __LINE__, __FILE__);
		$a_courses = array ();
		while ($row = mysql_fetch_array($result)) {
			$a_courses[$row['course_code']] = $row['course_code'];
		}
		return $a_courses;
	}

}
?>