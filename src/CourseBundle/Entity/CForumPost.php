<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CForumPost.
 *
 * @ORM\Table(
 *     name="c_forum_post",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="forum_id", columns={"forum_id"}),
 *         @ORM\Index(name="idx_forum_post_thread_id", columns={"thread_id"}),
 *         @ORM\Index(name="idx_forum_post_visible", columns={"visible"}),
 *         @ORM\Index(name="c_id_visible_post_date", columns={"c_id", "visible", "post_date"})
 *     }
 * )
 * @ORM\Entity
 */
class CForumPost extends AbstractResource implements ResourceInterface
{
    public const STATUS_VALIDATED = 1;
    public const STATUS_WAITING_MODERATION = 2;
    public const STATUS_REJECTED = 3;

    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="post_title", type="string", length=250, nullable=false)
     */
    protected string $postTitle;

    /**
     * @ORM\Column(name="post_text", type="text", nullable=true)
     */
    protected ?string $postText;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumThread", inversedBy="posts")
     * @ORM\JoinColumn(name="thread_id", referencedColumnName="iid", nullable=true, onDelete="SET NULL")
     */
    protected ?CForumThread $thread = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumForum", inversedBy="posts")
     * @ORM\JoinColumn(name="forum_id", referencedColumnName="iid", nullable=true, onDelete="SET NULL")
     */
    protected ?CForumForum $forum = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="poster_id", referencedColumnName="id")
     */
    protected ?User $user = null;

    /**
     * @ORM\Column(name="post_date", type="datetime", nullable=false)
     */
    protected DateTime $postDate;

    /**
     * @ORM\Column(name="post_notification", type="boolean", nullable=true)
     */
    protected ?bool $postNotification;

    /**
     * @ORM\Column(name="post_parent_id", type="integer", nullable=true)
     */
    protected ?int $postParentId = null;

    /**
     * @ORM\Column(name="visible", type="boolean", nullable=false)
     */
    protected bool $visible;

    /**
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    protected ?int $status;

    /**
     * @var Collection|CForumAttachment[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CourseBundle\Entity\CForumAttachment",
     *     mappedBy="post", cascade={"persist", "remove"}, orphanRemoval=true
     * )
     */
    protected $attachments;

    public function __construct()
    {
        $this->visible = false;
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

    public function setPostText(string $postText): self
    {
        $this->postText = $postText;

        return $this;
    }

    public function getPostText(): ?string
    {
        return $this->postText;
    }

    public function setThread(CForumThread $thread = null): self
    {
        $this->thread = $thread;

        return $this;
    }

    /**
     * Get thread.
     *
     * @return null|CForumThread
     */
    public function getThread()
    {
        return $this->thread;
    }

    /**
     * Set postDate.
     *
     * @param DateTime $postDate
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
     * @return DateTime
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

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function removeAttachment(CForumAttachment $attachment): void
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
     * @return User
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
