<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\XApiToolLaunchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: XApiToolLaunchRepository::class)]
class XApiToolLaunch
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\ManyToOne]
    private ?Session $session = null;

    #[ORM\Column(length: 255)]
    private ?string $launchUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activityId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activityType = null;

    #[ORM\Column]
    private ?bool $allowMultipleAttempts = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lrsUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lrsAuthUsername = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lrsAuthPassword = null;

    /**
     * @var Collection<int, XApiCmi5Item>
     */
    #[ORM\OneToMany(mappedBy: 'tool', targetEntity: XApiCmi5Item::class)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getLaunchUrl(): ?string
    {
        return $this->launchUrl;
    }

    public function setLaunchUrl(string $launchUrl): static
    {
        $this->launchUrl = $launchUrl;

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

    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    public function setActivityType(?string $activityType): static
    {
        $this->activityType = $activityType;

        return $this;
    }

    public function isAllowMultipleAttempts(): ?bool
    {
        return $this->allowMultipleAttempts;
    }

    public function setAllowMultipleAttempts(bool $allowMultipleAttempts): static
    {
        $this->allowMultipleAttempts = $allowMultipleAttempts;

        return $this;
    }

    public function getLrsUrl(): ?string
    {
        return $this->lrsUrl;
    }

    public function setLrsUrl(?string $lrsUrl): static
    {
        $this->lrsUrl = $lrsUrl;

        return $this;
    }

    public function getLrsAuthUsername(): ?string
    {
        return $this->lrsAuthUsername;
    }

    public function setLrsAuthUsername(?string $lrsAuthUsername): static
    {
        $this->lrsAuthUsername = $lrsAuthUsername;

        return $this;
    }

    public function getLrsAuthPassword(): ?string
    {
        return $this->lrsAuthPassword;
    }

    public function setLrsAuthPassword(?string $lrsAuthPassword): static
    {
        $this->lrsAuthPassword = $lrsAuthPassword;

        return $this;
    }

    /**
     * @return Collection<int, XApiCmi5Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(XApiCmi5Item $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setTool($this);
        }

        return $this;
    }

    public function removeItem(XApiCmi5Item $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getTool() === $this) {
                $item->setTool(null);
            }
        }

        return $this;
    }
}
