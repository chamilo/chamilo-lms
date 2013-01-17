<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCForumForum
 *
 * @Table(name="c_forum_forum")
 * @Entity
 */
class EntityCForumForum
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
     * @Column(name="forum_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $forumId;

    /**
     * @var string
     *
     * @Column(name="forum_title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $forumTitle;

    /**
     * @var string
     *
     * @Column(name="forum_comment", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumComment;

    /**
     * @var integer
     *
     * @Column(name="forum_threads", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumThreads;

    /**
     * @var integer
     *
     * @Column(name="forum_posts", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumPosts;

    /**
     * @var integer
     *
     * @Column(name="forum_last_post", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumLastPost;

    /**
     * @var integer
     *
     * @Column(name="forum_category", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumCategory;

    /**
     * @var integer
     *
     * @Column(name="allow_anonymous", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $allowAnonymous;

    /**
     * @var integer
     *
     * @Column(name="allow_edit", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $allowEdit;

    /**
     * @var string
     *
     * @Column(name="approval_direct_post", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $approvalDirectPost;

    /**
     * @var integer
     *
     * @Column(name="allow_attachments", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $allowAttachments;

    /**
     * @var integer
     *
     * @Column(name="allow_new_threads", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $allowNewThreads;

    /**
     * @var string
     *
     * @Column(name="default_view", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $defaultView;

    /**
     * @var string
     *
     * @Column(name="forum_of_group", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumOfGroup;

    /**
     * @var string
     *
     * @Column(name="forum_group_public_private", type="string", length=20, precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumGroupPublicPrivate;

    /**
     * @var integer
     *
     * @Column(name="forum_order", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $forumOrder;

    /**
     * @var integer
     *
     * @Column(name="locked", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $locked;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @Column(name="forum_image", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $forumImage;

    /**
     * @var \DateTime
     *
     * @Column(name="start_time", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @Column(name="end_time", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $endTime;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCForumForum
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
     * Set forumId
     *
     * @param integer $forumId
     * @return EntityCForumForum
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
     * Set forumTitle
     *
     * @param string $forumTitle
     * @return EntityCForumForum
     */
    public function setForumTitle($forumTitle)
    {
        $this->forumTitle = $forumTitle;

        return $this;
    }

    /**
     * Get forumTitle
     *
     * @return string 
     */
    public function getForumTitle()
    {
        return $this->forumTitle;
    }

    /**
     * Set forumComment
     *
     * @param string $forumComment
     * @return EntityCForumForum
     */
    public function setForumComment($forumComment)
    {
        $this->forumComment = $forumComment;

        return $this;
    }

    /**
     * Get forumComment
     *
     * @return string 
     */
    public function getForumComment()
    {
        return $this->forumComment;
    }

    /**
     * Set forumThreads
     *
     * @param integer $forumThreads
     * @return EntityCForumForum
     */
    public function setForumThreads($forumThreads)
    {
        $this->forumThreads = $forumThreads;

        return $this;
    }

    /**
     * Get forumThreads
     *
     * @return integer 
     */
    public function getForumThreads()
    {
        return $this->forumThreads;
    }

    /**
     * Set forumPosts
     *
     * @param integer $forumPosts
     * @return EntityCForumForum
     */
    public function setForumPosts($forumPosts)
    {
        $this->forumPosts = $forumPosts;

        return $this;
    }

    /**
     * Get forumPosts
     *
     * @return integer 
     */
    public function getForumPosts()
    {
        return $this->forumPosts;
    }

    /**
     * Set forumLastPost
     *
     * @param integer $forumLastPost
     * @return EntityCForumForum
     */
    public function setForumLastPost($forumLastPost)
    {
        $this->forumLastPost = $forumLastPost;

        return $this;
    }

    /**
     * Get forumLastPost
     *
     * @return integer 
     */
    public function getForumLastPost()
    {
        return $this->forumLastPost;
    }

    /**
     * Set forumCategory
     *
     * @param integer $forumCategory
     * @return EntityCForumForum
     */
    public function setForumCategory($forumCategory)
    {
        $this->forumCategory = $forumCategory;

        return $this;
    }

    /**
     * Get forumCategory
     *
     * @return integer 
     */
    public function getForumCategory()
    {
        return $this->forumCategory;
    }

    /**
     * Set allowAnonymous
     *
     * @param integer $allowAnonymous
     * @return EntityCForumForum
     */
    public function setAllowAnonymous($allowAnonymous)
    {
        $this->allowAnonymous = $allowAnonymous;

        return $this;
    }

    /**
     * Get allowAnonymous
     *
     * @return integer 
     */
    public function getAllowAnonymous()
    {
        return $this->allowAnonymous;
    }

    /**
     * Set allowEdit
     *
     * @param integer $allowEdit
     * @return EntityCForumForum
     */
    public function setAllowEdit($allowEdit)
    {
        $this->allowEdit = $allowEdit;

        return $this;
    }

    /**
     * Get allowEdit
     *
     * @return integer 
     */
    public function getAllowEdit()
    {
        return $this->allowEdit;
    }

    /**
     * Set approvalDirectPost
     *
     * @param string $approvalDirectPost
     * @return EntityCForumForum
     */
    public function setApprovalDirectPost($approvalDirectPost)
    {
        $this->approvalDirectPost = $approvalDirectPost;

        return $this;
    }

    /**
     * Get approvalDirectPost
     *
     * @return string 
     */
    public function getApprovalDirectPost()
    {
        return $this->approvalDirectPost;
    }

    /**
     * Set allowAttachments
     *
     * @param integer $allowAttachments
     * @return EntityCForumForum
     */
    public function setAllowAttachments($allowAttachments)
    {
        $this->allowAttachments = $allowAttachments;

        return $this;
    }

    /**
     * Get allowAttachments
     *
     * @return integer 
     */
    public function getAllowAttachments()
    {
        return $this->allowAttachments;
    }

    /**
     * Set allowNewThreads
     *
     * @param integer $allowNewThreads
     * @return EntityCForumForum
     */
    public function setAllowNewThreads($allowNewThreads)
    {
        $this->allowNewThreads = $allowNewThreads;

        return $this;
    }

    /**
     * Get allowNewThreads
     *
     * @return integer 
     */
    public function getAllowNewThreads()
    {
        return $this->allowNewThreads;
    }

    /**
     * Set defaultView
     *
     * @param string $defaultView
     * @return EntityCForumForum
     */
    public function setDefaultView($defaultView)
    {
        $this->defaultView = $defaultView;

        return $this;
    }

    /**
     * Get defaultView
     *
     * @return string 
     */
    public function getDefaultView()
    {
        return $this->defaultView;
    }

    /**
     * Set forumOfGroup
     *
     * @param string $forumOfGroup
     * @return EntityCForumForum
     */
    public function setForumOfGroup($forumOfGroup)
    {
        $this->forumOfGroup = $forumOfGroup;

        return $this;
    }

    /**
     * Get forumOfGroup
     *
     * @return string 
     */
    public function getForumOfGroup()
    {
        return $this->forumOfGroup;
    }

    /**
     * Set forumGroupPublicPrivate
     *
     * @param string $forumGroupPublicPrivate
     * @return EntityCForumForum
     */
    public function setForumGroupPublicPrivate($forumGroupPublicPrivate)
    {
        $this->forumGroupPublicPrivate = $forumGroupPublicPrivate;

        return $this;
    }

    /**
     * Get forumGroupPublicPrivate
     *
     * @return string 
     */
    public function getForumGroupPublicPrivate()
    {
        return $this->forumGroupPublicPrivate;
    }

    /**
     * Set forumOrder
     *
     * @param integer $forumOrder
     * @return EntityCForumForum
     */
    public function setForumOrder($forumOrder)
    {
        $this->forumOrder = $forumOrder;

        return $this;
    }

    /**
     * Get forumOrder
     *
     * @return integer 
     */
    public function getForumOrder()
    {
        return $this->forumOrder;
    }

    /**
     * Set locked
     *
     * @param integer $locked
     * @return EntityCForumForum
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return integer 
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return EntityCForumForum
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

    /**
     * Set forumImage
     *
     * @param string $forumImage
     * @return EntityCForumForum
     */
    public function setForumImage($forumImage)
    {
        $this->forumImage = $forumImage;

        return $this;
    }

    /**
     * Get forumImage
     *
     * @return string 
     */
    public function getForumImage()
    {
        return $this->forumImage;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return EntityCForumForum
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime 
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return EntityCForumForum
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime 
     */
    public function getEndTime()
    {
        return $this->endTime;
    }
}
