<?php
require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'database.lib.php');
require_once(api_get_path(LIBRARY_PATH).'classmanager.lib.php');
require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'tablesort.lib.php');
require_once(dirname(__FILE__).'/../../../simpletest/mock_objects.php');

Mock::generate('Database');
Mock::generate('ClassManager');
Mock::generate('FileManager');
Mock::generate('CourseManager');
Mock::generate('TableSort');
$_course = api_get_course_info('0001');


class TestGroupManager extends UnitTestCase {
	/**
	 *  Test about groupmanager csv using many class database, class manager,
	 * file manager, course manager and table sort.
	 * @author Ricardo Rodriguez Salazar
	 */

	/*
	  function testExportTableCsv() {
        $docman = new MockDocumentManager();
		$data = array();
		$filename = 'export';
		$this->export = new Export();
		$res=$this->export->export_table_csv($data,$filename);
        $docman->expectOnce('DocumentManager::file_send_for_download',array($filename,true,$filename.'.csv'));
		$this->assertTrue(is_object($this->export));
        //var_dump($docman);
        //var_dump($export);
    }
	 */
	public function testGetGroupList(){
		global $_user;
		$res = GroupManager::get_group_list();
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testCreateGroup(){
		$name='1';
		$category_id='1';
		$tutor='';
		$places='1';
		global $_course, $_user;
		$res = GroupManager::create_group($name, $category_id, $tutor, $places);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testCreateSubgroups(){
		$group_id = 2;
		$number_of_groups=3;
		$res = GroupManager::create_subgroups($group_id, $number_of_groups);
		$this->assertTrue(is_null($res));
		$this->assertTrue(GroupManager::create_subgroups($res)===null);
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
		$this->assertTrue(GroupManager::create_class_groups($category_id) === array());
		//var_dump($res);
	}

	public function testDeleteGroups(){
		$fmanager = new MockFileManager();
		$dbase = new MockDatabase();
		$group_ids='01';
		$course_code=null;
		$res =GroupManager::delete_groups($group_ids, $course_code = null);
		$fmanager->expectOnce('FileManager :: mkdirs($group_garbage, $perm);');
		$dbase->expectOnce('Database::affected_rows()');
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
					  'self_registration_allowed'=>'',
					  'self_unregistration_allowed'=>'');
		$res = GroupManager::set_group_properties($group['group_id'], $group['name'], $group['description'],
													  $group['maximum_number_of_students'], $group['doc_state'],
													  $group['work_state'], $group['calendar_state'], $group['announcements_state'],
													  $group['forum_state'],$group['wiki_state'], $group['self_registration_allowed'],
													  $group['self_unregistration_allowed']);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testGetNumberOfGroups(){
		$dbase = new MockDataBase();
		$res = GroupManager::get_number_of_groups();
		$dbase->expectOnce('Database :: get_course_table(TABLE_GROUP)');
		$dbase->expectOnce('Database::fetch_object($res)');
		$dbase->expectOnce('$obj->number_of_groups');
		$this->assertTrue(is_null($res));
		//$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testGetCategories(){
		$course_code ='COD128983';
		$course_db = '';
		$res = GroupManager::get_categories($course_code);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testGetCategory(){
		$dbase = new MockDataBase();
		$id =2;
		$course_code =null;
		$res = GroupManager::get_category($id,$course_code);
		$dbase->expectOnce('Database::fetch_array($res)');
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testGetCategoryFromGroup(){
		$course_code='';
		$group_id='';
		$course_db = '';
		$sql = "SELECT 1 ";
		$res = Database::query($sql);
		$cat = Database::fetch_array($res);
		$resu = GroupManager::get_category_from_group($group_id,$course_code);
		$this->assertTrue(is_bool($resu));
		$this->assertTrue(is_array($cat));
		//var_dump($res);
		//var_dump($cat);
	}

	public function testDeleteCategory(){
		$cat_id=1;
		$course_code =null;
		$course_db = 'z22COD12A945';
		$res = GroupManager::delete_category($cat_id, $course_code);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
		//var_dump($res);
	}

	public function testCreateCategory(){
		$categ = array(
		'title'=>'DefaultGroupCategory',
		'description'=>'xxxxx',
		'doc_state'=>'xxxx',
		'work_state'=>'xxxxx',
		'calendar_state'=>'',
		'announcements_state'=>'',
		'forum_state'=>'',
		'wiki_state'=>'',
		'self_registration_allowed'=>'',
		'self_unregistration_allowed'=>'',
		'maximum_number_of_students'=>'',
		'groups_per_user'=>'0');
		$res = GroupManager::create_category($categ['title'], $categ['description'],
		$categ['doc_state'], $categ['work_state'], $categ['calendar_state'], $categ['announcements_state'],
		$categ['forum_state'],$categ['wiki_state'],$categ['self_registration_allowed'],$categ['self_unregistration_allowed'],
		$categ['maximum_number_of_students'],$categ['groups_per_user']);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);

	}

	public function testUpdateCategory(){
		$categ = array(
		'id'=>'1',
		'title'=>'DefaultGroupCategory',
		'description'=>'xxxxx',
		'doc_state'=>'xxxx',
		'work_state'=>'xxxxx',
		'calendar_state'=>'',
		'announcements_state'=>'',
		'forum_state'=>'',
		'wiki_state'=>'',
		'self_registration_allowed'=>'',
		'self_unregistration_allowed'=>'',
		'maximum_number_of_students'=>'',
		'groups_per_user'=>'0');
		$res = GroupManager::update_category($categ['id'], $categ['title'], $categ['description'],
		$categ['doc_state'], $categ['work_state'], $categ['calendar_state'], $categ['announcements_state'],
		$categ['forum_state'],$categ['wiki_state'],$categ['self_registration_allowed'],$categ['self_unregistration_allowed'],
		$categ['maximum_number_of_students'],$categ['groups_per_user']);
		$this->assertTrue(is_null($res));
		$this->assertTrue($res ===null);
		//var_dump($res);
	}

	public function testGetCurrenMaxGroupsPerUser(){
		$category_id = null;
		$course_code = null;
		$course_db='';
		$res =GroupManager::get_current_max_groups_per_user($category_id = null, $course_code = null);
		$this->assertTrue(is_Null($res));
		//var_dump($res);
	}

	public function testSwapCategoryOrder(){
		$id1='2';
		$id2=null;
		$res = GroupManager::swap_category_order($id1,$id2);
		$this->assertFalse($res);
		$this->assertNull($res,true);
		//var_dump($res);
	}

	public function testGetUsers(){
		$group_id='1';
		$res =GroupManager::get_users($group_id);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res ===array());
		//var_dump($res);
	}

	public function testFillGroups(){
		$group_ids='2';
		global $_course;
		$res = GroupManager::fill_groups($group_ids);
		$this->assertNull($res);
		$this->assertEqual($res,0);
		//var_dump($res);
	}
	/*
	public function testNumberOfStudents(){
				/*
		$connection = &new MockDatabase($this);
        $connection->setReturnValue('get_course_table', 'dokeos_0001.group_rel_user');
        $connection->get_course_table();


		$group_id='2';
		$_course = api_get_course_info('0001');
		$res = $this->gManager->number_of_students($group_id);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		var_dump($res);
	}
	*/

	public function testMaximumNumberOfStudents(){
		$group_id ='2';
		$_course = api_get_course_info('0001');
		$res =GroupManager::maximum_number_of_students($group_id);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function testUserInNumberOfGroups(){
		$user_id='1';
		$cat_id = '6';
		//$_course = api_get_course_info('0001');
		$res = GroupManager::user_in_number_of_groups($user_id,$cat_id);
		$this->assertTrue(is_numeric($cat_id));
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testIsSelfRegistrationAllowed(){
		$user_id='1';
		$group_id='6';
		$res = GroupManager::is_self_registration_allowed($user_id,$group_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testIsSelfUnregistrationAllowed(){
		$user_id='2';
		$group_id='6';
		$res =GroupManager::is_self_unregistration_allowed($user_id,$group_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testIsSubscribed(){
		$dbase = new MockDataBase();
		$db_result ='2';
		$user_id='2';
		$group_id='6';
		$res = GroupManager::is_subscribed($user_id, $group_id);
		$dbase->expectOnce('Database::fetch_array($res)');
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testCanUserSubscribe(){
		$user_id='2';
		$group_id='2';
		global $_course;
		$res = GroupManager::can_user_subscribe($user_id, $group_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testCanUserUnsubscribe(){
		$user_id ='6';
		$group_id='6';
		$res = GroupManager::can_user_unsubscribe($user_id, $group_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testGetSubscribedUsers(){
		$group_id='2';
		$res = GroupManager::get_subscribed_users($group_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testGetSubscribedTutors(){
		$group_id='2';
		$res = GroupManager::get_subscribed_tutors($group_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testSubscribeUsers(){
		$user_ids = '2';
		$group_id= '2';
		$res = GroupManager::subscribe_users($user_ids, $group_id);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testSubscribeTutors(){
		$user_ids='2';
		$group_id='6';
		$res &= GroupManager::subscribe_tutors($user_ids, $group_id);
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
		$group_ids=array(2,);
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
		$user_id=2;
		$group_id=2;
		$res = GroupManager::is_tutor_of_group($user_id,$group_id);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testIsUserInGroup(){
		$user_id=  2;
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
		$user_id = 2;
		global $_course;
		$res = GroupManager::is_tutor($user_id);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);

	}

	public function testGetGroupIds(){
		$course_db='';
		$user_id=2;
		$res = GroupManager::get_group_ids($course_db,$user_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testGetCompleteListOfUsersThatCanBeAddedToGroup(){
		global $_course, $_user;
		$course_code=0001;
		$group_id=2;
		$res = GroupManager::get_complete_list_of_users_that_can_be_added_to_group($course_code, $group_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	public function testfilter_duplicates(){
		$user_array_in='';
		$compare_field='';
		$res = GroupManager::filter_duplicates($user_array_in, $compare_field);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
	/*
	public function testFilterUsersAlreadyInGroup(){
		$user_array_in='2';
		$group_id=2;
		$res = $this->gManager->filter_users_already_in_group($user_array_in, $group_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}*/

	public function testFilterOnlyStudents(){
		$user_array_in='';
		$res= GroupManager::filter_only_students($user_array_in);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testUserHasAccess(){
		$user_id='2';
		$group_id='5';
		$tool='wiki_state';
		$res = GroupManager::user_has_access($user_id, $group_id, $tool);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		//var_dump($res);
	}

	public function testGetUserGroupName(){
		$user_id='';
		$res=GroupManager::get_user_group_name($user_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}
}
?>
