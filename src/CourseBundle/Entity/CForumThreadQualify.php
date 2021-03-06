<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThreadQualify.
 *
 * @ORM\Table(
 *     name="c_forum_thread_qualify",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="user_id", columns={"user_id", "thread_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CForumThreadQualify
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
    protected ?int $qualifyUserId;

    /**
     * @ORM\Column(name="qualify_time", type="datetime", nullable=true)
     */
    protected ?DateTime $qualifyTime;

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CForumThreadQualify
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

    /**
     * Set threadId.
     *
     * @param int $threadId
     *
     * @return CForumThreadQualify
     */
    public function setThreadId($threadId)
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
     * @param float $qualify
     *
     * @return CForumThreadQualify
     */
    public function setQualify($qualify)
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
     * @param int $qualifyUserId
     *
     * @return CForumThreadQualify
     */
    public function setQualifyUserId($qualifyUserId)
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
     * @param DateTime $qualifyTime
     *
     * @return CForumThreadQualify
     */
    public function setQualifyTime($qualifyTime)
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CForumThreadQualify
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
}
