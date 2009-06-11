<?php
require_once(api_get_path(LIBRARY_PATH).'debug.lib.inc.php');

class TestDebug extends UnitTestCase {
	
	function testDebug() {
		$variable='';
		$res= debug($variable);
		$this->assertTrue($res);
		
	}
	
	function debug_course() {
		
		
	}
	
	function debug_paths() {
		
		
	}
	
	function debug_user() {
		
		
	}
	
	function printVar() {
		
		
	}
		
	
	
} 
?>