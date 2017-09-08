<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThread
 *
 * @ORM\Table(
 *  name="c_forum_thread",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="idx_forum_thread_forum_id", columns={"forum_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CForumThread
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_id", type="integer")
     */
    private $threadId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_title", type="string", length=255, nullable=true)
     */
    private $threadTitle;

    /**
     * @var integer
     *
     * @ORM\Column(name="forum_id", type="integer", nullable=true)
     */
    private $forumId;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_replies", type="integer", nullable=false, options={"unsigned":true, "default" = 0})
     */
    private $threadReplies;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_poster_id", type="integer", nullable=true)
     */
    private $threadPosterId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_poster_name", type="string", length=100, nullable=true)
     */
    private $threadPosterName;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_views", type="integer", nullable=false, options={"unsigned":true, "default" = 0})
     */
    private $threadViews;

    /**
     * @var integer
     *
     * @ORM\Column(name="thread_last_post", type="integer", nullable=true)
     */
    private $threadLastPost;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="thread_date", type="datetime", nullable=true)
     */
    private $threadDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="thread_sticky", type="boolean", nullable=true)
     */
    private $threadSticky;

    /**
     * @var integer
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    private $locked;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_title_qualify", type="string", length=255, nullable=true)
     */
    private $threadTitleQualify;

    /**
     * @var float
     *
     * @ORM\Column(name="thread_qualify_max", type="float", precision=6, scale=2, nullable=false)
     */
    private $threadQualifyMax;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="thread_close_date", type="datetime", nullable=true)
     */
    private $threadCloseDate;

    /**
     * @var float
     *
     * @ORM\Column(name="thread_weight", type="float", precision=6, scale=2, nullable=false)
     */
    private $threadWeight;

    /**
     * @var bool
     *
     * @ORM\Column(name="thread_peer_qualify", type="boolean")
     */
    private $threadPeerQualify;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_item_id", type="integer", options={"unsigned":true})
     */
    private $lpItemId;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->threadPeerQualify = false;
        $this->threadReplies = 0;
        $this->threadViews = 0;
    }

    /**
     * @return boolean
     */
    public function isThreadPeerQualify()
    {
        return $this->threadPeerQualify;
    }

    /**
     * set threadPeerQualify
     * @param bool $threadPeerQualify
     * @return $this
     */
    public function setThreadPeerQualify($threadPeerQualify)
    {
        $this->threadPeerQualify = $threadPeerQualify;

        return $this;
    }

    /**
     * Set threadTitle
     *
     * @param string $threadTitle
     *
     * @return CForumThread
     */
    public function setThreadTitle($threadTitle)
    {
        $this->threadTitle = $threadTitle;

        return $this;
    }

    /**
     * Get threadTitle
     *
     * @return string
     */
    public function getThreadTitle()
    {
        return $this->threadTitle;
    }

    /**
     * Set forumId
     *
     * @param integer $forumId
     *
     * @return CForumThread
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
     * Set threadReplies
     *
     * @param integer $threadReplies
     *
     * @return CForumThread
     */
    public function setThreadReplies($threadReplies)
    {
        $this->threadReplies = $threadReplies;

        return $this;
    }

    /**
     * Get threadReplies
     *
     * @return integer
     */
    public function getThreadReplies()
    {
        return $this->threadReplies;
    }

    /**
     * Set threadPosterId
     *
     * @param integer $threadPosterId
     *
     * @return CForumThread
     */
    public function setThreadPosterId($threadPosterId)
    {
        $this->threadPosterId = $threadPosterId;

        return $this;
    }

    /**
     * Get threadPosterId
     *
     * @return integer
     */
    public function getThreadPosterId()
    {
        return $this->threadPosterId;
    }

    /**
     * Set threadPosterName
     *
     * @param string $threadPosterName
     *
     * @return CForumThread
     */
    public function setThreadPosterName($threadPosterName)
    {
        $this->threadPosterName = $threadPosterName;

        return $this;
    }

    /**
     * Get threadPosterName
     *
     * @return string
     */
    public function getThreadPosterName()
    {
        return $this->threadPosterName;
    }

    /**
     * Set threadViews
     *
     * @param integer $threadViews
     *
     * @return CForumThread
     */
    public function setThreadViews($threadViews)
    {
        $this->threadViews = $threadViews;

        return $this;
    }

    /**
     * Get threadViews
     *
     * @return integer
     */
    public function getThreadViews()
    {
        return $this->threadViews;
    }

    /**
     * Set threadLastPost
     *
     * @param integer $threadLastPost
     *
     * @return CForumThread
     */
    public function setThreadLastPost($threadLastPost)
    {
        $this->threadLastPost = $threadLastPost;

        return $this;
    }

    /**
     * Get threadLastPost
     *
     * @return integer
     */
    public function getThreadLastPost()
    {
        return $this->threadLastPost;
    }

    /**
     * Set threadDate
     *
     * @param \DateTime $threadDate
     *
     * @return CForumThread
     */
    public function setThreadDate($threadDate)
    {
        $this->threadDate = $threadDate;

        return $this;
    }

    /**
     * Get threadDate
     *
     * @return \DateTime
     */
    public function getThreadDate()
    {
        return $this->threadDate;
    }

    /**
     * Set threadSticky
     *
     * @param boolean $threadSticky
     *
     * @return CForumThread
     */
    public function setThreadSticky($threadSticky)
    {
        $this->threadSticky = $threadSticky;

        return $this;
    }

    /**
     * Get threadSticky
     *
     * @return boolean
     */
    public function getThreadSticky()
    {
        return $this->threadSticky;
    }

    /**
     * Set locked
     *
     * @param integer $locked
     *
     * @return CForumThread
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
     *
     * @return CForumThread
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
     * Set threadTitleQualify
     *
     * @param string $threadTitleQualify
     *
     * @return CForumThread
     */
    public function setThreadTitleQualify($threadTitleQualify)
    {
        $this->threadTitleQualify = $threadTitleQualify;

        return $this;
    }

    /**
     * Get threadTitleQualify
     *
     * @return string
     */
    public function getThreadTitleQualify()
    {
        return $this->threadTitleQualify;
    }

    /**
     * Set threadQualifyMax
     *
     * @param float $threadQualifyMax
     *
     * @return CForumThread
     */
    public function setThreadQualifyMax($threadQualifyMax)
    {
        $this->threadQualifyMax = $threadQualifyMax;

        return $this;
    }

    /**
     * Get threadQualifyMax
     *
     * @return float
     */
    public function getThreadQualifyMax()
    {
        return $this->threadQualifyMax;
    }

    /**
     * Set threadCloseDate
     *
     * @param \DateTime $threadCloseDate
     *
     * @return CForumThread
     */
    public function setThreadCloseDate($threadCloseDate)
    {
        $this->threadCloseDate = $threadCloseDate;

        return $this;
    }

    /**
     * Get threadCloseDate
     *
     * @return \DateTime
     */
    public function getThreadCloseDate()
    {
        return $this->threadCloseDate;
    }

    /**
     * Set threadWeight
     *
     * @param float $threadWeight
     *
     * @return CForumThread
     */
    public function setThreadWeight($threadWeight)
    {
        $this->threadWeight = $threadWeight;

        return $this;
    }

    /**
     * Get threadWeight
     *
     * @return float
     */
    public function getThreadWeight()
    {
        return $this->threadWeight;
    }

    /**
     * Set threadId
     *
     * @param integer $threadId
     *
     * @return CForumThread
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
     * Set cId
     *
     * @param integer $cId
     *
     * @return CForumThread
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
     * Set lpItemId
     * @param integer $lpItemId
     *
     * @return $this
     */
    public function setLpItemId($lpItemId)
    {
        $this->lpItemId = $lpItemId;

        return $this;
    }

    /**
     * Get lpId
     * @return integer
     */
    public function getLpItemId()
    {
        return $this->lpItemId;
    }

    /**
     * Get iid
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
