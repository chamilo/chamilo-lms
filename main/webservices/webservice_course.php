<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/webservice.php';

/**
 * Web services available for the Course module. This class extends the WS class.
 */
class WSCourse extends WS
{
    /**
     * List courses.
     *
     * @param string API secret key
     * @param string A list of visibility filter we want to apply
     *
     * @return array An array with elements of the form
     *               ('id' => 'Course internal id', 'code' => 'Course code', 'title' => 'Course title', 'language' => 'Course language', 'visibility' => 'Course visibility',
     *               'category_name' => 'Name of the category of the course', 'number_students' => 'Number of students in the course', 'external_course_id' => 'External course id')
     */
    public function ListCourses(
        $secret_key,
        $visibility = 'public,public-registered,private,closed'
    ) {
        $verifKey = $this->verifyKey($secret_key);
        if ($verifKey instanceof WSError) {
            $this->handleError($verifKey);
        } else {
            $visibilities = split(',', $visibility);
            $vis = [
                'public' => '3',
                'public-registered' => '2',
                'private' => '1',
                'closed' => '0',
            ];
            foreach ($visibilities as $p => $visibility) {
                $visibilities[$p] = $vis[$visibility];
            }
            $courses_result = [];
            $category_names = [];

            $courses = CourseManager::get_courses_list();
            foreach ($courses as $course) {
                //skip elements that do not match required visibility
                if (!in_array($course['visibility'], $visibilities)) {
                    continue;
                }
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
                    $category = CourseManager::get_course_category(
                        $course['category_code']
                    );
                    $category_names[$course['category_code']] = $category['name'];
                    $course_tmp['category_name'] = $category['name'];
                }

                // Determining number of students registered in course
                $user_list = CourseManager::get_user_list_from_course_code(
                    $course['code'],
                    0
                );
                $course_tmp['number_students'] = count($user_list);

                // Determining external course id - this code misses the external course id field name
                // $course_tmp['external_course_id'] = CourseManager::get_course_extra_field_value($course_field_name, $course['code']);

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
            $course_id = $this->getCourseId(
                $course_id_field_name,
                $course_id_value
            );
            if ($course_id instanceof WSError) {
                return $course_id;
            } else {
                // Course exists, get its descriptions
                $descriptions = CourseDescription::get_descriptions($course_id);
                $results = [];
                foreach ($descriptions as $description) {
                    $results[] = [
                        'course_desc_id' => $description->get_description_type(
                        ),
                        'course_desc_title' => $description->get_title(),
                        'course_desc_content' => $description->get_content(),
                    ];
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
            $course_id = $this->getCourseId(
                $course_id_field_name,
                $course_id_value
            );
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
                $cd->set_course_id($course_info['real_id']);
                // Check if this course description exists
                $descriptions = CourseDescription::get_descriptions($course_id);
                $exists = false;
                foreach ($descriptions as $description) {
                    if ($description->get_description_type() == $course_desc_id) {
                        $exists = true;
                    }
                }
                if (!$exists) {
                    $cd->set_progress(0);
                    $cd->insert();
                } else {
                    $cd->update();
                }
            }
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
        $course_id = $this->getCourseId(
            $course_id_field_name,
            $course_id_value
        );
        if ($course_id instanceof WSError) {
            return $course_id;
        } else {
            $user_id = $this->getUserId($user_id_field_name, $user_id_value);
            if ($user_id instanceof WSError) {
                return $user_id;
            } else {
                $course_code = CourseManager::get_course_code_from_course_id(
                    $course_id
                );
                if ($state == 0) {
                    // Unsubscribe user
                    CourseManager::unsubscribe_user($user_id, $course_code);

                    return true;
                } else {
                    // Subscribe user
                    if (CourseManager::subscribeUser(
                        $user_id,
                        $course_code,
                        $status
                    )
                    ) {
                        return true;
                    } else {
                        return new WSError(
                            203,
                            'An error occured subscribing to this course'
                        );
                    }
                }
            }
        }
    }
}
