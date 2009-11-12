<?php
require_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');
require_once(api_get_path(LIBRARY_PATH).'database.lib.php');

Mock::generate('DocumentManager');

class TestTracking extends UnitTestCase {

	function testchat_connections_during_last_x_days_by_course() {
		$course_code=001;
		$last_days=8;
		$this->tracking = new Tracking();
		$res=$this->tracking->chat_connections_during_last_x_days_by_course($course_code,$last_days);
		$this->assertTrue(is_object($this->tracking));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
        //var_dump($docman);
    }


	function testchat_last_connection() {
		$docman2 = new MockDocumentManager();
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');
		$student_id= $_POST['student_id'];
		$course_code=001;
		$this->tracking = new Tracking();
		$res=$this->tracking->chat_last_connection($student_id,$course_code);
		$docman2->expectOnce(CourseManager :: get_course_information($course_code));
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_object($docman2));
		if(!is_string($res))$this->assertTrue(is_null($res));
	}

	function testcount_course_per_student() {
		$user_id=1;
		$this->tracking = new Tracking();
		$res=$this->tracking->count_course_per_student($user_id);
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testcount_login_per_student() {
		$student_id=1;
		$course_code=001;
		$this->tracking = new Tracking();
		$res=$this->tracking->count_login_per_student($student_id, $course_code);
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testcount_number_of_forums_by_course() {
		$docman2 = new MockDocumentManager();
		$course_code='';
		$this->tracking = new Tracking();
		$res=$this->tracking->count_number_of_forums_by_course($course_code);
		$docman2->expectOnce(CourseManager :: get_course_information($course_code));
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_object($docman2));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testcount_number_of_posts_by_course() {
	 	$docman2 = new MockDocumentManager();
		$course_code='';
		$this->tracking = new Tracking();
		$res=$this->tracking->count_number_of_posts_by_course($course_code);
		$docman2->expectOnce(CourseManager :: get_course_information($course_code));
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_object($docman2));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testcount_number_of_threads_by_course() {
	 	$docman2 = new MockDocumentManager();
		$course_code='';
		$this->tracking = new Tracking();
		$res=$this->tracking->count_number_of_threads_by_course($course_code);
		$docman2->expectOnce(CourseManager :: get_course_information($course_code));
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_object($docman2));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testcount_student_assignments() {
	 	require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');
	 	$docman2 = new MockDocumentManager();
		$course_code='';
		$student_id='';
		$a_course = CourseManager :: get_course_information($course_code);
		$this->tracking = new Tracking();
		$res=$this->tracking->count_student_assignments($student_id, $course_code);
		$docman2->expectOnce(CourseManager :: get_course_information($course_code));
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_object($docman2));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testcount_student_downloaded_documents() {
	 	$student_id='';
	 	$course_code='';
	 	$this->tracking = new Tracking();
	 	$res=$this->tracking->count_student_downloaded_documents($student_id, $course_code);
	 	$this->assertTrue(is_object($this->tracking));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testcount_student_messages() {
	 	require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');
	 	$docman2 = new MockDocumentManager();
		$student_id='';
	 	$course_code='';
	 	$a_course = CourseManager :: get_course_information($course_code);
		$this->tracking = new Tracking();
		$res=$this->tracking->count_student_messages($student_id, $course_code);
		$docman2->expectOnce(CourseManager :: get_course_information($course_code));
		$this->assertTrue(is_object($this->tracking));
		$this->assertTrue(is_object($docman2));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testcount_student_visited_links() {
	 	$student_id='';
	 	$course_code='';
	 	$this->tracking = new Tracking();
	 	$res=$this->tracking->count_student_visited_links($student_id, $course_code);
	 	$this->assertTrue(is_object($this->tracking));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testget_average_test_scorm_and_lp () {
	 	$user_id='';
	 	$course_id='';
	 	$this->tracking = new Tracking();
	 	$res=$this->tracking->get_average_test_scorm_and_lp($user_id,$course_id);
	 	$this->assertTrue(is_object($this->tracking));

		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testget_avg_student_exercise_score() {
	 	$docman2 = new MockDocumentManager();
	 	$student_id='';
	 	$course_code='';
	 	$this->tracking = new Tracking();
	 	$res=$this->tracking->get_avg_student_exercise_score($student_id, $course_code);
	  	$docman2->expectOnce(CourseManager :: get_course_information($course_code));
	 	$this->assertTrue(is_object($this->tracking));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testget_avg_student_progress() {
		require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');
		$docman2 = new MockDocumentManager();
	 	$student_id='';
	 	$course_code='';
	 	$this->tracking = new Tracking();
	 	$res=$this->tracking->get_avg_student_progress($student_id, $course_code);
	 	$docman2->expectOnce(CourseManager :: get_course_information($course_code));
	 	$this->assertTrue(is_object($this->tracking));
		if(!is_numeric($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }

	 function testget_avg_student_score() {
	 	$docman = new Database();
	 	$student_id='';
	 	$course_code='';
	 	$this->tracking = new Tracking();
	 	$res=$this->tracking->get_avg_student_score($student_id, $course_code, $lp_ids=array());

		if(!is_string($res))$this->assertTrue(is_null($res));
		//var_dump($res);
	 }


	 function testget_course_list_in_session_from_student() {
	 	//setUp (not practical to have a real SetUp() here)
	 	$user_id = 1;
	 	$course_code = '';
	 	$course_code2 = '';
	 	// create one course and one session
	 	require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
		register_course($course_code,$course_code,$course_code,$course_code,$course_code,$course_code,$course_code,'english',$user_id);
	 	require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
	 	$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
	 	//var_dump($session_id);
/*
	 	// test that there is nothing in the table at first
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	//var_dump($res);
	 	// test that there is something after insert
	 	SessionManager::add_courses_to_session($session_id,array($course_code));
	 	SessionManager::suscribe_users_to_session($session_id,array($user_id));
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	// test that there is something after insert of a second course
		register_course($course_code2,$course_code2,$course_code2,$course_code2,$course_code2,$course_code2,$course_code2,'english',$user_id);
	 	SessionManager::add_courses_to_session($session_id,array($course_code2));
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	// test that adding a user doesn't change the results
	 	SessionManager::suscribe_users_to_session($session_id,array($user_id+3));
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	//destroy users, courses and sessions created (and their relations)
	 	//tearDown (not practical to have a real tearDown() here)
	 	SessionManager::suscribe_users_to_session($session_id,array());
	 	SessionManager::add_courses_to_session($session_id,array());
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	SessionManager::delete_session($session_id);
	 	CourseManager::delete_course($course_code2);
	 	CourseManager::delete_course($course_code);*/

	 }
	 /*
	 function testget_course_list_in_session_from_student2() {
	 	$user_id = 1;
	 	$course_code = 'ABC';
	 	$course_code2 = 'ABCD';
	 	$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
	 	// test that there is nothing in the table at first
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	var_dump($res);
	 }*/
	 /*
	 function testget_course_list_in_session_from_student3() {
	 	$user_id = 1;
	 	$course_code = 'ABC';
	 	$course_code2 = 'ABCD';
	 	$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
	 	// test that there is something after insert
	 	SessionManager::add_courses_to_session($session_id,array($course_code));
	 	SessionManager::suscribe_users_to_session($session_id,array($user_id));
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
//	 	CourseManager::delete_course($course_code);
	 	var_dump($res);
	 }*/
	 /*
	 function testget_course_list_in_session_from_student4() {
	  	$user_id = 1;
	 	$course_code = 'ABC';
	 	$course_code2 = 'ABCD';
	 	$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		// test that adding a user doesn't change the results
	 	SessionManager::suscribe_users_to_session($session_id,array($user_id+3));
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	var_dump($res);
	  }*/
	/*
	function testget_course_list_in_session_from_student5() {
		$user_id = 1;
	 	$course_code = 'ABC';
	 	$course_code2 = 'ABCD';
	 	//ob_start();
	 	$session_id = SessionManager::create_session($course_code,2009,07,20,2010,07,20,0,0,1,'coach');
		SessionManager::suscribe_users_to_session($session_id,array());
	 	SessionManager::add_courses_to_session($session_id,array());
	 	$res=Tracking::get_course_list_in_session_from_student($user_id, $session_id);
	 	$this->assertTrue(is_array($res));
	 	$this->assertTrue(count($res)==0);
	 	SessionManager::delete_session($session_id);
	 	CourseManager::delete_course($course_code2);
	 	CourseManager::delete_course($course_code);
	 	//ob_end_clean();
	}*/

	function testget_courses_followed_by_coach() {
		$coach_id='';
		$res=Tracking::get_courses_followed_by_coach($coach_id, $id_session='');
	 	if(!is_null($res))$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

	function testget_courses_list_from_session() {
		$session_id='';
		$res=Tracking::get_courses_list_from_session($session_id);
	 	if(!is_null($res))$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

	function testget_first_connection_date() {
		$student_id='';
		$res=Tracking::get_first_connection_date($student_id);
	 	if(!is_null($res))$this->assertTrue(is_string($res));
	 	//var_dump($res);
	}

	function testget_first_connection_date_on_the_course() {
		$student_id='';
		$course_code='';
		$res=Tracking::get_first_connection_date_on_the_course($student_id, $course_code);
	 	if(!is_null($res))$this->assertTrue(is_bool($res));
	 	//var_dump($res);
	}

	function testget_inactives_students_in_course() {
		$course_code='';
		$since='';
		$res=Tracking::get_inactives_students_in_course($course_code, $since, $session_id=0);
	 	if(!is_null($res))$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

	function testget_last_connection_date() {
		$student_id='';
		$res=Tracking::get_last_connection_date($student_id);
	 	if(!is_null($res))$this->assertTrue(is_string($res));
	 	//var_dump($res);
	}

	function testget_last_connection_date_on_the_course() {
		$student_id='';
		$course_code='';
		$res=Tracking::get_last_connection_date_on_the_course($student_id, $course_code);
	 	if(!is_null($res))$this->assertTrue(is_bool($res));
	 	//var_dump($res);
	}

	function testget_sessions_coached_by_user() {
		$coach_id='';
		$res=Tracking::get_sessions_coached_by_user($coach_id);
	 	if(!is_null($res))$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

	function testget_student_followed_by_coach() {
		$coach_id='';
		$res=Tracking::get_student_followed_by_coach($coach_id);
	 	if(!is_null($res))$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

	function testget_student_followed_by_coach_in_a_session() {
		$id_session='';
		$coach_id='';
		$res=Tracking::get_student_followed_by_coach_in_a_session($id_session, $coach_id);
	 	if(!is_null($res))$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

	function testget_student_followed_by_drh() {
		$hr_dept_id='';
		$res=Tracking::get_student_followed_by_drh($hr_dept_id);
	 	if(!is_null($res))$this->assertTrue(is_array($res));
	 	//var_dump($res);
	}

	function testget_time_spent_on_the_course() {
		$user_id='';
		$course_code='';
		$res=Tracking::get_time_spent_on_the_course($user_id, $course_code);
	 	if(!is_null($res))$this->assertTrue(is_numeric($res));
	 	//var_dump($res);
	}

	function testget_time_spent_on_the_platform(){
		$user_id='';
		$res=Tracking::get_time_spent_on_the_platform($user_id);
	 	if(!is_null($res))$this->assertTrue(is_numeric($res));
	 	//var_dump($res);
	}

	function testis_allowed_to_coach_student() {
		$coach_id='';
		$student_id='';
		$res=Tracking::is_allowed_to_coach_student($coach_id, $student_id);
	 	if(!is_null($res))$this->assertTrue(is_bool($res));
	 	//var_dump($res);
	}
}
?>
