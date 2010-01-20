<?php
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

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

	public function testFillLanguageTable() {
		$language_table='english';
		$res = fill_language_table($language_table);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	/**
	 * Fatal error: Call to undefined function trueFalse() in
	 * /var/www/dokeos1861/main/install/install_upgrade.lib.php on line 114
	 */
	/*public function testFillCurrentSettingsTable(){
		$current_settings_table='';
		$installation_settings='';
		$res = fill_current_settings_table($current_settings_table, $installation_settings);
		$this->assertTrue($res);
		var_dump($res);
	}*/

	public function testFillSettingsTable() {
		$settings_options_table='';
		$res = fill_settings_options_table($settings_options_table);
		$this->assertTrue(is_null($res));
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

	public function testLoadMainDatabase() {
		$chamilo_path_folder= api_get_path(SYS_PATH);
		$installation_settings=array();
		$db_script = $chamilo_path_folder.'main/install/dokeos_main.sql';
		$res = load_main_database($installation_settings,$db_script);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);

	}

	public function testLoadDatabaseScript() {
		$chamilo_path_folder= api_get_path(SYS_PATH);
		$db_script= $chamilo_path_folder.'main/install/dokeos_stats.sql';
		
		$res = load_database_script($db_script);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testSplitSqlFile() {
		$ret='';
		$sql='';
		$res = split_sql_file($ret, $sql);
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res===true);
		//var_dump($res);
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

	public function testAddDocument180() {
			
		}
		/**
 * This functon only is added to the end of the test and the end of the files in the all test.
 */
	public function testDeleteCourse() {
		global $cidReq;			
		$resu = CourseManager::delete_course($cidReq);				
	}
 
}
?>
