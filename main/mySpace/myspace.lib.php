<?php
/* For licensing terms, see /license.txt */
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';

class MySpace {

	/**
	 * This function serves exporting data in CSV format.
	 * @param array $header			The header labels.
	 * @param array $data			The data array.
	 * @param string $file_name		The name of the file which contains exported data.
	 * @return string mixed				Returns a message (string) if an error occurred.
	 */
	function export_csv($header, $data, $file_name = 'export.csv') {

		$archive_path = api_get_path(SYS_ARCHIVE_PATH);
		$archive_url = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';

		if (!$open = fopen($archive_path.$file_name, 'w+')) {
			$message = get_lang('noOpen');
		} else {
			$info = '';

			foreach ($header as $value) {
				$info .= $value.';';
			}
			$info .= "\r\n";

			foreach ($data as $row) {
				foreach ($row as $value) {
					$info .= $value.';';
				}
				$info .= "\r\n";
			}

			fwrite($open, $info);
			fclose($open);
			@chmod($file_name, api_get_permissions_for_new_files());

			header("Location:".$archive_url.$file_name);
		}
		return $message;
	}

	/**
	 * Gets the connections to a course as an array of login and logout time
	 *
	 * @param 	int 	User ud
	 * @param 	string 	Course code
	 * @param	int		Session id (optional, default = 0)
	 * @return 	array   Conections
	 */
	static function get_connections_to_course($user_id, $course_code, $session_id = 0) {

		// Database table definitions
	    $tbl_track_course 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

		// protect data
		$user_id     = intval($user_id);
		$course_code = Database::escape_string($course_code);
		$session_id  = intval($session_id);

	    $sql = 'SELECT login_course_date, logout_course_date FROM ' . $tbl_track_course . '
                WHERE   user_id = '.$user_id.' AND 
                        course_code="'.$course_code.'" AND 
                        session_id = '.$session_id.' 
                ORDER BY login_course_date ASC';
	    $rs = Database::query($sql);
	    $connections = array();

	    while ($row = Database::fetch_array($rs)) {
	        $timestamp_login_date = api_strtotime($row['login_course_date'], 'UTC');
	        $timestamp_logout_date = api_strtotime($row['logout_course_date'], 'UTC');
	        $connections[] = array('login' => $timestamp_login_date, 'logout' => $timestamp_logout_date);
	    }
	    return $connections;
	}
    
    static function get_connections_from_course_list($user_id, $course_list, $session_id = 0) {
		// Database table definitions
	    $tbl_track_course 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        if (empty($course_list)) {
            return false;   
        }

		// protect data
		$user_id     = intval($user_id);
		$course_code = Database::escape_string($course_code);
		$session_id  = intval($session_id);
        $new_course_list = array();;
        foreach ($course_list as $course_item) {            
            $new_course_list[] =  '"'.Database::escape_string($course_item['code']).'"';
        }
        $course_list = implode(', ', $new_course_list);
        
        if (empty($course_list)) {
            return false;   
        }
	    $sql = 'SELECT login_course_date, logout_course_date, course_code FROM ' . $tbl_track_course . '
                WHERE   user_id = '.$user_id.' AND 
                        course_code IN ('.$course_list.') AND 
                        session_id = '.$session_id.' 
                ORDER BY login_course_date ASC';
	    $rs = Database::query($sql);
	    $connections = array();

	    while ($row = Database::fetch_array($rs)) {
	        $timestamp_login_date = api_strtotime($row['login_course_date'], 'UTC');
	        $timestamp_logout_date = api_strtotime($row['logout_course_date'], 'UTC');
	        $connections[] = array('login' => $timestamp_login_date, 'logout' => $timestamp_logout_date,'course_code' => $row['course_code']);
	    }
	    return $connections;
	}

	/**
	 * TODO: Not used, to b deleted?
	 * Enter description here...
	 * @param int $user_id
	 * @param string $course_code
	 * @param date $year
	 * @param date $month
	 * @param date $day
	 * @return unknown
	 */
	static function get_connections_to_course_by_time($user_id, $course_code, $year = '', $month = '', $day = '') {
		// Database table definitions
	    $tbl_track_course 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

	    $sql = 'SELECT login_course_date, logout_course_date FROM ' . $tbl_track_course . '
	    				WHERE user_id = ' . intval($user_id) . '
	    				AND course_code="' . Database::escape_string($course_code) . '"
	    				ORDER BY login_course_date DESC';

	    $rs = Database::query($sql);
	    $connections = array();
	    while ($row = Database::fetch_array($rs)) {
	        $login_date = $row['login_course_date'];
	        $logout_date = $row['logout_course_date'];
	        $timestamp_login_date = strtotime($login_date);
	        $timestamp_logout_date = strtotime($logout_date);
	        $connections[] = array('login' => $timestamp_login_date, 'logout' => $timestamp_logout_date);
	    }
	    return $connections;
	}

	/**
	 * Creates a small table in the last column of the table with the user overview
	 *
	 * @param integer $user_id the id of the user
	 * @param array $url_params additonal url parameters
	 * @param array $row the row information (the other columns)
	 * @return string html code
	 */
	function course_info_tracking_filter($user_id, $url_params, $row) {
		// the table header
		$return .= '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
		/*$return .= '	<tr>';
		$return .= '		<th>'.get_lang('Course').'</th>';
		$return .= '		<th>'.get_lang('AvgTimeSpentInTheCourse').'</th>';
		$return .= '		<th>'.get_lang('AvgStudentsProgress').'</th>';
		$return .= '		<th>'.get_lang('AvgCourseScore').'</th>';
		$return .= '		<th>'.get_lang('AvgExercisesScore').'</th>';
		$return .= '		<th>'.get_lang('AvgMessages').'</th>';
		$return .= '		<th>'.get_lang('AvgAssignments').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesScoreObtained').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesScorePossible').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesAnswered').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesScorePercentage').'</th>';
		$return .= '		<th>'.get_lang('FirstLogin').'</th>';
		$return .= '		<th>'.get_lang('LatestLogin').'</th>';
		$return .= '	</tr>';*/

		// database table definition
		$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

		// getting all the courses of the user
		$sql = "SELECT * FROM $tbl_course_user WHERE user_id = '".Database::escape_string($user_id)."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
		$result = Database::query($sql);
		while ($row = Database::fetch_row($result)) {
			$return .= '<tr>';
			// course code
			$return .= '	<td width="157px" >'.cut($row[0], 20, true).'</td>';
			// time spent in the course
			$return .= '	<td><div>'.api_time_to_hms(Tracking :: get_time_spent_on_the_course($user_id, $row[0])).'</div></td>';
			// student progress in course
			$return .= '	<td><div>'.round(Tracking :: get_avg_student_progress($user_id, $row[0]), 2).'</div></td>';
			// student score
			$avg_score = Tracking :: get_avg_student_score($user_id, $row[0]);
			if (is_numeric($avg_score)) {
				$avg_score = round($avg_score,2);
			} else {
				$$avg_score = '-';
			}

			$return .= '	<td><div>'.$avg_score.'</div></td>';
			// student tes score
			//$return .= '	<td><div style="width:40px">'.round(Tracking :: get_avg_student_exercise_score ($user_id, $row[0]),2).'%</div></td>';
			// student messages
			$return .= '	<td><div>'.Tracking :: count_student_messages($user_id, $row[0]).'</div></td>';
			// student assignments
			$return .= '	<td><div>'.Tracking :: count_student_assignments($user_id, $row[0]).'</div></td>';
			// student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
			$exercises_results = MySpace::exercises_results($user_id, $row[0]);
			$return .= '	<td width="105px"><div>'.(is_null($exercises_results['percentage']) ? '' : $exercises_results['score_obtained'].'/'.$exercises_results['score_possible'].' ( '.$exercises_results['percentage'].'% )').'</div></td>';
			//$return .= '	<td><div>'.$exercises_results['score_possible'].'</div></td>';
			$return .= '	<td><div>'.$exercises_results['questions_answered'].'</div></td>';
			//$return .= '	<td><div>'.$exercises_results['percentage'].'% </div></td>';
			// first connection
			//$return .= '	<td width="60px">'.Tracking :: get_first_connection_date_on_the_course ($user_id, $row[0]).'</td>';
			// last connection
			$return .= '	<td><div>'.Tracking :: get_last_connection_date_on_the_course ($user_id, $row[0]).'</div></td>';
			$return .= '<tr>';
		}
		$return .= '</table>';
		return $return;
	}

	/**
	 * Display a sortable table that contains an overview off all the reporting progress of all users and all courses the user is subscribed to
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @version Dokeos 1.8.6
	 * @since October 2008
	 */
	function display_tracking_user_overview() {
		MySpace::display_user_overview_export_options();

		$t_head .= '	<table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
		//$t_head .= '	<caption>'.get_lang('CourseInformation').'</caption>';
		$t_head .=		'<tr>';
		$t_head .= '		<th width="155px" style="border-left:0;border-bottom:0"><span>'.get_lang('Course').'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'), 6, true).'</span></th>';
		//$t_head .= '		<th><div style="width:40px">'.get_lang('AvgExercisesScore').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfMessages'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfAssignments'), 6, true).'</span></th>';
		$t_head .= '		<th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
		//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePossible').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'), 6, true).'</span></th>';
		//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePercentage').'</div></th>';
		//$t_head .= '		<th><div style="width:60px">'.get_lang('FirstLogin').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
		$t_head .= '	</tr></table>';

		$addparams = array('view' => 'admin', 'display' => 'useroverview');

		$table = new SortableTable('tracking_user_overview', array('MySpace','get_number_of_users_tracking_overview'), array('MySpace','get_user_data_tracking_overview'), 0);
		$table->additional_parameters = $addparams;

		$table->set_header(0, get_lang('OfficialCode'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
		if (api_is_western_name_order()) {
			$table->set_header(1, get_lang('FirstName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
			$table->set_header(2, get_lang('LastName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
		} else {
			$table->set_header(1, get_lang('LastName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
			$table->set_header(2, get_lang('FirstName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
		}
		$table->set_header(3, get_lang('LoginName'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
		$table->set_header(4, $t_head, false, array('style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'), array('style' => 'width:90%;padding:0;font-size:7.5pt;'));
		$table->set_column_filter(4, array('MySpace','course_info_tracking_filter'));
		$table->display();
	}

	/**
	 * Displays a form with all the additionally defined user fields of the profile
	 * and give you the opportunity to include these in the CSV export
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @version Dokeos 1.8.6
	 * @since November 2008
	 */
	function display_user_overview_export_options() {
		// include the user manager and formvalidator library


		if ($_GET['export'] == 'options') {
			// get all the defined extra fields
			$extrafields = UserManager::get_extra_fields(0, 50, 5, 'ASC', false, 1);

			// creating the form with all the defined extra fields
			$form = new FormValidator('exportextrafields', 'post', api_get_self()."?view=".Security::remove_XSS($_GET['view']).'&display='.Security::remove_XSS($_GET['display']).'&export='.Security::remove_XSS($_GET['export']));

			if (is_array($extrafields) && count($extrafields) > 0) {
				foreach ($extrafields as $key => $extra) {
					$form->addElement('checkbox', 'extra_export_field'.$extra[0], '', $extra[3]);
				}
				$form->addElement('style_submit_button','submit', get_lang('Ok'),'class="save"' );

				// setting the default values for the form that contains all the extra fields
				if (is_array($_SESSION['additional_export_fields'])) {
					foreach ($_SESSION['additional_export_fields'] as $key => $value) {
						$defaults['extra_export_field'.$value] = 1;
					}
				}
				$form->setDefaults($defaults);
			} else {
				$form->addElement('html', Display::display_warning_message(get_lang('ThereAreNotExtrafieldsAvailable')));
			}

			if ($form->validate()) {
				// exporting the form values
				$values = $form->exportValues();

				// re-initialising the session that contains the additional fields that need to be exported
				$_SESSION['additional_export_fields'] = array();

				// adding the fields that are checked to the session
				$message = '';
				foreach ($values as $field_ids => $value) {
					if ($value == 1 && strstr($field_ids,'extra_export_field')) {
						$_SESSION['additional_export_fields'][] = str_replace('extra_export_field', '', $field_ids);
					}
				}

				// adding the fields that will be also exported to a message string
				if (is_array($_SESSION['additional_export_fields'])) {
					foreach ($_SESSION['additional_export_fields'] as $key => $extra_field_export) {
						$message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
					}
				}

				// Displaying a feedback message
				if (!empty($_SESSION['additional_export_fields'])) {
					Display::display_confirmation_message(get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>', false);
				} else  {
					Display::display_confirmation_message(get_lang('NoAdditionalFieldsWillBeExported'), false);
				}
				$message = '';
			} else {
				$form->display();
			}

		} else {
			if (!empty($_SESSION['additional_export_fields'])) {
				// get all the defined extra fields
				$extrafields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

				foreach ($_SESSION['additional_export_fields'] as $key => $extra_field_export) {
					$message .= '<li>'.$extrafields[$extra_field_export][3].'</li>';
				}

				Display::display_normal_message(get_lang('FollowingFieldsWillAlsoBeExported').': <br /><ul>'.$message.'</ul>', false);
				$message = '';
			}
		}
	}

	/**
	 * Display a sortable table that contains an overview of all the reporting progress of all courses
	 */
	function display_tracking_course_overview() {
		//MySpace::display_user_overview_export_options();

		$t_head .= '	<table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
		//$t_head .= '	<caption>'.get_lang('CourseInformation').'</caption>';
		$t_head .=		'<tr>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'), 6, true).'</span></th>';
		//$t_head .= '		<th><div style="width:40px">'.get_lang('AvgExercisesScore').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfMessages'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfAssignments'), 6, true).'</span></th>';
		$t_head .= '		<th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
		//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePossible').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'), 6, true).'</span></th>';
		//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePercentage').'</div></th>';
		//$t_head .= '		<th><div style="width:60px">'.get_lang('FirstLogin').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
		$t_head .= '	</tr></table>';

		$addparams = array('view' => 'admin', 'display' => 'courseoverview');

		$table = new SortableTable('tracking_session_overview', array('MySpace','get_total_number_courses'), array('MySpace','get_course_data_tracking_overview'), 1);
		$table->additional_parameters = $addparams;

		$table->set_header(0, '', false, null, array('style' => 'display: none'));
		$table->set_header(1, get_lang('Course'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
		$table->set_header(2, $t_head, false, array('style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'), array('style' => 'width:90%;padding:0;font-size:7.5pt;'));
		$table->set_column_filter(2, array('MySpace','course_tracking_filter'));
		$table->display();
	}

	/**
	 * Get the total number of courses
	 *
	 * @return integer Total number of courses
	 */
	public function get_total_number_courses() {
		// database table definition
		$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		return Database::count_rows($main_course_table);
	}

	/**
	 * Get data for the courses
	 *
	 * @param int Inferior limit
	 * @param int Number of items to select
	 * @param string Column to order on
	 * @param string Order direction
	 * @return array Results
	 */
	public function get_course_data_tracking_overview($from, $number_of_items, $column, $direction) {		
		$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $from = intval($from);
        $number_of_items = intval($number_of_items);
        
		$sql = "SELECT code AS col0, title AS col1 FROM $main_course_table";
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
		$result = Database::query($sql);
		$return = array ();
		while ($course = Database::fetch_row($result)) {
			$return[] = $course;
		}
		return $return;
	}

	/**
	 * Fills in course reporting data
	 *
	 * @param integer course code
	 * @param array $url_params additonal url parameters
	 * @param array $row the row information (the other columns)
	 * @return string html code
	 */
	function course_tracking_filter($course_code, $url_params, $row) {
		$course_code = $row[0];
		// the table header
		$return .= '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';

		// database table definition
		$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

		// getting all the courses of the user
		$sql = "SELECT * FROM $tbl_user AS u INNER JOIN $tbl_course_rel_user AS cu ON cu.user_id = u.user_id WHERE cu.course_code = '".$course_code."' AND ISNULL(cu.role);";
		$result = Database::query($sql);
		$time_spent = 0;
		$progress = 0;
		$nb_progress_lp = 0;
		$score = 0;
		$nb_score_lp = 0;
		$nb_messages = 0;
		$nb_assignments = 0;
		$last_login_date = false;
		$total_score_obtained = 0;
		$total_score_possible = 0;
		$total_questions_answered = 0;
		while ($row = Database::fetch_object($result)) {
			// get time spent in the course and session
			$time_spent += Tracking::get_time_spent_on_the_course($row->user_id, $course_code);
			$progress_tmp = Tracking::get_avg_student_progress($row->user_id, $course_code, array(), null, true);
			$progress += $progress_tmp[0];
			$nb_progress_lp += $progress_tmp[1];
			$score_tmp = Tracking :: get_avg_student_score($row->user_id, $course_code, array(), null, true);
			if(is_array($score_tmp)) {
				$score += $score_tmp[0];
				$nb_score_lp += $score_tmp[1];
			}
			$nb_messages += Tracking::count_student_messages($row->user_id, $course_code);
			$nb_assignments += Tracking::count_student_assignments($row->user_id, $course_code);

			$last_login_date_tmp = Tracking :: get_last_connection_date_on_the_course ($row->user_id, $course_code, null, false);
			if($last_login_date_tmp != false && $last_login_date == false) { // TODO: To be cleaned
				$last_login_date = $last_login_date_tmp;
			} else if($last_login_date_tmp != false && $last_login_date != false) { // TODO: Repeated previous condition. To be cleaned.
				// Find the max and assign it to first_login_date
				if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
					$last_login_date = $last_login_date_tmp;
				}
			}

			$exercise_results_tmp = MySpace::exercises_results($row->user_id, $course_code);
			$total_score_obtained += $exercise_results_tmp['score_obtained'];
			$total_score_possible += $exercise_results_tmp['score_possible'];
			$total_questions_answered += $exercise_results_tmp['questions_answered'];
		}
		if($nb_progress_lp > 0) {
			$avg_progress = round($progress / $nb_progress_lp, 2);
		} else {
			$avg_progress = 0;
		}
		if($nb_score_lp > 0) {
			$avg_score = round($score / $nb_score_lp, 2);
		} else {
			$avg_score = '-';
		}
		if($last_login_date) {
			$last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
		} else {
			$last_login_date = '-';
		}
		if($total_score_possible > 0) {
			$total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
		} else {
			$total_score_percentage = 0;
		}
		if($total_score_percentage > 0) {
			$total_score = $total_score_obtained.'/'.$total_score_possible.' ('.$total_score_percentage.' %)';
		} else {
			$total_score = '-';
		}
		$return .= '<tr>';
		// time spent in the course
		$return .= '	<td style="width:164px;">'.api_time_to_hms($time_spent).'</td>';
		// student progress in course
		$return .= '	<td>'.$avg_progress.'</td>';
		// student score
		$return .= '	<td>'.$avg_score.'</td>';
		// student messages
		$return .= '	<td>'.$nb_messages.'</td>';
		// student assignments
		$return .= '	<td>'.$nb_assignments.'</td>';
		// student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
		$return .= '<td width="105px;">'.$total_score.'</td>';
		$return .= '<td>'.$total_questions_answered.'</td>';
		// last connection
		$return .= '	<td>'.$last_login_date.'</td>';
		$return .= '</tr>';
		$return .= '</table>';
		return $return;
	}

	/**
	 * This function exports the table that we see in display_tracking_course_overview()
	 *
	 */
	function export_tracking_course_overview() {
		// database table definition
		$tbl_course_rel_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

		// the values of the sortable table
		if ($_GET['tracking_course_overview_page_nr']) {
			$from = $_GET['tracking_course_overview_page_nr'];
		} else {
			$from = 0;
		}
		if ($_GET['tracking_course_overview_column']) {
			$orderby = $_GET['tracking_course_overview_column'];
		} else {
			$orderby = 0;
		}

		if ($_GET['tracking_course_overview_direction']) {
			$direction = $_GET['tracking_course_overview_direction'];
		} else {
			$direction = 'ASC';
		}

		$course_data = MySpace::get_course_data_tracking_overview($from, 1000, $orderby, $direction);

		$csv_content = array();

		// the first line of the csv file with the column headers
		$csv_row = array();
		$csv_row[] = get_lang('Course', '');
		$csv_row[] = get_lang('AvgTimeSpentInTheCourse', '');
		$csv_row[] = get_lang('AvgStudentsProgress', '');
		$csv_row[] = get_lang('AvgCourseScore', '');
		$csv_row[] = get_lang('TotalNumberOfMessages', '');
		$csv_row[] = get_lang('TotalNumberOfAssignments', '');
		$csv_row[] = get_lang('TotalExercisesScoreObtained', '');
		$csv_row[] = get_lang('TotalExercisesScorePossible', '');
		$csv_row[] = get_lang('TotalExercisesAnswered', '');
		$csv_row[] = get_lang('TotalExercisesScorePercentage', '');
		$csv_row[] = get_lang('LatestLogin', '');
		$csv_content[] = $csv_row;

		// the other lines (the data)
		foreach ($course_data as $key => $course) {
			$course_code = $course[0];
			$course_title = $course[1];

			$csv_row = array();
			$csv_row[] = $course_title;

			// getting all the courses of the session
			$sql = "SELECT * FROM $tbl_user AS u INNER JOIN $tbl_course_rel_user AS cu ON cu.user_id = u.user_id WHERE cu.course_code = '".$course_code."' AND ISNULL(cu.role);";
			$result = Database::query($sql);
			$time_spent = 0;
			$progress = 0;
			$nb_progress_lp = 0;
			$score = 0;
			$nb_score_lp = 0;
			$nb_messages = 0;
			$nb_assignments = 0;
			$last_login_date = false;
			$total_score_obtained = 0;
			$total_score_possible = 0;
			$total_questions_answered = 0;
			while ($row = Database::fetch_object($result)) {
				// get time spent in the course and session
				$time_spent += Tracking::get_time_spent_on_the_course($row->user_id, $course_code);
				$progress_tmp = Tracking::get_avg_student_progress($row->user_id, $course_code, array(), null, true);
				$progress += $progress_tmp[0];
				$nb_progress_lp += $progress_tmp[1];
				$score_tmp = Tracking :: get_avg_student_score($row->user_id, $course_code, array(), null, true);
				if(is_array($score_tmp)) {
					$score += $score_tmp[0];
					$nb_score_lp += $score_tmp[1];
				}
				$nb_messages += Tracking::count_student_messages($row->user_id, $course_code);
				$nb_assignments += Tracking::count_student_assignments($row->user_id, $course_code);

				$last_login_date_tmp = Tracking :: get_last_connection_date_on_the_course ($row->user_id, $course_code, null, false);
				if($last_login_date_tmp != false && $last_login_date == false) { // TODO: To be cleaned.
					$last_login_date = $last_login_date_tmp;
				} else if($last_login_date_tmp != false && $last_login_date == false) { // TODO: Repeated previous condition. To be cleaned.
					// Find the max and assign it to first_login_date
					if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
						$last_login_date = $last_login_date_tmp;
					}
				}

				$exercise_results_tmp = MySpace::exercises_results($row->user_id, $course_code);
				$total_score_obtained += $exercise_results_tmp['score_obtained'];
				$total_score_possible += $exercise_results_tmp['score_possible'];
				$total_questions_answered += $exercise_results_tmp['questions_answered'];
			}
			if($nb_progress_lp > 0) {
				$avg_progress = round($progress / $nb_progress_lp, 2);
			} else {
				$avg_progress = 0;
			}
			if($nb_score_lp > 0) {
				$avg_score = round($score / $nb_score_lp, 2);
			} else {
				$avg_score = '-';
			}
			if($last_login_date) {
				$last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
			} else {
				$last_login_date = '-';
			}
			if($total_score_possible > 0) {
				$total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
			} else {
				$total_score_percentage = 0;
			}
			// time spent in the course
			$csv_row[] = api_time_to_hms($time_spent);
			// student progress in course
			$csv_row[] = $avg_progress;
			// student score
			$csv_row[] = $avg_score;
			// student messages
			$csv_row[] = $nb_messages;
			// student assignments
			$csv_row[] = $nb_assignments;
			// student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
			$csv_row[] = $total_score_obtained;
			$csv_row[] = $total_score_possible;
			$csv_row[] = $total_questions_answered;
			$csv_row[] = $total_score_percentage;
			// last connection
			$csv_row[] = $last_login_date;
			$csv_content[] = $csv_row;
		}
		Export :: export_table_csv($csv_content, 'reporting_course_overview');
		exit;
	}

	/**
	 * Display a sortable table that contains an overview of all the reporting progress of all sessions and all courses the user is subscribed to
	 * @author Guillaume Viguier <guillaume@viguierjust.com>
	 */
	function display_tracking_session_overview() {
		//MySpace::display_user_overview_export_options();

		$t_head .= '	<table style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
		//$t_head .= '	<caption>'.get_lang('CourseInformation').'</caption>';
		$t_head .=		'<tr>';
		$t_head .= '		<th width="155px" style="border-left:0;border-bottom:0"><span>'.get_lang('Course').'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgTimeSpentInTheCourse'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgStudentsProgress'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('AvgCourseScore'), 6, true).'</span></th>';
		//$t_head .= '		<th><div style="width:40px">'.get_lang('AvgExercisesScore').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfMessages'), 6, true).'</span></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalNumberOfAssignments'), 6, true).'</span></th>';
		$t_head .= '		<th width="105px" style="border-bottom:0"><span>'.get_lang('TotalExercisesScoreObtained').'</span></th>';
		//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePossible').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0"><span>'.cut(get_lang('TotalExercisesAnswered'), 6, true).'</span></th>';
		//$t_head .= '		<th><div>'.get_lang('TotalExercisesScorePercentage').'</div></th>';
		//$t_head .= '		<th><div style="width:60px">'.get_lang('FirstLogin').'</div></th>';
		$t_head .= '		<th style="padding:0;border-bottom:0;border-right:0;"><span>'.get_lang('LatestLogin').'</span></th>';
		$t_head .= '	</tr></table>';

		$addparams = array('view' => 'admin', 'display' => 'sessionoverview');

		$table = new SortableTable('tracking_session_overview', array('MySpace','get_total_number_sessions'), array('MySpace','get_session_data_tracking_overview'), 1);
		$table->additional_parameters = $addparams;

		$table->set_header(0, '', false, null, array('style' => 'display: none'));
		$table->set_header(1, get_lang('Session'), true, array('style' => 'font-size:8pt'), array('style' => 'font-size:8pt'));
		$table->set_header(2, $t_head, false, array('style' => 'width:90%;border:0;padding:0;font-size:7.5pt;'), array('style' => 'width:90%;padding:0;font-size:7.5pt;'));
		$table->set_column_filter(2, array('MySpace','session_tracking_filter'));
		$table->display();
	}

	/**
	 * Get the total number of sessions
	 *
	 * @return integer Total number of sessions
	 */
	public function get_total_number_sessions() {
		// database table definition
		$main_session_table = Database :: get_main_table(TABLE_MAIN_SESSION);
		return Database::count_rows($main_session_table);
	}

	/**
	 * Get data for the sessions
	 *
	 * @param int Inferior limit
	 * @param int Number of items to select
	 * @param string Column to order on
	 * @param string Order direction
	 * @return array Results
	 */
	public function get_session_data_tracking_overview($from, $number_of_items, $column, $direction) {
		//global $_configuration;
		// database table definition
		//$access_url_id = api_get_current_access_url_id();
	 	//$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$main_session_table = Database :: get_main_table(TABLE_MAIN_SESSION);

		/*if ($_configuration['multiple_access_urls']) {
			$condition_multi_url = ", $tbl_url_rel_user as url_user WHERE user.user_id=url_user.user_id AND access_url_id='$access_url_id'";
		}

		global $export_csv;
		if ($export_csv) {
			$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
		} else {
			$is_western_name_order = api_is_western_name_order();
		}*/
		$sql = "SELECT id AS col0, name AS col1 FROM $main_session_table";
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
		$result = Database::query($sql);
		$return = array ();
		while ($session = Database::fetch_row($result)) {
			$return[] = $session;
		}
		return $return;
	}

	/**
	 * Fills in session reporting data
	 *
	 * @param integer $user_id the id of the user
	 * @param array $url_params additonal url parameters
	 * @param array $row the row information (the other columns)
	 * @return string html code
	 */
	function session_tracking_filter($session_id, $url_params, $row) {
		$session_id = $row[0];
		// the table header
		$return .= '<table class="data_table" style="width: 100%;border:0;padding:0;border-collapse:collapse;table-layout: fixed">';
		/*$return .= '	<tr>';
		$return .= '		<th>'.get_lang('Course').'</th>';
		$return .= '		<th>'.get_lang('AvgTimeSpentInTheCourse').'</th>';
		$return .= '		<th>'.get_lang('AvgStudentsProgress').'</th>';
		$return .= '		<th>'.get_lang('AvgCourseScore').'</th>';
		$return .= '		<th>'.get_lang('AvgExercisesScore').'</th>';
		$return .= '		<th>'.get_lang('AvgMessages').'</th>';
		$return .= '		<th>'.get_lang('AvgAssignments').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesScoreObtained').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesScorePossible').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesAnswered').'</th>';
		$return .= '		<th>'.get_lang('TotalExercisesScorePercentage').'</th>';
		$return .= '		<th>'.get_lang('FirstLogin').'</th>';
		$return .= '		<th>'.get_lang('LatestLogin').'</th>';
		$return .= '	</tr>';*/

		// database table definition
		$tbl_session_rel_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_session_rel_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

		// getting all the courses of the user
		$sql = "SELECT * FROM $tbl_course AS c INNER JOIN $tbl_session_rel_course AS sc ON sc.course_code = c.code WHERE sc.id_session = '".$session_id."';";
		$result = Database::query($sql);
		while ($row = Database::fetch_object($result)) {
			$return .= '<tr>';
			// course code
			$return .= '	<td width="157px" >'.$row->title.'</td>';
			// get the users in the course
			$sql = "SELECT user_id FROM $tbl_user AS u INNER JOIN $tbl_session_rel_course_rel_user AS scu ON u.user_id = scu.id_user WHERE scu.id_session = '".$session_id."' AND scu.course_code = '".$row->code."';";
			$result_users = Database::query($sql);
			$time_spent = 0;
			$progress = 0;
			$nb_progress_lp = 0;
			$score = 0;
			$nb_score_lp = 0;
			$nb_messages = 0;
			$nb_assignments = 0;
			$last_login_date = false;
			$total_score_obtained = 0;
			$total_score_possible = 0;
			$total_questions_answered = 0;
			while($row_user = Database::fetch_object($result_users)) {
				// get time spent in the course and session
				$time_spent += Tracking::get_time_spent_on_the_course($row_user->user_id, $row->code, $session_id);
				$progress_tmp = Tracking::get_avg_student_progress($row_user->user_id, $row->code, array(), $session_id, true);
				$progress += $progress_tmp[0];
				$nb_progress_lp += $progress_tmp[1];
				$score_tmp = Tracking :: get_avg_student_score($row_user->user_id, $row->code, array(), $session_id, true);
				if(is_array($score_tmp)) {
					$score += $score_tmp[0];
					$nb_score_lp += $score_tmp[1];
				}
				$nb_messages += Tracking::count_student_messages($row_user->user_id, $row->code, $session_id);
				$nb_assignments += Tracking::count_student_assignments($row_user->user_id, $row->code, $session_id);

				$last_login_date_tmp = Tracking :: get_last_connection_date_on_the_course ($row_user->user_id, $row->code, $session_id, false);
				if($last_login_date_tmp != false && $last_login_date == false) { // TODO: To be cleaned.
					$last_login_date = $last_login_date_tmp;
				} else if($last_login_date_tmp != false && $last_login_date != false) { // TODO: Repeated previous condition! To be cleaned.
					// Find the max and assign it to first_login_date
					if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
						$last_login_date = $last_login_date_tmp;
					}
				}

				$exercise_results_tmp = MySpace::exercises_results($row_user->user_id, $row->code, $session_id);
				$total_score_obtained += $exercise_results_tmp['score_obtained'];
				$total_score_possible += $exercise_results_tmp['score_possible'];
				$total_questions_answered += $exercise_results_tmp['questions_answered'];
			}
			if($nb_progress_lp > 0) {
				$avg_progress = round($progress / $nb_progress_lp, 2);
			} else {
				$avg_progress = 0;
			}
			if($nb_score_lp > 0) {
				$avg_score = round($score / $nb_score_lp, 2);
			} else {
				$avg_score = '-';
			}
			if($last_login_date) {
				$last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
			} else {
				$last_login_date = '-';
			}
			if($total_score_possible > 0) {
				$total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
			} else {
				$total_score_percentage = 0;
			}
			if($total_score_percentage > 0) {
				$total_score = $total_score_obtained.'/'.$total_score_possible.' ('.$total_score_percentage.' %)';
			} else {
				$total_score = '-';
			}
			// time spent in the course
			$return .= '	<td><div>'.api_time_to_hms($time_spent).'</div></td>';
			// student progress in course
			$return .= '	<td><div>'.$avg_progress.'</div></td>';
			// student score
			$return .= '	<td><div>'.$avg_score.'</div></td>';
			// student messages
			$return .= '	<td><div>'.$nb_messages.'</div></td>';
			// student assignments
			$return .= '	<td><div>'.$nb_assignments.'</div></td>';
			// student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
			$return .= '<td width="105px;">'.$total_score.'</td>';
			$return .= '<td>'.$total_questions_answered.'</td>';
			// last connection
			$return .= '	<td><div>'.$last_login_date.'</div></td>';
			$return .= '<tr>';
		}
		$return .= '</table>';
		return $return;
	}

	/**
	 * This function exports the table that we see in display_tracking_session_overview()
	 *
	 */
	function export_tracking_session_overview() {
		// database table definition
		$tbl_session_rel_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_session_rel_course_rel_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

		// the values of the sortable table
		if ($_GET['tracking_session_overview_page_nr']) {
			$from = $_GET['tracking_session_overview_page_nr'];
		} else {
			$from = 0;
		}
		if ($_GET['tracking_session_overview_column']) {
			$orderby = $_GET['tracking_session_overview_column'];
		} else {
			$orderby = 0;
		}

		if ($_GET['tracking_session_overview_direction']) {
			$direction = $_GET['tracking_session_overview_direction'];
		} else {
			$direction = 'ASC';
		}

		$session_data = MySpace::get_session_data_tracking_overview($from, 1000, $orderby, $direction);

		$csv_content = array();

		// the first line of the csv file with the column headers
		$csv_row = array();
		$csv_row[] = get_lang('Session');
		$csv_row[] = get_lang('Course', '');
		$csv_row[] = get_lang('AvgTimeSpentInTheCourse', '');
		$csv_row[] = get_lang('AvgStudentsProgress', '');
		$csv_row[] = get_lang('AvgCourseScore', '');
		$csv_row[] = get_lang('TotalNumberOfMessages', '');
		$csv_row[] = get_lang('TotalNumberOfAssignments', '');
		$csv_row[] = get_lang('TotalExercisesScoreObtained', '');
		$csv_row[] = get_lang('TotalExercisesScorePossible', '');
		$csv_row[] = get_lang('TotalExercisesAnswered', '');
		$csv_row[] = get_lang('TotalExercisesScorePercentage', '');
		$csv_row[] = get_lang('LatestLogin', '');
		$csv_content[] = $csv_row;

		// the other lines (the data)
		foreach ($session_data as $key => $session) {
			$session_id = $session[0];
			$session_title = $session[1];

			// getting all the courses of the session
			$sql = "SELECT * FROM $tbl_course AS c INNER JOIN $tbl_session_rel_course AS sc ON sc.course_code = c.code WHERE sc.id_session = '".$session_id."';";
			$result = Database::query($sql);
			while ($row = Database::fetch_object($result)) {
				$csv_row = array();
				$csv_row[] = $session_title;
				$csv_row[] = $row->title;
				// get the users in the course
				$sql = "SELECT user_id FROM $tbl_user AS u INNER JOIN $tbl_session_rel_course_rel_user AS scu ON u.user_id = scu.id_user WHERE scu.id_session = '".$session_id."' AND scu.course_code = '".$row->code."';";
				$result_users = Database::query($sql);
				$time_spent = 0;
				$progress = 0;
				$nb_progress_lp = 0;
				$score = 0;
				$nb_score_lp = 0;
				$nb_messages = 0;
				$nb_assignments = 0;
				$last_login_date = false;
				$total_score_obtained = 0;
				$total_score_possible = 0;
				$total_questions_answered = 0;
				while($row_user = Database::fetch_object($result_users)) {
					// get time spent in the course and session
					$time_spent += Tracking::get_time_spent_on_the_course($row_user->user_id, $row->code, $session_id);
					$progress_tmp = Tracking::get_avg_student_progress($row_user->user_id, $row->code, array(), $session_id, true);
					$progress += $progress_tmp[0];
					$nb_progress_lp += $progress_tmp[1];
					$score_tmp = Tracking :: get_avg_student_score($row_user->user_id, $row->code, array(), $session_id, true);
					if(is_array($score_tmp)) {
						$score += $score_tmp[0];
						$nb_score_lp += $score_tmp[1];
					}
					$nb_messages += Tracking::count_student_messages($row_user->user_id, $row->code, $session_id);
					$nb_assignments += Tracking::count_student_assignments($row_user->user_id, $row->code, $session_id);

					$last_login_date_tmp = Tracking :: get_last_connection_date_on_the_course ($row_user->user_id, $row->code, $session_id, false);
					if($last_login_date_tmp != false && $last_login_date == false) { // TODO: To be cleaned.
						$last_login_date = $last_login_date_tmp;
					} else if($last_login_date_tmp != false && $last_login_date == false) { // TODO: Repeated previous condition. To be cleaned.
						// Find the max and assign it to first_login_date
						if(strtotime($last_login_date_tmp) > strtotime($last_login_date)) {
							$last_login_date = $last_login_date_tmp;
						}
					}

					$exercise_results_tmp = MySpace::exercises_results($row_user->user_id, $row->code, $session_id);
					$total_score_obtained += $exercise_results_tmp['score_obtained'];
					$total_score_possible += $exercise_results_tmp['score_possible'];
					$total_questions_answered += $exercise_results_tmp['questions_answered'];
				}
				if($nb_progress_lp > 0) {
					$avg_progress = round($progress / $nb_progress_lp, 2);
				} else {
					$avg_progress = 0;
				}
				if($nb_score_lp > 0) {
					$avg_score = round($score / $nb_score_lp, 2);
				} else {
					$avg_score = '-';
				}
				if($last_login_date) {
					$last_login_date = api_convert_and_format_date($last_login_date, DATE_FORMAT_SHORT, date_default_timezone_get());
				} else {
					$last_login_date = '-';
				}
				if($total_score_possible > 0) {
					$total_score_percentage = round($total_score_obtained / $total_score_possible * 100, 2);
				} else {
					$total_score_percentage = 0;
				}
				if($total_score_percentage > 0) {
					$total_score = $total_score_obtained.'/'.$total_score_possible.' ('.$total_score_percentage.' %)';
				} else {
					$total_score = '-';
				}
				// time spent in the course
				$csv_row[] = api_time_to_hms($time_spent);
				// student progress in course
				$csv_row[] = $avg_progress;
				// student score
				$csv_row[] = $avg_score;
				// student messages
				$csv_row[] = $nb_messages;
				// student assignments
				$csv_row[] = $nb_assignments;
				// student exercises results (obtained score, maximum score, number of exercises answered, score percentage)
				$csv_row[] = $total_score_obtained;
				$csv_row[] = $total_score_possible;
				$csv_row[] = $total_questions_answered;
				$csv_row[] = $total_score_percentage;
				// last connection
				$csv_row[] = $last_login_date;
				$csv_content[] = $csv_row;
			}
		}
		Export :: export_table_csv($csv_content, 'reporting_session_overview');
		exit;
	}


	/**
	 * Get general information about the exercise performance of the user
	 * the total obtained score (all the score on all the questions)
	 * the maximum score that could be obtained
	 * the number of questions answered
	 * the success percentage
	 * @param integer $user_id the id of the user
	 * @param string $course_code the course code
	 * @return array
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @version Dokeos 1.8.6
	 * @since November 2008
	 */
	function exercises_results($user_id, $course_code, $session_id = false) {
		$questions_answered = 0;
		$sql = 'SELECT exe_result , exe_weighting
			FROM '.Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES)."
			WHERE exe_cours_id = '".Database::escape_string($course_code)."'
			AND exe_user_id = '".Database::escape_string($user_id)."'";
		if($session_id !== false) {
			$sql .= " AND session_id = '".$session_id."' ";
		}
		$result = Database::query($sql);
		$score_obtained = 0;
		$score_possible = 0;
		$questions_answered = 0;
		while ($row = Database::fetch_array($result)) {
			$score_obtained += $row['exe_result'];
			$score_possible += $row['exe_weighting'];
			$questions_answered ++;
		}

		if ($score_possible != 0) {
			$percentage = round(($score_obtained / $score_possible * 100), 2);
		} else {
			$percentage = null;
		}

		return array('score_obtained' => $score_obtained, 'score_possible' => $score_possible, 'questions_answered' => $questions_answered, 'percentage' => $percentage);
	}

	/**
	 * This function exports the table that we see in display_tracking_user_overview()
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @version Dokeos 1.8.6
	 * @since October 2008
	 */
	function export_tracking_user_overview() {


		// database table definitions
		$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

		$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
		$sort_by_first_name = api_sort_by_first_name();

		// the values of the sortable table
		if ($_GET['tracking_user_overview_page_nr']) {
			$from = $_GET['tracking_user_overview_page_nr'];
		} else {
			$from = 0;
		}
		if ($_GET['tracking_user_overview_column']) {
			$orderby = $_GET['tracking_user_overview_column'];
		} else {
			$orderby = 0;
		}
		if ($is_western_name_order != api_is_western_name_order() && ($orderby == 1 || $orderby == 2)) {
			// Swapping the sorting column if name order for export is different than the common name order.
			$orderby = 3 - $orderby;
		}
		if ($_GET['tracking_user_overview_direction']) {
			$direction = $_GET['tracking_user_overview_direction'];
		} else {
			$direction = 'ASC';
		}

		$user_data = MySpace::get_user_data_tracking_overview($from, 1000, $orderby, $direction);

		// the first line of the csv file with the column headers
		$csv_row = array();
		$csv_row[] = get_lang('OfficialCode');
		if ($is_western_name_order) {
			$csv_row[] = get_lang('FirstName', '');
			$csv_row[] = get_lang('LastName', '');
		} else {
			$csv_row[] = get_lang('LastName', '');
			$csv_row[] = get_lang('FirstName', '');
		}
		$csv_row[] = get_lang('LoginName');
		$csv_row[] = get_lang('CourseCode');
		// the additional user defined fields (only those that were selected to be exported)

		$fields = UserManager::get_extra_fields(0, 50, 5, 'ASC');

		if (is_array($_SESSION['additional_export_fields'])) {
			foreach ($_SESSION['additional_export_fields'] as $key => $extra_field_export) {
				$csv_row[] = $fields[$extra_field_export][3];
				$field_names_to_be_exported[] = 'extra_'.$fields[$extra_field_export][1];
			}
		}
		$csv_row[] = get_lang('AvgTimeSpentInTheCourse', '');
		$csv_row[] = get_lang('AvgStudentsProgress', '');
		$csv_row[] = get_lang('AvgCourseScore', '');
		$csv_row[] = get_lang('AvgExercisesScore', '');
		$csv_row[] = get_lang('AvgMessages', '');
		$csv_row[] = get_lang('AvgAssignments', '');
		$csv_row[] = get_lang('TotalExercisesScoreObtained', '');
		$csv_row[] = get_lang('TotalExercisesScorePossible', '');
		$csv_row[] = get_lang('TotalExercisesAnswered', '');
		$csv_row[] = get_lang('TotalExercisesScorePercentage', '');
		$csv_row[] = get_lang('FirstLogin', '');
		$csv_row[] = get_lang('LatestLogin', '');
		$csv_content[] = $csv_row;

		// the other lines (the data)
		foreach ($user_data as $key => $user) {
			// getting all the courses of the user
			$sql = "SELECT * FROM $tbl_course_user WHERE user_id = '".Database::escape_string($user[4])."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
			$result = Database::query($sql);
			while ($row = Database::fetch_row($result)) {
				$csv_row = array();
				// user official code
				$csv_row[] = $user[0];
				// user first|last name
				$csv_row[] = $user[1];
				// user last|first name
				$csv_row[] = $user[2];
				// user login name
				$csv_row[] = $user[3];
				// course code
				$csv_row[] = $row[0];
				// the additional defined user fields
				$extra_fields = MySpace::get_user_overview_export_extra_fields($user[4]);

				if (is_array($field_names_to_be_exported)) {
					foreach ($field_names_to_be_exported as $key => $extra_field_export) {
						$csv_row[] = $extra_fields[$extra_field_export];
					}
				}
				// time spent in the course
				$csv_row[] = api_time_to_hms(Tracking :: get_time_spent_on_the_course ($user[4], $row[0]));
				// student progress in course
				$csv_row[] = round(Tracking :: get_avg_student_progress ($user[4], $row[0]), 2);
				// student score
				$csv_row[] = round(Tracking :: get_avg_student_score ($user[4], $row[0]), 2);
				// student tes score
				$csv_row[] = round(Tracking :: get_avg_student_exercise_score ($user[4], $row[0]), 2);
				// student messages
				$csv_row[] = Tracking :: count_student_messages ($user[4], $row[0]);
				// student assignments
				$csv_row[] = Tracking :: count_student_assignments ($user[4], $row[0]);
				// student exercises results
				$exercises_results = MySpace::exercises_results($user[4], $row[0]);
				$csv_row[] = $exercises_results['score_obtained'];
				$csv_row[] = $exercises_results['score_possible'];
				$csv_row[] = $exercises_results['questions_answered'];
				$csv_row[] = $exercises_results['percentage'];
				// first connection
				$csv_row[] = Tracking :: get_first_connection_date_on_the_course ($user[4], $row[0]);
				// last connection
				$csv_row[] = strip_tags(Tracking :: get_last_connection_date_on_the_course ($user[4], $row[0]));

				$csv_content[] = $csv_row;
			}
		}
		Export :: export_table_csv($csv_content, 'reporting_user_overview');
		exit;
	}

	/**
	 * Get data for courses list in sortable with pagination
	 * @return array
	 */
	static function get_course_data($from, $number_of_items, $column, $direction) {
		global $courses, $csv_content, $charset, $session_id;

		// definition database tables
		$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$course_data = array();
		$courses_code = array_keys($courses);

		foreach ($courses_code as &$code) {
			$code = "'$code'";
		}

		// get all courses with limit
		$sql = "SELECT course.code as col1, course.title as col2
				FROM $tbl_course course
				WHERE course.code IN (".implode(',',$courses_code).")";
		
		if (!in_array($direction, array('ASC','DESC'))) $direction = 'ASC';

	    $column = intval($column);
	    $from = intval($from);
	    $number_of_items = intval($number_of_items);
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";

		$res = Database::query($sql);
		while ($row_course = Database::fetch_row($res)) {
            
			$course_code = $row_course[0];
			$course_info = api_get_course_info($course_code);
			$avg_assignments_in_course = $avg_messages_in_course = $nb_students_in_course = $avg_progress_in_course = $avg_score_in_course = $avg_time_spent_in_course = $avg_score_in_exercise = 0;

			// students directly subscribed to the course
			if (empty($session_id)) {
				$sql = "SELECT user_id FROM $tbl_course_user as course_rel_user WHERE course_rel_user.status='5' AND course_rel_user.course_code='$course_code'";
			} else {
				$sql = "SELECT id_user as user_id FROM $tbl_session_course_user srcu WHERE  srcu. course_code='$course_code' AND id_session = '$session_id' AND srcu.status<>2";
			}
			$rs = Database::query($sql);
			$users = array();
			while ($row = Database::fetch_array($rs)) { $users[] = $row['user_id']; }

			if (count($users) > 0) {
				$nb_students_in_course = count($users);
				$avg_assignments_in_course  = Tracking::count_student_assignments($users, $course_code, $session_id);
				$avg_messages_in_course     = Tracking::count_student_messages($users, $course_code, $session_id);
				$avg_progress_in_course     = Tracking::get_avg_student_progress($users, $course_code, array(), $session_id);                
				$avg_score_in_course        = Tracking::get_avg_student_score($users, $course_code, array(), $session_id);
				$avg_score_in_exercise      = Tracking::get_avg_student_exercise_score($users, $course_code, 0, $session_id);				                
				$avg_time_spent_in_course   = Tracking::get_time_spent_on_the_course($users, $course_code, $session_id);

				$avg_progress_in_course = round($avg_progress_in_course / $nb_students_in_course, 2);
				if (is_numeric($avg_score_in_course)) {
					$avg_score_in_course = round($avg_score_in_course / $nb_students_in_course, 2);
				}
				$avg_time_spent_in_course = api_time_to_hms($avg_time_spent_in_course / $nb_students_in_course);

			} else {
				$avg_time_spent_in_course = null;
				$avg_progress_in_course = null;
				$avg_score_in_course = null;
				$avg_score_in_exercise = null;
				$avg_messages_in_course = null;
				$avg_assignments_in_course = null;
			}
			$table_row = array();
			$table_row[] = $row_course[1];
			$table_row[] = $nb_students_in_course;
			$table_row[] = $avg_time_spent_in_course;
			$table_row[] = is_null($avg_progress_in_course) ? '' : $avg_progress_in_course.'%';
			$table_row[] = is_null($avg_score_in_course) ? '' : $avg_score_in_course.'%';
			$table_row[] = is_null($avg_score_in_exercise) ? '' : $avg_score_in_exercise.'%';
			$table_row[] = $avg_messages_in_course;
			$table_row[] = $avg_assignments_in_course;

			//set the "from" value to know if I access the Reporting by the chamilo tab or the course link
			$table_row[] = '<center><a href="../tracking/courseLog.php?cidReq='.$course_code.'&studentlist=true&from=myspace&id_session='.$session_id.'">
			                 <img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>
			                </center>';
			$csv_content[] = array(
				api_html_entity_decode($row_course[1], ENT_QUOTES, $charset),
				$nb_students_in_course,
				$avg_time_spent_in_course,
				is_null($avg_progress_in_course) ? null : $avg_progress_in_course.'%',
				is_null($avg_score_in_course) ? null : is_numeric($avg_score_in_course) ? $avg_score_in_course.'%' : $avg_score_in_course ,
				is_null($avg_score_in_exercise) ? null : $avg_score_in_exercise.'%',
				$avg_messages_in_course,
				$avg_assignments_in_course,
			);
			$course_data[] = $table_row;
		}
		return $course_data;
	}

	/**
	 * get the numer of users of the platform
	 *
	 * @return integer
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @version Dokeos 1.8.6
	 * @since October 2008
	 */
	function get_number_of_users_tracking_overview() {
		// database table definition
		$main_user_table = Database :: get_main_table(TABLE_MAIN_USER);
		return Database::count_rows($main_user_table);
	}

	/**
	 * get all the data for the sortable table of the reporting progress of all users and all the courses the user is subscribed to.
	 *
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @version Dokeos 1.8.6
	 * @since October 2008
	 */
	function get_user_data_tracking_overview($from, $number_of_items, $column, $direction) {
		global $_configuration;
		// database table definition
		$access_url_id = api_get_current_access_url_id();
	 	$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$main_user_table = Database::get_main_table(TABLE_MAIN_USER);

		if ($_configuration['multiple_access_urls']) {
			$condition_multi_url = ", $tbl_url_rel_user as url_user WHERE user.user_id=url_user.user_id AND access_url_id='$access_url_id'";
		}

		global $export_csv;
		if ($export_csv) {
			$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
		} else {
			$is_western_name_order = api_is_western_name_order();
		}
		$sql = "SELECT
					official_code 	AS col0,
					".($is_western_name_order ? "
					firstname 		AS col1,
					lastname 		AS col2,
					" : "
					lastname 		AS col1,
					firstname 		AS col2,
					").
					"username		AS col3,
					user.user_id 		AS col4
				FROM
					$main_user_table as user $condition_multi_url
				";
		$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";
		$result = Database::query($sql);
		$return = array ();
		while ($user = Database::fetch_row($result)) {
			$return[] = $user;
		}
		return $return;
	}

	/**
	 * Get all information that the user with user_id = $user_data has
	 * entered in the additionally defined profile fields
	 * @param integer $user_id the id of the user
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	 * @version Dokeos 1.8.6
	 * @since November 2008
	 */
	function get_user_overview_export_extra_fields($user_id) {
		// include the user manager

		$extra_data = UserManager::get_extra_user_data($user_id, true);
		return $extra_data;
	}
	/**
	 * Checks if a username exist in the DB otherwise it create a "double"
	 * i.e. if we look into for jmontoya but the user's name already exist we create the user jmontoya2
	 * the return array will be array(username=>'jmontoya', sufix='2')
	 * @param string firstname
	 * @param string lastname
	 * @param string username
	 * @return array with the username, the sufix
	 * @author Julio Montoya Armas
	 */
	function make_username($firstname, $lastname, $username, $language = null, $encoding = null) {
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		// if username exist
		if (!UserManager::is_username_available($username) || empty($username)) {
			$i = 0;
			while (1) {
				if ($i == 0) {
					$sufix = '';
				} else {
					$sufix = $i;
				}
				$desired_username = UserManager::create_username($firstname, $lastname, $language, $encoding);
				if (UserManager::is_username_available($desired_username.$sufix)) {
					break;
				} else {
					$i++;
				}
			}
			$username_array = array('username' => $desired_username , 'sufix' => $sufix);
			return $username_array;
		} else {
			$username_array = array('username' => $username, 'sufix' => '');
			return $username_array;
		}
	}

	/**
	 * Checks if there are repeted users in a given array
	 * @param  array $usernames list of the usernames in the uploaded file
	 * @param  array $user_array['username'] and $user_array['sufix'] where sufix is the number part in a login i.e -> jmontoya2
	 * @return array with the $usernames array and the $user_array array
	 * @author Julio Montoya Armas
	 */
	function check_user_in_array($usernames, $user_array) {
		$user_list = array_keys($usernames);
		$username = $user_array['username'].$user_array['sufix'];

		if (in_array($username, $user_list)) {
			$user_array['sufix'] += $usernames[$username];
			$usernames[$username]++;
		} else {
			$usernames[$username] = 1;
		}
		$result_array = array($usernames, $user_array);
		return $result_array;
	}

	/**
	 * Checks whether a username has been already subscribed in a session.
	 * @param string a given username
	 * @param array  the array with the course list codes
	 * @param the session id
	 * @return 0 if the user is not subscribed  otherwise it returns the user_id of the given username
	 * @author Julio Montoya Armas
	 */
	function user_available_in_session($username, $course_list, $id_session) {
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$id_session = intval($id_session);
		$username = Database::escape_string($username);
		foreach($course_list as $enreg_course) {
			$sql_select = "	SELECT u.user_id FROM $tbl_session_rel_course_rel_user rel INNER JOIN $table_user u
						   	ON (rel.id_user=u.user_id)
							WHERE rel.id_session='$id_session' AND u.status='5' AND u.username ='$username' AND rel.course_code='$enreg_course'";
			$rs = Database::query($sql_select);
			if (Database::num_rows($rs) > 0) {
				return Database::result($rs, 0, 0);
			} else {
				return 0;
			}
		}
	}

	/**
	This function checks whether some users in the uploaded file repeated and creates unique usernames if necesary.
	A case: Within the file there is an user repeted twice (Julio Montoya / Julio Montoya) and the username fields are empty.
	Then, this function would create unique usernames based on the first and the last name. Two users wiould be created - jmontoya and jmontoya2.
	Of course, if in the database there is a user with the name jmontoya, the newly created two users registered would be jmontoya2 and jmontoya3.
	@param $users list of users
	@author Julio Montoya Armas
	*/
	function check_all_usernames($users, $course_list, $id_session) {
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$usernames = array();
		$new_users = array();
		foreach ($users as $index => $user) {
			$desired_username = array();
			if (empty($user['UserName'])) {
				$desired_username = MySpace::make_username($user['FirstName'], $user['LastName'], '');
				$pre_username = $desired_username['username'].$desired_username['sufix'];
				$user['UserName'] = $pre_username;
				$user['create'] = '1';
			} else {
				if (UserManager::is_username_available($user['UserName'])) {
					$desired_username = MySpace::make_username($user['FirstName'], $user['LastName'], $user['UserName']);
					$user['UserName'] = $desired_username['username'].$desired_username['sufix'];
					$user['create'] = '1';
				} else {
					$is_session_avail = MySpace::user_available_in_session($user['UserName'], $course_list, $id_session);
					if ($is_session_avail == 0) {
						$user_name = $user['UserName'];
						$sql_select = "SELECT user_id FROM $table_user WHERE username ='$user_name' ";
						$rs = Database::query($sql_select);
						$user['create'] = Database::result($rs, 0, 0); // This should be the ID because the user exists.
					} else {
						$user['create'] = $is_session_avail;
					}
				}
			}
			// Usernames is the current list of users in the file.
			$result_array = MySpace::check_user_in_array($usernames, $desired_username);
			$usernames = $result_array[0];
			$desired_username = $result_array[1];
			$user['UserName'] = $desired_username['username'].$desired_username['sufix'];
			$new_users[] = $user;
		}
		return $new_users;
	}

	/**
	 * This functions checks whether there are users that are already registered in the DB by different creator than the current coach.
	 * @param string a given username
	 * @param array  the array with the course list codes
	 * @param the session id
	 * @author Julio Montoya Armas
	 */
	function get_user_creator($users, $course_list, $id_session) {
		$errors = array();
		foreach ($users as $index => $user) {
			// database table definition
			$table_user = Database::get_main_table(TABLE_MAIN_USER);
			$tbl_session_rel_course_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$username = Database::escape_string($user['UserName']);
			//echo "<br>";
			$sql = "SELECT creator_id FROM $table_user WHERE username='$username' ";

			$rs = Database::query($sql);
			$creator_id = Database::result($rs, 0, 0);
			// check if we are the creators or not
			if ($creator_id != '') {
				if ($creator_id != api_get_user_id()) {
					$user['error'] = get_lang('UserAlreadyRegisteredByOtherCreator');
					$errors[] = $user;
				}
			}
		}
		return $errors;
	}

	/**
	 * Validates imported data.
	 * @param list of users
	 */
	function validate_data($users, $id_session = null) {
		$errors = array();
		$usernames = array();
		$new_users = array();
		foreach ($users as $index => $user) {
			// 1. Check whether mandatory fields are set.
			$mandatory_fields = array('LastName', 'FirstName');
			if (api_get_setting('registration', 'email') == 'true') {
				$mandatory_fields[] = 'Email';
			}

			foreach ($mandatory_fields as $key => $field) {
				if (!isset ($user[$field]) || strlen($user[$field]) == 0) {
					$user['error'] = get_lang($field.'Mandatory');
					$errors[] = $user;
				}
			}
			// 2. Check whether the username is too long.
			if (UserManager::is_username_too_long($user['UserName'])) {
				$user['error'] = get_lang('UserNameTooLong');
				$errors[] = $user;
			}

			$user['UserName'] = trim($user['UserName']);

			if (empty($user['UserName'])) {
				 $user['UserName'] = UserManager::create_username($user['FirstName'], $user['LastName']);
			}
			$new_users[] = $user;
		}
		$results = array('errors' => $errors, 'users' => $new_users);
		return $results;
	}

	/**
	 * Adds missing user-information (which isn't required, like password, etc).
	 */
	function complete_missing_data($user) {
		// 1. Generate a password if it is necessary.
		if (!isset ($user['Password']) || strlen($user['Password']) == 0) {
			$user['Password'] = api_generate_password();
		}
		return $user;
	}

	/**
	 * Saves imported data.
	 */
	function save_data($users, $course_list, $id_session) {
		$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);

		$id_session = intval($id_session);
		$sendMail = $_POST['sendMail'] ? 1 : 0;

		// Adding users to the platform.
		$new_users = array();
		foreach ($users as $index => $user) {
			$user = MySpace::complete_missing_data($user);
			// coach only will registered users
			$default_status = '5';
			if ($user['create'] == '1') {
				$user['id'] = UserManager :: create_user($user['FirstName'], $user['LastName'], $default_status, $user['Email'], $user['UserName'], $user['Password'], $user['OfficialCode'], api_get_setting('PlatformLanguage'), $user['PhoneNumber'], '');
				$user['added_at_platform'] = 1;
			} else {
				$user['id'] = $user['create'];
				$user['added_at_platform'] = 0;
			}
			$new_users[] = $user;
		}
		// Update user list.
		$users = $new_users;

		// Inserting users.
		$super_list = array();
		foreach ($course_list as $enreg_course) {
			$nbr_users = 0;
			$new_users = array();
			$enreg_course = Database::escape_string($enreg_course);
			foreach ($users as $index => $user) {
				$userid = intval($user['id']);
				$sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$userid')";
				$course_session = array('course' => $enreg_course, 'added' => 1);
				//$user['added_at_session'] = $course_session;
				Database::query($sql);
				if (Database::affected_rows()) {
					$nbr_users++;
				}
				$new_users[] = $user;
			}
			$super_list[] = $new_users;

			//update the nbr_users field
			$sql_select = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
			$rs = Database::query($sql_select);
			list($nbr_users) = Database::fetch_array($rs);
			$sql_update = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
			Database::query($sql_update);

			$sql_update = "UPDATE $tbl_session SET nbr_users= '$nbr_users' WHERE id='$id_session'";
			Database::query($sql_update);
		}
		// We don't delete the users (thoughts while dreaming)
		//$sql_delete = "DELETE FROM $tbl_session_rel_user WHERE id_session = '$id_session'";
		//Database::query($sql_delete);

		$new_users = array();
		foreach ($users as $index => $user) {
			$userid = $user['id'];
			$sql_insert = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$userid')";
			Database::query($sql_insert);
			$user['added_at_session'] = 1;
			$new_users[] = $user;
		}

		$users = $new_users;
		$registered_users = get_lang('FileImported').'<br /> Import file results : <br />';
		// Sending emails.
		$addedto = '';
		if ($sendMail) {
			$i = 0;
			foreach ($users as $index => $user) {
				$emailto = api_get_person_name($user['FirstName'], $user['LastName'], null, PERSON_NAME_EMAIL_ADDRESS).' <'.$user['Email'].'>';
				$emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
				$emailbody = get_lang('Dear').' '.api_get_person_name($user['FirstName'], $user['LastName']).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName')." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : $user[UserName]\n".get_lang('Pass')." : $user[Password]\n\n".get_lang('Address')." ".api_get_setting('siteName')." ".get_lang('Is')." : ".api_get_path(WEB_PATH)." \n\n".get_lang('Problem')."\n\n".get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".get_lang('Manager')." ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n".get_lang('Email')." : ".api_get_setting('emailAdministrator')."";
				$emailheaders = 'From: '.api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS).' <'.api_get_setting('emailAdministrator').">\n";
				$emailheaders .= 'Reply-To: '.api_get_setting('emailAdministrator');
				@api_send_mail($emailto, $emailsubject, $emailbody, $emailheaders);

				if (($user['added_at_platform'] == 1  && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
					if ($user['added_at_platform'] == 1) {
						$addedto = get_lang('UserCreatedPlatform');
					} else  {
						$addedto = '          ';
					}

					if ($user['added_at_session'] == 1) {
						$addedto .= get_lang('UserInSession');
					}
					$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
				} else {
					$addedto = get_lang('UserNotAdded');
					$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
				}
			}
		} else {
			$i = 0;
			foreach ($users as $index => $user) {
				if (($user['added_at_platform'] == 1 && $user['added_at_session'] == 1) || $user['added_at_session'] == 1) {
					if ($user['added_at_platform'] == 1) {
						$addedto = get_lang('UserCreatedPlatform');
					} else {
						$addedto = '          ';
					}

					if ($user['added_at_session'] == 1) {
						$addedto .= ' '.get_lang('UserInSession');
					}

					$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
				} else {
					$addedto = get_lang('UserNotAdded');
					$registered_users .= "<a href=\"../user/userInfo.php?uInfo=".$user['id']."\">".api_get_person_name($user['FirstName'], $user['LastName'])."</a> - ".$addedto.'<br />';
				}
			}
		}

		header('Location: course.php?id_session='.$id_session.'&action=show_message&message='.urlencode($registered_users));
		exit ();

		//header('Location: resume_session.php?id_session='.$id_session);
	}

	/**
	 * Reads CSV-file.
	 * @param string $file Path to the CSV-file
	 * @return array All userinformation read from the file
	 */
	function parse_csv_data($file) {
		$users = Import :: csv_to_array($file);
		foreach ($users as $index => $user) {
			if (isset ($user['Courses'])) {
				$user['Courses'] = explode('|', trim($user['Courses']));
			}
			$users[$index] = $user;
		}
		return $users;
	}

	/**
	 * XML-parser: the handler at the beginning of element.
	 */
	function element_start($parser, $data) {
		$data = api_utf8_decode($data);
		global $user;
		global $current_tag;
		switch ($data) {
			case 'Contact' :
				$user = array ();
				break;
			default :
				$current_tag = $data;
		}
	}

	/**
	 * XML-parser: the handler at the end of element.
	 */
	function element_end($parser, $data) {
		$data = api_utf8_decode($data);
		global $user;
		global $users;
		global $current_value;
		global $purification_option_for_usernames;
		$user[$data] = $current_value;
		switch ($data) {
			case 'Contact' :
				$user['UserName'] = UserManager::purify_username($user['UserName'], $purification_option_for_usernames);
				$users[] = $user;
				break;
			default :
				$user[$data] = $current_value;
				break;
		}
	}

	/**
	 * XML-parser: the handler for character data.
	 */
	function character_data($parser, $data) {
		$data = trim(api_utf8_decode($data));
		global $current_value;
		$current_value = $data;
	}

	/**
	 * Reads XML-file.
	 * @param string $file Path to the XML-file
	 * @return array All userinformation read from the file
	 */
	function parse_xml_data($file) {
		global $current_tag;
		global $current_value;
		global $user;
		global $users;
		$users = array ();
		$parser = xml_parser_create('UTF-8');
		xml_set_element_handler($parser, array('MySpace','element_start'), array('MySpace','element_end'));
		xml_set_character_data_handler($parser, "character_data");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
		xml_parse($parser, api_utf8_encode_xml(file_get_contents($file)));
		xml_parser_free($parser);
		return $users;
	}	
}

    
function get_stats($user_id, $course_code, $start_date = null, $end_date = null) {
    // Database table definitions
    $tbl_track_course   = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
    $tbl_main           = Database :: get_main_table(TABLE_MAIN_COURSE);

    $course_info = api_get_course_info($course_code);
    if (!empty($course_info)) {        
        $strg_sd    = "";
        $strg_ed    = "";        
        if ($start_date != null && $end_date != null){
            $end_date = add_day_to( $end_date );
            $strg_sd = "AND login_course_date BETWEEN '$start_date' AND '$end_date'";
            $strg_ed = "AND logout_course_date BETWEEN '$start_date' AND '$end_date'";
        }    
        $sql = 'SELECT SEC_TO_TIME(avg(time_to_sec(timediff(logout_course_date,login_course_date)))) as avrg,
            SEC_TO_TIME(sum(time_to_sec(timediff(logout_course_date,login_course_date)))) as total,
            count(user_id) as times
            FROM ' . $tbl_track_course . '
            WHERE user_id = ' . intval($user_id) . '
            AND course_code = "' . Database::escape_string($course_code) . '" '.$strg_sd.' '.$strg_ed.' '.'
            ORDER BY login_course_date ASC';
    
        $rs = Database::query($sql);
        $result = array();
    
        if ($row = Database::fetch_array($rs)) {    
            $foo_avg    = $row['avrg'];
            $foo_total  = $row['total'];
            $foo_times  = $row['times'];
            $result = array('avg' => $foo_avg, 'total' => $foo_total, 'times' => $foo_times);
        }
    }
    return $result;
}

function add_day_to($end_date) {
    $foo_date = strtotime( $end_date );
    $foo_date = strtotime(" +1 day", $foo_date);
    $foo_date = date("Y-m-d", $foo_date);
    return $foo_date;
}


/**
 * Gets the connections to a course as an array of login and logout time
 *
 * @param   int       $user_id
 * @param   string    $course_code
 * @author  Jorge Frisancho Jibaja
 * @author  Julio Montoya <gugli100@gmail.com> fixing the function
 * @version OCT-22- 2010
 * @return  array
 */
function get_connections_to_course_by_date($user_id, $course_code, $start_date, $end_date) {
    // Database table definitions
    $tbl_track_course   = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
    $tbl_main           = Database :: get_main_table(TABLE_MAIN_COURSE);
    
    $course_info = api_get_course_info($course_code);
    $user_id = intval($user_id);
    if (!empty($course_info)) {
        $end_date = add_day_to($end_date);
        $sql = "SELECT login_course_date, logout_course_date FROM $tbl_track_course 
            WHERE user_id = $user_id
            AND course_code = '$course_code' 
            AND login_course_date BETWEEN '$start_date' AND '$end_date'
            AND logout_course_date BETWEEN '$start_date' AND '$end_date'
            ORDER BY login_course_date ASC";    
        $rs = Database::query($sql);
        $connections = array();
    
        while ($row = Database::fetch_array($rs)) {    
            $login_date = $row['login_course_date'];
            $logout_date = $row['logout_course_date'];    
            $timestamp_login_date = strtotime($login_date);
            $timestamp_logout_date = strtotime($logout_date);    
            $connections[] = array('login' => $timestamp_login_date, 'logout' => $timestamp_logout_date);
        }
    }
    return $connections;
}

/**
 * 
 *
 * @param array     
 * @author Jorge Frisancho Jibaja
 * @version OCT-22- 2010
 * @return array
 */
function convert_to_array($sql_result){
    $result_to_print = '<table>';
    foreach ($sql_result as $key => $data) {
            $result_to_print .= '<tr><td>'.date('d-m-Y (H:i:s)', $data['login']).'</td><td>'.api_time_to_hms($data['logout'] - $data['login']).'</tr></td>'."\n";
    }
    $result_to_print .= '</table>';
    $result_to_print = array("result"=>$result_to_print);
    return $result_to_print;
}


/**
 * Converte an array to a table in html
 *
 * @param array $sql_result
 * @author Jorge Frisancho Jibaja
 * @version OCT-22- 2010
 * @return string
 */
function convert_to_string($sql_result){
    $result_to_print = '<table>';
    if (!empty($sql_result)) {
        foreach ($sql_result as $key => $data) {
                $result_to_print .= '<tr><td>'.date('d-m-Y (H:i:s)', $data['login']).'</td><td>'.api_time_to_hms($data['logout'] - $data['login']).'</tr></td>'."\n";
        }
    }
    $result_to_print .= '</table>';
    return $result_to_print;
}


/**
 * This function draw the graphic to be displayed on the user view as an image
 *
 * @param array $sql_result
 * @param string $start_date
 * @param string $end_date
 * @param string $type
 * @author Jorge Frisancho Jibaja
 * @version OCT-22- 2010
 * @return string
 */
function grapher($sql_result, $start_date, $end_date, $type = "") {
    require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
    require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
    require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';

    if (empty($start_date)) { $start_date =""; }
    if (empty($end_date)) { $end_date =""; }
    if ($type == ""){ $type = 'day'; }
    $main_year  = $main_month_year = $main_day = array();
    // get last 8 days/months
    $last_days      = 5;
    $last_months    = 3;
    for ($i = $last_days; $i >= 0; $i--) {
        $main_day[date ('d-m-Y', mktime () - $i * 3600 * 24)] = 0;
    }
    for ($i = $last_months; $i >= 0; $i--) {
        $main_month_year[date ('m-Y', mktime () - $i * 30 * 3600 * 24)] = 0;
    }

    $i = 0;
    if (is_array($sql_result) && count($sql_result) > 0) {
        foreach ($sql_result as $key => $data) {
            //creating the main array
            $main_month_year[date('m-Y', $data['login'])] += float_format(($data['logout'] - $data['login']) / 60, 0);
            $main_day[date('d-m-Y', $data['login'])] += float_format(($data['logout'] - $data['login']) / 60, 0);
            if ($i > 500) {
                    break;
            }
            $i++;
        }

        switch ($type) {
            case 'day':
                $main_date = $main_day;
                break;
            case 'month':
                $main_date = $main_month_year;
                break;
            case 'year':
                $main_date = $main_year;
                break;
        }

        // the nice graphics :D
        $labels = array_keys($main_date);
        if (count($main_date) == 1) {
            $labels = $labels[0];
            $main_date = $main_date[$labels];
        }

        $data_set = new pData();
        $data_set->AddPoint($main_date, 'Q');
        if (count($main_date)!= 1) {
            $data_set->AddPoint($labels, 'Date');
        }
        $data_set->AddAllSeries();
        $data_set->RemoveSerie('Date');
        $data_set->SetAbsciseLabelSerie('Date');
        $data_set->SetYAxisName(get_lang('Minutes', ''));
        $graph_id = api_get_user_id().'AccessDetails'.api_get_course_id().$start_date.$end_date.$type;
        $data_set->AddAllSeries();

        $cache = new pCache();
        // the graph id
        $data = $data_set->GetData();

        if ($cache->IsInCache($graph_id, $data_set->GetData())) {
        //if (0) {
            //if we already created the img
            //  echo 'in cache';
            $img_file = $cache->GetHash($graph_id, $data_set->GetData());
        } else {
            // if the image does not exist in the archive/ folder
            // Initialise the graph
            $test = new pChart(760, 230);

            //which schema of color will be used
            $quant_resources = count($data[0]) - 1;
            // Adding the color schemma
            $test->loadColorPalette(api_get_path(LIBRARY_PATH).'pchart/palette/default.txt');

            $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 8);
            $test->setGraphArea(70, 30, 680, 200);
            $test->drawFilledRoundedRectangle(7, 7, 693, 223, 5, 240, 240, 240);
            $test->drawRoundedRectangle(5, 5, 695, 225, 5, 230, 230, 230);
            $test->drawGraphArea(255, 255, 255, TRUE);
            $test->drawScale($data_set->GetData(), $data_set->GetDataDescription(), SCALE_START0, 150, 150, 150, TRUE, 0, 0);
            $test->drawGrid(4, TRUE, 230, 230, 230, 50);
            $test->setLineStyle(2);
            // Draw the 0 line
            $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 6);
            $test->drawTreshold(0, 143, 55, 72, TRUE, TRUE);

            if (count($main_date) == 1) {
                    //Draw a graph
                    echo '<strong>'.$labels.'</strong><br/>';
                    $test->drawBarGraph($data_set->GetData(), $data_set->GetDataDescription(), TRUE);
            } else {
                    //Draw the line graph
                    $test->drawLineGraph($data_set->GetData(), $data_set->GetDataDescription());
                    $test->drawPlotGraph($data_set->GetData(), $data_set->GetDataDescription(), 3, 2, 255, 255, 255);
            }

            // Finish the graph
            $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 8);
            $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 10);            
            $test->drawTitle(60, 22, get_lang('AccessDetails'), 50, 50, 50, 585);

            //------------------
            //echo 'not in cache';
            $cache->WriteToCache($graph_id, $data_set->GetData(), $test);
            ob_start();
            $test->Stroke();
            ob_end_clean();
            $img_file = $cache->GetHash($graph_id, $data_set->GetData());
        }
        $foo_img = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
        return $foo_img;
    } else {
        $foo_img = api_convert_encoding('<div id="messages" class="warning-message">'.get_lang('GraphicNotAvailable').'</div>','UTF-8');
        return $foo_img;
    }
}