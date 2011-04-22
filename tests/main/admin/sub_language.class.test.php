<?php
require_once (api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

class TestSubLanguageManager extends UnitTestCase {

	public $clean = array();

        public function __construct() {
            $this->UnitTestCase('Sublanguage Manager library - main/admin/sub_language.class.test.php');
        }
	/**
	 *	Testing who get all data of lang folder
	 *  @param String to url path folder
	 *  @param bool true if we only want the subname
	 *  @return Array All file of lang folder
	 */
	public function testget_lang_folder_files_list(){
		$path = api_get_path(SYS_LANG_PATH).'english';
		$res = SubLanguageManager::get_lang_folder_files_list($path, $only_main_name = false);
		$this->assertTrue(is_array($res));
		$this->assertTrue(count($res)>0);
	}

	/**
	 *
	 *
	 */
	public function testget_all_information_of_sub_language(){
		$parent_id = 13;
		$language_id = 10;
		$res = SubLanguageManager::get_all_information_of_sub_language($parent_id, $language_id);
        // under normal circumstances, there is no language 10 child of language 13
		$this->assertFalse($res);
	}

    /**
	 *
	 */
	public function testget_all_information_of_language(){
		$parent_id = 11;
		$res = SubLanguageManager::get_all_information_of_language($parent_id);
		$this->assertTrue(is_array($res));
	}

	/**
	 * Get variables within a language file
	 */
	public function testget_all_language_variable_in_file(){
	    $system_path_folder = api_get_path(SYS_LANG_PATH);
	    $system_path_file = $system_path_folder.'spanish/link.inc.php';
	    $res = SubLanguageManager::get_all_language_variable_in_file($system_path_file);
	    $this->assertTrue(is_array($res));
	}
    
    /**
     * Add directory for sub-language
     * @param String The sub-language path directory ( /var/www/my_lms/main/lang/spanish_corporate )
     * @return boolean
     */
    public function testadd_language_directory() {
        $res = SubLanguageManager :: add_language_directory('test');
        $this->assertTrue($res);
        $res = SubLanguageManager :: remove_language_directory('test');
    }

	/**
	 * 
	 */
	public function testadd_file_in_language_directory(){
        $res = SubLanguageManager :: add_language_directory('test');
        $this->assertTrue($res);
		$system_path_file = api_get_path(SYS_LANG_PATH).'test/spanish.inc.php';
		$res = SubLanguageManager::add_file_in_language_directory($system_path_file);
		$this->assertTrue($res);
        $res = SubLanguageManager :: remove_language_directory('test');
	}

	public function testwrite_data_in_file(){
		$dirname = api_get_path(SYS_LANG_PATH);
    	$file = $dirname.'spanish.inc.php';
		$path_file = $file;
		$new_sub_language='spanishtest';
		$variable_sub_language='test';
		$res = SubLanguageManager::write_data_in_file($path_file,$new_sub_language,$variable_sub_language);
		$this->assertTrue($res);
	}

	/**
	 * Delete sub language of database
	 * @param Integer id's.
	 * @return null
 	 */
	public function Testremove_sub_language() {
		$parent_id = '';
		$sub_language_id = 1;
		$res = SubLanguageManager :: remove_sub_language($parent_id, $sub_language_id);
		$this->assertFalse($res);
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
        $tbl_admin_languages    = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
        $sql='SELECT original_name FROM '.$tbl_admin_languages.' WHERE id=13';
        $rs=Database::query($sql);
        $name ='';
        if (Database::num_rows($rs)>0) {
            $name = Database::result($rs,0,'original_name');
        }        
		$res = SubLanguageManager :: get_name_of_language_by_id($language_id);
		$this->assertEqual($res,$name,'The language name from function does not match the database value');
	}

	/**
	 * Verified if language is an sub-language
	 * @param Integer
	 * @return Boolean
	 */
	public function Testcheck_if_language_is_sub_language_for_non_existing_sublanguage() {
		$language_id = 112;
		$res = SubLanguageManager :: check_if_language_is_sub_language($language_id);
		$this->assertFalse($res);
		//	var_dump($res);
	}

	/**
	 *
	 */
	public function Testcheck_if_language_is_father() {
		$language_id = 12;
		$res = SubLanguageManager :: check_if_language_is_father($language_id);
		$this->assertFalse($res);
		//	var_dump($res);
	}

	/**
	 *
	 */
	public function Testmake_language_unavailable_and_back() {
		$language_id = 11;
		$res = SubLanguageManager :: make_unavailable_language($language_id);
		$this->assertTrue($res,'Language could not be made unavailable');
        $res = SubLanguageManager :: make_available_language($language_id);
		// var_dump($res);
	}

	/**
	 *
	 */
	public function Testmake_available_language() {
		$language_id= 11;
        $res = SubLanguageManager :: make_unavailable_language($language_id);
		$res = SubLanguageManager :: make_available_language ($language_id);
        $this->assertTrue($res,'Language could not be made available');
		// var_dump($res);
	}
	/**
	 *
	 *
	 */
	public function Testset_platform_language_empty(){
        $backup = SubLanguageManager :: get_platform_language_id();
		$language_id = '';
		$res = SubLanguageManager :: set_platform_language($language_id);
		$this->assertFalse($res);
        $res = SubLanguageManager :: set_platform_language($backup);
		// var_dump($res);
	}
    public function Testset_platform_language_2(){
        $backup = SubLanguageManager :: get_platform_language_id();
        $language_id = 2;
        $res = SubLanguageManager :: set_platform_language($language_id);
        $this->assertTrue($res);
        $res = SubLanguageManager :: set_platform_language($backup);
        // var_dump($res);
    }
	/**
	 *
	 *
	 */
	public function Testremove_directory_of_sub_language(){
        $res = SubLanguageManager :: remove_language_directory('test');
        // create a directory of sub language
		$res = SubLanguageManager :: add_language_directory('test');
		$this->assertTrue($res);
		$res = SubLanguageManager :: remove_language_directory('test');
        // var_dump($res);
	}
}
?>
