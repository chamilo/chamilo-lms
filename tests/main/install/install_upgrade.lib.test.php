<?php

class TestInstallUpgrade extends UnitTestCase{

	public function TestInstallUpgrade() {

		$this->UnitTestCase('testing the function used by '.
						    'the install and upgrade scripts');
	}

	public function testSetFileFolderPermissions() {
		$res = set_file_folder_permissions();
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
	}

	/*public function testWriteCoursesHtaccessFile() {
		$chamilo_path_folder = api_get_path(SYS_PATH);
		$url_append=$chamilo_path_folder.'main/install/';
		$res = write_courses_htaccess_file($url_append);
		if(is_bool($res)){
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		}else
		$this->assertEqual($chamilo_path_folder.'main/install/',$res);
		//var_dump($res);
	}*/

	public function testSplitSqlFile() {
		$ret='';
		$sql='';
		$res = split_sql_file($ret, $sql);
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res===true);
	}

	public function testMyDirectoryToArray() {
		$chamilo_path_folder= api_get_path(SYS_PATH);
		$directory= $chamilo_path_folder.'home';
		$res = my_directory_to_array($directory);
		$this->assertTrue(is_array($res));
	}

	/*
	public function testDeleteCourse() {
		global $cidReq;
		$resu = CourseManager::delete_course($cidReq);
		session_destroy();
	}*/


}
?>
