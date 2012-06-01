<?php
/**
 * To can test this file you need commet the line 125 in "online.inc.php"
 */
require_once(api_get_path(LIBRARY_PATH).'online.inc.php');

class TestOnline extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Online (chat) library - main/inc/lib/online.inc.test.php');
    }

	function testLoginCheck() {
		global $_course;
		$uid=1;
		$res=LoginCheck($uid);
		$this->assertTrue(is_null($res));
	}

	function testLoginDelete() {
		$user_id=1;
		$res=LoginDelete($user_id);
		$this->assertTrue(is_null($res));
	}

	function testonline_logout(){
		global $_configuration, $extAuthSource;		
		$res=online_logout(null, true);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testwho_is_online_in_this_course() {
		$uid='';
		$valid='';
		$rarray = array();
		$barray = array();
		$login_user_id= '';
		$login_date= '';
		array_push($rarray,$barray);
		array_push($barray,$login_user_id);
		array_push($barray,$login_date);

		$res=who_is_online_in_this_course($uid, $valid, $coursecode=null);
		if(!empty($barray)){
		$this->assertTrue(is_array($barray));
		//var_dump($str);
		} else {
			$this->assertTrue(is_bool($barray));
			//var_dump($rarray);
		}
		//var_dump($rarray);
	}

	function testwho_is_online()  {
		$valid='';
		$res=who_is_online($valid);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
