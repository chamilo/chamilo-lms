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

	public function testFillTrackCountriesTable() {
		$track_countries_table='';
		$res = fill_track_countries_table($track_countries_table);
		$this->assertEqual(null,$res);
		//var_dump($res);
	}

	public function testWriteCoursesHtaccessFile() {
		$chamilo_path_folder = api_get_path(SYS_PATH);
		$url_append=$chamilo_path_folder.'main/install/';
		$res = write_courses_htaccess_file($url_append);
		if(is_bool($res)){
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		}else
		$this->assertEqual($chamilo_path_folder.'main/install/',$res);
		//var_dump($res);
	}
	//This function is ok but the problem is than create course with other code
	/*
	public function testLoadMainDatabase() {
		$chamilo_path_folder= api_get_path(SYS_CODE_PATH);
		$installation_settings['{ORGANISATIONNAME}'] = 'My Organisation';
		$installation_settings['{ORGANISATIONURL}'] = 'http://www.chamilo.org';
		$installation_settings['{CAMPUSNAME}'] = 'My campus';
		$installation_settings['{PLATFORMLANGUAGE}'] = 'spanish';
		$installation_settings['{ALLOWSELFREGISTRATION}'] = 1;
		$installation_settings['{ALLOWTEACHERSELFREGISTRATION}'] = 1;
		$installation_settings['{ADMINLASTNAME}'] = 'Doe';
		$installation_settings['{ADMINFIRSTNAME}'] = 'John';
		$installation_settings['{ADMINLOGIN}'] = 'admin';
		$installation_settings['{ADMINPASSWORD}'] = md5('admin');
		$installation_settings['{ADMINEMAIL}'] = '.localdomain';
		$installation_settings['{ADMINPHONE}'] = '(000) 001 02 03';
		$installation_settings['{PLATFORM_AUTH_SOURCE}'] = PLATFORM_AUTH_SOURCE;
		$installation_settings['{ADMINLANGUAGE}'] = 'spanish';
		$installation_settings['{HASHFUNCTIONMODE}'] = 'md5';
		$db_script = $chamilo_path_folder.'install/db_main.sql';
		$res = load_main_database($installation_settings,$db_script);
		$this->assertFalse($res);
	}
*/
	public function testLoadDatabaseScript() {
		$chamilo_path_folder= api_get_path(SYS_PATH);
		$db_script= $chamilo_path_folder.'main/install/db_stats.sql';
		$res = load_database_script($db_script);
		$this->assertTrue(is_null($res));
	}

	public function testSplitSqlFile() {
		$ret='';
		$sql='';
		$res = split_sql_file($ret, $sql);
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res===true);
	}

	public function testGetSqlFileContents() {
		ob_start();
		$file='txt';
		$section='course';
		$print_errors=true;
		$res = get_sql_file_contents($file,$section,$print_errors);
		ob_end_clean();
		if(is_bool($res));
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res===true || $res === false);
		//var_dump($res);
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
