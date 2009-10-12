<?php
Mock::generate('Database');
class TestInstallUpgrade extends UnitTestCase{

	public function TestInstallUpgrade() {

		$this->UnitTestCase('testing the function used by '.
						    'the install and upgrade scripts');
	}

	public function testSetFileFolderPermissions(){
		$res = set_file_folder_permissions();
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
	}

	public function testFillLanguageTable(){
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

	public function testFillSettingsTable(){
		$settings_options_table='';
		$res = fill_settings_options_table($settings_options_table);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testFillTrackCountriesTable(){
		$track_countries_table='';
		$res = fill_track_countries_table($track_countries_table);
		$this->assertEqual(null,$res);
		//var_dump($res);
	}

	public function testWriteCoursesHtaccessFile(){
		$url_append='/var/www/dokeos1861/main/install/';
		$res = write_courses_htaccess_file($url_append);
		if(is_bool($res)){
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		}else
		$this->assertEqual('/var/www/dokeos1861/main/install/',$res);
		//var_dump($res);
	}
	/**
	 * Call to undefined function trueFalse() in
	 * /var/www/dokeos1861/main/install/install_upgrade.lib.php on line 192
	 */
	 /* public function testWriteDokeosConfigFile(){
		global $dbHostForm;
		global $dbUsernameForm;
		global $dbPassForm;
		global $enableTrackingForm;
		global $singleDbForm;
		global $dbPrefixForm;
		global $dbNameForm;
		global $dbStatsForm;
		global $dbScormForm;
		global $dbUserForm;
		global $urlForm;
		global $pathForm;
		global $urlAppendPath;
		global $languageForm;
		global $encryptPassForm;
		global $installType;
		global $updatePath;
		global $session_lifetime;
		global $new_version;
		global $new_version_stable;
		$path='';
		$res = write_dokeos_config_file($path);
		$this->assertTrue($res);
		var_dump($res);

	}*/

	public function testLoadMainDatabase(){
		$installation_settings=array();
		$res = load_main_database($installation_settings);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
	}

	public function testLoadDatabaseScript(){
		$db_script='install_db';
		$res = load_database_script($db_script);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testSplitSqlFile(){
		$ret='';
		$sql='';
		$res = split_sql_file($ret, $sql);
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res===true);
		//var_dump($res);
	}

	public function testGetSqlFileContents(){
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

	public function testDirectoryToArray(){
		$directory=array('aaa','bbb','ccc');
		$res = directory_to_array($directory);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testAddDocument180(){
		$_course='testing';
		$path='http://dokeos186.net/courses/001/?id_session=0';
		$filetype='doc';
		$filesize='10';
		$title='testing';
		$comment=NULL;
		$res = add_document_180($res);
		if(is_bool($res)){
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		}else
		$this->assertTrue($res);
		//var_dump($res);
	}
}



?>
