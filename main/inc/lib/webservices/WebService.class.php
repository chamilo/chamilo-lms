<?php
/* For licensing terms, see /license.txt */

/**
 * Base class for Web Services
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.webservices
 */
abstract class WebService
{

    protected $apiKey;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->apiKey = null;
    }

    /**
     * Set the api key
     * @param string $apiKey The api key
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @abstract
     */
    abstract public function getApiKey($username);

    /**
     * Check whether the username and password are valid
     * @param string $username The username
     * @param string $password the password
     * @return boolean Whether the password belongs to the username return true. Otherwise return false
     */
    public static function isValidUser($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }

        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $whereConditions = array(
            "username = '?' " => $username,
            "AND password = '?'" => sha1($password)
        );

        $conditions = array(
            'where' => $whereConditions
        );

        $table = Database::select('count(1) as qty', $userTable, $conditions);

        if ($table != false) {
            $row = current($table);

            if ($row['qty'] > 0) {
                return true;
            }
        }

        return false;
    }

}
