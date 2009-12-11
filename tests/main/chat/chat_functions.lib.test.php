<?php
require_once(api_get_path(SYS_CODE_PATH).'chat/chat_functions.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

Mock::generate('CourseManager');
class TestChatFunctions extends UnitTestCase {

	public function setUp() {
		$this->tcourse = new CourseManager();
	}
	
	public function tearDown() {
		$this->tcourse = null;
	}



	function testuser_connected_in_chat () {
		global $_configuration;

 		require_once api_get_path(SYS_PATH).'tests/main/inc/lib/add_course.lib.inc.test.php';
		
		// create a course
		
		$course_datos = array(
				'wanted_code'=> 'COD21',
				'title'=>'metodologia de calculo diferencial',
				'tutor_name'=>'R. J. Wolfagan',
				'category_code'=>'2121',
				'course_language'=>'english',
				'course_admin_id'=>'1211',
				'db_prefix'=> $_configuration['db_prefix'].'COD21',
				'db_prefix'=> $_configuration['db_prefix'].'COD21',
				'firstExpirationDelay'=>'112'
				);
		$res = create_course($course_datos['wanted_code'], $course_datos['title'],
							 $course_datos['tutor_name'], $course_datos['category_code'],
							 $course_datos['course_language'],$course_datos['course_admin_id'],
							 $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);
		if ($res) {
			$course_code = 'COD21';			
			$course_info = api_get_course_info($course_code);
		
		}
		$user_id=1;			
		$course_info = api_get_course_info($course_code);
		$database_name = $course_info['dbName'];
		$res = user_connected_in_chat($user_id,$database_name);
 		$this->assertTrue(is_bool($res)); 	
	}
	

	function testUsersListInChat () {
		$course_code = 'COD21';
		$course_info = api_get_course_info($course_code);
		$database_name = $course_info['dbName'];
		$res = users_list_in_chat($database_name);
 		$this->assertTrue(is_array($res));
 		//var_dump($res);
	}
	
	function CreateChatConnection($database_name) {
		$session_id = 1;
		$tbl_chat_connected = Database::get_course_chat_connected_table($database_name);
		$sql = "SELECT user_id FROM $tbl_chat_connected WHERE user_id = 1";
		$result = Database::query($sql);
		//The user_id exists so we must do an UPDATE and not a INSERT
		$current_time = date('Y-m-d H:i:s');
		if (Database::num_rows($result)==0) {
			$query="INSERT INTO $tbl_chat_connected(user_id,last_connection,session_id) VALUES(1,'$current_time','$session_id')";
		} else {
			$query="UPDATE $tbl_chat_connected set last_connection='".$current_time."' WHERE user_id=1 AND session_id='$session_id'";
		}
		Database::query($query,__FILE__,__LINE__);
	}
	

	function testExitOfChat () {
		$course_code = 'COD21';
		$course_info = api_get_course_info($course_code);
		$database_name = $course_info['dbName'];
		$this->CreateChatConnection($database_name);
		$user_id = 1;
		$res = exit_of_chat($user_id);
		//$resu = $this->tcourse->delete_course($course_code);
 		$this->assertTrue(is_null($res));
 		//var_dump($res);
	}
	
	
		function testDisconnectUserOfChat() {
			
		$_SESSION['is_courseAdmin'] = 1;
		$course_code = 'COD21';
		
		$course_info = api_get_course_info($course_code);
		$database_name = $course_info['dbName'];
		
		$this->CreateChatConnection($database_name);		
		$res = disconnect_user_of_chat($database_name);
		$this->assertTrue(is_null($res));
		
		$resu = $this->tcourse->delete_course($course_code);

	}	

	
}
?>
