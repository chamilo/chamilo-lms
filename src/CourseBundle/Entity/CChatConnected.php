<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CChatConnected.
 *
 * @ORM\Table(
 *     name="c_chat_connected",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="user", columns={"user_id"}),
 *         @ORM\Index(name="char_connected_index", columns={"user_id", "session_id", "to_group_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CChatConnected
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="to_group_id", type="integer", nullable=false)
     */
    protected int $toGroupId;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    protected int $userId;

    /**
     * @ORM\Column(name="last_connection", type="datetime")
     */
    protected DateTime $lastConnection;

    /**
     * Set sessionId.
     *
     * @return CChatConnected
     */
    public function setSessionId(int $sessionId)
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
     * @return CChatConnected
     */
    public function setToGroupId(int $toGroupId)
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
     * @return CChatConnected
     */
    public function setCId(int $cId)
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
     * Set userId.
     *
     * @return CChatConnected
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set lastConnection.
     *
     * @return CChatConnected
     */
    public function setLastConnection(DateTime $lastConnection)
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    /**
     * Get lastConnection.
     *
     * @return DateTime
     */
    public function getLastConnection()
    {
        return $this->lastConnection;
    }
}
