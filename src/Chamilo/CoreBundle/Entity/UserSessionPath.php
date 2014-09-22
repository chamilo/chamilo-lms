<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserSessionPath
 *
 * @ORM\Table(name="user_session_path")
 * @ORM\Entity
 */
class UserSessionPath
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_path_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionPathId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="percentage", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $percentage;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sessionPathId
     *
     * @param integer $sessionPathId
     * @return UserSessionPath
     */
    public function setSessionPathId($sessionPathId)
    {
        $this->sessionPathId = $sessionPathId;

        return $this;
    }

    /**
     * Get sessionPathId
     *
     * @return integer
     */
    public function getSessionPathId()
    {
        return $this->sessionPathId;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserSessionPath
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return UserSessionPath
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set percentage
     *
     * @param integer $percentage
     * @return UserSessionPath
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * Get percentage
     *
     * @return integer
     */
    public function getPercentage()
    {
        return $this->percentage;
    }
}
