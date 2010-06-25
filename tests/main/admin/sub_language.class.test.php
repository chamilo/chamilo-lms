<?php
require_once (api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

class TestSubLanguageManager extends UnitTestCase {

	public $clean = array();

	/**
	 *	Testing who get all data of dokeos folder
	 *  @param String to url path folder
	 *  @param bool true if we only want the subname
	 *  @return Array All file of dokeos folder
	 */
	public function testget_all_data_of_dokeos_folder(){
		$dokeos_path_folder = api_get_path(SYS_PATH);
		$res = SubLanguageManager::get_all_data_of_dokeos_folder($dokeos_path_folder, $only_main_name = false);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
	}

	/**
	 *
	 *
	 */
	public function testget_all_information_of_sub_language(){
		$parent_id = 13;
		$language_id = 10;
		$res = SubLanguageManager::get_all_information_of_sub_language($parent_id, $language_id);
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
		// var_dump($res);

	}

    /**
	 *
	 *
	 */
	public function testget_all_information_of_language(){
		$parent_id = 11;
		$res = SubLanguageManager::get_all_information_of_language($parent_id);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		// var_dump($res);

	}

	/**
	 *
	 *
	 */
	public function testget_all_language_variable_in_file(){
	  $system_path_folder = api_get_path(SYS_LANG_PATH);
	  $system_path_file = $system_path_folder.'spanish/link.inc.php';
	  $res = SubLanguageManager::get_all_language_variable_in_file($system_path_file);
	  if(is_array($res)) {
	  		$this->assertTrue($res);
	  }
	  $this->assertFalse($res);
	  //var_dump($res);
	 }

	/**
	 *
	 *
	 */
	public function testadd_file_in_language_directory(){
		$dirname = api_get_path(SYS_LANG_PATH);
		$perm_dir = substr(sprintf('%o', fileperms($dirname)), -4);
		if ($perm_dir != '0777') {
			$msg = "Error";
			$this->assertTrue(is_string($msg));
		} else {
			$system_path_file = $dirname.'spanish.inc.php';
			$res = SubLanguageManager::add_file_in_language_directory($system_path_file);
			unlink($system_path_file);
			$this->assertNull($res);
			$this->assertTrue(is_null($res));
		}
		//var_dump($res);
	}

	public function testwrite_data_in_file(){
		$dirname = api_get_path(SYS_LANG_PATH);
		$perm_dir = substr(sprintf('%o', fileperms($dirname)), -4);
		if ($perm_dir != '0777') {
			$msg = "Error";
			$this->assertTrue(is_string($msg));
		} else {
			$file = $dirname.'spanish.inc.php';
			$path_file = $file;
			$new_sub_language='spanishtest';
			$variable_sub_language='test';
			$res = SubLanguageManager::write_data_in_file($path_file,$new_sub_language,$variable_sub_language);
			$this->assertTrue(is_null($res));
			$this->assertNull($res);
		}
		//var_dump($res);



		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
	}

	/**
	 * Add directory for sub-language
	 * @param String The sub-language path directory ( /var/www/my_dokeos/main/lang/spanish_corporate )
	 * @return boolean
	 */
	public function testadd_directory_of_sub_language() {
		$path_sub_language = api_get_path(SYS_LANG_PATH).'test';
		$res = SubLanguageManager :: add_directory_of_sub_language($path_sub_language);
		if (is_bool($res)) {
			$this->assertTrue($path_sub_language);
		} else {
			$this->assertFalse($path_sub_language);
		}

	}

	/**
	 * Delete sub language of database
	 * @param Integer id's.
	 * @return null
 	 */
	public function Testremoved_sub_language() {
		$parent_id = '';
		$sub_language_id = 1;
		$res = SubLanguageManager :: removed_sub_language($parent_id, $sub_language_id);
		$this->assertNull($res);
		//	var_dump($res);
	}

	/**
	 * Test of Check if language exist by id
	 * @param Integer
	 * @return Boolean
	 */
	public function Testcheck_if_exist_language_by_id() {
		$language_id = 14;
		$res = SubLanguageManager :: check_if_exist_language_by_id($language_id);
		$this->assertTrue($res);
		//	var_dump($res);
	}

	/**
	 * Show the name of language by id
	 * @param Integer id
	 * @return String the name of language
	 */
	public function Testget_name_of_language_by_id() {
		$language_id = 13;
		$res = SubLanguageManager :: get_name_of_language_by_id($language_id);
		$this->assertTrue(is_string($res));
		// 	var_dump($res);
	}

	/**
	 * Verified if language is an sub-language
	 * @param Integer
	 * @return Boolean
	 */
	public function Testcheck_if_language_is_sub_language() {
		$language_id = 112;
		$res = SubLanguageManager :: check_if_language_is_sub_language($language_id);
		$this->assertTrue(is_bool($res));
		//	var_dump($res);
	}

	/**
	 *
	 */
	public function Testcheck_if_language_is_father() {
		$language_id = 12;
		$res = SubLanguageManager :: check_if_language_is_father($language_id);
		$this->assertTrue(is_bool($res));
		//	var_dump($res);
	}

	/**
	 *
	 */
	public function Testmake_unavailable_language() {
		$language_id = 15;
		$res = SubLanguageManager :: make_unavailable_language($language_id);
		$this->assertNull($res);
		$this->assertTrue(is_null($res));
		// var_dump($res);
	}

	/**
	 *
	 */
	public function Testmake_available_language() {
		$language_id= 11;
		$res = SubLanguageManager :: make_available_language ($language_id);
		$this->assertNull($res);
		$this->assertTrue(is_null($res));
		// var_dump($res);
	}
	/**
	 *
	 *
	 */
	public function Testset_platform_language(){
		$language_id = '';
		$res = SubLanguageManager :: set_platform_language($language_id);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		// var_dump($res);
	}
	/**
	 *
	 *
	 */
	public function Testremove_directory_of_sub_language(){
		// create a directory of sub language
		$path_sub_language = api_get_path(SYS_LANG_PATH).'test';
		//$path_sub_language = '/var/www/prueba123';
		//$res = SubLanguageManager :: add_directory_of_sub_language($path_sub_language, 0777);
		$res = SubLanguageManager :: add_directory_of_sub_language($path_sub_language, api_get_permissions_for_new_directories());
		// remove a directory of sub language
		if (file_exists($path_sub_language)) {
			rmdir($path_sub_language);
		}
		$this->assertTrue(is_bool($res));
		// var_dump($res);
	}
/*
	public function TestDeleteCourse(){
		$code = 'COURSETEST';
		$res = CourseManager::delete_course($code);
		$path = api_get_path(SYS_PATH).'archive';
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if (strpos($file,$code)!==false) {
					if (is_dir($path.'/'.$file)) {
						rmdirr($path.'/'.$file);
					}
				}
			}
			closedir($handle);
		}
	}
*/
}
?>
