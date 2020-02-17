<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

/**
 * Base class for Web Services.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.webservices
 */
class WebService
{
    /**
     * @var User
     */
    protected $user;
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * Class constructor.
     *
     * @param $username
     * @param $apiKey
     */
    protected function __construct($username, $apiKey)
    {
        /** @var User user */
        $this->user = UserManager::getManager()->findUserByUsername($username);
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $username
     * @param string $apiKeyToValidate
     *
     * @return WebService
     */
    public static function validate($username, $apiKeyToValidate)
    {
        return new self($username, $apiKeyToValidate);
    }

    /**
     * Find the api key for a user. If the api key does not exists is created.
     *
     * @param string $username
     * @param string $serviceName
     *
     * @return string
     */
    public static function findUserApiKey($username, $serviceName)
    {
        $user = UserManager::getManager()->findUserByUsername($username);
        if ($user) {
            $apiKeys = UserManager::get_api_keys($user->getId(), $serviceName);

            if (empty($apiKeys)) {
                UserManager::add_api_key($user->getId(), $serviceName);
            }

            $apiKeys = UserManager::get_api_keys($user->getId(), $serviceName);

            return current($apiKeys);
        }

        return '';
    }

    /**
     * Check whether the username and password are valid.
     *
     * @param string $username
     * @param string $password
     *
     * @throws Exception
     *
     * @return bool Return true if the password belongs to the username. Otherwise return false
     */
    public static function isValidUser($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        error_log("89");
        $user = UserManager::getManager()->findUserByUsername($username);
        error_log("90");
        if (!$user) {
            return false;
        }
        error_log("95");

        return UserManager::isPasswordValid(
            $user->getPassword(),
            $password,
            $user->getSalt()
        );
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
