<?php
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/abstractlink.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evallink.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';

class TestDropBoxLink extends UnitTestCase {
	
	public function TestDropBoxLink() {
		$this->UnitTestCase('Test Drop Box Link');
	}	

	public function __construct() {
		// The constructor acts like a global setUp for the class			
		require_once api_get_path(SYS_TEST_PATH).'setup.inc.php';
		$this->dropbox = new DropboxLink();
	}
	
	public function testget_type_name() {
		$res = $this->dropbox->get_type_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	
	public function testget_view_url() {
		$stud_id = 1;
		$res = $this->dropbox->get_view_url($stud_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}
	
	public function testis_allowed_to_change_name() {
		$res = $this->dropbox->is_allowed_to_change_name();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function __destruct() {
		// The destructor acts like a global tearDown for the class			
		require_once api_get_path(SYS_TEST_PATH).'teardown.inc.php';			
	}
}
?>
