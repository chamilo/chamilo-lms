<?php
/* For licensing terms, see /license.txt */

require_once api_get_path(SYS_CODE_PATH).'announcements/announcements.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

class TestAnnouncements extends UnitTestCase {

        function TestAnnouncements(){
            $this->UnitTestCase('Displays one specific announcement test');
        }

        public function Testconstruct_not_selected_select_form(){
            $courseSysCode= '123';
            $course_code = $courseSysCode;
            ob_start();
            $to_already_selected="";
            $_SESSION['_cid'] = 'CURSO1';
            $user_list = array( 0=>array(
                              0 => '1','user_id' => '1',
                              1 =>'Doe','lastname' =>'Doe',
                              2 =>'John','firstname' =>'John',
                              3 => 'admin','username' =>'admin'
                          ));

            $res = construct_not_selected_select_form($group_list=null, $user_list,$to_already_selected);
            ob_end_clean();
            $this->assertTrue(is_null($res));
            //var_dump($res);
        }

        public function Testconstruct_selected_select_form(){
            $to_already_selected="";
            ob_start();
            $res = construct_selected_select_form($group_list=null, $user_list=null,$to_already_selected);
            ob_end_clean();
            $this->assertTrue(is_null($res));
            //var_dump($res);
        }

        public function Testshow_to_form_group(){
            ob_start();
            $group_id=1;
            $group_users=GroupManager::get_subscribed_users($group_id);
            $res = show_to_form_group($group_id);
            ob_end_clean();
            $this->assertTrue(is_null($res));
            //var_dump($res);
        }

        public function Testget_course_users(){
            $_SESSION['id_session'] = 'CURSO1';
            $user_list = CourseManager::get_real_and_linked_user_list(api_get_course_id(), true, $_SESSION['id_session']);
            $res = get_course_users();
            if($res = array($res)){
            $this->assertTrue(is_array($res));
            } else {
            $this->assertTrue(is_null($res));
            }
            //var_dump($res);
        }

        public function Testget_course_groups(){
            $_SESSION['id_session']='CURSO1';
            $new_group_list = CourseManager::get_group_list_of_course(api_get_course_id(), intval($_SESSION['id_session']));
            $res = get_course_groups();
            $this->assertFalse($res);
            $this->assertTrue(is_array($res));
            var_dump($res);
        }

        public function Testload_edit_users(){
            $_SESSION['id_session']='CURSO1';
            global $_course;
            global $tbl_item_property;
            $tbl_item_property 	= Database::get_course_table(TABLE_ITEM_PROPERTY);
            $tool = '';
            $id = '';
            $res = load_edit_users($tool, $id);
            $this->assertTrue(is_null($res));
            var_dump($res);
        }
        /*
        public function Testsent_to_form(){
            $group_names=get_course_groups();
            $sent_to_array='';
            $res = sent_to_form($sent_to_array);
            $this->assertTrue(is_null($res));
            var_dump($res);
        }*/
}
