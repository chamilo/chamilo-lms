<?php

require_once(api_get_path(LIBRARY_PATH).'add_courses_to_session_functions.lib.php');

class TestAddCoursesToSessionFunctions extends UnitTestCase{

    public function __construct(){
        $this->UnitTestCase('Courses-sessions library - main/inc/lib/add_courses_to_sessions_functions.lib.test.php');
    }

	public function setUp(){
		$this->TestAddCoursesToSessionFunctions = new AddCourseToSession();
	}

	public function tearDown(){
		$this->TestAddCoursesToSessionFunctions = null;
	}

	public function TestSearchCourses(){
		global $_courses;
		global $tbl_course, $tbl_session_rel_course, $id_session;
		$needle = '';
		$type = '';
		$res = AddCourseToSession::search_courses($needle, $type);
		$this->assertTrue($res);
		$this->assertTrue(is_object($res));
		$this->assertFalse(is_null($res));
		//var_dump($res);

	}

}

?>
