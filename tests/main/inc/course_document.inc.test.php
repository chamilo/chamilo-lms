<?php

class Testcdocu extends UnitTestCase{
	
	public function Testcdocu(){
		
		$this->UnitTestCase('Determine the course of document function tests');
	
	}
	
	public function testGetlist(){
		$directory='';
		$res = getlist($directory);
		if(is_bool($res)){
			$this->assertTrue(is_bool($res));
			$this->assertTrue($res ===false);
		}else{
			$this->assertTrue(is_array($res));
		}
		//var_dump($res);
	}
	
	public function testCheckAndCreateResourceDirectory(){
		global $_course, $_user;
		global $group_properties, $to_group_id;
		global $permissions_for_new_directories;
		ob_start();
		require_once(api_get_path(SYS_CODE_PATH).'inc/course_document.inc.php');
		ob_end_clean();
		$repository_path='';
		$resource_directory='';
		$resource_directory_name='';
		$res = check_and_create_resource_directory($repository_path, $resource_directory, $resource_directory_name);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true || $res === false);
		var_dump($res);		
	}
	
	
	
}



?>
