<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL

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
*	ExerciseResult class: This class allows to instantiate an object of type ExerciseResult
*	which allows you to export exercises results in multiple presentation forms
*	@package dokeos.exercise
* 	@author Yannick Warnier
* 	@version $Id: $
*/


if(!class_exists('ExerciseResult')):

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
		$result=api_sql_query($sql,__FILE__,__LINE__);

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
		$result=api_sql_query($sql,__FILE__,__LINE__);

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
	function _getExercisesReporting($document_path,$user_id=null)
	{
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
		if(empty($user_id))
		{
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

		}
		else
		{ // get only this user's results
			  $sql="SELECT '',ce.title, te.exe_result , te.exe_weighting, " .
			  		"UNIX_TIMESTAMP(te.exe_date),te.exe_id
				  FROM $TBL_EXERCISES ce , $TBL_TRACK_EXERCISES te
				  WHERE te.exe_exo_id = ce.id AND te.exe_user_id='".Database::escape_string($user_id)."' AND te.exe_cours_id='".Database::escape_string($cid)."'
				  ORDER BY te.exe_cours_id ASC, ce.title ASC, te.exe_date ASC";

			$hpsql="SELECT '',exe_name, exe_result , exe_weighting, UNIX_TIMESTAMP(exe_date)
					FROM $TBL_TRACK_HOTPOTATOES
					WHERE exe_user_id = '".Database::escape_string($user_id)."' AND exe_cours_id = '".Database::escape_string($cid)."'
					ORDER BY exe_cours_id ASC, exe_date ASC";

		}

		$results=getManyResultsXCol($sql,8);
		$hpresults=getManyResultsXCol($hpsql,7);

		$NoTestRes = 0;
		$NoHPTestRes = 0;
		$j=0;
		//Print the results of tests
		if(is_array($results))
		{
			for($i = 0; $i < sizeof($results); $i++)
			{
				$return[$i] = array();
				$id = $results[$i][5];
				$mailid = $results[$i][6];
				$user = $results[$i][0];
				$test = $results[$i][1];
				$dt = strftime(get_lang('dateTimeFormatLong'),$results[$i][4]);
				$res = $results[$i][2];
				if(empty($user_id))
				{
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
		if(is_array($hpresults))
		{
			for($i = 0; $i < sizeof($hpresults); $i++)
			{
				$return[$j+$i] = array();
				$title = GetQuizName($hpresults[$i][1],$document_path);
				if ($title =='')
				{
					$title = GetFileName($hpresults[$i][1]);
				}
				if(empty($user_id))
				{
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
	public function exportCompleteReportCSV($document_path='',$user_id=null, $export_user_fields)
	{
		global $charset;
		$this->_getExercisesReporting($document_path,$user_id);
		$filename = 'exercise_results_'.date('YmdGis').'.csv';
		if(!empty($user_id))
		{
			$filename = 'exercise_results_user_'.$user_id.'_'.date('YmdGis').'.csv';
		}
		$data = '';
		//build the results
		//titles
		if(!empty($this->results[0]['user']))
		{
			$data .= get_lang('User').';';
		}
		if($export_user_fields)
		{
			//show user fields section with a big th colspan that spans over all fields
			$extra_user_fields = UserManager::get_extra_fields(0,0,5,'ASC',false);
			$num = count($extra_user_fields);
			foreach($extra_user_fields as $field)
			{
				$data .= '"'.str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES, $charset)).'";';
			}
			$display_extra_user_fields = true;
		}
		$data .= get_lang('Title').';';
		$data .= get_lang('Date').';';
		$data .= get_lang('Results').';';
		$data .= get_lang('Weighting').';';
		$data .= "\n";
		//results
		foreach($this->results as $row)
		{
			if(!empty($row['user']))
			{
				$data .= str_replace("\r\n",'  ',api_html_entity_decode(strip_tags($row['user']), ENT_QUOTES, $charset)).';';
			}
			if($export_user_fields)
			{
				//show user fields data, if any, for this user
				$user_fields_values = UserManager::get_extra_user_data(intval($row['user_id']),false,false);
				foreach($user_fields_values as $value)
				{
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
		if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT']))
		{
			header('Content-Disposition: filename= '.$filename);
		}
		else
		{
			header('Content-Disposition: attachment; filename= '.$filename);
		}
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
		{
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
	public function exportCompleteReportXLS($document_path='',$user_id=null, $export_user_fields)
	{
		global $charset;
		$this->_getExercisesReporting($document_path,$user_id);
		$filename = 'exercise_results_'.date('YmdGis').'.xls';
		if(!empty($user_id))
		{
			$filename = 'exercise_results_user_'.$user_id.'_'.date('YmdGis').'.xls';
		}		//build the results
		require_once(api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php');
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->send($filename);
		$worksheet =& $workbook->addWorksheet('Report '.date('YmdGis'));
		$line = 0;
		$column = 0; //skip the first column (row titles)
		if(!empty($this->results[0]['user']))
		{
			$worksheet->write($line,$column,get_lang('User'));
			$column++;
		}
		if($export_user_fields)
		{
			//show user fields section with a big th colspan that spans over all fields
			$extra_user_fields = UserManager::get_extra_fields(0,0,5,'ASC',false);
			//show the fields names for user fields
			foreach($extra_user_fields as $field)
			{
				$worksheet->write($line,$column,api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES, $charset));
				$column++;
			}
		}
		$worksheet->write($line,$column,get_lang('Title'));
		$column++;
		$worksheet->write($line,$column,get_lang('Date'));
		$column++;
		$worksheet->write($line,$column,get_lang('Results'));
		$column++;
		$worksheet->write($line,$column,get_lang('Weighting'));
		$line++;

		foreach($this->results as $row)
		{
			$column = 0;
			if(!empty($row['user']))
			{
				$worksheet->write($line,$column,api_html_entity_decode(strip_tags($row['user']), ENT_QUOTES, $charset));
				$column++;
			}
			//show user fields data, if any, for this user
			$user_fields_values = UserManager::get_extra_user_data(intval($row['user_id']),false,false);
			foreach($user_fields_values as $value)
			{
				$worksheet->write($line,$column,api_html_entity_decode(strip_tags($value), ENT_QUOTES, $charset));
				$column++;
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
?>
