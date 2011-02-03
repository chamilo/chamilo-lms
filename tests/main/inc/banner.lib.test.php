<?php

class TestBanner extends UnitTestCase{

	public function TestBanner(){
		$this->UnitTestCase('Tabs library - main/inc/banner.lib.test.php');
    }

	public function testGetTabs(){
		global $_course, $rootAdminWeb, $_user;
    	$res = get_tabs();
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
	}

}
?>
