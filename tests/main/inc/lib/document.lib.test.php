<?php
require_once(api_get_path(LIBRARY_PATH).'document.lib.php');

class TestDocumentManager extends UnitTestCase {
	
	/**
	 * This check if a document has the readonly property checked, then see if the user
	 * is the owner of this file, if all this is true then return true.
	 * 
	 * @param array  $_course
	 * @param int    $user_id id of the current user
	 * @param string $file path stored in the database
	 * @param int    $document_id in case you dont have the file path ,insert the id of the file here and leave $file in blank ''
	 * @return boolean true/false	 
	 **/
	public function testcheck_readonly() {
		$_course='';
		$user_id='';
		$file='';
		$res=DocumentManager::check_readonly($_course,$user_id,$file);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	
	
	
	
	
	
	
	
	
	
















	
}
?>
