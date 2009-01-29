<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue du Corbeau, 108
		B-1030 Brussels - Belgium
		info@dokeos.com
*/
/**
*	ExerciseResult class: This class allows to instantiate an object of type ExerciseResult
*	which allows you to export exercises results in multiple presentation forms
*	@package dokeos.exercise
* 	@author Yannick Warnier
* 	@version $Id: $
*/


if(!class_exists('GradeBookResult')):

class GradeBookResult
{
	private $gradebook_list = array(); //stores the list of exercises
	private $results = array(); //stores the results
	
	/**
	 * constructor of the class
	 */
	public function GradeBookResult($get_questions=false,$get_answers=false) {
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
	private function _readGradebookList($only_visible = false) {
		$return = array();
    	$TBL_EXERCISES          = Database::get_course_table(TABLE_QUIZ_TEST);

		$sql="SELECT id,title,type,random,active FROM $TBL_EXERCISES";
		if($only_visible) {
			$sql.= ' WHERE active=1';
		}
		$sql .= ' ORDER BY title';
		$result=api_sql_query($sql,__FILE__,__LINE__);

		// if the exercise has been found
		while($row=Database::fetch_array($result,'ASSOC')) {
			$return[] = $row;
		}
		// exercise not found
		return $return;
	}

	/**
	 * Gets the questions related to one exercise
	 * @param	integer		Exercise ID
	 */
	private function _readGradeBookQuestionsList($e_id) {
		$return = array();
    	$TBL_EXERCISE_QUESTION  = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    	$TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_QUESTION);
		$sql="SELECT q.id, q.question, q.ponderation, q.position, q.type, q.picture " .
			" FROM $TBL_EXERCISE_QUESTION eq, $TBL_QUESTIONS q " .
			" WHERE eq.question_id=q.id AND eq.exercice_id='$e_id' " .
			" ORDER BY q.position";
		$result=api_sql_query($sql,__FILE__,__LINE__);

		// fills the array with the question ID for this exercise
		// the key of the array is the question position
		while($row=Database::fetch_array($result,'ASSOC')) {
			$return[] = $row;
		}
		return true;		
	}
	/**
	 * Gets the results of all students (or just one student if access is limited)
	 * @param	string		The document path (for HotPotatoes retrieval)
	 * @param	integer		User ID. Optional. If no user ID is provided, we take all the results. Defauts to null
	 */
	function _getGradeBookReporting($document_path,$user_id=null) {
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

    	$cid = api_get_course_id();
		if (empty($user_id)) {
			//get all results (ourself and the others) as an admin should see them
			//AND exe_user_id <> $_user['user_id']  clause has been removed
			$sql="SELECT CONCAT(lastname,' ',firstname),ce.title, te.exe_result ,
						te.exe_weighting, UNIX_TIMESTAMP(te.exe_date),te.exe_id, user.email, user.user_id
				  FROM $TBL_EXERCISES ce , $TBL_TRACK_EXERCISES te, $TBL_USER user
				  WHERE te.exe_exo_id = ce.id AND user_id=te.exe_user_id AND te.exe_cours_id='$cid'
				  ORDER BY te.exe_cours_id ASC, ce.title ASC, te.exe_date ASC";

			$hpsql="SELECT CONCAT(tu.lastname,' ',tu.firstname), tth.exe_name,
						tth.exe_result , tth.exe_weighting, UNIX_TIMESTAMP(tth.exe_date), tu.email, tu.user_id
					FROM $TBL_TRACK_HOTPOTATOES tth, $TBL_USER tu
					WHERE  tu.user_id=tth.exe_user_id AND tth.exe_cours_id = '".$cid."'
					ORDER BY tth.exe_cours_id ASC, tth.exe_date ASC";

		} else { // get only this user's results
			  $sql="SELECT '',ce.title, te.exe_result , te.exe_weighting, " .
			  		"UNIX_TIMESTAMP(te.exe_date),te.exe_id
				  FROM $TBL_EXERCISES ce , $TBL_TRACK_EXERCISES te
				  WHERE te.exe_exo_id = ce.id AND te.exe_user_id='".$user_id."' AND te.exe_cours_id='$cid'
				  ORDER BY te.exe_cours_id ASC, ce.title ASC, te.exe_date ASC";

			$hpsql="SELECT '',exe_name, exe_result , exe_weighting, UNIX_TIMESTAMP(exe_date)
					FROM $TBL_TRACK_HOTPOTATOES
					WHERE exe_user_id = '".$user_id."' AND exe_cours_id = '".$cid."'
					ORDER BY exe_cours_id ASC, exe_date ASC";

		}

		$results=getManyResultsXCol($sql,8);
		$hpresults=getManyResultsXCol($hpsql,7);

		$NoTestRes = 0;
		$NoHPTestRes = 0;
		$j=0;
		//Print the results of tests
		if (is_array($results)) {
			for ($i = 0; $i < sizeof($results); $i++) {
				$return[$i] = array();
				$id = $results[$i][5];
				$mailid = $results[$i][6];
				$user = $results[$i][0];
				$test = $results[$i][1];
				$dt = strftime(get_lang('dateTimeFormatLong'),$results[$i][4]);
				$res = $results[$i][2];
				if(empty($user_id)) {
					$user = $results[$i][0];
					$return[$i]['user'] = $user;
					$return[$i]['user_id'] = $results[$i][7];
				}
				$return[$i]['title'] = $test;
				$return[$i]['time'] = format_locale_date(get_lang('dateTimeFormatLong'),$results[$i][4]);
				$return[$i]['result'] = $res;
				$return[$i]['max'] = $results[$i][3];
				$j=$i;
			}
		}
		$j++;
		// Print the Result of Hotpotatoes Tests
		if (is_array($hpresults)) {
			for ($i = 0; $i < sizeof($hpresults); $i++) {
				$return[$j+$i] = array();
				$title = GetQuizName($hpresults[$i][1],$document_path);
				if ($title =='') {
					$title = GetFileName($hpresults[$i][1]);
				}
				if (empty($user_id)) {
					$return[$j+$i]['user'] = $hpresults[$i][0];
					$return[$j+$i]['user_id'] = $results[$i][6];
					
				}
				$return[$j+$i]['title'] = $title;
				$return[$j+$i]['time'] = strftime(get_lang('dateTimeFormatLong'),$hpresults[$i][4]);
				$return[$j+$i]['result'] = $hpresults[$i][2];
				$return[$j+$i]['max'] = $hpresults[$i][3];
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
	public function exportCompleteReportCSV($dato) {
		//$this->_getGradeBookReporting($document_path,$user_id);
		$filename = 'gradebook_results_'.date('YmdGis').'.csv';
		if (!empty($user_id)) {
			$filename = 'gradebook_results_user_'.$user_id.'_'.date('YmdGis').'.csv';
		}
		$data = '';
		//build the results
		//titles
	
		foreach ($dato[0] as $header_col) {
			if(!empty($header_col)) {
				$data .= str_replace("\r\n",'  ',html_entity_decode(strip_tags($header_col))).';';
			}			
		}
		
		$data .="\r\n";
		$cant_students = count($dato[1]);
		//print_r($data);		exit();
		
		for($i=0;$i<$cant_students;$i++) {
			$column = 0;
			foreach($dato[1][$i] as $col_name) {	
				$data .= str_replace("\r\n",'  ',html_entity_decode(strip_tags($col_name))).';';					
			}
			$data .="\r\n";
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
		} if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
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
	public function exportCompleteReportXLS($data) {
	   	$filename = 'gradebook_results_user_'.date('YmdGis').'.xls';
		//build the results
		require_once(api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php');
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->send($filename);
		$worksheet =& $workbook->addWorksheet('Report '.date('YmdGis'));
		$line = 0;
		$column = 0; //skip the first column (row titles)
		//headers			
		foreach ($data[0] as $header_col) {
			$worksheet->write($line,$column,$header_col);
			$column++;
		}
		//$worksheet->write($line,$column,get_lang('Total'));
		//$column++;		
		$line++;	
		
		$cant_students = count($data[1]);
		//print_r($data);		exit();
		
		for ($i=0;$i<$cant_students;$i++) {
			$column = 0;
			foreach ($data[1][$i] as $col_name) {		
				$worksheet->write($line,$column,strip_tags($col_name));
				$column++;
				
			}
			$line++;
		}
		//output the results
		$workbook->close();
		return true;
	}
}

endif;
?>