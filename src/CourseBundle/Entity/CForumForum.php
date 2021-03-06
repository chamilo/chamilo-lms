<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CForumForum.
 *
 * @ORM\Table(
 *     name="c_forum_forum",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CForumForum extends AbstractResource implements ResourceInterface
{
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
     * @Assert\NotBlank
     *
     * @ORM\Column(name="forum_title", type="string", length=255, nullable=false)
     */
    protected string $forumTitle;

    /**
     * @ORM\Column(name="forum_comment", type="text", nullable=true)
     */
    protected ?string $forumComment;

    /**
     * @ORM\Column(name="forum_threads", type="integer", nullable=true)
     */
    protected ?int $forumThreads;

    /**
     * @ORM\Column(name="forum_posts", type="integer", nullable=true)
     */
    protected ?int $forumPosts;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumPost")
     * @ORM\JoinColumn(name="forum_last_post", referencedColumnName="iid")
     */
    protected CForumPost $forumLastPost;

    /**
     * @Gedmo\SortableGroup
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumCategory", inversedBy="forums")
     * @ORM\JoinColumn(name="forum_category", referencedColumnName="iid", nullable=true, onDelete="SET NULL")
     */
    protected ?CForumCategory $forumCategory;

    /**
     * @ORM\Column(name="allow_anonymous", type="integer", nullable=true)
     */
    protected ?int $allowAnonymous;

    /**
     * @ORM\Column(name="allow_edit", type="integer", nullable=true)
     */
    protected ?int $allowEdit;

    /**
     * @ORM\Column(name="approval_direct_post", type="string", length=20, nullable=true)
     */
    protected ?string $approvalDirectPost;

    /**
     * @ORM\Column(name="allow_attachments", type="integer", nullable=true)
     */
    protected ?int $allowAttachments;

    /**
     * @ORM\Column(name="allow_new_threads", type="integer", nullable=true)
     */
    protected ?int $allowNewThreads;

    /**
     * @ORM\Column(name="default_view", type="string", length=20, nullable=true)
     */
    protected ?string $defaultView;

    /**
     * @ORM\Column(name="forum_of_group", type="string", length=20, nullable=true)
     */
    protected ?string $forumOfGroup;

    /**
     * @ORM\Column(name="forum_group_public_private", type="string", length=20, nullable=true)
     */
    protected ?string $forumGroupPublicPrivate;

    /**
     * @Gedmo\SortablePosition
     *
     * @ORM\Column(name="forum_order", type="integer", nullable=true)
     */
    protected ?int $forumOrder;

    /**
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected int $locked;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="forum_image", type="string", length=255, nullable=false)
     */
    protected string $forumImage;

    /**
     * @ORM\Column(name="start_time", type="datetime", nullable=true)
     */
    protected ?DateTime $startTime;

    /**
     * @ORM\Column(name="end_time", type="datetime", nullable=true)
     */
    protected ?DateTime $endTime;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CourseBundle\Entity\CLp", inversedBy="forum")
     * @ORM\JoinColumn(name="lp_id", referencedColumnName="iid", nullable=true)
     */
    protected ?CLp $lp;

    /**
     * @ORM\Column(name="moderated", type="boolean", nullable=true)
     */
    protected ?bool $moderated;

    /**
     * @var ArrayCollection|CForumThread[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CForumThread", mappedBy="forum")
     */
    protected $threads;

    /**
     * @var ArrayCollection|CForumPost[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CForumPost", mappedBy="forum")
     */
    protected $posts;

    public function __construct()
    {
        $this->threads = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->locked = 0;
        $this->forumComment = '';
        $this->forumImage = '';
        $this->forumOfGroup = '';
        $this->forumPosts = 0;
        $this->forumGroupPublicPrivate = '';
    }

    public function __toString(): string
    {
        return $this->getForumTitle();
    }

    public function setForumTitle(string $forumTitle): self
    {
        $this->forumTitle = $forumTitle;

        return $this;
    }

    public function getForumTitle(): string
    {
        return $this->forumTitle;
    }

    public function setForumComment(string $forumComment): self
    {
        $this->forumComment = $forumComment;

        return $this;
    }

    public function getForumComment(): string
    {
        return $this->forumComment;
    }

    /**
     * Set forumThreads.
     *
     * @param int $forumThreads
     */
    public function setForumThreads($forumThreads): self
    {
        $this->forumThreads = $forumThreads;

        return $this;
    }

    /**
     * Get forumThreads.
     *
     * @return int
     */
    public function getForumThreads()
    {
        return $this->forumThreads;
    }

    public function hasThread(CForumThread $thread): bool
    {
        return $this->threads->contains($thread);
    }

    /**
     * Set forumPosts.
     *
     * @param int $forumPosts
     *
     * @return CForumForum
     */
    public function setForumPosts($forumPosts)
    {
        $this->forumPosts = $forumPosts;

        return $this;
    }

    /**
     * Get forumPosts.
     *
     * @return int
     */
    public function getForumPosts()
    {
        return $this->forumPosts;
    }

    /**
     * Set forumCategory.
     *
     * @return CForumForum
     */
    public function setForumCategory(CForumCategory $forumCategory = null)
    {
        $this->forumCategory = $forumCategory;

        return $this;
    }

    /**
     * Get forumCategory.
     *
     * @return null|CForumCategory
     */
    public function getForumCategory()
    {
        return $this->forumCategory;
    }

    /**
     * Set allowAnonymous.
     *
     * @param int $allowAnonymous
     *
     * @return CForumForum
     */
    public function setAllowAnonymous($allowAnonymous)
    {
        $this->allowAnonymous = $allowAnonymous;

        return $this;
    }

    /**
     * Get allowAnonymous.
     *
     * @return int
     */
    public function getAllowAnonymous()
    {
        return $this->allowAnonymous;
    }

    public function setAllowEdit(int $allowEdit): self
    {
        $this->allowEdit = $allowEdit;

        return $this;
    }

    /**
     * Get allowEdit.
     *
     * @return int
     */
    public function getAllowEdit()
    {
        return $this->allowEdit;
    }

    /**
     * Set approvalDirectPost.
     *
     * @param string $approvalDirectPost
     *
     * @return CForumForum
     */
    public function setApprovalDirectPost($approvalDirectPost)
    {
        $this->approvalDirectPost = $approvalDirectPost;

        return $this;
    }

    /**
     * Get approvalDirectPost.
     *
     * @return string
     */
    public function getApprovalDirectPost()
    {
        return $this->approvalDirectPost;
    }

    /**
     * Set allowAttachments.
     *
     * @param int $allowAttachments
     *
     * @return CForumForum
     */
    public function setAllowAttachments($allowAttachments)
    {
        $this->allowAttachments = $allowAttachments;

        return $this;
    }

    /**
     * Get allowAttachments.
     *
     * @return int
     */
    public function getAllowAttachments()
    {
        return $this->allowAttachments;
    }

    /**
     * Set allowNewThreads.
     *
     * @param int $allowNewThreads
     *
     * @return CForumForum
     */
    public function setAllowNewThreads($allowNewThreads)
    {
        $this->allowNewThreads = $allowNewThreads;

        return $this;
    }

    /**
     * Get allowNewThreads.
     *
     * @return int
     */
    public function getAllowNewThreads()
    {
        return $this->allowNewThreads;
    }

    /**
     * Set defaultView.
     *
     * @param string $defaultView
     *
     * @return CForumForum
     */
    public function setDefaultView($defaultView)
    {
        $this->defaultView = $defaultView;

        return $this;
    }

    /**
     * Get defaultView.
     *
     * @return string
     */
    public function getDefaultView()
    {
        return $this->defaultView;
    }

    /**
     * Set forumOfGroup.
     *
     * @param string $forumOfGroup
     *
     * @return CForumForum
     */
    public function setForumOfGroup($forumOfGroup)
    {
        $this->forumOfGroup = $forumOfGroup;

        return $this;
    }

    /**
     * Get forumOfGroup.
     *
     * @return string
     */
    public function getForumOfGroup()
    {
        return $this->forumOfGroup;
    }

    public function getForumGroupPublicPrivate(): string
    {
        return $this->forumGroupPublicPrivate;
    }

    /**
     * @return $this
     */
    public function setForumGroupPublicPrivate(string $forumGroupPublicPrivate)
    {
        $this->forumGroupPublicPrivate = $forumGroupPublicPrivate;

        return $this;
    }

    /**
     * Set forumOrder.
     *
     * @param int $forumOrder
     *
     * @return CForumForum
     */
    public function setForumOrder($forumOrder)
    {
        $this->forumOrder = $forumOrder;

        return $this;
    }

    /**
     * Get forumOrder.
     *
     * @return int
     */
    public function getForumOrder()
    {
        return $this->forumOrder;
    }

    /**
     * Set locked.
     *
     * @param int $locked
     *
     * @return CForumForum
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
     * @return CForumForum
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
     * Set forumImage.
     *
     * @param string $forumImage
     *
     * @return CForumForum
     */
    public function setForumImage($forumImage)
    {
        $this->forumImage = $forumImage;

        return $this;
    }

    /**
     * Get forumImage.
     *
     * @return string
     */
    public function getForumImage()
    {
        return $this->forumImage;
    }

    /**
     * Set startTime.
     *
     * @param DateTime $startTime
     *
     * @return CForumForum
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime.
     *
     * @param DateTime $endTime
     *
     * @return CForumForum
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CForumForum
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

    public function isModerated(): bool
    {
        return $this->moderated;
    }

    public function setModerated(bool $moderated): self
    {
        $this->moderated = $moderated;

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
     * Get threads.
     *
     * @return ArrayCollection|CForumThread[]
     */
    public function getThreads()
    {
        return $this->threads;
    }

    public function getForumLastPost(): ?CForumPost
    {
        return $this->forumLastPost;
    }

    public function setForumLastPost(CForumPost $forumLastPost): self
    {
        $this->forumLastPost = $forumLastPost;

        return $this;
    }

    public function getLp(): ?CLp
    {
        return $this->lp;
    }

    public function setLp(?CLp $lp): self
    {
        $this->lp = $lp;

        return $this;
    }

    /**
     * @return ArrayCollection|CForumPost[]
     */
    public function getPosts()
    {
        return $this->posts;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getForumTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setForumTitle($name);
    }
}
