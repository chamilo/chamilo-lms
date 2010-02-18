<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/scormOrganization.class.php');

class TestScormOrganization extends UnitTestCase {

	public function testget_flat_items_list() {
		//ob_start();
		$obj = new scormOrganization($type='manifest',&$element,$scorm_charset='UTF-8'); 
		$res = $obj->get_flat_items_list();
		$this->assertTrue(is_array($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
	
	public function testGetScormType() {
		//ob_start();
		$res = scormOrganization::get_name();
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}

	public function testGetRef() {
		//ob_start();
		$res = scormOrganization::get_ref();
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}

	public function testSetName() {
		//ob_start();
		$res = scormOrganization::set_name($title = 'Titulo');
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
}
?>