<?php

require_once api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php';

class TestExerciseResult extends UnitTestCase {
	
		public $eExerciseResult;
	
	public function TestExerciseResult() {
		$this->UnitTestCase('');
	}
	
	public function setUp() {
		$this->eExerciseResult = new ExerciseResult();			
	}
	
	public function tearDown() {		
		$this->eExerciseResult = null;
	}
	
	/**
	 * Gets the results of all students (or just one student if access is limited)
	 * @param	string		The document path (for HotPotatoes retrieval)
	 * @param	integer		User ID. Optional. If no user ID is provided, we take all the results. Defauts to null
	 */
	 
	function test_getExercisesReporting() {
		global $user_id;
	 	$document_path = api_get_path(SYS_COURSE_PATH).'document/';
		$res = $this->eExerciseResult->_getExercisesReporting($document_path,$user_id,$filter=0);
		if(!is_null($res)) {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}
	
	/**
	 * Exports the complete report as a CSV file
	 * @param	string		Document path inside the document tool
	 * @param	integer		Optional user ID
	 * @param	boolean		Whether to include user fields or not
	 * @return	boolean		False on error
	 */
	 
	 function testexportCompleteReportCSV() {
		global $user_id;
		$document_path = api_get_path(SYS_COURSE_PATH).'document/';
		if(!headers_sent()){
		$res = $this->eExerciseResult->exportCompleteReportCSV($document_path,$user_id, $export_user_fields = array(), $export_filter = 0);
		}
		if(!is_null($res)) {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}
	
	/**
	 * Exports the complete report as an XLS file
	 * @return	boolean		False on error
	 */
	 
	 function testexportCompleteReportXLS() {
		global $user_id;
		$document_path = api_get_path(SYS_COURSE_PATH).'document/';
		if(!headers_sent()){
		$res = $this->eExerciseResult->exportCompleteReportXLS($document_path='',$user_id, $export_user_fields=array(), $export_filter = 0);
		}
		if(!is_null($res)) {
			$this->assertTrue(is_bool($res));
		}
		//var_dump($res);
	}
	 
	 
	
	
	
	
	
	
	
	
	
}
?>
