<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiContextRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: XApiContextRepository::class)]
class XApiContext
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $identifier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $registration = null;

    #[ORM\Column(nullable: true)]
    private ?bool $hasContextActivities = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $revision = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $platform = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $language = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statement = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiObject $instructor = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiObject $team = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(referencedColumnName: 'identifier')]
    private ?XApiExtensions $extensions = null;

    /**
     * @var Collection<int, XApiObject>
     */
    #[ORM\OneToMany(mappedBy: 'parentContext', targetEntity: XApiObject::class)]
    private Collection $parentActivities;

    /**
     * @var Collection<int, XApiObject>
     */
    #[ORM\OneToMany(mappedBy: 'groupingContext', targetEntity: XApiObject::class)]
    private Collection $groupingActivities;

    /**
     * @var Collection<int, XApiObject>
     */
    #[ORM\OneToMany(mappedBy: 'categoryContext', targetEntity: XApiObject::class)]
    private Collection $categoryActivities;

    /**
     * @var Collection<int, XApiObject>
     */
    #[ORM\OneToMany(mappedBy: 'otherContext', targetEntity: XApiObject::class)]
    private Collection $otherActivities;

    public function __construct()
    {
        $this->parentActivities = new ArrayCollection();
        $this->groupingActivities = new ArrayCollection();
        $this->categoryActivities = new ArrayCollection();
        $this->otherActivities = new ArrayCollection();
    }

    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    public function setRegistration(?string $registration): static
    {
        $this->registration = $registration;

        return $this;
    }

    public function hasContextActivities(): ?bool
    {
        return $this->hasContextActivities;
    }

    public function setHasContextActivities(?bool $hasContextActivities): static
    {
        $this->hasContextActivities = $hasContextActivities;

        return $this;
    }

    public function getRevision(): ?string
    {
        return $this->revision;
    }

    public function setRevision(?string $revision): static
    {
        $this->revision = $revision;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): static
    {
        $this->platform = $platform;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getStatement(): ?string
    {
        return $this->statement;
    }

    public function setStatement(?string $statement): static
    {
        $this->statement = $statement;

        return $this;
    }

    public function getInstructor(): ?XApiObject
    {
        return $this->instructor;
    }

    public function setInstructor(?XApiObject $instructor): static
    {
        $this->instructor = $instructor;

        return $this;
    }

    public function getTeam(): ?XApiObject
    {
        return $this->team;
    }

    public function setTeam(?XApiObject $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getExtensions(): ?XApiExtensions
    {
        return $this->extensions;
    }

    public function setExtensions(?XApiExtensions $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     * @return Collection<int, XApiObject>
     */
    public function getParentActivities(): Collection
    {
        return $this->parentActivities;
    }

    public function addParentActivity(XApiObject $parentActivity): static
    {
        if (!$this->parentActivities->contains($parentActivity)) {
            $this->parentActivities->add($parentActivity);
            $parentActivity->setParentContext($this);
        }

        return $this;
    }

    public function removeParentActivity(XApiObject $parentActivity): static
    {
        if ($this->parentActivities->removeElement($parentActivity)) {
            // set the owning side to null (unless already changed)
            if ($parentActivity->getParentContext() === $this) {
                $parentActivity->setParentContext(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, XApiObject>
     */
    public function getGroupingActivities(): Collection
    {
        return $this->groupingActivities;
    }

    public function addGroupingActivity(XApiObject $groupingActivity): static
    {
        if (!$this->groupingActivities->contains($groupingActivity)) {
            $this->groupingActivities->add($groupingActivity);
            $groupingActivity->setGroupingContext($this);
        }

        return $this;
    }

    public function removeGroupingActivity(XApiObject $groupingActivity): static
    {
        if ($this->groupingActivities->removeElement($groupingActivity)) {
            // set the owning side to null (unless already changed)
            if ($groupingActivity->getGroupingContext() === $this) {
                $groupingActivity->setGroupingContext(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, XApiObject>
     */
    public function getCategoryActivities(): Collection
    {
        return $this->categoryActivities;
    }

    public function addCategoryActivity(XApiObject $categoryActivity): static
    {
        if (!$this->categoryActivities->contains($categoryActivity)) {
            $this->categoryActivities->add($categoryActivity);
            $categoryActivity->setCategoryContext($this);
        }

        return $this;
    }

    public function removeCategoryActivity(XApiObject $categoryActivity): static
    {
        if ($this->categoryActivities->removeElement($categoryActivity)) {
            // set the owning side to null (unless already changed)
            if ($categoryActivity->getCategoryContext() === $this) {
                $categoryActivity->setCategoryContext(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, XApiObject>
     */
    public function getOtherActivities(): Collection
    {
        return $this->otherActivities;
    }

    public function addOtherActivity(XApiObject $otherActivity): static
    {
        if (!$this->otherActivities->contains($otherActivity)) {
            $this->otherActivities->add($otherActivity);
            $otherActivity->setOtherContext($this);
        }

        return $this;
    }

    public function removeOtherActivity(XApiObject $otherActivity): static
    {
        if ($this->otherActivities->removeElement($otherActivity)) {
            // set the owning side to null (unless already changed)
            if ($otherActivity->getOtherContext() === $this) {
                $otherActivity->setOtherContext(null);
            }
        }

        return $this;
    }
}
