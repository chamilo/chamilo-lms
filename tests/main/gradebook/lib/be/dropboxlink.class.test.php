<?php

class TestDropBoxLink extends UnitTestCase {

	public function TestDropBoxLink() {
		$this->UnitTestCase('Test Drop Box Link');
	}

	public function __construct() {
        $this->UnitTestCase('Gradebook dropbox library - main/gradebook/lib/be/dropboxlink.class.test.php');
	    // The constructor acts like a global setUp for the class
		TestManager::create_test_course('COURSEDROPBOXLINK');
		$this->dropbox = new DropboxLink();
		$this->dropbox->set_course_code('COURSEDROPBOXLINK');
		$this->dropbox->set_name('test');
	}

	public function testget_type_name() {
		$res = $this->dropbox->get_type_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_view_url() {
		$stud_id = 1;
		$res = $this->dropbox->get_view_url($stud_id);
		//$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testis_allowed_to_change_name() {
		$res = $this->dropbox->is_allowed_to_change_name();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function __destruct() {
		// The destructor acts like a global tearDown for the class
		TestManager::delete_test_course('COURSEDROPBOXLINK');
	}
}
?>
