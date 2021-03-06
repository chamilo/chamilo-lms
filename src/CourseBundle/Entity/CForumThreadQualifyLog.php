<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThreadQualifyLog.
 *
 * @ORM\Table(
 *     name="c_forum_thread_qualify_log",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="user_id", columns={"user_id", "thread_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CForumThreadQualifyLog
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
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected int $userId;

    /**
     * @ORM\Column(name="thread_id", type="integer", nullable=false)
     */
    protected int $threadId;

    /**
     * @ORM\Column(name="qualify", type="float", precision=6, scale=2, nullable=false)
     */
    protected float $qualify;

    /**
     * @ORM\Column(name="qualify_user_id", type="integer", nullable=true)
     */
    protected ?int $qualifyUserId = null;

    /**
     * @ORM\Column(name="qualify_time", type="datetime", nullable=true)
     */
    protected ?DateTime $qualifyTime = null;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected ?int $sessionId = null;

    /**
     * Set userId.
     *
     * @return CForumThreadQualifyLog
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
     * Set threadId.
     *
     * @return CForumThreadQualifyLog
     */
    public function setThreadId(int $threadId)
    {
        $this->threadId = $threadId;

        return $this;
    }

    /**
     * Get threadId.
     *
     * @return int
     */
    public function getThreadId()
    {
        return $this->threadId;
    }

    /**
     * Set qualify.
     *
     * @return CForumThreadQualifyLog
     */
    public function setQualify(float $qualify)
    {
        $this->qualify = $qualify;

        return $this;
    }

    /**
     * Get qualify.
     *
     * @return float
     */
    public function getQualify()
    {
        return $this->qualify;
    }

    /**
     * Set qualifyUserId.
     *
     * @return CForumThreadQualifyLog
     */
    public function setQualifyUserId(int $qualifyUserId)
    {
        $this->qualifyUserId = $qualifyUserId;

        return $this;
    }

    /**
     * Get qualifyUserId.
     *
     * @return int
     */
    public function getQualifyUserId()
    {
        return $this->qualifyUserId;
    }

    /**
     * Set qualifyTime.
     *
     * @return CForumThreadQualifyLog
     */
    public function setQualifyTime(DateTime $qualifyTime)
    {
        $this->qualifyTime = $qualifyTime;

        return $this;
    }

    /**
     * Get qualifyTime.
     *
     * @return DateTime
     */
    public function getQualifyTime()
    {
        return $this->qualifyTime;
    }

    /**
     * Set sessionId.
     *
     * @return CForumThreadQualifyLog
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
     * Set cId.
     *
     * @return CForumThreadQualifyLog
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
}
