<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CourseBundle\Repository\CForumRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Course forums.
 */
#[ORM\Table(name: 'c_forum_forum')]
#[ORM\Entity(repositoryClass: CForumRepository::class)]
class CForum extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'forum_comment', type: 'text', nullable: true)]
    protected ?string $forumComment;

    #[ORM\Column(name: 'forum_threads', type: 'integer', nullable: true)]
    protected ?int $forumThreads = null;

    #[ORM\Column(name: 'forum_posts', type: 'integer', nullable: true)]
    protected ?int $forumPosts;

    #[ORM\ManyToOne(targetEntity: CForumPost::class)]
    #[ORM\JoinColumn(name: 'forum_last_post', referencedColumnName: 'iid')]
    protected ?CForumPost $forumLastPost = null;

    #[ORM\ManyToOne(targetEntity: CForumCategory::class, inversedBy: 'forums')]
    #[ORM\JoinColumn(name: 'forum_category', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CForumCategory $forumCategory = null;

    #[ORM\Column(name: 'allow_anonymous', type: 'integer', nullable: true)]
    protected ?int $allowAnonymous = null;

    #[ORM\Column(name: 'allow_edit', type: 'integer', nullable: true)]
    protected ?int $allowEdit = null;

    #[ORM\Column(name: 'approval_direct_post', type: 'string', length: 20, nullable: true)]
    protected ?string $approvalDirectPost = null;

    #[ORM\Column(name: 'allow_attachments', type: 'integer', nullable: true)]
    protected ?int $allowAttachments = null;

    #[ORM\Column(name: 'allow_new_threads', type: 'integer', nullable: true)]
    protected ?int $allowNewThreads = null;

    #[ORM\Column(name: 'default_view', type: 'string', length: 20, nullable: true)]
    protected ?string $defaultView = null;

    #[ORM\Column(name: 'forum_of_group', type: 'string', length: 20, nullable: true)]
    protected ?string $forumOfGroup;

    #[ORM\Column(name: 'forum_group_public_private', type: 'string', length: 20, nullable: true)]
    protected ?string $forumGroupPublicPrivate;

    #[ORM\Column(name: 'locked', type: 'integer', nullable: false)]
    protected int $locked;

    #[ORM\Column(name: 'forum_image', type: 'string', length: 255, nullable: false)]
    protected string $forumImage;

    #[ORM\Column(name: 'start_time', type: 'datetime', nullable: true)]
    protected ?DateTime $startTime = null;

    #[ORM\Column(name: 'end_time', type: 'datetime', nullable: true)]
    protected ?DateTime $endTime = null;

    #[ORM\ManyToOne(targetEntity: CLp::class, cascade: ['remove'], inversedBy: 'forums')]
    #[ORM\JoinColumn(name: 'lp_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CLp $lp = null;

    #[ORM\Column(name: 'moderated', type: 'boolean', nullable: true)]
    protected ?bool $moderated = null;

    /**
     * @var Collection<int, CForumThread>
     */
    #[ORM\OneToMany(mappedBy: 'forum', targetEntity: CForumThread::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $threads;

    /**
     * @var Collection<int, CForumPost>
     */
    #[ORM\OneToMany(mappedBy: 'forum', targetEntity: CForumPost::class, cascade: ['persist'], orphanRemoval: true)]
    protected Collection $posts;

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
        return $this->getTitle();
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function setForumThreads(int $forumThreads): self
    {
        $this->forumThreads = $forumThreads;

        return $this;
    }

    public function getForumThreads(): ?int
    {
        return $this->forumThreads;
    }

    public function hasThread(CForumThread $thread): bool
    {
        return $this->threads->contains($thread);
    }

    public function setForumPosts(int $forumPosts): self
    {
        $this->forumPosts = $forumPosts;

        return $this;
    }

    public function getForumPosts(): ?int
    {
        return $this->forumPosts;
    }

    public function setForumCategory(?CForumCategory $forumCategory = null): self
    {
        $forumCategory?->getForums()->add($this);
        $this->forumCategory = $forumCategory;

        return $this;
    }

    public function getForumCategory(): ?CForumCategory
    {
        return $this->forumCategory;
    }

    public function setAllowAnonymous(int $allowAnonymous): self
    {
        $this->allowAnonymous = $allowAnonymous;

        return $this;
    }

    public function getAllowAnonymous(): ?int
    {
        return $this->allowAnonymous;
    }

    public function setAllowEdit(int $allowEdit): self
    {
        $this->allowEdit = $allowEdit;

        return $this;
    }

    public function getAllowEdit(): ?int
    {
        return $this->allowEdit;
    }

    public function setApprovalDirectPost(string $approvalDirectPost): self
    {
        $this->approvalDirectPost = $approvalDirectPost;

        return $this;
    }

    public function getApprovalDirectPost(): ?string
    {
        return $this->approvalDirectPost;
    }

    public function setAllowAttachments(int $allowAttachments): self
    {
        $this->allowAttachments = $allowAttachments;

        return $this;
    }

    public function getAllowAttachments(): ?int
    {
        return $this->allowAttachments;
    }

    public function setAllowNewThreads(int $allowNewThreads): self
    {
        $this->allowNewThreads = $allowNewThreads;

        return $this;
    }

    public function getAllowNewThreads(): ?int
    {
        return $this->allowNewThreads;
    }

    public function setDefaultView(string $defaultView): self
    {
        $this->defaultView = $defaultView;

        return $this;
    }

    public function getDefaultView(): ?string
    {
        return $this->defaultView;
    }

    public function setForumOfGroup(string $forumOfGroup): self
    {
        $this->forumOfGroup = $forumOfGroup;

        return $this;
    }

    public function getForumOfGroup(): ?string
    {
        return $this->forumOfGroup;
    }

    public function getForumGroupPublicPrivate(): string
    {
        return $this->forumGroupPublicPrivate;
    }

    public function setForumGroupPublicPrivate(string $forumGroupPublicPrivate): static
    {
        $this->forumGroupPublicPrivate = $forumGroupPublicPrivate;

        return $this;
    }

    public function setLocked(int $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked(): int
    {
        return $this->locked;
    }

    public function setForumImage(string $forumImage): self
    {
        $this->forumImage = $forumImage;

        return $this;
    }

    public function getForumImage(): string
    {
        return $this->forumImage;
    }

    public function setStartTime(?DateTime $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getStartTime(): ?DateTime
    {
        return $this->startTime;
    }

    public function setEndTime(?DateTime $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getEndTime(): ?DateTime
    {
        return $this->endTime;
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

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getThreads(): Collection
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
     * @return Collection<int, CForumPost>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
