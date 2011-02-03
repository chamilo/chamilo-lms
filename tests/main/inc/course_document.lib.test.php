<?php

class Testcdocu extends UnitTestCase{

    public function __construct(){
        $this->UnitTestCase('Course documents display library - main/inc/course_document.inc.test.php');
    }

	public function testGetlist(){
		global $is_allowed_in_course;
		$is_allowed_in_course = true;
		$directory = api_get_path(SYS_COURSE_PATH).'/document/audio/';
		$res = getlist($directory);
		if (is_bool($res)){
			$this->assertTrue(is_bool($res));
			$this->assertTrue($res ===false);
		} else{
			$this->assertTrue(is_null($res));
		}


	}

	public function testCheckAndCreateResourceDirectory(){
		global $_course, $_user;
		global $group_properties, $to_group_id;
		global $permissions_for_new_directories;
		$repository_path='';
		$resource_directory='';
		$resource_directory_name='';
		$res = check_and_create_resource_directory($repository_path, $resource_directory, $resource_directory_name);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
	}

}



?>
