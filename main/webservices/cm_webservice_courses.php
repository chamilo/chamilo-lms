<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/cm_webservice.php';

/**
 * Description of cm_soap_inbox.
 *
 * @author marcosousa
 */
class WSCMCourses extends WSCM
{
    public function get_courses_code($username, $password)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $user_id = UserManager::get_user_id_from_username($username);
            $listOfCourses = UserManager::get_personal_session_course_list($user_id);

            $courses_id = "#";
            foreach ($listOfCourses as $course) {
                $courses_id .= $course['code']."#";
            }

            return $courses_id;
        } else {
            return get_lang('InvalidId');
        }
    }

    public function get_course_title($username, $password, $course_code)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $course_info = CourseManager::get_course_information($course_code);

            return $course_info['title'];
        } else {
            return get_lang('InvalidId');
        }
    }
}
