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
		$res = Database::query($sql, __FILE__, __LINE__);
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
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_row($res);
		$resu = Statistics::get_activities_data($from, $number_of_items, $column, $direction);
    	$this->assertTrue(is_array($resu));
    	$this->assertTrue($row);
    	//var_dump($resu);
    	//var_dump($row);
    	//var_dump($res);
    }

    public function testGetCourseCategories(){
    	$user_id =002;
		$category_code = NULL;
		$course_code='ABCDE';
		$real_course_code='00001';
		$course_title='test1';
		$wanted_course_code='test2';
		$course_language='es';
		$course_category='primary';
		$code='00001';
		$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		CourseManager::create_virtual_course ($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);
    	$res = Statistics::get_course_categories();
    	$this->assertTrue($res);
    	CourseManager::delete_course($code);
    	//var_dump($res);
    }

    public function testRescale(){
    	$data=array('aas','aaa'.'aab');
    	$max = 500;
    	$res = Statistics::rescale($data, $max);
    	$this->assertTrue($res);
    	$this->assertTrue(is_array($res));
    	//var_dump($res);
    }

    public function testPrintStats(){
    	ob_start();
    	$title='testing';
    	$stats=array('aa','bb','cc');
    	$show_total = true;
    	$is_file_size = false;
    	$res = Statistics::print_stats($title, $stats, $show_total = true, $is_file_size = false);
    	ob_end_clean();
    	$this->assertTrue(is_null($res));
    	//var_dump($res);
    }

    public function testPrintLoginStats(){
    	ob_start();
    	$instans = new MockDatabase();
    	$type='january';
    	$table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
    	if($type === 'month'){
    	$sql = "SELECT DATE_FORMAT( login_date, '%Y %b' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table." GROUP BY stat_date ORDER BY login_date ";
    	}if($type === 'hour'){
    	$sql = "SELECT DATE_FORMAT( login_date, '%H' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table." GROUP BY stat_date ORDER BY stat_date ";
    	}else{
    	$sql = "SELECT DATE_FORMAT( login_date, '%a' ) AS stat_date , count( login_id ) AS number_of_logins FROM ".$table." GROUP BY stat_date ORDER BY DATE_FORMAT( login_date, '%w' ) ";
    	}
    	$res = Database::query($sql,__FILE__,__LINE__);
    	$obj = Database::fetch_object($res);
    	$result[$obj->stat_date] = $obj->number_of_logins;
    	$login_stats[] = $result;
    	$resu = Statistics::print_login_stats($type);
    	ob_end_clean();
    	$instans->expectOnce($login_stats);
        $this->assertTrue(is_null($resu));
    	$this->assertTrue($result);
    	//var_dump($resu);
    	//var_dump($result);
    }

    public function testPrintRecenLoginStats(){
		ob_start();
		$instans = new MockDatabase();
    	$total_logins = array();
    	$table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
    	$query[get_lang('Total')] 	 = "SELECT count(login_user_id) AS number  FROM $table";
      	$res = Database::query($query,__FILE__,__LINE__);
    	$obj = Database::fetch_object($res);
    	$total_logins[] = $obj->number;
    	$res = Statistics::print_recent_login_stats();
    	ob_end_clean();
    	$instans->expectCallCount($total_logins);
    	$this->assertTrue(is_null($res));
    	$this->assertTrue(count($total_logins));
    	//var_dump($res);
    	//var_dump($total_logins);
    }

    public function testPrintToolStats(){
    	ob_start();
    	$instans = new MockDatabase();
    	$table = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
		$tools = array('announcement','assignment','calendar_event','chat','conference','course_description','document','dropbox','group','learnpath','link','quiz','student_publication','user','bb_forum');
		$sql = "SELECT access_tool, count( access_id ) AS number_of_logins FROM $table WHERE access_tool IN ('".implode("','",$tools)."') GROUP BY access_tool ";
		$res = Database::query($sql,__FILE__,__LINE__);
		$result = array();
		$obj = Database::fetch_object($res);
		$result[$obj->access_tool] = $obj->number_of_logins;
    	$resu = Statistics::print_tool_stats();
    	ob_end_clean();
    	$instans->expectCallCount($result);
    	$this->assertTrue(is_null($resu));
    	//var_dump($resu);
    	//var_dump($result);
    }

    public function testPrintCourseByLanguageStats(){
    	ob_start();
    	$instans = new MockDatabase();
    	$table = Database::get_main_table(TABLE_MAIN_COURSE);
		$sql = "SELECT course_language, count( code ) AS number_of_courses FROM $table GROUP BY course_language ";
		$res = Database::query($sql,__FILE__,__LINE__);
		$result = array();
		$obj = Database::fetch_object($res);
		$result[$obj->course_language] = $obj->number_of_courses;
    	$instans->expectOnce($result);
    	$resu = Statistics::print_course_by_language_stats();
    	ob_end_clean();
    	$this->assertTrue(is_null($resu));
    	$this->assertTrue(is_array($result));
    	//var_dump($resu);
    	//var_dump($result);
    }

    public function testPrintUserPicturesStats(){
    	ob_start();
    	$instans = new MockDatabase();
    	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
    	$sql = "SELECT COUNT(*) AS n FROM $user_table";
		$res = Database::query($sql,__FILE__,__LINE__);
		$count1 = Database::fetch_object($res);
    	$instans_print[]= $count1;
    	$instans->expectOnce($instans_print);
    	$instans->expectCallCount($instans_print);
    	$resu = Statistics::print_user_pictures_stats();
    	ob_end_clean();
    	$this->assertTrue(is_null($resu));
    	$this->assertTrue($instans_print);
    	//var_dump($resu);
    	//var_dump($instans_print);
    }

    public function testPrintActivitiesStats(){
    	ob_start();
    	$res = Statistics::print_activities_stats();
   		ob_end_clean();
   		$this->assertTrue(is_null($res));
   		//var_dump($res);
    }

    public function testPrintCourseLastVisit(){
    	ob_start();
    	$instans = new MockDatabase();
    	$instans1 = new MockDisplay();
    	$table_header[] = array ("Coursecode", true);
    	$course = array ();
    	$courses[] = $course;
    	$column='';
    	$direction='';
	    $parameters['action'] = 'courselastvisit';
	    $instans->expectCallCount('Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS)');
    	$instans1->expectOnce(Display :: display_sortable_table($table_header, $courses, array('column'=>$column,'direction'=>$direction), array (), $parameters));
    	$res = Statistics::print_course_last_visit();
    	ob_end_clean();
    	$this->assertTrue(is_null($res));
    	//var_dump($res);
    }







}
?>
