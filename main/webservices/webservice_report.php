<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once(dirname(__FILE__).'/../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once($libpath.'sessionmanager.lib.php');
require_once $libpath.'usermanager.lib.php';
require_once $libpath.'tracking.lib.php';
require_once $libpath.'course.lib.php';
require_once(dirname(__FILE__).'/webservice.php');

/**
 * Web services available for the User module. This class extends the WS class
 */
class WSReport extends WS {

	/**
	 * Gets the time spent on the platform by a given user
	 *
	 * @param string User id field name
	 * @param string User id value
     * @return array Array of results
	 */
	public function GetTimeSpentOnPlatform($user_id_field_name, $user_id_value) {
		$user_id = $this->getUserId($user_id_field_name, $user_id_value);
		if($user_id instanceof WSError) {
			return $user_id;
		} else {
            return Tracking::get_time_spent_on_the_platform($user_id);
		}
	}

	/**
     * Gets the time spent in a course by a given user
	 *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
	 * @return array Array of results
	 */
	public function GetTimeSpentOnCourse($user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
        if($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
        }
        return Tracking::get_time_spent_on_the_course($user_id, $course_code);
	}

    /**
     * Gets the time spent in a course by a given user
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     * @return array Array of results
     */
    public function GetTimeSpentOnCourseInSession($user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value, $session_id_field_name, $session_id_value) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
        if($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
        }
        $session_id = $this->getSessionId($session_id_field_name, $session_id_value);
        if($session_id instanceof WSError) {
            return $session_id;
        }
        return Tracking::get_time_spent_on_the_course($user_id, $course_code, $session_id);
    }
    /**
     * Gets a list of learning paths by course
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     * @return array Array of id=>title of learning paths
     */
    public function GetLearnpathsByCourse($user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value, $lp_id) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
        if($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
        }
        require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
        $list = new LearnpathList($user_id,$course_code);
        $return = array();
        foreach ($list as $id => $item) {
            $return[] = array('id'=>$id, 'title' => $item);
        }
        return $return;
    }
    /**
     * Gets progress attained in the given learning path by the given user
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     * @param string Learnpath ID
     * @return double   Between 0 and 100 (% of progress)
     */
    public function GetLearnpathProgress($user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value, $learnpath_id) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
        if($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
        }
        require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
        $lp = new learnpath($course_code, $learnpath_id, $user_id);
        return $lp['progress'];
    }
    /**
     * Gets score obtained in the given learning path by the given user,
     * assuming there is only one item (SCO) in the learning path
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     * @param string Learnpath ID
     * @return double   Generally between 0 and 100
     */
    public function GetLearnpathScoreSingleItem($user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value, $learnpath_id) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
        if($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
        }
        require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
        $lp = new learnpath($course_code, $learnpath_id, $user_id);
        //return $lp['progress'];
        return 100;
    }
    /**
     * Gets status obtained in the given learning path by the given user,
     * assuming there is only one item (SCO) in the learning path
     *
     * @param string Secret key
     * @param string User id field name (use chamilo_user_id if none)
     * @param string User id value
     * @param string Course id field name (use chamilo_course_id if none)
     * @param string Course id value
     * @param string Learnpath ID
     * @return string "not attempted", "passed", "completed", "failed", "incomplete"
     */
    public function GetLearnpathStatusSingleItem($secret_key, $user_id_field_name = 'chamilo_user_id', $user_id_value, $course_id_field_name = 'chamilo_course_id', $course_id_value, $learnpath_id) {
        return null;
        $verifKey = $this->verifyKey($secret_key);
        if($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $user_id = $this->getUserId($user_id_field_name, $user_id_value);
            if($user_id instanceof WSError) {
                return $user_id;
            }
            $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
            if($course_id instanceof WSError) {
                return $course_id;
            } else {
                $course_code = CourseManager::get_course_code_from_course_id($course_id);
            }
            require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
            $lp = new learnpath($course_code, $learnpath_id, $user_id);
error_log('Ready to return status for '.$course_code.'-'.$learnpath_id.'-'.$user_id.': '.$lp->items[0]['status']);
            return $lp->items[0]['status'];
        }
    }
    public function test() {
        return 'Hello world!';
    }
}
