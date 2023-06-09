<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_wiki', options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(columns: ['c_id'], name: 'course')]
#[ORM\Index(columns: ['reflink'], name: 'reflink')]
#[ORM\Index(columns: ['group_id'], name: 'group_id')]
#[ORM\Index(columns: ['page_id'], name: 'page_id')]
#[ORM\Index(columns: ['session_id'], name: 'session_id')]
#[ORM\Entity(repositoryClass: CWikiRepository::class)]
class CWiki extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\Column(name: 'c_id', type: 'integer')]
    protected int $cId;

    #[ORM\Column(name: 'page_id', type: 'integer', nullable: true)]
    protected ?int $pageId = null;

    #[ORM\Column(name: 'reflink', type: 'string', length: 255, nullable: false)]
    protected string $reflink;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'content', type: 'text', nullable: false)]
    protected string $content;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: false)]
    protected int $userId;

    #[ORM\Column(name: 'group_id', type: 'integer', nullable: true)]
    protected ?int $groupId = null;

    #[ORM\Column(name: 'dtime', type: 'datetime', nullable: true)]
    protected ?DateTime $dtime = null;

    #[ORM\Column(name: 'addlock', type: 'integer', nullable: false)]
    protected int $addlock;

    #[ORM\Column(name: 'editlock', type: 'integer', nullable: false)]
    protected int $editlock;

    #[ORM\Column(name: 'visibility', type: 'integer', nullable: false)]
    protected int $visibility;

    #[ORM\Column(name: 'addlock_disc', type: 'integer', nullable: false)]
    protected int $addlockDisc;

    #[ORM\Column(name: 'visibility_disc', type: 'integer', nullable: false)]
    protected int $visibilityDisc;

    #[ORM\Column(name: 'ratinglock_disc', type: 'integer', nullable: false)]
    protected int $ratinglockDisc;

    #[ORM\Column(name: 'assignment', type: 'integer', nullable: false)]
    protected int $assignment;

    #[ORM\Column(name: 'comment', type: 'text', nullable: false)]
    protected string $comment;

    #[ORM\Column(name: 'progress', type: 'text', nullable: false)]
    protected string $progress;

    #[ORM\Column(name: 'score', type: 'integer', nullable: true)]
    protected ?int $score = null;

    #[ORM\Column(name: 'version', type: 'integer', nullable: true)]
    protected ?int $version = null;

    #[ORM\Column(name: 'is_editing', type: 'integer', nullable: false)]
    protected int $isEditing;

    #[ORM\Column(name: 'time_edit', type: 'datetime', nullable: true)]
    protected ?DateTime $timeEdit = null;

    #[ORM\Column(name: 'hits', type: 'integer', nullable: true)]
    protected ?int $hits = null;

    #[ORM\Column(name: 'linksto', type: 'text', nullable: false)]
    protected string $linksto;

    #[ORM\Column(name: 'tag', type: 'text', nullable: false)]
    protected string $tag;

    #[ORM\Column(name: 'user_ip', type: 'string', length: 45, nullable: false)]
    protected string $userIp;

    #[ORM\Column(name: 'session_id', type: 'integer', nullable: true)]
    protected ?int $sessionId = null;

    /**
     * @ORM\ManyToMany(targetEntity="Chamilo\CourseBundle\Entity\CWikiCategory", inversedBy="wikiPages")
     * @ORM\JoinTable(
     *     name="c_wiki_rel_category",
     *     joinColumns={@ORM\JoinColumn(name="wiki_id", referencedColumnName="iid", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $categories;

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPageId(): ?int
    {
        return $this->pageId;
    }

    public function setPageId(int $pageId): static
    {
        $this->pageId = $pageId;

        return $this;
    }

    public function getReflink(): string
    {
        return $this->reflink;
    }

    public function setReflink(string $reflink): static
    {
        $this->reflink = $reflink;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId): static
    {
        $this->groupId = $groupId;

        return $this;
    }

    public function getDtime(): ?DateTime
    {
        return $this->dtime;
    }

    public function setDtime(DateTime $dtime): static
    {
        $this->dtime = $dtime;

        return $this;
    }

    public function getAddlock(): int
    {
        return $this->addlock;
    }

    public function setAddlock(int $addlock): static
    {
        $this->addlock = $addlock;

        return $this;
    }

    public function getEditlock(): int
    {
        return $this->editlock;
    }

    public function setEditlock(int $editlock): static
    {
        $this->editlock = $editlock;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getAddlockDisc(): int
    {
        return $this->addlockDisc;
    }

    public function setAddlockDisc(int $addlockDisc): static
    {
        $this->addlockDisc = $addlockDisc;

        return $this;
    }

    public function getVisibilityDisc(): int
    {
        return $this->visibilityDisc;
    }

    public function setVisibilityDisc(int $visibilityDisc): static
    {
        $this->visibilityDisc = $visibilityDisc;

        return $this;
    }

    public function getRatinglockDisc(): int
    {
        return $this->ratinglockDisc;
    }

    public function setRatinglockDisc(int $ratinglockDisc): static
    {
        $this->ratinglockDisc = $ratinglockDisc;

        return $this;
    }

    public function getAssignment(): int
    {
        return $this->assignment;
    }

    public function setAssignment(int $assignment): static
    {
        $this->assignment = $assignment;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getProgress(): string
    {
        return $this->progress;
    }

    public function setProgress(string $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getIsEditing(): int
    {
        return $this->isEditing;
    }

    public function setIsEditing(int $isEditing): static
    {
        $this->isEditing = $isEditing;

        return $this;
    }

    public function getTimeEdit(): ?DateTime
    {
        return $this->timeEdit;
    }

    public function setTimeEdit(DateTime $timeEdit): static
    {
        $this->timeEdit = $timeEdit;

        return $this;
    }

    public function getHits(): ?int
    {
        return $this->hits;
    }

    public function setHits(int $hits): static
    {
        $this->hits = $hits;

        return $this;
    }

    public function getLinksto(): string
    {
        return $this->linksto;
    }

    public function setLinksto(string $linksto): static
    {
        $this->linksto = $linksto;

        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function getUserIp(): string
    {
        return $this->userIp;
    }

    public function setUserIp(string $userIp): static
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getSessionId(): ?int
    {
        return $this->sessionId;
    }

    public function setSessionId(int $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getCId(): int
    {
        return $this->cId;
    }

    public function setCId(int $cId): static
    {
        $this->cId = $cId;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    /**
     * @return Collection<int, CWikiCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(CWikiCategory $category): self
    {
        $category->addWikiPage($this);
        $this->categories->add($category);

        return $this;
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
