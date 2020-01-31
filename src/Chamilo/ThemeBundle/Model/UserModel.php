<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Model;

/**
 * Class UserModel.
 *
 * @package Chamilo\ThemeBundle\Model
 */
class UserModel implements UserInterface
{
    /**
     * @var string
     */
    protected $avatar;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var \DateTime
     */
    protected $memberSince;

    /**
     * @var bool
     */
    protected $isOnline = false;

    public function __construct($username = '', $avatar = '', $memberSince = null, $isOnline = true)
    {
        $this->avatar = $avatar;
        $this->isOnline = $isOnline;
        $this->memberSince = $memberSince ?: new \DateTime();
        $this->username = $username;
    }

    /**
     * @param string $avatar
     *
     * @return $this
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param bool $isOnline
     *
     * @return $this
     */
    public function setIsOnline($isOnline)
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsOnline()
    {
        return $this->isOnline;
    }

    /**
     * @return $this
     */
    public function setMemberSince(\DateTime $memberSince)
    {
        $this->memberSince = $memberSince;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getMemberSince()
    {
        return $this->memberSince;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return $this->getIsOnline();
    }

    public function getIdentifier()
    {
        return str_replace(' ', '-', $this->getUsername());
    }
}
