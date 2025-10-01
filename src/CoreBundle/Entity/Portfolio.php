<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Table(name: 'portfolio')]
#[ORM\Index(columns: ['user_id'], name: 'user')]
#[ORM\Index(columns: ['c_id'], name: 'course')]
#[ORM\Index(columns: ['session_id'], name: 'session')]
#[ORM\Index(columns: ['category_id'], name: 'category')]
#[ORM\Entity]
class Portfolio extends AbstractResource implements ResourceInterface, \Stringable
{
    use UserTrait;

    public const TYPE_ITEM = 1;
    public const TYPE_COMMENT = 2;

    public const VISIBILITY_HIDDEN = 0;
    public const VISIBILITY_VISIBLE = 1;
    public const VISIBILITY_HIDDEN_EXCEPT_TEACHER = 2;
    public const VISIBILITY_PER_USER = 3;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'content', type: 'text')]
    protected string $content;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id')]
    protected ?Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id')]
    protected ?Session $session;

    #[ORM\Column(name: 'creation_date', type: 'datetime')]
    protected DateTime $creationDate;

    #[ORM\Column(name: 'update_date', type: 'datetime')]
    protected DateTime $updateDate;

    #[ORM\Column(name: 'visibility', type: 'smallint', options: ['default' => self::VISIBILITY_VISIBLE])]
    protected int $visibility = self::VISIBILITY_VISIBLE;

    #[ORM\ManyToOne(targetEntity: PortfolioCategory::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?PortfolioCategory $category;

    #[ORM\OneToMany(mappedBy: 'item', targetEntity: PortfolioComment::class)]
    private Collection $comments;

    #[ORM\Column(name: 'origin', type: 'integer', nullable: true)]
    private ?int $origin;

    #[ORM\Column(name: 'origin_type', type: 'integer', nullable: true)]
    private ?int $originType;

    #[ORM\Column(name: 'score', type: 'float', nullable: true)]
    private ?float $score;

    #[ORM\Column(name: 'is_highlighted', type: 'boolean', options: ['default' => false])]
    private bool $isHighlighted = false;

    #[ORM\Column(name: 'is_template', type: 'boolean', options: ['default' => false])]
    private bool $isTemplate = false;

    #[ORM\ManyToOne(targetEntity: Portfolio::class, inversedBy: 'duplicates')]
    #[ORM\JoinColumn(name: 'duplicated_from', onDelete: 'SET NULL')]
    private ?Portfolio $duplicatedFrom = null;

    /**
     * @var Collection<int, Portfolio>
     */
    #[ORM\OneToMany(mappedBy: 'duplicatedFrom', targetEntity: Portfolio::class)]
    private Collection $duplicates;

    public function __construct()
    {
        $this->category = null;
        $this->comments = new ArrayCollection();
        $this->duplicates = new ArrayCollection();
    }

    public function setCourse(?Course $course = null): static
    {
        $this->course = $course;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session = null): static
    {
        $this->session = $session;

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(bool $stripTags = false): string
    {
        if ($stripTags) {
            return strip_tags($this->title);
        }

        return $this->title;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setCreationDate(DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): DateTime
    {
        return $this->creationDate;
    }

    public function setUpdateDate(DateTime $updateDate): static
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getUpdateDate(): DateTime
    {
        return $this->updateDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setVisibility(int $visibility): Portfolio
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function getCategory(): ?PortfolioCategory
    {
        return $this->category;
    }

    public function setCategory(?PortfolioCategory $category = null): static
    {
        $this->category = $category;

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getLastComments(int $number = 3, bool $avoidPerUserVisibility = false): Collection
    {
        $criteria = Criteria::create();
        $criteria
            ->orderBy(['date' => 'DESC'])
            ->setMaxResults($number)
        ;

        if ($avoidPerUserVisibility) {
            $criteria->where(
                Criteria::expr()->neq('visibility', PortfolioComment::VISIBILITY_PER_USER)
            );
        }

        return $this->comments->matching($criteria);
    }

    public function getOrigin(): ?int
    {
        return $this->origin;
    }

    public function setOrigin(?int $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getOriginType(): ?int
    {
        return $this->originType;
    }

    public function setOriginType(?int $originType): static
    {
        $this->originType = $originType;

        return $this;
    }

    public function getExcerpt(int $count = 380): string
    {
        return api_get_short_text_from_html($this->content, $count);
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): void
    {
        $this->score = $score;
    }

    public function isHighlighted(): bool
    {
        return $this->isHighlighted;
    }

    public function setIsHighlighted(bool $isHighlighted): self
    {
        $this->isHighlighted = $isHighlighted;

        return $this;
    }

    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): self
    {
        $this->isTemplate = $isTemplate;

        return $this;
    }

    public function getDuplicatedFrom(): ?Portfolio
    {
        return $this->duplicatedFrom;
    }

    public function setDuplicatedFrom(?Portfolio $duplicatedFrom): Portfolio
    {
        $this->duplicatedFrom = $duplicatedFrom;

        return $this;
    }

    /**
     * @return Collection<int, Portfolio>
     */
    public function getDuplicates(): Collection
    {
        return $this->duplicates;
    }

    public function addDuplicate(Portfolio $duplicate): Portfolio
    {
        if (!$this->duplicates->contains($duplicate)) {
            $this->duplicates->add($duplicate);
            $duplicate->setDuplicatedFrom($this);
        }

        return $this;
    }

    public function removeDuplicate(Portfolio $duplicate): Portfolio
    {
        if ($this->duplicates->removeElement($duplicate)) {
            // set the owning side to null (unless already changed)
            if ($duplicate->getDuplicatedFrom() === $this) {
                $duplicate->setDuplicatedFrom(null);
            }
        }

        return $this;
    }

    public function hasDuplicates(): bool
    {
        return $this->duplicates->count() > 0;
    }

    public function isDuplicated(): bool
    {
        return null !== $this->duplicatedFrom;
    }

    public function isDuplicatedInSession(Session $session): bool
    {
        return $this->duplicates->exists(fn ($key, Portfolio $duplicated): bool => $duplicated->session === $session);
    }

    public function isDuplicatedInSessionId(int $sessionId): bool
    {
        return $this->duplicates->exists(fn ($key, Portfolio $duplicated): bool => $duplicated->session && $duplicated->session->getId() === $sessionId);
    }

    public function reset(): void
    {
        $this->id = null;
        $this->duplicates = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function duplicateInSession(Session $session): Portfolio
    {
        $duplicate = clone $this;
        $duplicate->reset();

        $duplicate->setSession($session);
        $this->addDuplicate($duplicate);

        return $duplicate;
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): static
    {
        return $this->setTitle($name);
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getId();
    }
}
