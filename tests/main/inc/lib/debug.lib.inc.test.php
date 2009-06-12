<?php
require_once(api_get_path(LIBRARY_PATH).'debug.lib.inc.php');


class TestDebug extends UnitTestCase {
	
	function testDebugFunction() {
		$variable='br';
		ob_start();
		debug($variable);
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_string($res));	
		$this->assertTrue(is_scalar($res));
	}
	
	function testDebugCourse() {
		global $_course;
		ob_start();
		debug_course($_course);
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_numeric($_course));
	}
	
	function testDebugPaths() {
		ob_start();
		debug_paths();
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue(is_scalar($res));
	}
	
	function testDebugUser() {
		global $_user;
		ob_start();
		debug_user($_user);
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_array($_user));		
	}
	
	function testPrintVar() {
		$var='';
		GLOBAL $DEBUG;
		ob_start();
		printVar($var, $varName = "@");
		$res=ob_get_contents();
		ob_end_clean();
		//print_r($res);
		$this->assertTrue(is_string($res));
	}
} 
?>