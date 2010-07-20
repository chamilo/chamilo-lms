<?php
require_once(api_get_path(LIBRARY_PATH).'security.lib.php');
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

class TestSecurity extends UnitTestCase {

	public $clean = array();

    public function __construct() {
        $this->UnitTestCase('Security library - main/inc/lib/security.lib.test.php');
    }

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
			$var ='';
			$type='string';
			$options=array();
			$res=Security::filter($var, $type, $options);
			$this->assertTrue(is_bool($res));
			//var_dump($res);
	}

	function testfilter_filename() {
	 		$filename = 'security/.htaccess';
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
			$var ='';
			$user_status=ANONYMOUS;
			$res=Security::remove_XSS($var,$user_status=ANONYMOUS);
			if(!empty($res)) {
				$this->assertTrue(is_array($res));
			} else {
				$this->assertTrue(is_string($res));
			}
			//var_dump($res);
	}
}
?>
