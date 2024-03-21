<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_thematic')]
#[ORM\Index(columns: ['active'], name: 'active')]
#[ORM\Entity(repositoryClass: CThematicRepository::class)]
class CThematic extends AbstractResource implements ResourceInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'content', type: 'text', nullable: true)]
    protected ?string $content = null;

    #[ORM\Column(name: 'active', type: 'boolean', nullable: false)]
    protected bool $active;

    /**
     * @var Collection<int, CThematicPlan>
     */
    #[ORM\OneToMany(mappedBy: 'thematic', targetEntity: CThematicPlan::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $plans;

    /**
     * @var Collection<int, CThematicAdvance>
     */
    #[ORM\OrderBy(['startDate' => 'ASC'])]
    #[ORM\OneToMany(mappedBy: 'thematic', targetEntity: CThematicAdvance::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $advances;

    public function __construct()
    {
        $this->plans = new ArrayCollection();
        $this->advances = new ArrayCollection();
        $this->active = true;
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

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    /**
     * @return Collection<int, CThematicPlan>
     */
    public function getPlans(): Collection
    {
        return $this->plans;
    }

    /**
     * @return Collection<int, CThematicAdvance>
     */
    public function getAdvances(): Collection
    {
        return $this->advances;
    }

    public function getResourceIdentifier(): int|Uuid
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
