<?php
class TestForumThreadLink extends UnitTestCase {

	public function TestForumThreadLink() {
		$this->UnitTestCase('Test Forum Thread Link');
	}

	public function __construct() {
        $this->UnitTestCase('Gradebook forum library - main/gradebook/lib/be/forumthreadlink.class.test.php');
	    // The constructor acts like a global setUp for the class
		TestManager::create_test_course('COURSEFORUMTHREAD');
		$this->forumthreadlink = new ForumThreadLink();
		$this->forumthreadlink->set_id(1);
		$this->forumthreadlink->set_type(5);
		$this->forumthreadlink->set_ref_id(1);
		$this->forumthreadlink->set_user_id(1);
		$this->forumthreadlink->set_course_code('COURSEFORUMTHREAD');
		$this->forumthreadlink->set_category_id(1);
		$this->forumthreadlink->set_date(date);
		$this->forumthreadlink->set_weight(1);
		$this->forumthreadlink->set_visible('visible');

	}

	public function testcalc_score() {
		$res = $this->forumthreadlink->calc_score(null);
		$this->assertNull($res);
		//var_dump($res);
		$res2 = $this->forumthreadlink->calc_score(1);
		$this->assertTrue(is_array($res2));
		//var_dump($res2);
	}



	public function __destruct() {
		// The destructor acts like a global tearDown for the class
		TestManager::delete_test_course('COURSEFORUMTHREAD');
	}

}
?>