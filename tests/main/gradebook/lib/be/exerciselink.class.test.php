<?php
class TestExerciseLink extends UnitTestCase {

	public function TestExerciseLink() {
		$this->UnitTestCase('Test Exercise Link');
	}

	public function __construct() {
        $this->UnitTestCase('Gradebook exercises library - main/gradebook/lib/be/exerciselink.class.test.php');
	    global $date;
		// The constructor acts like a global setUp for the class
		TestManager::create_test_course('COURSEEXERCISELINK');
		$this->exerciselink = new ExerciseLink();
		$this->exerciselink-> set_id (1);
		$this->exerciselink-> set_name ('test');
		$this->exerciselink-> set_description ('test description');
		$this->exerciselink-> set_user_id (1);
		$this->exerciselink-> set_course_code ('COURSEEXERCISELINK');
		$this->exerciselink-> set_category_id (1);
		$this->exerciselink-> set_date ($date);
		$this->exerciselink-> set_weight (1);
		$this->exerciselink-> set_max (1);
		$this->exerciselink-> set_visible (1);
	}

	/**
	 * Generate an array of all exercises available.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */

	public function testget_all_links() {
		$res = $this->exerciselink->get_all_links();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	/**
	 * Get the score of this exercise. Only the first attempts are taken into account.
	 * @param $stud_id student id (default: all students who have results - then the average is returned)
	 * @return	array (score, max) if student is given
	 * 			array (sum of scores, number of scores) otherwise
	 * 			or null if no scores available
	 */

	public function testcalc_score() {
		$res = $this->exerciselink->calc_score($stud_id = null);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}



	/**
     * Get description to display: same as exercise description
     */

	public function testget_description() {
		$res = $this->exerciselink->get_description();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
    * Get URL where to go to if the user clicks on the link.
    * First we go to exercise_jump.php and then to the result page.
    * Check this php file for more info.
    */

	public function testget_link() {
		$res = $this->exerciselink->get_link();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
     * Get name to display: same as exercise title
     */

    public function testget_name() {
		$res = $this->exerciselink->get_name();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Generate an array of exercises that a teacher hasn't created a link for.
	 * @return array 2-dimensional array - every element contains 2 subelements (id, name)
	 */

	public function testget_not_created_links() {
		$_SESSION['id_session'] = 1;
		$res = $this->exerciselink->get_not_created_links();
		$this->assertTrue(is_array($res));
		$_SESSION['id_session'] = null;
		//var_dump($res);
	}

	public function testget_type_name() {
		$res = $this->exerciselink->get_type_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
     * Has anyone done this exercise yet ?
     */

    public function testhas_results() {
		$res = $this->exerciselink->has_results();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testis_allowed_to_change_name() {
		$res = $this->exerciselink->is_allowed_to_change_name();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
    * Check if this still links to an exercise
    */

	public function testis_valid_link() {
		$res = $this->exerciselink->is_valid_link();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

    public function testneeds_max() {
		$res = $this->exerciselink->needs_max();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testneeds_name_and_description() {
		$res = $this->exerciselink->needs_name_and_description();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testneeds_results() {
		$res = $this->exerciselink->needs_results();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function __destruct() {
		// The destructor acts like a global tearDown for the class
		TestManager::delete_test_course('COURSEEXERCISELINK');
	}
}
?>
