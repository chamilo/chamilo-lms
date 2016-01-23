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

        $user = UserManager::getRepository()->findOneBy([
            'username' => $username
        ]);

        if (empty($user)) {
            return false;
        }

        return UserManager::isPasswordValid($password, $user);
    }

}
