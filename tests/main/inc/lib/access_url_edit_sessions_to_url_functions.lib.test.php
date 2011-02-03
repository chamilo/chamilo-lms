<?php

require_once(api_get_path(LIBRARY_PATH).'access_url_edit_sessions_to_url_functions.lib.php');

class TestAccessUrlEditSessionsToUrlFunctions extends UnitTestCase{

    public function __construct(){
        $this->UnitTestCase('Access URL sessions library - main/inc/lib/access_url_edit_sessions_to_url_functions.lib.test.php');
    }

	public function setUp(){
		$this->AccessUrlEditSessionsToUrl = new Accessurleditsessionstourl();
	}

	public function tearDown(){
		$this->AccessUrlEditSessionsToUrl = null;
	}

	public function TestSearchSessions(){
		global $_courses;
		$needle = '';
		$id = $_courses['id'];
		$res = Accessurleditsessionstourl::search_sessions($needle, $id);
		$this->assertTrue($res);
		$this->assertTrue(is_object($res));
		$this->assertFalse(is_null($res));
		//var_dump($res);

	}

}
?>
