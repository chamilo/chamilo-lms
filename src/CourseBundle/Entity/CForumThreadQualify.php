<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThreadQualify.
 *
 * @ORM\Table(
 *  name="c_forum_thread_qualify",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="user_id", columns={"user_id", "thread_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CForumThreadQualify
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
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_id", type="integer", nullable=false)
     */
    protected $threadId;

    /**
     * @var float
     *
     * @ORM\Column(name="qualify", type="float", precision=6, scale=2, nullable=false)
     */
    protected $qualify;

    /**
     * @var int
     *
     * @ORM\Column(name="qualify_user_id", type="integer", nullable=true)
     */
    protected $qualifyUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="qualify_time", type="datetime", nullable=true)
     */
    protected $qualifyTime;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

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
     * @param \DateTime $qualifyTime
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
     * @return \DateTime
     */
    public function getQualifyTime()
    {
        return $this->qualifyTime;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CForumThreadQualify
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
     * Set id.
     *
     * @param int $id
     *
     * @return CForumThreadQualify
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
