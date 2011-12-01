<?php
/* For licensing terms, see /license.txt */
/**
 * ExerciseResult class: This class allows to instantiate an object 
 * of type ExerciseResult
 * which allows you to export exercises results in multiple presentation forms
 * @package chamilo.exercise
 * @author Yannick Warnier
 * @version $Id: $
 */
/**
 * Code
 */
if(!class_exists('ExerciseResult')):
/**
 * Exercise results class
 * @package chamilo.exercise
 */
class ExerciseResult
{
	private $exercises_list = array(); //stores the list of exercises
	private $results = array(); //stores the results

	/**
	 * constructor of the class
	 */
	public function ExerciseResult($get_questions=false,$get_answers=false)
	{
		//nothing to do
		/*
		$this->exercise_list = array();
		$this->readExercisesList();
		if($get_questions)
		{
			foreach($this->exercises_list as $exe)
			{
				$this->exercises_list['questions'] = $this->getExerciseQuestionList($exe['id']);
			}
		}
		*/
	}

	/**
	 * Reads exercises information (minimal) from the data base
	 * @param	boolean		Whether to get only visible exercises (true) or all of them (false). Defaults to false.
	 * @return	array		A list of exercises available
	 */
	private function _readExercisesList($only_visible = false)
	{
		$return = array();
    	$TBL_EXERCISES          = Database::get_course_table(TABLE_QUIZ_TEST);

		$sql="SELECT id,title,type,random,active FROM $TBL_EXERCISES";
		if($only_visible)
		{
			$sql.= ' WHERE active=1';
		}
		$sql .= ' ORDER BY title';
		$result=Database::query($sql);

		// if the exercise has been found
		while($row=Database::fetch_array($result,'ASSOC'))
		{
			$return[] = $row;
		}
		// exercise not found
		return $return;
	}

	/**
	 * Gets the questions related to one exercise
	 * @param	integer		Exercise ID
	 */
	private function _readExerciseQuestionsList($e_id)
	{
		$return = array();
    	$TBL_EXERCISE_QUESTION  = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    	$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
		$sql="SELECT q.id, q.question, q.ponderation, eq.question_order, q.type, q.picture " .
			" FROM $TBL_EXERCISE_QUESTION eq, $TBL_QUESTIONS q " .
			" WHERE eq.question_id=q.id AND eq.exercice_id='".Database::escape_string($e_id)."' " .
			" ORDER BY eq.question_order";
		$result=Database::query($sql);

		// fills the array with the question ID for this exercise
		// the key of the array is the question position
		while($row=Database::fetch_array($result,'ASSOC'))
		{
			$return[] = $row;
		}
		return true;
	}
	/**
	 * Gets the results of all students (or just one student if access is limited)
	 * @param	string		The document path (for HotPotatoes retrieval)
	 * @param	integer		User ID. Optional. If no user ID is provided, we take all the results. Defauts to null
	 */
	function _getExercisesReporting($document_path, $user_id = null, $filter=0, $exercise_id = 0, $hotpotato_name = null) {
		$return = array();
    	$TBL_EXERCISES          = Database::get_course_table(TABLE_QUIZ_TEST);
    	$TBL_EXERCISE_QUESTION  = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    	$TBL_QUESTIONS 			= Database::get_course_table(TABLE_QUIZ_QUESTION);
		$TBL_USER          	    = Database::get_main_table(TABLE_MAIN_USER);
		$TBL_DOCUMENT          	= Database::get_course_table(TABLE_DOCUMENT);
		$TBL_ITEM_PROPERTY      = Database::get_course_table(TABLE_ITEM_PROPERTY);
		$TBL_TRACK_EXERCISES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
		$TBL_TRACK_HOTPOTATOES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
		$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $TBL_TRACK_ATTEMPT_RECORDING= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
		$TBL_COURSE_REL_USER 	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        
        

    	$cid             = api_get_course_id();
        $course_id       = api_get_course_int_id();
    	$user_id         = intval($user_id);
    	$session_id_and  = ' AND te.session_id = ' . api_get_session_id() . ' ';
        $exercise_id     = intval($exercise_id);        
        $hotpotato_name  = Database::escape_string($hotpotato_name);
        
        if (!empty($exercise_id)) {
            $session_id_and .= " AND exe_exo_id = $exercise_id ";
        }
        
		if (empty($user_id)) {		
			$sql="SELECT ".(api_is_western_name_order() ? "firstname as userpart1, lastname userpart2" : "lastname as userpart1, firstname as userpart2").", ce.title as extitle, te.exe_result as exresult , te.exe_weighting as exweight,
                te.exe_date as exdate, te.exe_id as exid, email as exemail, te.start_date as exstart, steps_counter as exstep, exe_user_id as excruid, te.exe_duration as exduration
                FROM $TBL_EXERCISES  AS ce INNER JOIN $TBL_TRACK_EXERCISES AS te ON (te.exe_exo_id = ce.id) INNER JOIN  
                     $TBL_USER  AS user ON (user.user_id = exe_user_id)
                WHERE   ce.c_id = $course_id AND
                        te.status != 'incomplete' AND 
                        te.exe_cours_id='" . Database :: escape_string($cid) . "'  $user_id_and  $session_id_and AND 
                        ce.active <>-1 AND 
                        orig_lp_id = 0 AND 
                        orig_lp_item_id = 0";
        
            $hpsql="SELECT ".(api_is_western_name_order() ? "firstname as userpart1, lastname userpart2" : "lastname as userpart1, firstname as userpart2").", email, tth.exe_name, tth.exe_result , tth.exe_weighting, tth.exe_date
                    FROM $TBL_TRACK_HOTPOTATOES tth, $TBL_USER tu
                    WHERE   tu.user_id=tth.exe_user_id AND 
                            tth.exe_cours_id = '" . Database :: escape_string($cid) . "' AND 
                            tth.exe_name = '$hotpotato_name'
                    ORDER BY tth.exe_cours_id ASC, tth.exe_date DESC";

		} else {
            $user_id_and = ' AND te.exe_user_id = ' . api_get_user_id() . ' ';
			// get only this user's results
            $sql="SELECT ".(api_is_western_name_order() ? "firstname as userpart1, lastname userpart2" : "lastname as userpart1, firstname as userpart2").", ce.title as extitle, te.exe_result as exresult, " .
                        "te.exe_weighting as exweight, te.exe_date as exdate, te.exe_id as exid, email as exemail, " .
                        "te.start_date as exstart, steps_counter as exstep, exe_user_id as excruid, te.exe_duration as exduration, ce.results_disabled as exdisabled
                        FROM $TBL_EXERCISES  AS ce INNER JOIN $TBL_TRACK_EXERCISES AS te ON (te.exe_exo_id = ce.id) INNER JOIN  $TBL_USER  AS user ON (user.user_id = exe_user_id)
                        WHERE   ce.c_id = $course_id AND
                                te.status != 'incomplete' AND 
                                te.exe_cours_id='" . Database :: escape_string($cid) . "'  $user_id_and $session_id_and AND 
                                ce.active <>-1 AND 
                                orig_lp_id = 0 AND 
                                orig_lp_item_id = 0 
                        ORDER BY userpart2, te.exe_cours_id ASC, ce.title ASC, te.exe_date DESC";
        
            $hpsql = "SELECT '', exe_name, exe_result , exe_weighting, exe_date 
                            FROM $TBL_TRACK_HOTPOTATOES
                            WHERE   exe_user_id = '" . $user_id . "' AND 
                                    exe_cours_id = '" . Database :: escape_string($cid) . "' AND 
                                    tth.exe_name = '$hotpotato_name'
                            ORDER BY exe_cours_id ASC, exe_date DESC";
		}

		$results = array();         
		
		$resx = Database::query($sql);
		while ($rowx = Database::fetch_array($resx,'ASSOC')) {
            $results[] = $rowx;
		}
		
		$hpresults = array();
		$resx = Database::query($hpsql);
	    while ($rowx = Database::fetch_array($resx,'ASSOC')) {
            $hpresults[] = $rowx;
		}
 		
		if ($filter) {
			switch ($filter) {
				case 1 :
                    $filter_by_not_revised = true;
                    break;
				case 2 :
                    $filter_by_revised = true;
                    break;
				default :
                    null;
			}
		}

		//Print the results of tests
		if (is_array($results) && empty($hotpotato_name)) {
			for ($i = 0; $i < sizeof($results); $i++) {
				$revised = false;
				
				//revised or not
				$sql_exe = "SELECT exe_id FROM $TBL_TRACK_ATTEMPT_RECORDING
							WHERE author != '' AND exe_id = ".Database :: escape_string($results[$i]['exid'])." LIMIT 1";							
				$query = Database::query($sql_exe);

				if (Database :: num_rows($query) > 0) 
                    $revised = true;
                
				if ($filter_by_not_revised && $revised) continue;
				if ($filter_by_revised && !$revised) continue;

				$return[$i] = array();
				                
				if (empty($user_id)) {					
					$return[$i]['first_name']   = $results[$i]['userpart1'];
					$return[$i]['last_name']    = $results[$i]['userpart2'];
					$return[$i]['user_id']      = $results[$i]['excruid'];
					$return[$i]['email']        = $results[$i]['exemail'];					
				}
				$return[$i]['title']   = $results[$i]['extitle'];
				$return[$i]['time']    = api_convert_and_format_date($results[$i]['exdate'], null, date_default_timezone_get());
				$return[$i]['result']  = $results[$i]['exresult'];
				$return[$i]['max']     = $results[$i]['exweight'];
			}
		}
		
		// Print the Result of Hotpotatoes Tests	
		if (is_array($hpresults)) {
			for($i = 0; $i < sizeof($hpresults); $i++) {
				$return[$i] = array();				
				$title = GetQuizName($hpresults[$i]['exe_name'], $document_path);
				if ($title =='') {
					$title = basename($hpresults[$i]['exe_name']);
				}
				if(empty($user_id)) {					
				    $return[$i]['email'] = $hpresults[$i]['email'];
					$return[$i]['first_name'] = $hpresults[$i]['userpart1'];
					$return[$i]['last_name'] = $hpresults[$i]['userpart2'];
					//$return[$i]['user_id'] = $results[$i]['userid'];
				}
				$return[$i]['title'] = $title;
				$return[$i]['time'] = api_convert_and_format_date($hpresults[$i]['exdate'], null, date_default_timezone_get());
				$return[$i]['result'] = $hpresults[$i]['exe_result'];
				$return[$i]['max'] = $hpresults[$i]['exe_weighting'];
			}
		}		
		$this->results = $return;
		return true;
	}
	/**
	 * Exports the complete report as a CSV file
	 * @param	string		Document path inside the document tool
	 * @param	integer		Optional user ID
	 * @param	boolean		Whether to include user fields or not
	 * @return	boolean		False on error
	 */
	public function exportCompleteReportCSV($document_path='',$user_id=null, $export_user_fields = false, $export_filter = 0, $exercise_id = 0, $hotpotato_name = null) {
		global $charset;
		$this->_getExercisesReporting($document_path,$user_id, $export_filter, $exercise_id, $hotpotato_name);
		$filename = 'exercise_results_'.date('YmdGis').'.csv';
		if(!empty($user_id)) {
			$filename = 'exercise_results_user_'.$user_id.'_'.date('YmdGis').'.csv';
		}
		$data = '';
			
        if (api_is_western_name_order()) {
            if(!empty($this->results[0]['first_name'])) {
                $data .= get_lang('FirstName').';';
            }  
            if(!empty($this->results[0]['last_name'])) {
                $data .= get_lang('LastName').';';
            }             
        } else {
            if(!empty($this->results[0]['last_name'])) {
                $data .= get_lang('LastName').';';
            }
            if(!empty($this->results[0]['first_name'])) {
                $data .= get_lang('FirstName').';';
            }            
        }        
        $data .= get_lang('Email').';';        
        
		if ($export_user_fields) {
			//show user fields section with a big th colspan that spans over all fields
			$extra_user_fields = UserManager::get_extra_fields(0,1000,5,'ASC',false, 1);			
			$num = count($extra_user_fields);			
			foreach($extra_user_fields as $field) {
				$data .= '"'.str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES, $charset)).'";';
			}
			$display_extra_user_fields = true;
		}
		$data .= get_lang('Title').';';
		$data .= get_lang('Date').';';
		$data .= get_lang('Score').';';
		$data .= get_lang('Total').';';
		$data .= "\n";
        
		//results
		foreach($this->results as $row) {
            if (api_is_western_name_order()) {            
                if(!empty($row['first_name'])) {
                    $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['first_name']), ENT_QUOTES, $charset)).';';
                }    
    			if(!empty($row['last_name'])) {
    				$data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['last_name']), ENT_QUOTES, $charset)).';';
    			}
            } else {
                if(!empty($row['last_name'])) {
                    $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['last_name']), ENT_QUOTES, $charset)).';';
                }
                if(!empty($row['first_name'])) {
                    $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['first_name']), ENT_QUOTES, $charset)).';';
                }
            }
            $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['email']), ENT_QUOTES, $charset)).';';
			
			if ($export_user_fields) {
				//show user fields data, if any, for this user
				$user_fields_values = UserManager::get_extra_user_data($row['user_id'],false,false, false, true);
				foreach($user_fields_values as $value) {
					$data .= '"'.str_replace('"','""',api_html_entity_decode(strip_tags($value), ENT_QUOTES, $charset)).'";';
				}
			}
			$data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['title']), ENT_QUOTES, $charset)).';';
			$data .= str_replace("\r\n",'  ',$row['time']).';';
			$data .= str_replace("\r\n",'  ',$row['result']).';';
			$data .= str_replace("\r\n",'  ',$row['max']).';';
			$data .= "\n";
		}

		//output the results
		$len = strlen($data);
		header('Content-type: application/octet-stream');
		header('Content-Type: application/force-download');
		header('Content-length: '.$len);
		if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
			header('Content-Disposition: filename= '.$filename);
		} else {
			header('Content-Disposition: attachment; filename= '.$filename);
		}
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			header('Pragma: ');
			header('Cache-Control: ');
			header('Cache-Control: public'); // IE cannot download from sessions without a cache
		}
		header('Content-Description: '.$filename);
		header('Content-transfer-encoding: binary');
		echo $data;
		return true;
	}

	/**
	 * Exports the complete report as an XLS file
	 * @return	boolean		False on error
	 */
	public function exportCompleteReportXLS($document_path='',$user_id = null, $export_user_fields= false, $export_filter = 0, $exercise_id=0, $hotpotato_name = null) {
		global $charset;		
		$this->_getExercisesReporting($document_path, $user_id, $export_filter, $exercise_id,$hotpotato_name);
		$filename = 'exercise_results_'.date('YmdGis').'.xls';
		if(!empty($user_id)) {
			$filename = 'exercise_results_user_'.$user_id.'_'.date('YmdGis').'.xls';
		}
		
		require_once api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php';
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->setTempDir(api_get_path(SYS_ARCHIVE_PATH));
		$workbook->setVersion(8); // BIFF8

		$workbook->send($filename);
		$worksheet =& $workbook->addWorksheet('Report '.date('YmdGis'));
		$worksheet->setInputEncoding(api_get_system_encoding());
		$line = 0;
		$column = 0; //skip the first column (row titles)

		// check if exists column 'user'
		$with_column_user = false;
		foreach ($this->results as $result) {
			if (!empty($result['last_name']) && !empty($result['first_name'])) {
				$with_column_user = true;
				break;
			}
		}

		if ($with_column_user) {
		    
		    $worksheet->write($line,$column,get_lang('Email'));
		    $column++;
            
            if (api_is_western_name_order()) {         
		   	
    			$worksheet->write($line,$column,get_lang('FirstName'));
    			$column++;
                
                $worksheet->write($line,$column,get_lang('LastName'));
                $column++;
            } else {
                $worksheet->write($line,$column,get_lang('LastName'));
                $column++;
                
                $worksheet->write($line,$column,get_lang('FirstName'));
                $column++;               
            }
		}

		if ($export_user_fields) {
			//show user fields section with a big th colspan that spans over all fields
			$extra_user_fields = UserManager::get_extra_fields(0,1000,5,'ASC',false, 1);
					
			//show the fields names for user fields
			foreach($extra_user_fields as $field) {			    	
				$worksheet->write($line,$column,api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES, $charset));
				$column++;
			}
		}
		
		$worksheet->write($line,$column,get_lang('Title'));
		$column++;
		$worksheet->write($line,$column,get_lang('Date'));
		$column++;
		$worksheet->write($line,$column,get_lang('Score'));
		$column++;
		$worksheet->write($line,$column,get_lang('Total'));
		$line++;

		foreach($this->results as $row) {
			$column = 0;
			if(!empty($row['last_name']) && !empty($row['first_name'])) {
			   $worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['email']), ENT_QUOTES, $charset));
			    $column++;
				$worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['last_name']), ENT_QUOTES, $charset));
				$column++;
				$worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['first_name']), ENT_QUOTES, $charset));
				$column++;
			}
			
			if ($export_user_fields) {			
				//show user fields data, if any, for this user
				$user_fields_values = UserManager::get_extra_user_data($row['user_id'],false,false, false, true);
								
				foreach($user_fields_values as $value) {	
					$worksheet->write($line,$column, api_html_entity_decode(strip_tags($value), ENT_QUOTES, $charset));
					$column++;
				}
			}
			$worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['title']), ENT_QUOTES, $charset));
			$column++;
			$worksheet->write($line,$column,$row['time']);
			$column++;
			$worksheet->write($line,$column,$row['result']);
			$column++;
			$worksheet->write($line,$column,$row['max']);
			$line++;
		}
		//output the results
		$workbook->close();
		return true;
	}
}
endif;
