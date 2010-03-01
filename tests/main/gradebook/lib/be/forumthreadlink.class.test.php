<?php
class TestForumThreadLink extends UnitTestCase {
	
	public function TestForumThreadLink() {
		$this->UnitTestCase('Test Forum Thread Link');
	}

	public function __construct() {
		// The constructor acts like a global setUp for the class
		TestManager::create_test_course('COURSEFORUMTHREAD');			
	}
	
	
	
	
	public function __destruct() {
		// The destructor acts like a global tearDown for the class			
		TestManager::delete_test_course('COURSEFORUMTHREAD');			
	}
	
}
?>