<?php
/* For licensing terms, see /license.txt */
use Chamilo\UserBundle\Entity\User;

/**
 * Base class for Web Services
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
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
     * Class constructor
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
     * @return WebService
     */
    public static function validate($username, $apiKeyToValidate)
    {
        return new self($username, $apiKeyToValidate);
    }

    /**
     * Find the api key for a user. If the api key does not exists is created
     * @param string $username
     * @param string $serviceName
     * @return string
     */
    public static function findUserApiKey($username, $serviceName)
    {
        $user = UserManager::getManager()->findUserByUsername($username);

        $apiKeys = UserManager::get_api_keys($user->getId(), $serviceName);

        if (empty($apiKeys)) {
            UserManager::add_api_key($user->getId(), $serviceName);
        }

        $apiKeys = UserManager::get_api_keys($user->getId(), $serviceName);

        return current($apiKeys);
    }

    /**
     * Check whether the username and password are valid
     * @param string $username
     * @param string $password
     * @return bool Return true if the password belongs to the username. Otherwise return false
     * @throws Exception
     */
    public static function isValidUser($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }

        /** @var \Chamilo\UserBundle\Entity\User $user */
        $user = UserManager::getRepository()
            ->findOneBy(['username' => $username]);

        if (!$user) {
            return false;
        }

        return UserManager::isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }
}
