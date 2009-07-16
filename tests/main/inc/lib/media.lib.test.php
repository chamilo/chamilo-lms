<?php
require_once(api_get_path(LIBRARY_PATH).'media.lib.php');
define('FLASH_PLAYER_AUDIO', 'FLASH_PLAYER_AUDIO');
define('FLASH_PLAYER_VIDEO', 'FLASH_PLAYER_VIDEO');
define('SCRIPT_SWFOBJECT', 'SCRIPT_SWFOBJECT');


class TestMedia extends UnitTestCase {
	
		public function testget_path() {
			$media_resource='';
			$res=Media::get_path($media_resource,$path_type = REL_PATH);
			$this->assertTrue(is_string($res));
			//var_dump($res);
		}
}
?>
