<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.webservices
 */
require_once(dirname(__FILE__).'/../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
require_once($libpath.'sessionmanager.lib.php');
require_once($libpath.'course.lib.php');
require_once(dirname(__FILE__).'/webservice.php');

/**
 * Web services available for the Session module. This class extends the WS class
 */
class WSSession extends WS {

	/**
	 * Creates a session (helper method)
	 *
	 * @param string Name of the session
	 * @param string Start date, use the 'YYYY-MM-DD' format
	 * @param string End date, use the 'YYYY-MM-DD' format
	 * @param int Access delays of the coach (days before)
	 * @param int Access delays of the coach (days after)
	 * @param int Nolimit (0 = no limit of time, 1 = limit of time)
	 * @param int Visibility
	 * @param string User id field name for the coach
	 * @param string User id value for the coach
	 * @param string Original session id field name (use "chamilo_session_id" to use internal id)
	 * @param string Original session id value
	 * @param array Array of extra fields
	 * @return mixed Generated id in case of success, WSError otherwise
	 */
	protected function createSessionHelper($name, $start_date, $end_date, $nb_days_access_before, $nb_days_access_after, $nolimit, $visibility, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, $extras) {
		// Verify that coach exists and get its id
		$user_id = $this->getUserId($user_id_field_name, $user_id_value);
		if($user_id instanceof WSError) {
			return $user_id;
		}
		// Build the date
		$start_date_array = explode('-', $start_date);
		foreach($start_date_array as &$sd_element) {
			$sd_element = intval($sd_element);
		}
		$end_date_array = explode('-', $end_date);
		foreach($end_date_array as &$ed_element) {
			$ed_element = intval($ed_element);
		}
		// Try to create the session
		$session_id = SessionManager::create_session($name, $start_date_array[0], $start_date_array[1], $start_date_array[2], $end_date_array[0], $end_date_array[1], $end_date_array[2], (int)$nb_days_access_before, (int)$nb_days_access_after, (int)$nolimit, $user_id, 0, (int)$visibility);
		if(!is_int($session_id)) {
			return new WSError(301, 'Could not create the session');
		} else {
			// Add the Original session id to the extra fields
			$extras_associative = array();
			if($session_id_field_name != "chamilo_session_id") {
				$extras_associative[$session_id_field_name] = $session_id_value;
			}
			foreach($extras as $extra) {
				$extras_associative[$extra['field_name']] = $extra['field_value'];
			}
			// Create the extra fields
			foreach($extras_associative as $fname => $fvalue) {
				SessionManager::create_session_extra_field($fname, 1, $fname);
				SessionManager::update_session_extra_field_value($session_id, $fname, $fvalue);
			}
			return $session_id;
		}
	}

	/**
	 * Creates a session
	 *
	 * @param string API secret key
	 * @param string Name of the session
	 * @param string Start date, use the 'YYYY-MM-DD' format
	 * @param string End date, use the 'YYYY-MM-DD' format
	 * @param int Access delays of the coach (days before)
	 * @param int Access delays of the coach (days after)
	 * @param int Nolimit (0 = no limit of time, 1 = limit of time)
	 * @param int Visibility
	 * @param string User id field name for the coach
	 * @param string User id value for the coach
	 * @param string Original session id field name (use "chamilo_session_id" to use internal id)
	 * @param string Original session id value
	 * @param array Array of extra fields
	 * @return int Session id generated
	 */
	public function CreateSession($secret_key, $name, $start_date, $end_date, $nb_days_access_before, $nb_days_access_after, $nolimit, $visibility, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, $extras) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$session_id = $this->createSessionHelper($name, $start_date, $end_date, $nb_days_access_before, $nb_days_access_after, $nolimit, $visibility, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, $extras);
			if($session_id instanceof WSError) {
				$this->handleError($session_id);
			} else {
				return $session_id;
			}
		}
	}

	/**
	 * Deletes a session (helper method)
	 *
	 * @param string Session id field name
	 * @param string Session id value
	 * @return mixed True in case of success, WSError otherwise
	 */
	protected function deleteSessionHelper($session_id_field_name, $session_id_value) {
		$session_id = $this->getSessionId($session_id_field_name, $session_id_value);
		if($session_id instanceof WSError) {
			return $session_id;
		} else {
			SessionManager::delete_session($session_id, true);
			return true;
		}
	}

	/**
	 * Deletes a session
	 *
	 * @param string API secret key
	 * @param string Session id field name
	 * @param string Session id value
	 */
	public function DeleteSession($secret_key, $session_id_field_name, $session_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->deleteSessionHelper($session_id_field_name, $session_id_value);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}

	/**
	 * Edits a session (helper method)
	 *
	 * @param string Name of the session
	 * @param string Start date, use the 'YYYY-MM-DD' format
	 * @param string End date, use the 'YYYY-MM-DD' format
	 * @param int Access delays of the coach (days before)
	 * @param int Access delays of the coach (days after)
	 * @param int Nolimit (0 = no limit of time, 1 = limit of time)
	 * @param int Visibility
	 * @param string User id field name for the coach
	 * @param string User id value for the coach
	 * @param string Original session id field name (use "chamilo_session_id" to use internal id)
	 * @param string Original session id value
	 * @param array Array of extra fields
	 * @return mixed True on success, WSError otherwise
	 */
	protected function editSessionHelper($name, $start_date, $end_date, $nb_days_access_before, $nb_days_access_after, $nolimit, $visibility, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, $extras) {
		$session_id = $this->getSessionId($session_id_field_name, $session_id_value);
		if($session_id instanceof WSError) {
			return $session_id;
		} else {
			// Verify that coach exists and get its id
			$user_id = $this->getUserId($user_id_field_name, $user_id_value);
			if($user_id instanceof WSError) {
				return $user_id;
			}
			// Build the date
			$start_date_array = explode('-', $start_date);
			foreach($start_date_array as &$sd_element) {
				$sd_element = intval($sd_element);
			}
			$end_date_array = explode('-', $end_date);
			foreach($end_date_array as &$ed_element) {
				$ed_element = intval($ed_element);
			}
			$result_id = SessionManager::edit_session($session_id, $name, $start_date_array[0], $start_date_array[1], $start_date_array[2], $end_date_array[0], $end_date_array[1], $end_date_array[2], (int)$nb_days_access_before, (int)$nb_days_access_after, (int)$nolimit, $user_id, 0, (int)$visibility);
			if(!is_int($result_id)) {
				return new WSError(302, 'Could not edit the session');
			} else {
				if(!empty($extras)) {
					$extras_associative = array();
					foreach($extras as $extra) {
						$extras_associative[$extra['field_name']] = $extra['field_value'];
					}
					// Create the extra fields
					foreach($extras_associative as $fname => $fvalue) {
						SessionManager::create_session_extra_field($fname, 1, $fname);
						SessionManager::update_session_extra_field_value($session_id, $fname, $fvalue);
					}
				}
				return true;
			}
		}
	}

	/**
	 * Edits a session
	 *
	 * @param string API secret key
	 * @param string Name of the session
	 * @param string Start date, use the 'YYYY-MM-DD' format
	 * @param string End date, use the 'YYYY-MM-DD' format
	 * @param int Access delays of the coach (days before)
	 * @param int Access delays of the coach (days after)
	 * @param int Nolimit (0 = no limit of time, 1 = limit of time)
	 * @param int Visibility
	 * @param string User id field name for the coach
	 * @param string User id value for the coach
	 * @param string Original session id field name (use "chamilo_session_id" to use internal id)
	 * @param string Original session id value
	 * @param array Array of extra fields
	 */
	public function EditSession($secret_key, $name, $start_date, $end_date, $nb_days_access_before, $nb_days_access_after, $nolimit, $visibility, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, $extras) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->editSessionHelper($name, $start_date, $end_date, $nb_days_access_before, $nb_days_access_after, $nolimit, $visibility, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, $extras);
			if($session_id_value instanceof WSError) {
				$this->handleError($result);
			}
		}
	}

	/**
	 * Change user subscription (helper method)
	 *
	 * @param string User id field name
	 * @param string User id value
	 * @param string Session id field name
	 * @param string Session id value
	 * @param int State (1 to subscribe, 0 to unsubscribe)
	 * @return mixed True on success, WSError otherwise
	 */
	protected function changeUserSubscription($user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, $state) {
		$session_id = $this->getSessionId($session_id_field_name, $session_id_value);
		if($session_id instanceof WSError) {
			return $session_id;
		} else {
			$user_id = $this->getUserId($user_id_field_name, $user_id_value);
			if($user_id instanceof WSError) {
				return $user_id;
			} else {
				if($state  == 1) {
					SessionManager::suscribe_users_to_session($session_id, array($user_id));
				} else {
					$result = SessionManager::unsubscribe_user_from_session($session_id, $user_id);
					if (!$result) {
						return new WSError(303, 'There was an error unsubscribing this user from the session');
					}
				}
				return true;
			}
		}
	}

	/**
	 * Subscribe user to a session
	 *
	 * @param string API secret key
	 * @param string User id field name
	 * @param string User id value
	 * @param string Session id field name
	 * @param string Session id value
	 */
	public function SubscribeUserToSession($secret_key, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->changeUserSubscription($user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, 1);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}

	/**
	 * Subscribe user to a session
	 *
	 * @param string API secret key
	 * @param string User id field name
	 * @param string User id value
	 * @param string Session id field name
	 * @param string Session id value
	 */
	public function UnsubscribeUserFromSession($secret_key, $user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->changeUserSubscription($user_id_field_name, $user_id_value, $session_id_field_name, $session_id_value, 0);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}

	/**
	 * Change course subscription
	 *
	 * @param string Course id field name
	 * @param string Course id value
	 * @param string Session id field name
	 * @param string Session id value
	 * @param int State (1 to subscribe, 0 to unsubscribe)
	 * @return mixed True on success, WSError otherwise
	 */
	protected function changeCourseSubscription($course_id_field_name, $course_id_value, $session_id_field_name, $session_id_value, $state) {
		$session_id = $this->getSessionId($session_id_field_name, $session_id_value);
		if($session_id instanceof WSError) {
			return $session_id;
		} else {
			$course_id = $this->getCourseId($course_id_field_name, $course_id_value);
			if($course_id instanceof WSError) {
				return $course_id;
			} else {
				if($state  == 1) {
					$course_code = CourseManager::get_course_code_from_course_id($course_id);
					SessionManager::add_courses_to_session($session_id, array($course_code));
					return true;
				} else {
					$result = SessionManager::unsubscribe_course_from_session($session_id, $course_id);
					if ($result) {
						return true;
					} else {
						return new WSError(304, 'Error unsubscribing course from session');
					}
				}
			}
		}
    }

	/**
	 * Subscribe course to session
	 *
	 * @param string API secret key
	 * @param string Course id field name
	 * @param string Course id value
	 * @param string Session id field name
	 * @param string Session id value
	 */
	public function SubscribeCourseToSession($secret_key, $course_id_field_name, $course_id_value, $session_id_field_name, $session_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->changeCourseSubscription($course_id_field_name, $course_id_value, $session_id_field_name, $session_id_value, 1);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}

	/**
	 * Unsubscribe course from session
	 *
	 * @param string API secret key
	 * @param string Course id field name
	 * @param string Course id value
	 * @param string Session id field name
	 * @param string Session id value
	 */
	public function UnsubscribeCourseFromSession($secret_key, $course_id_field_name, $course_id_value, $session_id_field_name, $session_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->changeCourseSubscription($course_id_field_name, $course_id_value, $session_id_field_name, $session_id_value, 0);
			if($result instanceof WSError) {
				$this->handleError($result);
			}
		}
	}

}
