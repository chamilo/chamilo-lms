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
 * Promotion.
 *
 * @ORM\Table(name="promotion")
 * @ORM\Entity
 */
class Promotion
{
    use TimestampableEntity;

    public const PROMOTION_STATUS_ACTIVE = 1;
    public const PROMOTION_STATUS_INACTIVE = 0;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected ?string $description = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Career")
     * @ORM\JoinColumn(name="career_id", referencedColumnName="id")
     */
    protected Career $career;

    /**
     * @var Collection|Session[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Chamilo\CoreBundle\Entity\Session", mappedBy="promotion", cascade={"persist"}
     * )
     */
    protected Collection $sessions;

    /**
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected int $status;

    public function __construct()
    {
        $this->status = self::PROMOTION_STATUS_ACTIVE;
        $this->sessions = new ArrayCollection();
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

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setCareer(Career $career): self
    {
        $this->career = $career;

        return $this;
    }

    public function getCareer(): Career
    {
        return $this->career;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Session[]|Collection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    public function setSessions(Collection $sessions): self
    {
        $this->sessions = $sessions;

        return $this;
    }
}
