<?php
require_once(api_get_path(SYS_CODE_PATH).'auth/openid/xrds.lib.php');

class TestXrds extends UnitTestCase {

    public function __construct(){
        $this->UnitTestCase('XRDS library for OpenID - main/auth/openid/xrds.lib.test.php');
    }
	/*
	function testxrds_cdata() {
		global $xrds_open_elements, $xrds_services, $xrds_current_service;
		$parser='';
		$data='';
		$res=_xrds_cdata(&$parser, $data);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	*/

	function testxrdsparse() {

		$xml = <<<XML
		<xml version="1.0">
		<users>
		<fname>Christian</fname>
		<lname>Fasa Fasa</lname>
		</users>
XML;

		$parser = xrds_parse($xml);
		if (is_resource($parser)) {
			$this->assertTrue(is_resource($parser));
		} else {
			$this->assertTrue(is_null($parser));
		}
	}
/*
	function test_xrds_element_end() {
		 global $xrds_open_elements, $xrds_services, $xrds_current_service;
		 $parser='';
		 $name='';
		 $xrds_current_service['version'] = 2;
		 $xrds_current_service['version'] = 1;
		 $xrds_services[] = $xrds_current_service;
		 $xrds_current_service= array();
		 $res=_xrds_element_end(&$parser, $name);
		 $this->assertTrue(is_null($res));
		 //var_dump($xrds_current_service);
	}

	function test_xrds_element_start() {

		global $xrds_open_elements;

		$name='';
		$attribs='';

		$res=_xrds_element_start(&$parser, $name, $attribs);

		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function test_xrds_strip_namespace() {
		$name='';
		$res=_xrds_strip_namespace($name);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}*/
}
?>
