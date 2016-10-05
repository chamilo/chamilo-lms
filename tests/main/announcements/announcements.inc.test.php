<?php
/* For licensing terms, see /license.txt */

class TestAnnouncements extends UnitTestCase {

    function TestAnnouncements(){
        $this->UnitTestCase('Displays one specific announcement test');
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
}
