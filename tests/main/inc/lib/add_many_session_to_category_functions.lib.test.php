<?php

require_once (api_get_path(LIBRARY_PATH).'add_many_session_to_category_functions.lib.php');

class TestAddManySessionToCategoryFunctions extends UnitTestCase {

    public function __construct(){
        $this->UnitTestCase('Sessions to category library - main/inc/lib/add_many_session_to_category_functions.lib.test.php');
    }

	public function setUp(){
		$this-> AddManySessionToCategory = new AddManySessionToCategoryFunctions();
	}

	public function tearDown(){
		$this-> AddManySessionToCategory = null;

	}
	public function Testsearchcourses(){
		global $_courses, $tbl_course, $tbl_session, $id_session;
		$needle ='';
		$type ='';
		$res = AddManySessionToCategoryFunctions::search_courses($needle,$type);
		$this->assertTrue($res);
		$this->assertTrue(is_object($res));
		//var_dump($res);
	}
}
?>
