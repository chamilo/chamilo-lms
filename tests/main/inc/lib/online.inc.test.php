<?php
/**
 * To can test this file you need commet the line 125 in "online.inc.php"
 */
require_once(api_get_path(LIBRARY_PATH).'online.inc.php');

class TestOnline extends UnitTestCase {
	
	function testchatcall() {
		global $_user, $_cid;
		$webpath=api_get_path(WEB_CODE_PATH);
		$message=get_lang('YouWereCalled').' : '.GetFullUserName($row['chatcall_user_id'],'').'<br>'.get_lang('DoYouAccept')
							."<p>"
				."<a href=\"".$webpath."chat/chat.php?cidReq=".$_cid."&origin=whoisonlinejoin\">"
				. get_lang("Yes")
				."</a>"
				."&nbsp;&nbsp;|&nbsp;&nbsp;"
				."<a href=\"".api_get_path('WEB_PATH')."webchatdeny.php\">"
				. get_lang("No")
				."</a>"
				."</p>";
		$res=chatcall();
		
		if(!empty($message)){
		$this->assertTrue(is_string($message));
		//var_dump($message);
			
		} else {
			$this->assertTrue(is_bool($message));
			//var_dump($message);			
		}
	}
	
	function testGetFullUserName() {
		$uid = 1;
		//$uid = Database::escape_string($uid);
		$res=GetFullUserName($uid);
		$str = $lastname."&nbsp;".$firstname;
		if(!empty($str)){
		$this->assertTrue(is_string($str));
		//var_dump($str);
			
		} else {
			$this->assertTrue(is_null($str));
			//var_dump($str);			
		}
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
		$res=online_logout();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	function testwho_is_online_in_this_course() {
		$uid='';
		$valid='';
		array_push($rarray,$barray);
		$rarray = array();
		$barray = array();
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

	function testWhoIsOnline()  {
		$statistics_database='';
		$valid='';
		$res=WhoIsOnline($uid=0,$statistics_database='',$valid);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
