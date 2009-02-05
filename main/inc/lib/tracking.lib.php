<?php
// $Id: tracking.lib.php 2007-28-02 15:51:53
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
	
==============================================================================
*/
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
	 * @return timestamp $nb_seconds
	 */
	function get_time_spent_on_the_platform($user_id) {

		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

		$sql = 'SELECT login_date, logout_date FROM ' . $tbl_track_login . ' 
						WHERE login_user_id = ' . intval($user_id);;

		$rs = api_sql_query($sql,__FILE__,__LINE__);

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
	function get_time_spent_on_the_course($user_id, $course_code) {
		// protect datas
		$user_id = intval($user_id);
		$course_code = addslashes($course_code);

		$tbl_track_course = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

		$sql = 'SELECT login_course_date, logout_course_date FROM ' . $tbl_track_course . ' 
						WHERE user_id = ' . $user_id . '
						AND course_code="' . $course_code . '"';

		$rs = api_sql_query($sql,__FILE__,__LINE__);

		$nb_seconds = 0;

		while ($a_connections = Database::fetch_array($rs)) {

			$s_login_date = $a_connections["login_course_date"];
			$s_logout_date = $a_connections["logout_course_date"];

			$i_timestamp_login_date = strtotime($s_login_date);
			$i_timestamp_logout_date = strtotime($s_logout_date);

			$nb_seconds += ($i_timestamp_logout_date - $i_timestamp_login_date);

		}

		return $nb_seconds;
	}
	
	function get_first_connection_date($student_id) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql = 'SELECT login_date FROM ' . $tbl_track_login . ' 
						WHERE login_user_id = ' . intval($student_id) . ' 
						ORDER BY login_date ASC LIMIT 0,1';

		$rs = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($rs)>0)
		{
			if ($first_login_date = Database::result($rs, 0, 0)) {
				return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($first_login_date));			
			}
		}
		return false;
	}

	function get_last_connection_date($student_id, $warning_message = false, $return_timestamp = false) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql = 'SELECT login_date FROM ' . $tbl_track_login . ' 
						WHERE login_user_id = ' . intval($student_id) . ' 
						ORDER BY login_date DESC LIMIT 0,1';

		$rs = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($rs)>0)
		{
			if ($last_login_date = Database::result($rs, 0, 0)) 
			{
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
	
	function get_first_connection_date_on_the_course($student_id, $course_code) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = 'SELECT login_course_date FROM ' . $tbl_track_login . ' 
						WHERE user_id = ' . intval($student_id) . ' 
						AND course_code = "' . Database::escape_string($course_code) . '"
						ORDER BY login_course_date ASC LIMIT 0,1';

		$rs = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($rs)>0)
		{
			if ($first_login_date = Database::result($rs, 0, 0)) {
				return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($first_login_date));
			}
		}
		return false;
	}

	function get_last_connection_date_on_the_course($student_id, $course_code) {
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$sql = 'SELECT login_course_date FROM ' . $tbl_track_login . ' 
						WHERE user_id = ' . intval($student_id) . ' 
						AND course_code = "' . Database::escape_string($course_code) . '"
						ORDER BY login_course_date DESC LIMIT 0,1';

		$rs = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($rs)>0)
		{
			if ($last_login_date = Database::result($rs, 0, 0)) {
				$timestamp = strtotime($last_login_date);
				$currentTimestamp = mktime();
				//If the last connection is > than 7 days, the text is red
				//345600 = 7 days in seconds 
				if ($currentTimestamp - $timestamp > 604800) {
					return '<span style="color: #F00;">' . format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date)) . ' <a href="'.api_get_path(REL_CODE_PATH).'announcements/announcements.php?action=add&remind_inactive='.$student_id.'" title="'.get_lang('RemindInactiveUser').'"><img align="middle" src="'.api_get_path(WEB_IMG_PATH).'messagebox_warning.gif" /></a></span>';
				} else {
					return format_locale_date(get_lang('DateFormatLongWithoutDay'), strtotime($last_login_date));
				}
			}
		}
		return false;
	}

	function count_course_per_student($user_id) {

		$user_id = intval($user_id);
		$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$sql = 'SELECT DISTINCT course_code
						FROM ' . $tbl_course_rel_user . '
						WHERE user_id = ' . $user_id;
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$nb_courses = Database::num_rows($rs);

		$sql = 'SELECT DISTINCT course_code
						FROM ' . $tbl_session_course_rel_user . '
						WHERE id_user = ' . $user_id;
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$nb_courses += Database::num_rows($rs);

		return $nb_courses;
	}

	function get_avg_student_progress($student_id, $course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);

		if(!empty($a_course['db_name']))
		{		
			// table definition
			$tbl_course_lp_view = Database :: get_course_table(TABLE_LP_VIEW, $a_course['db_name']);
			$tbl_course_lp_view_item = Database :: get_course_table(TABLE_LP_ITEM_VIEW, $a_course['db_name']);
			$tbl_course_lp_item = Database :: get_course_table(TABLE_LP_ITEM, $a_course['db_name']);
			$tbl_course_lp = Database :: get_course_table(TABLE_LP_MAIN, $a_course['db_name']);
	
			//get the list of learning paths
			$sql = 'SELECT id FROM ' . $tbl_course_lp;
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			$nb_lp = Database::num_rows($rs);
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
					if(Database::num_rows($resultItem)>0)
					{
						$avg_progress += Database::result($resultItem, 0, 0);
					}
				}
				$avg_progress = round($avg_progress / $nb_lp, 1);
			}
	
			return $avg_progress;
		}
		else
		{
			return null;
		}
	}
	/**
	 * This function gets:
	 * 1. The score average from all SCORM Test items in all LP in a course-> All the answers / All the max score.     
	 * 2. The score average from all Tests (quiz) in all LP in a course-> All the answers / All the max score.
	 * 3. And finally it will return the average between 1. and 2.
	 * This function does not take the results of a Test out of a LP
	 * 
	 * @param User id
	 * @param Course id
	 * @param Array limit average to listed lp ids
	 * @return string value (number %) Which represents a round integer explain in got in 3.
	 */
	function get_avg_student_score($student_id, $course_code, $lp_ids=array()) {
		
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		
		$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
		$tbl_stats_attempts= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
		

		
		
		$course = CourseManager :: get_course_information($course_code);
		if(!empty($course['db_name']))
		{
			$tbl_quiz_questions= Database :: get_course_table(TABLE_QUIZ_QUESTION,$course['db_name']);		
			$lp_table = Database :: get_course_table(TABLE_LP_MAIN,$course['db_name']);
			$lp_item_table = Database  :: get_course_table(TABLE_LP_ITEM,$course['db_name']);
			$lp_view_table = Database  :: get_course_table(TABLE_LP_VIEW,$course['db_name']);
			$lp_item_view_table = Database  :: get_course_table(TABLE_LP_ITEM_VIEW,$course['db_name']);
	
			$sql_course_lp = 'SELECT id FROM '.$lp_table;
			if(count($lp_ids)!=0)
			{
				$sql_course_lp.=' WHERE id IN ('.implode(',',$lp_ids).')';
			}
			$sql_result_lp = api_sql_query($sql_course_lp, __FILE__, __LINE__);
			
			$lp_scorm_score_total = 0;
			$lp_scorm_weighting_total = 0;
			
			if(Database::num_rows($sql_result_lp)>0){
				//Scorm test
				while($a_learnpath = Database::fetch_array($sql_result_lp)){
					
					//We get the last view id of this LP
					$sql='SELECT max(id) as id FROM '.$lp_view_table.' WHERE lp_id='.$a_learnpath['id'].' AND user_id="'.intval($student_id).'"';	
					$rs_last_lp_view_id = api_sql_query($sql, __FILE__, __LINE__);
					$lp_view_id = Database::result($rs_last_lp_view_id,0,'id');
					
					
					$sql='SELECT SUM(lp_iv.score)/count(lp_item_id) as score, SUM(lp_iv.max_score)/count(lp_item_id) as max_score 
							FROM '.$lp_item_view_table.' as lp_iv
							INNER JOIN '.$lp_item_table.' as lp_i
								ON lp_i.id = lp_iv.lp_item_id
								AND lp_i.item_type="sco"
							WHERE lp_iv.max_score != ""
							AND lp_view_id="'.$lp_view_id.'"';
					
					$rs = api_sql_query($sql, __FILE__, __LINE__);	
					$lp_scorm_score_total+=Database::result($rs, 0, 'score');
					$lp_scorm_weighting_total+=Database::result($rs, 0, 'max_score');
				}
					
				//The next call to a MySQL fetch function, such as mysql_fetch_assoc(), would return that row. 
				mysql_data_seek($sql_result_lp,0);
				
				
				//Quizz in a LP
				while($a_learnpath = Database::fetch_array($sql_result_lp)){
					//we got the maxscore this is wrong			
					/*
					echo $sql = 'SELECT id as item_id, max_score 
							FROM '.$lp_item_table.' AS lp_item
							WHERE lp_id='.$a_learnpath['id'].'
							AND item_type="quiz"';
					*/
					
					//Path is the exercise id
					$sql = 'SELECT path, id as item_id, max_score 
					FROM '.$lp_item_table.' AS lp_item
					WHERE lp_id='.$a_learnpath['id'].'
					AND item_type="quiz"';	
					
					$rsItems = api_sql_query($sql, __FILE__, __LINE__);
				
					//We get the last view id of this LP
					$sql='SELECT max(id) as id FROM '.$lp_view_table.' WHERE lp_id='.$a_learnpath['id'].' AND user_id="'.intval($student_id).'"';	
					$rs_last_lp_view_id = api_sql_query($sql, __FILE__, __LINE__);
					$lp_view_id = intval(Database::result($rs_last_lp_view_id,0,'id'));
					
					$total_score = $total_weighting = 0;
					if($lp_view_id!=0)
					{
						while($item = Database :: fetch_array($rsItems, 'ASSOC'))
						{
							// we take the score from a LP because we have lp_view_id
							$sql = 'SELECT score as student_score 
									FROM '.$lp_item_view_table.' as lp_view_item
									WHERE lp_view_item.lp_item_id = '.$item['item_id'].'
									AND lp_view_id = "'.$lp_view_id.'"
									';
		
							$rsScores = api_sql_query($sql, __FILE__, __LINE__);
														 
							// Real max score - this was implemented because of the random exercises							
					 		$sql_last_attempt = 'SELECT exe_id FROM '. $tbl_stats_exercices. ' ' .
					 							'WHERE exe_exo_id="' .$item['path']. '" AND exe_user_id="' . $student_id . '" AND orig_lp_id = "'.$a_learnpath['id'].'" AND orig_lp_item_id = "'.$item['item_id'].'" AND exe_cours_id="' . $course_code . '" ORDER BY exe_date DESC limit 1';
							
							$resultLastAttempt = api_sql_query($sql_last_attempt, __FILE__, __LINE__);
							$num = Database :: num_rows($resultLastAttempt);				
							if ($num > 0){								
								if ($num > 1){
									while ($rowLA = Database :: fetch_row($resultLastAttempt)) {
										$id_last_attempt = $rowLA[0];						
									}
								} else {
									$id_last_attempt = Database :: result($resultLastAttempt, 0, 0);
							
								}
							}	
							$sql = "SELECT SUM(t.ponderation) as maxscore from ( SELECT distinct question_id, marks,ponderation FROM $tbl_stats_attempts as at " .
						  	"INNER JOIN  $tbl_quiz_questions as q  on(q.id = at.question_id) where exe_id ='$id_last_attempt' ) as t";																
							$result = api_sql_query($sql, __FILE__, __LINE__);
							$row_max_score = Database :: fetch_array($result);							
							$maxscore = $row_max_score['maxscore'];							
							if ($maxscore=='')
							{
								$maxscore = $item['max_score'];
							}
							
							if(Database::num_rows($rsScores)>0)
							{
								$total_score += Database::result($rsScores, 0, 0);
															
								//echo $total_weighting += $item['max_score'];
								$total_weighting += $maxscore;
								
								if($total_weighting>0)
								{
									$lp_scorm_score_total += ($total_score/$total_weighting)*100;									
									$lp_scorm_weighting_total+=100;
								}
							}					
						}
					}
					
				}
			}
	
			$totalScore = $lp_scorm_score_total;
	
			$pourcentageScore = 0;
			if($lp_scorm_weighting_total>0)
			{
				$pourcentageScore = round(($totalScore * 100) / $lp_scorm_weighting_total);
				return $pourcentageScore;
			}	
			else
			{
				return null;
			}
		}
		else
		{
			return null;
		}
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
		
		global $_configuration;	
		if ($_configuration['multiple_access_urls']==true) {	
			$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){			
				$sql = 'SELECT id_session, course_code 
						FROM ' . $tbl_session_course . ' session_course INNER JOIN '.$tbl_session_rel_access_url.'  session_rel_url
						ON (session_course.id_session=session_rel_url.session_id) 
						WHERE id_coach=' . $coach_id.' AND access_url_id = '.$access_url_id;				  			
			}
		}
		
		$result = api_sql_query($sql,__FILE__,__LINE__);

		while ($a_courses = Database::fetch_array($result)) {
			$course_code = $a_courses["course_code"];
			$id_session = $a_courses["id_session"];

			$sql = "SELECT distinct	srcru.id_user  
								FROM $tbl_session_course_user AS srcru 
								WHERE course_code='$course_code' AND id_session='$id_session'";

			$rs = api_sql_query($sql,__FILE__,__LINE__);

			while ($row = Database::fetch_array($rs)) {
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
		if ($_configuration['multiple_access_urls']==true) {	
			$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){				
				$sql = 'SELECT session_course_user.id_user 
				FROM ' . $tbl_session_course_user . ' as session_course_user
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
		
		$result = api_sql_query($sql,__FILE__,__LINE__);

		while ($row = Database::fetch_array($result)) {
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

		$result = api_sql_query($sql,__FILE__,__LINE__);

		while ($a_courses = Database::fetch_array($result)) {
			$course_code = $a_courses["course_code"];

			$sql = "SELECT distinct	srcru.id_user  
								FROM $tbl_session_course_user AS srcru 
								WHERE course_code='$course_code' and id_session = '" . $id_session . "'";

			$rs = api_sql_query($sql, __FILE__, __LINE__);

			while ($row = Database::fetch_array($rs)) {
				$a_students[$row['id_user']] = $row['id_user'];
			}
		}

		//////////////////////////////////////////////////////////////
		// Then, courses where $coach_id is coach of the session    //
		//////////////////////////////////////////////////////////////

		$dsl_session_coach = 'SELECT id_coach FROM ' . $tbl_session . ' WHERE id="' . $id_session . '" AND id_coach="' . $coach_id . '"';
		$result = api_sql_query($dsl_session_coach, __FILE__, __LINE__);
		//He is the session_coach so we select all the users in the session
		if (Database::num_rows($result) > 0) {
			$sql = 'SELECT DISTINCT srcru.id_user FROM ' . $tbl_session_course_user . ' AS srcru WHERE id_session="' . $id_session . '"';
			$result = api_sql_query($sql,__FILE__,__LINE__);
			while ($row = Database::fetch_array($result)) {
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
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (Database::num_rows($result) > 0) {
			return true;
		}

		return false;

	}

	function get_courses_followed_by_coach($coach_id, $id_session = '') 
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
		$sql = 'SELECT DISTINCT course_code FROM ' . $tbl_session_course . ' WHERE id_coach=' . $coach_id;
		
		global $_configuration;	 
		if ($_configuration['multiple_access_urls']==true) {			
			$tbl_course_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);	
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
				$sql = 'SELECT DISTINCT session_course.course_code FROM ' . $tbl_session_course . ' session_course INNER JOIN '.$tbl_course_rel_access_url.' course_rel_url
						ON (session_course.course_code = course_rel_url.course_code)
						WHERE id_coach=' . $coach_id.' AND access_url_id = '.$access_url_id;	
			}
		}
		
		if (!empty ($id_session))
			$sql .= ' AND id_session=' . $id_session;
		$result = api_sql_query($sql, __FILE__, __LINE__);
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
			$sql .=  ' AND access_url_id = '.$access_url_id;
		}  else {
			$sql .=  ' WHERE access_url_id = '.$access_url_id;
		}
		
		$result = api_sql_query($sql, __FILE__, __LINE__);

		while ($row = Database::fetch_array($result)) {
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

		$rs = api_sql_query($sql,__FILE__,__LINE__);
		
		while ($row = Database::fetch_array($rs)) 
		{
			$a_sessions[$row["id"]] = $row;
		}

		// session where we are coach of a course
		$sql = 'SELECT DISTINCT session.id, session.name, session.date_start, session.date_end
						FROM ' . $tbl_session . ' as session
						INNER JOIN ' . $tbl_session_course . ' as session_course
							ON session.id = session_course.id_session
							AND session_course.id_coach=' . $coach_id;
		
		global $_configuration;	 
		if ($_configuration['multiple_access_urls']==true) {			
			$tbl_session_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);	
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
				$sql = 'SELECT DISTINCT session.id, session.name, session.date_start, session.date_end
						FROM ' . $tbl_session . ' as session
						INNER JOIN ' . $tbl_session_course . ' as session_course 
							ON session.id = session_course.id_session AND session_course.id_coach=' . $coach_id.'  
						INNER JOIN '.$tbl_session_rel_access_url.' session_rel_url							
						ON (session.id = session_rel_url.session_id) 
						WHERE access_url_id = '.$access_url_id;
			}
		}
		
		$rs = api_sql_query($sql,__FILE__,__LINE__);

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
		while ($row = Database::fetch_array($rs)) {
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

		if(!empty($a_course['db_name']))
		{
			// table definition
			$tbl_item_property = Database :: get_course_table(TABLE_ITEM_PROPERTY, $a_course['db_name']);
			$sql = 'SELECT 1
							FROM ' . $tbl_item_property . ' 
							WHERE insert_user_id=' . $student_id . '
							AND tool="work"';
	
			$rs = api_sql_query($sql, __LINE__, __FILE__);
			return Database::num_rows($rs);
		}
		else
		{
			return null;
		}
	}

	function count_student_messages($student_id, $course_code) {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');

		// protect datas
		$student_id = intval($student_id);
		$course_code = addslashes($course_code);

		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);

		if(!empty($a_course['db_name']))
		{
			// table definition
			$tbl_messages = Database :: get_course_table(TABLE_FORUM_POST, $a_course['db_name']);
			$sql = 'SELECT 1
							FROM ' . $tbl_messages . ' 
							WHERE poster_id=' . $student_id;
	
			$rs = api_sql_query($sql, __LINE__, __FILE__);
			return Database::num_rows($rs);
		}
		else
		{
			return null;
		}
	}
	
/**
* This function counts the number of post by course
* @param  string $course_code - Course ID   
* @return	int the number of post by course
* @author Christian Fasanando <christian.fasanando@dokeos.com>, 
* @version enero 2009, dokeos 1.8.6
*/
	function count_number_of_posts_by_course($course_code) {		
		//protect data
		$course_code = addslashes($course_code);
		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);
		$count = 0;		
		if (!empty($a_course['db_name'])) {
			$tbl_posts = Database :: get_course_table(TABLE_FORUM_POST, $a_course['db_name']);
			$sql = "SELECT count(*) FROM $tbl_posts";
			$result = api_sql_query($sql, __FILE__, __LINE__);
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
	function count_number_of_threads_by_course($course_code) {		
		//protect data
		$course_code = addslashes($course_code);
		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);
		$count = 0;		
		if (!empty($a_course['db_name'])) {
			$tbl_threads = Database :: get_course_table(TABLE_FORUM_THREAD, $a_course['db_name']);
			$sql = "SELECT count(*) FROM $tbl_threads";
			$result = api_sql_query($sql, __FILE__, __LINE__);
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
	function count_number_of_forums_by_course($course_code) {		
		//protect data
		$course_code = addslashes($course_code);
		// get the informations of the course 
		$a_course = CourseManager :: get_course_information($course_code);
		$count = 0;		
		if (!empty($a_course['db_name'])) {
			$tbl_forums = Database :: get_course_table(TABLE_FORUM, $a_course['db_name']);
			$sql = "SELECT count(*) FROM $tbl_forums";
			$result = api_sql_query($sql, __FILE__, __LINE__);
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
	function chat_connections_during_last_x_days_by_course($course_code,$last_days) {		
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
			$result = api_sql_query($sql, __FILE__, __LINE__);
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
	function chat_last_connection($student_id,$course_code) {
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
					 	
			$rs = api_sql_query($sql, __LINE__, __FILE__);
			$row = Database::fetch_array($rs);			
			$last_connection = $row['access_date'];
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
		return Database::num_rows($rs);
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
		return Database::num_rows($rs);
	}

	function get_course_list_in_session_from_student($user_id, $id_session) {
		$user_id = intval($user_id);
		$id_session = intval($id_session);
		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$sql = 'SELECT course_code FROM ' . $tbl_session_course_user . ' WHERE id_user="' . $user_id . '" AND id_session="' . $id_session . '"';
		$result = api_sql_query($sql, __LINE__, __FILE__);
		$a_courses = array ();
		while ($row = Database::fetch_array($result)) {
			$a_courses[$row['course_code']] = $row['course_code'];
		}
		return $a_courses;
	}
	
	function get_inactives_students_in_course($course_code, $since, $session_id=0) 
	{
		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
		$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
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

		$rs = api_sql_query($sql,__FILE__,__LINE__);
		$inactive_users = array();
		while($user = Database::fetch_array($rs))
		{
			$inactive_users[] = $user['user_id'];
		}
		return $inactive_users;
	}
	
	function count_login_per_student($student_id, $course_code) { 
		$student_id = intval($student_id); 
		$course_code = addslashes($course_code); 
		$tbl_course_rel_user = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS); 
		
		$sql = 'SELECT '.$student_id.' 
		FROM ' . $tbl_course_rel_user . ' 
		WHERE access_user_id=' . $student_id . ' 
		AND access_cours_code="' . $course_code . '"'; 
		
		$rs = api_sql_query($sql, __FILE__, __LINE__); 
		$nb_login = Database::num_rows($rs); 
		
		return $nb_login; 
	}
	
	
	function get_student_followed_by_drh($hr_dept_id) {
		
		$hr_dept_id = intval($hr_dept_id);
		$a_students = array ();
		
		$tbl_organism = Database :: get_main_table(TABLE_MAIN_ORGANISM);
		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
		
		$sql = 'SELECT DISTINCT user_id FROM '.$tbl_user.' as user
				WHERE hr_dept_id='.$hr_dept_id;
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		
		while($user = Database :: fetch_array($rs))
		{
			$a_students[$user['user_id']] = $user['user_id'];
		}
		
		
		return $a_students;
	}

}
?>
