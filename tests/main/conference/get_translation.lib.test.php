<?php
require_once(api_get_path(SYS_CODE_PATH).'conference/get_translation.lib.php');

class TestGetTranslation extends UnitTestCase {
	
	function testget_language_file_as_xml(){
		$res=get_language_file_as_xml($language='english');
		$this->assertTrue(($res));
		//var_dump($res);
	}
}
?>
