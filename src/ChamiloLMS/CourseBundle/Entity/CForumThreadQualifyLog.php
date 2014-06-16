<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThreadQualifyLog
 *
 * @ORM\Table(name="c_forum_thread_qualify_log", indexes={@ORM\Index(name="user_id", columns={"user_id", "thread_id"})})
 * @ORM\Entity
 */
class CForumThreadQualifyLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $threadId;

    /**
     * @var float
     *
     * @ORM\Column(name="qualify", type="float", precision=10, scale=0, nullable=false, unique=false)
     */
    private $qualify;

    /**
     * @var integer
     *
     * @ORM\Column(name="qualify_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $qualifyUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="qualify_time", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $qualifyTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CForumThreadQualifyLog
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CForumThreadQualifyLog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set userId
     *
     * @param integer $userId
     * @return CForumThreadQualifyLog
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
     * Set threadId
     *
     * @param integer $threadId
     * @return CForumThreadQualifyLog
     */
    public function setThreadId($threadId)
    {
        $this->threadId = $threadId;

        return $this;
    }

    /**
     * Get threadId
     *
     * @return integer
     */
    public function getThreadId()
    {
        return $this->threadId;
    }

    /**
     * Set qualify
     *
     * @param float $qualify
     * @return CForumThreadQualifyLog
     */
    public function setQualify($qualify)
    {
        $this->qualify = $qualify;

        return $this;
    }

    /**
     * Get qualify
     *
     * @return float
     */
    public function getQualify()
    {
        return $this->qualify;
    }

    /**
     * Set qualifyUserId
     *
     * @param integer $qualifyUserId
     * @return CForumThreadQualifyLog
     */
    public function setQualifyUserId($qualifyUserId)
    {
        $this->qualifyUserId = $qualifyUserId;

        return $this;
    }

    /**
     * Get qualifyUserId
     *
     * @return integer
     */
    public function getQualifyUserId()
    {
        return $this->qualifyUserId;
    }

    /**
     * Set qualifyTime
     *
     * @param \DateTime $qualifyTime
     * @return CForumThreadQualifyLog
     */
    public function setQualifyTime($qualifyTime)
    {
        $this->qualifyTime = $qualifyTime;

        return $this;
    }

    /**
     * Get qualifyTime
     *
     * @return \DateTime
     */
    public function getQualifyTime()
    {
        return $this->qualifyTime;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CForumThreadQualifyLog
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
