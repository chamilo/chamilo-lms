<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CForumPost.
 *
 * @ORM\Table(
 *  name="c_forum_post",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="forum_id", columns={"forum_id"}),
 *      @ORM\Index(name="idx_forum_post_thread_id", columns={"thread_id"}),
 *      @ORM\Index(name="idx_forum_post_visible", columns={"visible"}),
 *      @ORM\Index(name="c_id_visible_post_date", columns={"c_id", "visible", "post_date"})
 *  }
 * )
 * @ORM\Entity
 */
class CForumPost extends AbstractResource implements ResourceInterface
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
     * @var CForumThread|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumThread", inversedBy="posts")
     * @ORM\JoinColumn(name="thread_id", referencedColumnName="iid", nullable=true, onDelete="SET NULL")
     */
    protected $thread;

    /**
     * @var CForumForum|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumForum", inversedBy="posts")
     * @ORM\JoinColumn(name="forum_id", referencedColumnName="iid", nullable=true, onDelete="SET NULL")
     */
    protected $forum;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="poster_id", referencedColumnName="id")
     */
    protected $user;

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
     * @var ArrayCollection|CForumAttachment[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CForumAttachment", mappedBy="post", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $attachments;

    public function __construct()
    {
        $this->postParentId = null;
        $this->attachments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getPostTitle();
    }

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
     * Set thread.
     *
     * @return CForumPost
     */
    public function setThread(CForumThread $thread = null)
    {
        $this->thread = $thread;

        return $this;
    }

    /**
     * Get thread.
     *
     * @return CForumThread|null
     */
    public function getThread()
    {
        return $this->thread;
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

    /**
     * @return CForumAttachment[]
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    public function removeAttachment(CForumAttachment $attachment)
    {
        $this->attachments->removeElement($attachment);
    }

    public function getForum(): ?CForumForum
    {
        return $this->forum;
    }

    public function setForum(?CForumForum $forum): self
    {
        $this->forum = $forum;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getPostTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setPostTitle($name);
    }
}
