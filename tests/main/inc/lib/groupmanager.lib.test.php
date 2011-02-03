<?php
require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'database.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'classmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once(api_get_path(LIBRARY_PATH).'tablesort.lib.php');


class TestGroupManager extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Group manager library - main/inc/lib/groupmanager.lib.test.php');
        TestManager::create_test_course('COURSEGROUP');
    }

    public function __destruct() {
        TestManager::delete_test_course('COURSEGROUP');
    }

	/**
	 *  Tests about groupmanager csv using many class database, class manager,
	 * file manager, course manager and table sort.
	 * @author Ricardo Rodriguez Salazar
	 */
	public function testGetGroupList(){
		global $_user;
		$res = GroupManager::get_group_list();
		$this->assertTrue(is_array($res));
	}

	public function testCreateGroupIsNumeric(){
		$name='1';
		$category_id='1';
		$tutor='';
		$places='1';
		global $_course, $_user;
		$res = GroupManager::create_group($name, $category_id, $tutor, $places);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res > 0);
	}

	public function testCreateSubgroups(){
		global $_course;
		$group_id = 1;
		$number_of_groups = 2;
		$res = GroupManager::create_subgroups($group_id, $number_of_groups);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testCreateGroupsFromVirtualCourses(){
		$res = GroupManager::create_groups_from_virtual_courses();
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testCreateClassGroups(){
		$category_id=2;
		$res =GroupManager::create_class_groups($category_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testDeleteGroups(){
		$group_ids='01';
		$course_code=null;
		$res =GroupManager::delete_groups($group_ids, $course_code = null);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testGetGroupProperties(){
		$group_id=01;
		$res = GroupManager::get_group_properties($group_id);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testSetGroupProperties(){
		$group= array('group_id'=>'01',
					  'name'=>'1',
					  'description'=>'',
					  'maximum_number_of_students'=>'2',
 					  'doc_state' =>'',
                      'work_state' =>'',
					  'calendar_state' =>'',
					  'announcements_state'=>'',
					  'forum_state'=>'',
					  'wiki_state'=>'',
					  'chat_state' =>'',
					  'self_registration_allowed'=>'',
					  'self_unregistration_allowed'=>'');
		$res = GroupManager::set_group_properties($group['group_id'], $group['name'], $group['description'],
													  $group['maximum_number_of_students'], $group['doc_state'],
													  $group['work_state'], $group['calendar_state'], $group['announcements_state'],
													  $group['forum_state'],$group['wiki_state'],$group['chat_state'], $group['self_registration_allowed'],
													  $group['self_unregistration_allowed']);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testGetNumberOfGroups(){
		$res = GroupManager::get_number_of_groups();
		$this->assertTrue(($res));
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testGetCategories(){
		$course_code ='COURSETEST';
		$course_db = 'chamilo_COURSETEST';
		$res = GroupManager::get_categories($course_code);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testGetCategory(){
		$id =2;
		$course_code = 'COURSETEST';
		$res = GroupManager::get_category($id,$course_code);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testGetCategoryFromGroup(){
		$course_code='COURSETEST';
		$group_id= 1;
		$course_db = 'chamilo_COURSETEST';
		$resu = GroupManager::get_category_from_group($group_id,$course_code);
		$this->assertTrue(is_bool($resu));
		//var_dump($res);
		//var_dump($cat);
	}

	public function testDeleteCategory(){
		$cat_id=1;
		$course_code = 'COURSETEST';
		$course_db = 'chamilo_COURSETEST';
		$res = GroupManager::delete_category($cat_id, $course_code);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
	}

	public function testCreateCategory(){
		$categ = array(
		'title'=>'group test',
		'description'=>'description test',
		'doc_state'=> 1,
		'work_state'=> 1,
		'calendar_state'=> 1,
		'announcements_state' => 1,
		'forum_state' => 1,
		'wiki_state' => 1,
		'chat_state' => 1,
		'self_registration_allowed' => TRUE,
		'self_unregistration_allowed' => FALSE,
		'maximum_number_of_students'=> 2,
		'groups_per_user'=>4);
		$res = GroupManager::create_category($categ['title'], $categ['description'],
		$categ['doc_state'], $categ['work_state'], $categ['calendar_state'],
		$categ['announcements_state'], $categ['forum_state'],$categ['wiki_state'],
		$categ['chat_state'],$categ['self_registration_allowed'],$categ['self_unregistration_allowed'],
		$categ['maximum_number_of_students'],$categ['groups_per_user']);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);

	}

	public function testUpdateCategory(){
		$categ = array(
		'id'=>2,
		'title'=>'DefaultGroupCategory',
		'description'=>'xxxxx',
		'doc_state'=>'xxxx',
		'work_state'=>'xxxxx',
		'calendar_state'=>'',
		'announcements_state'=>'',
		'forum_state'=>'',
		'wiki_state'=>'',
		'chat_state' =>'',
		'self_registration_allowed'=>'',
		'self_unregistration_allowed'=>'',
		'maximum_number_of_students'=>'',
		'groups_per_user'=>'0');
		$res = GroupManager::update_category($categ['id'], $categ['title'], $categ['description'],
		$categ['doc_state'], $categ['work_state'], $categ['calendar_state'], $categ['announcements_state'],
		$categ['forum_state'],$categ['wiki_state'], $categ['chat_state'],$categ['self_registration_allowed'],$categ['self_unregistration_allowed'],
		$categ['maximum_number_of_students'],$categ['groups_per_user']);
		$this->assertTrue(is_null($res));
		$this->assertTrue($res ===null);
	}

	public function testGetCurrenMaxGroupsPerUser(){
		$category_id = 2;
		$course_code = 'COURSEGROUP';
		$res =GroupManager::get_current_max_groups_per_user($category_id = null, $course_code = null);
		$this->assertTrue(is_Null($res));
	}

	public function testSwapCategoryOrder(){
		$id1= 1;
		$id2= 3;
		$res = GroupManager::swap_category_order($id1,$id2);
		$this->assertFalse($res);
		$this->assertNull($res,true);
	}

	public function testGetUsers(){
		$group_id= 1;
		$res =GroupManager::get_users($group_id);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res ===array());
	}

	public function testFillGroups(){
		global $_course;
		$group_ids= 2;
		$res = GroupManager::fill_groups($group_ids);
		$this->assertNull($res);
		$this->assertEqual($res,0);
	}

	public function testNumberOfStudents(){
		global $_course;
		$group_id= 2;
		$res = GroupManager::number_of_students($group_id);
		$this->assertFalse($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testMaximumNumberOfStudents(){
		global $_course;
		$group_id = 2;
		$res =GroupManager::maximum_number_of_students($group_id);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testUserInNumberOfGroups(){
		global $_course;
		$user_id= 1;
		$cat_id = 6;
		$res = GroupManager::user_in_number_of_groups($user_id,$cat_id);
		$this->assertTrue(is_numeric($cat_id));
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testIsSelfRegistrationAllowed(){
		$user_id = 1;
		$group_id = 6;
		$res = GroupManager::is_self_registration_allowed($user_id,$group_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testIsSelfUnregistrationAllowed(){
		$user_id = 2 ;
		$group_id = 6;
		$res =GroupManager::is_self_unregistration_allowed($user_id,$group_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testIsSubscribed(){
		$db_result = 2;
		$user_id = 2;
		$group_id = 6;
		$res = GroupManager::is_subscribed($user_id, $group_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testCanUserSubscribe(){
		global $user_id;
		$group_id = 2;
		global $_course;
		$res = GroupManager::can_user_subscribe($user_id, $group_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testCanUserUnsubscribe(){
		global $user_id;
		$group_id = 6;
		$res = GroupManager::can_user_unsubscribe($user_id, $group_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testGetSubscribedUsers(){
		$group_id = 2;
		$res = GroupManager::get_subscribed_users($group_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testGetSubscribedTutors(){
		$group_id = 2;
		$res = GroupManager::get_subscribed_tutors($group_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testSubscribeUsers(){
		global $user_ids;
		$group_id = 2;
		$res = GroupManager::subscribe_users($user_ids, $group_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testSubscribeTutors(){
		global $user_id;
		$group_id='6';
		$res &= GroupManager::subscribe_tutors($user_id, $group_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testUnsubscribeUsers(){
		$user_ids = array(5);
		$group_id = 5;
		$res &= GroupManager::unsubscribe_users($user_ids, $group_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testUnsubscribeAllUsers(){
		$group_ids=array(2);
		$res = GroupManager::unsubscribe_all_users($group_ids);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testUnsubscribeAllTutors(){
		$group_ids =array(6,9,10,11,14,15,16,19);
		$res = GroupManager::unsubscribe_all_tutors($group_ids);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testIsTutorOfGroup(){
		global $user_id;
		$group_id=2;
		$res = GroupManager::is_tutor_of_group($user_id,$group_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testIsUserInGroup(){
		global $user_id;
		$group_id= 4;
		$res =GroupManager::is_user_in_group($user_id, $group_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testGetAllTutors(){
		$res =GroupManager::get_all_tutors();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testIsTutor(){
		global $user_id, $_course;
		$res = GroupManager::is_tutor($user_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);

	}

	public function testGetGroupIds(){
		global $user_id;
		$course_db= 'chamilo_COURSETEST';
		$res = GroupManager::get_group_ids($course_db,$user_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testGetCompleteListOfUsersThatCanBeAddedToGroup(){
		global $_course, $_user;
		$course_code= 'chamilo_COURSETEST';
		$group_id=2;
		$res = GroupManager::get_complete_list_of_users_that_can_be_added_to_group($course_code, $group_id);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testfilter_duplicates(){
		$user_array_in = '';
		$compare_field = '';
		$res = GroupManager::filter_duplicates($user_array_in, $compare_field);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testFilterUsersAlreadyInGroup(){
		global $_course;
		$user_array_in = array();
		$group_id = 2;
		$res = GroupManager::filter_users_already_in_group($user_array_in, $group_id);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
	}

	public function testFilterOnlyStudents(){
		$user_array_in=array();
		$res= GroupManager::filter_only_students($user_array_in);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testUserHasAccess(){
		global $user_id;
		$group_id='5';
		$tool='wiki_state';
		$res = GroupManager::user_has_access($user_id, $group_id, $tool);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testGetUserGroupName(){
		global $user_id;
		$res=GroupManager::get_user_group_name($user_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
/*
	public function TestDeleteCourse(){
		$code = 'COURSETEST';
		$res = CourseManager::delete_course($code);
		$path = api_get_path(SYS_PATH).'archive';
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if (strpos($file,$code)!==false) {
					if (is_dir($path.'/'.$file)) {
						rmdirr($path.'/'.$file);
					}
				}
			}
			closedir($handle);
		}
	}
*/
}
?>
