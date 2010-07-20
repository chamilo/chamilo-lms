<?php
require_once(api_get_path(SYS_CODE_PATH).'conference/get_translation.lib.php');

class TestGetTranslation extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Conference translation getter library - main/conference/get_translation.lib.test.php');
    }

	function testget_language_file_as_xml(){
		ob_start();
		$res=get_language_file_as_xml($language='english');
		ob_end_clean();
		$this->assertTrue(is_string($res));
	}
}