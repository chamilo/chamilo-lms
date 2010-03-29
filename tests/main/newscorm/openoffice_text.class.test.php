<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php');
require_once(api_get_path(SYS_CODE_PATH).'newscorm/openoffice_text.class.php');
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

class TestOpenOfficeTextClass extends UnitTestCase {

	public function testOpenOfficeText() {
		//ob_start();
		$res = OpenofficeText::make_lp($files=array());
		$this->assertTrue(is_bool($res)); 
		//ob_end_clean();
		//var_dump($res);
	}	
/*
	public function testDealPerChapter() {
		//ob_start();
		$obj = new OpenofficeText($split_steps=false, $course_code=null, $resource_id=null,$user_id=null); 
		$res = $obj->dealPerChapter($header = 'Header', $content = 'Content');
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}

	public function testDalPerPage() {
		//ob_start();
		$obj = new OpenofficeText($split_steps=false, $course_code=null, $resource_id=null,$user_id=null); 
		$res = $obj->dealPerPage($header = 'Header', $body= 'Body');
		$this->assertTrue(is_null($res)); 
		//ob_end_clean();
		//var_dump($res);
	}
*/
	public function testAddCommandParameters() {
		//ob_start();
		$res = OpenofficeText::add_command_parameters();
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}

	public function testFormatPageContent() {
		//ob_start();
		$res = OpenofficeText::format_page_content($header = 'Header', $content = 'Content');
		$this->assertTrue(is_string($res)); 
		//ob_end_clean();
		//var_dump($res);
	}	
}
?> 