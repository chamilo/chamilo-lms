<?php
require_once(api_get_path(LIBRARY_PATH).'debug.lib.php');


class TestDebug extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Debug helper library - main/inc/lib/debug.lib.inc.test.php');
    }
	function test_printr_is_string() {
		$variable='br';
		ob_start();
		Debug::printr($variable);
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue(is_scalar($res));
	}

	function test_debug_course_is_string() {
		global $_course;
		ob_start();
		Debug::course($_course);
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);

	}

	function test_debug_paths_is_string() {
		ob_start();
		Debug::debug_paths();
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue(is_scalar($res));
	}

	function test_debug_user_is_array() {
		global $_user;
		ob_start();
		Debug::user($_user);
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(array($_user));
		//var_dump($res);
	}

	function test_print_var_is_string() {
		$var='';
		GLOBAL $DEBUG;
		ob_start();
		Debug::print_var($var, $varName = "@");
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_string($res));
	}
}
?>
