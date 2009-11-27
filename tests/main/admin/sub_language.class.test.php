<?php
require_once (api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'admin/sub_language_add.php');

class TestSubLanguageManager extends UnitTestCase {

	public $clean = array();
	
	/**
	 *	Testing who get all data of dokeos folder 
	 *  @param String to url path folder
	 *  @param bool true if we only want the subname
	 *  @return Array All file of dokeos folder
	 */
	public function testget_all_data_of_dokeos_folder(){
		$dokeos_path_folder = '/var/www/dokeoshg1.8.6.1/';
		$res = SubLanguageManager::get_all_data_of_dokeos_folder($dokeos_path_folder, $only_main_name = false);
		$this->assertTrue(is_null($res));
		$this->assertFalse($res);
		//var_dump($res);
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
		$dokeos_path_file = '/var/www/dokeoshg1.8.6.1/main/lang/spanish/link.inc.php';
		$res = SubLanguageManager::get_all_language_variable_in_file($dokeos_path_file);
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	/**
	 * 
	 * 
	 */ 
	public function testadd_file_in_language_directory(){
		
		$dirname = '/var/www/';				
		$dokeos_path_file = $dirname.'/prueba1234.inc.php';
		$res = SubLanguageManager::add_file_in_language_directory($dokeos_path_file);
		
		unlink($dokeos_path_file);
		$this->assertNull($res);
		$this->assertTrue(is_null($res));
		//var_dump($res);
		
	}
	
	public function testwrite_data_in_file(){
		//create a new directory of sub language
		$dirname 	= '/var/www/prueba2';
		$filename 	= 'gradebook.inc.php';
		$file = $dirname.DIRECTORY_SEPARATOR.$filename;

		$path_sub_language = $dirname;
		$res1 = SubLanguageManager::add_directory_of_sub_language($path_sub_language);		
		
		//add file in language directory of sub language
		$dokeos_path_file = $file;
		$res2 = SubLanguageManager::add_file_in_language_directory($dokeos_path_file);

		//write data in file of sub language
		$path_file = $file;
		$new_sub_language='caucasico';
		$variable_sub_language='extremo';
		$res3 = SubLanguageManager::write_data_in_file($path_file,$new_sub_language,$variable_sub_language);
		
		//remove directory and its content of sub language
		if (file_exists($file)) {
			unlink($file);
			rmdir($dirname);	
		}	
		$this->assertFalse($res3);
		$this->assertTrue(is_null($res3));
		//var_dump($res1, $res2 , $res3);
		
	}
	
	/**
	 * Add directory for sub-language
	 * @param String The sub-language path directory ( /var/www/my_dokeos/main/lang/spanish_corporate )
	 * @return boolean
	 */
	public function testadd_directory_of_sub_language() {
		$path_sub_language = '/var/www/prueba1';
		$res = SubLanguageManager :: add_directory_of_sub_language($path_sub_language, 0777);
		if (is_bool($res)) {
			$this->assertTrue($path_sub_language);
		} else {
			$this->assertFalse($path_sub_language);
		}
		if (file_exists($path_sub_language)) {
			rmdir($path_sub_language);	
		}
	}

	/**
	 * Delete sub language of database
	 * @param Integer id's.
	 * @return null
 	 */ 
	public function Testremoved_sub_language() {
		$parent_id = $_GET['id'];
		$sub_language_id = $_GET['sub_language_id'];
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
		$path_sub_language = '/var/www/prueba123';
		$res = SubLanguageManager :: add_directory_of_sub_language($path_sub_language, 0777);
		// remove a directory of sub language
		if (file_exists($path_sub_language)) {
			rmdir($path_sub_language);	
		}
		$this->assertTrue(is_bool($res));
		// var_dump($res);
	}
}
?>
