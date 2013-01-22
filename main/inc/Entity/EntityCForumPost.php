<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCForumPost
 *
 * @Table(name="c_forum_post")
 * @Entity
 */
class EntityCForumPost
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
     * @Column(name="post_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $postId;

    /**
     * @var string
     *
     * @Column(name="post_title", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $postTitle;

    /**
     * @var string
     *
     * @Column(name="post_text", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postText;

    /**
     * @var integer
     *
     * @Column(name="thread_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $threadId;

    /**
     * @var integer
     *
     * @Column(name="forum_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumId;

    /**
     * @var integer
     *
     * @Column(name="poster_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $posterId;

    /**
     * @var string
     *
     * @Column(name="poster_name", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $posterName;

    /**
     * @var \DateTime
     *
     * @Column(name="post_date", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postDate;

    /**
     * @var boolean
     *
     * @Column(name="post_notification", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postNotification;

    /**
     * @var integer
     *
     * @Column(name="post_parent_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $postParentId;

    /**
     * @var boolean
     *
     * @Column(name="visible", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $visible;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCForumPost
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
     * Set postId
     *
     * @param integer $postId
     * @return EntityCForumPost
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

    /**
     * Set postTitle
     *
     * @param string $postTitle
     * @return EntityCForumPost
     */
    public function setPostTitle($postTitle)
    {
        $this->postTitle = $postTitle;

        return $this;
    }

    /**
     * Get postTitle
     *
     * @return string 
     */
    public function getPostTitle()
    {
        return $this->postTitle;
    }

    /**
     * Set postText
     *
     * @param string $postText
     * @return EntityCForumPost
     */
    public function setPostText($postText)
    {
        $this->postText = $postText;

        return $this;
    }

    /**
     * Get postText
     *
     * @return string 
     */
    public function getPostText()
    {
        return $this->postText;
    }

    /**
     * Set threadId
     *
     * @param integer $threadId
     * @return EntityCForumPost
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
     * Set forumId
     *
     * @param integer $forumId
     * @return EntityCForumPost
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
     * Set posterId
     *
     * @param integer $posterId
     * @return EntityCForumPost
     */
    public function setPosterId($posterId)
    {
        $this->posterId = $posterId;

        return $this;
    }

    /**
     * Get posterId
     *
     * @return integer 
     */
    public function getPosterId()
    {
        return $this->posterId;
    }

    /**
     * Set posterName
     *
     * @param string $posterName
     * @return EntityCForumPost
     */
    public function setPosterName($posterName)
    {
        $this->posterName = $posterName;

        return $this;
    }

    /**
     * Get posterName
     *
     * @return string 
     */
    public function getPosterName()
    {
        return $this->posterName;
    }

    /**
     * Set postDate
     *
     * @param \DateTime $postDate
     * @return EntityCForumPost
     */
    public function setPostDate($postDate)
    {
        $this->postDate = $postDate;

        return $this;
    }

    /**
     * Get postDate
     *
     * @return \DateTime 
     */
    public function getPostDate()
    {
        return $this->postDate;
    }

    /**
     * Set postNotification
     *
     * @param boolean $postNotification
     * @return EntityCForumPost
     */
    public function setPostNotification($postNotification)
    {
        $this->postNotification = $postNotification;

        return $this;
    }

    /**
     * Get postNotification
     *
     * @return boolean 
     */
    public function getPostNotification()
    {
        return $this->postNotification;
    }

    /**
     * Set postParentId
     *
     * @param integer $postParentId
     * @return EntityCForumPost
     */
    public function setPostParentId($postParentId)
    {
        $this->postParentId = $postParentId;

        return $this;
    }

    /**
     * Get postParentId
     *
     * @return integer 
     */
    public function getPostParentId()
    {
        return $this->postParentId;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     * @return EntityCForumPost
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean 
     */
    public function getVisible()
    {
        return $this->visible;
    }
}
