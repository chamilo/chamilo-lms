<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Career.
 *
 * @ORM\Table(name="career")
 * @ORM\Entity
 */
class Career
{
    use TimestampableEntity;

    public const CAREER_STATUS_ACTIVE = 1;
    public const CAREER_STATUS_INACTIVE = 0;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected int $status;

    /**
     * @var Collection|Promotion[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\Promotion", mappedBy="career", cascade={"persist"}
     * )
     */
    protected Collection $promotions;

    public function __construct()
    {
        $this->status = self::CAREER_STATUS_ACTIVE;
        $this->promotions = new ArrayCollection();
        $this->description = '';
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getPromotions(): array | ArrayCollection | Collection
    {
        return $this->promotions;
    }

    public function setPromotions(Collection $promotions): self
    {
        $this->promotions = $promotions;

        return $this;
    }
}
