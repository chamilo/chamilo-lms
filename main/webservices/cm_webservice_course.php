<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/cm_webservice.php';

/**
 * Web services available for the Course module. This class extends the WS class.
 */
class WSCMCourse extends WSCM
{
    /**
     * Deletes a course.
     *
     * @param string API secret key
     * @param string Course id field name
     * @param string Course id value
     */
    public function DeleteCourse(
        $secret_key,
        $course_id_field_name,
        $course_id_value
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->deleteCourseHelper(
                $course_id_field_name,
                $course_id_value
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * Deletes multiple courses.
     *
     * @param string API secret key
     * @param array Array of courses with elements of the form
     * array('course_id_field_name' => 'name_of_field', 'course_id_value' => 'value')
     *
     * @return array Array with elements like
     *               array('course_id_value' => 'value', 'result' => array('code' => 0, 'message' => 'Operation was successful')).
     *               Note that if the result array contains a code different
     *               than 0, an error occured
     */
    public function DeleteCourses($secret_key, $courses)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $results = [];
            foreach ($courses as $course) {
                $result_tmp = [];
                $result_op = $this->deleteCourseHelper($course['course_id_field_name'], $course['course_id_value']);
                $result_tmp['course_id_value'] = $course['course_id_value'];
                if ($result_op instanceof WSCMError) {
                    // Return the error in the results
                    $result_tmp['result'] = $result_op->toArray();
                } else {
                    $result_tmp['result'] = $this->getSuccessfulResult();
                }
                $results[] = $result_tmp;
            }

            return $results;
        }
    }

    /**
     * Creates a course.
     *
     * @param string API secret key
     * @param string Title
     * @param string Category code
     * @param string Wanted code. If it's not defined, it will be generated automatically
     * @param string Tutor name
     * @param string Course admin user id field name
     * @param string Course admin user id value
     * @param string Course language
     * @param string Course id field name
     * @param string Course id value
     * @param array Course extra fields
     *
     * @return int Course id generated
     */
    public function CreateCourse(
        $secret_key,
        $title,
        $category_code,
        $wanted_code,
        $tutor_name,
        $course_admin_user_id_field_name,
        $course_admin_user_id_value,
        $language,
        $course_id_field_name,
        $course_id_value,
        $extras
    ) {
        // First, verify the secret key
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->createCourseHelper(
                $title,
                $category_code,
                $wanted_code,
                $tutor_name,
                $course_admin_user_id_field_name,
                $course_admin_user_id_value,
                $language,
                $course_id_field_name,
                $course_id_value,
                $extras
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            } else {
                return $result;
            }
        }
    }

    /**
     * Create multiple courses.
     *
     * @param string API secret key
     * @param array Courses to be created, with elements following the structure presented in CreateCourse
     *
     * @return array Array with elements of the form
     *               array('course_id_value' => 'original value sent', 'course_id_generated' => 'value_generated', 'result' => array('code' => 0, 'message' => 'Operation was successful'))
     */
    public function CreateCourses($secret_key, $courses)
    {
        // First, verify the secret key
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSCMError) {
            $this->handleError($verifKey);
        } else {
            $results = [];
            foreach ($courses as $course) {
                $result_tmp = [];
                //reinitialize variables just in case
                $title = $category_code = $wanted_code = $tutor_name = $course_admin_user_id_field_name = $course_admin_user_id_value = $language = $course_id_field_name = $course_id_value = $extras = null;
                extract($course);
                $result = $this->createCourseHelper(
                    $title,
                    $category_code,
                    $wanted_code,
                    $tutor_name,
                    $course_admin_user_id_field_name,
                    $course_admin_user_id_value,
                    $language,
                    $course_id_field_name,
                    $course_id_value,
                    $extras
                );
                if ($result instanceof WSCMError) {
                    $result_tmp['result'] = $result->toArray();
                    $result_tmp['course_id_value'] = $course_id_value;
                    $result_tmp['course_id_generated'] = 0;
                } else {
                    $result_tmp['result'] = $this->getSuccessfulResult();
                    $result_tmp['course_id_value'] = $course_id_value;
                    $result_tmp['course_id_generated'] = $result;
                }
                $results[] = $result_tmp;
            }

            return $results;
        }
    }

    /**
     * Edits a course.
     *
     * @param string API secret key
     * @param string Course id field name
     * @param string Course id value
     * @param string Title
     * @param string Category code
     * @param string Department name
     * @param string Department url
     * @param string Course language
     * @param int Visibility
     * @param int Subscribe (0 = denied, 1 = allowed)
     * @param int Unsubscribe (0 = denied, 1 = allowed)
     * @param string Visual code
     * @param array Course extra fields
     */
    public function EditCourse(
        $secret_key,
        $course_id_field_name,
        $course_id_value,
        $title,
        $category_code,
        $department_name,
        $department_url,
        $language,
        $visibility,
        $subscribe,
        $unsubscribe,
        $visual_code,
        $extras
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSCMError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->editCourseHelper(
                $course_id_field_name,
                $course_id_value,
                $title,
                $category_code,
                $department_name,
                $department_url,
                $language,
                $visibility,
                $subscribe,
                $unsubscribe,
                $visual_code,
                $extras
            );
            if ($result instanceof WSCMError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * List courses.
     *
     * @param string API secret key
     * @param string Course id field name. Use "chamilo_course_id" to use internal id
     *
     * @return array An array with elements of the form
     *               ('id' => 'Course internal id', 'code' => 'Course code', 'title' => 'Course title', 'language' => 'Course language', 'visibility' => 'Course visibility',
     *               'category_name' => 'Name of the category of the course', 'number_students' => 'Number of students in the course', 'external_course_id' => 'External course id')
     */
    public function ListCourses($secret_key, $course_id_field_name)
    {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $courses_result = [];
            $category_names = [];

            $courses = CourseManager::get_courses_list();
            foreach ($courses as $course) {
                $course_tmp = [];
                $course_tmp['id'] = $course['id'];
                $course_tmp['code'] = $course['code'];
                $course_tmp['title'] = $course['title'];
                $course_tmp['language'] = $course['course_language'];
                $course_tmp['visibility'] = $course['visibility'];

                // Determining category name
                if ($category_names[$course['category_code']]) {
                    $course_tmp['category_name'] = $category_names[$course['category_code']];
                } else {
                    $category = CourseManager::get_course_category($course['category_code']);
                    $category_names[$course['category_code']] = $category['name'];
                    $course_tmp['category_name'] = $category['name'];
                }

                // Determining number of students registered in course
                $user_list = CourseManager::get_user_list_from_course_code($course['code']);
                $course_tmp['number_students'] = count($user_list);

                // Determining external course id
                $course_tmp['external_course_id'] = CourseManager::get_course_extra_field_value($course_id_field_name, $course['code']);
                $courses_result[] = $course_tmp;
            }

            return $courses_result;
        }
    }

    /**
     * Subscribe user to a course.
     *
     * @param string API secret key
     * @param string Course id field name. Use "chamilo_course_id" to use internal id
     * @param string course id value
     * @param string User id field name. Use "chamilo_user_id" to use internal id
     * @param string User id value
     * @param int Status (1 = Teacher, 5 = Student)
     */
    public function SubscribeUserToCourse(
        $secret_key,
        $course_id_field_name,
        $course_id_value,
        $user_id_field_name,
        $user_id_value,
        $status
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->changeUserSubscription(
                $course_id_field_name,
                $course_id_value,
                $user_id_field_name,
                $user_id_value,
                1,
                $status
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * Unsusbscribe user from course.
     *
     * @param string API secret key
     * @param string Course id field name. Use "chamilo_course_id" to use internal id
     * @param string course id value
     * @param string User id field name. Use "chamilo_user_id" to use internal id
     * @param string User id value
     */
    public function UnsubscribeUserFromCourse(
        $secret_key,
        $course_id_field_name,
        $course_id_value,
        $user_id_field_name,
        $user_id_value
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $result = $this->changeUserSubscription(
                $course_id_field_name,
                $course_id_value,
                $user_id_field_name,
                $user_id_value,
                0
            );
            if ($result instanceof WSError) {
                $this->handleError($result);
            }
        }
    }

    /**
     * Returns the descriptions of a course, along with their id.
     *
     * @param string API secret key
     * @param string Course id field name
     * @param string Course id value
     *
     * @return array Returns an array with elements of the form
     *               array('course_desc_id' => 1, 'course_desc_title' => 'Title', 'course_desc_content' => 'Content')
     */
    public function GetCourseDescriptions(
        $secret_key,
        $course_id_field_name,
        $course_id_value
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
            if ($course_id instanceof WSError) {
                return $course_id;
            } else {
                // Course exists, get its descriptions
                $descriptions = CourseDescription::get_descriptions($course_id);
                $results = [];
                foreach ($descriptions as $description) {
                    $results[] = ['course_desc_id' => $description->get_description_type(),
                        'course_desc_title' => $description->get_title(),
                        'course_desc_content' => $description->get_content(), ];
                }

                return $results;
            }
        }
    }

    /**
     * Edit course description.
     *
     * @param string API secret key
     * @param string Course id field name
     * @param string Course id value
     * @param int Category id from course description
     * @param string Description title
     * @param string Course description content
     */
    public function EditCourseDescription(
        $secret_key,
        $course_id_field_name,
        $course_id_value,
        $course_desc_id,
        $course_desc_title,
        $course_desc_content
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
            if ($course_id instanceof WSError) {
                return $course_id;
            } else {
                // Create the new course description
                $cd = new CourseDescription();
                $cd->set_description_type($course_desc_id);
                $cd->set_title($course_desc_title);
                $cd->set_content($course_desc_content);
                $cd->set_session_id(0);

                // Get course info
                $course_info = CourseManager::get_course_information(
                    CourseManager::get_course_code_from_course_id($course_id)
                );
                // Check if this course description exists
                $descriptions = CourseDescription::get_descriptions($course_id);
                $exists = false;
                foreach ($descriptions as $description) {
                    if ($description->get_description_type() == $course_desc_id) {
                        $exists = true;
                    }
                }
                $cd->set_course_id($course_info['real_id']);
                if (!$exists) {
                    $cd->set_progress(0);
                    $cd->insert();
                } else {
                    $cd->update();
                }
            }
        }
    }

    public function unreadMessage($username, $password)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $table_message = Database::get_main_table(TABLE_MESSAGE);
            $user_id = UserManager::get_user_id_from_username($username);
            $condition_msg_status = ' msg_status = 1 '; // define('MESSAGE_STATUS_UNREAD', '1');

            $sql_query = "SELECT COUNT(*) as number_messages
                          FROM $table_message 
                          WHERE $condition_msg_status AND user_receiver_id=".$user_id;

            $sql_result = Database::query($sql_query);
            $result = Database::fetch_array($sql_result);

            return $result['number_messages'];
        }

        return "0";
    }

    public function get_message_data($username, $password)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            $user_id = get_user_id_from_username($username);
        }
    }

    public function nada($username, $password)
    {
        if ($this->verifyUserPass($username, $password) == "valid") {
            return $username.$password;
        }

        return $username;
    }

    /**
     * Deletes a course (helper method).
     *
     * @param string Course id field name
     * @param string Course id value
     *
     * @return mixed True if the course was successfully deleted, WSError otherwise
     */
    protected function deleteCourseHelper(
        $course_id_field_name,
        $course_id_value
    ) {
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSCMError) {
            return $course_id;
        } else {
            $course_code = CourseManager::get_course_code_from_course_id(
                $course_id
            );
            CourseManager::delete_course($course_code);

            return true;
        }
    }

    /**
     * Creates a course (helper method).
     *
     * @param string Title
     * @param string Category code
     * @param string Wanted code. If it's not defined, it will be generated automatically
     * @param string Tutor name
     * @param string Course admin user id field name
     * @param string Course admin user id value
     * @param string Course language
     * @param string Course id field name
     * @param string Course id value
     * @param array Course extra fields
     *
     * @return mixed Generated id if creation was successful, WSError otherwise
     */
    protected function createCourseHelper(
        $title,
        $category_code,
        $wanted_code,
        $tutor_name,
        $course_admin_user_id_field_name,
        $course_admin_user_id_value,
        $language,
        $course_id_field_name,
        $course_id_value,
        $extras
    ) {
        // Add the original course id field name and value to the extra fields if needed
        $extras_associative = [];
        if ($course_id_field_name != "chamilo_course_id") {
            $extras_associative[$course_id_field_name] = $course_id_value;
        }
        foreach ($extras as $extra) {
            $extras_associative[$extra['field_name']] = $extra['field_value'];
        }
        $course_admin_id = $this->getUserId($course_admin_user_id_field_name, $course_admin_user_id_value);
        if ($course_admin_id instanceof WSError) {
            return $course_admin_id;
        }
        if ($wanted_code == '') {
            $wanted_code = CourseManager::generate_course_code($title);
        }
        $result = create_course(
            $wanted_code,
            $title,
            $tutor_name,
            $category_code,
            $language,
            $course_admin_id,
            $this->_configuration['db_prefix'],
            0
        );
        if (!$result) {
            return new WSError(202, 'There was an error creating the course');
        } else {
            // Update extra fields
            foreach ($extras_associative as $fname => $fvalue) {
                CourseManager::update_course_extra_field_value($result, $fname, $fvalue);
            }
            // Get course id
            $course_info = CourseManager::get_course_information($result);

            return $course_info['real_id'];
        }
    }

    /**
     * Edits a course (helper method).
     *
     * @param string Course id field name
     * @param string Course id value
     * @param string Title
     * @param string Category code
     * @param string Department name
     * @param string Department url
     * @param string Course language
     * @param int Visibility
     * @param int Subscribe (0 = denied, 1 = allowed)
     * @param int Unsubscribe (0 = denied, 1 = allowed)
     * @param string Visual code
     * @param array Course extra fields
     *
     * @return mixed True in case of success, WSError otherwise
     */
    protected function editCourseHelper(
        $course_id_field_name,
        $course_id_value,
        $title,
        $category_code,
        $department_name,
        $department_url,
        $language,
        $visibility,
        $subscribe,
        $unsubscribe,
        $visual_code,
        $extras
    ) {
        $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
        if ($course_id instanceof WSCMError) {
            return $course_id;
        } else {
            $attributes = [];
            if (!empty($title)) {
                $attributes['title'] = $title;
            }
            if (!empty($category_code)) {
                $attributes['category_code'] = $category_code;
            }
            if (!empty($department_name)) {
                $attributes['department_name'] = $department_name;
            }
            if (!empty($department_url)) {
                $attributes['department_url'] = $department_url;
            }
            if (!empty($language)) {
                $attributes['course_language'] = $language;
            }
            if ($visibility != '') {
                $attributes['visibility'] = (int) $visibility;
            }
            if ($subscribe != '') {
                $attributes['subscribe'] = (int) $subscribe;
            }
            if ($unsubscribe != '') {
                $attributes['unsubscribe'] = (int) $unsubscribe;
            }
            if (!empty($visual_code)) {
                $attributes['visual_code'] = $visual_code;
            }
            if (!empty($attributes)) {
                CourseManager::update_attributes($course_id, $attributes);
            }
            if (!empty($extras)) {
                $course_code = CourseManager::get_course_code_from_course_id($course_id);
                $extras_associative = [];
                foreach ($extras as $extra) {
                    $extras_associative[$extra['field_name']] = $extra['field_value'];
                }
                foreach ($extras_associative as $fname => $fvalue) {
                    CourseManager::update_extra_field_value($course_code, $fname, $fvalue);
                }
            }

            return true;
        }
    }

    /**
     * Subscribe or unsubscribe user to a course (helper method).
     *
     * @param string Course id field name. Use "chamilo_course_id" to use internal id
     * @param string course id value
     * @param string User id field name. Use "chamilo_user_id" to use internal id
     * @param string User id value
     * @param int Set to 1 to subscribe, 0 to unsubscribe
     * @param int Status (STUDENT or TEACHER) Used for subscription only
     *
     * @return mixed True if subscription or unsubscription was successful, false otherwise
     */
    protected function changeUserSubscription(
        $course_id_field_name,
        $course_id_value,
        $user_id_field_name,
        $user_id_value,
        $state,
        $status = STUDENT
    ) {
        $course_id = $this->getCourseId($course_id_field_name, $course_id_value);
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $user_id = $this->getUserId($user_id_field_name, $user_id_value);
            if ($user_id instanceof WSError) {
                return $user_id;
            } else {
                $course_code = CourseManager::get_course_code_from_course_id($course_id);
                if ($state == 0) {
                    // Unsubscribe user
                    CourseManager::unsubscribe_user($user_id, $course_code);

                    return true;
                } else {
                    // Subscribe user
                    if (CourseManager::subscribeUser($user_id, $course_code, $status)) {
                        return true;
                    } else {
                        return new WSError(203, 'An error occured subscribing to this course');
                    }
                }
            }
        }
    }
}
