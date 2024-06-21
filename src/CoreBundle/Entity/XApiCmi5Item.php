<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiCmi5ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\Tree(type: 'nested')]
#[ORM\Entity(repositoryClass: XApiCmi5ItemRepository::class)]
class XApiCmi5Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $identifier = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private array $title = [];

    #[ORM\Column]
    private array $description = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activityType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $launchMethod = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $moveOn = null;

    #[ORM\Column(nullable: true)]
    private ?float $masteryScore = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $launchParameters = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entitlementKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[Gedmo\TreeLeft]
    #[ORM\Column]
    private ?int $lft = null;

    #[Gedmo\TreeLevel]
    #[ORM\Column]
    private ?int $lvl = null;

    #[Gedmo\TreeRight]
    #[ORM\Column]
    private ?int $rgt = null;

    #[Gedmo\TreeRoot]
    #[ORM\ManyToOne(targetEntity: self::class)]
    private ?self $root = null;

    #[Gedmo\TreeParent]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\ManyToOne(inversedBy: 'items')]
    private ?XApiToolLaunch $tool = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): array
    {
        return $this->title;
    }

    public function setTitle(array $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function setDescription(array $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

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

    public function getLaunchMethod(): ?string
    {
        return $this->launchMethod;
    }

    public function setLaunchMethod(?string $launchMethod): static
    {
        $this->launchMethod = $launchMethod;

        return $this;
    }

    public function getMoveOn(): ?string
    {
        return $this->moveOn;
    }

    public function setMoveOn(?string $moveOn): static
    {
        $this->moveOn = $moveOn;

        return $this;
    }

    public function getMasteryScore(): ?float
    {
        return $this->masteryScore;
    }

    public function setMasteryScore(?float $masteryScore): static
    {
        $this->masteryScore = $masteryScore;

        return $this;
    }

    public function getLaunchParameters(): ?string
    {
        return $this->launchParameters;
    }

    public function setLaunchParameters(?string $launchParameters): static
    {
        $this->launchParameters = $launchParameters;

        return $this;
    }

    public function getEntitlementKey(): ?string
    {
        return $this->entitlementKey;
    }

    public function setEntitlementKey(?string $entitlementKey): static
    {
        $this->entitlementKey = $entitlementKey;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(int $lft): static
    {
        $this->lft = $lft;

        return $this;
    }

    public function getñlvl(): ?int
    {
        return $this->lvl;
    }

    public function setñlvl(int $lvl): static
    {
        $this->lvl = $lvl;

        return $this;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): static
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRoot(): ?self
    {
        return $this->root;
    }

    public function setRoot(?self $root): static
    {
        $this->root = $root;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getTool(): ?XApiToolLaunch
    {
        return $this->tool;
    }

    public function setTool(?XApiToolLaunch $tool): static
    {
        $this->tool = $tool;

        return $this;
    }
}
