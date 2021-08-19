<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_webservices();

/**
 * Error returned by one of the methods of the web service.
 * Contains an error code and an error message.
 */
class WSError
{
    /**
     * Error code.
     *
     * @var int
     */
    public $code;

    /**
     * Error message.
     *
     * @var string
     */
    public $message;
    /**
     * Error handler. This needs to be a class that implements the interface WSErrorHandler.
     *
     * @var WSErrorHandler
     */
    protected static $_handler;

    /**
     * Constructor.
     *
     * @param int Error code
     * @param string Error message
     */
    public function __construct($code, $message)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Sets the error handler.
     *
     * @param WSErrorHandler Error handler
     */
    public static function setErrorHandler($handler)
    {
        if ($handler instanceof WSErrorHandler) {
            self::$_handler = $handler;
        }
    }

    /**
     * Returns the error handler.
     *
     * @return WSErrorHandler Error handler
     */
    public static function getErrorHandler()
    {
        return self::$_handler;
    }

    /**
     * Transforms the error into an array.
     *
     * @return array Associative array with code and message
     */
    public function toArray()
    {
        return ['code' => $this->code, 'message' => $this->message];
    }
}

/**
 * Interface that must be implemented by any error handler.
 */
interface WSErrorHandler
{
    /**
     * Handle method.
     *
     * @param WSError Error
     */
    public function handle($error);
}

/**
 * Main class of the webservice. Webservice classes extend this class.
 */
class WS
{
    /**
     * Chamilo configuration.
     *
     * @var array
     */
    protected $_configuration;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_configuration = $GLOBALS['_configuration'];
    }

    /**
     * Test function. Returns the string success.
     *
     * @return string Success
     */
    public function test()
    {
        return "success";
    }

    /**
     * Verifies the API key.
     *
     * @param string Secret key
     *
     * @return mixed WSError in case of failure, null in case of success
     */
    protected function verifyKey($secret_key)
    {
        $ip = trim($_SERVER['REMOTE_ADDR']);
        // if we are behind a reverse proxy, assume it will send the
        // HTTP_X_FORWARDED_FOR header and use this IP instead
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            list($ip1, $ip2) = preg_split(
                '/,/',
                $_SERVER['HTTP_X_FORWARDED_FOR']
            );
            $ip = trim($ip1);
        }
        $security_key = $ip.$this->_configuration['security_key'];

        if (!api_is_valid_secret_key($secret_key, $security_key)) {
            return new WSError(1, "API key is invalid");
        } else {
            return null;
        }
    }

    /**
     * Gets the real user id based on the user id field name and value.
     * Note that if the user id field name is "chamilo_user_id", it will use the user id
     * in the system database.
     *
     * @param string User id field name
     * @param string User id value
     *
     * @return mixed System user id if the user was found, WSError otherwise
     */
    protected function getUserId($user_id_field_name, $user_id_value)
    {
        if ($user_id_field_name == "chamilo_user_id") {
            if (UserManager::is_user_id_valid(intval($user_id_value))) {
                return intval($user_id_value);
            } else {
                return new WSError(100, "User not found");
            }
        } else {
            $user_id = UserManager::get_user_id_from_original_id(
                $user_id_value,
                $user_id_field_name
            );
            if ($user_id == 0) {
                return new WSError(100, "User not found");
            } else {
                return $user_id;
            }
        }
    }

    /**
     * Gets the real course id based on the course id field name and value.
     * Note that if the course id field name is "chamilo_course_id", it will use the course id
     * in the system database.
     *
     * @param string Course id field name
     * @param string Course id value
     *
     * @return mixed System course id if the course was found, WSError otherwise
     */
    protected function getCourseId($course_id_field_name, $course_id_value)
    {
        if ($course_id_field_name == "chamilo_course_id") {
            if (CourseManager::get_course_code_from_course_id(
                    intval($course_id_value)
                ) != null
            ) {
                return intval($course_id_value);
            } else {
                return new WSError(200, "Course not found");
            }
        } else {
            $courseId = CourseManager::get_course_code_from_original_id(
                $course_id_value,
                $course_id_field_name
            );
            if (!empty($courseId)) {
                return $courseId;
            } else {
                return new WSError(200, "Course not found");
            }
        }
    }

    /**
     * Gets the real session id based on the session id field name and value.
     * Note that if the session id field name is "chamilo_session_id", it will use the session id
     * in the system database.
     *
     * @param string Session id field name
     * @param string Session id value
     *
     * @return mixed System session id if the session was found, WSError otherwise
     */
    protected function getSessionId($session_id_field_name, $session_id_value)
    {
        if ($session_id_field_name == "chamilo_session_id") {
            $session = SessionManager::fetch((int) $session_id_value);
            if (!empty($session)) {
                return intval($session_id_value);
            } else {
                return new WSError(300, "Session not found");
            }
        } else {
            $session_id = SessionManager::getSessionIdFromOriginalId(
                $session_id_value,
                $session_id_field_name
            );
            if ($session_id == 0) {
                return new WSError(300, "Session not found");
            } else {
                return $session_id;
            }
        }
    }

    /**
     * Handles an error by calling the WSError error handler.
     *
     * @param WSError Error
     */
    protected function handleError($error)
    {
        $handler = WSError::getErrorHandler();
        $handler->handle($error);
    }

    /**
     * Gets a successful result.
     *
     * @return array Array with a code of 0 and a message 'Operation was successful'
     */
    protected function getSuccessfulResult()
    {
        return ['code' => 0, 'message' => 'Operation was successful'];
    }
}
