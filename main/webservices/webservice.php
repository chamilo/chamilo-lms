<?php
require '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'usermanager.lib.php';

/**
 * Error returned by one of the methods of the web service. Contains an error code and an error message
 */
class WSError {
	/**
	 * Error handler. This needs to be a class that implements the interface WSErrorHandler
	 * 
	 * @var WSErrorHandler
	 */
	protected static $_handler;
	
	/**
	 * Error code
	 * 
	 * @var int
	 */
	public $code;
	
	/**
	 * Error message
	 * 
	 * @var string
	 */
	public $message;
	
	/**
	 * Constructor
	 * 
	 * @param int Error code
	 * @param string Error message
	 */
	public function __construct($code, $message) {
		$this->code = $code;
		$this->message = $message;
	}
	
	/**
	 * Sets the error handler
	 * 
	 * @param WSErrorHandler Error handler
	 */
	public static function setErrorHandler($handler) {
		if($handler instanceof WSErrorHandler) {
			self::$_handler = $handler;
		}
	}
	
	/**
	 * Returns the error handler
	 * 
	 * @return WSErrorHandler Error handler
	 */
	public static function getErrorHandler() {
		return self::$_handler;
	}
}

/**
 * Interface that must be implemented by any error handler
 */
interface WSErrorHandler {
	/**
	 * Handle method
	 * 
	 * @param WSError Error
	 */
	public function handle($error);
}

/**
 * Main class of the webservice
 */
class WS {

	/**
	 * Verifies the API key
	 * 
	 * @param string Secret key
	 * @return mixed WSError in case of failure, null in case of success
	 */
	protected function verifyKey($secret_key) {
		global $_configuration;
		
		$security_key = $_SERVER['REMOTE_ADDR'].$_configuration['security_key'];

		if(!api_is_valid_secret_key($secret_key, $security_key)) {
			return new WSError(1, "API key is invalid");
		} else {
			return null;
		}
	}
	
	/**
	 * Gets the real user id based on the user id field name and value. Note that if the user id field name is "chamilo_user_id", it will use the user id
	 * in the system database
	 * 
	 * @param string User id field name
	 * @param string User id value
	 * @return mixed System user id if the user was found, WSError otherwise
	 */
	protected function getUserId($user_id_field_name, $user_id_value) {
		if($user_id_field_name == "chamilo_user_id") {
			if(UserManager::is_user_id_valid(intval($user_id_value))) {
				return intval($user_id_value);
			} else {
				return new WSError(2, "User was not found");
			}
		} else {
			$user_id = UserManager::get_user_id_from_original_id($user_id_value, $user_id_field_name);
			if($user_id == 0) {
				return new WSError(2, "User was not found");
			} else {
				return $user_id;
			}
		}
	}
	
	/**
	 * Handles an error by calling the WSError error handler
	 * 
	 * @param WSError Error
	 */
	protected function handleError($error) {
		$handler = WSError::getErrorHandler();
		$handler->handle($error);
	}
	
	/**
	 * Test function. Returns the string success
	 * 
	 * @return string Success
	 */
	public function test() {
		return "success";
	}
	
	/**
	 * Enables or disables a user
	 * 
	 * @param string User id field name
	 * @param string User id value
	 * @param int Set to 1 to enable and to 0 to disable
	 */
	protected function changeUserActiveState($user_id_field_name, $user_id_value, $state) {
		$user_id = $this->getUserId($user_id_field_name, $user_id_value);
		if($user_id instanceof WSError) {
			$this->handleError($user_id);
		} else {
			if($state == 0) {
				UserManager::disable($user_id);
			} else if($state == 1) {
				UserManager::enable($user_id);
			}
		}
	}
	
	/**
	 * Disables a user
	 * 
	 * @param string API secret key
	 * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
	 * @param string User id value
	 */
	public function DisableUser($secret_key, $user_id_field_name, $user_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			// Let the implementation handle it
			$this->handleError($verifKey);
		} else {
			$this->changeUserActiveState($user_id_field_name, $user_id_value, 0);
		}
	}
	
	/**
	 * Enables a user
	 * 
	 * @param string API secret key
	 * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
	 * @param string User id value
	 */
	public function EnableUser($secret_key, $user_id_field_name, $user_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$this->changeUserActiveState($user_id_field_name, $user_id_value, 1);
		}
	}
	
	/**
	 * Deletes a user
	 * 
	 * @param string API secret key
	 * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
	 * @param string User id value
	 */
	public function DeleteUser($secret_key, $user_id_field_name, $user_id_value) {
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$user_id = $this->getUserId($user_id_field_name, $user_id_value);
			if($user_id instanceof WSError) {
				$this->handleError($user_id);
			} else {
				if(!UserManager::delete_user($user_id)) {
					$error = new WSError(3, "There was a problem while deleting this user");
					$this->handleError($error);
				}
			}
		}
	}
	
	/**
	 * Creates a user (helper method)
	 * 
	 * @param string User first name
	 * @param string User last name
	 * @param int User status
	 * @param string Login name
	 * @param string Password (encrypted or not)
	 * @param string Encrypt method. Leave blank if you are passing the password in clear text, set to the encrypt method used to encrypt the password otherwise. Remember 
	 * to include the salt in the extra fields if you are encrypting the password
	 * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
	 * @param string User id value. Leave blank if you are using the internal user_id
	 * @param int Visibility.
	 * @param string User email.
	 * @param string Language.
	 * @param string Phone.
	 * @param string Expiration date
	 * @param array Extra fields. An array with elements of the form ('field_name' => 'name_of_the_field', 'field_value' => 'value_of_the_field').
	 * @return mixed New user id generated by the system, WSError otherwise
	 */
	protected function createUserHelper($firstname, $lastname, $status, $login, $password, $encrypt_method, $user_id_field_name, $user_id_value, $visibility, $email, $language, $phone, $expiration_date, $extras) {
		// Add the original user id field name and value to the extra fields if needed
		$extras_associative = array();
		if($user_id_field_name != "chamilo_user_id") {
			$extras_associative[$user_id_field_name] = $user_id_value;
		}
		foreach($extras as $extra) {
			$extras_associative[$extra['field_name']] = $extra['field_value'];
		}
		$result = UserManager::create_user($firstname, $lastname, $status, $email, $login, $password, '', $language, $phone, '', PLATFORM_AUTH_SOURCE, $expiration_date, $visibility, 0, $extras_associative, $encrypt_method);
		if($result == false) {
			$failure = $api_failureList[0];
			if($failure == 'login-pass already taken') {
				return new WSError(4, 'This username is already taken');
			} else if($failure == 'encrypt_method invalid') {
				return new WSError(5, 'The encryption of the password is invalid');
			} else {
				return new WSError(6, 'There was an error creating the user');
			}
		} else {
			return $result;
		}
	}
	
	/**
	 * Creates a user
	 * 
	 * @param string API secret key
	 * @param string User first name
	 * @param string User last name
	 * @param int User status
	 * @param string Login name
	 * @param string Password (encrypted or not)
	 * @param string Encrypt method. Leave blank if you are passing the password in clear text, set to the encrypt method used to encrypt the password otherwise. Remember 
	 * to include the salt in the extra fields if you are encrypting the password
	 * @param string User id field name. Use "chamilo_user_id" as the field name if you want to use the internal user_id
	 * @param string User id value. Leave blank if you are using the internal user_id
	 * @param int Visibility. Set by default to 1
	 * @param string User email. Set by default to an empty string
	 * @param string Language. Set by default to english
	 * @param string Phone. Set by default to an empty string
	 * @param string Expiration date. Set to null by default
	 * @param array Extra fields. An array with elements of the form ('field_name' => 'name_of_the_field', 'field_value' => 'value_of_the_field'). Set to an empty array by default
	 * @return mixed New user id generated by the system
	 */
	public function CreateUser($secret_key, $firstname, $lastname, $status, $login, $password, $encrypt_method, $user_id_field_name, $user_id_value, $visibility = 1, $email = '', $language = 'english', $phone = '', $expiration_date = '0000-00-00 00:00:00', $extras = array()) {
		// First, verify the secret key
		$verifKey = $this->verifyKey($secret_key);
		if($verifKey instanceof WSError) {
			$this->handleError($verifKey);
		} else {
			$result = $this->createUserHelper($firstname, $lastname, $status, $login, $password, $encrypt_method, $user_id_field_name, $user_id_value, $visibility, $email, $language, $phone, $expiration_date, $extras);
			if($result instanceof WSError) {
				$this->handleError($result);
			} else {
				return $result;
			}
		}
	}
	
	
}

