<?php

class TestCategoryClass extends UnitTestCase {

	public function TestCategoryClass() {
		$this->UnitTestCase('Test Category Class');
	}

	public function __construct() {
        $this->UnitTestCase('Gradebook categories library - main/gradebook/lib/be/category.class.test.php');
	    // The constructor acts like a global setUp for the class
		TestManager::create_test_course('COURSECATEGORYCLASS');
		$this->category = new Category();
		$this->category->set_id(1);
		$this->category->set_name('test');
		$this->category->set_description('test description');
		$this->category->set_user_id(1);
		$this->category->set_course_code('COURSECATEGORYCLASS');
		$this->category->set_certificate_min_score(20);
		$this->category->set_parent_id(0);
		$this->category->set_session_id(1);
		$this->category->set_weight(1);
		$this->category->set_visible(1);
	}

	/**
     * Insert this category into the database
     */

	public function testadd() {
		$_SESSION['id_session'] = 1;
		$res = $this->category->add();
		$this->assertTrue(is_null($res));
		$_SESSION['id_session'] = null;
		//var_dump($res);
	}

	/**
	 * Apply the same visibility to every subcategory, evaluation and link
	 */

	public function testapply_visibility_to_children() {
		$res = $this->category->apply_visibility_to_children();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Calculate the score of this category
	 * @param $stud_id student id (default: all students - then the average is returned)
	 * @return	array (score sum, weight sum)
	 * 			or null if no scores available
	 */

	public function testcalc_score() {
		$res = $this->category->calc_score($stud_id = null);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Check if a category name (with the same parent category) already exists
	 * @param $name name to check (if not given, the name property of this object will be checked)
	 * @param $parent parent category
	 * @return bool
	 */

	public function testdoes_name_exist() {
		$name = 'test';
		$parent=1;
		$res = $this->category->does_name_exist($name, $parent);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
     * Find category by name
     * @param string $name_mask search string
     * @return array category objects matching the search criterium
     */

	public function testfind_category() {
		$name_mask = 'test';
		$allcat=array();
		$res = $this->category->find_category($name_mask,$allcat);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	/**
	 * Generate an array of all courses that a teacher is admin of.
	 * @return array 2-dimensional array - every element contains 2 subelements (code, title)
	 */

	public function testget_all_courses() {
		$user_id = 1;
		$res = $this->category->get_all_courses($user_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_certificate_min_score() {
		$res = $this->category->get_certificate_min_score();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testget_course_code() {
		$res = $this->category->get_course_code();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_description() {
		$res = $this->category->get_description();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	/**
	 * Get appropriate evaluations visible for the user
	 * @param int $stud_id student id (default: all students)
	 * @param boolean $recursive process subcategories (default: no recursion)
	 */

	public function testget_evaluations() {
		$res = $this->category->get_evaluations($stud_id = null, $recursive = false);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_icon_name() {
		$res = $this->category->get_icon_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_id() {
		$res = $this->category->get_id();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testget_independent_categories_with_result_for_student() {
		$cat_id=1;
		$stud_id=1;
		$res = $this->category->get_independent_categories_with_result_for_student($cat_id, $stud_id, $cats = array());
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_item_type() {
		$res = $this->category->get_item_type();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_links() {
		$res = $this->category->get_links($stud_id = null, $recursive = false);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_name() {
		$res = $this->category->get_name();
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testget_not_created_course_categories() {
		$user_id = 1;
		$res = $this->category->get_not_created_course_categories($user_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_parent_id() {
		$res = $this->category->get_parent_id();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	/**
	 * Return array of Category objects where a student is subscribed to.
	 * @param int       student id
     * @param string    Course code
     * @param int       Session id
	 */

	public function testget_root_categories_for_student() {
		$stud_id=1;
		$res = $this->category->get_root_categories_for_student($stud_id, $course_code = null, $session_id = null);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	/**
	 * Return array of Category objects where a teacher is admin for.
	 * @param int user id (to return everything, use 'null' here)
     * @param string course code (optional)
     * @param int session id (optional)
	 */

	public function testget_root_categories_for_teacher() {
		$user_id=1;
		$res = $this->category->get_root_categories_for_teacher($user_id, $course_code = null, $session_id = null);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_session_id() {
		$user_id=1;
		$res = $this->category->get_session_id($user_id, $course_code = null, $session_id = null);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	/**
	 * Get appropriate subcategories visible for the user (and optionally the course and session)
	 * @param int      $stud_id student id (default: all students)
     * @param string   Course code (optional)
     * @param int      Session ID (optional)
     * @return  array   Array of subcategories
	 */

	public function testget_subcategories() {
		$res = $this->category->get_subcategories($stud_id = null, $course_code = null, $session_id = null);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	/**
	 * Generate an array of possible categories where this category can be moved to.
	 * Notice: its own parent will be included in the list: it's up to the frontend
	 * to disable this element.
	 * @return array 2-dimensional array - every element contains 3 subelements (id, name, level)
	 */

	public function testget_target_categories() {
		$res = $this->category->get_target_categories();
		if(is_array($res)) {
		$this->assertTrue(is_array($res));
		} else {
			$this->assertTrue(is_null($res));
		}
		var_dump($res);

	}

	/**
	 * Generate an array of all categories the user can navigate to
	 */

	public function testget_tree() {
		$res = $this->category->get_tree();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testget_user_id() {
		$res = $this->category->get_user_id();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testget_weight() {
		$res = $this->category->get_weight();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	/**
	 * Check if a category contains evaluations with a result for a given student
	 */

	public function testhas_evaluations_with_results_for_student() {
		$stud_id = 1;
		$res = $this->category->has_evaluations_with_results_for_student($stud_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
	 * Checks if the certificate is available for the given user in this category
	 * @param	integer	User ID
	 * @return	boolean	True if conditions match, false if fails
	 */

	public function testis_certificate_available() {
		$user_id = 1;
		$res = $this->category->is_certificate_available($user_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
	 * Is this category a course ?
	 * A category is a course if it has a course code and no parent category.
	 */

	public function testis_course() {
		$res = $this->category->is_course();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
	 * Can this category be moved to somewhere else ?
	 * The root and courses cannot be moved.
	 */

	public function testis_movable() {
		$res = $this->category->is_movable();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testis_visible() {
		$res = $this->category->is_visible();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	/**
	 * Retrieve categories and return them as an array of Category objects
	 * @param int      category id
	 * @param int      user id (category owner)
	 * @param string   course code
	 * @param int      parent category
	 * @param bool     visible
     * @param int      session id (in case we are in a session)
     * @param bool     Whether to show all "session" categories (true) or hide them (false) in case there is no session id
	 */

	public function testload() {
		$res = $this->category->load($id = null, $user_id = null, $course_code = null, $parent_id = null, $visible = null, $session = null, $show_session_categories = true);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	/**
	 * Move this category to the given category.
	 * If this category moves from inside a course to outside,
	 * its course code must be changed, as well as the course code
	 * of all underlying categories and evaluations. All links will
	 * be deleted as well !
	 */

	public function testmove_to_cat() {
		$res = $this->category->move_to_cat($this->category);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}


	/**
	 * Update the properties of this category in the database
	 */

	public function testsave() {
		$res = $this->category->save();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Show message resource delete
	 */

	public function testshow_message_resource_delete() {
		$course_id = 1;
		$res = $this->category->show_message_resource_delete($course_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
	 * Shows all information of an category
	 */

	public function testshows_all_information_an_category() {
		$res = $this->category->shows_all_information_an_category($selectcat='');
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Not delete this category from the database,when visible=3 is category eliminated
	 */

	public function testupdate_category_delete() {
		$course_id = 1;
		$res = $this->category->update_category_delete($course_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Delete this evaluation from the database
	 */

	public function testdelete() {
		$res = $this->category->delete();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	/**
	 * Delete this category and every subcategory, evaluation and result inside
	 */

	public function testdelete_all() {
		$res = $this->category->delete_all();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function __destruct() {
		// The destructor acts like a global tearDown for the class
		TestManager::delete_test_course('COURSECATEGORYCLASS');
	}
}
?>
