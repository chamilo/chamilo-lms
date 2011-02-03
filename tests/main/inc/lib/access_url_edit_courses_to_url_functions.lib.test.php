<?php
//require_once(api_get_path(INCLUDE_PATH).'global.inc.php');
//require_once(api_get_path(SYS_CODE_PATH).'admin/access_url_edit_courses_to_url.php');
require_once(api_get_path(LIBRARY_PATH).'access_url_edit_courses_to_url_functions.lib.php');

class TestAccessUrlEditCoursesToUrlFunctions extends UnitTestCase{

    public function __construct(){
        $this->UnitTestCase('Access URL courses library - main/inc/lib/access_url_edit_courses_to_url_functions.lib.test.php');
    }

	public function setUp(){
		$this->AccessUrlEditCoursesToUrl = new Accessurleditcoursestourl();
	}

	public function tearDown(){
		$this->AccessUrlEditCoursesToUrl = null;
	}

	public function TestSearchCourses(){
		global $_course, $user_id;
		$needle = '';
		$id = $_course['id'];
		$res = Accessurleditcoursestourl::search_courses($needle, $id);
		$this->assertTrue($res);
		$this->assertTrue(is_object($res));
		$this->assertFalse(is_null($res));
		//var_dump($res);
	}



}

?>
