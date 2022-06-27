<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CForumPost.
 *
 * @ORM\Table(
 *  name="c_forum_post",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="poster_id", columns={"poster_id"}),
 *      @ORM\Index(name="forum_id", columns={"forum_id"}),
 *      @ORM\Index(name="idx_forum_post_thread_id", columns={"thread_id"}),
 *      @ORM\Index(name="idx_forum_post_visible", columns={"visible"})
 *  }
 * )
 * @ORM\Entity
 */
class CForumPost
{
    public const STATUS_VALIDATED = 1;
    public const STATUS_WAITING_MODERATION = 2;
    public const STATUS_REJECTED = 3;

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
     * @ORM\Column(name="post_id", type="integer")
     */
    protected $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="post_title", type="string", length=250, nullable=true)
     */
    protected $postTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="post_text", type="text", nullable=true)
     */
    protected $postText;

    /**
     * @var int
     *
     * @ORM\Column(name="thread_id", type="integer", nullable=true)
     */
    protected $threadId;

    /**
     * @var int
     *
     * @ORM\Column(name="forum_id", type="integer", nullable=true)
     */
    protected $forumId;

    /**
     * @var int
     *
     * @ORM\Column(name="poster_id", type="integer", nullable=true)
     */
    protected $posterId;

    /**
     * @var string
     *
     * @ORM\Column(name="poster_name", type="string", length=100, nullable=true)
     */
    protected $posterName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="post_date", type="datetime", nullable=true)
     */
    protected $postDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="post_notification", type="boolean", nullable=true)
     */
    protected $postNotification;

    /**
     * @var int
     *
     * @ORM\Column(name="post_parent_id", type="integer", nullable=true)
     */
    protected $postParentId;

    /**
     * @var bool
     *
     * @ORM\Column(name="visible", type="boolean", nullable=true)
     */
    protected $visible;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    protected $status;

    /**
     * Set postTitle.
     *
     * @param string $postTitle
     *
     * @return CForumPost
     */
    public function setPostTitle($postTitle)
    {
        $this->postTitle = $postTitle;

        return $this;
    }

    /**
     * Get postTitle.
     *
     * @return string
     */
    public function getPostTitle()
    {
        return $this->postTitle;
    }

    /**
     * Set postText.
     *
     * @param string $postText
     *
     * @return CForumPost
     */
    public function setPostText($postText)
    {
        $this->postText = $postText;

        return $this;
    }

    /**
     * Get postText.
     *
     * @return string
     */
    public function getPostText()
    {
        return $this->postText;
    }

    /**
     * Set threadId.
     *
     * @param int $threadId
     *
     * @return CForumPost
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
     * Set forumId.
     *
     * @param int $forumId
     *
     * @return CForumPost
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
     * Set posterId.
     *
     * @param int $posterId
     *
     * @return CForumPost
     */
    public function setPosterId($posterId)
    {
        $this->posterId = $posterId;

        return $this;
    }

    /**
     * Get posterId.
     *
     * @return int
     */
    public function getPosterId()
    {
        return $this->posterId;
    }

    /**
     * Set posterName.
     *
     * @param string $posterName
     *
     * @return CForumPost
     */
    public function setPosterName($posterName)
    {
        $this->posterName = $posterName;

        return $this;
    }

    /**
     * Get posterName.
     *
     * @return string
     */
    public function getPosterName()
    {
        return $this->posterName;
    }

    /**
     * Set postDate.
     *
     * @param \DateTime $postDate
     *
     * @return CForumPost
     */
    public function setPostDate($postDate)
    {
        $this->postDate = $postDate;

        return $this;
    }

    /**
     * Get postDate.
     *
     * @return \DateTime
     */
    public function getPostDate()
    {
        return $this->postDate;
    }

    /**
     * Set postNotification.
     *
     * @param bool $postNotification
     *
     * @return CForumPost
     */
    public function setPostNotification($postNotification)
    {
        $this->postNotification = $postNotification;

        return $this;
    }

    /**
     * Get postNotification.
     *
     * @return bool
     */
    public function getPostNotification()
    {
        return $this->postNotification;
    }

    /**
     * Set postParentId.
     *
     * @param int $postParentId
     *
     * @return CForumPost
     */
    public function setPostParentId($postParentId)
    {
        $this->postParentId = $postParentId;

        return $this;
    }

    /**
     * Get postParentId.
     *
     * @return int
     */
    public function getPostParentId()
    {
        return $this->postParentId;
    }

    /**
     * Set visible.
     *
     * @param bool $visible
     *
     * @return CForumPost
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Get visible.
     *
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set postId.
     *
     * @param int $postId
     *
     * @return CForumPost
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get postId.
     *
     * @return int
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CForumPost
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
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return CForumPost
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
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
