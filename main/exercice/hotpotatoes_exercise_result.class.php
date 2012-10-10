<?php
/* For licensing terms, see /license.txt */
/**
 * HotpotatoesExerciseResult class: This class allows to instantiate an object
 * of type HotpotatoesExerciseResult
 * which allows you to export exercises results in multiple presentation forms
 * @package chamilo.exercise
 * @author 
 * @version $Id: $
 */
/**
 * Code
 */
if(!class_exists('HotpotatoesExerciseResult')):
/**
 * Exercise results class
 * @package chamilo.exercise
 */
class HotpotatoesExerciseResult
{
	private $exercises_list = array(); //stores the list of exercises
	private $results = array(); //stores the results

	/**
	 * constructor of the class
	 */
	public function ExerciseResult($get_questions=false,$get_answers=false) {
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
	 * Gets the results of all students (or just one student if access is limited)
	 * @param	string		The document path (for HotPotatoes retrieval)
	 * @param	integer		User ID. Optional. If no user ID is provided, we take all the results. Defauts to null
	 */
//	function _getExercisesReporting($document_path, $user_id = null, $filter = 0, $hotpotato_name = null) {
	function _getExercisesReporting($document_path, $hotpotato_name) {
		$return = array();

    $TBL_EXERCISES          = Database::get_course_table(TABLE_QUIZ_TEST);
    $TBL_USER          	    = Database::get_main_table(TABLE_MAIN_USER);
    $TBL_TRACK_EXERCISES    	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
    $TBL_TRACK_HOTPOTATOES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
    $TBL_TRACK_ATTEMPT_RECORDING= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
    
    $cid             = api_get_course_id();
    $course_id       = api_get_course_int_id();
    $user_id         = intval($user_id);
    $session_id_and  = ' AND te.session_id = ' . api_get_session_id() . ' ';
    $hotpotato_name  = Database::escape_string($hotpotato_name);
    
    
    if (!empty($exercise_id)) {
      $session_id_and .= " AND exe_exo_id = $exercise_id ";
    }

		if (empty($user_id)) {
    $sql="SELECT firstname as userpart1, lastname as userpart2 ,
            email,
            tth.exe_name,
            tth.exe_result,
            tth.exe_weighting,
            tth.exe_date
            FROM $TBL_TRACK_HOTPOTATOES tth, $TBL_USER tu
            WHERE   tu.user_id=tth.exe_user_id AND
                    tth.exe_cours_id = '" . Database :: escape_string($cid) . "' AND
                    tth.exe_name = '$hotpotato_name'
            ORDER BY tth.exe_cours_id ASC, tth.exe_date DESC";
		} else {
      $user_id_and = ' AND te.exe_user_id = ' . api_get_user_id() . ' ';
      // get only this user's results
  
      $sql = "SELECT '', exe_name, exe_result , exe_weighting, exe_date
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
		$resx = Database::query($sql);
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
				}
				$return[$i]['title'] = $title;
				$return[$i]['exe_date']  = $hpresults[$i]['exe_date'];

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
	public function exportCompleteReportCSV($document_path='', $hotpotato_name) {
		global $charset;
		$this->_getExercisesReporting($document_path, $hotpotato_name);
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
		}

		$data .= get_lang('Title').';';
		$data .= get_lang('StartDate').';';
		$data .= get_lang('Score').';';
		$data .= get_lang('Total').';';
		$data .= "\n";

		//results
		foreach($this->results as $row) {
      if (api_is_western_name_order()) {
          $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['first_name']), ENT_QUOTES, $charset)).';';
          $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['last_name']), ENT_QUOTES, $charset)).';';
      } else {
          $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['last_name']), ENT_QUOTES, $charset)).';';
          $data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['first_name']), ENT_QUOTES, $charset)).';';
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
			$data .= str_replace("\r\n",'  ',$row['exe_date']).';';
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
		// @todo add this utf-8 header for all csv files
		echo "\xEF\xBB\xBF";  // force utf-8 header of csv file
		echo $data;
		return true;
	}

	/**
	 * Exports the complete report as an XLS file
	 * @return	boolean		False on error
	 */
	public function exportCompleteReportXLS($document_path='',$user_id = null, $export_user_fields= false, $export_filter = 0, $exercise_id=0, $hotpotato_name = null) {
		global $charset;
		$this->_getExercisesReporting($document_path, $user_id, $export_filter, $exercise_id, $hotpotato_name);
		$filename = 'exercise_results_'.date('YmdGis').'.xls';
		if (!empty($user_id)) {
			$filename = 'exercise_results_user_'.$user_id.'_'.date('YmdGis').'.xls';
		}
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
			foreach ($extra_user_fields as $field) {
				$worksheet->write($line,$column,api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES, $charset));
				$column++;
			}
		}

		$worksheet->write($line,$column, get_lang('Title'));
		$column++;
		$worksheet->write($line,$column, get_lang('StartDate'));
        $column++;
        $worksheet->write($line,$column, get_lang('EndDate'));
        $column++;
        $worksheet->write($line,$column, get_lang('Duration').' ('.get_lang('MinMinutes').')');
		$column++;
		$worksheet->write($line,$column, get_lang('Score'));
		$column++;
		$worksheet->write($line,$column, get_lang('Total'));
		$column++;
        $worksheet->write($line,$column, get_lang('Status'));
		$line++;

		foreach ($this->results as $row) {
			$column = 0;

            if ($with_column_user) {
                $worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['email']), ENT_QUOTES, $charset));
                $column++;

                if (api_is_western_name_order()) {
                    $worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['first_name']), ENT_QUOTES, $charset));
                    $column++;
                    $worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['last_name']), ENT_QUOTES, $charset));
                    $column++;
                } else {
                    $worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['last_name']), ENT_QUOTES, $charset));
                    $column++;
                    $worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['first_name']), ENT_QUOTES, $charset));
                    $column++;
                }
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
			$worksheet->write($line,$column,$row['start_date']);
            $column++;
			$worksheet->write($line,$column,$row['end_date']);
            $column++;
			$worksheet->write($line,$column,$row['duration']);
			$column++;
			$worksheet->write($line,$column,$row['result']);
			$column++;
			$worksheet->write($line,$column,$row['max']);
			$column++;
            $worksheet->write($line,$column,$row['status']);
			$line++;
		}
		//output the results
		$workbook->close();
		return true;
	}
}
endif;