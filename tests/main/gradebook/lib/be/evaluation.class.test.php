<?php
class TestEvaluation extends UnitTestCase {

	public function TestEvaluation() {
		$this->UnitTestCase('Test Evaluation');
	}

	public function __construct() {
        $this->UnitTestCase('Gradebook evaluation library - main/gradebook/lib/be/evaluation.class.test.php');
	    // The constructor acts like a global setUp for the class
		global $date;
		TestManager::create_test_course('COURSEEVALUATION');
		$this->evaluation = new Evaluation();
		$this->evaluation-> set_id (1);
		$this->evaluation-> set_name ('test');
		$this->evaluation-> set_description ('test description');
		$this->evaluation-> set_user_id (1);
		$this->evaluation-> set_course_code ('COURSEEVALUATION');
		$this->evaluation-> set_category_id (1);
		$this->evaluation-> set_date ($date);
		$this->evaluation-> set_weight (1);
		$this->evaluation-> set_max (1);
		$this->evaluation-> set_visible (1);
	}

	/**
	* Insert this evaluation into the database
	*/

	public function testadd() {
		$res = $this->evaluation->add();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testadd_evaluation_log() {
		$idevaluation = 1;
		$res = $this->evaluation->add_evaluation_log($idevaluation);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	* Calculate the score of this evaluation
	* @param $stud_id student id (default: all students who have results for this eval - then the average is returned)
	* @return	array (score, max) if student is given
	* 			array (sum of scores, number of scores) otherwise
	* 			or null if no scores available
	*/

	public function testcalc_score() {
		$res = $this->evaluation->calc_score($stud_id = null);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	* Delete this evaluation from the database
	*/

	public function testdelete() {
		$res = $this->evaluation->delete();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
    * Delete all results for this evaluation
    */

    public function testdelete_results() {
		$res = $this->evaluation->delete_results();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
    * Delete this evaluation and all underlying results.
    */

    public function testdelete_with_results() {
		$res = $this->evaluation->delete_with_results();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Check if an evaluation name (with the same parent category) already exists
	 * @param $name name to check (if not given, the name property of this object will be checked)
	 * @param $parent parent category
	 */

	public function testdoes_name_exist() {
		$name = 'test name';
		$parent = 1;
		$res = $this->evaluation->does_name_exist($name, $parent);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
	* Find evaluations by name
	* @param string $name_mask search string
	* @return array evaluation objects matching the search criterium
	* @todo can be written more efficiently using a new (but very complex) sql query
	*/
	//problem with the call get_evaluations():  Call to a member function get_evaluations() on a non-object
	/*public function testfind_evaluations() {
		$name_mask = 'test name mask';
		$selectcat = 1;
		$res = Evaluation::find_evaluations($name_mask,$selectcat);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}*/

	public function testget_category_id() {
		$res = $this->evaluation->get_category_id();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testget_course_code() {
		$res = $this->evaluation->get_course_code();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_date() {
		$res = $this->evaluation->get_date();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testget_description() {
		$res = $this->evaluation->get_description();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
	 * Retrieve evaluations where a student has results for
	 * and return them as an array of Evaluation objects
	 * @param $cat_id parent category (use 'null' to retrieve them in all categories)
	 * @param $stud_id student id
	 */

	public function testget_evaluations_with_result_for_student() {
		$stud_id = 1;
		$res = $this->evaluation->get_evaluations_with_result_for_student($cat_id = null, $stud_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_icon_name() {
		$res = $this->evaluation->get_icon_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_id() {
		$res = $this->evaluation->get_id();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testget_item_type() {
		$res = $this->evaluation->get_item_type();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_max() {
		$res = $this->evaluation->get_max();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testget_name() {
		$res = $this->evaluation->get_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
     * Get a list of students that do not have a result record for this evaluation
     */

	public function testget_not_subscribed_students() {
		$res = $this->evaluation->get_not_subscribed_students($first_letter_user = '');
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	/**
	 * Generate an array of possible categories where this evaluation can be moved to.
	 * Notice: its own parent will be included in the list: it's up to the frontend
	 * to disable this element.
	 * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
	 */

	public function testget_target_categories() {
		$res = $this->evaluation->get_target_categories();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_user_id() {
		$res = $this->evaluation->get_user_id();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testget_weight() {
		$res = $this->evaluation->get_weight();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	/**
	 * Are there any results for this evaluation yet ?
	 * The 'max' property should not be changed then.
	 */

	public function testhas_results() {
		$res = $this->evaluation->has_results();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testis_valid_score() {
		$score = 1;
		$res = $this->evaluation->is_valid_score($score);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testis_visible() {
		$res = $this->evaluation->is_visible();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	/**
	 * Retrieve evaluations and return them as an array of Evaluation objects
	 * @param $id evaluation id
	 * @param $user_id user id (evaluation owner)
	 * @param $course_code course code
	 * @param $category_id parent category
	 * @param $visible visible
	 */

	public function testload() {
		$res = $this->evaluation->load($id = null, $user_id = null, $course_code = null, $category_id = null, $visible = null);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	/**
	 * Move this evaluation to the given category.
	 * If this evaluation moves from inside a course to outside,
	 * its course code is also changed.
	 */

	public function testmove_to_cat() {
		$cat = $this->evaluation;
		$res = $this->evaluation->move_to_cat($cat);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Update the properties of this evaluation in the database
	 */

	public function testsave() {
		$res = $this->evaluation->save();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_category_id() {
		$res = $this->evaluation->set_category_id(1);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_course_code() {
		$res = $this->evaluation->set_course_code('COURSEEVALUATION');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_date() {
		global $date;
		$res = $this->evaluation->set_date('02/02/2010');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_description() {
		$res = $this->evaluation->set_description('test description');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_id() {
		$res = $this->evaluation->set_id(1);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_max() {
		$res = $this->evaluation->set_max(1);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_name() {
		$res = $this->evaluation->set_name('test name');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_user_id() {
		$res = $this->evaluation->set_user_id(1);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_visible() {
		$res = $this->evaluation->set_visible(1);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testset_weight() {
		$res = $this->evaluation->set_weight(1);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function __destruct() {
		// The destructor acts like a global tearDown for the class
		TestManager::delete_test_course('COURSEEVALUATION');
	}
}
?>
