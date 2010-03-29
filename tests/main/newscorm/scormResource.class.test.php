<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/scormResource.class.php');

class TestScormResource extends UnitTestCase {
	/*
	function testScormResource() {
		//ob_start();
		$res = scormResource::scormResource($type='manifest',&$element);
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}	
	*/
	public function testGetPath() {
		//ob_start();
		$res = scormResource::get_path();
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testGetScormType() {
		//ob_start();
		$res = scormResource::get_scorm_type();
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
}
?>