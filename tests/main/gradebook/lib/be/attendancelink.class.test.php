<?php

class TestAttendanceLink extends UnitTestCase {

	public function TestAttendanceLink() {
		$this->UnitTestCase('Test Attendance Link');
	}

	public function __construct() {
        $this->UnitTestCase('Gradebook attendances library - main/gradebook/lib/be/attendancelink.class.test.php');
	    // The constructor acts like a global setUp for the class
		global $date;
		TestManager::create_test_course('COURSEATTENDANCELINK');
		$this->attendance = new AttendanceLink();
		$this->attendance->set_course_code('COURSEATTENDANCELINK');
		$this->attendance->set_id (1);
		$this->attendance->set_type (LINK_ATTENDANCE);
		$this->attendance->set_ref_id (1) ;
		$this->attendance->set_user_id (1);
		$this->attendance->set_category_id(1);
		$this->attendance->set_date ($date);
		$this->attendance->set_weight (1);
		$this->attendance->set_visible (1);
		$this->attendance->set_session_id(1);
	}

	public function testcalc_score() {
		$res = $this->attendance->calc_score($stud_id = null);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Generate an array of all attendances available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */

	public function testget_all_links() {
		$_SESSION['id_session'] = 1;
		$res = $this->attendance->get_all_links();
		if(!is_array($res)){
			$this->assertTrue(is_null($res));
		} else {
			$this->assertTrue(is_array($res));
		}
		$_SESSION['id_session'] = null;
		//var_dump($res);
	}

	/**
	 * @return string description
	 */

	public function testget_description() {
		$res = $this->attendance->get_description();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_link() {
		$res = $this->attendance->get_link();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_name() {
		$res = $this->attendance->get_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_not_created_links() {
		$res = $this->attendance->get_not_created_links();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_test_id() {
		$res = $this->attendance->get_test_id();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_type_name() {
		$res = $this->attendance->get_type_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
    * Has anyone done this exercise yet ?
    */

	public function testhas_results() {
		$res = $this->attendance->has_results();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testis_allowed_to_change_name() {
		$res = $this->attendance->is_allowed_to_change_name();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testis_valid_link() {
		$res = $this->attendance->is_valid_link();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testneeds_max() {
		$res = $this->attendance->needs_max();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testneeds_name_and_description() {
		$res = $this->attendance->needs_name_and_description();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testneeds_results() {
		$res = $this->attendance->needs_results();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function __destruct() {
		// The destructor acts like a global tearDown for the class
		TestManager::delete_test_course('COURSEATTENDANCELINK');
	}
}
?>
