<?php
/*
 * Created on 17/06/2009
 *
 */
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

class TestCourse extends UnitTestCase{
	
	public $tcourse;
	public function TestCourse(){
		
		$this->UnitTestCase('All main course function tests');
		
	}
	
	public function setUp(){
		
		$this->tcourse = new CourseManager();
	}
	
	public function tearDown(){
		$this->tcourse = null;
		
	}
	
	/*
	 *todo public function testGetCourseInformation()
	 *todo public function testGetCoursesList()
	 *todo public function testGetAccessSettings()
	 *todo public function testGetUserInCourseStatus()
	 *todo public function testUnsubcribeUser()
	 *todo public function testSubscribeUser()
	 *todo public function testAddUserToCourse()
	 *todo public function testGetRealCourseCodeSelectHtml()
	 *todo public function testCheckParameter()
	 *todo public function testCheckParameterOrFail()
	 *todo public function testIsExistingCourseCode()
	 *todo public function testGetRealCourseList()
	 *todo public function testGetVirtualCourseList()
	 *todo public function testGetRealCourseListOfUserAsCourseAdmin()
	 *todo public function testGetCourseListOfUserAsCourseAdmin()
	 *todo public function testDetermineCourseTitleFromCourseInfo()
	 *todo public function testCreateCombinedName()
	 *todo public function testCreateCombinedCode()
	 *todo public function testGetVirtualCourseInfo()
	 *todo public function testIsVirtualCourseFromSystemCode()
	 *todo public function testIsVirtualCourseFromVisualCode()
	 *todo public function testHasVirtualCoursesFromCode()
	 *todo public function testGetVirtualCoursesLinkedToRealCourse()
	 *todo public function testGetTargetOfLinkedCourse()
	 *todo public function testIsCourseTeacher()
	 *todo public function testIsUserSubscribedInRealOrLinkedCourse()
	 *todo public function testGetUserListFromCourseCode()
	 *todo public function testGetCoachListFromCourseCode()
	 *todo public function testGetStudentListFromCourseCode()
	 *todo public function testGetTeacherListFromCourseCode()
	 *todo public function testGetRealAndLinkedUserList()
	 *todo public function testGetListOfVirtualCoursesForSpecificUserAndRealCourse()
	 *todo public function testGetGroupListOfCourse()
	 *todo public function testAttemptCreateVirtualCourse()
	 *todo public function testCreateVirtualCourse()
	 *todo public function testDeleteCourse()
	 *todo public function testCreateDatabaseDump()
	 *todo public function testUserCourseSort()
	 *todo public function testSelectAndSortCategories()
	 *todo public function testCourseExists()
	 *todo public function testEmailToTutor()
	 *todo public function testGetCourseListByUserId()
	 *todo public function testGetCourseIdFromPath()
	 *todo public function testGetCourseInfoVisualCode()
	 *todo public function testGetEmailOfTutorsToCourse()
	 *todo public function testGetEmailOfTutorToSession()
	 *todo public function testCreateCourseExtrField()
	 *todo public function testUpdateCourseExtraFieldValue()
	 */
	 
	 public function testGetCourseInformation(){
	  	$res = $this->tcourse->get_course_information(1211);
	 	$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
	 	$this->assertTrue($this->tcourse->get_course_information(1211)=== is_array($res));	 	
	 	
	 }
	 
	 public function testGetCoursesList(){
	 	$res = $this->tcourse->get_courses_list();
	 	$this->assertTrue($res);
	  	$this->assertTrue(is_array($res));
	 	//var_dump($res);
	 	
	 }
	 
	 public function testGetAccessSettings(){
	 	$res = $this->tcourse->get_access_settings(0001);
	 	$this->assertFalse($res);
	 	$this->assertTrue($this->tcourse->get_access_settings(0001)===is_array($res));
	 	$this->assertFalse(is_null($res));

	 }
	 
	 public function testGetUserInCourseStatus(){
	 	$res = $this->tcourse->get_user_in_course_status(01,0001);
		$this->assertFalse($res);
		$this->assertTrue($this->tcourse->get_user_in_course_status(01,0001)===null);
		$this->assertTrue(is_null($res));

	 }
	 
	 public function testUnsubscribeUser(){
	 	$res = $this->tcourse->unsubscribe_user();
	 	$this->assertTrue($this->tcourse->unsubscribe_user()===null);
	 	$this->assertNull($res);
	 	$this->assertFalse(is_string($res));
	 	
	 }
	 
	 public function testSubscribeUser(){
	 	$res = $this->tcourse->subscribe_user();
	 	$this->assertFalse($res);
	 	$this->assertTrue(is_bool($res));
	 	$this->assertTrue($this->tcourse->subscribe_user()===false);
	 	
	 }
	 
	public function testAddUserToCourse(){
		$res = $this->tcourse->add_user_to_course();
		$this->assertFalse($res);
		$this->assertTrue($this->tcourse->add_user_to_course()=== false);
		$this->assertTrue(is_bool($res));
	}
	/*  function deprecated
	public function testGetRealCourseCodeSelectHtml(){}*/
	
	public function testCheckParameter(){
		$res = $this->tcourse->check_parameter();
		$this->assertFalse($res);
		$this->assertFalse($this->tcourse->check_parameter()===bool);
	}
	
	public function testCheckParameterOrFail(){
		$parameter = 'course';
		$error_message = 'upps';
		$res = $this->tcourse->check_parameter_or_fail($parameter,$error_message); //ob_get_contents();
		$this->assertTrue(is_null($res));
		$this->assertFalse($res);
		$this->assertTrue($res=== null);
		$this->assertEqual($res,null);
		
	}
	
	public function testIsExistingCourseCode() {
		$res = $this->tcourse->is_existing_course_code();
		$this->assertTrue($this->tcourse->is_existing_course_code()===false);
		$this->assertTrue(is_bool($res));
	}
	
	public function testGetRealCourseList(){
		$res = $this->tcourse->get_real_course_list();
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		
	}
	
	public function testGetVirtualCourseList(){
		$res = $this->tcourse->get_virtual_course_list();
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		$this->assertNull($res);
	//	$this->assertTrue(is_array($res));
	}
	
	public function testGetRealCourseListOfUserAsCourseAdmin(){
		$res = $this->tcourse->get_real_course_list_of_user_as_course_admin();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($this->tcourse->get_real_course_list_of_user_as_course_admin()===array());
				
	}
	
	public function testGetCourseListOfUserAsCourseAdmin(){
		$res = $this->tcourse->get_course_list_of_user_as_course_admin();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
	}
	
	public function testDetermineCourseTitleFromCourseInfo(){
		$res = $this->tcourse->determine_course_title_from_course_info();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($this->tcourse->determine_course_title_from_course_info()=== array());
		
	}
	
	public function testCreateCombinedName(){
		$res = $this->tcourse->create_combined_name();
		$this->assertFalse($res);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}
	public function testCreateCombinedCode(){
		$res = $this->tcourse->create_combined_code();
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		$this->assertTrue($this->tcourse->create_combined_code()===null);
	}
	
	public function testGetVirtualCourseInfo(){
		$res = $this->tcourse->get_virtual_course_info();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($this->tcourse->get_virtual_course_info()===array());	
	}
	
	public function testIsVirtualCourseFromSystemCode(){
		$res = $this->tcourse->is_virtual_course_from_system_code();
		$this->assertFalse($res);
		$this->assertTrue($this->tcourse->is_virtual_course_from_system_code()===is_bool());
		$this->assertTrue(is_bool($res));
		$this->assertFalse($res,null);
	}
	
	public function testIsVirtualCourseFromVisualCode(){
		$res = $this->tcourse->is_virtual_course_from_visual_code();
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		$this->assertFalse($this->tcourse->is_virtual_course_from_visual_code()===null);
	}
		

	public function testHasVirtualCourseFromCode(){
		$res = $this->tcourse->has_virtual_courses_from_code();
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		$this->assertFalse(is_null($res));		
	}
	
	public function testGetVirtualCourseLinkedToRealCourse(){
		$res = $this->tcourse->get_virtual_courses_linked_to_real_course();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
		$this->assertFalse(is_null($res));		
	}
	
	public function testGetTargetOfLinkedCourse(){
		$res = $this->tcourse->get_target_of_linked_course();
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		$this->assertTrue($this->tcourse->get_target_of_linked_course()===null);
		$this->assertNull($res,true);
	}
	
	public function testIsUserSubscribedInCourse(){
		$res = $this->tcourse->is_user_subscribed_in_course();
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($this->tcourse->is_user_subscribed_in_course()===is_bool());
	}
	
	public function testIsCourseTeacher(){
		$res = $this->tcourse->is_course_teacher();
		$this->assertTrue(is_bool($res));
		$this->assertTrue($this->tcourse->is_course_teacher()===is_bool());
		$this->assertFalse($res);
	}
	
	public function testIsUserSubscribedInRealOrLinkedCourse(){
		$res = $this->tcourse->is_user_subscribed_in_real_or_linked_course();
		$this-> assertTrue(is_bool($res));
		$this->assertFalse($this->tcourse->is_user_subscribed_in_real_or_linked_course()=== null);
		$this->assertFalse($res);		
	}
	
	public function testGetUserListFromCourseCode(){
		$res = $this->tcourse->get_user_list_from_course_code();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
	}
	
	public function testGetCoachListFromCourseCode(){
		$res = $this->tcourse->get_coach_list_from_course_code();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($this->tcourse->get_coach_list_from_course_code()===array());
	}
	
	public function testGetStudentListFromCourseCode(){
		$res = $this->tcourse->get_coach_list_from_course_code();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
	}
	
	public function testGetTeacherListFromCourseCode(){
		$res = $this->tcourse->get_teacher_list_from_course_code();
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		$this->assertTrue($this->tcourse->get_teacher_list_from_course_code()===null);
	}
	
	public function testGetRealAndLinkedUserList(){
		$res = $this->tcourse->get_real_and_linked_user_list();
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		$this->assertTrue($this->tcourse->get_real_and_linked_user_list()===null);
	}
	
	public function testGetListOfVirtualCoursesForSpecificUserAndRealCourse(){
		$res = $this->tcourse->get_list_of_virtual_courses_for_specific_user_and_real_course();
		$this->assertFalse($res);
		$this->assertTrue(is_array($res));
	}
	
	public function testGetGroupListOfCourse(){
		$course_code='0001';
		$session_id=0;
		ob_start();
		$res = $this->tcourse->get_group_list_of_course($course_code,null);
		ob_end_clean();
		$this->assertFalse($res);
	}
	
	public function testAttemptCreateVirtualCourse(){
		$real_course_code = '0001';
		$course_title = 'training course';
		$wanted_course_code='0001';
		$course_language= 'english';
		$course_category= 'lang';
		ob_start();
		$res = $this->tcourse->attempt_create_virtual_course($real_course_code,$course_title,$wanted_course_code,$course_language,$course_category);// ob_get_contents();
		ob_end_clean();
		$this->assertFalse($res);
		$this->assertTrue(is_bool($res));
		
	}
	
	public function testCreateVirtualCourse(){
		global $firsExpirationDelay;
		$real_course_code = '0002';
		$course_title = 'training course';
		$wanted_course_code='0002';
		$course_language= 'english';
		$course_category= 'lang';
		$res = $this->tcourse->create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);
		$this->assertTrue($res);
		$this->assertTrue($this->tcourse->create_virtual_course()===true);
		
	}
	
	public function testDeleteCourse(){
		global $_configuration;
		$code = '0002';
		$res = $this->tcourse->delete_course($code);
		$this->assertFalse();
		$this->assertTrue($this->tcourse->delete_course()===null);
		$this->assertTrue(is_null($res));
	
	}
	
	public function testCreateDatabaseDump(){
		global $_configuration;
		$course_code='0001';
		$res = $this->tcourse->create_database_dump($course_code);
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
		$this->assertTrue($this->tcourse->create_database_dump()=== null);

	}
			

	public function testUserCourseSort(){
		$user_id ='01';
		$course_code='0001';
		$res = $this->tcourse->UserCourseSort($user_id,$course_code);
		$this->assertTrue($res);
		$this->assertTrue(is_numeric($res));
		$this->assertFalse($this->tcourse->UserCourseSort()=== null);
		
	}
	
	public function testSelectAndSortCategories(){
		$form = new FormValidator('add_course');
		$categories = array('name' => 'prueba');
        $categories_select = $form->addElement('select', 'category_code', get_lang('Fac'), $categories);
		$res = $this->tcourse->select_and_sort_categories($categories_select);
		$this->assertFalse($res);
		$this->assertTrue(is_null($res));
				
	}
	
	public function testCourseExists(){
		$course_code='0001';
		$res=$this->tcourse->course_exists($course_code);
		$this->assertTrue($res);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($this->tcourse->course_exists()===0);
		
	}
	
	public function testEmailToTutor(){
		/*$user_id=1;
		$course_code='0001';
		$send_to_tutor_also=false;*/
		$res = $this->tcourse->email_to_tutor(/*$user_id,$course_code,$send_to_tutor_also*/);
		$this->assertFalse($res);
		$this->assertFalse(is_null($res));
				
	}
	
	public function testGetCoursesListByUserId(){
		$user_id = '01';
		$res = $this->tcourse->get_courses_list_by_user_id($user_id);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res,array());
		$this->assertFalse(is_null($res));
	
	}
	
	public function testGetCourseIdFromPath(){
		$path = '/var/www/path';
		$res = $this->tcourse->get_course_id_from_path($path);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertTrue( $this->tcourse->get_course_id_from_path()===false);
	}
	
	public function testGetCoursesInfoFromVisualCode(){
		$code = '0001';
		$res = $this->tcourse->get_courses_info_from_visual_code($code);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
	}
	
	public function testGetEmailsOfTutorsToCourse(){
		$code = '0001';
		$res = $this->tcourse->get_emails_of_tutors_to_course($code);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res,array());
		$this->assertTrue($res);
	}
	
	public function testGetEmailOfTutorToSession(){
		$session = '1';
		$res = $this->tcourse->get_email_of_tutor_to_session($session);
		$this->assertTrue($res);
		$this->assertTrue(is_array($res));
	}
	
	public function testCreateCourseExtraField(){
		$fieldvarname = '';
		$fieldtype = '5';
		$fieldtitle = '';
		$res = $this->tcourse->create_course_extra_field($fieldvarname, $fieldtype, $fieldtitle);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res);
	}
	
	public function testUpdateCourseExtraFieldValue(){
		$course_code = '0001';
		$fname = '';
		$fvalue= '';
		$res = $this->tcourse->update_course_extra_field_value($course_code,$fname,$fvalue='');
		$this->assertTrue($res);
		$this->assertTrue(is_bool($res));
		$this->assertFalse(is_null($res));
	
	}
} 

?>
