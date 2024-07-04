<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiObjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiObjectRepository::class)]
class XApiObject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $identifier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activityId = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasActivityDefinition = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasActivityName = null;

    #[ORM\Column(nullable: true)]
    private ?array $activityName = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasActivityDescription = null;

    #[ORM\Column(nullable: true)]
    private ?array $activityDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activityType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activityMoreInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mbox = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mboxSha1Sum = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $openId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountHomePage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referencedStatementId = null;

    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?self $actor = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiVerb $verb = null;

    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?self $object = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiExtensions $activityExtensions = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'members')]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?self $group = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'group', targetEntity: self::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private Collection $members;

    #[ORM\ManyToOne(inversedBy: 'parentActivities')]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiContext $parentContext = null;

    #[ORM\ManyToOne(inversedBy: 'groupingActivities')]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiContext $groupingContext = null;

    #[ORM\ManyToOne(inversedBy: 'categoryActivities')]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiContext $categoryContext = null;

    #[ORM\ManyToOne(inversedBy: 'otherActivities')]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiContext $otherContext = null;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getActivityId(): ?string
    {
        return $this->activityId;
    }

    public function setActivityId(?string $activityId): static
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function hasActivityDefinition(): ?bool
    {
        return $this->hasActivityDefinition;
    }

    public function setHasActivityDefinition(?bool $hasActivityDefinition): static
    {
        $this->hasActivityDefinition = $hasActivityDefinition;

        return $this;
    }

    public function hasActivityName(): ?bool
    {
        return $this->hasActivityName;
    }

    public function setHasActivityName(?bool $hasActivityName): static
    {
        $this->hasActivityName = $hasActivityName;

        return $this;
    }

    public function getActivityName(): ?array
    {
        return $this->activityName;
    }

    public function setActivityName(?array $activityName): static
    {
        $this->activityName = $activityName;

        return $this;
    }

    public function hasActivityDescription(): ?bool
    {
        return $this->hasActivityDescription;
    }

    public function setHasActivityDescription(?bool $hasActivityDescription): static
    {
        $this->hasActivityDescription = $hasActivityDescription;

        return $this;
    }

    public function getActivityDescription(): ?array
    {
        return $this->activityDescription;
    }

    public function setActivityDescription(?array $activityDescription): static
    {
        $this->activityDescription = $activityDescription;

        return $this;
    }

    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    public function setActivityType(?string $activityType): static
    {
        $this->activityType = $activityType;

        return $this;
    }

    public function getActivityMoreInfo(): ?string
    {
        return $this->activityMoreInfo;
    }

    public function setActivityMoreInfo(?string $activityMoreInfo): static
    {
        $this->activityMoreInfo = $activityMoreInfo;

        return $this;
    }

    public function getMbox(): ?string
    {
        return $this->mbox;
    }

    public function setMbox(?string $mbox): static
    {
        $this->mbox = $mbox;

        return $this;
    }

    public function getMboxSha1Sum(): ?string
    {
        return $this->mboxSha1Sum;
    }

    public function setMboxSha1Sum(?string $mboxSha1Sum): static
    {
        $this->mboxSha1Sum = $mboxSha1Sum;

        return $this;
    }

    public function getOpenId(): ?string
    {
        return $this->openId;
    }

    public function setOpenId(?string $openId): static
    {
        $this->openId = $openId;

        return $this;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function setAccountName(?string $accountName): static
    {
        $this->accountName = $accountName;

        return $this;
    }

    public function getAccountHomePage(): ?string
    {
        return $this->accountHomePage;
    }

    public function setAccountHomePage(?string $accountHomePage): static
    {
        $this->accountHomePage = $accountHomePage;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getReferencedStatementId(): ?string
    {
        return $this->referencedStatementId;
    }

    public function setReferencedStatementId(?string $referencedStatementId): static
    {
        $this->referencedStatementId = $referencedStatementId;

        return $this;
    }

    public function getActor(): ?self
    {
        return $this->actor;
    }

    public function setActor(?self $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    public function getVerb(): ?XApiVerb
    {
        return $this->verb;
    }

    public function setVerb(?XApiVerb $verb): static
    {
        $this->verb = $verb;

        return $this;
    }

    public function getObject(): ?self
    {
        return $this->object;
    }

    public function setObject(?self $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getActivityExtensions(): ?XApiExtensions
    {
        return $this->activityExtensions;
    }

    public function setActivityExtensions(?XApiExtensions $activityExtensions): static
    {
        $this->activityExtensions = $activityExtensions;

        return $this;
    }

    public function getGroup(): ?self
    {
        return $this->group;
    }

    public function setGroup(?self $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(self $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setGroup($this);
        }

        return $this;
    }

    public function removeMember(self $member): static
    {
        if ($this->members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getGroup() === $this) {
                $member->setGroup(null);
            }
        }

        return $this;
    }

    public function getParentContext(): ?XApiContext
    {
        return $this->parentContext;
    }

    public function setParentContext(?XApiContext $parentContext): static
    {
        $this->parentContext = $parentContext;

        return $this;
    }

    public function getGroupingContext(): ?XApiContext
    {
        return $this->groupingContext;
    }

    public function setGroupingContext(?XApiContext $groupingContext): static
    {
        $this->groupingContext = $groupingContext;

        return $this;
    }

    public function getCategoryContext(): ?XApiContext
    {
        return $this->categoryContext;
    }

    public function setCategoryContext(?XApiContext $categoryContext): static
    {
        $this->categoryContext = $categoryContext;

        return $this;
    }

    public function getOtherContext(): ?XApiContext
    {
        return $this->otherContext;
    }

    public function setOtherContext(?XApiContext $otherContext): static
    {
        $this->otherContext = $otherContext;

        return $this;
    }
}
