<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCForumNotification
 *
 * @Table(name="c_forum_notification")
 * @Entity
 */
class EntityCForumNotification
{
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
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var integer
     *
     * @Column(name="forum_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $forumId;

    /**
     * @var integer
     *
     * @Column(name="thread_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $threadId;

    /**
     * @var integer
     *
     * @Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $postId;


    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCForumNotification
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
     * Set cId
     *
     * @param integer $cId
     * @return EntityCForumNotification
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
     * Set userId
     *
     * @param integer $userId
     * @return EntityCForumNotification
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
     * Set forumId
     *
     * @param integer $forumId
     * @return EntityCForumNotification
     */
    public function setForumId($forumId)
    {
        $this->forumId = $forumId;

        return $this;
    }

    /**
     * Get forumId
     *
     * @return integer 
     */
    public function getForumId()
    {
        return $this->forumId;
    }

    /**
     * Set threadId
     *
     * @param integer $threadId
     * @return EntityCForumNotification
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
     * Set postId
     *
     * @param integer $postId
     * @return EntityCForumNotification
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get postId
     *
     * @return integer 
     */
    public function getPostId()
    {
        return $this->postId;
    }
}
