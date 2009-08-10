<?php
require_once(api_get_path(LIBRARY_PATH).'media.lib.php');

class TestMedia extends UnitTestCase {
	
		public function testget_path() {
			$result1 = Media::get_path(FLASH_PLAYER_AUDIO, REL_PATH);
			$result2 = Media::get_path(FLASH_PLAYER_VIDEO, WEB_PATH);
			$result3 = Media::get_path(SCRIPT_SWFOBJECT, SYS_PATH);
			$result4 = Media::get_path(SCRIPT_ASCIIMATHML, REL_PATH);
			$this->assertTrue(!empty($result1) && !empty($result2) && !empty($result3) && !empty($result4));
		}
}
?>
