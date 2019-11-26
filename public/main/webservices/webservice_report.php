<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/webservice.php';

/**
 * Web services available for the User module. This class extends the WS class.
 */
class WSReport extends WS
{
    /**
     * Gets the time spent on the platform by a given user.
     *
     * @param string User id field name
     * @param string User id value
     *
     * @return array Array of results
     */
    public function GetTimeSpentOnPlatform($user_id_field_name, $user_id_value)
    {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        } else {
            return Tracking::get_time_spent_on_the_platform($user_id);
        }
    }

    /**
     * Gets the time spent in a course by a given user.
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     *
     * @return array Array of results
     */
    public function GetTimeSpentOnCourse(
        $user_id_field_name,
        $user_id_value,
        $course_id_field_name,
        $course_id_value
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id(
                $course_id
            );
        }

        return Tracking::get_time_spent_on_the_course($user_id, $course_id);
    }

    /**
     * Gets the time spent in a course by a given user.
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     *
     * @return array Array of results
     */
    public function GetTimeSpentOnCourseInSession(
        $user_id_field_name,
        $user_id_value,
        $course_id_field_name,
        $course_id_value,
        $session_id_field_name,
        $session_id_value
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id(
                $course_id
            );
        }
        $session_id = $this->getSessionId(
            $session_id_field_name,
            $session_id_value
        );
        if ($session_id instanceof WSError) {
            return $session_id;
        }

        return Tracking::get_time_spent_on_the_course(
            $user_id,
            $course_id,
            $session_id
        );
    }

    /**
     * Gets a list of learning paths by course.
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     *
     * @return array Array of id=>title of learning paths
     */
    public function GetLearnpathsByCourse(
        $secret_key,
        $user_id_field_name,
        $user_id_value,
        $course_id_field_name,
        $course_id_value
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id(
                $course_id
            );
        }

        $lp = new LearnpathList($user_id, api_get_course_info($course_code));
        $list = $lp->list;
        $return = [];
        foreach ($list as $id => $item) {
            $return[] = ['id' => $id, 'title' => $item['lp_name']];
        }

        return $return;
    }

    /**
     * Gets progress attained in the given learning path by the given user.
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     * @param string Learnpath ID
     *
     * @return float Between 0 and 100 (% of progress)
     */
    public function GetLearnpathProgress(
        $secret_key,
        $user_id_field_name,
        $user_id_value,
        $course_id_field_name,
        $course_id_value,
        $learnpath_id
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id(
                $course_id
            );
        }
        $lp = new learnpath($course_code, $learnpath_id, $user_id);
        $return = [
            'progress_bar_mode' => $lp->progress_bar_mode,
            'progress_db' => $lp->progress_db,
        ];

        return $return;
    }

    /**
     * Gets the highest element seen (lesson_location) in the given learning
     * path by the given user. If the user saw the learning path several times,
     * the last time (lp_view) is assumed. If there are several items in the lp,
     * the last item seen (lp_view.last_item) is considered as the relevant one
     * to get the lesson_location from.
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     * @param string Learnpath ID
     *
     * @return string The last item's lesson_location value
     */
    public function GetLearnpathHighestLessonLocation(
        $secret_key,
        $user_id_field_name,
        $user_id_value,
        $course_id_field_name,
        $course_id_value,
        $learnpath_id
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id(
                $course_id
            );
        }
        $lp = new learnpath($course_code, $learnpath_id, $user_id);
        $item = $lp->last_item_seen;
        $return = $lp->items[$item]->get_lesson_location();

        return $return;
    }

    /**
     * Gets score obtained in the given learning path by the given user,
     * assuming there is only one item (SCO) in the learning path.
     *
     * @param string User id field name
     * @param string User id value
     * @param string Course id field name
     * @param string Course id value
     * @param int Learnpath ID
     * @param int Learnpath *ITEM* ID
     *
     * @return float Generally between 0 and 100
     */
    public function GetLearnpathScoreSingleItem(
        $secret_key,
        $user_id_field_name,
        $user_id_value,
        $course_id_field_name,
        $course_id_value,
        $learnpath_id,
        $learnpath_item_id
    ) {
        $user_id = $this->getUserId($user_id_field_name, $user_id_value);
        if ($user_id instanceof WSError) {
            return $user_id;
        }
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id(
                $course_id
            );
        }

        $lp = new learnpath($course_code, $learnpath_id, $user_id);
        $return = [
            'min_score' => $lp->items[$learnpath_item_id]->min_score,
            'max_score' => $lp->items[$learnpath_item_id]->max_score,
            'mastery_score' => $lp->items[$learnpath_item_id]->mastery_score,
            'current_score' => $lp->items[$learnpath_item_id]->current_score,
        ];

        return $return;
    }

    /**
     * Gets status obtained in the given learning path by the given user,
     * assuming there is only one item (SCO) in the learning path.
     *
     * @param string Secret key
     * @param string User id field name (use chamilo_user_id if none)
     * @param string User id value
     * @param string Course id field name (use chamilo_course_id if none)
     * @param string Course id value
     * @param int Learnpath ID
     * @param int Learnpath *ITEM* ID
     *
     * @return string "not attempted", "passed", "completed", "failed", "incomplete"
     */
    public function GetLearnpathStatusSingleItem(
        $secret_key,
        $user_id_field_name,
        $user_id_value,
        $course_id_field_name,
        $course_id_value,
        $learnpath_id,
        $learnpath_item_id
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $user_id = $this->getUserId($user_id_field_name, $user_id_value);
            if ($user_id instanceof WSError) {
                return $user_id;
            }
            $course_id = $this->getCourseId(
                $course_id_field_name,
                $course_id_value
            );
            if ($course_id instanceof WSError) {
                return $course_id;
            } else {
                $course_code = CourseManager::get_course_code_from_course_id(
                    $course_id
                );
            }
            $lp = new learnpath($course_code, $learnpath_id, $user_id);

            return $lp->items[$learnpath_item_id]->status;
        }
    }

    public function test()
    {
        return 'Hello world!';
    }
}
