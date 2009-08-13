<?php
require_once(api_get_path(SYS_CODE_PATH).'webservices/user_import/import.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'import.lib.php');
require_once(api_get_path(LIBRARY_PATH).'classmanager.lib.php');


class TestImpor extends UnitTestCase {
	
	function testcomplete_missing_data(){
		$user='admin';
		$res=complete_missing_data($user);
		$this->assertTrue(is_string($res));
		//var_dump($res);	
	}
	
	function testparse_csv_data() {
		$file='/var/www/1.csv';
		$res=parse_csv_data($file);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	
	/**
 	* Save the imported data
	*/
 
	function testsave_data() {
		global $users;
		$res=save_data($users);
		$this->assertTrue(is_null($res));
		var_dump($res);
	}
	
	function testvalidate_data() {
		global $defined_auth_sources, $users;
		$res=validate_data($users);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
