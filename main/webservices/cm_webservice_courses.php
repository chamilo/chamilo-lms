<?php

require_once(dirname(__FILE__).'/../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);

require_once $libpath.'usermanager.lib.php';
require_once $libpath.'course.lib.php';
require_once(dirname(__FILE__).'/cm_webservice.php');


/**
 * Description of cm_soap_inbox
 *
 * @author marcosousa
 */
class WSCMCourses extends WSCM {
    
    public function get_courses_code($username, $password)
    { 
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $user_id = UserManager::get_user_id_from_username($username);
            $listOfCourses = UserManager::get_personal_session_course_list($user_id);

            $courses_id = "#";
            foreach ($listOfCourses as $course){
                $courses_id .= $course['c']."#";
            }
            return $courses_id;
        } else
            return get_lang('InvalidId');

    }

    public function get_course_title($username, $password, $course_code)
    {
        if($this->verifyUserPass($username, $password) == "valid")
        {
            $course_info = CourseManager::get_course_information($course_code);
            return $course_info['title'];
        } else
            return get_lang('InvalidId');

    }

}

/*
echo "aqui: ";
$aqui = new WSCMCourses();
echo "<pre>";
//print_r($aqui->unreadMessage("aluno", "e695f51fe3dd6b7cf2be3188a614f10f"));
print_r($aqui->get_course_title("aluno", "c4ca4238a0b923820dcc509a6f75849b", "P0204"));
echo "</pre>";

*/