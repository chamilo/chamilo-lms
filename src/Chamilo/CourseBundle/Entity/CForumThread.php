<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumThread.
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
     * @ORM\Column(name="thread_id", type="integer")
     */
    protected $threadId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_title", type="string", length=255, nullable=true)
     */
    protected $threadTitle;

    /**
     * @var int
     *
     * @ORM\Column(name="forum_id", type="integer", nullable=true)
     */
    protected $forumId;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_replies", type="integer", nullable=false, options={"unsigned":true, "default" = 0})
     */
    protected $threadReplies;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_poster_id", type="integer", nullable=true)
     */
    protected $threadPosterId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_poster_name", type="string", length=100, nullable=true)
     */
    protected $threadPosterName;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_views", type="integer", nullable=false, options={"unsigned":true, "default" = 0})
     */
    protected $threadViews;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_last_post", type="integer", nullable=true)
     */
    protected $threadLastPost;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="thread_date", type="datetime", nullable=true)
     */
    protected $threadDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="thread_sticky", type="boolean", nullable=true)
     */
    protected $threadSticky;

    /**
     * @var int
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected $locked;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="thread_title_qualify", type="string", length=255, nullable=true)
     */
    protected $threadTitleQualify;

    /**
     * @var float
     *
     * @ORM\Column(name="thread_qualify_max", type="float", precision=6, scale=2, nullable=false)
     */
    protected $threadQualifyMax;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="thread_close_date", type="datetime", nullable=true)
     */
    protected $threadCloseDate;

    /**
     * @var float
     *
     * @ORM\Column(name="thread_weight", type="float", precision=6, scale=2, nullable=false)
     */
    protected $threadWeight;

    /**
     * @var bool
     *
     * @ORM\Column(name="thread_peer_qualify", type="boolean")
     */
    protected $threadPeerQualify;

    /**
     * @var int
     *
     * @ORM\Column(name="lp_item_id", type="integer", options={"unsigned":true})
     */
    protected $lpItemId;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->threadPeerQualify = false;
        $this->threadReplies = 0;
        $this->threadViews = 0;
    }

    /**
     * @return bool
     */
    public function isThreadPeerQualify()
    {
        return $this->threadPeerQualify;
    }

    /**
     * set threadPeerQualify.
     *
     * @param bool $threadPeerQualify
     *
     * @return $this
     */
    public function setThreadPeerQualify($threadPeerQualify)
    {
        $this->threadPeerQualify = $threadPeerQualify;

        return $this;
    }

    /**
     * Set threadTitle.
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
     * Get threadTitle.
     *
     * @return string
     */
    public function getThreadTitle()
    {
        return $this->threadTitle;
    }

    /**
     * Set forumId.
     *
     * @param int $forumId
     *
     * @return CForumThread
     */
    public function setForumId($forumId)
    {
        $this->forumId = $forumId;

        return $this;
    }

    /**
     * Get forumId.
     *
     * @return int
     */
    public function getForumId()
    {
        return $this->forumId;
    }

    /**
     * Set threadReplies.
     *
     * @param int $threadReplies
     *
     * @return CForumThread
     */
    public function setThreadReplies($threadReplies)
    {
        $this->threadReplies = $threadReplies;

        return $this;
    }

    /**
     * Get threadReplies.
     *
     * @return int
     */
    public function getThreadReplies()
    {
        return $this->threadReplies;
    }

    /**
     * Set threadPosterId.
     *
     * @param int $threadPosterId
     *
     * @return CForumThread
     */
    public function setThreadPosterId($threadPosterId)
    {
        $this->threadPosterId = $threadPosterId;

        return $this;
    }

    /**
     * Get threadPosterId.
     *
     * @return int
     */
    public function getThreadPosterId()
    {
        return $this->threadPosterId;
    }

    /**
     * Set threadPosterName.
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
     * Get threadPosterName.
     *
     * @return string
     */
    public function getThreadPosterName()
    {
        return $this->threadPosterName;
    }

    /**
     * Set threadViews.
     *
     * @param int $threadViews
     *
     * @return CForumThread
     */
    public function setThreadViews($threadViews)
    {
        $this->threadViews = $threadViews;

        return $this;
    }

    /**
     * Get threadViews.
     *
     * @return int
     */
    public function getThreadViews()
    {
        return $this->threadViews;
    }

    /**
     * Set threadLastPost.
     *
     * @param int $threadLastPost
     *
     * @return CForumThread
     */
    public function setThreadLastPost($threadLastPost)
    {
        $this->threadLastPost = $threadLastPost;

        return $this;
    }

    /**
     * Get threadLastPost.
     *
     * @return int
     */
    public function getThreadLastPost()
    {
        return $this->threadLastPost;
    }

    /**
     * Set threadDate.
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
     * Get threadDate.
     *
     * @return \DateTime
     */
    public function getThreadDate()
    {
        return $this->threadDate;
    }

    /**
     * Set threadSticky.
     *
     * @param bool $threadSticky
     *
     * @return CForumThread
     */
    public function setThreadSticky($threadSticky)
    {
        $this->threadSticky = $threadSticky;

        return $this;
    }

    /**
     * Get threadSticky.
     *
     * @return bool
     */
    public function getThreadSticky()
    {
        return $this->threadSticky;
    }

    /**
     * Set locked.
     *
     * @param int $locked
     *
     * @return CForumThread
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return int
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CForumThread
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
     * Set threadTitleQualify.
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
     * Get threadTitleQualify.
     *
     * @return string
     */
    public function getThreadTitleQualify()
    {
        return $this->threadTitleQualify;
    }

    /**
     * Set threadQualifyMax.
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
     * Get threadQualifyMax.
     *
     * @return float
     */
    public function getThreadQualifyMax()
    {
        return $this->threadQualifyMax;
    }

    /**
     * Set threadCloseDate.
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
     * Get threadCloseDate.
     *
     * @return \DateTime
     */
    public function getThreadCloseDate()
    {
        return $this->threadCloseDate;
    }

    /**
     * Set threadWeight.
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
     * Get threadWeight.
     *
     * @return float
     */
    public function getThreadWeight()
    {
        return $this->threadWeight;
    }

    /**
     * Set threadId.
     *
     * @param int $threadId
     *
     * @return CForumThread
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
     * Set cId.
     *
     * @param int $cId
     *
     * @return CForumThread
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
     * Set lpItemId.
     *
     * @param int $lpItemId
     *
     * @return $this
     */
    public function setLpItemId($lpItemId)
    {
        $this->lpItemId = $lpItemId;

        return $this;
    }

    /**
     * Get lpId.
     *
     * @return int
     */
    public function getLpItemId()
    {
        return $this->lpItemId;
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }
}
