<?php
require_once(api_get_path(SYS_CODE_PATH).'auth/openid/xrds.lib.php');

class TestXrds extends UnitTestCase {
	
	function testxrds_cdata() {
		global $xrds_open_elements, $xrds_services, $xrds_current_service;
		$parser='';
		$data='';
		$res=_xrds_cdata($parser, $data);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function test_xrds_element_end() {
		 global $xrds_open_elements, $xrds_services, $xrds_current_service;
		 $parser='';
		 $name='';
		 $xrds_current_service['version'] = 2;
		 $xrds_current_service['version'] = 1;
		 $xrds_services[] = $xrds_current_service;
		 $xrds_current_service= array();
		 $res=_xrds_element_end($parser, $name);
		 $this->assertTrue(is_null($res));
		 //var_dump($xrds_current_service);
	}
	
	function test_xrds_element_start() {
		global $xrds_open_elements;
		$parser='';
		$name='';
		$attribs='';
		$xrds_open_elements[] = _xrds_strip_namespace($name);
		$res=_xrds_element_start($parser, $name);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function test_xrds_strip_namespace() {
		$name='';
		$res=_xrds_strip_namespace($name);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}		
}
?>
