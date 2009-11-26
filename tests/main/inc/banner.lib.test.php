<?php

class TestBanner extends UnitTestCase{

	public function TestBanner(){

		$this->UnitTestCase('Determine the tabs function tests');

	}
	
	public function testGetTabs(){
		global $_course, $rootAdminWeb, $_user;
    	$res = get_tabs();
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
	}

}
?>
