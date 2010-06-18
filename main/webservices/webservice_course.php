<?php

require_once(dirname(__FILE__).'/../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
require_once $libpath.'add_course.lib.inc.php';
require_once(dirname(__FILE__).'/webservice.php');

/**
 * Web services available for the Course module. This class extends the WS class
 */
class WSCourse extends WS {
	/**
	 * Deletes a course (helper method)
	 * 
	 * @param string Course id field name
	 * @param string Course id value
	 * @return mixed True if the course was successfully deleted, WSError otherwise
	 */
	protected function deleteCourseHelper($course_id_field_name, $course_id_value) {
		$course_id = $this->getCourseId($course_id_field_name, $course_id_value);
		if($course_id instanceof WSError) {
			return $course_id;
		} else {
			$course_code = CourseManager::get_course_code_from_course_id($course_id);
			if(!CourseManager::delete_course($course_code)) {
				return new WSError(201, "There was a problem while deleting this course");
			} else {
				return true;
			}
		}
	}
	
	/**
	 * Deletes a course
	 * 
	 * @param string API secret key
	 * @param string Course id field name
	 * @param string Course id value
	 */
	public function DeleteCourse($secret_key, $course_id_field_name, $course_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->deleteCourseHelper($course_id_field_name, $course_id_value);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}
	
	/**
	 * Deletes multiple courses
	 * 
	 * @param string API secret key
	 * @param array Array of courses with elements of the form array('course_id_field_name' => 'name_of_field', 'course_id_value' => 'value')
	 * @return array Array with elements like array('course_id_value' => 'value', 'result' => array('code' => 0, 'message' => 'Operation was successful')). Note that if the result array contains a code different
	 * than 0, an error occured
	 */
	public function DeleteCourses($secret_key, $courses) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$results = array();
			foreach($users as $user) {
				$result_tmp = array();
				$result_op = $this->deleteCourseHelper($course['course_id_field_name'], $course['course_id_value']);
				$result_tmp['course_id_value'] = $course['course_id_value'];
				if($result_op instanceof WSError) {
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
	 * Creates a course (helper method)
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
	 * @return mixed Generated id if creation was successful, WSError otherwise
	 */
	protected function createCourseHelper($title, $category_code, $wanted_code, $tutor_name, $course_admin_user_id_field_name, $course_admin_user_id_value, $language, $course_id_field_name, $course_id_value, $extras) {
		// Add the original course id field name and value to the extra fields if needed
		$extras_associative = array();
		if($course_id_field_name != "chamilo_course_id") {
			$extras_associative[$course_id_field_name] = $course_id_value;
		}
		foreach($extras as $extra) {
			$extras_associative[$extra['field_name']] = $extra['field_value'];
		}
		$course_admin_id = $this->getUserId($course_admin_user_id_field_name, $course_admin_user_id_value);
		if($wanted_code == '') {
			$wanted_code = generate_course_code($title);
		}
		$result = create_course($wanted_code, $title, $tutor_name, $category_code, $course_admin_id, $this->_configuration['db_prefix'], 0);
		if($result == false) {
			return new WSError(202, 'There was an error creating the course');
		} else {
			// Update extra fields
			foreach($extras_associative as $fname => $fvalue) {
				CourseManager::update_course_extra_field_value($result, $fname, $fvalue);
			}
			// Get course id
			$course_info = CourseManager::get_course_information($result);
			return $course_info['id'];
		}
	}
	
	/**
	 * Creates a course
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
	 * @return int Course id generated
	 */
	public function CreateCourse($secret_key, $title, $category_code, $wanted_code, $tutor_name, $course_admin_user_id_field_name, $course_admin_user_id_value, $language, $course_id_field_name, $course_id_value, $extras) {
		// First, verify the secret key
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->createCourseHelper($title, $category_code, $wanted_code, $tutor_name, $course_admin_user_id_field_name, $course_admin_user_id_value, $language, $course_id_field_name, $course_id_value, $extras);
			if($result instanceof WSError) {
				$this->handleError($result);
			} else {
				return $result;
			}
		}
	}
	
	/**
	 * Create multiple courses
	 * 
	 * @param string API secret key
	 * @param array Courses to be created, with elements following the structure presented in CreateCourse
	 * @return array Array with elements of the form array('course_id_value' => 'original value sent', 'course_id_generated' => 'value_generated', 'result' => array('code' => 0, 'message' => 'Operation was successful'))
	 */
	public function CreateCourses($secret_key, $courses) {
		// First, verify the secret key
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$results = array();
			foreach($courses as $course) {
				$result_tmp = array();
				extract($course);
				$result = $this->createCourseHelper($title, $category_code, $wanted_code, $tutor_name, $course_admin_user_id_field_name, $course_admin_user_id_value, $language, $course_id_field_name, $course_id_value, $extras);
				if($result instanceof WSError) {
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
	 * Edits a course (helper method)
	 * 
	 * @param string Course id field name
	 * @param string Course id value
	 * @param ...
	 */
	
	/**
	 * Subscribe or unsubscribe user to a course (helper method)
	 * 
	 * @param string Course id field name. Use "chamilo_course_id" to use internal id
	 * @param string Course id value.
	 * @param string User id field name. Use "chamilo_user_id" to use internal id
	 * @param string User id value
	 * @param int Set to 1 to subscribe, 0 to unsubscribe
	 * @param int Status (STUDENT or TEACHER) Used for subscription only
	 * @return mixed True if subscription or unsubscription was successful, false otherwise
	 */
	protected function changeUserSubscription($course_id_field_name, $course_id_value, $user_id_field_name, $user_id_value, $state, $status = STUDENT) {
		$course_id = $this->getCourseId($course_id_field_name, $course_id_value);
		if($course_id instanceof WSError) {
			return $course_id;
		} else {
			$user_id = $this->getUserId($user_id_field_name, $user_id_value);
			if($user_id instanceof WSError) {
				return $user_id;
			} else {
				$course_code = CourseManager::get_course_code_from_course_id($course_id);
				if($state == 0) {
					// Unsubscribe user
					CourseManager::unsubscribe_user($user_id, $course_code);
					return true;
				} else {
					// Subscribe user
					if(CourseManager::subscribe_user($user_id, $course_code, $status)) {
						return true;
					} else {
						return new WSError(203, 'An error occured subscribing to this course');
					}
				}
			}
		}
	}
	
	/**
	 * Subscribe user to a course
	 * 
	 * @param string API secret key
	 * @param string Course id field name. Use "chamilo_course_id" to use internal id
	 * @param string Course id value.
	 * @param string User id field name. Use "chamilo_user_id" to use internal id
	 * @param string User id value
	 * @param int Status (1 = Teacher, 5 = Student)
	 */
	public function SubscribeUserToCourse($secret_key, $course_id_field_name, $course_id_value, $user_id_field_name, $user_id_value, $status) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->changeUserSubscription($course_id_field_name, $course_id_value, $user_id_field_name, $user_id_value, 1, $status);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}
	
	/**
	 * Unsusbscribe user from course
	 * 
	 * @param string API secret key
	 * @param string Course id field name. Use "chamilo_course_id" to use internal id
	 * @param string Course id value.
	 * @param string User id field name. Use "chamilo_user_id" to use internal id
	 * @param string User id value
	 */
	public function UnsubscribeUserFromCourse($secret_key, $course_id_field_name, $course_id_value, $user_id_field_name, $user_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->changeUserSubscription($course_id_field_name, $course_id_value, $user_id_field_name, $user_id_value, 0);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}
	
	/**
	 * Edit course description
	 * 
	 * @param string API secret key
	 * @param string Course id field name
	 * @param string Course id value
	 * @param int Course description id
	 * @param string Description title
	 * @param string Course description content
	 */
	
	
	
}

