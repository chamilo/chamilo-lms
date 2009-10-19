<?php

class TestBanner extends UnitTestCase{

	public function TestBanner(){

		$this->UnitTestCase('Determine the tabs function tests');

	}
	public function testGetTabs(){
		global $_course, $rootAdminWeb, $_user;
		ob_start();
    	require_once(api_get_path(SYS_CODE_PATH).'inc/banner.inc.php');
    	ob_end_clean();
        $res = get_tabs();
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

}
?>
