<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * COnlineConnected.
 *
 * @ORM\Table(
 *     name="c_online_connected",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class COnlineConnected
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
     * @ORM\Column(name="last_connection", type="datetime", nullable=false)
     */
    protected DateTime $lastConnection;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    protected int $userId;

    /**
     * Set lastConnection.
     *
     * @param DateTime $lastConnection
     *
     * @return COnlineConnected
     */
    public function setLastConnection($lastConnection)
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

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return COnlineConnected
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return COnlineConnected
     */
    public function setUserId($userId)
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
}
