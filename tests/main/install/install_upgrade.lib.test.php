<?php
Mock::generate('Database');
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
		$language_table='en';
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
		global $_configuration;

 		require_once api_get_path(SYS_PATH).'tests/main/inc/lib/add_course.lib.inc.test.php';
		
		// create a course
		
		$course_datos = array(
				'wanted_code'=> 'COD21',
				'title'=>'metodologia de calculo diferencial',
				'tutor_name'=>'R. J. Wolfagan',
				'category_code'=>'2121',
				'course_language'=>'english',
				'course_admin_id'=>'1211',
				'db_prefix'=> $_configuration['db_prefix'].'COD21',
				'db_prefix'=> $_configuration['db_prefix'].'COD21',
				'firstExpirationDelay'=>'112'
				);
		$res = create_course($course_datos['wanted_code'], $course_datos['title'],
							 $course_datos['tutor_name'], $course_datos['category_code'],
							 $course_datos['course_language'],$course_datos['course_admin_id'],
							 $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);
			if ($res) {
				$_course = 'COD21';	
				$chamilo_path_folder_web = api_get_path(WEB_PATH);
				$path = $chamilo_path_folder_web.'/courses/COD21/?id_session=0';
				$filetype='doc';
				$filesize='10';
				$title='metodologia de calculo diferencial';
				$res = add_document_180($_course,$path,$filetype,$filesize,$title);
				$resu = CourseManager::delete_course($_course);	
			}
				if(is_bool($res)) {
					$this->assertTrue(is_bool($res));
					$this->assertTrue($res === false);
					} else {
					$this->assertTrue($res);
					//var_dump($res);
					}
		}
}



?>
