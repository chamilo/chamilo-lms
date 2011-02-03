<?php

require_once(api_get_path(LIBRARY_PATH).'access_url_edit_users_to_url_functions.lib.php');

class TestAccessUrlEditUsersToUrlFunctions extends UnitTestCase{

    public function __construct(){
        $this->UnitTestCase('Access URL users library - main/inc/lib/access_url_edit_users_to_url_functions.lib.test.php');
    }

	public function setUp(){
		$this->AccessUrlEditUsersToUrl = new AccessurleditUserstourl();
	}

	public function tearDown(){
		$this->AccessUrlEditUsersToUrl = null;
	}

	public function TestSearchUsers(){
		global $_courses;
		$needle = '';
		$id = $_courses['id'];
		$res = AccessurleditUserstourl::search_users($needle, $id);
		$this->assertTrue($res);
		$this->assertTrue(is_object($res));
		$this->assertFalse(is_null($res));
		//var_dump($res);

	}

}

?>
