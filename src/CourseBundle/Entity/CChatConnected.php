<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CChatConnected.
 *
 * @ORM\Table(
 *  name="c_chat_connected",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user", columns={"user_id"}),
 *      @ORM\Index(name="char_connected_index", columns={"user_id", "session_id", "to_group_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CChatConnected
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="to_group_id", type="integer", nullable=false)
     */
    protected $toGroupId;

    /**
     * @var User
     * @ORM\ManyToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="cChatConnected"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_connection", type="datetime")
     */
    protected $lastConnection;

    /**
     * Get user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set user.
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CChatConnected
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set toGroupId.
     *
     * @param int $toGroupId
     *
     * @return CChatConnected
     */
    public function setToGroupId($toGroupId)
    {
        $this->toGroupId = $toGroupId;

        return $this;
    }

    /**
     * Get toGroupId.
     *
     * @return int
     */
    public function getToGroupId()
    {
        return $this->toGroupId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CChatConnected
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set lastConnection.
     *
     * @param \DateTime $lastConnection
     *
     * @return CChatConnected
     */
    public function setLastConnection($lastConnection)
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    /**
     * Get lastConnection.
     *
     * @return \DateTime
     */
    public function getLastConnection()
    {
        return $this->lastConnection;
    }
}
