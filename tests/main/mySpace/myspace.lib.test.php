<?php


class TestMySpaceLib extends UnitTestCase {
	
	public function TestMySpaceLib() {
		$this->UnitTestCase('Test My Space');
	}	
	
	
	public function __construct() {
		// The constructor acts like a global setUp for the class			
		require_once api_get_path(SYS_TEST_PATH).'setup.inc.php';
	}
/*

	public function testExportCsv() {
		//ob_start();
		$res = MySpace::export_csv($header = array(1, 2 ,3), $data = array(1, 2 ,3), $file_name = 'export.csv');
 		$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/	
	public function testGetConnectionsToCourse() {
		//ob_start();
		$res = MySpace::get_connections_to_course($user_id = 1, $course_code = 'COURSETEST');
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetConnectionsToCourseByTime() {
		//ob_start();
		$res = MySpace::get_connections_to_course_by_time($user_id = 1, $course_code = 'COURSETEST', $year = '', $month = '', $day = '');
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testCourseInfoTrackingFilter() {
		//ob_start();
		$res = MySpace::course_info_tracking_filter($user_id = 1, $url_params = array(), $row = array());
 		$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testDisplayTrackingUserOverview() {
		ob_start();
		$res = MySpace::display_tracking_user_overview();
 		$this->assertTrue(is_null($res));
		ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testDisplayUserOverviewExportOptions() {
		//ob_start();
		$res = MySpace::display_user_overview_export_options();
 		$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testExercisesResults() {
		//ob_start();
		$res = MySpace::exercises_results($user_id = 1, $course_code = 'COURSETEST');
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testExportTrackingUserOverview() {
		//ob_start();
		$res = MySpace::export_tracking_user_overview();
 		$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetCourseData() {
		//ob_start();
		$res = MySpace::get_course_data($from = 1, $number_of_items = 2, $column = 2, $direction = 1);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testGetNumberOfUsersTrackingOverview() {
		//ob_start();
		$res = MySpace::get_number_of_users_tracking_overview();
 		$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testGetUserDataTrackingOverview() {
		//ob_start();
		$res = MySpace::get_user_data_tracking_overview($from = 1, $number_of_items = 1, $column = 1, $direction = 1);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	public function testGetUserOverviewExportExtraFields() {
		//ob_start();
		$res = MySpace::get_user_overview_export_extra_fields($user_id = 1);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testMakeUsername() {
		//ob_start();
		$res = MySpace::make_username($firstname = 'Vargas', $lastname = 'Carlos', $username = 'cvargas', $language = null, $encoding = null);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testCheckUserInArray() {
		//ob_start();
		$res = MySpace::check_user_in_array($usernames = array(), $user_array = array());
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testUserAvailableInSession() {
		//ob_start();
		$res = MySpace::user_available_in_session($username = 1, $course_list = array(), $id_session = 1);
 		$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testCheckAllUsernames() {
		//ob_start();
		$res = MySpace::check_all_usernames($users = array(), $course_list = array(), $id_session = 1);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetUserCreator() {
		//ob_start();
		$res = MySpace::get_user_creator($users = array(), $course_list = array(), $id_session = 1);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
		
	public function testValidate_data() {
		//ob_start();
		$res = MySpace::validate_data($users = array(), $id_session = null);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testCompleteMissingData() {
		//ob_start();
		$res = MySpace::complete_missing_data($user = 1);
 		$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}*//*
	
	public function testSaveData() {
		//ob_start();
		$res = MySpace::save_data($users = array(), $course_list = array(), $id_session = 1);
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testParseCsvData() {
		//ob_start();
		$res = MySpace::parse_csv_data($file = '');
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testElementStart() {
		//ob_start();
		$res = MySpace::element_start($parser = 'Contact', $data = '');
 		$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testElementEnd() {
		//ob_start();
		$res = MySpace::element_end($parser = 'Contact', $data = '');
 		$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testCharacterData() {
		//ob_start();
		$res = MySpace::character_data($parser = 'Contact', $data = '');
 		$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testParseXmlData() {
		//ob_start();
		$res = MySpace::parse_xml_data($file = '');
 		$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function __destruct() {
		// The destructor acts like a global tearDown for the class			
		//require_once api_get_path(SYS_TEST_PATH).'teardown.inc.php';			
	}
}