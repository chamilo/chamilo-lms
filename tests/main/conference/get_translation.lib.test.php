<?php
require_once(api_get_path(SYS_CODE_PATH).'conference/get_translation.lib.php');

class TestGetTranslation extends UnitTestCase {

	function testget_language_file_as_xml(){
		ob_start();
		$res=get_language_file_as_xml($language='english');
		ob_end_clean();
		if(is_string($res)) {
			$this->assertFalse($res);
		} else {
		$this->assertTrue($res);
		}
		//var_dump($res);
	}
}
?>
