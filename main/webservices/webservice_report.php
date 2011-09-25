<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once(dirname(__FILE__).'/../inc/global.inc.php');
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
    public function GetLearnpathsByCourse($secret_key, $user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value) {
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
        $lp = new LearnpathList($user_id,$course_code);
        $list = $lp->list;
        $return = array();
        foreach ($list as $id => $item) {
            $return[] = array('id'=>$id, 'title' => $item['lp_name']);
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
    public function GetLearnpathProgress($secret_key, $user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value, $learnpath_id) {
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
        $items = $lp->items[$learnpath_id];
        $return = array(
          'progress_bar_mode' => $lp->progress_bar_mode,
          'progress_db' => $lp->progress_db,
        );
        return $return;
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
    public function GetLearnpathScoreSingleItem($secret_key, $user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value, $learnpath_id) {
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
        $return = array(
          'min_score' => $lp->items[$learnpath_id]->min_score,
          'max_score' => $lp->items[$learnpath_id]->max_score,
          'mastery_score' => $lp->items[$learnpath_id]->mastery_score,
          'current_score' => $lp->items[$learnpath_id]->current_score,
        );
        return $return;
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
    public function GetLearnpathStatusSingleItem($secret_key, $user_id_field_name, $user_id_value, $course_id_field_name, $course_id_value, $learnpath_id) {
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
            return $lp->items[$learnpath_id]->status;
        }
    }


    public function test() {
        return 'Hello world!';
    }
}
