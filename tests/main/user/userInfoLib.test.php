<?php
require_once(api_get_path(SYS_CODE_PATH).'user/userInfoLib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

class TestUserInfoLib extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('User info library - main/user/userInfoLib.test.php');
    }

	/**
 	* clean the content of a bloc for information category
 	*/

	function testcleanout_cat_content(){
		global $TBL_USERINFO_CONTENT;
		$user_id=1;
		$definition_id=1;
		$res=cleanout_cat_content($user_id, $definition_id);
		$this->assertTrue(($res));
		//var_dump($res);
	}

	/**
	* create a new category definition for the user information
 	*/

	function testcreate_cat_def() {
		global $TBL_USERINFO_DEF;
		$res=create_cat_def($title="test", $comment="comment test", $nbline="5");
		$this->assertTrue(($res));
		//var_dump($res);
	}

	/**
 	* Edit a bloc for information category
	*/

	function testedit_cat_content() {
		global $TBL_USERINFO_CONTENT;
		$definition_id=1;
		$user_id=1;
		$res=edit_cat_content($definition_id, $user_id, $content ="", $user_ip="");
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
 	* modify the definition of a user information category
 	*/

	function testedit_cat_def() {
		$id=1;
		$title='test';
		$comment='comment test';
		$nbline=2;
		$res=edit_cat_def($id, $title, $comment, $nbline);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
 	* fill a bloc for information category
 	*/

	function testfill_new_cat_content() {
		$definition_id='';
		$user_id=1;
		$res=fill_new_cat_content($definition_id, $user_id, $content="", $user_ip="");
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
 	* get the user content of a categories plus the categories definition
	*/

	function testget_cat_content() {
		$userId=1;
		$catId=1;
		$res=get_cat_content($userId, $catId);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testget_cat_def() {
		$catId=1;
		$res=get_cat_def($catId);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testget_cat_def_list() {
		$res=get_cat_def_list();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testget_course_user_info() {
		$user_id=1;
		$res=get_course_user_info($user_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testget_main_user_info() {
		$user_id=1;
		$courseCode='TEST';
		$res=get_main_user_info($user_id,$courseCode);
		if(!is_bool($res))$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testhtmlize() {
		$phrase='test';
		$res=htmlize($phrase);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	function testmove_cat_rank() {
		$id=1;
		$direction='up';
		$res=move_cat_rank($id, $direction);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

/*
	function testmove_cat_rank_by_rank()  {
		$rank=5;
		$direction='up';
		$res=move_cat_rank_by_rank($rank, $direction);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
*/
	/**
 	* remove a category from the category list
 	* @param  - int $id - id of the category
 	*				or "ALL" for all category
 	* @param  - boolean $force - FALSE (default) : prevents removal if users have
 	*                            already fill this category
 	*                            TRUE : bypass user content existence check
 	* @param  - int $nbline - lines number for the field the user will fill.
 	* @return - bollean  - TRUE if succeed, ELSE otherwise
	 */
	function testremove_cat_def() {
		$id=1;
		$res=remove_cat_def($id, $force = false);
		if(!is_null($res))$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
	 * @author Hugues Peeters - peeters@ipm.ucl.ac.be
	 * @param  int     $user_id
	 * @param  string  $course_code
	 * @param  array   $properties - should contain 'role', 'status', 'tutor_id'
	 * @return boolean true if succeed false otherwise
	 */
	function testupdate_user_course_properties() {
		$user_id=1;
		$course_code='test';
		$properties=array();
		$res=update_user_course_properties($user_id, $course_code, $properties);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	/**
 * This functon only is added to the end of the test and the end of the files in the all test.
 */
	/*public function testDeleteCourse() {
		global $cidReq;
		$resu = CourseManager::delete_course($cidReq);
	}*/
}
?>
