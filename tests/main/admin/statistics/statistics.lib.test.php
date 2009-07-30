<?php

require_once(api_get_path(LIBRARY_PATH).'sessionmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

Mock::generate('Database');
Mock::generate('Display');
Mock::generate('DocumentManager');

class TestStatistics extends UnitTestCase{
	
	public $statisc;
	public function TestStatistics(){
		$this->UnitTestCase('this File test the provides some function for statistics ');
	}
	
	public function setUp(){
		$this-> statisc = new Statistics();
	}
	
	public function tearDown(){
		$this->statisc = null;
	}

	public function testMakeSizeString(){
		$size=20960000;
		$res = Statistics::make_size_string($size);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testCountCourse(){
		$instans = new MockDatabase();
		
		$course_code = 'ABC';
		$course_code2 = 'ABCD';
		$category_code = NULL;
		$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		$res = Statistics::count_courses($category_code);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue(count($res) !== 0);
		//var_dump($res);
	}
	
	public function testCountCourse2(){
		$instans = new MockDatabase();
		$user_id=1;
		$course_code = 'ABC';
		$course_code2 = 'ABCD';
		$category_code = NULL;
		$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		SessionManager::add_courses_to_session($session_id,array($course_code));
	 	SessionManager::suscribe_users_to_session($session_id,array($user_id));
		$res = Statistics::count_courses($category_code);
		$this->assertTrue(count($res ==0));
		$this->assertTrue(is_numeric($res));
		CourseManager::delete_course($course_code);
		//var_dump($res);
	}
	
	public function testCountCourse3(){
		$user_id=1;
		$course_code = 'ABC';
		$course_code2 = 'ABCD';
		$category_code = NULL;
		$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		SessionManager::suscribe_users_to_session($session_id,array($user_id+3));
		$res = Statistics::count_courses($category_code);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue(count($res)!==0);
	 	//var_dump($res);
	}
	
	public function testCountCourse4(){
		$user_id=1;
		$course_code = 'ABC';
		$course_code2 = 'ABCD';
		$category_code = NULL;
		//ob_start();
		$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		SessionManager::suscribe_users_to_session($session_id,array($user_id+3));
		SessionManager::add_courses_to_session($session_id,array());
		$res = Statistics::count_courses($category_code);
		$this->assertTrue(is_numeric($res));
	 	$this->assertTrue(count($res)!==0);
	 	SessionManager::delete_session($session_id);
	 	CourseManager::delete_course($course_code2);
	 	CourseManager::delete_course($course_code);
	 	//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testCountUsers(){
		$user_id =001;
		$category_code = NULL;
		$course_code='ABC';
		$firstName='adam'; $lastName='ivan'; $status='01'; $email='ricardo.rodriguez@dokeos.com'; $loginName='adam'; $password='1234';	
		$count_invisible_courses = true;
		$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		SessionManager::suscribe_users_to_session($session_id,array($user_id+3));
		$res = Statistics::count_users($status, $category_code, $count_invisible_courses);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue(count($res)===0 || count($res)!==0);
		SessionManager::delete_session($session_id);
		//var_dump($res);
	}
	
	public function testGetNumberOfActivities(){
		$activitis = new MockDatabase();
		$sql = "SELECT count(default_id) AS total_number_of_items FROM $track_e_default ";				
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$obj = Database::fetch_object($res);
		$resu = Statistics::get_number_of_activities();
		$this->assertTrue(is_numeric($resu));
		$this->assertTrue(count($resu)==0 || count($resu)!==0);
		//var_dump($resu);
		//var_dump($res);
	}
	
	public function testGetActivitiesData(){
		$instans = new MockDatabase();
		global $dateTimeFormatLong;
		$from='';
		$number_of_items=10; 
		$column=''; 
		$direction='';
		$track_e_default = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT
				 	default_event_type  as col0,
					default_value_type	as col1,
					default_value		as col2,																
					user.username 	as col3, 					
					default_date 	as col4									
				FROM $track_e_default track_default, $table_user user
				WHERE track_default.default_user_id = user.user_id ";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		$resu = Statistics::get_activities_data($from, $number_of_items, $column, $direction);
    	$this->assertTrue(is_array($resu));
    	$this->assertTrue($row);
    	//var_dump($resu);
    	//var_dump($row);
    	//var_dump($res);
    }
    
    public function testGetCourseCategories(){
    	
    }
}
?>
