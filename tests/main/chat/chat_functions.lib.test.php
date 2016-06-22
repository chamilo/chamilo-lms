<?php
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

class TestChatFunctions extends UnitTestCase {

    public function __construct(){
        $this->UnitTestCase('Chat library - main/chat/chat_functions.lib.test.php');
    }

	public function setUp() {
		$this->tcourse = new CourseManager();
	}

	public function tearDown() {
		$this->tcourse = null;
	}

	function CreateChatConnection($database_name) {
		$session_id = 1;
		$tbl_chat_connected = Database::get_main_table(TABLE_MAIN_CHAT);
		$sql = "SELECT user_id FROM $tbl_chat_connected WHERE user_id = 1";
		$result = Database::query($sql);
		//The user_id exists so we must do an UPDATE and not a INSERT
		$current_time = date('Y-m-d H:i:s');
		if (Database::num_rows($result)==0) {
			$query="INSERT INTO $tbl_chat_connected(user_id,last_connection,session_id)
					VALUES(1,'$current_time','$session_id')";
		} else {
			$query="UPDATE $tbl_chat_connected set last_connection='".$current_time."'
					WHERE user_id=1 AND session_id='$session_id'";
		}
		Database::query($query);
	}
}
?>
