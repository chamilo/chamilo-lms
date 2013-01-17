<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCForumThreadQualify
 *
 * @Table(name="c_forum_thread_qualify")
 * @Entity
 */
class EntityCForumThreadQualify
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="thread_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $threadId;

    /**
     * @var float
     *
     * @Column(name="qualify", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $qualify;

    /**
     * @var integer
     *
     * @Column(name="qualify_user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $qualifyUserId;

    /**
     * @var \DateTime
     *
     * @Column(name="qualify_time", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $qualifyTime;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCForumThreadQualify
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
     * @return EntityCForumThreadQualify
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
     * @return EntityCForumThreadQualify
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
     * @return EntityCForumThreadQualify
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
     * @return EntityCForumThreadQualify
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
     * @return EntityCForumThreadQualify
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
     * @return EntityCForumThreadQualify
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
     * @return EntityCForumThreadQualify
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
