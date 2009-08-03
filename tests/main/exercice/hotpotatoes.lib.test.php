<?php
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php');

class TestHotpotatoes extends UnitTestCase {
	
	function testCheckImageName() {
		$imgparams='';
		$string='';
		$res=CheckImageName();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
	
	
	
	
	
}
?>
