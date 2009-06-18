<?php
require_once(api_get_path(LIBRARY_PATH).'display.lib.php');

class TestDisplay extends UnitTestCase {
	
	public function testdisplay_introduction_section() {
		$tool='';
		$res=$this->testdisplay_introduction_section();
		$this->assertTrue(($res));
	}
}