<?php
require_once(api_get_path(LIBRARY_PATH).'security.lib.php');

class TestSecurity extends UnitTestCase {
	
	public $clean = array();
	
	function testcheck_abs_path() {
			$abs_path='';
			$checker_path='';	
			$res=Security::check_abs_path($abs_path,$checker_path);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
	}
	
	function testcheck_rel_path() {
			$rel_path='';
			$checker_path='';
			$res=Security::check_rel_path($rel_path,$checker_path);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
		
	}

	function testcheck_token() {
			$res=Security::check_token();
			$this->assertTrue(is_bool($res));
			//var_dump($res);	
	}

	function testcheck_ua() {
			$res=Security::check_ua();
			$this->assertTrue(is_bool($res));
			//var_dump($res);
	}
	function testclear_token() {
			$res=Security::clear_token();
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}
	
	function testfilter() {
			$var='';
			$res=Security::filter();
			$this->assertTrue(is_bool($res));
			//var_dump($res);
	}
	
	 function testfilter_filename($filename) {
	 		require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
	 		$filename='';
	 		$res=Security::filter_filename($filename);
			$this->assertTrue(is_string($res));
			//var_dump($res);
	 }
	
	 function testget() {
	 		$varname='';
	 		$res=Security::get($varname);
	 		if(!empty($res)) {
	 				$this->assertTrue(is_string($res));
	 		}	else { 
	 				$this->assertTrue(is_null($res));	
	 		}	
			//var_dump($res);
	 }
	 
	 function testget_HTML_token() {
	 		$res=Security::get_HTML_token();
			$this->assertTrue(is_string($res));
			//var_dump($res);
	 }
	
	function testget_token() {
			$res=Security::get_token();
			$this->assertTrue(is_string($res));
			//var_dump($res);
	}
	
	function testget_ua() {
			$res=Security::get_ua();
			$this->assertTrue(is_null($res));
			//var_dump($res);
	}
	
	function testremove_XSS() {
			global $charset;
			$var='';
			$res=Security::remove_XSS();
			if(!empty($res)) {
				$this->assertTrue(is_array($res));	
			} else {
				$this->assertTrue(is_string($res));	
			}
			var_dump($res);
	}
}
?>
