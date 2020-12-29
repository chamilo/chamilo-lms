<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
class CForumThread extends AbstractResource implements ResourceInterface
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
     * @var string
     *
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="thread_title", type="string", length=255, nullable=true)
     */
    protected $threadTitle;

    /**
     * @var CForumForum|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumForum", inversedBy="threads")
     * @ORM\JoinColumn(name="forum_id", referencedColumnName="iid", nullable=true, onDelete="SET NULL")
     */
    protected $forum;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_replies", type="integer", nullable=false, options={"unsigned":true, "default" = 0})
     */
    protected $threadReplies;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="thread_poster_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_views", type="integer", nullable=false, options={"unsigned":true, "default" = 0})
     */
    protected $threadViews;

    /**
     * @var ArrayCollection|CForumPost[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CForumPost",
     *     mappedBy="thread", cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected $posts;

    /**
     * @var CForumPost
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumPost", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="thread_last_post", referencedColumnName="iid", onDelete="SET NULL")
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

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->threadPeerQualify = false;
        $this->threadReplies = 0;
        $this->threadViews = 0;
        $this->locked = 0;
        $this->threadQualifyMax = 0;
        $this->threadWeight = 0;
        $this->lpItemId = 0;
    }

    public function __toString(): string
    {
        return (string) $this->getThreadTitle();
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
     * Set forum.
     *
     * @return CForumThread
     */
    public function setForum(CForumForum $forum = null)
    {
        $this->forum = $forum;

        return $this;
    }

    /**
     * Get forumId.
     *
     * @return CForumForum|null
     */
    public function getForum()
    {
        return $this->forum;
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
     * Set threadDate.
     *
     * @param \DateTime $threadDate
     */
    public function setThreadDate($threadDate): self
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
     */
    public function setThreadQualifyMax($threadQualifyMax): self
    {
        $this->threadQualifyMax = (float) $threadQualifyMax;

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
    public function setThreadWeight($threadWeight): self
    {
        $this->threadWeight = (float) $threadWeight;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return ArrayCollection|CForumPost[]
     */
    public function getPosts()
    {
        return $this->posts;
    }

    public function getThreadLastPost(): ?CForumPost
    {
        return $this->threadLastPost;
    }

    public function setThreadLastPost(CForumPost $threadLastPost): self
    {
        $this->threadLastPost = $threadLastPost;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getThreadTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setThreadTitle($name);
    }
}
