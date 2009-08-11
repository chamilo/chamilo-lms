<?php
require_once(api_get_path(SYS_CODE_PATH).'chat/chat_functions.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php'); 

Mock::generate('CourseManager');
class TestChatFunctions extends UnitTestCase {
	
	function testdisconnect_user_of_chat() {
		$res = disconnect_user_of_chat();
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
	}
	
	function testexit_of_chat () {
		$docu = new MockCourseManager();
		$user_id=1;
		$res = exit_of_chat();
		$docu->expectOnce(CourseManager::get_courses_list_by_user_id($user_id),'admin');
 		$this->assertTrue(is_object($docu));
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
	}
	
	function testuser_connected_in_chat () {
		$user_id=1;
		$database_name='';
		$res = user_connected_in_chat($user_id,$database_name);
 		$this->assertTrue(is_bool($res));
 		//var_dump($res);
	}
	
	function testusers_list_in_chat () {
		$res = users_list_in_chat();
 		$this->assertTrue(is_array($res));
 		//var_dump($res);
	}
}
?>
