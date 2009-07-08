<?php
require_once(api_get_path(LIBRARY_PATH).'classmanager.lib.php');

class TestClassManager extends UnitTestCase {
	
	function testAddUser() {
		$user_id='1';
		$class_id='1';
		$res=ClassManager::add_user($user_id, $class_id);
		$this->assertNull($res);
		$this->assertTrue(is_null($res));	
	}
	
	function testclass_name_exists() {
		$name='arthur';
		$res=ClassManager::class_name_exists($name);
		$this->assertTrue(is_bool($res));	
	}
	
	function testCreateClass() {
		$name='new class';
		$res=ClassManager::create_class($name);
		$this->assertTrue(is_bool($res));	
	}
	
	function testDeleteClass() {
		$class_id='new class';
		$res=ClassManager::delete_class($class_id);
		$this->assertTrue(is_null($res));	
	}
	
	function testGetClassId() {
		$name='new class';
		$res=ClassManager::get_class_id($name);
		$this->assertTrue(is_numeric($res));
	}
	
	function testGetClassInfo() {
		$class_id='1';
		$res=ClassManager::get_class_info($class_id);
		$this->assertTrue(is_array($res));		
	}
	
	function testGetClassesInCourse() {
		$course_code='FDI';
		$res=ClassManager::get_classes_in_course($course_code);
		$this->assertTrue(is_array($res));		
	}
	
	function testGetCourses() {
		$class_id='1';
		$res=ClassManager::get_courses($class_id);
		$this->assertTrue(is_array($res));	
	}
	
	function testGetUsers() {
		$class_id='1';
		$res=ClassManager::get_users($class_id);
		$this->assertTrue(is_array($res));			
	}
	
	function testSetName() {
		$name='new class';
		$class_id='1';
		$res=ClassManager::set_name($name, $class_id);
		$this->assertTrue(is_null($res));		
	}
	
	function testSubscribeToCourse() {
		$class_id='1';
		$course_code='FDI';
		$res=ClassManager::subscribe_to_course($class_id,$course_code);
		$this->assertTrue(is_null($res));	
	}
	
	function testUnsubscribeFromCourse() {
		$class_id='1';
		$course_code='FDI';
		$res=ClassManager::unsubscribe_from_course($class_id, $course_code);
		$this->assertTrue(is_null($res));		
	}
	
	function testUnsubscribeUser() {
		$user_id='1';
		$class_id='1';
		$res=ClassManager::unsubscribe_user($user_id, $class_id);
		$this->assertTrue(is_null($res));
	}
	
}
?>