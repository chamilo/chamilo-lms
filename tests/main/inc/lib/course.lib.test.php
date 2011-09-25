<?php
/*
 * To can run this test you need comment this line or "die(mysql_error())" in 1345 course.lib.php
 *
 */
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'database.lib.php');
require_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

Mock::generate('Database');
Mock::generate('CourseManager');
Mock::generate('Display');

class TestCourse extends UnitTestCase{

    public $tcourse;
    public function TestCourse(){

        $this->UnitTestCase('Courses library - main/inc/lib/course.lib.test.php');
    }

    public function setUp(){
        global $_configuration;
        $this->tcourse = new CourseManager();

        $course_datos = array(
                'wanted_code'=> 'COURSE1',
                'title'=>'COURSE1',
                'tutor_name'=>'R. J. Wolfagan',
                'category_code'=>'2121',
                'course_language'=>'english',
                'course_admin_id'=>'1211',
                'db_prefix'=> $_configuration['db_prefix'],
                'firstExpirationDelay'=>'112'
                );
        $res = create_course($course_datos['wanted_code'], $course_datos['title'],
                             $course_datos['tutor_name'], $course_datos['category_code'],
                             $course_datos['course_language'],$course_datos['course_admin_id'],
                             $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);
    }

    public function tearDown(){
        $this->tcourse = null;
        $this->dbase = null;
         $code = 'COURSE1';

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
         $user_id = 1;
         $course_code = 'COURSE1';
         $res = CourseManager::unsubscribe_user($user_id, $course_code);
         $this->assertNull($res);
         $this->assertFalse(is_string($res));
     }

     public function testSubscribeUser(){
         $user_id = 1;
         $course_code = 'COURSE1';
         $status = STUDENT;
         $res = CourseManager::subscribe_user($user_id, $course_code, $status);
         $this->assertTrue(is_bool($res));
     }

    public function testAddUserToCourse(){
        $user_id = 1;
        $course_code = 'COURSE1';
        $status = STUDENT;
        $res = CourseManager::add_user_to_course($user_id, $course_code, $status);
        $this->assertTrue($res);
        $this->assertTrue(is_bool($res));
        //var_dump($res);
    }
    //function deprecated public function testGetRealCourseCodeSelectHtml(){}
    public function testCheckParameter(){
        $parameter = '123';
        $error_message = 'oops!!';
        $res = $this->tcourse->check_parameter($parameter, $error_message);
        $this->assertTrue($res);
        $this->assertTrue(is_bool($res));
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

    public function testCourseCodeExists() {
        $wanted_course_code = 'COURSE1';
        $res = $this->tcourse->course_code_exists($wanted_course_code);
        $this->assertTrue(is_bool($res));
        $this->assertTrue($res);
    }

   /** Return a array() but now its empty, with this test is cheking is get the list course
     * @author Arthur Portugal <arthur.portugal@dokeos.com> -
     * doesn't work correctly refactoring by Ricardo Rodriguez <ricardo.rodriguez@beeznest.com>
     **/

    public function testGetRealCourseList(){
        $res = CourseManager::get_real_course_list();
        $this->assertTrue(is_array($res));
        $this->assertTrue($res);
        //var_dump($res);
    }

    public function testGetVirtualCourseList(){
        $course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
        $sql_query = "SELECT * FROM $course_table WHERE target_course_code IS NOT NULL";
        $sql_result = Database::query($sql_query);
        $num = Database::num_rows($sql_result);
        $res = $this->tcourse->get_virtual_course_list();
        $this->assertEqual($num,count($res));
    }

    public function testGetRealCourseListOfUserAsCourseAdmin(){
        $user_id = 1;
        $res = $this->tcourse->get_real_course_list_of_user_as_course_admin($user_id);
            $this->assertTrue(is_array($res));
    }

    public function testGetCourseListOfUserAsCourseAdmin(){
        $user_id = 1;
        $res = $this->tcourse->get_course_list_of_user_as_course_admin($user_id);
        $this->assertTrue(is_array($res));
    }

    public function testDetermineCourseTitleFromCourseInfo(){
        $user_id = 1;
        $course_info = 'abcd123';
        $res = $this->tcourse->determine_course_title_from_course_info($user_id, $course_info);
        $this->assertTrue($res);
        $this->assertTrue(is_array($res));
    }

    public function testCreateCombinedName(){
        $complete_course_name = array();
        $user_is_registered_in_real_course = false;
        $real_course_name = 'COURSE1';
        $virtual_course_list = array();
        $res = CourseManager::create_combined_name($user_is_registered_in_real_course,
                                                   $real_course_name,
                                                   $virtual_course_list
                                                   );
        $this->assertFalse($res);
        $this->assertTrue(is_string($res));
        //var_dump($res);
    }

    public function testCreateCombinedCode(){
        $complete_course_code = array();
        $user_is_registered_in_real_course = false;
        $real_course_code = 'COURSE1';
        $virtual_course_list = array();
        $res = CourseManager::create_combined_code($user_is_registered_in_real_course,
                                                    $real_course_code,
                                                    $virtual_course_list);
        if(is_array($res)){
        $this->assertFalse($res);
        $this->assertTrue(is_string($res));
        } else {
        $this->assertFalse(is_null($res));
        }
        //var_dump($res);
    }

    public function testGetVirtualCourseInfo(){
        $real_course_code = 'COURSE1';
        $res = $this->tcourse->get_virtual_course_info($real_course_code);
        if(is_array($res)){
        $this->assertTrue(is_array($res));
        } else {
            $this->assertTrue($res);
        }
        //var_dump($res);
    }

    public function testIsVirtualCourseFromSystemCode(){
        $system_code = 'COURSE1';
        $res = $this->tcourse->is_virtual_course_from_system_code($system_code);
        $this->assertFalse($res);
        $this->assertTrue(is_bool($res));
        $this->assertFalse($res,null);
    }

    public function testIsVirtualCourseFromVisualCode(){
        $system_code = 'COURSE1';
        $res = $this->tcourse->is_virtual_course_from_visual_code($system_code);
        $this->assertFalse($res);
        $this->assertTrue(is_bool($res));
    }

    public function testHasVirtualCourseFromCode(){
        $real_course_code = 'COURSE1';
        $user_id = 1;
        $res = CourseManager::has_virtual_courses_from_code($real_course_code, $user_id);
        if(is_bool($res)){
            $this->assertFalse($res);
            $this->assertTrue(is_bool($res));
        } else {
            $this->assertFalse(is_null($res));
        }
        //var_dump($res);
    }

    public function testGetVirtualCourseLinkedToRealCourse(){
        $real_course_code = 'COURSE1';
        $res = CourseManager::get_virtual_courses_linked_to_real_course($real_course_code);
        $this->assertFalse($res);
        $this->assertTrue(is_array($res));
        $this->assertFalse(is_null($res));
    }

    public function testGetTargetOfLinkedCourse(){
        $virtual_course_code = 'COURSE1';
        $res = CourseManager::get_target_of_linked_course($virtual_course_code);
        $this->assertFalse($res);
        $this->assertTrue(is_null($res));
        $this->assertNull($res,true);
    }

    public function testIsUserSubscribedInCourse(){
        $user_id = 1;
        $course_code = 'COURSE1';
        $in_a_session = false;
        $res = CourseManager::is_user_subscribed_in_course($user_id, $course_code, $in_a_session);
        $this->assertTrue(is_bool($res));
        //var_dump($res);
    }

    public function testIsCourseTeacher(){
        $user_id = 1;
        $course_code = 'COURSE1';
        $res = CourseManager::is_course_teacher($user_id, $course_code);
        $this->assertTrue(is_bool($res));
        $this->assertFalse($res);
    }

    public function testIsUserSubscribedInRealOrLinkedCourse(){
        $user_id = 1;
        $course_code = 'COURSE1';
        $session_id = '';
        $res = CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_code, $session_id);
        if(is_bool($res)){
            $this-> assertTrue(is_bool($res));
            $this->assertFalse($res);
        } else {
            $this->assertTrue($res);
        }
        //var_dump($res);
    }

    public function testGetUserListFromCourseCode(){
        $course_code = 'COURSE1';
        $with_session = true;
        $session_id = 0;
        $limit = '';
        $order_by = '';
        $res = CourseManager::get_user_list_from_course_code($course_code, $with_session, $session_id, $limit, $order_by);
        //$this->assertTrue($res);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }

    public function testGetCoachListFromCourseCode(){
        $course_code = 'COURSE1';
        $session_id = '';
        $res = CourseManager::get_coach_list_from_course_code($course_code, $session_id);
        $this->assertFalse($res);
        $this->assertTrue(is_array($res));
    }

    public function testGetStudentListFromCourseCode(){
        $course_code = 'COURSE1';
        $session_id = '001';
        $res = $this->tcourse->get_student_list_from_course_code($course_code, $session_id);
        //$this->assertTrue($res);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }

    public function testGetTeacherListFromCourseCode(){
        $course_code = 'COURSE1';
        $res = $this->tcourse->get_teacher_list_from_course_code($course_code);
        $this->assertFalse($res);
        $this->assertFalse(is_null($res));
        //var_dump($res);
    }

    public function testGetRealAndLinkedUserList(){
        $course_code = 'COURSE1';
        $with_sessions = true;
        $session_id = 0;
        $res = $this->tcourse->get_real_and_linked_user_list($course_code, $with_sessions, $session_id);
        $this->assertNull($res);
        $this->assertTrue(is_null($res));
        //$this->assertTrue($this->tcourse->get_real_and_linked_user_list()===null);
        //var_dump($res);
    }

    public function testGetListOfVirtualCoursesForSpecificUserAndRealCourse(){
        $result_array = array('user_id' => 1, 'real_course_code' => 'COURSE1');
        $res = CourseManager::get_list_of_virtual_courses_for_specific_user_and_real_course($result_array['user_id'],$result_array['real_course_code']);
        if(is_array($res)){
        $this->assertTrue(is_array($res));
        } else {
            $this->assertTrue($res);
        }
        //var_dump($res);
    }

    public function testGetGroupListOfCourse(){
        $course_code = 'COURSE1';
        $sql= 	"SELECT * FROM chamilo_COURSE1";
        $result = CourseManager::get_group_list_of_course($course_code);
        $this->assertTrue(is_array($result));
        $this->assertFalse($result);
        //var_dump($res);
    }

    public function testAttemptCreateVirtualCourse(){
        $real_course_code = 'COURSE1';
        $course_title = 'COURSE1';
        $wanted_course_code = 'COURSE1';
        $course_language = 'english';
        $course_category = 'LANG';
        ob_start();
        $res = CourseManager::attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);
        ob_end_clean();
        $this->assertTrue(is_bool($res));
    }

    public function testCreateVirtualCourse(){
        $real_course_code = 'COURSE1';
        $course_title = 'COURSE1';
        $wanted_course_code = 'COURSE1';
        $course_language = 'english';
        $course_category = 'LANG';
        ob_start();
        $res = CourseManager::create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);
        ob_end_clean();
        if(is_bool($res)){
            $this->assertTrue(is_bool($res));
        } else {
            $this->assertTrue($res);
        }
        //var_dump($res);
    }

    public function testCreateDatabaseDump(){
        global $_configuration;
        $course_code='COURSE1';
        $res = $this->tcourse->create_database_dump($course_code);
        $this->assertFalse($res);
        $this->assertTrue(is_null($res));
    }

    public function testUserCourseSort(){
        $user_id ='01';
        $course_code='COURSE1';
        $res = CourseManager::UserCourseSort($user_id,$course_code);
        $this->assertTrue($res);
        $this->assertTrue(is_numeric($res));
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
        $course_code='COURSE1';
        $accept_virtual = false;
        $res=$this->tcourse->course_exists($course_code, $accept_virtual);
        $this->assertTrue(is_numeric($res));
    }

    public function testEmailToTutor() {
        $user_id= '01';
        $course_code= 'COURSE1';
        $res=CourseManager::email_to_tutor($course_code,$user_id);
        $this->assertTrue(is_string($course_code));
        //var_dump($res);
    }

    public function testGetCoursesListByUserId(){
        $user_id = '01';
        $res = $this->tcourse->get_courses_list_by_user_id($user_id);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }

    public function testGetCourseIdFromPath(){
        $path = '/var/www/path';
        $res = $this->tcourse->get_course_id_from_path($path);
        $this->assertTrue(is_bool($res));
        $this->assertTrue($res === false);
    }

    public function testGetCoursesInfoFromVisualCode(){
        $code = '0001';
        $res=$this->tcourse->get_courses_info_from_visual_code($code);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }

    public function testGetEmailsOfTutorsToCourse(){
        $code = '0001';
        $res= $this->tcourse->get_emails_of_tutors_to_course($code);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }

    public function testGetEmailOfTutorToSession(){
        $session_id = '01';
        $course_code = 'COURSE1';
        ob_start();
        $res = CourseManager::get_email_of_tutor_to_session($session_id,$course_code);
        ob_end_clean();
        $this->assertTrue(is_array($res));
        //var_dump($res);
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
        $course_code = 'COURSE1';
        $fname = '';
        $fvalue= '';
        $res = $this->tcourse->update_course_extra_field_value($course_code,$fname,$fvalue='');
        $this->assertTrue($res);
        $this->assertTrue(is_bool($res));
        $this->assertFalse(is_null($res));
    }

    public function testDeleteCourse(){
        global $_configuration;
        $code = 'COURSE1';
        if (!empty($course_code)) {
            $code = $course_code;
        }
        $res = CourseManager::delete_course($code);
        $this->assertTrue(is_null($res));
    }


}

?>
